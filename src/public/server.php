<?php
include('db.php');
//$link = mysqli_connect('mysql', 'user1', 's123', 'chat');
$host = 'localhost'; //хост
$port = '9000'; //port
$null = NULL; //null заглушка
$users_from_db_online=[];


//Создать сокет TCP/IP stream
$socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
//разрешить повторно использовать адрес
socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);

//привязать сокет к указанному хосту
socket_bind($socket, 0, $port);

//прослушать порт
socket_listen($socket);

//создайте и добавьте в список сокет для прослушивания
$clients = array($socket);

// сбросить всем чатид при запуске сокета
mysqli_query($link,'UPDATE `users` SET `chatId`=""');


//запустить бесконечный цикл, чтобы наш скрипт не останавливался
while (true) {
	//управление многопользовательскими соединениями
	$changed = $clients;
	//возвращает ресурсы сокета в $измененном массиве
	socket_select($changed, $null, $null, 0, 10);
	
	//проверьте наличие нового сокета
	if (in_array($socket, $changed)) {
		$socket_new = socket_accept($socket); //установить новый сокет
		$clients[] = $socket_new; //добавить сокет в клиентский массив
		
		$header = socket_read($socket_new, 1024);  //считывание данных, отправленных сокетом
		perform_handshaking($header, $socket_new, $host, $port);  //выполнение рукопожатия websocket

		//освободите место для нового сокета
		$found_socket = array_search($socket, $changed);
		unset($changed[$found_socket]);
	}
	
	//цикл по всем подключенным сокетам
	foreach ($changed as $changed_socket) {	

		//проверяем наличие любых входящих данных
		while(socket_recv($changed_socket, $buf, 1024, 0) >= 1)
		{

			$received_text = unmask($buf); //unmask data
			$tst_msg = json_decode($received_text, true); //json decode 

			$user_token = clearGP($tst_msg['token']);
			$user_history_page = clearGPint($tst_msg['historyPage']); //для страниц истории (нет в index.php)
			//$user_message = iconv('UTF-8', 'UTF-8//IGNORE',clearGP($tst_msg['message'])); //message text
			$user_message = clearGP($tst_msg['message']); //message text
			$user_color = clearGP($tst_msg['color']); //color	
			$group_avatar = clearGPurl($tst_msg['avatar']); //аватар группы (если есть)
			$group_id=clearGPid($tst_msg['groupid']);//ид группы
			$group_name=clearGP($tst_msg['groupname']);//название группы
			$group_name_users=[];//имена пользователей группы
			$user_id_receive= clearGPid($tst_msg['sendToUserId']); //ид пользователя для переписки
			$id_edit_sms=clearGPid($tst_msg['ideditsms']);// ид редактируемого (удаляемого) сообщения
			$user_update= clearGP($tst_msg['update']); //служебный текст для обновления конкретных блоков
			$user_group_sms_delete= clearGP($tst_msg['delete']); //служебный текст для удаления пользователей, смс, групп
			$user_group_sms_id_delete= clearGPid($tst_msg['iddelsmsuser']); //служебный текст для удаления пользователей, смс, групп
			$user_id_send='';//ид авторизованного пользователя
			$users_id_for_db='';
			$users_chat_id=[];//чатиды совпавших пользователей
			$idonline=[];//иды пользователей онлайн
			//проверка токена
		$res=mysqli_query($link, "SELECT * FROM users WHERE token = '". $user_token ."'");
		if($res->num_rows == 0){
				$users_from_db_online=[];
				$users_from_db_online[0]=$changed_socket;
				$resp = mask(json_encode(array('type'=>'system', 'message'=>'Ошибка токена, <a href="?auth">Авторизуйтесь повторно</a>')));
				send_message($resp);
				// очистить чатид из бд
				mysqli_query($link,'UPDATE `users` SET `chatId`="" where `chatId`=\''.(string)$changed_socket.'\'');
				//закрыть сокет, если нет токена
				$found_socket = array_search($changed_socket, $clients);
				unset($clients[$found_socket]);
				socket_close($changed_socket);
				//break 2;//думаю, не надо
			}else{
				//если токен правильный - подгрузим данные пользователя из БД
				$user_bd = mysqli_fetch_assoc($res);
				$user_bd_isname=(!empty($user_bd['name'])) ? $user_bd['name'] : $user_bd['email'];//имя пользователя
				$user_id_send=$user_bd['id'];//ид авторизованного пользователя


				if (empty((string)$user_bd['chatId'])) {//если пустая строка чатид - отправим приветствие всем друзьяи и запишем в бд
					//print_r('socket '.$changed_socket. 'user '.$user_bd['email']);
						//строчка, сохраняющая в базу сокетид для пользователя
						mysqli_query($link,'UPDATE `users` SET `chatId`=\''.(string)$changed_socket.'\' where `id`=\''.$user_id_send.'\'');
						helloUser($link,$user_bd,'Онлайн','on');
				}

					//если групповой чат - сделать запрос пользователей из базы группа
					$users_id_for_db=$user_id_receive;//собеседники из базы
					if(strrpos($user_id_receive, 'ch')!==false){
						$id_group_chat=str_replace('ch','',$user_id_receive);
						$usergroup=mysqli_query($link, "SELECT usersId FROM groupChat WHERE id= '". $id_group_chat ."'");
						if($usergroup->num_rows > 0){
							$user_group = mysqli_fetch_assoc($usergroup);
							$users_id_for_db=$user_group['usersId'];
						}
					}
					if(!empty($user_update)){
						$users_id_for_db='("'.str_replace(',','","',$users_id_for_db).'")';
						if(($user_update=='updateInfo') or ($user_update=='idonline')){$users_id_for_db='("'.str_replace(',','","',$user_bd['friends']).'")';}//если обновляем информацию о себе - оповестим и заменим блок о себе у всех
					}else{
						$users_id_for_db='("'.str_replace(',','","',$users_id_for_db).'","'.$user_id_send.'")';
					}
					//ищем собеседников в базе и вытаскиваем их чатиды, так же достанем их имена и email для использования в чате
					$result = mysqli_query($link, "SELECT `id`, `chatId`,`name`,`email` FROM users WHERE id IN ". $users_id_for_db );
					//print_r("SELECT * FROM users WHERE id IN ". $users_id_for_db );
					if($result->num_rows > 0){
						while($userdb = mysqli_fetch_array($result)){
							$users_chat_id[]=$userdb['chatId'];
							$group_name_users[]=(!empty($userdb['name'])) ? $userdb['name'] : $userdb['email'];//имена друзей для группы
							if(!empty($userdb['chatId'])){$idonline[]=$userdb['id'];}//если чатид есть - значит онлайн, запишем в переменную
						}
					}

				//если пришло сообщение, которое требуется сделать системным и отправить конкретному юзеру
				if(!empty($user_update)){
					if($user_update=='idonline'){//считать кто онлайн и отправить себе
						$users_from_db_online=[];
						$users_from_db_online[0]=$changed_socket;
						$sy = mask(json_encode(array('type'=>'updateonline', 'idonline'=>$idonline ))); //подготовить json-данные
						send_message($sy); //уведомить пользователей о событии
						break 2; //exist this loop
					}
						$users_from_db_online=[];
						$users_from_db_online=array_intersect($clients,$users_chat_id);
						if(!empty($group_id)){
							$avatar=(empty($group_avatar)) ? './image/avatar.png' : $group_avatar;
							$forHTML='<div class="users__list__item" id="'.$group_id.'"><div class="v">&#128270;</div><div class="i" style="width:30px;height:30px;background:url(\''.$avatar.'\');background-size:cover;"></div><span class="users__list__item__span"><b>'.$group_name.'</b><br><i>'.implode(' ',$group_name_users).'</i></span></div>';
						}else{
							$avatar=(!empty($user_bd['avatar'])) ? $user_bd['avatar'] : './image/avatar.png';// фото пользователя или группы
							$forHTML='<div class="users__list__item" id="'.$user_id_send.'"><div class="v">&#128270;</div><div class="i" style="width:30px;height:30px;background:url(\''.$avatar.'\');background-size:cover;"></div><span class="users__list__item__span">'.$user_bd_isname.'</span><div class="online" style="color:green" title="Онлайн">⚫</div></div>';
						}
						$sysmsg = mask(json_encode(array('userIdSend'=>$user_id_send, 'type'=>'update', 'forblockid'=>$user_update, 'forhtml'=>$forHTML, 'message'=>$user_bd_isname.$user_message))); //подготовить json-данные
						
						send_message($sysmsg); //уведомить пользователей о событии
					break 2; //exist this loop
				}


				//удаление блоков, отдельных смс и выход из группы (удаление себя из группы)
				if(!empty($user_group_sms_delete)){
					if(!empty($user_group_sms_id_delete)){
						$users_from_db_online=[];
						$users_from_db_online=array_intersect($clients,$users_chat_id);
						if($user_group_sms_delete=='message'){
							mysqli_query($link,"DELETE FROM `message` WHERE id='$user_group_sms_id_delete' and (userIdSend='$user_id_send' or userIdReceive='$user_id_send');");
							$user_group_sms_id_delete='m'.$user_group_sms_id_delete;
						}else{
							//запросим список друзей у друга
							if($user_group_sms_delete=='friends'){$tablrow='friends'; $tabl='users'; $ugsidd=$user_group_sms_id_delete;}else{$tablrow='usersId'; $tabl='groupChat';$ugsidd=str_ireplace('ch','',$user_group_sms_id_delete);}
								$frindbfordelme=mysqli_query($link,"SELECT `$tablrow` FROM `$tabl` WHERE id='$ugsidd'");
								if($frindbfordelme->num_rows > 0){
									$frindbfordelmeitem = explode(',',mysqli_fetch_assoc($frindbfordelme)[$tablrow]);
									if (($key = array_search($user_id_send, $frindbfordelmeitem)) !== false) {//найти себя в друзьях или группе и удалить себя из массива
										unset($frindbfordelmeitem[$key]);
									}
									mysqli_query($link,"UPDATE `$tabl` SET `$tablrow` = '".implode(',',$frindbfordelmeitem)."' WHERE id='$ugsidd'");//удалили у друга себя
								}
							//удалим друга у себя
							$myfriends=explode(',',$user_bd['friends']);
							if (($k = array_search($user_group_sms_id_delete, $myfriends)) !== false) {//найти друга (группу) в друзьях и удалить друга из массива
								unset($myfriends[$k]);
							}
							mysqli_query($link,"UPDATE users SET friends = '".implode(',',$myfriends)."' WHERE id='$user_id_send'");//удалили у себя друга (группу)
						}
						$delmsg = mask(json_encode(array('userIdSend'=>$user_group_sms_id_delete, 'userIdReceive'=>$user_id_send, 'type'=>'update', 'forblockid'=>'updateInfo', 'forhtml'=>'', 'message'=>'Пользователь '.$user_bd_isname.' удалил'.$user_message,))); //подготовить json-данные
						//!!!ТОЛЬКО В ЭТОЙ ФУНКЦИИ задействуем ПРИ ОТПРАВКЕ useridsend для отправки ид удаленного смс, юзера или группы
						// userIdReceive нужен для того, чтобы у юзера обновился список, касаемый отправителя и после этого выйдем в цикл
						send_message($delmsg); //уведомить пользователей о событии
					}
					break 2; //exist this loop
				}



			}

		//Если прилетело пустое сообщение - загрузим историю
		if (empty($user_message)){

				//сделали возможность передачи истории в сообщениях самому себе
				$users_from_db_online=[];
				$users_from_db_online[0]=$changed_socket;
				//проверяем, с какой страницы грузить историю
				$user_history_page=(empty($user_history_page)) ? 0 : $user_history_page;
			//подгружаем сообщения из базы данных
			
			if(strrpos($user_id_receive, 'ch')!==false){
				//если есть ch - значит чат, и сделаем запрос всего, что касается чата
				//print_r('SELECT * FROM `message` WHERE userIdReceive=\''.$user_id_receive.'\'  ORDER BY `id` DESC  LIMIT '.$user_history_page.', 10');
				$msgindb = mysqli_query($link, 'SELECT * FROM `message` WHERE userIdReceive=\''.$user_id_receive.'\'  ORDER BY `id` DESC  LIMIT '.$user_history_page.', 10');
			}else{
				//print_r('SELECT * FROM `message` WHERE (`userIdSend`=\''.$user_id_send.'\' AND `userIdReceive`=\''.$user_id_receive .'\') or (`userIdSend`=\''.$user_id_receive.'\' AND `userIdReceive`=\''.$user_id_send .'\' ) ORDER BY `id` DESC  LIMIT '.$user_history_page.', 10 ');
				$msgindb = mysqli_query($link, 'SELECT * FROM `message` WHERE (`userIdSend`=\''.$user_id_send.'\' AND `userIdReceive`=\''.$user_id_receive .'\') or (`userIdSend`=\''.$user_id_receive.'\' AND `userIdReceive`=\''.$user_id_send .'\' ) ORDER BY `id` DESC  LIMIT '.$user_history_page.', 10 ');
			}

			if($msgindb->num_rows > 0){
				while($messagedb = mysqli_fetch_array($msgindb)){
					//подготовить данные для отправки клиенту
					$response_text_history = mask(json_encode(array('type'=>'usermsg', 'smsid'=>$messagedb['id'], 'historyid'=>$messagedb['id'], 'userIdReceive'=>$messagedb['userIdReceive'], 'userIdSend'=>$messagedb['userIdSend'], 'name'=>$messagedb['userIdSend'], 'message'=>'<span class="s">'.$messagedb['message'].'</span><br><span class="user_message_span">'.date("d.m.Y H:i",strtotime($messagedb['datetime'])).'</span>', 'color'=>$user_color)));
					send_message($response_text_history); //send data
				}
			}

		}else{
			//Если чат - сохраним в базу имена отправителей
			if(strrpos($user_id_receive, 'ch')!==false){
				$user_message = '<b>'.$user_bd_isname.':</b><br>'. $user_message;
			}
			$idsmsdb='';
			if(!empty($id_edit_sms)){//если сообщение отредактировали - обновим в базе
				$user_message .='✎';
				mysqli_query($link,"UPDATE `message` SET `message`='$user_message' WHERE `id`='$id_edit_sms'");
				$idsmsdb=$id_edit_sms;
			}else{
				//Если есть новое сообщение - Сохраняем все в базу
				mysqli_query($link, 'INSERT INTO `message`(`userIdSend`, `message`, `userIdReceive`) VALUES(\''.$user_id_send.'\', \''.$user_message.'\', \''.$user_id_receive.'\');');
				$idsmsdb = mysqli_insert_id($link);
			}
			
			// сравниваем массивы пользователей и отправляем совпавшим
			$users_from_db_online=[];
			$users_from_db_online=array_intersect($clients,$users_chat_id);
			//подготовить данные для отправки клиенту
			$response_text = mask(json_encode(array('type'=>'usermsg', 'smsid'=>$idsmsdb,  'userIdReceive'=>$user_id_receive, 'userIdSend'=>$user_id_send, 'name'=>'', 'message'=>'<span class="s">'.$user_message.'</span><br><span class="user_message_span">'.date("d.m.Y H:i").'</span>', 'color'=>$user_color)));
			send_message($response_text); //send data

			}

			break 2; //exist this loop
		}

		
		$buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
		if ($buf === false) { // проверить отключенный клиент
			// удалить клиента из массива $clients
			$found_socket = array_search($changed_socket, $clients);
			socket_getpeername($changed_socket, $ip);
			unset($clients[$found_socket]);
			
			//уведомлять всех пользователей об отключенном соединении
			$resoff=mysqli_query($link, "SELECT * FROM users WHERE `chatId`='".(string)$changed_socket."'");
			if($resoff->num_rows > 0){
				$user_off = mysqli_fetch_assoc($resoff);
				helloUser($link,$user_off,'Офлайн','off');
			}
			// сбрасываем чатид у вышедшего
			mysqli_query($link,'UPDATE `users` SET `chatId`="" where `chatId`=\''.(string)$changed_socket.'\'');

		}
	}
}
// закройте прослушивающий сокет
socket_close($socket);

function send_message($msg)
{
	global $clients;
	global $users_from_db_online;
	//print_r($users_from_db_online);
	//foreach($clients as $changed_socket)
	//в useronline содержится массив resurceid, которые должны получить сообщение
	foreach($users_from_db_online as $changed_socket)
	{
		@socket_write($changed_socket,$msg,strlen($msg));
	}
	return true;
}


//маска для входящего сообщения в чате
function unmask($text) {
	$length = ord($text[1]) & 127;
	if($length == 126) {
		$masks = substr($text, 4, 4);
		$data = substr($text, 8);
	}
	elseif($length == 127) {
		$masks = substr($text, 10, 4);
		$data = substr($text, 14);
	}
	else {
		$masks = substr($text, 2, 4);
		$data = substr($text, 6);
	}
	$text = "";
	for ($i = 0; $i < strlen($data); ++$i) {
		$text .= $data[$i] ^ $masks[$i%4];
	}
	return $text;
}

//Закодировать сообщение для передачи клиенту.
function mask($text)
{
	$b1 = 0x80 | (0x1 & 0x0f);
	$length = strlen($text);
	
	if($length <= 125)
		$header = pack('CC', $b1, $length);
	elseif($length > 125 && $length < 65536)
		$header = pack('CCn', $b1, 126, $length);
	elseif($length >= 65536)
		$header = pack('CCNN', $b1, 127, $length);
	return $header.$text;
}

//рукопожатие с новым клиентом.
function perform_handshaking($receved_header,$client_conn, $host, $port)
{
	$headers = array();
	$lines = preg_split("/\r\n/", $receved_header);
	foreach($lines as $line)
	{
		$line = chop($line);
		if(preg_match('/\A(\S+): (.*)\z/', $line, $matches))
		{
			$headers[$matches[1]] = $matches[2];
		}
	}

	$secKey = $headers['Sec-WebSocket-Key'];
	$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
	//информация для пожатия рук
	$upgrade  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
	"Upgrade: websocket\r\n" .
	"Connection: Upgrade\r\n" .
	"WebSocket-Origin: $host\r\n" .
	"WebSocket-Location: ws://$host:$port/demo/shout.php\r\n".
	"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
	socket_write($client_conn,$upgrade,strlen($upgrade));
}


function helloUser($link,$user_bd,$connected,$onoff){
	//сформируем список друзей для рассылки о том, что пользователь онлайн
	//print_r("SELECT * FROM users WHERE id IN (". $user_bd['friends'] .")");
	global $users_from_db_online;
	global $clients;
	$user_chat_id_hello=[];
	$users_iddb='("'.str_replace(',','","',$user_bd['friends']).'")';
	$resultuser = mysqli_query($link, "SELECT * FROM users WHERE id IN ". $users_iddb );
	if($resultuser->num_rows > 0){
		while($users_chat_id_hello = mysqli_fetch_array($resultuser)){
			$user_chat_id_hello[]=$users_chat_id_hello['chatId'];
		}
	$users_from_db_online=[];
	$users_from_db_online=array_intersect($clients,$user_chat_id_hello);
	$hellomsg = mask(json_encode(array('type'=>'system', 'onoff'=>$onoff, 'idonline'=>$user_bd['id'], 'message'=>$user_bd['email'].' '.$connected))); //подготовить json-данные
	send_message($hellomsg); //уведомить всех пользователей о новом подключении
	}
}

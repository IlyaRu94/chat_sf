<?php
session_start([
    'cookie_lifetime' => 86400*30*12,
]);
    require_once("db.php");
    require_once("user_profile.php");

    $accaunts=mysqli_query($link, "SELECT * FROM users WHERE token = '". $_SESSION['CSRF'] ."'");
    if($accaunts->num_rows > 0){
        $strHTML='';
        $accaunt = mysqli_fetch_assoc($accaunts);
        $user_profile[$accaunt['id']] = new User_Profile($accaunt['id'], $accaunt['email'], $accaunt['showemail'], $accaunt['avatar'],$accaunt['friends'],$accaunt['name']);
        $friends_list=$user_profile[$accaunt['id']]->get_friends();//друзья аккаунта

        $friends_list_id_arr=[];//иды друзей в group чате 
        $accaunt_friends=[];//друзья аккаунта
        $friends_chat_name='';//имена друзей в чате
        $chat_name=[];//имя чата
        $chat_avatar=[];//имя чата
        $id_chats=[];//айди найденных чатов
        //проверяем, если ли среди друзей групповые чаты
        if(strrpos($friends_list, 'ch')!==false){

            //разделим друзей и найдем среди них чаты
            $friends_list_arr=explode(',',$friends_list);
            //разделим друзей и найдем среди них чаты
            $friends_list_arr=explode(',',$friends_list);
            foreach($friends_list_arr as $friends_list_val){
                //возьмем только чаты, добудем ид и обратимся за пользователями
                if(strrpos($friends_list_val, 'ch')!==false){
                    $id_chats[]=$friends_list_val;
                    $id_group_chat=str_replace('ch','',$friends_list_val);
                    $usergroup=mysqli_query($link, "SELECT * FROM groupChat WHERE id = '". $id_group_chat ."'");
                    if($usergroup->num_rows > 0){
                        $user_group = mysqli_fetch_assoc($usergroup);
                        //формируем список и возьмем по каждому информацию из массива профилей друзей
                        $chat_name[$friends_list_val]=$user_group ['nameGroup'];
                        $chat_avatar[$friends_list_val]=$user_group ['avatar'];
                        $friends_list_id_arr[$friends_list_val]=$user_group ['usersId'];
                    }
                }else{
                    $accaunt_friends[]=$friends_list_val;
                }
            }

        $friends_list='"'.str_replace(',','","',implode(',',$accaunt_friends).','.implode(',',$friends_list_id_arr)).'"';
        }else{$friends_list='"'.str_replace(',','","',$friends_list).'"';}
        $accauntsfriends=mysqli_query($link, "SELECT * FROM users WHERE id IN (". $friends_list .")");
        
        if($accauntsfriends->num_rows > 0){
            $strHTML.= '<div class="users" id="friends">';
            while($friend = mysqli_fetch_array($accauntsfriends)){
                //если друг есть в списке друзей - выводим его в список. В противном случае этот друг только в чате, и его в свой список не добавляем
                $user_profile[$friend['id']]= new User_Profile($friend['id'], $friend['email'], $friend['showemail'], $friend['avatar'],$friend['friends'],$friend['name']);
                //$friends_name= (!empty($friend['showemail']) and !empty($friend['name'])) ? $friend['name'] : $friend['email'];
                if((in_array($friend['id'],$accaunt_friends)) or (empty($friends_list_id_arr))){//если есть друг в списке (массиве) друзей (относится к ситуации, когда есть группы) - выведем его в графу друзей, если нет групп - выведем всех
                    $friends_name=$user_profile[$friend['id']]->get_name();
                    $friend_avatar=$user_profile[$friend['id']]->get_avatar();
                    $strHTML.= '<div class="users__list__item" id="'.$friend['id'].'"><div class="v">&#128270;</div>
                    <div class="i" style="width:30px;height:30px;background:url(\''.$friend_avatar.'\');background-size:cover;background-position:center;"></div><span class="users__list__item__span">'.$friends_name.
                    '</span></div>';
                }
            }
            $strHTML.= '</div><div class="users" id="group" style="border-top:solid black 1px">';

            //проверяем, массив групповых чатов на пустоту
            if(!empty($id_chats)){
                foreach($id_chats as $id_chats_val){
                    $friends_chat_name='';
                    $friends_list_group_name=$chat_name[$id_chats_val];
                    $chat_avatar_item=(!empty($chat_avatar[$id_chats_val])) ? $chat_avatar[$id_chats_val] : './image/avatar.png';
                    $friends_list_val_arr=explode(',',$friends_list_id_arr[$id_chats_val]);
                    foreach($friends_list_val_arr as $friends_list_name){
                        $friends_list_name=intval($friends_list_name);
                        if(empty($user_profile[$friends_list_name])){
                            continue;
                        }
                        $friends_chat_name.=$user_profile[$friends_list_name]->get_name().' ';
                    }
                $strHTML.= '<div class="users__list__item" id="'.$id_chats_val.'"><div class="v">&#128270;</div>
                <div class="i" style="width:30px;height:30px;background:url(\''.$chat_avatar_item.'\');background-size:cover;background-position:center;"></div><span class="users__list__item__span"><b>'.$friends_list_group_name.'</b><br><i>'.$friends_chat_name.
                '</i></span></div>';
                }
            }


        $strHTML.= '</div>';

        }else{
            $strHTML.= '<div class="users" id="friends"><span class="errmsg">Список друзей пуст.<br>Воспользуйтесь поиском ↑</span></div><div class="users" id="group" style="border-top:solid black 1px"></div><script>setTimeout(function(){document.querySelector(".errmsg").remove();}, 3000);</script>';
        }

        if(!empty($_GET['json'])){
            header("Content-Type: application/json; charset=UTF-8");
            echo json_encode(array("stat"=>"true","msg"=>$strHTML));
        }else{
            echo $strHTML;
        }


    }


    
?>
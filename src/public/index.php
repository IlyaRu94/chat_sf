<?php 
$colors = array('#007AFF','#FF7000','#FF7000','#15E25F','#CFC700','#CFC700','#CF1100','#CF00BE','#F00');
$color_pick = array_rand($colors);
session_start([
    'cookie_lifetime' => 86400*30*12,
]);

if (isset($_GET['exit'])){
    unset($_COOKIE['sendToUserId']);
    setcookie('sendToUserId', '', -1, '/');
    $_SESSION = [];
    session_destroy();
    header('Location: index.php?auth');
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="widht=device-widht, initial-scale=1.0">
    <script src="js/sweetalert2.all.min.js"></script>
    <link rel="stylesheet" href="style.css">
    <title>Чат</title>
</head>
<body>
    <?php 
        if(isset($_GET['register'])){
            include_once ('register.php');
        } elseif(!isset($_SESSION['id']) or isset($_GET['auth'])){
            include_once ('auth.php');
        }else{
        ?>

    <div class="main">
        <div class="mobileMenu">
            <div class="mobileMenu__item" id="mmsidebar"> 👥 Друзья и чаты</div>
            <div class="mobileMenu__item" id="mmnav"> ★ Главное меню</div>
        </div>
        <div class="sidebar">
            <div class="sidebar__search"><input type="text" id="userSearch" placeholder="Введите имя пользователя"><div class="search__result"></div></div>
            <div class="list" id="userslist">
            <?php include_once ('friends.php'); ?>
            </div>
        </div>
        <div class="content">
            <div class="container">
                <h4 class="container__header"><span id="title-chat">Выберите чат</span><span class="mute"><img class="snd" id="on" src="image/on.svg"></span></h4>
                <div class="container__history"><div style="display:none" id="history">Ранее в переписке</div></div>
                <div class="container__main" id="message-box"></div>
                <div id="bottom"></div>
            </div>
            <div class="form">
                <textarea class="form__input" type="text" name="message" id="message" placeholder="Наберите Ваше сообщение здесь"></textarea>
                <div class="form__send" id="send-message"></div>
            </div>
        </div>
        <div class="nav" id="navusermenu">
            <div class="nav__item" id="profile">Профиль</div>
            <div class="nav__item" id="setting">Настройки</div>
            <div class="nav__item" id="newGroup">Создать группу</div>
            <a id="exit" href="?exit"><div class="nav__item">Выйти</div></a>
        </div>
    </div>
    <div class="profile_modal"><?php include_once ('my_profile.php'); ?><?php include_once ('group_create.php'); ?>
        <div class="container__setting">
            <div class="container__setting__sidebar">
                <h2 class="container__setting_header">Настройки Чат SF!</h2>
                <div class="container__setting__item" id="syssound"><span>Системные звуки</span><img class="snd" id="snd" src="image/on.svg"></div>
                <div class="container__setting__item" id="allsound"><span>Все уведомления</span><img class="snd" id="snd" src="image/on.svg"></div>
            </div>
            <div class="close_setting">X</div>
        </div>
    </div>

    <menu id="ctxMenu">
        <menu class="rmenu" title="Переслать" id="menuitemresend" onclick="click_menu_item_resend(this)"></menu>
        <menu class="rmenu" title="Изменить" id="menuitemedit" onclick="click_menu_item_edit(this)"></menu>
        <menu class="rmenu" title="Удалить" id="menuitemdelt" onclick="click_menu_item_delete(this)"></menu>
    </menu>
        <div class="container__resend" id="myfriendstoresend"></div>
    <script>
        var ideditsms='';
        var token="<?php echo $_SESSION['CSRF'];?>";
        var historyPage=10;//страница истории с 0 на конце
        var historyIdPage=0;//ид последнего исторического сообщения
        const messageBox = document.querySelector('#message-box');
        var titleChat = document.querySelector('#title-chat');
        const wshost='chat.local';
        const wsUri = "ws://"+wshost+":9005/server.php";
        var websocket = new WebSocket(wsUri);
        var userId = '<?php echo $_SESSION['id'];?>';//ид текущего пользователя
        var sendToUserId = document.cookie.replace(/(?:(?:^|.*;\s*)sendToUserId\s*\=\s*([^;]*).*$)|^.*$/, "$1");//ид собеседника
 
        window.onload = function() {// для корректной работы запускать только после загрузки страницы
            if(document.getElementById(sendToUserId)){
                titleChat.innerHTML=document.getElementById(sendToUserId).querySelector('.users__list__item__span').innerHTML;//выводим имя пользователя в название чата
                mutereadimg(titleChat.closest('.container__header').querySelector('.snd'),sendToUserId,mute);//считываем состояние звука чата
            }
            Swal.fire("Привет, <?echo $_SESSION['email'];?> Добро пожаловать в чат");
        }

        document.querySelector(".mobileMenu").addEventListener('click', function(event){
            if(event.target.id=="mmsidebar"){
                document.querySelector('.sidebar').classList.toggle('displayblock');
            }
            if(event.target.id=="mmnav"){
                document.querySelector('.nav').classList.toggle('displayblock');
            }

        });

        //функция замены переписки при клике на польователя
        document.querySelector("#userslist").addEventListener('click', function(event){
            let parentEvent=event.target.closest(".users__list__item");//по событью клика ищем родителя и считываем его инфу
            if(parentEvent){
            sendToUserId = parentEvent.id
            document.cookie = "sendToUserId="+sendToUserId+"; path=/; max-age=25920000";
            messageBox.innerHTML='';
            titleChat.innerHTML=parentEvent.querySelector('.users__list__item__span').innerHTML;//изменение заголовка чата
            mutereadimg(titleChat.closest('.container__header').querySelector('.snd'),sendToUserId,mute);//считываем состояние звука чата
            historyPage=10;
            historyIdPage=0;
            websocket.send(JSON.stringify({ sendToUserId: sendToUserId, token: token, }));
            }
        })

        websocket.onopen = function (){
            //представимся серверу и загрузим историю
            const name_message = {
                sendToUserId: sendToUserId,
                token: token,
            }
            websocket.send(JSON.stringify(name_message));
            websocket.send(JSON.stringify({token: token, update:'idonline',}));
        }
        websocket.onmessage = function (ev){
            const response = JSON.parse(ev.data);

            const res_type = response.type;
            const user_message = response.message;
            const user_name = response.name;
            const user_color = response.color;
            const smsId = response.smsid;
            const userIdReceive=response.userIdReceive;
            const userIdSend=response.userIdSend;
            const forHTML=response.forhtml;
            const forblockid=response.forblockid;
            const historyId=parseInt(response.historyid);
            const idonline=response.idonline;
            const onoff=response.onoff;

            switch (res_type){
                case 'usermsg':
                    //console.log(userIdReceive+'-userIdReceive для переписки серв, '+sendToUserId+'-sendToUserId для переписки из куки, '+userIdSend+'-userIdSend я сервер, '+userId +'- userId я в браузере' );
                    if((userIdReceive==sendToUserId) || ((userIdSend==sendToUserId) & (userIdReceive==userId))){//в чат выводим только относящиеся к чату сообщения, остальные -в окно
                        //если изменил пользователь сообщение
                        if(document.getElementById('m'+smsId)){
                            let updatesmstext='<p class="user_message">'+user_message+'</p>';
                            let oldsmstext=document.getElementById('m'+smsId).querySelector('.user_message');
                            oldsmstext.outerHTML= oldsmstext.outerHTML.replace(oldsmstext.outerHTML,updatesmstext );
                            break;
                        }

                        let user_class= (userIdSend==userId) ? 'sms sendUser' : 'sms sendToUser' ;
                        //если не пустой historyid - значит мы в истории, отключаем пролистывание вниз и сравниваем иды, попутно записывая их в historyIdPage
                        //выводим только те сообщения, которые меньше по иду
                        if (historyId){
                            if((historyId < historyIdPage) || (historyIdPage==0)){
                                messageBox.innerHTML='<div class="'+user_class+'" oncontextmenu="msg__menu_click(event)" id="m'+smsId+'"><p class="user_name" style="color:'+user_color+'"></p> <p class="user_message">'+user_message+'</p></div>'+messageBox.innerHTML;
                            }
                                historyIdPage=historyId;
                                historybtn();
                                break;
                        }
                        if (historyIdPage > smsId){break;}//чтобы старые сообщения не отображались в реальном времени при изменении
                            messageBox.innerHTML+='<div class="'+user_class+'" oncontextmenu="msg__menu_click(event)" id="m'+smsId+'"><p class="user_name"></p> <p class="user_message" style="color:'+user_color+'">'+user_message+'</p></div>';
                            document.querySelector('#bottom').scrollIntoView();//всегда пролистывать вниз
                    }else{
                        //если сообщение в другом чате - вывести в swetalert всплывающий внизу экрана
                        Swal.fire({position: 'bottom-end', title: user_message, showConfirmButton: false, timer: 3000, toast:true})
                    }
                    mutesoundplay(userIdReceive,mute,'usr');//воспроизведение звука от пользователя с сервера
                break;
                case 'system':
                    if(idonline){
                        if(onoff=='on'){
                            if(document.getElementById(idonline).closest('#friends')){
                                if(!document.getElementById(idonline).closest('#friends').querySelector('.online')){
                                    document.getElementById(idonline).insertAdjacentHTML("beforeend", '<div class="online" style="color:green" title="Онлайн">⚫</div>');
                                }
                            }
                        }else{
                            if(document.getElementById(idonline).closest('#friends').querySelector('.online')){
                                document.getElementById(idonline).querySelector('.online').outerHTML='';
                            }
                        }
                    }
                    Swal.fire({position: 'bottom-end', title: user_message, showConfirmButton: false, timer: 3000, toast:true})
                    mutesoundplay(sendToUserId,mute,'sys');//воспроизведение звука
                break;
                case 'update':
                    if(forblockid=='updateInfo' ){ 
                        let blockforid='';//в этот блок поместим найденный элемент, данная функция нужна для обновления информации, как у себя, так и у собеседника, если сообщение не найдено (к примеру у собеседника) - бездействуем.
                        if(document.getElementById(userIdSend)){blockforid=document.getElementById(userIdSend);}else{if((userIdSend.indexOf("m") < 0)){blockforid=document.getElementById(userIdReceive);}}
                        if ((((userIdSend.indexOf("ch") >= 0) && (userIdReceive==userId)) || (userIdSend.indexOf("ch") < 0)) && (blockforid!=='') ){//Если удалил группу я - удалить только у меня, если удалил друзей, сообщения - удалить везде. Если сообщение не найдено - не удалять
                            blockforid.outerHTML= blockforid.outerHTML.replace(blockforid.outerHTML,forHTML );
                        }
                    }else{  document.querySelector('#'+forblockid).innerHTML +=forHTML;  }
                    if(!document.getElementById(sendToUserId)){document.cookie = "sendToUserId=''; path=/; -1";}//если удалили друга - удалим и в куках упоминание о нем
                    Swal.fire({position: 'bottom-end', title: user_message, showConfirmButton: false, timer: 3000, toast:true})
                    mutesoundplay(sendToUserId,mute,'sys');//воспроизведение звука
                break;
                case 'updateonline':
                    if(idonline){
                     for (x in idonline) {
                        if(document.getElementById(idonline[x]).closest('#friends')){
                            document.getElementById(idonline[x]).insertAdjacentHTML("beforeend", '<div class="online" style="color:green" title="Онлайн">⚫</div>');
                        }
                    } 
                }
                break;
            }

        }
        websocket.onerror = function (ev){
            messageBox.innerHTML+='<div class="system_error">Произошла ошибка '+ev.data+'</div>';
        }
        websocket.onclose = function (){
            messageBox.innerHTML+='<div class="system_msg">Соединение закрыто</div>';
        }


        document.querySelector('#send-message').addEventListener('click', function() {
            send_message();
        });

        function send_message(){
            const message_input = document.querySelector('#message');

            if(message_input.value===''){
                Swal.fire('Необходимо ввести сообщение!');
                return false
            }

            const result_message = {
                message: message_input.value,
                sendToUserId: sendToUserId,
                token: token,
                ideditsms: ideditsms,
                color:'<?php echo $colors[$color_pick]; ?>',
            }
            if(sendToUserId){
            websocket.send(JSON.stringify(result_message));
            }else{Swal.fire("Выберите или добавьте собеседника для переписки")}
            ideditsms='';
            message_input.value='';
            message_input.style.color = '';
            if(document.querySelector('.cansel')){document.querySelector('.cansel').outerHTML='';}//удаляем саму себя кнопку отмена
        }

//если есть сообщения с идами истории - показываем кнопку "ранее в переписке" 
function historybtn (){
    document.querySelector('#history').style.display = 'block';
}

    const historyBotton = document.querySelector('#history')
    historyBotton.addEventListener('click', function(){
        const history_page_message = {
                sendToUserId: sendToUserId,
                historyPage: historyPage,
                token: token,
            }
            websocket.send(JSON.stringify(history_page_message));
            historyPage=historyPage+10;
            historyBotton.style.display = 'none';
    })


    const searchResult = document.querySelector('.search__result');
    const searchUser = document.querySelector('#userSearch');
    searchUser.addEventListener("input", function(){
        let objToServer = { "token":token, "name":searchUser.value };
        let postParam ='search=';
        searchResult.innerHTML ='';
        loadPage(objToServer, searchResult, "user_search.php", postParam, '');
        searchResult.style.display = 'block';        
        if(searchUser.value==''){
            searchResult.style.display = 'none';
            setTimeout(function(){searchResult.innerHTML ='';},1000);
        }
    });

    function search_click(user_id_click){
        Swal.fire('Пользователь добавлен');
        searchResult.style.display = 'none';
        document.querySelector('#userSearch').value = '';
        let objToServer = { "token":token, "id":user_id_click };
        let postParam ='add=';
        const addResult = document.querySelector('#friends');
        loadPage(objToServer, addResult, "user_search.php", postParam, user_id_click);
        websocket.send(JSON.stringify({ sendToUserId: user_id_click, token: token, update: 'friends', message:' добавил Вас в список друзей. База друзей успешно обновлена', }));
    }

    var myfriendstogroup=document.querySelector('#myfriendstogroup');// див для помещения туда списка друзей
    var addfriendsidgroup=''; //переменная для сбора кликов по друзьям при добавлении в группу

    //отслеживаем клики по меню
    document.querySelector("#navusermenu").addEventListener('click', function(evnt){
        switch(evnt.target.id){
            case 'newGroup'://клик по кнопки создания группы
                document.querySelector('.container__group').style.display = 'flex';
                var myfrblock=document.querySelector('#friends').innerHTML;
                myfriendstogroup.innerHTML=myfrblock.replace(/users__list__item/g,"users__list__group__item");
            break;
            case 'profile'://клик по профилю показывает блок профиля
                document.querySelector('.container__myProfile').style.display = 'flex';
            break;
            case 'setting'://клик по профилю показывает блок профиля
                document.querySelector('.container__setting').style.display = 'flex';
                mutereadimg(syssound.querySelector('img'),'sys',mute);//считываем состояние системного звука чата
                mutereadimg(allsound.querySelector('img'),'all',mute);//считываем состояние всех звуков чата
            break;
        }
    })
    
 // отслеживание кликов по списку друзей для добавления в группу
    myfriendstogroup.addEventListener('click', functionMyFriendsToGroup, false);
    //функция для addeventlistener для помещения кликов по друзьям в поле отправки (для создания чата)
    function functionMyFriendsToGroup(eve){
       if(addfriendsidgroup==''){addfriendsidgroup='<?php echo $_SESSION['id'];?>';};
        let parentEventgroup=eve.target.closest(".users__list__group__item");//по событью клика ищем родителя и считываем его инфу
        let friendsidgroup = parentEventgroup.id
        maskid = new RegExp( `,${friendsidgroup}\\b`, 'g' );
            if (!parentEventgroup.classList.contains('add')){
                parentEventgroup.classList.add('add');
                addfriendsidgroup+=','+friendsidgroup
            }else{
                parentEventgroup.classList.remove('add');
                addfriendsidgroup = addfriendsidgroup.replace(maskid, '');
            }
        document.querySelector('.container__group__input__friends').value=addfriendsidgroup//строка для сбора друзей в группу
    }


    //клик по кнопке закрыть в создании группы - убирает данное окно
    document.querySelector(".close_group").addEventListener('click', function(){
        document.querySelector('.container__group').style.display = 'none';
        myfriendstogroup.innerHTML='';
    })    

     //клик по кнопке закрыть в профиле - убирает профиль
    document.querySelector(".close_profile").addEventListener('click', function(){
        document.querySelector('.container__myProfile').style.display = 'none';
    })

    //клик по кнопке закрыть в профиле - убирает профиль
    document.querySelector(".close_setting").addEventListener('click', function(){
        document.querySelector('.container__setting').style.display = 'none';
    })

    //клик по кнопке изменить фото
    document.querySelector("#Photoedit").addEventListener('click', function(){
        document.querySelector('#imageuploads').classList.toggle('displayblock');
    })
    //клик по кнопке изменить имя
    document.querySelector("#editName").addEventListener('click', function(){
        document.querySelector('#formEditName').classList.toggle('displayblock');
    })
    //клик по кнопке изменить имя
    document.querySelector("#submitName").addEventListener('click', function(ev){
        ev.preventDefault();
        const newName = document.querySelector('#newName');
        const blockRes = document.querySelector('#myName');
        blockRes.innerHTML='';
        loadPage({ "name":newName.value }, blockRes, 'my_profile.php', 'nameUpdate=', '')
        newName.value='';
    })
    //клик по чекбоксу
    document.querySelector("#showemail").addEventListener('click', function(){
        const showemail = document.querySelector('#showemail');
        let showemailsend='';
        if (showemail.checked) {
            Swal.fire("Вы скрыли email из поиска");
            showemailsend="1";
        } else {
            Swal.fire("Ваш E-mail доступен для поиска");
            showemailsend="0";
        }
        loadPage(showemailsend, showemail, 'my_profile.php', 'showemail=', '')
    })


// блок для отправки фото профиля
    const formphoto = document.querySelector('#imageuploads');
    const filephoto=document.querySelector('#inputfilephoto');
    const urlphoto = 'my_profile.php';
    const outphoto=document.querySelector('#photoProfile')
    // вешаем обработчик на форму с загрузкой файла
    formphoto.addEventListener('submit', e => {
        // отменяем действие по умолчанию
        e.preventDefault()
        uploadimage (formphoto, filephoto, urlphoto, outphoto);
    })

    // блок для отправки фото группы и сбора данных из формы для создания группы
    const formphotogroup = document.querySelector('#imagegroupupload');
    const filephotogroup=document.querySelector('#inputfilephotogroup');
    const urlphotogroup = 'group_create.php';
    const outphotogroup=document.querySelector('#photogroup');
    document.querySelector('#inputfilephotogroup').addEventListener('change', inputPhotoView, false);
function inputPhotoView(e){
        let fls = e.target.files[0]
        if (fls) {//редактирование группы не предусмотрено, окно зикроется после успешного создания, чтобы лишних запросов к базе не делать - фото покажем перед загрузкой на сервер
            document.querySelector('#photogroup').style.backgroundImage = "url('"+URL.createObjectURL(fls)+"')";
        }
}

    // вешаем обработчик на форму с загрузкой файла
    formphotogroup.addEventListener('submit', e => {
        // отменяем действие по умолчанию
        e.preventDefault()
        uploadimage (formphotogroup, filephotogroup, urlphotogroup, outphotogroup);

    })

    //функция отправки файлов и сложных форм
    function uploadimage (form,filesinput,url, out){   
        // находим файл
        const files = filesinput.files
        const formData = new FormData(form)//считываем данные существующей формы
        // создаем xhr
        const xhr = new XMLHttpRequest()
        // разбираем полученное
        xhr.onload = () => {
            let answer = JSON.parse(xhr.responseText);
            //проверяем ответ и заменяем фото, если все гуд
            if(answer.stat=='true'){
                let phURL='';
                if (answer.url) {
                    out.style.backgroundImage = "url('"+answer.url+"')";//исключительно для профиля, чтобы картинка добавлялась только при успешной загрузке
                    phURL=answer.url;
                }
                if(answer.insert=='group'){
                    websocket.send(JSON.stringify({ sendToUserId: formData.get("friendsgroup"), groupid: answer.groupid, token: token, avatar: phURL, update: 'group', groupname: formData.get("namegroup"),  message:' добавил Вас в группу '+formData.get("namegroup")+' . ', }));
                }else{
                    websocket.send(JSON.stringify({ sendToUserId: '', token: token, update: 'updateInfo', message:': Пользователь изменил информацию о себе', }));
                }
                Swal.fire(answer.msg);
            }else{
                form.innerHTML+='<div class="errmsg">'+answer.msg+'</div>';
                setTimeout(function(){document.querySelector('.errmsg').remove();}, 3000);
                }

                form.reset();//сброс формы
                //сброс переменной для сбора пользователей группы
                addfriendsidgroup='';
                //перечитаем друзей, заодно и очистим выбранных, подготовим форму для нового добавления
                myfriendstogroup=document.querySelector('#myfriendstogroup');
                myfrblock=document.querySelector('#friends').innerHTML;
                myfriendstogroup.innerHTML=myfrblock.replace(/users__list__item/g,"users__list__group__item");
                myfriendstogroup.addEventListener('click', functionMyFriendsToGroup, false);
                document.querySelector('#photogroup').style.backgroundImage = "url('./image/avatar.png')";
                document.querySelector('#inputfilephotogroup').addEventListener('change', inputPhotoView, false);
        }
        // создаем соединение и отправляем файл
        xhr.open('POST', url)
        xhr.send(formData)
    
    }



    function loadPage(objToServer, htmlBlock, urlParam, postParam, idclick) {
        let xmlhttp, objFromServer, classitem, custom, x, block = '';
        xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function() {
            if (this.readyState == 4 && this.status == 200 && (this.responseText.length>0)) {
                objFromServer = JSON.parse(this.responseText);
                    //сделать проверку objFromServer.stat==true и только в этом случае выполнять скрипты
                    if(objFromServer.stat=="true"){
                        if(postParam=='search='){
                            classitem="search__result__item";
                            custom='';
                            for (x in objFromServer) {
                                if(objFromServer[x]=="true"){continue}
                                let name, isOnlUser, avatar =''
                                name=objFromServer[x].name ? objFromServer[x].name : objFromServer[x].email;
                                if(document.getElementById(objFromServer[x].id)){custom=''; name+=' <small><i><b>В друзьях</b></i></small>' }else{if(objFromServer[x].id=='<?php echo $_SESSION['id'] ?>'){custom=''; name+=' <small><i><b>Вы</b></i></small>' }else{custom='onClick="search_click(this.id)"';}}
                                avatar=objFromServer[x].avatar ? objFromServer[x].avatar : './image/avatar.png';
                                isOnlUser=(objFromServer[x].chatId) ? '<div class="online" style="color:green" title="Онлайн">⚫</div>' : '' ;
                                block += '<div class="'+classitem+'" id="'+objFromServer[x].id+'" '+custom+'><div class="v">&#128270;</div><div class="i" style="width:30px;height:30px;background:url(\''+avatar+'\');background-size:cover;"></div><span class="users__list__item__span">'+name+'</span>'+isOnlUser+'</div>' ;
                            }
                        }
                        if(postParam=='add='){
                            //сделать замену в блоке класса на переданный и удалить онклик
                            block=document.getElementById(idclick).outerHTML;
                            block=block.replace(/search__result__item/g,"users__list__item");
                            block=block.replace('onclick="search_click(this.id)"'," ");
                            document.querySelector('.search__result').innerHTML ='';//после добавления из поиска - очистим список поиска
                        }
                        if(postParam=='nameUpdate='){
                            block=(objFromServer.name=='NULL') ? '<?echo $_SESSION['email'];?>' : objFromServer.name;
                            websocket.send(JSON.stringify({ sendToUserId: '', token: token, update: 'updateInfo', message:': Пользователь изменил свое имя', }));
                        }
                        if(postParam=='showemail='){
                            block=(objFromServer.check=='1') ? htmlBlock.checked : htmlBlock;
                        }

                        htmlBlock.innerHTML += block;
                    }else{
                        block='<div class="errmsg">'+objFromServer.errmsg+'</div>';
                        htmlBlock.innerHTML += block;
                        setTimeout(function(){if(document.querySelector('.errmsg')){document.querySelector('.errmsg').remove();}}, 2000);
                    }
            }
        };
        xmlhttp.open("POST", urlParam, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        xmlhttp.send(postParam + JSON.stringify(objToServer));
    }



    </script>
    <script src="./js/menu.js"></script>
    <script src="./js/sound.js"></script>

<?php } ?>

</body>
</html>

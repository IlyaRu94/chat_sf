var ctxMenu = document.getElementById("ctxMenu");
var blockparenttocontextmenu='';//родительский блок для контекстного меню
var smsOrUsersClickId='';//собираем клики правой кнопкой мыши по пользователям и сообщениям, сохраняем иды элементов
//Обрабатывает контекстное меню
document.querySelector(".main").addEventListener('contextmenu', function(event){
    clearmenu();
    let parentEventsmsclick=event.target.closest(".sms");//по событью клика ищем родителя и считываем его инфу
    if(parentEventsmsclick){ 
        blockparenttocontextmenu='message';
        smsOrUsersClickId=parentEventsmsclick.id; 
    if(document.getElementById(smsOrUsersClickId).classList.contains("sendToUser")){//отображение блоков в зависимости от автора
        document.getElementById('menuitemresend').style.display = 'block';
    }else{
        document.getElementById('menuitemedit').style.display = "block";
        document.getElementById('menuitemresend').style.display = 'block';
        document.getElementById('menuitemdelt').style.display = 'block';
        }
    }
    let pE=event.target.closest(".users__list__item");//по событью клика ищем родителя и считываем его инфу
    if(pE){
        blockparenttocontextmenu=pE.closest(".users").id;//получаем ид родителя, который показывает, что перед нами за блок
        document.getElementById('menuitemdelt').style.display = 'block';//сброс отображение меню на по умолчанию
        smsOrUsersClickId=pE.id;
    }
    event.preventDefault();
    ctxMenu.style.display = "block";
    ctxMenu.style.left = (event.pageX - 10)+"px";
    ctxMenu.style.top = (event.pageY - 10)+"px";
})

//убирать меню при клике по main
document.querySelector(".main").addEventListener("click",function(event){
    clearmenu();
},false);

 //убирать меню при скролах message-box и users
document.querySelector('.container__main').addEventListener('wheel', function() {
    clearmenu();
 });
 document.querySelector('.users').addEventListener('wheel', function() {
    clearmenu();
 });

 //Функция вызываемая при клике по кнопке переслать
function click_menu_item_resend(eve){
    myfriendstoresend=document.querySelector('#myfriendstoresend');
    myfrbl=document.querySelector('#friends').innerHTML;//блок с друзьями
    myfriendstoresend.innerHTML=myfrbl.replace(/users__list__item/g,"users__list__resend__item");
    myfriendstoresend.style.display = 'block';
    myfriendstoresend.addEventListener('click', functionMyFriendsToresend, false);
}

//функция пересылки сообщений
function functionMyFriendsToresend(eve){
    let parentEventsend=eve.target.closest(".users__list__resend__item");//по событью клика ищем родителя и считываем его инфу
    if(parentEventsend){
        let resendid = parentEventsend.id;
        let resendtext = document.getElementById(smsOrUsersClickId);
        let resendinuser='';
        if(resendtext.classList.contains("sendToUser")){// если есть класс отправлено от пользователя - считаем его имя из титла, иначе возьмем свое имя из профиля
            resendinuser=document.querySelector('#title-chat').textContent;
        }else{
            resendinuser=document.querySelector('#myName').textContent;
        }
        //формируем пересылаемое письмо
        let resendsmsdate=resendtext.querySelector('.user_message_span').textContent;
        let resendsmstext=resendtext.querySelector('.s').textContent;
        let strresendsms='__i__Пользователь '+resendinuser+' '+resendsmsdate+' писал: __!i____br__'+resendsmstext;//Заменяем на безопасные символы тэги html
        websocket.send(JSON.stringify({message: strresendsms, sendToUserId: resendid, token: token,}));

     //убираем и очищаем все лишнее
    clearmenu();
     }
 }

//клик по кнопке изменить
function click_menu_item_edit(ev){
    if(document.getElementById(smsOrUsersClickId)){
        let edittext = document.getElementById(smsOrUsersClickId).querySelector('.s').textContent;
        document.querySelector('#bottom').scrollIntoView();
        let msginput = document.getElementById('message');
        msginput.insertAdjacentHTML('afterend', '<div class="cansel" onclick="canselclear()">Отмена</div>');
        msginput.value=edittext;
        msginput.style.color = "red"; // пометим редактируемое сообщение и текст красным
        ideditsms=smsOrUsersClickId.replace('m','');
        clearmenu();
    }
 }

//клик по кнопке удалить
 function click_menu_item_delete(ev){
    //функция для удаления сообщений
let stui='';//кому отправлять: в смс - глобальный получатель, при удалении друзей и групп - ид кликнутого блока
let iddelsms = '';//что удалять: у смс - ид сообщения, у друзей и групп - их иды
let ss='';// сообщение при удалении
if(document.getElementById(smsOrUsersClickId)){
    if(blockparenttocontextmenu=='friends'){stui=iddelsms=smsOrUsersClickId; ss=' из списка друзей ';}
    if(blockparenttocontextmenu=='group'){stui=iddelsms=smsOrUsersClickId; ss=' группу ';}
    if(blockparenttocontextmenu=='message'){stui=sendToUserId; iddelsms=smsOrUsersClickId.replace('m',''); ss=' сообщение';}
            websocket.send(JSON.stringify({ sendToUserId: stui, token: token, iddelsmsuser: iddelsms, delete:blockparenttocontextmenu, message: ss}));
            clearmenu();
        }
 }

//функция очистки и скрытия меню
 function clearmenu(){
    //убираем и очищаем все лишнее
    smsOrUsersClickId='';
    ctxMenu.style.display = "";
    ctxMenu.style.left = "";
    ctxMenu.style.top = "";
    document.getElementById('menuitemresend').style.display = 'none';//сброс отображение меню на по умолчанию
    document.getElementById('menuitemedit').style.display = 'none';
    document.getElementById('menuitemdelt').style.display = 'none';
    blockparenttocontextmenu='';//очищаем переменную
    document.getElementById('myfriendstoresend').style.display='none';//убираем функцию пересылки сообщения и очищаем ее
    document.getElementById('myfriendstoresend').innerHTML='';
 }

 //функция отмены пересылки письма
function canselclear(){
    clearmenu();
    document.getElementById('message').value="";
    document.querySelector('.cansel').outerHTML='';
    ideditsms='';
    message_input.style.color = '';

}
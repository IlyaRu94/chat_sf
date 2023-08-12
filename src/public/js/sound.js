var audio = new Audio('audio/sms.mp3');
var sysaudio = new Audio('audio/alert.mp3');
var mute ={};
//объект с тихими каналами
if(document.cookie.replace(/(?:(?:^|.*;\s*)mute\s*\=\s*([^;]*).*$)|^.*$/, "$1")){
    mute =JSON.parse(document.cookie.replace(/(?:(?:^|.*;\s*)mute\s*\=\s*([^;]*).*$)|^.*$/, "$1"));
}
const divMute=document.querySelector('.mute');//картинка громкости
const syssound=document.querySelector('#syssound');//картинка громкости
const allsound=document.querySelector('#allsound');//картинка громкости

divMute.addEventListener('click',function(e){mutetoggle(e.target,sendToUserId,mute)});//клик по верхнему колокольчику чата
syssound.addEventListener('click',function(e){mutetoggle(e.target,'sys',mute)});//клик по колокольчику с системными уведомлениями
allsound.addEventListener('click',function(e){mutetoggle(e.target,'all',mute)});//клик по общему колокольчику
//функция обработки щелчков по колокольчикам
function mutetoggle(muteblock,sendToUserId,mute){
    if(muteblock.classList.contains('snd')){
    if(mute[sendToUserId]){
        delete mute[sendToUserId];
    }else{
        mute[sendToUserId]='off';
    }
    mutereadimg(muteblock,sendToUserId,mute);
    document.cookie = "mute="+JSON.stringify(mute)+"; path=/; max-age=25920000";
}
}
//функция показа иконки
function mutereadimg(muteblock,sendToUserId,mute){
    let muteonoff='';
    if(mute[sendToUserId]){
        muteonoff='off';
    }else{
        muteonoff='on';
    }

    if((mute['all'])){//перекрасим в красный колокольчик, если массово отключены звуки
        muteblock.outerHTML='<img style="background:red;" class="snd" id="snd" src="image/'+muteonoff+'.svg">';
    }else{
        muteblock.outerHTML='<img class="snd" id="snd" src="image/'+muteonoff+'.svg">';
    }
}
//функция воспроизведения звука
function mutesoundplay(sendToUserId,mute,category){
    if((!mute['all']) & (!mute['sys']) & (category=='sys')){
        sysaudio.play().catch((error) => {
            console.log(error);
          });;
    }
    if((!mute['all']) & (!mute[sendToUserId]) & (category=='usr')){
        audio.play().catch((error) => {
            console.log(error);
          });;
    }
}
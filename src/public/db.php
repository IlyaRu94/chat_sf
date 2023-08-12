<?php
//для процедурного кода
$link = mysqli_connect('mysql:3306', 'user1', 's123', 'chat');
//для ооп подключения
$connect = new mysqli('mysql:3306', 'user1', 's123', 'chat');
?>
<?php
//для очистки данных перед записью в бд
function clearGP($str){
    $str=preg_replace("/[^\p{L}0-9\!\.\-_\s\?,:*\+;\(\)]/iu", "", $str);
    $str=preg_replace("/__i__/iu", "<i>", $str);
    $str=preg_replace("/__!i__/iu", "</i>", $str);
    $str=preg_replace("/__br__/iu", "<br>", $str);
    $str=preg_replace("/__b__/iu", "<b>", $str);
    $str=preg_replace("/__!b__/iu", "</b>", $str);
    return $str;
}
function clearGPid($str){
    return preg_replace("/[^0-9chm,]/", "", $str);
}
function clearGPint($str){
    return preg_replace("/[^0-9]/", "", $str);
}
function clearGPfriends($str){
    return preg_replace("/[^0-9,]/", "", $str);
}
function clearGPurl($str){
    return preg_replace("/[^a-zA-Z0-9_=\?\/\.:]/", "", $str);
}
?>

<?php
session_start([
    'cookie_lifetime' => 86400*30*12,
]);
require_once("db.php");
header("Content-Type: application/json; charset=UTF-8");
//Поиск в базе пользователей
if(!empty($_POST['search'])){
    $obj = json_decode($_POST["search"], false);
    if ((!empty($obj->token)) and (!empty($obj->name)) ){
        $obj->token=clearGP($obj->token);
        $obj->name = clearGP(trim($obj->name));
        if ($_SESSION['CSRF']==$obj->token){        
            $result = $connect->query("SELECT email, name, id, avatar, chatId FROM users WHERE ( showemail !=1 AND email LIKE '%".$obj->name."%' ) OR (name LIKE '%".$obj->name."%') LIMIT 10");
            $outp = array();
            if($result->num_rows > 0){
                $outpstat = array("stat"=>"true");
                $outp = $result->fetch_all(MYSQLI_ASSOC);
                $outp['stat']='true';//отправляет последним элементом статус подключения на js вырежем функцией Array.pop()
                echo json_encode($outp);
            }else{
                echo json_encode(array("stat"=>"false","errmsg"=>"Пользователь не найден"));
            }
        }
    }
}

//Добавление пользователя в свой список контактов и список контактов собеседника
if(!empty($_POST['add'])){
    $obj = json_decode($_POST["add"], false);
    if ((!empty($obj->token)) and (!empty($obj->id)) ){
        $obj->token= clearGP($obj->token);
        $obj->id = clearGPint(trim($obj->id));
        if ($_SESSION['CSRF']==$obj->token){
            //Записываем себя в список друзей. Если есть другие данные в строке - допишем в строку, если пусто - добавим без запятой
            $result = $connect->query("UPDATE users SET friends = IF (friends = '', '$obj->id', CONCAT(friends, ',$obj->id')) WHERE token='$obj->token'");
            //обновление списка друзей у друзей
            $result = $connect->query("UPDATE users SET friends = IF (friends = '', '".$_SESSION['id']."', CONCAT(friends, ',".$_SESSION['id']."')) WHERE id='$obj->id'");
            //print_r($result);
            if($result > 0){
                echo json_encode(array("stat"=>"true"));
            }else{
                echo json_encode(array("stat"=>"false","errmsg"=>"Пользователь не найден в базе"));
            }
        }else{echo json_encode(array("stat"=>"false","errmsg"=>"Неверный токен, авторизуйтесь повторно"));}
    }
}

?>
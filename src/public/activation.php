<?php
	include_once 'db.php';

if(($_GET['activation']) && (preg_match("/^[-=_\w]+$/i", $_GET['activation']))){
	if( (preg_match("/^[\w]+$/i", base64_decode($_GET['activation'])))){
        $activation=base64_decode($_GET['activation']);
        $resultdb = mysqli_query($link, "SELECT * FROM users WHERE token='". $activation. "'");
        if($resultdb->num_rows > 0){
            mysqli_query($link,'UPDATE `users` SET `active`="1" where `token`=\''.$activation.'\'');
            echo 'Активация прошла успешно! <a href="index.php?auth">Войдите в аккаунт</a>';
        }else{echo 'Неверный Токен!';}
	}else{echo 'Некорректный Токен!';}
}else{echo 'Токен для активации не найден!';}

?>
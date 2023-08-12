<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require_once "vendor/autoload.php";

$mail = new PHPMailer;
$mail->isSMTP();

$mail->SMTPDebug = 0;

$mail->Host = 'ssl://smtp.mail.ru';

$mail->SMTPAuth = true;
$mail->Username = 'sftestuser@mail.ru'; // логин от вашей почты
$mail->Password = 'xgLUZ55RC26rLThAZDfB'; // пароль от почтового ящика
$mail->SMTPSecure = 'SSL';
$mail->Port = '465';

$mail->CharSet = 'UTF-8';
$mail->From = 'sftestuser@mail.ru';  // адрес почты, с которой идет отправка
$mail->FromName = 'Ilya_Ru'; // имя отправителя
$mail->addAddress($_SESSION['email'], 'User');

$mail->isHTML(true);

$mail->Subject = 'Регистрация в чате';
$mail->Body = '<b>Здравствуйте '.$_SESSION['email'].'!</b><br>Для завершения регистрации в чате, <a href="'.$from_URL.'">перейдите по ссылке</a>';
$mail->AltBody = 'Здравствуйте '.$_SESSION['email'].'! Для завершения регистрации в чате, перейдите по ссылке: '.$from_URL;

//$mail->SMTPDebug = 1;

if ($mail->send()) {
    echo 'Письмо успешно отправлено. ';
} else {
    echo 'Письмо не может быть отправлено, обратитесь к админу. ';
    file_put_contents('errormail.log','Ошибка: ' . $mail->ErrorInfo);
}

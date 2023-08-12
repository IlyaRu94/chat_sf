<?php
use PHPMailer\PHPMailer\PHPMailer;
	session_start([
		'cookie_lifetime' => 86400*30*12,
	]);
	include_once 'db.php';
	mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
?>

<div class="container__form">
	<?php

	if(($_POST["token"] == $_SESSION["CSRF"])){
		if(((!empty($_POST["email"])) && (!empty($_POST["pass"])) && (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) && (preg_match("/^[-._\w]+$/i", $_POST['pass'])) ) ){
			$email = mb_strtolower($_POST['email']);
			$password = $_POST['pass'];
			$res = mysqli_query($link, "SELECT * FROM users WHERE email = '$email'");
			$rowuser = mysqli_fetch_assoc($res);
			if (empty($rowuser)) {
				mysqli_query($link, 'INSERT INTO `users`(`email`, `password`, `token`) VALUES(\''.$email.'\', \''.password_hash($password, PASSWORD_DEFAULT).'\', \''.$_SESSION["CSRF"].'\');');
				$_SESSION['email'] = $email;
				$snm=(!empty($_SERVER['SERVER_NAME'])) ? $_SERVER['SERVER_NAME'] : $_SERVER['HTTP_HOST'];
				$from_URL ='http://'.$snm.':'.$_SERVER['SERVER_PORT'].str_replace(strrchr($_SERVER['REQUEST_URI'], "/"),'/activation.php?activation=',$_SERVER['REQUEST_URI']).base64_encode($_SESSION["CSRF"]);
				include_once('mailsend.php');
				echo '<a href=".?auth">Авторизоваться</a>';
			}else{
				echo '<script>window.onload = function() { Swal.fire("Пользователь уже есть в базе данных, авторизуйтесь или зарегистрируйтесь с другим E-mail") }</script>';
			}
		}else{
			echo '<script>window.onload = function() { Swal.fire("Есть незаполненные поля или в них содержатся недопустимые символы!") }</script>';
		}
	}else{
		if ($_POST["token"]){
			echo '<script>window.onload = function() { Swal.fire("Недействительный токен") }</script>';
		}
	}
	$token = hash('gost-crypto', random_int(0,999999));
	$_SESSION["CSRF"] = $token;

	

	if ($_SESSION['email']) {
		echo '<script>window.onload = function() { Swal.fire({text:"На Ваш E-mail отправлено письмо. Для продолжения регистрации перейдите по ссылке из письма", footer: "Или зарегистрируйте новую учетную запись"}) }</script>';
	}
		echo '<div class="container__login">
				<div class="container__content">
					<h1>Добро пожаловать в чат SF!</h1>
					<h2>Придумайте E-mail и пароль</h2>
					<br>
						<form method="post" class="form-group">
							<input type="text" name="email" placeholder="E-mail">
							<input type="text" name="pass" placeholder="Пароль">
							<input type="hidden" name="token" value="'.$token.'"> <br/>
							<input type="submit" class="btn btn-primary">
						</form>
						<a href=".?auth">Авторизоваться</a><br>
				</div>
			</div>';
	?>
	</div>

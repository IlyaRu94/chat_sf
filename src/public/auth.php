<?php
    session_start([
        'cookie_lifetime' => 86400*30*12,
    ]);
    require_once("db.php");
?>
<?php
//проверка на существование данных
$re = mysqli_query($link, "SELECT * FROM users WHERE 1");
if(!($re->num_rows  >0)){
mysqli_multi_query($link, file_get_contents('str.sql'));
}

 ?>
<div class="container__form">
    <?php
        if(($_POST["token"] == $_SESSION["CSRF"])){
            // сразу запрещаю в post все лишнее
            if(((isset($_POST["email"]))&& (isset($_POST["pass"])) && (filter_var($_POST["email"], FILTER_VALIDATE_EMAIL)) && (preg_match("/^[-.@_\w]+$/i", $_POST['pass'])) )  ){
                $email = mb_strtolower($_POST['email']);
                $password = $_POST['pass'];
                $result = mysqli_query($link, "SELECT * FROM `users` WHERE `email`='". $email. "'");
                if($result->num_rows  >0){
                    $row = mysqli_fetch_assoc($result);
                    if (password_verify ($password, $row['password'])) {
                        if(!empty($row['active'])){
                            // E-mail и пароль нашли
                            $_SESSION['email'] = $row['email'];
                            $_SESSION['id'] = $row['id'];
                        }else{
                            echo '<script>window.onload = function() { Swal.fire("На адрес '.$row['email'].' было отправлено письмо активации. Перейдите по ссылке из письма и авторизуйтесь снова") }</script>';
                        }
                    }else{
                    //Отображаем сообщение, что E-mail и пароль не найдены
                    echo '<script>window.onload = function() { Swal.fire("Неверно введен E-mail или пароль") }</script>';
                    }
                }else{
                    //Отображаем сообщение, что E-mail и пароль не найдены
                    echo '<script>window.onload = function() { Swal.fire("Пользователь не найден") }</script>';
                }

            }else{echo '<script>window.onload = function() { Swal.fire("Что-то пошло не так, проверьте E-mail") }</script>';}
        }else{
            if ($_POST["token"]){
                echo '<script>window.onload = function() { Swal.fire("Авторизация не выполнена. Токен устарел. Обновите страницу") }</script>';
            }
        }
        
        $token = hash('gost-crypto', random_int(0,999999));
        $_SESSION["CSRF"] = $token;

        if(!$_SESSION['id']){
    ?>
    <div class="container__login">
        <div class="container__content">
        <h1>Добро пожаловать в чат SF!</h1>
        <h2>Введите свой E-mail и пароль</h2>
        <br>
        <form method="post" action="">
            <input type="text" name="email" placeholder="E-mail"><br/>
            <input type="password" name="pass"  placeholder="Пароль"> <br/>
            <input type="hidden" name="token" value="<?=$token?>"> <br/>
            <input type="submit" value="Войти">
        </form>

        <a href="?register">Зарегистрироваться</a>
        </div>
    </div>
    <?php }else{
        mysqli_query($link,'UPDATE `users` SET `token`=\''.$_SESSION["CSRF"].'\' where `id`=\''.$_SESSION['id'].'\'');
        ?>
        <script>
                    window.location.replace("./index.php");
        </script>
    <?php } ?>
    
</div>

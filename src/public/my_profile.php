<?php
session_start([
    'cookie_lifetime' => 86400*30*12,
]);
require_once("db.php");
require_once("user_profile.php");
include_once('file_upload.php');


$myAccauntInfo=mysqli_query($link, "SELECT * FROM users WHERE token = '". $_SESSION['CSRF'] ."'");
if($myAccauntInfo->num_rows > 0){
    $Myaccaunt = mysqli_fetch_assoc($myAccauntInfo);
    if(isset($_POST['nameUpdate'])){//для обновления имени
        $jsonName=json_decode($_POST['nameUpdate'],true);
        $nameUpdate=(empty(clearGP($jsonName['name']))) ? 'NULL' : '"'.clearGP($jsonName['name']).'"';
        $rslt=mysqli_query($link, "UPDATE users SET name = $nameUpdate WHERE id='".$Myaccaunt['id']."'");
        if($rslt > 0){echo json_encode(array("stat"=>"true","name"=>$nameUpdate));}else{echo json_encode(array("stat"=>"false","errmsg"=>"Имя уже занято"));}
    }elseif (isset($_POST['showemail'])) {//для скрытия или показа эмейл
        $showEmailUpdate=(clearGPint($_POST['showemail'])=="0") ? "0" : "1";
        $rslt=mysqli_query($link, "UPDATE users SET showemail = '$showEmailUpdate' WHERE id='".$Myaccaunt['id']."'");
        if($rslt > 0){echo json_encode(array("stat"=>"true", "check"=>$showEmailUpdate));}else{echo json_encode(array("stat"=>"false","errmsg"=>"Что-то пошло не так, обновите страницу и попробуйте снова"));}
    }
    elseif (!empty($_FILES)) {//для загрузки файла
        echo upFile($link,'users',$Myaccaunt['id'],$Myaccaunt['avatar']);
    }else{
       $My_profile = new User_Profile($Myaccaunt['id'], $Myaccaunt['email'], $Myaccaunt['showemail'], $Myaccaunt['avatar'],$Myaccaunt['friends'],$Myaccaunt['name']);
?>
<div class="container__myProfile">
    <div class="container__myProfile__sidebar">
        <div class="container__myProfile__sidebar__photo" id="photoProfile" style="width:400px;height:300px;background:url('<?php echo $My_profile->get_avatar() ?>');background-size:cover;background-position:center;"></div>
        <div class="container__myProfile__sidebar__photo_edit" id="Photoedit">Изменить</div>
        <form  class="container__myProfile__sidebar__photo_uploadImage displaynone" id="imageuploads" method="POST" enctype="multipart/form-data">
            <input type="file" name="file_nm" id="inputfilephoto">
            <button type="submit" role="button">Заменить фото</button>
        </form>
    </div>
    <div class="container__myProfile__sidebar">
        <h2 class="container__myProfile__sidebar__name">Привет, <span id="myName"><?php echo $My_profile->get_name(); ?> </span><span id="editName"><img style="vertical-align: bottom;" width="30px" height="30px" src="./image/edit.svg" alt="Изменить"></span></h2>
        <form class="container__myProfile__sidebar__form displaynone" id="formEditName"><input type="text" name="nameUpdate" id="newName" placeholder="Придумайте новый псевдоним"><button type="submit" id="submitName">Применить</button></form>
        <div class="container__myProfile__sidebar__email"><p>Ваш E-mail: <?php echo $My_profile->get_email(); ?></p></div>
        <div class="container__myProfile__sidebar__showemail"><label for="showemail">Скрывать E-mail при поиске друзей?</label> <input type="checkbox" id="showemail" name="showemail" <?php echo $My_profile->get_showemail(); ?>></div>
    </div>
    <div class="close_profile">X</div>

</div>




<?php
    }
}
?>
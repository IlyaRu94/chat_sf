<?php
session_start();
require_once("db.php");
require_once("user_profile.php");
include_once('file_upload.php');

if(!empty($_SESSION['CSRF']) and !empty($_SESSION['id'])){
    if(isset($_POST['namegroup'])){
        if(!empty(clearGP($_POST['namegroup']))){
            $namenewgroup=clearGP($_POST['namegroup']);
            if(!empty(clearGPfriends($_POST['friendsgroup']))){
                $friendsnewgroup=clearGPfriends($_POST['friendsgroup']);
                if(!empty($_FILES)) {
                    //echo upFile('','','','');
                    $jsonimageurldecode=json_decode(upFile('','','',''),true);
                    $avatarnewgroup=(!empty($jsonimageurldecode['url'])) ? './'.$jsonimageurldecode['url'] : '' ;
                    //запрос в бд для создания группы
                    mysqli_query($link, 'INSERT INTO `groupChat`(`isAdmin`,`nameGroup`, `usersId`, `avatar`) VALUES(\''.$_SESSION['id'].'\', \''.$namenewgroup.'\', \''.$friendsnewgroup.'\', \''.$avatarnewgroup.'\');');
                    
                }else{
                    mysqli_query($link, 'INSERT INTO `groupChat`(`isAdmin`,nameGroup`, `usersId`, `avatar`) VALUES(\''.$_SESSION['id'].'\', \''.$namenewgroup.'\', \''.$friendsnewgroup.'\', \'\');');
                }
                $id = mysqli_insert_id($link);
                $idfliendstogroup='("'.str_replace(',','","',$friendsnewgroup).'")';
                mysqli_query($link, "UPDATE users SET friends = IF (friends = '', 'ch".$id."', CONCAT(friends, ',ch".$id."')) WHERE id in $idfliendstogroup");
                echo json_encode(array("stat"=>"true","msg"=>'Группа создана','insert'=>'group', 'groupid'=>'ch'.$id, 'url'=>(!empty($avatarnewgroup)) ? $avatarnewgroup : '' ));
            }else{echo json_encode(array("stat"=>"false","msg"=>'Не выбраны собеседники '));}
        }else{echo json_encode(array("stat"=>"false","msg"=>'Не задано имя группы '));}

    }else{
?>
<div class="container__group">
    <div class="container__group__sidebar">
        <h2>Создание групповой беседы</h2>
        <div class="container__group__sidebar__photo" id="photogroup" style="width:400px;height:400px;background:url('./image/avatar.png');background-size:cover;background-position:center;"></div>
    </div>
    <div class="container__group__sidebar">
        
        <form class="container__group__form" id="imagegroupupload">
            <input type="next" name="namegroup" id="nameGroup" class="container__group__input" placeholder="Введите название группы">
            <p style="padding-left:5px;">Выберите друзей:</p>
            <div id="myfriendstogroup"></div>
            <input type="text" hidden name="friendsgroup" id="friendsgroup" class="container__group__input__friends">
            <p style="padding-left:5px;">Выберите фото группы:</p>
            <input type="file" name="file_nm" id="inputfilephotogroup">
            <button type="submit" role="button">Создать группу</button>
        </form>
    </div>
    <div class="close_group">X</div>
</div>

<?php } } ?>
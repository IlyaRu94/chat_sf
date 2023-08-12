<?php
require_once("config.php");
// Если файл был отправлен

function upFile($link,$table,$id,$oldimage){
        $fileName = $_FILES['file_nm']['name'];
        // Проверяем размер
        if ($_FILES['file_nm']['size'] > UPLOAD_MAX_SIZE) {
            return json_encode(array("stat"=>"false","msg"=>'Недопостимый размер файла ' . $fileName));
        }
        // Проверяем формат
        if (!in_array($_FILES['file_nm']['type'], ALLOWED_TYPES)) {
            return json_encode(array("stat"=>"false","msg"=>'Недопустимый формат файла ' . $fileName));
        }
        //переименуем файл в уникальное имя
        $temp = explode(".", $fileName);
        $newfilename = round(microtime(true)) . '.' . end($temp);
        $filePath = UPLOAD_DIR . '/' .$newfilename;
        // Пытаемся загрузить файл
        if (!move_uploaded_file($_FILES['file_nm']['tmp_name'], $filePath)) {
            return json_encode(array("stat"=>"false","msg"=>'Ошибка загрузки файла ' . $fileName));
        }
        if (empty($errors)) {
            if(!empty($table)){
                mysqli_query($link, "UPDATE $table SET avatar = '".'./'.$filePath."' WHERE id='$id'");
            }
           //проверяем и удаляем старый файл
           if(!empty($oldimage)){
                if(file_exists($oldimage)){
                        unlink($oldimage);
                }
            }
        }
    if (empty($errors)) {
        return json_encode(array("stat"=>"true","msg"=>'Фото успешно загружено',"url"=>$filePath));
    }
}


?>
<?php
require_once "../assets/database.php";

$id = $_POST['id'];
$title = $_POST['title'];
$description = $_POST['description'];
$category = $_POST['category'];
$date = $_POST['publish_date'];

if($id==""){

$sql="INSERT INTO cm_news (title,description,category,publish_date)
VALUES ('$title','$description','$category','$date')";

}else{

$sql="UPDATE cm_news 
SET title='$title',
description='$description',
category='$category',
publish_date='$date'
WHERE id=$id";

}

$savienojums->query($sql);

header("Location: news.php");
exit;
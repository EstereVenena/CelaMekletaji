<?php
require_once "../assets/database.php";

$id = $_GET['id'];

$sql = "DELETE FROM cm_news WHERE id=$id";

$savienojums->query($sql);

header("Location: news.php");
exit;
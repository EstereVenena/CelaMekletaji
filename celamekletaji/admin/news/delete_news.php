<?php
require_once __DIR__ . "/../../includes/config/database.php";

$id = $_GET['id'];

$sql = "DELETE FROM cm_news WHERE id=$id";

$savienojums->query($sql);

header("Location: news.php");
exit;
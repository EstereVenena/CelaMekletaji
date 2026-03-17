<?php

require_once "../assets/database.php";

$id = $_GET['id'];

$sql = "DELETE FROM cm_clubs WHERE id=?";
$stmt = $savienojums->prepare($sql);
$stmt->bind_param("i",$id);
$stmt->execute();

header("Location: clubs.php");
exit;
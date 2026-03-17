<?php

require_once "../assets/database.php";

$id = $_POST['id'] ?? null;
$name = $_POST['name'];
$address = $_POST['address'];

if ($id) {

$sql = "UPDATE cm_clubs 
SET name=?, address=? 
WHERE id=?";

$stmt = $savienojums->prepare($sql);
$stmt->bind_param("ssi",$name,$address,$id);

} else {

$sql = "INSERT INTO cm_clubs (name,address)
VALUES (?,?)";

$stmt = $savienojums->prepare($sql);
$stmt->bind_param("ss",$name,$address);

}

$stmt->execute();

header("Location: clubs.php");
exit;
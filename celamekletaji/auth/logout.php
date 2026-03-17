<?php
session_start();
require_once "assets/database.php";

if (isset($_SESSION["lietotajs_id"])) {
    $stmt = $savienojums->prepare("
        UPDATE cm_lietotaji
        SET remember_token = NULL
        WHERE lietotajs_id = ?
    ");
    $stmt->bind_param("i", $_SESSION["lietotajs_id"]);
    $stmt->execute();
}

setcookie("remember_token", "", time() - 3600, "/");
session_destroy();

header("Location: login.php");
exit;

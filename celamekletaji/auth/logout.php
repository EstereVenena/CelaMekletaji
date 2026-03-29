<?php
session_start();

require_once __DIR__ . "/../includes/config/app.php";
require_once __DIR__ . "/../includes/config/database.php";

if (isset($_SESSION["lietotajs_id"])) {
    $stmt = $savienojums->prepare("
        UPDATE cm_lietotaji
        SET remember_token = NULL
        WHERE lietotajs_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("i", $_SESSION["lietotajs_id"]);
        $stmt->execute();
        $stmt->close();
    }
}

setcookie("remember_token", "", [
    "expires" => time() - 3600,
    "path" => "/",
    "secure" => true,
    "httponly" => true,
    "samesite" => "Lax"
]);

$_SESSION = [];
session_unset();
session_destroy();

redirect("public/about.php");
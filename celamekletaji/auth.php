<?php
session_start();
require_once "assets/database.php";

// auto login ar remember me
if (!isset($_SESSION["lietotajs_id"]) && isset($_COOKIE["remember_token"])) {
    $stmt = $savienojums->prepare("
        SELECT lietotajs_id, lietotajvards, loma
        FROM cm_lietotaji
        WHERE remember_token = ?
    ");
    $stmt->bind_param("s", $_COOKIE["remember_token"]);
    $stmt->execute();
    $rez = $stmt->get_result();

    if ($rez->num_rows === 1) {
        $u = $rez->fetch_assoc();
        session_regenerate_id(true);

        $_SESSION["lietotajs_id"] = $u["lietotajs_id"];
        $_SESSION["lietotajvards"] = $u["lietotajvards"];
        $_SESSION["loma"] = $u["loma"];
    }
}

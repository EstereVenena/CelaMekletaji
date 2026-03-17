<?php
function logLogin($savienojums, $lietotajs, $statuss) {
    $ip = $_SERVER["REMOTE_ADDR"];

    $stmt = $savienojums->prepare("
        INSERT INTO cm_login_logi (lietotajs, ip, statuss)
        VALUES (?, ?, ?)
    ");
    $stmt->bind_param("sss", $lietotajs, $ip, $statuss);
    $stmt->execute();
}

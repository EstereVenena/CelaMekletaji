<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/../includes/config/app.php";
require_once __DIR__ . "/../includes/config/database.php";

$redirectType = $_GET["redirect"] ?? "about";

/* ===============================
   DZĒŠ REMEMBER TOKEN NO DB
================================ */
if (isset($_SESSION["lietotajs_id"])) {
    $stmt = $savienojums->prepare("
        UPDATE cm_lietotaji
        SET remember_token = NULL
        WHERE lietotajs_id = ?
    ");

    if ($stmt) {
        $lietotajsId = (int) $_SESSION["lietotajs_id"];
        $stmt->bind_param("i", $lietotajsId);
        $stmt->execute();
        $stmt->close();
    }
}

/* ===============================
   DZĒŠ REMEMBER COOKIE
================================ */
$isHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";

setcookie("remember_token", "", [
    "expires"  => time() - 3600,
    "path"     => "/",
    "secure"   => $isHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

/* ===============================
   DZĒŠ PHP SESIJAS COOKIE
================================ */
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();

    setcookie(session_name(), "", [
        "expires"  => time() - 3600,
        "path"     => $params["path"],
        "domain"   => $params["domain"],
        "secure"   => $isHttps,
        "httponly" => true,
        "samesite" => "Lax"
    ]);
}

/* ===============================
   DZĒŠ PHP SESIJU
================================ */
$_SESSION = [];
session_unset();
session_destroy();

/* ===============================
   DROŠA NOVIRZĪŠANA
================================ */
$redirects = [
    "home"    => "index.php",
    "about"   => "public/about.php",
    "clubs"   => "public/clubs.php",
    "gallery" => "public/gallery.php",
    "privacy" => "public/privatumapolitika.php",
    "login"   => "auth/login.php",
];

$target = $redirects[$redirectType] ?? "public/about.php";

redirect($target);
exit();
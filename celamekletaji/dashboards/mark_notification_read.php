<?php
session_start();

require_once __DIR__ . "/../includes/config/database.php";

if (!isset($_SESSION['lietotajs_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = (int)$_SESSION['lietotajs_id'];
$notificationId = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($notificationId <= 0) {
    header("Location: notifications.php");
    exit;
}

$sql = "
    UPDATE cm_notifications
    SET is_read = 1
    WHERE id = ?
    AND user_id = ?
";

$stmt = $savienojums->prepare($sql);
$stmt->bind_param("ii", $notificationId, $userId);
$stmt->execute();

header("Location: notifications.php");
exit;
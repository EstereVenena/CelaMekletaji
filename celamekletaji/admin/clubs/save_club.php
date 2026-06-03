<?php
session_start();

require_once __DIR__ . "/../../includes/config/database.php";

/* ===============================
   DROŠĪBA: TIKAI ADMIN
================================ */
if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["admin", "Admin"], true)
) {
    header("Location: ../../auth/login.php");
    exit();
}

/* ===============================
   DATI NO FORMAS
================================ */
$id = (int)($_POST["id"] ?? 0);
$name = trim($_POST["name"] ?? "");
$address = trim($_POST["address"] ?? "");

if ($name === "" || $address === "") {
    header("Location: clubs.php?error=empty");
    exit();
}

/* ===============================
   REDIĢĒŠANA
================================ */
if ($id > 0) {
    $sql = "
        UPDATE cm_clubs
        SET name = ?, address = ?
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($sql);

    if (!$stmt) {
        header("Location: clubs.php?error=prepare");
        exit();
    }

    $stmt->bind_param("ssi", $name, $address, $id);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: clubs.php?success=updated");
        exit();
    }

    $stmt->close();
    header("Location: clubs.php?error=update");
    exit();
}

/* ===============================
   PIEVIENOŠANA
================================ */
$sql = "
    INSERT INTO cm_clubs
        (name, address)
    VALUES
        (?, ?)
";

$stmt = $savienojums->prepare($sql);

if (!$stmt) {
    header("Location: clubs.php?error=prepare");
    exit();
}

$stmt->bind_param("ss", $name, $address);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: clubs.php?success=created");
    exit();
}

$stmt->close();

header("Location: clubs.php?error=create");
exit();
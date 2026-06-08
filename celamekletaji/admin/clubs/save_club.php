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
   PROGRAMMU SAGLABĀŠANAS FUNKCIJA
================================ */
function saveClubPrograms(mysqli $db, int $clubId, array $programs): void
{
    $deleteStmt = $db->prepare("
        DELETE FROM cm_club_programs
        WHERE club_id = ?
    ");

    if ($deleteStmt) {
        $deleteStmt->bind_param("i", $clubId);
        $deleteStmt->execute();
        $deleteStmt->close();
    }

    if (empty($programs)) {
        return;
    }

    $insertStmt = $db->prepare("
        INSERT INTO cm_club_programs
            (club_id, program_id)
        VALUES
            (?, ?)
    ");

    if (!$insertStmt) {
        return;
    }

    foreach ($programs as $programId) {
        $programId = (int)$programId;

        if ($programId <= 0) {
            continue;
        }

        $insertStmt->bind_param("ii", $clubId, $programId);
        $insertStmt->execute();
    }

    $insertStmt->close();
}

/* ===============================
   DATI NO FORMAS
================================ */
$id = (int)($_POST["id"] ?? 0);

$name = trim($_POST["name"] ?? "");
$address = trim($_POST["address"] ?? "");
$city = trim($_POST["city"] ?? "");
$description = trim($_POST["description"] ?? "");

$churchId = (int)($_POST["church_id"] ?? 0);
$directorId = (int)($_POST["director_id"] ?? 0);
$programs = $_POST["programs"] ?? [];

if ($name === "" || $city === "") {
    header("Location: clubs.php?error=empty");
    exit();
}

$churchValue = $churchId > 0 ? $churchId : null;
$directorValue = $directorId > 0 ? $directorId : null;

/* ===============================
   ATTĒLA AUGŠUPIELĀDE
================================ */
$imagePath = null;

if (!empty($_FILES["image"]["name"])) {
    $allowedExtensions = ["jpg", "jpeg", "png", "webp", "gif"];
    $maxSize = 5 * 1024 * 1024;

    if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
        header("Location: clubs.php?error=image_upload");
        exit();
    }

    if ($_FILES["image"]["size"] > $maxSize) {
        header("Location: clubs.php?error=image_size");
        exit();
    }

    $originalName = $_FILES["image"]["name"];
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    if (!in_array($extension, $allowedExtensions, true)) {
        header("Location: clubs.php?error=image_type");
        exit();
    }

    $uploadDir = __DIR__ . "/../../assets/images/clubs/";

    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0775, true);
    }

    $safeName = "club_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;
    $targetPath = $uploadDir . $safeName;

    if (!move_uploaded_file($_FILES["image"]["tmp_name"], $targetPath)) {
        header("Location: clubs.php?error=image_save");
        exit();
    }

    $imagePath = "assets/images/clubs/" . $safeName;
}

/* ===============================
   REDIĢĒŠANA
================================ */
if ($id > 0) {
    if ($imagePath !== null) {
        $sql = "
            UPDATE cm_clubs
            SET
                name = ?,
                address = ?,
                city = ?,
                director_id = ?,
                church_id = ?,
                description = ?,
                image_path = ?
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if (!$stmt) {
            header("Location: clubs.php?error=prepare");
            exit();
        }

        $stmt->bind_param(
            "sssiissi",
            $name,
            $address,
            $city,
            $directorValue,
            $churchValue,
            $description,
            $imagePath,
            $id
        );
    } else {
        $sql = "
            UPDATE cm_clubs
            SET
                name = ?,
                address = ?,
                city = ?,
                director_id = ?,
                church_id = ?,
                description = ?
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if (!$stmt) {
            header("Location: clubs.php?error=prepare");
            exit();
        }

        $stmt->bind_param(
            "sssiisi",
            $name,
            $address,
            $city,
            $directorValue,
            $churchValue,
            $description,
            $id
        );
    }

    if (!$stmt->execute()) {
        $stmt->close();
        header("Location: clubs.php?error=update");
        exit();
    }

    $stmt->close();

    saveClubPrograms($savienojums, $id, $programs);

    header("Location: clubs.php?success=updated");
    exit();
}

/* ===============================
   PIEVIENOŠANA
================================ */
$sql = "
    INSERT INTO cm_clubs
        (name, address, city, director_id, church_id, description, image_path)
    VALUES
        (?, ?, ?, ?, ?, ?, ?)
";

$stmt = $savienojums->prepare($sql);

if (!$stmt) {
    header("Location: clubs.php?error=prepare");
    exit();
}

$stmt->bind_param(
    "sssiiss",
    $name,
    $address,
    $city,
    $directorValue,
    $churchValue,
    $description,
    $imagePath
);

if (!$stmt->execute()) {
    $stmt->close();
    header("Location: clubs.php?error=create");
    exit();
}

$newClubId = $stmt->insert_id;
$stmt->close();

saveClubPrograms($savienojums, $newClubId, $programs);

header("Location: clubs.php?success=created");
exit();
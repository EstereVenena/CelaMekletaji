<?php
require_once "../assets/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {

    $year = $_POST['year'] ?? '';
    $creator = $_POST['creator'] ?? '';
    $category = $_POST['category'] ?? '';
    $uploaded_by = 1;

    $galleryDir = "../images/gallery/";

    if (!is_dir($galleryDir)) {
        mkdir($galleryDir, 0755, true);
    }

    $files = $_FILES['images'];
    $count = count($files['name']);

    for ($i = 0; $i < $count; $i++) {

        if ($files['error'][$i] === UPLOAD_ERR_OK) {

            $tmpName = $files['tmp_name'][$i];

            $filename = preg_replace('/[^A-Za-z0-9\.\-_]/', '_', basename($files['name'][$i]));

            $mime = mime_content_type($tmpName);

            if (strpos($mime, 'image/') === 0) {

                $newPath = $galleryDir . uniqid() . '_' . $filename;

                if (move_uploaded_file($tmpName, $newPath)) {

                    $relativePath = str_replace('../', '', $newPath);

                    $stmt = $savienojums->prepare("
                        INSERT INTO cm_gallery_images 
                        (filename, path, year, creator, category, uploaded_by) 
                        VALUES (?, ?, ?, ?, ?, ?)
                    ");

                    $stmt->bind_param("ssissi", $filename, $relativePath, $year, $creator, $category, $uploaded_by);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    header("Location: gallery.php?success=1");
    exit;

} else {
    die("Invalid request.");
}
?>
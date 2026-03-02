<?php
require_once "../assets/database.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    $year = $_POST['year'] ?? '';
    $creator = $_POST['creator'] ?? '';
    $category = $_POST['category'] ?? '';
    $uploaded_by = 1; // Assume admin id 1, or get from session

    // Create gallery directory if not exists
    $galleryDir = "../images/gallery/";
    if (!is_dir($galleryDir)) {
        mkdir($galleryDir, 0755, true);
    }

    // Process uploaded files
    $files = $_FILES['images'];
    $count = count($files['name']);
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $tmpName = $files['tmp_name'][$i];
            $filename = basename($files['name'][$i]);
            $mime = mime_content_type($tmpName);
            if (strpos($mime, 'image/') === 0) {
                // It's an image
                $newPath = $galleryDir . uniqid() . '_' . $filename;
                if (move_uploaded_file($tmpName, $newPath)) {
                    // Insert into DB
                    $relativePath = str_replace('../', '', $newPath);
                    $stmt = $savienojums->prepare("INSERT INTO cm_gallery_images (filename, path, year, creator, category, uploaded_by) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("ssissi", $filename, $relativePath, $year, $creator, $category, $uploaded_by);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }

    header("Location: index.php?success=1");
    exit;
} else {
    die("Invalid request.");
}
?>
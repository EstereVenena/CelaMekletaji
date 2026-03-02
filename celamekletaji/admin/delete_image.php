<?php
require_once "../assets/database.php";

if (isset($_GET['id'])) {
    $id = (int)$_GET['id'];

    // Get path
    $stmt = $savienojums->prepare("SELECT path FROM cm_gallery_images WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $path = $row['path'];
        // Delete file
        if (file_exists($path)) {
            unlink($path);
        }
        // Delete from DB
        $stmt2 = $savienojums->prepare("DELETE FROM cm_gallery_images WHERE id = ?");
        $stmt2->bind_param("i", $id);
        $stmt2->execute();
        $stmt2->close();
    }
    $stmt->close();

    header("Location: index.php");
    exit;
}
?>
<?php
require_once "../assets/database.php";

// Function to convert image to JPEG
function convertToJpeg($sourcePath, $destinationPath, $quality = 85) {
    $imageInfo = getimagesize($sourcePath);
    $mimeType = $imageInfo['mime'];
    
    switch($mimeType) {
        case 'image/jpeg':
        case 'image/jpg':
            $image = imagecreatefromjpeg($sourcePath);
            break;
        case 'image/png':
            $image = imagecreatefrompng($sourcePath);
            break;
        case 'image/gif':
            $image = imagecreatefromgif($sourcePath);
            break;
        case 'image/webp':
            $image = imagecreatefromwebp($sourcePath);
            break;
        case 'image/bmp':
            $image = imagecreatefrombmp($sourcePath);
            break;
        default:
            return false;
    }
    
    // Create a true color image with white background (for transparent images)
    $width = imagesx($image);
    $height = imagesy($image);
    $whiteBg = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($whiteBg, 255, 255, 255);
    imagefilledrectangle($whiteBg, 0, 0, $width, $height, $white);
    imagecopy($whiteBg, $image, 0, 0, 0, 0, $width, $height);
    
    // Save as JPEG
    $result = imagejpeg($whiteBg, $destinationPath, $quality);
    
    // Clean up
    imagedestroy($image);
    imagedestroy($whiteBg);
    
    return $result;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['images'])) {
    
    $year = $_POST['year'] ?? '';
    $creator = $_POST['creator'] ?? '';
    $category = $_POST['category'] ?? '';
    $uploaded_by = 1; // You might want to get this from session
    
    $galleryDir = "../images/gallery/";
    $tempDir = "../images/temp/";
    
    // Create directories if they don't exist
    if (!is_dir($galleryDir)) {
        mkdir($galleryDir, 0755, true);
    }
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }
    
    $files = $_FILES['images'];
    $count = count($files['name']);
    
    for ($i = 0; $i < $count; $i++) {
        
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            
            $tmpName = $files['tmp_name'][$i];
            $originalName = $files['name'][$i];
            $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
            
            // Check if it's a ZIP file
            if ($fileExt === 'zip') {
                // Create a unique temp directory for this upload
                $uniqueId = uniqid();
                $extractPath = $tempDir . $uniqueId . '/';
                mkdir($extractPath, 0755, true);
                
                $zip = new ZipArchive;
                if ($zip->open($tmpName) === TRUE) {
                    $zip->extractTo($extractPath);
                    $zip->close();
                    
                    // Scan extracted files
                    $extractedFiles = scandir($extractPath);
                    foreach ($extractedFiles as $file) {
                        if ($file != '.' && $file != '..') {
                            $filePath = $extractPath . $file;
                            
                            // Check if it's an image file
                            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                            $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                            
                            if (in_array($ext, $imageExtensions)) {
                                // Generate new filename with .jpg extension
                                $newFilename = uniqid() . '_' . pathinfo($file, PATHINFO_FILENAME) . '.jpg';
                                $newPath = $galleryDir . $newFilename;
                                
                                // Convert to JPEG
                                if (convertToJpeg($filePath, $newPath)) {
                                    $relativePath = str_replace('../', '', $newPath);
                                    
                                    // Save to database
                                    $stmt = $savienojums->prepare("
                                        INSERT INTO cm_gallery_images 
                                        (filename, path, year, creator, category, uploaded_by) 
                                        VALUES (?, ?, ?, ?, ?, ?)
                                    ");
                                    
                                    $stmt->bind_param("ssissi", $newFilename, $relativePath, $year, $creator, $category, $uploaded_by);
                                    $stmt->execute();
                                    $stmt->close();
                                }
                            }
                        }
                    }
                    
                    // Clean up extracted files
                    array_map('unlink', glob("$extractPath*"));
                    rmdir($extractPath);
                    
                } else {
                    // Handle ZIP open error
                    error_log("Failed to open ZIP file: $originalName");
                }
                
            } else {
                // Handle regular image uploads (keep your existing code)
                $imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
                
                if (in_array($fileExt, $imageExtensions)) {
                    $mime = mime_content_type($tmpName);
                    
                    if (strpos($mime, 'image/') === 0) {
                        // Generate new filename with .jpg extension
                        $newFilename = uniqid() . '_' . pathinfo($originalName, PATHINFO_FILENAME) . '.jpg';
                        $newPath = $galleryDir . $newFilename;
                        
                        // Convert to JPEG
                        if (convertToJpeg($tmpName, $newPath)) {
                            $relativePath = str_replace('../', '', $newPath);
                            
                            $stmt = $savienojums->prepare("
                                INSERT INTO cm_gallery_images 
                                (filename, path, year, creator, category, uploaded_by) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            
                            $stmt->bind_param("ssissi", $newFilename, $relativePath, $year, $creator, $category, $uploaded_by);
                            $stmt->execute();
                            $stmt->close();
                        }
                    }
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
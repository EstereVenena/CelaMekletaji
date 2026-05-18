<?php
session_start();

require_once __DIR__ . "/../../includes/config/database.php";

/*
|--------------------------------------------------------------------------
| Galerijas attēlu augšupielāde
|--------------------------------------------------------------------------
| Atbalsta:
| - vairākus attēlus vienlaicīgi
| - ZIP failu ar attēliem
| - automātisku pārveidošanu uz JPG
|--------------------------------------------------------------------------
*/

// Ja lietotājs nav ielogojies, var pārsūtīt uz login lapu
// Ja pagaidām testē bez login, šo bloku vari aizkomentēt
if (!isset($_SESSION["lietotajs_id"])) {
    header("Location: ../../auth/login.php");
    exit();
}

$uploaded_by = (int) $_SESSION["lietotajs_id"];

// Pārbauda, vai pieprasījums ir pareizs
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    die("Kļūda: fails upload_gallery.php tika atvērts tieši vai forma nesūta ar POST.");
}

if (!isset($_FILES["images"])) {
    die("Kļūda: nav atrasts failu lauks images[]. Pārbaudi, vai input ir name='images[]'.");
}

if (empty($_FILES["images"]["name"][0])) {
    die("Kļūda: nav izvēlēts neviens fails.");
}

// Ievaddati no formas
$year     = isset($_POST["year"]) ? (int) $_POST["year"] : null;
$creator  = trim($_POST["creator"] ?? "");
$category = trim($_POST["category"] ?? "");

// Mapes
$galleryDir = __DIR__ . "/../../images/gallery/";
$tempDir    = __DIR__ . "/../../images/temp/";

// Ceļš, ko saglabā datubāzē
$relativeGalleryDir = "images/gallery/";

// Atļautie attēlu paplašinājumi
$imageExtensions = ["jpg", "jpeg", "png", "gif", "webp", "bmp"];

// Izveido mapes, ja tās neeksistē
if (!is_dir($galleryDir)) {
    mkdir($galleryDir, 0755, true);
}

if (!is_dir($tempDir)) {
    mkdir($tempDir, 0755, true);
}

/**
 * Faila nosaukuma attīrīšana
 */
function cleanFileName(string $filename): string
{
    $filename = pathinfo($filename, PATHINFO_FILENAME);
    $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
    return trim($filename, "_");
}

/**
 * Rekursīva mapes dzēšana
 */
function deleteDirectory(string $dir): void
{
    if (!is_dir($dir)) {
        return;
    }

    $items = scandir($dir);

    foreach ($items as $item) {
        if ($item === "." || $item === "..") {
            continue;
        }

        $path = $dir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($path)) {
            deleteDirectory($path);
        } else {
            unlink($path);
        }
    }

    rmdir($dir);
}

/**
 * Attēla pārveidošana uz JPG
 */
function convertToJpeg(string $sourcePath, string $destinationPath, int $quality = 85): bool
{
    if (!file_exists($sourcePath)) {
        return false;
    }

    $imageInfo = getimagesize($sourcePath);

    if ($imageInfo === false || empty($imageInfo["mime"])) {
        return false;
    }

    $mimeType = $imageInfo["mime"];

    switch ($mimeType) {
        case "image/jpeg":
        case "image/jpg":
            $image = imagecreatefromjpeg($sourcePath);
            break;

        case "image/png":
            $image = imagecreatefrompng($sourcePath);
            break;

        case "image/gif":
            $image = imagecreatefromgif($sourcePath);
            break;

        case "image/webp":
            $image = imagecreatefromwebp($sourcePath);
            break;

        case "image/bmp":
            $image = imagecreatefrombmp($sourcePath);
            break;

        default:
            return false;
    }

    if (!$image) {
        return false;
    }

    $width  = imagesx($image);
    $height = imagesy($image);

    // Balts fons caurspīdīgiem PNG/GIF/WEBP attēliem
    $whiteBg = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($whiteBg, 255, 255, 255);

    imagefilledrectangle($whiteBg, 0, 0, $width, $height, $white);
    imagecopy($whiteBg, $image, 0, 0, 0, 0, $width, $height);

    $result = imagejpeg($whiteBg, $destinationPath, $quality);

    imagedestroy($image);
    imagedestroy($whiteBg);

    return $result;
}

/**
 * Attēla saglabāšana datubāzē
 */
function saveImageToDatabase(
    mysqli $savienojums,
    string $newFilename,
    string $relativePath,
    ?int $year,
    string $creator,
    string $category,
    int $uploaded_by
): bool {
    $stmt = $savienojums->prepare("
        INSERT INTO cm_gallery_images 
            (filename, path, year, creator, category, uploaded_by) 
        VALUES 
            (?, ?, ?, ?, ?, ?)
    ");

    if (!$stmt) {
        error_log("Prepare failed: " . $savienojums->error);
        return false;
    }

    $stmt->bind_param(
        "ssissi",
        $newFilename,
        $relativePath,
        $year,
        $creator,
        $category,
        $uploaded_by
    );

    $result = $stmt->execute();

    if (!$result) {
        error_log("Execute failed: " . $stmt->error);
    }

    $stmt->close();

    return $result;
}

/**
 * Viena attēla apstrāde
 */
function processImageFile(
    mysqli $savienojums,
    string $sourcePath,
    string $originalName,
    string $galleryDir,
    string $relativeGalleryDir,
    ?int $year,
    string $creator,
    string $category,
    int $uploaded_by
): bool {
    $cleanName = cleanFileName($originalName);

    if ($cleanName === "") {
        $cleanName = "image";
    }

    $newFilename = uniqid("gallery_", true) . "_" . $cleanName . ".jpg";
    $newPath = $galleryDir . $newFilename;
    $relativePath = $relativeGalleryDir . $newFilename;

    if (!convertToJpeg($sourcePath, $newPath)) {
        error_log("Image conversion failed: " . $originalName);
        return false;
    }

    return saveImageToDatabase(
        $savienojums,
        $newFilename,
        $relativePath,
        $year,
        $creator,
        $category,
        $uploaded_by
    );
}

$files = $_FILES["images"];
$count = count($files["name"]);

$successCount = 0;
$errorCount = 0;

for ($i = 0; $i < $count; $i++) {

    if ($files["error"][$i] !== UPLOAD_ERR_OK) {
        $errorCount++;
        error_log("Upload error for file: " . $files["name"][$i]);
        continue;
    }

    $tmpName = $files["tmp_name"][$i];
    $originalName = $files["name"][$i];
    $fileExt = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));

    /*
    |--------------------------------------------------------------------------
    | ZIP faila apstrāde
    |--------------------------------------------------------------------------
    */
    if ($fileExt === "zip") {

        $uniqueId = uniqid("zip_", true);
        $extractPath = $tempDir . $uniqueId . "/";

        if (!mkdir($extractPath, 0755, true)) {
            $errorCount++;
            error_log("Could not create temp directory.");
            continue;
        }

        $zip = new ZipArchive();

        if ($zip->open($tmpName) === true) {

            $zip->extractTo($extractPath);
            $zip->close();

            // Rekursīvi pārbauda ZIP saturu, arī apakšmapes
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($extractPath, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                $filePath = $file->getPathname();
                $fileName = $file->getFilename();

                if (!is_file($filePath)) {
                    continue;
                }

                $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

                if (!in_array($ext, $imageExtensions, true)) {
                    continue;
                }

                if (processImageFile(
                    $savienojums,
                    $filePath,
                    $fileName,
                    $galleryDir,
                    $relativeGalleryDir,
                    $year,
                    $creator,
                    $category,
                    $uploaded_by
                )) {
                    $successCount++;
                } else {
                    $errorCount++;
                }
            }

            deleteDirectory($extractPath);

        } else {
            $errorCount++;
            error_log("Failed to open ZIP file: " . $originalName);
            deleteDirectory($extractPath);
        }

        continue;
    }

    /*
    |--------------------------------------------------------------------------
    | Parasta attēla apstrāde
    |--------------------------------------------------------------------------
    */
    if (!in_array($fileExt, $imageExtensions, true)) {
        $errorCount++;
        error_log("Unsupported file extension: " . $originalName);
        continue;
    }

    $mime = mime_content_type($tmpName);

    if ($mime === false || strpos($mime, "image/") !== 0) {
        $errorCount++;
        error_log("Invalid image MIME type: " . $originalName);
        continue;
    }

    if (processImageFile(
        $savienojums,
        $tmpName,
        $originalName,
        $galleryDir,
        $relativeGalleryDir,
        $year,
        $creator,
        $category,
        $uploaded_by
    )) {
        $successCount++;
    } else {
        $errorCount++;
    }
}

// Pārsūta atpakaļ uz galeriju
header("Location: gallery.php?success=" . $successCount . "&errors=" . $errorCount);
exit();
?>
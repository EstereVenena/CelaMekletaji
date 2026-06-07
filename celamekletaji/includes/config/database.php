<?php
// ===============================
// DATUBĀZES SAVIENOJUMS
// ===============================

// Ielādē .env failu no projekta galvenās mapes
$envPath = __DIR__ . '/../../../.env';

if (file_exists($envPath)) {
    $env = parse_ini_file($envPath);
    foreach ($env as $key => $value) {
        putenv("$key=$value");
    }
}

$serveris  = getenv('DB_SERVER') ?: 'localhost';
$lietotajs = getenv('DB_USER') ?: '';
$parole    = getenv('DB_PASSWORD') ?: '';
$datubaze  = getenv('DB_NAME') ?: '';

if (empty($lietotajs) || empty($parole) || empty($datubaze)) {
    die("Kļūda: Datubāzes pieslēguma dati nav konfigurēti.");
}

$savienojums = new mysqli($serveris, $lietotajs, $parole, $datubaze);

if ($savienojums->connect_error) {
    die("Kļūda savienojumā ar datubāzi");
}

$savienojums->set_charset("utf8mb4");
<?php
// ===============================
// DATUBĀZES SAVIENOJUMS
// ===============================

$serveris  = "localhost";
$lietotajs = "grobina1_venena";
$parole    = "rpAKFVnbOZV1@";
$datubaze  = "grobina1_venena";

// Izveido savienojumu
$savienojums = new mysqli($serveris, $lietotajs, $parole, $datubaze);

// Pārbauda savienojumu
if ($savienojums->connect_error) {
    die("Kļūda savienojumā ar datubāzi");
}

// Iestata UTF-8 (latviešu burtiem)
$savienojums->set_charset("utf8mb4");

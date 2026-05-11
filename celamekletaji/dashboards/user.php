<?php
session_start();

require_once __DIR__ . "/../includes/config/database.php";

// Check if user is logged in
if (!isset($_SESSION["lietotajs_id"])) {
    header("Location: ../auth/login.php");
    exit();
}

// Ja lietotājam jau ir konkrēta loma, met uz īsto paneli
$loma = $_SESSION["loma"] ?? "";

if ($loma === "admin") {
    header("Location: admin.php");
    exit();
} elseif ($loma === "Vecāks" || $loma === "parent") {
    header("Location: parent.php");
    exit();
} elseif ($loma === "Direktors") {
    header("Location: director.php");
    exit();
} elseif ($loma === "Skolotājs") {
    header("Location: teacher.php");
    exit();
} elseif ($loma === "Ceļameklētājs") {
    header("Location: student.php");
    exit();
}

$lapa  = "Lietotāja panelis";
$title = "Lietotāja panelis - Ceļa meklētāji";

require __DIR__ . "/../includes/templates/header.php";
?>

<main class="dashboard-main">
    <div class="container">
        <div class="dashboard-header">
            <h2>Sveiki, <?php echo htmlspecialchars($_SESSION["lietotajvards"] ?? "Lietotājs"); ?>!</h2>
            <p>Šis ir jūsu lietotāja panelis. Šobrīd tiek izstrādāts.</p>
        </div>

        <div class="dashboard-content">
            <div class="dashboard-card">
                <h3>Mani dati</h3>
                <p>WIP: Šeit būs jūsu personīgā informācija.</p>
            </div>

            <div class="dashboard-card">
                <h3>Aktivitātes</h3>
                <p>WIP: Šeit būs informācija par jūsu aktivitātēm klubā.</p>
            </div>

            <div class="dashboard-card">
                <h3>Paziņojumi</h3>
                <p>WIP: Šeit būs aktuālie paziņojumi un jaunumi.</p>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
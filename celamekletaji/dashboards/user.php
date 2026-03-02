<?php
session_start();
$lapa  = "Lietotāja panelis";
$title = "Lietotāja panelis - Ceļa meklētāji";

require "../assets/header.php";
require_once "../assets/database.php";

// Check if user is logged in
if (!isset($_SESSION["lietotajs_id"])) {
    header("Location: ../login.php");
    exit();
}

// Get user information (placeholder)
?>

<main class="dashboard-main">
    <div class="container">
        <div class="dashboard-header">
            <h2>Sveiki, <?php echo htmlspecialchars($_SESSION["lietotajvards"]); ?>!</h2>
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

<?php require "../assets/footer.php"; ?>
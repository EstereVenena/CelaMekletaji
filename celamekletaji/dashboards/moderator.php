<?php
session_start();

$lapa  = "Moderatora panelis";
$title = "Moderatora panelis - Ceļa meklētāji";

require __DIR__ . "/../includes/templates/header.php";
require_once __DIR__ . "/../includes/config/database.php";

// Check if user is logged in and is a moderator
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? "") !== "moderators") {
    header("Location: ../auth/login.php");
    exit();
}
?>

<main class="dashboard-main">
    <div class="container">
        <div class="dashboard-header">
            <h2>Sveiki, moderators <?php echo htmlspecialchars($_SESSION["lietotajvards"] ?? ""); ?>!</h2>
            <p>Šis ir moderatora panelis. Šobrīd tiek izstrādāts.</p>
        </div>

        <div class="dashboard-content">
            <div class="dashboard-card">
                <h3>Lietotāju pārvaldība</h3>
                <p>WIP: Šeit būs lietotāju apstiprināšana un pārvaldība.</p>
            </div>

            <div class="dashboard-card">
                <h3>Satura pārvaldība</h3>
                <p>WIP: Šeit būs ziņu un satura pārvaldība.</p>
            </div>

            <div class="dashboard-card">
                <h3>Statistika</h3>
                <p>WIP: Šeit būs sistēmas statistika un pārskati.</p>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
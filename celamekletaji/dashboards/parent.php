<?php
session_start();

$lapa  = "Vecāku panelis";
$title = "Vecāku panelis - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

// Check if user is logged in and is a parent
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? "") !== "Vecāks") {
    header("Location: ../auth/login.php");
    exit();
}

require __DIR__ . "/../includes/templates/header-parent.php";

// Get parent's children information (placeholder)
$children = [];
?>

<main class="dashboard-main">
    <div class="container">
        <div class="dashboard-header">
            <?php $username = htmlspecialchars($_SESSION['lietotajvards'] ?? 'Vecāks'); ?>
            <h2>Sveiki, <?php echo $username; ?>!</h2>
            <p class="lead">Šis ir jūsu vecāku panelis — pārvaldiet bērnu dalību, maksājumus un paziņojumus.</p>
        </div>

        <div class="section-title-row" style="margin-bottom:1rem;">
            <div>
                <h3 class="small">Konts</h3>
                <p class="muted small">Loma: <strong>Vecāks</strong></p>
            </div>
            <div style="display:flex; gap:.6rem; align-items:center;">
                <a class="btn btn-primary btn-sm" href="../auth/register.php">Pievienot bērnu</a>
            </div>
        </div>

        <div class="dashboard-content">
            <div class="dashboard-card">
                <h3>Mani bērni</h3>
                <p class="muted">Pārvaldīt jūsu bērnu informāciju un redzēt viņu dalību klubos.</p>

                <?php if (empty($children)): ?>
                    <div class="divider"></div>
                    <p class="muted">
                        Pagaidām nav pievienotu bērnu.
                        <a class="link" href="../auth/register.php">Pievienot bērnu</a>
                    </p>
                <?php else: ?>
                    <div class="divider"></div>
                    <div class="cards">
                        <?php foreach ($children as $child): ?>
                            <div class="card">
                                <div class="program-head">
                                    <div style="display:flex; gap:.75rem; align-items:center;">
                                        <div class="program-logo">
                                            <img src="../assets/images/avatar-placeholder.png" alt="avatar">
                                        </div>
                                        <div>
                                            <h4 style="margin:0"><?php echo htmlspecialchars($child['vards'] ?? 'Bērns'); ?></h4>
                                            <p class="muted small">
                                                <?php echo htmlspecialchars($child['vecums'] ?? '—'); ?>
                                                •
                                                <?php echo htmlspecialchars($child['klubi'] ?? 'Nav klubu'); ?>
                                            </p>
                                        </div>
                                    </div>
                                    <div style="display:flex; gap:.5rem; align-items:center;">
                                        <a class="btn btn-outline btn-sm" href="#">Skatīt</a>
                                        <a class="btn btn-sm" href="#">Rediģēt</a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <h3>Bērnu aktivitātes</h3>
                <p class="muted">Ātrs pārskats par klubu apmeklējumu, nākamajiem pasākumiem un paziņojumiem.</p>
                <div class="divider"></div>
                <ul class="footer-list" style="margin-top:.5rem;">
                    <li><i class="badge badge-blue">Aktuāli</i> Nav gaidāmu pasākumu</li>
                    <li><i class="badge badge-gold">Klubs</i> Nav reģistrēta dalība</li>
                </ul>
            </div>

            <div class="dashboard-card">
                <h3>Maksājumi</h3>
                <p class="muted">Skatīt rēķinus un veikt maksājumus par klubu dalību.</p>
                <div class="divider"></div>
                <p class="muted">Nav neapmaksātu rēķinu.</p>
            </div>
        </div>
    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
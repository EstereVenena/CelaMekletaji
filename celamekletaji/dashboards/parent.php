<?php
session_start();

$lapa  = "Vecāku panelis";
$title = "Vecāku panelis - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

// Pārbauda, vai lietotājs ir ielogojies un ir Vecāks
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? "") !== "Vecāks") {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) ($_SESSION["lietotajs_id"] ?? 0);
$children = [];
$error = null;

// Ielādē bērnus, kas piesaistīti konkrētajam vecākam
$sql = "
    SELECT 
        c.lietotajs_id,
        c.lietotajvards,
        c.vards,
        c.uzvards,
        c.epasts,
        c.loma,
        c.statuss,
        c.Reg_datums
    FROM cm_parent_children pc
    INNER JOIN cm_lietotaji c 
        ON c.lietotajs_id = pc.child_id
    WHERE pc.parent_id = ?
      AND c.statuss <> 'dzēsts'
    ORDER BY c.vards ASC, c.uzvards ASC
";

if ($stmt = $savienojums->prepare($sql)) {
    $stmt->bind_param("i", $parentId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $children[] = $row;
        }
    } else {
        $error = "Neizdevās ielādēt bērnu sarakstu.";
    }

    $stmt->close();
} else {
    $error = "Neizdevās sagatavot SQL vaicājumu.";
}

require __DIR__ . "/../includes/templates/header-parent.php";
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
                <a class="btn btn-primary btn-sm" href="../children/add.php">Pievienot bērnu</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="dashboard-card">
                <p class="muted"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <div class="dashboard-content">
            <div class="dashboard-card">
                <h3>Mani bērni (<?php echo count($children); ?>)</h3>
                <p class="muted">Pārvaldīt jūsu bērnu informāciju un redzēt viņu dalību klubos.</p>

                <?php if (empty($children)): ?>
                    <div class="divider"></div>
                    <p class="muted">
                        Pagaidām nav pievienotu bērnu.
                        <a class="link" href="../children/add.php">Pievienot bērnu</a>
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
                                            <h4 style="margin:0;">
                                                <?php echo htmlspecialchars(($child['vards'] ?? '') . ' ' . ($child['uzvards'] ?? '')); ?>
                                            </h4>

                                            <p class="muted small" style="margin:.2rem 0 0 0;">
                                                Lietotājvārds: <?php echo htmlspecialchars($child['lietotajvards'] ?? '—'); ?>
                                            </p>

                                            <p class="muted small" style="margin:.2rem 0 0 0;">
                                                E-pasts: <?php echo htmlspecialchars($child['epasts'] ?? '—'); ?>
                                            </p>

                                            <p class="muted small" style="margin:.2rem 0 0 0;">
                                                Loma: <?php echo htmlspecialchars($child['loma'] ?? '—'); ?>
                                                &nbsp;•&nbsp;
                                                Statuss: <?php echo htmlspecialchars($child['statuss'] ?? '—'); ?>
                                            </p>

                                            <p class="muted small" style="margin:.2rem 0 0 0;">
                                                Reģistrēts: 
                                                <?php
                                                    echo !empty($child['Reg_datums'])
                                                        ? htmlspecialchars(date('d.m.Y H:i', strtotime($child['Reg_datums'])))
                                                        : '—';
                                                ?>
                                            </p>
                                        </div>
                                    </div>

                                    <div style="display:flex; gap:.5rem; align-items:center;">
                                        <a class="btn btn-outline btn-sm" href="../children/view.php?id=<?php echo (int)$child['lietotajs_id']; ?>">Skatīt</a>
                                        <a class="btn btn-sm" href="../children/edit.php?id=<?php echo (int)$child['lietotajs_id']; ?>">Rediģēt</a>
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
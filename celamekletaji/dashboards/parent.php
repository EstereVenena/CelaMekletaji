<?php
session_start();

$lapa  = "Vecāku panelis";
$title = "Vecāku panelis - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

// Pārbauda, vai lietotājs ir pieslēdzies un vai viņa loma ir Vecāks.
// Ja lietotājam nav tiesību, viņš tiek novirzīts uz pieslēgšanās lapu.
if (!isset($_SESSION["lietotajs_id"]) || !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) ($_SESSION["lietotajs_id"] ?? 0);
$children = [];
$error = null;

// Atlasa tikai tos bērnus, kuri ir piesaistīti konkrētajam vecākam.
// Tiek izmantota starptabula cm_parent_children, lai nodrošinātu pareizu datu sasaisti.
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

// Sagatavotais vaicājums pasargā sistēmu no SQL injekcijām.
if ($stmt = $savienojums->prepare($sql)) {
    $stmt->bind_param("i", $parentId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $children[] = $row;
    }

    $stmt->close();
} else {
    $error = "Neizdevās ielādēt bērnu sarakstu.";
}


// Nākamās aktivitātes
$activitySql = "
    SELECT
        e.id,
        e.title,
        e.description,
        e.start_date,
        e.end_date,
        e.start_time,
        e.end_time,
        e.location,
        e.event_type,
        c.vards AS child_vards,
        c.uzvards AS child_uzvards,
        ea.status
    FROM cm_parent_children pc
    INNER JOIN cm_lietotaji c
        ON c.lietotajs_id = pc.child_id
    INNER JOIN cm_event_applications ea
        ON ea.child_id = c.lietotajs_id
    INNER JOIN cm_events e
        ON e.id = ea.event_id
    WHERE pc.parent_id = ?
      AND c.statuss <> 'dzēsts'
      AND e.is_active = 1
      AND (
            e.start_date >= CURDATE()
            OR (
                e.end_date IS NOT NULL
                AND e.end_date >= CURDATE()
            )
      )
      AND ea.status IN ('pieteikts', 'apstiprināts')
    ORDER BY e.start_date ASC, e.start_time ASC
    LIMIT 5
";

if ($stmt = $savienojums->prepare($activitySql)) {
    $stmt->bind_param("i", $parentId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }

    $stmt->close();
}

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<main class="dashboard-main">
    <div class="container">
        <div class="dashboard-header">
            <?php $username = htmlspecialchars($_SESSION['lietotajvards'] ?? 'Vecāks'); ?>
            <h2>Sveiki, <?php echo $username; ?>!</h2>
            <p class="lead">Šis ir jūsu vecāku panelis — pārvaldiet bērnu dalību, aktivitātes un paziņojumus.</p>
        </div>

        <div class="section-title-row" style="margin-bottom:1rem;">
            <div>
                <h3 class="small">Konts</h3>
                <p class="muted small">Loma: <strong>Vecāks</strong></p>
            </div>

            <div style="display:flex; gap:.6rem; align-items:center; flex-wrap:wrap;">
                <a class="btn btn-primary btn-sm" href="../children/add.php">Pievienot bērnu</a>
                <a class="btn btn-outline btn-sm" href="../children/manage.php">Pārvaldīt bērnus</a>
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
                <p class="muted">Pārvaldīt jūsu bērnu informāciju un redzēt viņu dalību aktivitātēs.</p>

                <div class="divider"></div>

                <?php if (empty($children)): ?>
                    <p class="muted">
                        Pagaidām nav pievienotu bērnu.
                        <a class="link" href="../children/add.php">Pievienot bērnu</a>
                    </p>
                <?php else: ?>
                    <div class="parent-child-list">
                        <?php foreach ($children as $child): ?>
                            <div class="parent-child-card">
                                <div class="parent-child-info">
                                    <div class="parent-child-text">
                                        <h4>
                                            <?php echo htmlspecialchars(trim(($child['vards'] ?? '') . ' ' . ($child['uzvards'] ?? ''))); ?>
                                        </h4>

                                        <p class="muted small">
                                            Lietotājvārds: <?php echo htmlspecialchars($child['lietotajvards'] ?? '—'); ?>
                                        </p>

                                        <p class="muted small">
                                            E-pasts: <?php echo htmlspecialchars($child['epasts'] ?? '—'); ?>
                                        </p>

                                        <p class="muted small">
                                            Loma: <?php echo htmlspecialchars($child['loma'] ?? '—'); ?>
                                            &nbsp;•&nbsp;
                                            Statuss: <?php echo htmlspecialchars($child['statuss'] ?? '—'); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="parent-child-actions">
                                    <a class="btn btn-outline btn-sm" href="../children/view.php?id=<?php echo (int)$child['lietotajs_id']; ?>">Skatīt</a>
                                    <a class="btn btn-sm" href="../children/edit.php?id=<?php echo (int)$child['lietotajs_id']; ?>">Rediģēt</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="dashboard-card">
                <div class="section-title-row">
                    <div>
                        <h3>Bērnu aktivitātes</h3>
                        <p class="muted">Tuvākās aktivitātes, kurās bērni ir pieteikti.</p>
                    </div>

                    <a class="btn btn-outline btn-sm" href="../parent/activities.php">Skatīt kalendāru</a>
                </div>

                <div class="divider"></div>

                <?php if (empty($activities)): ?>
                    <ul class="footer-list" style="margin-top:.5rem;">
                        <li><i class="badge badge-blue">Aktuāli</i> Nav gaidāmu aktivitāšu</li>
                        <li><i class="badge badge-gold">Klubs</i> Nav reģistrēta dalība</li>
                    </ul>
                <?php else: ?>
                    <div class="parent-activity-list">
                        <?php foreach ($activities as $activity): ?>
                            <div class="parent-activity-item">
                                <div>
                                    <strong><?php echo htmlspecialchars($activity["title"] ?? "Aktivitāte"); ?></strong>

                                    <p class="muted small">
                                        Bērns:
                                        <?php echo htmlspecialchars(trim(($activity["child_vards"] ?? "") . " " . ($activity["child_uzvards"] ?? ""))); ?>
                                    </p>

                                    <p class="muted small">
                                        Datums:
                                        <?php echo htmlspecialchars(formatEventDateRange($activity["start_date"], $activity["end_date"])); ?>

                                        <?php
                                        $timeText = formatEventTimeRange($activity["start_time"], $activity["end_time"]);
                                        if ($timeText !== ""):
                                        ?>
                                            <?php echo htmlspecialchars($timeText); ?>
                                        <?php endif; ?>

                                        <?php if (!empty($activity["location"])): ?>
                                            • <?php echo htmlspecialchars($activity["location"]); ?>
                                        <?php endif; ?>
                                    </p>

                                    <p class="muted small">
                                        Veids: <?php echo htmlspecialchars($activity["event_type"] ?? "pasākums"); ?>
                                    </p>
                                </div>

                                <span class="badge badge-blue">
                                    <?php echo htmlspecialchars($activity["status"] ?? "pieteikts"); ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</main>

<style>
.parent-child-list {
    display: grid;
    gap: .75rem;
}

.parent-child-card,
.parent-activity-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .75rem;
    padding: .85rem;
    border: 1px solid #eef1f6;
    border-radius: 16px;
    background: #fff;
}

.parent-child-info,
.parent-child-text {
    min-width: 0;
    width: 100%;
}

.parent-child-text h4 {
    margin: 0 0 .25rem;
    font-size: 1rem;
    line-height: 1.2;
    word-break: normal;
    overflow-wrap: anywhere;
}

.parent-child-text p,
.parent-activity-item p {
    margin: .25rem 0 0;
    line-height: 1.35;
    word-break: normal;
    overflow-wrap: anywhere;
}

.parent-child-actions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.parent-activity-list {
    display: grid;
    gap: .75rem;
}

@media (max-width: 700px) {
    .parent-child-card,
    .parent-activity-item {
        flex-direction: column;
        align-items: stretch;
    }

    .parent-child-actions {
        justify-content: flex-start;
    }
}
</style>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
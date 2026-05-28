<?php
session_start();

$lapa  = "Bērna profils";
$title = "Bērna profils - Ceļa meklētāji";

require_once __DIR__ . "/../../includes/config/database.php";

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)
) {
    header("Location: ../../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION["lietotajs_id"];
$childId  = (int) ($_GET["id"] ?? 0);

if ($childId <= 0) {
    header("Location: manage.php");
    exit();
}

$child = null;
$activities = [];

/* ===============================
   BĒRNA DATI
================================ */
$sql = "
    SELECT 
        c.lietotajs_id,
        c.lietotajvards,
        c.vards,
        c.uzvards,
        c.epasts,
        c.loma,
        c.statuss,
        c.Reg_datums,
        pc.relationship
    FROM cm_parent_children pc
    INNER JOIN cm_lietotaji c
        ON c.lietotajs_id = pc.child_id
    WHERE pc.parent_id = ?
      AND pc.child_id = ?
      AND c.statuss <> 'dzēsts'
    LIMIT 1
";

if ($stmt = $savienojums->prepare($sql)) {
    $stmt->bind_param("ii", $parentId, $childId);
    $stmt->execute();
    $result = $stmt->get_result();
    $child = $result->fetch_assoc();
    $stmt->close();
}

if (!$child) {
    header("Location: manage.php");
    exit();
}

/* ===============================
   BĒRNA AKTIVITĀTES
================================ */
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
        ea.status
    FROM cm_event_applications ea
    INNER JOIN cm_events e
        ON e.id = ea.event_id
    WHERE ea.child_id = ?
      AND e.is_active = 1
    ORDER BY e.start_date DESC, e.start_time DESC
    LIMIT 6
";

if ($stmt = $savienojums->prepare($activitySql)) {
    $stmt->bind_param("i", $childId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $activities[] = $row;
    }

    $stmt->close();
}

$childName = trim(($child["vards"] ?? "") . " " . ($child["uzvards"] ?? ""));
$childName = $childName !== "" ? $childName : "Bērns";
$childInitial = mb_strtoupper(mb_substr($childName, 0, 1));

require __DIR__ . "/../../includes/templates/header-parent.php";
?>

<style>
.child-view-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.child-view-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1.2rem;
    align-items: center;
    margin-bottom: 1.4rem;
    padding: 1.8rem;
    border-radius: 28px;
    background:
        radial-gradient(circle at top right, rgba(244,196,48,.28), transparent 34%),
        linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
    box-shadow: 0 24px 60px rgba(23, 63, 132, 0.22);
}

.child-view-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.child-view-hero > * {
    position: relative;
    z-index: 1;
}

.child-view-avatar {
    width: 82px;
    height: 82px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(244,196,48,.55);
    color: #f4c430;
    font-size: 2.1rem;
    font-weight: 1000;
}

.child-view-hero h1 {
    margin: 0 0 .35rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.child-view-hero p {
    margin: 0;
    color: rgba(255,255,255,.88);
    line-height: 1.6;
}

.child-view-actions {
    display: flex;
    gap: .65rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.child-view-grid {
    display: grid;
    grid-template-columns: .9fr 1.1fr;
    gap: 1.2rem;
}

.child-view-card {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.child-view-card h2 {
    margin: 0 0 .35rem;
    color: #173f84;
    font-size: 1.25rem;
}

.child-view-sub {
    margin: 0 0 1rem;
    color: #667085;
}

.child-info-list {
    display: grid;
    gap: .75rem;
}

.child-info-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: .9rem 1rem;
    border-radius: 16px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
}

.child-info-row span {
    color: #667085;
    font-weight: 800;
}

.child-info-row strong {
    color: #101828;
    text-align: right;
    overflow-wrap: anywhere;
}

.child-activity-list {
    display: grid;
    gap: .85rem;
}

.child-activity-item {
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 18px;
    background: #f8fbff;
}

.child-activity-top {
    display: flex;
    justify-content: space-between;
    gap: .75rem;
    align-items: flex-start;
}

.child-activity-title {
    margin: 0;
    color: #101828;
    font-size: 1rem;
}

.child-activity-item p {
    margin: .35rem 0 0;
    color: #667085;
    line-height: 1.45;
}

.child-date-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin-top: .65rem;
    padding: .35rem .65rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-size: .86rem;
    font-weight: 900;
}

.child-empty {
    padding: 1.15rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
}

@media (max-width: 900px) {
    .child-view-hero {
        grid-template-columns: 1fr;
        text-align: left;
    }

    .child-view-actions {
        justify-content: flex-start;
    }

    .child-view-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .child-view-page {
        padding: 1.5rem 0 2.5rem;
    }

    .child-view-hero,
    .child-view-card {
        border-radius: 22px;
        padding: 1.2rem;
    }

    .child-view-actions .btn {
        width: 100%;
    }

    .child-info-row {
        flex-direction: column;
    }

    .child-info-row strong {
        text-align: left;
    }

    .child-activity-top {
        flex-direction: column;
    }
}
</style>

<main class="child-view-page">
    <div class="container">

        <section class="child-view-hero">
            <div class="child-view-avatar">
                <?= htmlspecialchars($childInitial); ?>
            </div>

            <div>
                <h1><?= htmlspecialchars($childName); ?></h1>
                <p>
                    Bērna profila informācija, piesaiste vecākam un aktivitāšu pārskats.
                </p>
            </div>

            <div class="child-view-actions">
                <a href="edit.php?id=<?= (int)$childId; ?>" class="btn btn-primary btn-sm">
                    <i class="fas fa-pen-to-square"></i>
                    Rediģēt
                </a>

                <a href="manage.php" class="btn btn-outline btn-sm">
                    Atpakaļ
                </a>
            </div>
        </section>

        <section class="child-view-grid">

            <div class="child-view-card">
                <h2>Pamatinformācija</h2>
                <p class="child-view-sub">Bērna konta dati un statuss sistēmā.</p>

                <div class="child-info-list">
                    <div class="child-info-row">
                        <span>Lietotājvārds</span>
                        <strong><?= htmlspecialchars($child["lietotajvards"] ?? "—"); ?></strong>
                    </div>

                    <div class="child-info-row">
                        <span>Vārds</span>
                        <strong><?= htmlspecialchars($child["vards"] ?? "—"); ?></strong>
                    </div>

                    <div class="child-info-row">
                        <span>Uzvārds</span>
                        <strong><?= htmlspecialchars($child["uzvards"] ?? "—"); ?></strong>
                    </div>

                    <div class="child-info-row">
                        <span>E-pasts</span>
                        <strong><?= htmlspecialchars($child["epasts"] ?? "—"); ?></strong>
                    </div>

                    <div class="child-info-row">
                        <span>Loma</span>
                        <strong><?= htmlspecialchars($child["loma"] ?? "—"); ?></strong>
                    </div>

                    <div class="child-info-row">
                        <span>Statuss</span>
                        <strong><?= htmlspecialchars($child["statuss"] ?? "—"); ?></strong>
                    </div>

                    <div class="child-info-row">
                        <span>Radniecība</span>
                        <strong><?= htmlspecialchars($child["relationship"] ?? "—"); ?></strong>
                    </div>

                    <div class="child-info-row">
                        <span>Reģistrēts</span>
                        <strong><?= htmlspecialchars($child["Reg_datums"] ?? "—"); ?></strong>
                    </div>
                </div>
            </div>

            <div class="child-view-card">
                <h2>Aktivitātes</h2>
                <p class="child-view-sub">Pēdējās vai tuvākās aktivitātes, kurās bērns ir iesaistīts.</p>

                <?php if (empty($activities)): ?>
                    <div class="child-empty">
                        Šim bērnam pašlaik nav reģistrētu aktivitāšu.
                    </div>
                <?php else: ?>
                    <div class="child-activity-list">
                        <?php foreach ($activities as $activity): ?>
                            <?php
                                $dateText = function_exists("formatEventDateRange")
                                    ? formatEventDateRange($activity["start_date"], $activity["end_date"])
                                    : ($activity["start_date"] ?? "");

                                $timeText = function_exists("formatEventTimeRange")
                                    ? formatEventTimeRange($activity["start_time"], $activity["end_time"])
                                    : "";
                            ?>

                            <article class="child-activity-item">
                                <div class="child-activity-top">
                                    <h3 class="child-activity-title">
                                        <?= htmlspecialchars($activity["title"] ?? "Aktivitāte"); ?>
                                    </h3>

                                    <span class="badge badge-blue">
                                        <?= htmlspecialchars($activity["status"] ?? "pieteikts"); ?>
                                    </span>
                                </div>

                                <span class="child-date-pill">
                                    <i class="fas fa-calendar-day"></i>
                                    <?= htmlspecialchars($dateText); ?>

                                    <?php if ($timeText !== ""): ?>
                                        <?= htmlspecialchars($timeText); ?>
                                    <?php endif; ?>
                                </span>

                                <?php if (!empty($activity["location"])): ?>
                                    <p>
                                        <i class="fas fa-location-dot"></i>
                                        <?= htmlspecialchars($activity["location"]); ?>
                                    </p>
                                <?php endif; ?>

                                <p>
                                    <i class="fas fa-tag"></i>
                                    Veids:
                                    <?= htmlspecialchars($activity["event_type"] ?? "pasākums"); ?>
                                </p>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </div>
</main>

<?php require __DIR__ . "/../../includes/templates/footer.php"; ?>
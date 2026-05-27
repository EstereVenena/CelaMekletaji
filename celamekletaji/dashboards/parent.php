<?php
session_start();

$lapa  = "Vecāku panelis";
$title = "Vecāku panelis - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   DROŠĪBA
================================ */
if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) ($_SESSION["lietotajs_id"] ?? 0);
$username = htmlspecialchars($_SESSION['lietotajvards'] ?? 'Vecāks');

$children = [];
$activities = [];
$error = null;

/* ===============================
   BĒRNI
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
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $children[] = $row;
    }

    $stmt->close();
} else {
    $error = "Neizdevās ielādēt bērnu sarakstu.";
}

/* ===============================
   NĀKAMĀS AKTIVITĀTES
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

$childrenCount = count($children);
$activitiesCount = count($activities);

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<style>
/* ===============================
   PARENT DASHBOARD
================================ */

.parent-dashboard {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.parent-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 1.5rem;
    align-items: center;
    margin-bottom: 1.4rem;
    padding: 2rem;
    border-radius: 28px;
    background:
        radial-gradient(circle at top right, rgba(244,196,48,.28), transparent 34%),
        linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
    box-shadow: 0 24px 60px rgba(23, 63, 132, 0.22);
}

.parent-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.parent-hero > * {
    position: relative;
    z-index: 1;
}

.parent-hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .45rem .85rem;
    margin-bottom: 1rem;
    border-radius: 999px;
    background: rgba(255,255,255,.14);
    color: #f4c430;
    font-weight: 900;
}

.parent-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.parent-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.parent-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.parent-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.parent-hero-card strong {
    display: block;
    margin-bottom: .4rem;
    font-size: 1.15rem;
}

.parent-hero-card span {
    display: block;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.parent-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.parent-stat-card {
    position: relative;
    overflow: hidden;
    padding: 1.25rem;
    border-radius: 22px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.parent-stat-card::after {
    content: "";
    position: absolute;
    right: -40px;
    bottom: -40px;
    width: 110px;
    height: 110px;
    border-radius: 50%;
    background: rgba(30,79,161,0.06);
}

.parent-stat-top {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: space-between;
    gap: .8rem;
}

.parent-stat-label {
    margin: 0;
    color: #667085;
    font-size: .92rem;
    font-weight: 800;
}

.parent-stat-value {
    margin: .2rem 0 0;
    color: #101828;
    font-size: 2rem;
    font-weight: 1000;
    line-height: 1;
}

.parent-stat-icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    border-radius: 14px;
    background: #eef3ff;
    color: #173f84;
    flex-shrink: 0;
}

.parent-grid {
    display: grid;
    grid-template-columns: 1.15fr .85fr;
    gap: 1.2rem;
}

.parent-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.parent-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.parent-panel h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.25rem;
}

.parent-panel-sub {
    margin: .3rem 0 0;
    color: #667085;
    font-size: .95rem;
}

.parent-child-list,
.parent-activity-list {
    display: grid;
    gap: .85rem;
}

.parent-child-card,
.parent-activity-item {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: .9rem;
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 18px;
    background: #f8fbff;
    transition: .2s ease;
}

.parent-child-card:hover,
.parent-activity-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.parent-child-main {
    display: flex;
    gap: .85rem;
    min-width: 0;
}

.parent-child-avatar {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #173f84, #1e4fa1);
    color: #f4c430;
    font-weight: 1000;
}

.parent-child-text {
    min-width: 0;
}

.parent-child-text h3,
.parent-activity-title {
    margin: 0 0 .25rem;
    color: #101828;
    font-size: 1rem;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.parent-child-text p,
.parent-activity-item p {
    margin: .25rem 0 0;
    color: #667085;
    font-size: .94rem;
    line-height: 1.4;
    overflow-wrap: anywhere;
}

.parent-child-actions {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    flex-shrink: 0;
}

.parent-empty {
    padding: 1.25rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
}

.parent-activity-date {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    margin-top: .55rem;
    padding: .35rem .65rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-size: .86rem;
    font-weight: 900;
}

.parent-activity-status {
    display: inline-flex;
    align-items: center;
    white-space: nowrap;
}

.parent-alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
    font-weight: 800;
}

@media (max-width: 980px) {
    .parent-hero {
        grid-template-columns: 1fr;
    }

    .parent-stats {
        grid-template-columns: 1fr;
    }

    .parent-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 700px) {
    .parent-dashboard {
        padding: 1.5rem 0 2.5rem;
    }

    .parent-hero,
    .parent-panel {
        border-radius: 20px;
    }

    .parent-hero {
        padding: 1.4rem;
    }

    .parent-panel {
        padding: 1rem;
    }

    .parent-panel-head,
    .parent-child-card,
    .parent-activity-item {
        flex-direction: column;
        align-items: stretch;
    }

    .parent-hero-actions .btn,
    .parent-panel-head .btn,
    .parent-child-actions .btn {
        width: 100%;
    }
}
</style>

<main class="parent-dashboard">
    <div class="container">

        <section class="parent-hero">
            <div>
                <div class="parent-hero-kicker">
                    <i class="fas fa-user-shield"></i>
                    Vecāku piekļuve
                </div>

                <h1>Sveiki, <?= $username; ?>!</h1>

                <p>
                    Šeit vari pārvaldīt bērnu informāciju, sekot līdzi aktivitātēm un apskatīt svarīgākos paziņojumus.
                    Viss vienā vietā — mazāk haosa, vairāk kontroles.
                </p>

                <div class="parent-hero-actions">
                    <a class="btn btn-primary btn-sm" href="../children/add.php">
                        <i class="fas fa-child-reaching"></i>
                        Pievienot bērnu
                    </a>

                    <a class="btn btn-outline btn-sm" href="../children/manage.php">
                        <i class="fas fa-children"></i>
                        Pārvaldīt bērnus
                    </a>

                    <a class="btn btn-outline btn-sm" href="../parent/activities.php">
                        <i class="fas fa-calendar-check"></i>
                        Aktivitātes
                    </a>
                </div>
            </div>

            <aside class="parent-hero-card">
                <strong>Ģimenes pārskats</strong>
                <span>
                    Ātri redzi bērnus, tuvākās aktivitātes un svarīgākās darbības.
                    Panelis bez liekas klikšķu vingrošanas.
                </span>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="parent-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
<section class="parent-stats">
    <div class="parent-stat-card">
        <div class="parent-stat-top">
            <div>
                <p class="parent-stat-label">Pievienotie bērni</p>
                <p class="parent-stat-value"><?= (int)$childrenCount; ?></p>
            </div>

            <span class="parent-stat-icon">
                <i class="fas fa-children"></i>
            </span>
        </div>
    </div>

    <div class="parent-stat-card">
        <div class="parent-stat-top">
            <div>
                <p class="parent-stat-label">Tuvākās aktivitātes</p>
                <p class="parent-stat-value"><?= (int)$activitiesCount; ?></p>
            </div>

            <span class="parent-stat-icon">
                <i class="fas fa-calendar-days"></i>
            </span>
        </div>
    </div>
</section>
</section>

        <section class="parent-grid">

            <div class="parent-panel">
                <div class="parent-panel-head">
                    <div>
                        <h2>Mani bērni</h2>
                        <p class="parent-panel-sub">
                            Pārvaldi bērnu informāciju un apskati viņu dalību aktivitātēs.
                        </p>
                    </div>

                    <a class="btn btn-outline btn-sm" href="../children/manage.php">
                        Pārvaldīt
                    </a>
                </div>

                <?php if (empty($children)): ?>
                    <div class="parent-empty">
                        <p>
                            Pagaidām nav pievienotu bērnu.
                            <a class="link" href="../children/add.php">Pievienot bērnu</a>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="parent-child-list">
                        <?php foreach ($children as $child): ?>
                            <?php
                                $childName = trim(($child['vards'] ?? '') . ' ' . ($child['uzvards'] ?? ''));
                                $childName = $childName !== '' ? $childName : ($child['lietotajvards'] ?? 'Bērns');
                                $childInitial = mb_strtoupper(mb_substr($childName, 0, 1));
                            ?>

                            <article class="parent-child-card">
                                <div class="parent-child-main">
                                    <span class="parent-child-avatar">
                                        <?= htmlspecialchars($childInitial); ?>
                                    </span>

                                    <div class="parent-child-text">
                                        <h3><?= htmlspecialchars($childName); ?></h3>

                                        <p>
                                            <i class="fas fa-user"></i>
                                            Lietotājvārds:
                                            <?= htmlspecialchars($child['lietotajvards'] ?? '—'); ?>
                                        </p>

                                        <p>
                                            <i class="fas fa-envelope"></i>
                                            E-pasts:
                                            <?= htmlspecialchars($child['epasts'] ?? '—'); ?>
                                        </p>

                                        <p>
                                            <i class="fas fa-id-badge"></i>
                                            Loma:
                                            <?= htmlspecialchars($child['loma'] ?? '—'); ?>
                                            · Statuss:
                                            <?= htmlspecialchars($child['statuss'] ?? '—'); ?>
                                        </p>
                                    </div>
                                </div>

                                <div class="parent-child-actions">
                                    <a class="btn btn-outline btn-sm" href="../children/view.php?id=<?= (int)$child['lietotajs_id']; ?>">
                                        Skatīt
                                    </a>

                                    <a class="btn btn-sm" href="../children/edit.php?id=<?= (int)$child['lietotajs_id']; ?>">
                                        Rediģēt
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="parent-panel">
                <div class="parent-panel-head">
                    <div>
                        <h2>Bērnu aktivitātes</h2>
                        <p class="parent-panel-sub">
                            Tuvākās aktivitātes, kurās bērni ir pieteikti.
                        </p>
                    </div>

                    <a class="btn btn-outline btn-sm" href="../parent/activities.php">
                        Kalendārs
                    </a>
                </div>

                <?php if (empty($activities)): ?>
                    <div class="parent-empty">
                        <p>
                            Nav gaidāmu aktivitāšu. Klusums ēterā — pagaidām bez trauksmes.
                        </p>
                    </div>
                <?php else: ?>
                    <div class="parent-activity-list">
                        <?php foreach ($activities as $activity): ?>
                            <?php
                                $childFullName = trim(($activity["child_vards"] ?? "") . " " . ($activity["child_uzvards"] ?? ""));
                                $timeText = formatEventTimeRange($activity["start_time"], $activity["end_time"]);
                                $dateText = formatEventDateRange($activity["start_date"], $activity["end_date"]);
                            ?>

                            <article class="parent-activity-item">
                                <div>
                                    <h3 class="parent-activity-title">
                                        <?= htmlspecialchars($activity["title"] ?? "Aktivitāte"); ?>
                                    </h3>

                                    <p>
                                        <i class="fas fa-child"></i>
                                        Bērns:
                                        <?= htmlspecialchars($childFullName ?: "—"); ?>
                                    </p>

                                    <span class="parent-activity-date">
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
                                </div>

                                <span class="badge badge-blue parent-activity-status">
                                    <?= htmlspecialchars($activity["status"] ?? "pieteikts"); ?>
                                </span>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

        </section>
    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
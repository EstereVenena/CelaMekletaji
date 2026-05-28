<?php
session_start();

$lapa  = "Aktivitātes";
$title = "Aktivitātes - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION["lietotajs_id"];
$error = null;
$activities = [];

$sql = "
    SELECT
        e.id AS event_id,
        e.title,
        e.description,
        e.start_date,
        e.end_date,
        e.start_time,
        e.end_time,
        e.location,
        e.event_type,
        e.is_active,

        c.lietotajs_id AS child_id,
        c.vards AS child_vards,
        c.uzvards AS child_uzvards,

        ea.status AS application_status,
        ea.applied_at
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
      AND ea.status IN ('pieteikts', 'apstiprināts')
    ORDER BY e.start_date ASC, e.start_time ASC
";

if ($stmt = $savienojums->prepare($sql)) {
    $stmt->bind_param("i", $parentId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
    } else {
        $error = "Neizdevās ielādēt aktivitātes.";
    }

    $stmt->close();
} else {
    $error = "Neizdevās sagatavot SQL vaicājumu.";
}

/* ===============================
   KALENDĀRA DATI
================================ */
$calendarItems = [];

foreach ($activities as $activity) {
    $startDate = $activity["start_date"] ?? "";
    $endDate   = $activity["end_date"] ?? "";

    if (!$startDate) {
        continue;
    }

    if (!$endDate) {
        $endDate = $startDate;
    }

    $startTs = strtotime($startDate);
    $endTs   = strtotime($endDate);

    if ($startTs === false || $endTs === false) {
        continue;
    }

    if ($endTs < $startTs) {
        $endTs = $startTs;
    }

    for ($dayTs = $startTs; $dayTs <= $endTs; $dayTs = strtotime("+1 day", $dayTs)) {
        $dateKey = date("Y-m-d", $dayTs);
        $calendarItems[$dateKey][] = $activity;
    }
}

$month = (int) ($_GET["month"] ?? date("m"));
$year  = (int) ($_GET["year"] ?? date("Y"));

if ($month < 1 || $month > 12) {
    $month = (int) date("m");
}

if ($year < 2020 || $year > 2100) {
    $year = (int) date("Y");
}

$firstDay = strtotime(sprintf("%04d-%02d-01", $year, $month));
$daysInMonth = (int) date("t", $firstDay);
$startWeekDay = (int) date("N", $firstDay);

$prevMonth = $month - 1;
$prevYear = $year;

if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;

if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

$monthNames = [
    1 => "Janvāris",
    2 => "Februāris",
    3 => "Marts",
    4 => "Aprīlis",
    5 => "Maijs",
    6 => "Jūnijs",
    7 => "Jūlijs",
    8 => "Augusts",
    9 => "Septembris",
    10 => "Oktobris",
    11 => "Novembris",
    12 => "Decembris"
];

function formatEventDateRange(?string $startDate, ?string $endDate): string
{
    if (empty($startDate)) {
        return "—";
    }

    $start = date("d.m.Y", strtotime($startDate));

    if (!empty($endDate) && $endDate !== $startDate) {
        $end = date("d.m.Y", strtotime($endDate));
        return $start . " - " . $end;
    }

    return $start;
}

function formatEventTimeRange(?string $startTime, ?string $endTime): string
{
    if (empty($startTime)) {
        return "";
    }

    $start = substr($startTime, 0, 5);

    if (!empty($endTime) && $endTime !== $startTime) {
        $end = substr($endTime, 0, 5);
        return "plkst. " . $start . " - " . $end;
    }

    return "plkst. " . $start;
}

$activitiesCount = count($activities);

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<style>
.parent-activities-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.activities-hero {
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

.activities-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.activities-hero > * {
    position: relative;
    z-index: 1;
}

.activities-kicker {
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

.activities-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.activities-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.activities-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.activities-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.activities-hero-card strong {
    display: block;
    font-size: 2.1rem;
    line-height: 1;
    color: #f4c430;
}

.activities-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.activities-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
    font-weight: 800;
}

.calendar-card,
.activities-list-card {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.calendar-card {
    margin-bottom: 1.2rem;
}

.calendar-toolbar {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1.2rem;
}

.calendar-toolbar h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.45rem;
}

.calendar-toolbar p {
    margin: .3rem 0 0;
    color: #667085;
}

.calendar-nav {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
}

.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, minmax(0, 1fr));
    gap: .65rem;
}

.calendar-head {
    font-weight: 900;
    color: #173f84;
    text-align: center;
    padding: .65rem;
    background: #eef3ff;
    border-radius: 14px;
}

.calendar-day {
    min-height: 135px;
    background: #fff;
    border: 1px solid #edf2fb;
    border-radius: 18px;
    padding: .7rem;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
    transition: .2s ease;
}

.calendar-day:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.calendar-empty {
    background: transparent;
    border: none;
    box-shadow: none;
}

.calendar-empty:hover {
    transform: none;
    box-shadow: none;
}

.calendar-today {
    border: 2px solid #f4c430;
    background: #fffdf3;
}

.calendar-date {
    display: inline-flex;
    width: 30px;
    height: 30px;
    align-items: center;
    justify-content: center;
    margin-bottom: .5rem;
    border-radius: 50%;
    color: #173f84;
    font-weight: 1000;
    background: #eef3ff;
}

.calendar-today .calendar-date {
    background: #f4c430;
    color: #173f84;
}

.calendar-event {
    background: linear-gradient(135deg, #1e4fa1, #173f84);
    color: #fff;
    border-radius: 14px;
    padding: .5rem .6rem;
    margin-bottom: .4rem;
    font-size: .82rem;
    box-shadow: 0 10px 20px rgba(23, 63, 132, 0.15);
}

.calendar-event-multiday {
    background: linear-gradient(135deg, #f4c430, #e1aa16);
    color: #173f84;
}

.calendar-event strong,
.calendar-event span,
.calendar-event small {
    display: block;
}

.calendar-event strong {
    line-height: 1.2;
}

.calendar-event span {
    margin-top: .18rem;
    opacity: .92;
}

.calendar-event small {
    opacity: .9;
    margin-top: .15rem;
}

.activities-list-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.activities-list-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.activities-list-head p {
    margin: .3rem 0 0;
    color: #667085;
}

.activities-count {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .75rem 1rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-weight: 950;
    white-space: nowrap;
}

.activity-list {
    display: grid;
    gap: .9rem;
}

.activity-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 20px;
    background: #f8fbff;
    transition: .2s ease;
}

.activity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.activity-card h3 {
    margin: 0 0 .4rem;
    color: #101828;
    font-size: 1.08rem;
}

.activity-meta {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    margin-top: .55rem;
}

.activity-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .34rem .65rem;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #edf2fb;
    color: #667085;
    font-size: .84rem;
    font-weight: 800;
}

.activity-pill i {
    color: #1e4fa1;
}

.activity-desc {
    margin: .75rem 0 0;
    color: #667085;
    line-height: 1.55;
}

.activity-status {
    align-self: flex-start;
    white-space: nowrap;
}

.activities-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.activities-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

@media (max-width: 980px) {
    .activities-hero,
    .activity-card {
        grid-template-columns: 1fr;
    }

    .calendar-toolbar,
    .activities-list-head {
        flex-direction: column;
    }

    .calendar-grid {
        grid-template-columns: 1fr;
    }

    .calendar-head,
    .calendar-empty {
        display: none;
    }

    .calendar-day {
        min-height: auto;
    }
}

@media (max-width: 640px) {
    .parent-activities-page {
        padding: 1.5rem 0 2.5rem;
    }

    .activities-hero,
    .calendar-card,
    .activities-list-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .activities-hero-actions .btn,
    .calendar-nav .btn {
        width: 100%;
    }

    .calendar-nav {
        width: 100%;
    }
}
</style>

<main class="parent-activities-page">
    <div class="container">

        <section class="activities-hero">
            <div>
                <div class="activities-kicker">
                    <i class="fas fa-calendar-check"></i>
                    Bērnu aktivitātes
                </div>

                <h1>Aktivitāšu kalendārs</h1>

                <p>
                    Šeit redzamas aktivitātes, kurās ir pieteikti jūsu bērni.
                    Var pārskatīt gan kalendāru, gan detalizētu aktivitāšu sarakstu.
                </p>

                <div class="activities-hero-actions">
                    <a class="btn btn-primary btn-sm" href="../dashboards/parent.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="activities-hero-card">
                <strong><?= (int)$activitiesCount; ?></strong>
                <span>
                    Aktivitātes, kurās bērni pašlaik ir pieteikti vai apstiprināti.
                </span>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="activities-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="calendar-card">
            <div class="calendar-toolbar">
                <div>
                    <h2><?= htmlspecialchars($monthNames[$month] . " " . $year); ?></h2>
                    <p>Kalendārs ar bērnu pieteiktajām aktivitātēm.</p>
                </div>

                <div class="calendar-nav">
                    <a class="btn btn-outline btn-sm" href="?month=<?= (int)$prevMonth; ?>&year=<?= (int)$prevYear; ?>">
                        <i class="fas fa-chevron-left"></i>
                        Iepriekšējais
                    </a>

                    <a class="btn btn-primary btn-sm" href="?month=<?= date("m"); ?>&year=<?= date("Y"); ?>">
                        Šodien
                    </a>

                    <a class="btn btn-outline btn-sm" href="?month=<?= (int)$nextMonth; ?>&year=<?= (int)$nextYear; ?>">
                        Nākamais
                        <i class="fas fa-chevron-right"></i>
                    </a>
                </div>
            </div>

            <div class="calendar-grid">
                <div class="calendar-head">Pirmd.</div>
                <div class="calendar-head">Otrd.</div>
                <div class="calendar-head">Trešd.</div>
                <div class="calendar-head">Cet.</div>
                <div class="calendar-head">Piekt.</div>
                <div class="calendar-head">Sest.</div>
                <div class="calendar-head">Svētd.</div>

                <?php for ($empty = 1; $empty < $startWeekDay; $empty++): ?>
                    <div class="calendar-day calendar-empty"></div>
                <?php endfor; ?>

                <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                    <?php
                        $dateKey = sprintf("%04d-%02d-%02d", $year, $month, $day);
                        $dayActivities = $calendarItems[$dateKey] ?? [];
                        $isToday = ($dateKey === date("Y-m-d"));
                    ?>

                    <div class="calendar-day <?= $isToday ? 'calendar-today' : ''; ?>">
                        <div class="calendar-date"><?= (int)$day; ?></div>

                        <?php foreach ($dayActivities as $item): ?>
                            <?php
                                $isMultiDay = !empty($item["end_date"]) && $item["end_date"] !== $item["start_date"];
                                $childName = trim(($item["child_vards"] ?? "") . " " . ($item["child_uzvards"] ?? ""));
                            ?>

                            <div class="calendar-event <?= $isMultiDay ? 'calendar-event-multiday' : ''; ?>">
                                <strong><?= htmlspecialchars($item["title"] ?? "Aktivitāte"); ?></strong>

                                <span><?= htmlspecialchars($childName ?: "Bērns"); ?></span>

                                <?php if ($isMultiDay): ?>
                                    <small>
                                        <?= htmlspecialchars(formatEventDateRange($item["start_date"], $item["end_date"])); ?>
                                    </small>
                                <?php endif; ?>

                                <?php if (!empty($item["start_time"])): ?>
                                    <small>
                                        <?= htmlspecialchars(formatEventTimeRange($item["start_time"], $item["end_time"])); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </section>

        <section class="activities-list-card">
            <div class="activities-list-head">
                <div>
                    <h2>Pieteikto aktivitāšu saraksts</h2>
                    <p>Detalizēts pārskats par aktivitātēm, kurās bērni ir pieteikti.</p>
                </div>

                <div class="activities-count">
                    <i class="fas fa-list-check"></i>
                    Kopā: <?= (int)$activitiesCount; ?>
                </div>
            </div>

            <?php if (empty($activities)): ?>
                <div class="activities-empty">
                    <h3>Nav pieteiktu aktivitāšu</h3>
                    <p>Pagaidām bērni nav pieteikti nevienai aktivitātei.</p>
                </div>
            <?php else: ?>
                <div class="activity-list">
                    <?php foreach ($activities as $activity): ?>
                        <?php
                            $childName = trim(($activity["child_vards"] ?? "") . " " . ($activity["child_uzvards"] ?? ""));
                            $timeText = formatEventTimeRange($activity["start_time"], $activity["end_time"]);
                        ?>

                        <article class="activity-card">
                            <div>
                                <h3><?= htmlspecialchars($activity["title"] ?? "Aktivitāte"); ?></h3>

                                <div class="activity-meta">
                                    <span class="activity-pill">
                                        <i class="fas fa-child"></i>
                                        <?= htmlspecialchars($childName ?: "Bērns"); ?>
                                    </span>

                                    <span class="activity-pill">
                                        <i class="fas fa-tag"></i>
                                        <?= htmlspecialchars($activity["event_type"] ?? "pasākums"); ?>
                                    </span>

                                    <span class="activity-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatEventDateRange($activity["start_date"], $activity["end_date"])); ?>
                                    </span>

                                    <?php if ($timeText !== ""): ?>
                                        <span class="activity-pill">
                                            <i class="fas fa-clock"></i>
                                            <?= htmlspecialchars($timeText); ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="activity-pill">
                                        <i class="fas fa-location-dot"></i>
                                        <?= htmlspecialchars($activity["location"] ?? "—"); ?>
                                    </span>
                                </div>

                                <?php if (!empty($activity["description"])): ?>
                                    <p class="activity-desc">
                                        <?= htmlspecialchars($activity["description"]); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <span class="badge badge-blue activity-status">
                                <?= htmlspecialchars($activity["application_status"] ?? "—"); ?>
                            </span>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
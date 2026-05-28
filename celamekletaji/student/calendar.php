<?php
session_start();

$lapa  = "Mans kalendārs";
$title = "Mans kalendārs - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   PIEKĻUVES PĀRBAUDE
================================ */
$allowedRoles = ["Skolēns", "Ceļameklētājs", "Bērns", "student", "child"];

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), $allowedRoles, true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = (int) $_SESSION["lietotajs_id"];

$error = null;
$calendarItemsRaw = [];

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function tableExists(mysqli $db, string $table): bool
{
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $table);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int) ($row["total"] ?? 0) > 0;
}

function formatDateRangeLv(?string $startDate, ?string $endDate): string
{
    if (empty($startDate) || $startDate === "0000-00-00") {
        return "—";
    }

    $start = date("d.m.Y", strtotime($startDate));

    if (!empty($endDate) && $endDate !== "0000-00-00" && $endDate !== $startDate) {
        $end = date("d.m.Y", strtotime($endDate));
        return $start . " - " . $end;
    }

    return $start;
}

function formatTimeRangeLv(?string $startTime, ?string $endTime): string
{
    if (empty($startTime)) {
        return "";
    }

    $start = substr($startTime, 0, 5);

    if (!empty($endTime) && $endTime !== $startTime) {
        $end = substr($endTime, 0, 5);
        return $start . " - " . $end;
    }

    return $start;
}

function normalizeDate(?string $date): ?string
{
    if (empty($date) || $date === "0000-00-00") {
        return null;
    }

    return $date;
}

/* ===============================
   TABULU PĀRBAUDE
================================ */
$lessonsTableExists = tableExists($savienojums, "cm_lessons");
$lessonAppTableExists = tableExists($savienojums, "cm_lesson_applications");

$eventsTableExists = tableExists($savienojums, "cm_events");
$eventAppTableExists = tableExists($savienojums, "cm_event_applications");

/* ===============================
   PIETEIKTĀS NODARBĪBAS
================================ */
if ($lessonsTableExists && $lessonAppTableExists) {
    $lessonsSql = "
        SELECT
            l.id,
            l.title,
            l.description,
            l.lesson_date AS start_date,
            NULL AS end_date,
            l.lesson_time AS start_time,
            NULL AS end_time,
            l.location,
            la.status,
            'nodarbība' AS item_type
        FROM cm_lesson_applications la
        INNER JOIN cm_lessons l
            ON l.id = la.lesson_id
        WHERE la.user_id = ?
          AND la.status IN ('pieteikts', 'apstiprināts', 'apstiprinats')
          AND l.is_active = 1
          AND l.lesson_date >= CURDATE()
        ORDER BY l.lesson_date ASC, l.lesson_time ASC
    ";

    if ($stmt = $savienojums->prepare($lessonsSql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $calendarItemsRaw[] = $row;
        }

        $stmt->close();
    } else {
        $error = "Neizdevās ielādēt pieteiktās nodarbības.";
    }
}

/* ===============================
   PIETEIKTIE PASĀKUMI
================================ */
if ($eventsTableExists && $eventAppTableExists) {
    $eventsSql = "
        SELECT
            e.id,
            e.title,
            e.description,
            e.start_date,
            e.end_date,
            e.start_time,
            e.end_time,
            e.location,
            ea.status,
            e.event_type AS item_type
        FROM cm_event_applications ea
        INNER JOIN cm_events e
            ON e.id = ea.event_id
        WHERE ea.child_id = ?
          AND ea.status IN ('pieteikts', 'apstiprināts', 'apstiprinats')
          AND e.is_active = 1
          AND (
                e.start_date >= CURDATE()
                OR (
                    e.end_date IS NOT NULL
                    AND e.end_date >= CURDATE()
                )
          )
        ORDER BY e.start_date ASC, e.start_time ASC
    ";

    if ($stmt = $savienojums->prepare($eventsSql)) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $calendarItemsRaw[] = $row;
        }

        $stmt->close();
    } else {
        $error = "Neizdevās ielādēt pieteiktos pasākumus.";
    }
}

$itemsCount = count($calendarItemsRaw);

/* ===============================
   KALENDĀRA DATI
================================ */
$calendarItems = [];

foreach ($calendarItemsRaw as $item) {
    $startDate = normalizeDate($item["start_date"] ?? null);
    $endDate   = normalizeDate($item["end_date"] ?? null);

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
        $calendarItems[$dateKey][] = $item;
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

require __DIR__ . "/../includes/templates/header-student.php";
?>

<style>
.student-calendar-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.calendar-hero {
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

.calendar-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.calendar-hero > * {
    position: relative;
    z-index: 1;
}

.calendar-kicker {
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

.calendar-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.calendar-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.calendar-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.calendar-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.calendar-hero-card strong {
    display: block;
    font-size: 2.1rem;
    line-height: 1;
    color: #f4c430;
}

.calendar-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.calendar-alert {
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

.calendar-card {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
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
    display: block;
    background: linear-gradient(135deg, #1e4fa1, #173f84);
    color: #fff;
    border-radius: 14px;
    padding: .5rem .6rem;
    margin-bottom: .4rem;
    font-size: .82rem;
    box-shadow: 0 10px 20px rgba(23, 63, 132, 0.15);
}

.calendar-event--lesson {
    background: linear-gradient(135deg, #1e4fa1, #173f84);
}

.calendar-event--event {
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

.calendar-legend {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin: 1rem 0 0;
}

.calendar-legend-item {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    color: #667085;
    font-weight: 800;
    font-size: .9rem;
}

.calendar-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
}

.calendar-dot.lesson {
    background: #173f84;
}

.calendar-dot.event {
    background: #f4c430;
}

.calendar-empty-state {
    margin-top: 1rem;
    padding: 1.2rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.calendar-empty-state h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

@media (max-width: 900px) {
    .calendar-hero {
        grid-template-columns: 1fr;
    }

    .calendar-toolbar {
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
    .student-calendar-page {
        padding: 1.5rem 0 2.5rem;
    }

    .calendar-hero,
    .calendar-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .calendar-hero-actions .btn,
    .calendar-nav,
    .calendar-nav .btn {
        width: 100%;
    }
}
</style>

<main class="student-calendar-page">
    <div class="container">

        <section class="calendar-hero">
            <div>
                <div class="calendar-kicker">
                    <i class="fas fa-calendar-days"></i>
                    Mans kalendārs
                </div>

                <h1>Kalendārs</h1>

                <p>
                    Šeit redzamas tikai tās nodarbības un pasākumi, kuriem esi pieteicies.
                </p>

                <div class="calendar-hero-actions">
                    <a class="btn btn-primary btn-sm" href="applications.php">
                        <i class="fas fa-clipboard-check"></i>
                        Mani pieteikumi
                    </a>

                    <a class="btn btn-outline btn-sm" href="events.php">
                        <i class="fas fa-list-check"></i>
                        Pasākumu saraksts
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/student.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="calendar-hero-card">
                <strong><?= (int)$itemsCount; ?></strong>
                <span>
                    Pieteiktās nodarbības un pasākumi kalendārā.
                </span>
            </aside>
        </section>

        <?php if (!empty($error)): ?>
            <div class="calendar-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="calendar-card">
            <div class="calendar-toolbar">
                <div>
                    <h2><?= htmlspecialchars($monthNames[$month] . " " . $year); ?></h2>
                    <p>Pieteikto nodarbību un pasākumu kalendārs.</p>
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
                        $dayItems = $calendarItems[$dateKey] ?? [];
                        $isToday = ($dateKey === date("Y-m-d"));
                    ?>

                    <div class="calendar-day <?= $isToday ? 'calendar-today' : ''; ?>">
                        <div class="calendar-date"><?= (int)$day; ?></div>

                        <?php foreach ($dayItems as $item): ?>
                            <?php
                                $itemType = $item["item_type"] ?? "";
                                $isLesson = ($itemType === "nodarbība");
                                $timeText = formatTimeRangeLv($item["start_time"] ?? null, $item["end_time"] ?? null);
                                $dateText = formatDateRangeLv($item["start_date"] ?? null, $item["end_date"] ?? null);
                            ?>

                            <div class="calendar-event <?= $isLesson ? 'calendar-event--lesson' : 'calendar-event--event'; ?>">
                                <strong><?= htmlspecialchars($item["title"] ?? "Aktivitāte"); ?></strong>

                                <span>
                                    <?= $isLesson ? "Nodarbība" : htmlspecialchars($itemType ?: "Pasākums"); ?>
                                </span>

                                <?php if ($timeText !== ""): ?>
                                    <small><?= htmlspecialchars($timeText); ?></small>
                                <?php endif; ?>

                                <?php if (!$isLesson && $dateText !== "—"): ?>
                                    <small><?= htmlspecialchars($dateText); ?></small>
                                <?php endif; ?>

                                <?php if (!empty($item["location"])): ?>
                                    <small>
                                        <i class="fas fa-location-dot"></i>
                                        <?= htmlspecialchars($item["location"]); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <div class="calendar-legend">
                <span class="calendar-legend-item">
                    <span class="calendar-dot lesson"></span>
                    Nodarbības
                </span>

                <span class="calendar-legend-item">
                    <span class="calendar-dot event"></span>
                    Pasākumi
                </span>
            </div>

            <?php if (empty($calendarItemsRaw)): ?>
                <div class="calendar-empty-state">
                    <h3>Nav pieteiktu aktivitāšu</h3>
                    <p>Kalendārā parādīsies tikai tās nodarbības un pasākumi, kuriem esi pieteicies.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
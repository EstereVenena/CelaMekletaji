<?php
session_start();

$lapa  = "Aktivitātes";
$title = "Aktivitātes - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

if (!isset($_SESSION["lietotajs_id"]) || !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)) {
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

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<main class="dashboard-main">
    <div class="container">

        <div class="dashboard-header">
            <h2>Aktivitāšu kalendārs</h2>
            <p class="lead">Šeit redzamas aktivitātes, kurās ir pieteikti jūsu bērni.</p>
        </div>

        <?php if ($error): ?>
            <div class="dashboard-card" style="border-left:4px solid #c0392b;">
                <p class="muted"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <div class="dashboard-card">
            <div class="section-title-row" style="margin-bottom:1rem;">
                <div>
                    <h3><?php echo htmlspecialchars($monthNames[$month] . " " . $year); ?></h3>
                    <p class="muted small">Kalendārs ar bērnu pieteiktajām aktivitātēm.</p>
                </div>

                <div style="display:flex; gap:.5rem; flex-wrap:wrap;">
                    <a class="btn btn-outline btn-sm" href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>">
                        Iepriekšējais
                    </a>

                    <a class="btn btn-primary btn-sm" href="?month=<?php echo date("m"); ?>&year=<?php echo date("Y"); ?>">
                        Šodien
                    </a>

                    <a class="btn btn-outline btn-sm" href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>">
                        Nākamais
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

                    <div class="calendar-day <?php echo $isToday ? 'calendar-today' : ''; ?>">
                        <div class="calendar-date"><?php echo $day; ?></div>

                        <?php foreach ($dayActivities as $item): ?>
                            <?php
                            $isMultiDay = !empty($item["end_date"]) && $item["end_date"] !== $item["start_date"];
                            ?>
                            <div class="calendar-event <?php echo $isMultiDay ? 'calendar-event-multiday' : ''; ?>">
                                <strong><?php echo htmlspecialchars($item["title"] ?? "Aktivitāte"); ?></strong>

                                <span>
                                    <?php echo htmlspecialchars(trim(($item["child_vards"] ?? "") . " " . ($item["child_uzvards"] ?? ""))); ?>
                                </span>

                                <?php if ($isMultiDay): ?>
                                    <small>
                                        <?php echo htmlspecialchars(formatEventDateRange($item["start_date"], $item["end_date"])); ?>
                                    </small>
                                <?php endif; ?>

                                <?php if (!empty($item["start_time"])): ?>
                                    <small>
                                        <?php echo htmlspecialchars(formatEventTimeRange($item["start_time"], $item["end_time"])); ?>
                                    </small>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endfor; ?>
            </div>
        </div>

        <div class="dashboard-card" style="margin-top:1rem;">
            <h3>Pieteikto aktivitāšu saraksts</h3>
            <p class="muted">Detalizēts pārskats par aktivitātēm, kurās bērni ir pieteikti.</p>

            <div class="divider"></div>

            <?php if (empty($activities)): ?>
                <p class="muted">Pagaidām bērni nav pieteikti nevienai aktivitātei.</p>
            <?php else: ?>
                <div class="cards">
                    <?php foreach ($activities as $activity): ?>
                        <div class="card">
                            <div class="program-head">
                                <div>
                                    <h4 style="margin:0;">
                                        <?php echo htmlspecialchars($activity["title"] ?? "Aktivitāte"); ?>
                                    </h4>

                                    <p class="muted small" style="margin:.3rem 0 0;">
                                        Bērns:
                                        <strong>
                                            <?php echo htmlspecialchars(trim(($activity["child_vards"] ?? "") . " " . ($activity["child_uzvards"] ?? ""))); ?>
                                        </strong>
                                    </p>

                                    <p class="muted small" style="margin:.3rem 0 0;">
                                        Veids:
                                        <?php echo htmlspecialchars($activity["event_type"] ?? "pasākums"); ?>
                                    </p>

                                    <p class="muted small" style="margin:.3rem 0 0;">
                                        Datums:
                                        <?php echo htmlspecialchars(formatEventDateRange($activity["start_date"], $activity["end_date"])); ?>

                                        <?php
                                        $timeText = formatEventTimeRange($activity["start_time"], $activity["end_time"]);
                                        if ($timeText !== ""):
                                        ?>
                                            <?php echo htmlspecialchars($timeText); ?>
                                        <?php endif; ?>
                                    </p>

                                    <p class="muted small" style="margin:.3rem 0 0;">
                                        Vieta: <?php echo htmlspecialchars($activity["location"] ?? "—"); ?>
                                    </p>

                                    <p class="muted small" style="margin:.3rem 0 0;">
                                        Statuss: <?php echo htmlspecialchars($activity["application_status"] ?? "—"); ?>
                                    </p>

                                    <?php if (!empty($activity["description"])): ?>
                                        <p class="muted" style="margin:.6rem 0 0;">
                                            <?php echo htmlspecialchars($activity["description"]); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    gap: .6rem;
}

.calendar-head {
    font-weight: 800;
    color: #173f84;
    text-align: center;
    padding: .6rem;
    background: #eef3ff;
    border-radius: 12px;
}

.calendar-day {
    min-height: 125px;
    background: #fff;
    border: 1px solid #eef1f6;
    border-radius: 16px;
    padding: .65rem;
    box-shadow: 0 8px 22px rgba(15, 23, 42, 0.04);
}

.calendar-empty {
    background: transparent;
    border: none;
    box-shadow: none;
}

.calendar-today {
    border: 2px solid #f4c430;
    background: #fffdf3;
}

.calendar-date {
    font-weight: 800;
    color: #173f84;
    margin-bottom: .45rem;
}

.calendar-event {
    background: linear-gradient(135deg, #1e4fa1, #173f84);
    color: #fff;
    border-radius: 12px;
    padding: .45rem .55rem;
    margin-bottom: .35rem;
    font-size: .82rem;
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

.calendar-event small {
    opacity: .9;
    margin-top: .15rem;
}

@media (max-width: 850px) {
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
</style>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
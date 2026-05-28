<?php
session_start();

$lapa  = "Pasākumi";
$title = "Pasākumi - Ceļa meklētāji";

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
$events = [];
$success = trim($_GET["success"] ?? "");
$error   = trim($_GET["error"] ?? "");

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
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
        return "—";
    }

    $start = substr($startTime, 0, 5);

    if (!empty($endTime) && $endTime !== $startTime) {
        $end = substr($endTime, 0, 5);
        return $start . " - " . $end;
    }

    return $start;
}

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

function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: events.php?" . $param . "=" . urlencode($message));
    exit();
}

$eventsTableExists = tableExists($savienojums, "cm_events");
$appTableExists    = tableExists($savienojums, "cm_event_applications");

/* ===============================
   PIETEIKŠANĀS PASĀKUMAM
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["event_id"])) {
    if (!$eventsTableExists || !$appTableExists) {
        redirectWithMessage("error", "Pasākumu pieteikšanās tabula vēl nav izveidota.");
    }

    $eventId = (int) $_POST["event_id"];

    if ($eventId <= 0) {
        redirectWithMessage("error", "Nederīgs pasākums.");
    }

    $checkSql = "
        SELECT id, max_participants
        FROM cm_events
        WHERE id = ?
          AND is_active = 1
          AND (
                start_date >= CURDATE()
                OR (
                    end_date IS NOT NULL
                    AND end_date >= CURDATE()
                )
          )
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($checkSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās pārbaudīt pasākumu.");
    }

    $stmt->bind_param("i", $eventId);
    $stmt->execute();

    $eventResult = $stmt->get_result();
    $event = $eventResult->fetch_assoc();

    $stmt->close();

    if (!$event) {
        redirectWithMessage("error", "Pasākums nav atrasts, nav aktīvs vai jau ir beidzies.");
    }

    /* Pārbauda, vai jau aktīvi pieteicies */
    $alreadySql = "
        SELECT id
        FROM cm_event_applications
        WHERE event_id = ?
          AND child_id = ?
          AND status = 'pieteikts'
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($alreadySql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās pārbaudīt esošu pieteikumu.");
    }

    $stmt->bind_param("ii", $eventId, $userId);
    $stmt->execute();

    $alreadyResult = $stmt->get_result();
    $alreadyApplied = $alreadyResult->num_rows > 0;

    $stmt->close();

    if ($alreadyApplied) {
        redirectWithMessage("error", "Tu jau esi pieteicies šim pasākumam.");
    }

    /* Ja iepriekš bija atcelts, atjauno esošo pieteikumu */
    $cancelledSql = "
        SELECT id
        FROM cm_event_applications
        WHERE event_id = ?
          AND child_id = ?
          AND status = 'atcelts'
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($cancelledSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās pārbaudīt atceltu pieteikumu.");
    }

    $stmt->bind_param("ii", $eventId, $userId);
    $stmt->execute();

    $cancelledResult = $stmt->get_result();
    $cancelledApplication = $cancelledResult->fetch_assoc();

    $stmt->close();

    if ($cancelledApplication) {
        $cancelledApplicationId = (int) $cancelledApplication["id"];

        $restoreSql = "
            UPDATE cm_event_applications
            SET status = 'pieteikts',
                applied_at = NOW()
            WHERE id = ?
              AND child_id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($restoreSql);

        if (!$stmt) {
            redirectWithMessage("error", "Neizdevās atjaunot pieteikumu.");
        }

        $stmt->bind_param("ii", $cancelledApplicationId, $userId);

        if ($stmt->execute()) {
            $stmt->close();
            redirectWithMessage("success", "Pieteikums pasākumam veiksmīgi atjaunots.");
        }

        $stmt->close();
        redirectWithMessage("error", "Neizdevās atjaunot pieteikumu.");
    }

    /* Vietu limita pārbaude */
    if (!empty($event["max_participants"])) {
        $countSql = "
            SELECT COUNT(*) AS total
            FROM cm_event_applications
            WHERE event_id = ?
              AND status = 'pieteikts'
        ";

        $stmt = $savienojums->prepare($countSql);

        if (!$stmt) {
            redirectWithMessage("error", "Neizdevās pārbaudīt brīvās vietas.");
        }

        $stmt->bind_param("i", $eventId);
        $stmt->execute();

        $countResult = $stmt->get_result();
        $countRow = $countResult->fetch_assoc();

        $stmt->close();

        if ((int) ($countRow["total"] ?? 0) >= (int) $event["max_participants"]) {
            redirectWithMessage("error", "Šim pasākumam vairs nav brīvu vietu.");
        }
    }

    $insertSql = "
        INSERT INTO cm_event_applications 
            (event_id, child_id, status, applied_at)
        VALUES 
            (?, ?, 'pieteikts', NOW())
    ";

    $stmt = $savienojums->prepare($insertSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot pieteikumu.");
    }

    $stmt->bind_param("ii", $eventId, $userId);

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage("success", "Pieteikšanās pasākumam veiksmīga.");
    }

    $stmt->close();

    if ($savienojums->errno === 1062) {
        redirectWithMessage("error", "Tu jau esi pieteicies šim pasākumam.");
    }

    redirectWithMessage("error", "Neizdevās saglabāt pieteikumu.");
}

/* ===============================
   PASĀKUMU SARAKSTS
================================ */
if ($eventsTableExists) {
    if ($appTableExists) {
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
                e.event_type,
                e.max_participants,
                (
                    SELECT COUNT(*)
                    FROM cm_event_applications ea
                    WHERE ea.event_id = e.id
                      AND ea.status = 'pieteikts'
                ) AS applied_count,
                (
                    SELECT COUNT(*)
                    FROM cm_event_applications ea2
                    WHERE ea2.event_id = e.id
                      AND ea2.child_id = ?
                      AND ea2.status = 'pieteikts'
                ) AS user_applied
            FROM cm_events e
            WHERE e.is_active = 1
              AND (
                    e.start_date >= CURDATE()
                    OR (
                        e.end_date IS NOT NULL
                        AND e.end_date >= CURDATE()
                    )
              )
            ORDER BY e.start_date ASC, e.start_time ASC
        ";

        $stmt = $savienojums->prepare($eventsSql);

        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }

            $stmt->close();
        } else {
            $error = "Neizdevās ielādēt pasākumus.";
        }
    } else {
        $eventsSql = "
            SELECT
                id,
                title,
                description,
                start_date,
                end_date,
                start_time,
                end_time,
                location,
                event_type,
                max_participants,
                0 AS applied_count,
                0 AS user_applied
            FROM cm_events
            WHERE is_active = 1
              AND (
                    start_date >= CURDATE()
                    OR (
                        end_date IS NOT NULL
                        AND end_date >= CURDATE()
                    )
              )
            ORDER BY start_date ASC, start_time ASC
        ";

        if ($result = $savienojums->query($eventsSql)) {
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
        } else {
            $error = "Neizdevās ielādēt pasākumus.";
        }
    }
} else {
    $error = "Pasākumu tabula cm_events vēl nav izveidota.";
}

$eventsCount = count($events);

require __DIR__ . "/../includes/templates/header-student.php";
?>

<style>
.student-events-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.events-hero {
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

.events-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.events-hero > * {
    position: relative;
    z-index: 1;
}

.events-kicker {
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

.events-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.events-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.events-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.events-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.events-hero-card strong {
    display: block;
    font-size: 2.1rem;
    line-height: 1;
    color: #f4c430;
}

.events-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.event-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 800;
}

.event-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.event-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.events-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.events-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.events-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.events-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
}

.events-count {
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

.events-grid {
    display: grid;
    gap: .95rem;
}

.event-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 20px;
    background: #f8fbff;
    transition: .2s ease;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.event-card h3 {
    margin: 0 0 .45rem;
    color: #101828;
    font-size: 1.12rem;
    line-height: 1.25;
}

.event-meta {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    margin-bottom: .65rem;
}

.event-pill {
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

.event-pill i {
    color: #1e4fa1;
}

.event-description {
    margin: .65rem 0 0;
    color: #667085;
    line-height: 1.55;
}

.event-side {
    width: 250px;
    align-self: stretch;
    display: flex;
    flex-direction: column;
    gap: .7rem;
    justify-content: space-between;
    padding: .95rem;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #edf2fb;
}

.event-space {
    display: grid;
    gap: .25rem;
}

.event-space span {
    color: #667085;
    font-size: .88rem;
    font-weight: 800;
}

.event-space strong {
    color: #173f84;
    font-size: 1.05rem;
}

.event-progress {
    width: 100%;
    height: 9px;
    overflow: hidden;
    border-radius: 999px;
    background: #eef3ff;
}

.event-progress-bar {
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(135deg, #173f84, #1e4fa1);
}

.event-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    padding: .7rem .85rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-weight: 950;
    text-align: center;
}

.event-status.success {
    background: #ecfff4;
    color: #17633a;
}

.event-status.warning {
    background: #fff8e6;
    color: #7a5517;
}

.event-status.disabled {
    background: #f2f4f7;
    color: #667085;
}

.event-side form {
    margin: 0;
}

.event-side .btn {
    width: 100%;
}

.events-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.events-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

@media (max-width: 900px) {
    .events-hero,
    .event-card {
        grid-template-columns: 1fr;
    }

    .events-panel-head {
        flex-direction: column;
    }

    .event-side {
        width: 100%;
    }
}

@media (max-width: 640px) {
    .student-events-page {
        padding: 1.5rem 0 2.5rem;
    }

    .events-hero,
    .events-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .events-hero-actions .btn {
        width: 100%;
    }
}
</style>

<main class="student-events-page">
    <div class="container">

        <section class="events-hero">
            <div>
                <div class="events-kicker">
                    <i class="fas fa-calendar-days"></i>
                    Pieejamie pasākumi
                </div>

                <h1>Pasākumi</h1>

                <p>
                    Apskati tuvākos pasākumus, seko brīvajām vietām un piesakies dalībai.
                </p>

                <div class="events-hero-actions">
                    <a class="btn btn-primary btn-sm" href="applications.php">
                        <i class="fas fa-clipboard-check"></i>
                        Mani pieteikumi
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/student.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="events-hero-card">
                <strong><?= (int)$eventsCount; ?></strong>
                <span>
                    Aktīvi pasākumi, kuri pašlaik ir pieejami apskatei.
                </span>
            </aside>
        </section>

        <?php if (!empty($success)): ?>
            <div class="event-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="event-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="events-panel">
            <div class="events-panel-head">
                <div>
                    <h2>Pasākumu saraksts</h2>
                    <p>Izvēlies pasākumu un piesakies, ja vēl ir brīvas vietas.</p>
                </div>

                <div class="events-count">
                    <i class="fas fa-list-check"></i>
                    Kopā: <?= (int)$eventsCount; ?>
                </div>
            </div>

            <?php if (!empty($events)): ?>
                <div class="events-grid">

                    <?php foreach ($events as $event): ?>
                        <?php
                            $eventId = (int) ($event["id"] ?? 0);
                            $alreadyApplied = (int) ($event["user_applied"] ?? 0) > 0;
                            $appliedCount = (int) ($event["applied_count"] ?? 0);

                            $maxParticipants = $event["max_participants"] ?? null;
                            $isFull = !empty($maxParticipants) && $appliedCount >= (int) $maxParticipants;

                            $description = trim($event["description"] ?? "");

                            if ($description === "") {
                                $description = "Apraksts nav pievienots.";
                            }

                            $spaceText = !empty($maxParticipants)
                                ? $appliedCount . " / " . (int)$maxParticipants
                                : $appliedCount . " pieteikušies";

                            $progress = 0;

                            if (!empty($maxParticipants)) {
                                $progress = min(100, round(($appliedCount / (int)$maxParticipants) * 100));
                            }
                        ?>

                        <article class="event-card">
                            <div>
                                <h3>
                                    <?= htmlspecialchars($event["title"] ?? "Bez nosaukuma"); ?>
                                </h3>

                                <div class="event-meta">
                                    <span class="event-pill">
                                        <i class="fas fa-tag"></i>
                                        <?= htmlspecialchars($event["event_type"] ?? "Pasākums"); ?>
                                    </span>

                                    <span class="event-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatDateRangeLv($event["start_date"] ?? null, $event["end_date"] ?? null)); ?>
                                    </span>

                                    <span class="event-pill">
                                        <i class="fas fa-clock"></i>
                                        <?= htmlspecialchars(formatTimeRangeLv($event["start_time"] ?? null, $event["end_time"] ?? null)); ?>
                                    </span>

                                    <span class="event-pill">
                                        <i class="fas fa-location-dot"></i>
                                        <?= htmlspecialchars($event["location"] ?? "—"); ?>
                                    </span>
                                </div>

                                <p class="event-description">
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($description, 0, 260, "..."))); ?>
                                </p>
                            </div>

                            <aside class="event-side">
                                <div class="event-space">
                                    <span>Pieteikušies</span>
                                    <strong><?= htmlspecialchars($spaceText); ?></strong>

                                    <?php if (!empty($maxParticipants)): ?>
                                        <div class="event-progress">
                                            <div class="event-progress-bar" style="width: <?= (int)$progress; ?>%;"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$appTableExists): ?>

                                    <div class="event-status disabled">
                                        <i class="fas fa-circle-info"></i>
                                        Pieteikšanās nav aktivizēta
                                    </div>

                                <?php elseif ($alreadyApplied): ?>

                                    <div class="event-status success">
                                        <i class="fas fa-circle-check"></i>
                                        Jau pieteicies
                                    </div>

                                <?php elseif ($isFull): ?>

                                    <div class="event-status warning">
                                        <i class="fas fa-triangle-exclamation"></i>
                                        Vietu nav
                                    </div>

                                <?php else: ?>

                                    <form method="post">
                                        <input 
                                            type="hidden" 
                                            name="event_id" 
                                            value="<?= $eventId; ?>"
                                        >

                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-paper-plane"></i>
                                            Pieteikties
                                        </button>
                                    </form>

                                <?php endif; ?>
                            </aside>
                        </article>

                    <?php endforeach; ?>

                </div>
            <?php else: ?>
                <div class="events-empty">
                    <h3>Šobrīd nav pieejamu pasākumu</h3>
                    <p>Pasākumu saraksts pagaidām ir tukšs.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
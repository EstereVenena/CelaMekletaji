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

function formatTimeLv(?string $time): string
{
    if (empty($time)) {
        return "—";
    }

    return date("H:i", strtotime($time));
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
        redirectWithMessage("error", "Pasākums nav atrasts vai nav aktīvs.");
    }

    $alreadySql = "
        SELECT id
        FROM cm_event_applications
        WHERE event_id = ?
          AND user_id = ?
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
        INSERT INTO cm_event_applications (event_id, user_id, status)
        VALUES (?, ?, 'pieteikts')
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
                e.event_date,
                e.event_time,
                e.location,
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
                      AND ea2.user_id = ?
                      AND ea2.status = 'pieteikts'
                ) AS user_applied
            FROM cm_events e
            WHERE e.is_active = 1
            ORDER BY e.event_date ASC, e.event_time ASC
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
                event_date,
                event_time,
                location,
                max_participants,
                0 AS applied_count,
                0 AS user_applied
            FROM cm_events
            WHERE is_active = 1
            ORDER BY event_date ASC, event_time ASC
        ";

        if ($result = $savienojums->query($eventsSql)) {
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
        }
    }
} else {
    $error = "Pasākumu tabula cm_events vēl nav izveidota.";
}

require __DIR__ . "/../includes/templates/header-student.php";
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <div class="page-hero-content">
            <h1>Pasākumi</h1>
            <p class="lead">
                Apskati tuvākos pasākumus un piesakies dalībai.
            </p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if (!empty($success)): ?>
            <div class="card" style="margin-bottom:1rem; border-left:4px solid #2e9e44;">
                <p class="muted"><?= htmlspecialchars($success) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="card" style="margin-bottom:1rem; border-left:4px solid #c0392b;">
                <p class="muted"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($events)): ?>
            <div class="lessons-grid">

                <?php foreach ($events as $event): ?>
                    <?php
                        $eventId = (int) $event["id"];
                        $alreadyApplied = (int) $event["user_applied"] > 0;
                        $appliedCount = (int) $event["applied_count"];

                        $maxParticipants = $event["max_participants"] ?? null;
                        $isFull = !empty($maxParticipants) && $appliedCount >= (int) $maxParticipants;

                        $description = trim($event["description"] ?? "");
                        if ($description === "") {
                            $description = "Apraksts nav pievienots.";
                        }
                    ?>

                    <article class="card news-card-page lesson-card">
                        <div class="news-meta">
                            <span class="news-tag">Pasākums</span>
                            <span class="news-date">
                                <?= htmlspecialchars(formatDateLv($event["event_date"] ?? null)) ?>
                            </span>
                        </div>

                        <h2 class="news-card-title-page">
                            <?= htmlspecialchars($event["title"] ?? "Bez nosaukuma") ?>
                        </h2>

                        <p class="muted small">
                            <strong>Laiks:</strong>
                            <?= htmlspecialchars(formatTimeLv($event["event_time"] ?? null)) ?>
                            &nbsp;•&nbsp;
                            <strong>Vieta:</strong>
                            <?= htmlspecialchars($event["location"] ?? "—") ?>
                        </p>

                        <p class="muted">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($description, 0, 240, "..."))) ?>
                        </p>

                        <p class="muted small">
                            <strong>Pieteikušies:</strong>
                            <?= $appliedCount ?>

                            <?php if (!empty($maxParticipants)): ?>
                                / <?= (int) $maxParticipants ?>
                            <?php endif; ?>
                        </p>

                        <div class="news-actions">
                            <?php if (!$appTableExists): ?>

                                <button class="btn btn-outline btn-sm" type="button" disabled>
                                    Pieteikšanās nav aktivizēta
                                </button>

                            <?php elseif ($alreadyApplied): ?>

                                <button class="btn btn-outline btn-sm" type="button" disabled>
                                    Jau pieteicies
                                </button>

                            <?php elseif ($isFull): ?>

                                <button class="btn btn-outline btn-sm" type="button" disabled>
                                    Vietu nav
                                </button>

                            <?php else: ?>

                                <form method="post">
                                    <input type="hidden" name="event_id" value="<?= $eventId ?>">

                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Pieteikties
                                    </button>
                                </form>

                            <?php endif; ?>
                        </div>
                    </article>

                <?php endforeach; ?>

            </div>
        <?php else: ?>
            <div class="card">
                <p class="muted">Šobrīd nav pieejamu pasākumu.</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>

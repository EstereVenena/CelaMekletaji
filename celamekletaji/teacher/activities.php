<?php
session_start();

$lapa  = "Pasākumi";
$title = "Pasākumi - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   PIEKĻUVES PĀRBAUDE
================================ */
$allowedRoles = ["Skolotājs", "skolotājs", "teacher"];

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), $allowedRoles, true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$teacherId = (int)($_SESSION["lietotajs_id"] ?? 0);
$teacherClubId = (int)($_SESSION["club_id"] ?? 0);

$success = "";
$error = "";
$events = [];
$club = null;

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function tableColumnExists(mysqli $db, string $table, string $column): bool
{
    $table = $db->real_escape_string($table);
    $column = $db->real_escape_string($column);

    $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");

    return $result && $result->num_rows > 0;
}

function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

function formatTimeLv(?string $time): string
{
    if (empty($time) || $time === "00:00:00") {
        return "";
    }

    return substr($time, 0, 5);
}

/* ===============================
   JA SESSIONĀ NAV CLUB_ID, PAŅEM NO DB
================================ */
if ($teacherClubId <= 0) {
    $sqlTeacher = "
        SELECT club_id
        FROM cm_lietotaji
        WHERE lietotajs_id = ?
        LIMIT 1
    ";

    if ($stmt = $savienojums->prepare($sqlTeacher)) {
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $teacherClubId = (int)($row["club_id"] ?? 0);
            $_SESSION["club_id"] = $teacherClubId;
        }

        $stmt->close();
    }
}

/* ===============================
   KLUBA DATI
================================ */
if ($teacherClubId > 0) {
    $sqlClub = "
        SELECT id, name, address
        FROM cm_clubs
        WHERE id = ?
        LIMIT 1
    ";

    if ($stmt = $savienojums->prepare($sqlClub)) {
        $stmt->bind_param("i", $teacherClubId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $club = $result->fetch_assoc();
        }

        $stmt->close();
    }
}

/* ===============================
   TABULAS KOLONNAS
================================ */
$hasClubId = tableColumnExists($savienojums, "cm_events", "club_id");
$hasCreatedBy = tableColumnExists($savienojums, "cm_events", "created_by");
$hasMaxParticipants = tableColumnExists($savienojums, "cm_events", "max_participants");
$hasIsActive = tableColumnExists($savienojums, "cm_events", "is_active");

/* ===============================
   PASĀKUMA DZĒŠANA / DEAKTIVĒŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete") {
    $eventId = (int)($_POST["event_id"] ?? 0);

    if ($eventId <= 0) {
        $error = "Nederīgs pasākuma ID.";
    } else {
        if ($hasIsActive) {
            $sqlDelete = "
                UPDATE cm_events
                SET is_active = 0
                WHERE id = ?
            ";

            $types = "i";
            $params = [$eventId];

            if ($hasCreatedBy) {
                $sqlDelete .= " AND created_by = ?";
                $types .= "i";
                $params[] = $teacherId;
            }

            $sqlDelete .= " LIMIT 1";

            if ($stmt = $savienojums->prepare($sqlDelete)) {
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $success = "Pasākums veiksmīgi deaktivizēts.";
                } else {
                    $error = "Neizdevās deaktivizēt pasākumu.";
                }

                $stmt->close();
            }
        } else {
            $sqlDelete = "
                DELETE FROM cm_events
                WHERE id = ?
            ";

            $types = "i";
            $params = [$eventId];

            if ($hasCreatedBy) {
                $sqlDelete .= " AND created_by = ?";
                $types .= "i";
                $params[] = $teacherId;
            }

            $sqlDelete .= " LIMIT 1";

            if ($stmt = $savienojums->prepare($sqlDelete)) {
                $stmt->bind_param($types, ...$params);

                if ($stmt->execute()) {
                    $success = "Pasākums veiksmīgi dzēsts.";
                } else {
                    $error = "Neizdevās dzēst pasākumu.";
                }

                $stmt->close();
            }
        }
    }
}

/* ===============================
   PASĀKUMA PIEVIENOŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add") {
    $titleInput       = trim($_POST["title"] ?? "");
    $description     = trim($_POST["description"] ?? "");
    $startDate       = trim($_POST["start_date"] ?? "");
    $endDate         = trim($_POST["end_date"] ?? "");
    $startTime       = trim($_POST["start_time"] ?? "");
    $endTime         = trim($_POST["end_time"] ?? "");
    $location        = trim($_POST["location"] ?? "");
    $eventType       = trim($_POST["event_type"] ?? "nodarbība");
    $maxParticipants = trim($_POST["max_participants"] ?? "");

    if ($titleInput === "" || $startDate === "" || $location === "") {
        $error = "Lūdzu aizpildi obligātos laukus: nosaukums, datums un vieta.";
    } else {
        if ($endDate === "") {
            $endDate = $startDate;
        }

        $fields = [
            "title",
            "description",
            "start_date",
            "end_date",
            "start_time",
            "end_time",
            "location",
            "event_type"
        ];

        $placeholders = ["?", "?", "?", "?", "?", "?", "?", "?"];
        $types = "ssssssss";

        $params = [
            $titleInput,
            $description,
            $startDate,
            $endDate,
            $startTime,
            $endTime,
            $location,
            $eventType
        ];

        if ($hasMaxParticipants) {
            $fields[] = "max_participants";
            $placeholders[] = "?";
            $types .= "i";
            $params[] = $maxParticipants !== "" ? (int)$maxParticipants : 0;
        }

        if ($hasCreatedBy) {
            $fields[] = "created_by";
            $placeholders[] = "?";
            $types .= "i";
            $params[] = $teacherId;
        }

        if ($hasClubId && $teacherClubId > 0) {
            $fields[] = "club_id";
            $placeholders[] = "?";
            $types .= "i";
            $params[] = $teacherClubId;
        }

        if ($hasIsActive) {
            $fields[] = "is_active";
            $placeholders[] = "?";
            $types .= "i";
            $params[] = 1;
        }

        $sqlInsert = "
            INSERT INTO cm_events
            (" . implode(", ", $fields) . ")
            VALUES
            (" . implode(", ", $placeholders) . ")
        ";

        if ($stmt = $savienojums->prepare($sqlInsert)) {
            $stmt->bind_param($types, ...$params);

            if ($stmt->execute()) {
                $success = "Pasākums veiksmīgi pievienots.";
            } else {
                $error = "Neizdevās pievienot pasākumu.";
            }

            $stmt->close();
        } else {
            $error = "Neizdevās sagatavot pasākuma pievienošanas vaicājumu.";
        }
    }
}

/* ===============================
   PASĀKUMU SARAKSTS
================================ */
$where = "1";

if ($hasIsActive) {
    $where .= " AND e.is_active = 1";
}

if ($hasClubId && $teacherClubId > 0) {
    $where .= " AND e.club_id = " . (int)$teacherClubId;
}

$sqlEvents = "
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
        " . ($hasMaxParticipants ? "e.max_participants" : "0 AS max_participants") . ",
        " . ($hasCreatedBy ? "e.created_by" : "NULL AS created_by") . ",
        COUNT(ea.id) AS applications_count
    FROM cm_events e
    LEFT JOIN cm_event_applications ea ON e.id = ea.event_id
    WHERE {$where}
    GROUP BY
        e.id,
        e.title,
        e.description,
        e.start_date,
        e.end_date,
        e.start_time,
        e.end_time,
        e.location,
        e.event_type,
        " . ($hasMaxParticipants ? "e.max_participants," : "") . "
        " . ($hasCreatedBy ? "e.created_by" : "e.id") . "
    ORDER BY e.start_date DESC, e.id DESC
";

$resultEvents = $savienojums->query($sqlEvents);

if ($resultEvents) {
    while ($row = $resultEvents->fetch_assoc()) {
        $events[] = $row;
    }
} else {
    $error = "Neizdevās ielādēt pasākumus.";
}

require __DIR__ . "/../includes/templates/header-teacher.php";
?>

<style>
.teacher-activities-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30,79,161,.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244,196,48,.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.teacher-activities-hero {
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
    box-shadow: 0 24px 60px rgba(23,63,132,.22);
}

.teacher-activities-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.teacher-activities-hero > * {
    position: relative;
    z-index: 1;
}

.teacher-kicker {
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

.teacher-activities-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.teacher-activities-hero p {
    max-width: 760px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.teacher-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.18);
    backdrop-filter: blur(8px);
}

.teacher-hero-card strong {
    display: block;
    font-size: 2.2rem;
    line-height: 1;
    color: #f4c430;
}

.teacher-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.teacher-panel {
    padding: 1.35rem;
    margin-bottom: 1.2rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16,24,40,.06);
}

.teacher-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.teacher-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.teacher-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
    line-height: 1.6;
}

.teacher-alert {
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 850;
    line-height: 1.55;
}

.teacher-alert.success {
    background: #ecfdf3;
    border: 1px solid #abefc6;
    color: #027a48;
}

.teacher-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.teacher-form-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 1rem;
}

.teacher-form-group {
    display: grid;
    gap: .4rem;
}

.teacher-form-group.full {
    grid-column: 1 / -1;
}

.teacher-form-group label {
    color: #344054;
    font-weight: 900;
}

.teacher-input,
.teacher-textarea,
.teacher-select {
    width: 100%;
    border: 1px solid #d0d8e8;
    border-radius: 15px;
    padding: .9rem 1rem;
    font: inherit;
    outline: none;
    background: #fff;
    color: #101828;
    transition: .2s ease;
}

.teacher-textarea {
    min-height: 110px;
    resize: vertical;
}

.teacher-input:focus,
.teacher-textarea:focus,
.teacher-select:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.teacher-form-actions {
    display: flex;
    gap: .7rem;
    flex-wrap: wrap;
    justify-content: flex-end;
    margin-top: 1rem;
}

.teacher-events-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem;
}

.teacher-event-card {
    display: flex;
    flex-direction: column;
    padding: 1.1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.teacher-event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23,63,132,.08);
    border-color: #d7e5ff;
}

.teacher-event-card h3 {
    margin: 0 0 .45rem;
    color: #101828;
    line-height: 1.3;
}

.teacher-event-card p {
    margin: 0;
    color: #667085;
    line-height: 1.6;
}

.teacher-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .45rem;
    margin-bottom: .7rem;
}

.teacher-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem .65rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-size: .84rem;
    font-weight: 900;
}

.teacher-event-actions {
    display: flex;
    gap: .6rem;
    flex-wrap: wrap;
    margin-top: auto;
    padding-top: 1rem;
}

.teacher-delete-btn {
    border: none;
    cursor: pointer;
    background: #fff0f0;
    color: #b42318;
}

.teacher-delete-btn:hover {
    background: #ffdede;
}

.teacher-empty {
    padding: 1.4rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    line-height: 1.6;
}

@media (max-width: 900px) {
    .teacher-activities-hero {
        grid-template-columns: 1fr;
    }

    .teacher-panel-head {
        flex-direction: column;
    }

    .teacher-form-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .teacher-activities-page {
        padding: 1.5rem 0 2.5rem;
    }

    .teacher-activities-hero,
    .teacher-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .teacher-event-actions .btn,
    .teacher-event-actions button,
    .teacher-form-actions .btn {
        width: 100%;
    }
}
</style>

<main class="teacher-activities-page">
    <div class="container">

        <section class="teacher-activities-hero">
            <div>
                <div class="teacher-kicker">
                    <i class="fas fa-calendar-days"></i>
                    Skolotāja pasākumi
                </div>

                <h1>Pasākumi</h1>

                <p>
                    Šeit skolotājs var apskatīt, pievienot un pārvaldīt pasākumus.
                    Ja datubāzē ir <strong>club_id</strong> lauks, pasākumi tiek filtrēti pēc skolotāja kluba.
                </p>
            </div>

            <aside class="teacher-hero-card">
                <strong><?= count($events); ?></strong>
                <span>Aktīvi pasākumi</span>
            </aside>
        </section>

        <?php if ($success): ?>
            <div class="teacher-alert success"><?= htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="teacher-alert error"><?= htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <section class="teacher-panel">
            <div class="teacher-panel-head">
                <div>
                    <h2>Pievienot pasākumu</h2>
                    <p>Izveido jaunu pasākumu vai aktivitāti.</p>
                </div>

                <a href="../dashboards/teacher.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-arrow-left"></i>
                    Atpakaļ uz paneli
                </a>
            </div>

            <form method="post">
                <input type="hidden" name="action" value="add">

                <div class="teacher-form-grid">
                    <div class="teacher-form-group">
                        <label for="title">Nosaukums *</label>
                        <input class="teacher-input" type="text" name="title" id="title" required>
                    </div>

                    <div class="teacher-form-group">
                        <label for="event_type">Veids</label>
                        <select class="teacher-select" name="event_type" id="event_type">
                            <option value="nodarbība">Nodarbība</option>
                            <option value="pārgājiens">Pārgājiens</option>
                            <option value="nometne">Nometne</option>
                            <option value="pasākums">Pasākums</option>
                            <option value="cits">Cits</option>
                        </select>
                    </div>

                    <div class="teacher-form-group">
                        <label for="start_date">Sākuma datums *</label>
                        <input class="teacher-input" type="date" name="start_date" id="start_date" required>
                    </div>

                    <div class="teacher-form-group">
                        <label for="end_date">Beigu datums</label>
                        <input class="teacher-input" type="date" name="end_date" id="end_date">
                    </div>

                    <div class="teacher-form-group">
                        <label for="start_time">Sākuma laiks</label>
                        <input class="teacher-input" type="time" name="start_time" id="start_time">
                    </div>

                    <div class="teacher-form-group">
                        <label for="end_time">Beigu laiks</label>
                        <input class="teacher-input" type="time" name="end_time" id="end_time">
                    </div>

                    <div class="teacher-form-group">
                        <label for="location">Vieta *</label>
                        <input class="teacher-input" type="text" name="location" id="location" required>
                    </div>

                    <?php if ($hasMaxParticipants): ?>
                        <div class="teacher-form-group">
                            <label for="max_participants">Maks. dalībnieki</label>
                            <input class="teacher-input" type="number" min="0" name="max_participants" id="max_participants">
                        </div>
                    <?php endif; ?>

                    <div class="teacher-form-group full">
                        <label for="description">Apraksts</label>
                        <textarea class="teacher-textarea" name="description" id="description"></textarea>
                    </div>
                </div>

                <div class="teacher-form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i>
                        Pievienot pasākumu
                    </button>
                </div>
            </form>
        </section>

        <section class="teacher-panel">
            <div class="teacher-panel-head">
                <div>
                    <h2>Pasākumu saraksts</h2>
                    <p>Visi aktīvie pasākumi.</p>
                </div>
            </div>

            <?php if (empty($events)): ?>

                <div class="teacher-empty">
                    Pašlaik nav aktīvu pasākumu.
                </div>

            <?php else: ?>

                <div class="teacher-events-grid">
                    <?php foreach ($events as $event): ?>
                        <article class="teacher-event-card">
                            <div class="teacher-meta">
                                <span class="teacher-pill">
                                    <i class="fas fa-tag"></i>
                                    <?= htmlspecialchars($event["event_type"] ?? "Pasākums"); ?>
                                </span>

                                <span class="teacher-pill">
                                    <i class="fas fa-calendar-day"></i>
                                    <?= htmlspecialchars(formatDateLv($event["start_date"] ?? null)); ?>
                                </span>

                                <?php if (!empty($event["start_time"])): ?>
                                    <span class="teacher-pill">
                                        <i class="fas fa-clock"></i>
                                        <?= htmlspecialchars(formatTimeLv($event["start_time"])); ?>
                                    </span>
                                <?php endif; ?>

                                <span class="teacher-pill">
                                    <i class="fas fa-users"></i>
                                    <?= (int)($event["applications_count"] ?? 0); ?> pieteikumi
                                </span>
                            </div>

                            <h3><?= htmlspecialchars($event["title"] ?? "Bez nosaukuma"); ?></h3>

                            <p>
                                <strong>Vieta:</strong>
                                <?= htmlspecialchars($event["location"] ?? "Nav norādīta"); ?>
                            </p>

                            <?php if (!empty($event["description"])): ?>
                                <p>
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($event["description"], 0, 180, "..."))); ?>
                                </p>
                            <?php endif; ?>

                            <div class="teacher-event-actions">
                                <a class="btn btn-outline btn-sm" href="applications.php?event_id=<?= (int)$event["id"]; ?>">
                                    <i class="fas fa-file-signature"></i>
                                    Pieteikumi
                                </a>

                                <?php if (!$hasCreatedBy || (int)($event["created_by"] ?? 0) === $teacherId): ?>
                                    <form method="post" onsubmit="return confirm('Tiešām deaktivizēt/dzēst šo pasākumu?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="event_id" value="<?= (int)$event["id"]; ?>">

                                        <button type="submit" class="btn teacher-delete-btn">
                                            <i class="fas fa-trash"></i>
                                            Dzēst
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>

            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
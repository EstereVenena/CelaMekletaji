<?php
session_start();

$lapa  = "Aktivitātes";
$title = "Aktivitātes - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   PIEKĻUVES PĀRBAUDE
================================ */
$allowedRoles = ["Direktors", "direktors"];

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), $allowedRoles, true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$directorId = (int)($_SESSION["lietotajs_id"] ?? 0);
$directorClubId = (int)($_SESSION["club_id"] ?? 0);

$activities = [];
$error = trim($_GET["error"] ?? "");
$success = trim($_GET["success"] ?? "");

$form = [
    "title" => "",
    "description" => "",
    "start_date" => "",
    "end_date" => "",
    "start_time" => "",
    "end_time" => "",
    "location" => "",
    "event_type" => "pasākums",
    "max_participants" => "",
];

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: activities.php?" . $param . "=" . urlencode($message));
    exit();
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

    return (int)($row["total"] ?? 0) > 0;
}

function tableColumnExists(mysqli $db, string $table, string $column): bool
{
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
          AND COLUMN_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("ss", $table, $column);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int)($row["total"] ?? 0) > 0;
}

function formatDateRangeLv(?string $startDate, ?string $endDate): string
{
    if (empty($startDate) || $startDate === "0000-00-00") {
        return "—";
    }

    $start = date("d.m.Y", strtotime($startDate));

    if (!empty($endDate) && $endDate !== "0000-00-00" && $endDate !== $startDate) {
        return $start . " - " . date("d.m.Y", strtotime($endDate));
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
        return $start . " - " . substr($endTime, 0, 5);
    }

    return $start;
}

function shortText(?string $text, int $limit = 180): string
{
    $text = trim($text ?? "");

    if ($text === "") {
        return "Apraksts nav pievienots.";
    }

    return mb_strimwidth($text, 0, $limit, "...");
}

/* ===============================
   TABULU / KOLONNU PĀRBAUDE
================================ */
$eventsTableExists = tableExists($savienojums, "cm_events");

$hasClubId = $eventsTableExists && tableColumnExists($savienojums, "cm_events", "club_id");
$hasMaxParticipants = $eventsTableExists && tableColumnExists($savienojums, "cm_events", "max_participants");
$hasCreatedBy = $eventsTableExists && tableColumnExists($savienojums, "cm_events", "created_by");

if (!$eventsTableExists) {
    $error = "Tabula cm_events vēl nav izveidota.";
}

if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs. Pārbaudi lietotāja club_id datubāzē.";
}

/* ===============================
   AKTIVITĀTES STATUSA MAIŅA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "toggle_status" && empty($error)) {
    $activityId = (int)($_POST["activity_id"] ?? 0);

    if ($activityId <= 0) {
        redirectWithMessage("error", "Nederīgs aktivitātes ID.");
    }

    if ($hasClubId) {
        $sql = "
            UPDATE cm_events
            SET is_active = IF(is_active = 1, 0, 1)
            WHERE id = ?
              AND club_id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ii", $activityId, $directorClubId);
        }
    } else {
        $sql = "
            UPDATE cm_events
            SET is_active = IF(is_active = 1, 0, 1)
            WHERE id = ?
              AND created_by = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ii", $activityId, $directorId);
        }
    }

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot statusa maiņu.");
    }

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage("success", "Aktivitātes statuss mainīts.");
    }

    $stmt->close();
    redirectWithMessage("error", "Neizdevās mainīt aktivitātes statusu.");
}

/* ===============================
   JAUNAS AKTIVITĀTES PIEVIENOŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "create" && empty($error)) {
    $title = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $startDate = trim($_POST["start_date"] ?? "");
    $endDate = trim($_POST["end_date"] ?? "");
    $startTime = trim($_POST["start_time"] ?? "");
    $endTime = trim($_POST["end_time"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $eventType = trim($_POST["event_type"] ?? "pasākums");
    $maxParticipantsRaw = trim($_POST["max_participants"] ?? "");

    $form = [
        "title" => $title,
        "description" => $description,
        "start_date" => $startDate,
        "end_date" => $endDate,
        "start_time" => $startTime,
        "end_time" => $endTime,
        "location" => $location,
        "event_type" => $eventType,
        "max_participants" => $maxParticipantsRaw,
    ];

    $allowedTypes = [
        "nometne",
        "nodarbība",
        "pārgājiens",
        "pasākums",
        "cits",
    ];

    if ($title === "" || $startDate === "") {
        $error = "Aizpildi aktivitātes nosaukumu un sākuma datumu.";
    } elseif (!in_array($eventType, $allowedTypes, true)) {
        $error = "Izvēlēts nederīgs aktivitātes tips.";
    } elseif ($endDate !== "" && strtotime($endDate) < strtotime($startDate)) {
        $error = "Beigu datums nevar būt pirms sākuma datuma.";
    }

    $maxParticipants = null;

    if ($maxParticipantsRaw !== "") {
        $maxParticipants = (int)$maxParticipantsRaw;

        if ($maxParticipants < 1) {
            $error = "Maksimālajam dalībnieku skaitam jābūt vismaz 1.";
        }
    }

    if (empty($error)) {
        $columns = [
            "title",
            "description",
            "start_date",
            "end_date",
            "start_time",
            "end_time",
            "location",
            "event_type",
            "created_by",
            "is_active",
        ];

        $placeholders = [
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "?",
            "1",
        ];

        $values = [
            $title,
            $description,
            $startDate,
            $endDate !== "" ? $endDate : null,
            $startTime !== "" ? $startTime : null,
            $endTime !== "" ? $endTime : null,
            $location,
            $eventType,
            $directorId,
        ];

        $types = "ssssssssi";

        if ($hasClubId) {
            $columns[] = "club_id";
            $placeholders[] = "?";
            $values[] = $directorClubId;
            $types .= "i";
        }

        if ($hasMaxParticipants) {
            $columns[] = "max_participants";
            $placeholders[] = "?";
            $values[] = $maxParticipants;
            $types .= "i";
        }

        $sql = "
            INSERT INTO cm_events
                (" . implode(", ", $columns) . ")
            VALUES
                (" . implode(", ", $placeholders) . ")
        ";

        $stmt = $savienojums->prepare($sql);

        if (!$stmt) {
            $error = "Neizdevās sagatavot aktivitātes pievienošanu.";
        } else {
            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                $stmt->close();
                redirectWithMessage("success", "Aktivitāte veiksmīgi pievienota.");
            }

            $stmt->close();
            $error = "Neizdevās pievienot aktivitāti.";
        }
    }
}

/* ===============================
   AKTIVITĀŠU SARAKSTS
================================ */
if ($eventsTableExists && empty($error)) {
    $where = "WHERE 1";

    $params = [];
    $types = "";

    if ($hasClubId) {
        $where .= " AND e.club_id = ?";
        $params[] = $directorClubId;
        $types .= "i";
    } elseif ($hasCreatedBy) {
        $where .= " AND e.created_by = ?";
        $params[] = $directorId;
        $types .= "i";
    }

    $maxSelect = $hasMaxParticipants ? "e.max_participants" : "NULL AS max_participants";

    $sql = "
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
            e.created_by,
            e.is_active,
            e.created_at,
            $maxSelect
        FROM cm_events e
        $where
        ORDER BY e.start_date DESC, e.start_time DESC
    ";

    $stmt = $savienojums->prepare($sql);

    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }

        $stmt->close();
    } else {
        $error = "Neizdevās ielādēt aktivitātes.";
    }
}

$activitiesCount = count($activities);

require __DIR__ . "/../includes/templates/header-director.php";
?>

<style>
.director-activities-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-activities-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1.25fr .75fr;
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

.director-activities-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-activities-hero > * {
    position: relative;
    z-index: 1;
}

.director-activities-kicker {
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

.director-activities-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-activities-hero p {
    max-width: 740px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.director-activities-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.director-activities-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.director-activities-hero-card strong {
    display: block;
    font-size: 2.2rem;
    color: #f4c430;
    line-height: 1;
}

.director-activities-hero-card span {
    display: block;
    margin-top: .55rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.director-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 800;
}

.director-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.director-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.director-activities-layout {
    display: grid;
    grid-template-columns: .9fr 1.1fr;
    gap: 1.1rem;
    align-items: start;
}

.director-card {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.director-card h2 {
    margin: 0 0 .35rem;
    color: #173f84;
    font-size: 1.35rem;
}

.director-muted {
    margin: 0;
    color: #667085;
    line-height: 1.6;
}

.director-form {
    display: grid;
    gap: 1rem;
    margin-top: 1.2rem;
}

.director-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .9rem;
}

.director-form-group {
    display: grid;
    gap: .4rem;
}

.director-form-group label {
    color: #344054;
    font-weight: 900;
}

.director-input,
.director-select,
.director-textarea {
    width: 100%;
    padding: .86rem .95rem;
    border-radius: 14px;
    border: 1px solid #d0d8e8;
    background: #fff;
    color: #101828;
    font: inherit;
    outline: none;
    transition: .2s ease;
}

.director-textarea {
    min-height: 120px;
    resize: vertical;
}

.director-input:focus,
.director-select:focus,
.director-textarea:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.director-form-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
}

.director-activity-list {
    display: grid;
    gap: .9rem;
    margin-top: 1rem;
}

.director-activity-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    padding: 1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.director-activity-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.director-activity-card h3 {
    margin: 0 0 .45rem;
    color: #101828;
    font-size: 1.08rem;
}

.director-activity-card p {
    margin: .55rem 0 0;
    color: #667085;
    line-height: 1.55;
}

.director-activity-meta {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
}

.director-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .34rem .65rem;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #edf2fb;
    color: #667085;
    font-size: .84rem;
    font-weight: 850;
}

.director-pill.type {
    background: #eef3ff;
    color: #173f84;
}

.director-pill.active {
    background: #ecfff4;
    color: #17633a;
}

.director-pill.inactive {
    background: #fff8e6;
    color: #7a5517;
}

.director-activity-side {
    width: 170px;
    display: grid;
    gap: .55rem;
    align-content: start;
}

.director-activity-side .btn {
    width: 100%;
    justify-content: center;
}

.director-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.director-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

@media (max-width: 980px) {
    .director-activities-hero,
    .director-activities-layout,
    .director-activity-card {
        grid-template-columns: 1fr;
    }

    .director-activity-side {
        width: 100%;
    }
}

@media (max-width: 640px) {
    .director-activities-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-activities-hero,
    .director-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-form-grid {
        grid-template-columns: 1fr;
    }

    .director-activities-actions .btn,
    .director-form-actions .btn {
        width: 100%;
    }
}
</style>

<main class="director-activities-page">
    <div class="container">

        <section class="director-activities-hero">
            <div>
                <div class="director-activities-kicker">
                    <i class="fas fa-calendar-days"></i>
                    Kluba aktivitātes
                </div>

                <h1>Aktivitātes</h1>

                <p>
                    Pievieno un pārskati sava kluba pasākumus, nodarbības, pārgājienus un nometnes.
                    Šīs aktivitātes pēc tam varēs izmantot pieteikumu sadaļā.
                </p>

                <div class="director-activities-actions">
                    <a class="btn btn-primary btn-sm" href="applications.php">
                        <i class="fas fa-file-signature"></i>
                        Pieteikumi
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/director.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="director-activities-hero-card">
                <strong><?= (int)$activitiesCount; ?></strong>
                <span>Aktivitātes šajā skatā.</span>
            </aside>
        </section>

        <?php if (!empty($success)): ?>
            <div class="director-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="director-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="director-activities-layout">

            <article class="director-card">
                <h2>Pievienot aktivitāti</h2>
                <p class="director-muted">
                    Izveido jaunu aktivitāti savam klubam.
                </p>

                <form method="post" class="director-form">
                    <input type="hidden" name="action" value="create">

                    <div class="director-form-group">
                        <label for="title">Nosaukums</label>
                        <input
                            class="director-input"
                            type="text"
                            id="title"
                            name="title"
                            value="<?= htmlspecialchars($form["title"]); ?>"
                            required
                        >
                    </div>

                    <div class="director-form-group">
                        <label for="description">Apraksts</label>
                        <textarea
                            class="director-textarea"
                            id="description"
                            name="description"
                            placeholder="Īss aktivitātes apraksts..."
                        ><?= htmlspecialchars($form["description"]); ?></textarea>
                    </div>

                    <div class="director-form-grid">
                        <div class="director-form-group">
                            <label for="start_date">Sākuma datums</label>
                            <input
                                class="director-input"
                                type="date"
                                id="start_date"
                                name="start_date"
                                value="<?= htmlspecialchars($form["start_date"]); ?>"
                                required
                            >
                        </div>

                        <div class="director-form-group">
                            <label for="end_date">Beigu datums</label>
                            <input
                                class="director-input"
                                type="date"
                                id="end_date"
                                name="end_date"
                                value="<?= htmlspecialchars($form["end_date"]); ?>"
                            >
                        </div>
                    </div>

                    <div class="director-form-grid">
                        <div class="director-form-group">
                            <label for="start_time">Sākuma laiks</label>
                            <input
                                class="director-input"
                                type="time"
                                id="start_time"
                                name="start_time"
                                value="<?= htmlspecialchars($form["start_time"]); ?>"
                            >
                        </div>

                        <div class="director-form-group">
                            <label for="end_time">Beigu laiks</label>
                            <input
                                class="director-input"
                                type="time"
                                id="end_time"
                                name="end_time"
                                value="<?= htmlspecialchars($form["end_time"]); ?>"
                            >
                        </div>
                    </div>

                    <div class="director-form-grid">
                        <div class="director-form-group">
                            <label for="event_type">Tips</label>
                            <select class="director-select" id="event_type" name="event_type">
                                <?php
                                    $types = ["pasākums", "nodarbība", "pārgājiens", "nometne", "cits"];
                                ?>

                                <?php foreach ($types as $type): ?>
                                    <option value="<?= htmlspecialchars($type); ?>" <?= $form["event_type"] === $type ? "selected" : ""; ?>>
                                        <?= htmlspecialchars(ucfirst($type)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="director-form-group">
                            <label for="max_participants">Maks. dalībnieki</label>
                            <input
                                class="director-input"
                                type="number"
                                min="1"
                                id="max_participants"
                                name="max_participants"
                                value="<?= htmlspecialchars($form["max_participants"]); ?>"
                                placeholder="Nav limita"
                            >
                        </div>
                    </div>

                    <div class="director-form-group">
                        <label for="location">Vieta</label>
                        <input
                            class="director-input"
                            type="text"
                            id="location"
                            name="location"
                            value="<?= htmlspecialchars($form["location"]); ?>"
                            placeholder="Piemēram: Grobiņa, baznīca, parks..."
                        >
                    </div>

                    <div class="director-form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i>
                            Saglabāt
                        </button>

                        <a href="activities.php" class="btn btn-outline">
                            Notīrīt
                        </a>
                    </div>
                </form>
            </article>

            <aside class="director-card">
                <h2>Aktivitāšu saraksts</h2>
                <p class="director-muted">
                    Pēdējās pievienotās aktivitātes ir augšpusē.
                </p>

                <?php if (!empty($activities)): ?>
                    <div class="director-activity-list">
                        <?php foreach ($activities as $activity): ?>
                            <?php
                                $isActive = ((int)($activity["is_active"] ?? 0) === 1);
                                $max = $activity["max_participants"] ?? null;
                            ?>

                            <article class="director-activity-card">
                                <div>
                                    <h3><?= htmlspecialchars($activity["title"] ?? "Bez nosaukuma"); ?></h3>

                                    <div class="director-activity-meta">
                                        <span class="director-pill type">
                                            <i class="fas fa-tag"></i>
                                            <?= htmlspecialchars($activity["event_type"] ?? "pasākums"); ?>
                                        </span>

                                        <span class="director-pill">
                                            <i class="fas fa-calendar-day"></i>
                                            <?= htmlspecialchars(formatDateRangeLv($activity["start_date"] ?? null, $activity["end_date"] ?? null)); ?>
                                        </span>

                                        <span class="director-pill">
                                            <i class="fas fa-clock"></i>
                                            <?= htmlspecialchars(formatTimeRangeLv($activity["start_time"] ?? null, $activity["end_time"] ?? null)); ?>
                                        </span>

                                        <span class="director-pill <?= $isActive ? 'active' : 'inactive'; ?>">
                                            <i class="fas <?= $isActive ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                                            <?= $isActive ? "Aktīva" : "Neaktīva"; ?>
                                        </span>

                                        <?php if (!empty($max)): ?>
                                            <span class="director-pill">
                                                <i class="fas fa-users"></i>
                                                Max: <?= (int)$max; ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>

                                    <p><?= htmlspecialchars(shortText($activity["description"] ?? "")); ?></p>

                                    <?php if (!empty($activity["location"])): ?>
                                        <p>
                                            <i class="fas fa-location-dot"></i>
                                            <?= htmlspecialchars($activity["location"]); ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="director-activity-side">
                                    <form method="post">
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="activity_id" value="<?= (int)$activity["id"]; ?>">

                                        <button type="submit" class="btn btn-outline btn-sm">
                                            <?= $isActive ? "Deaktivizēt" : "Aktivizēt"; ?>
                                        </button>
                                    </form>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="director-empty">
                        <h3>Nav pievienotu aktivitāšu</h3>
                        <p>Izveido pirmo aktivitāti, izmantojot formu kreisajā pusē.</p>
                    </div>
                <?php endif; ?>
            </aside>

        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
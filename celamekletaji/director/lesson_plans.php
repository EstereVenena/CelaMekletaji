<?php
session_start();

$lapa  = "Nodarbību plāni";
$title = "Nodarbību plāni - Ceļa meklētāji";

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

$lessons = [];
$error = trim($_GET["error"] ?? "");
$success = trim($_GET["success"] ?? "");

$form = [
    "title" => "",
    "age_group" => "",
    "topic" => "",
    "description" => "",
    "lesson_date" => "",
    "lesson_time" => "",
    "location" => "",
];

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: lesson_plans.php?" . $param . "=" . urlencode($message));
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

function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

function formatDateTimeLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00 00:00:00") {
        return "—";
    }

    return date("d.m.Y H:i", strtotime($date));
}

function formatTimeLv(?string $time): string
{
    if (empty($time)) {
        return "—";
    }

    return substr($time, 0, 5);
}

function shortText(?string $text, int $limit = 180): string
{
    $text = trim($text ?? "");

    if ($text === "") {
        return "Apraksts nav pievienots.";
    }

    if (function_exists("mb_strimwidth")) {
        return mb_strimwidth($text, 0, $limit, "...");
    }

    return strlen($text) > $limit
        ? substr($text, 0, $limit) . "..."
        : $text;
}

/* ===============================
   TABULU / KOLONNU PĀRBAUDE
================================ */
$lessonsTableExists = tableExists($savienojums, "cm_lessons");

$hasTitle       = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "title");
$hasAgeGroup    = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "age_group");
$hasTopic       = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "topic");
$hasDescription = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "description");
$hasLessonDate  = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "lesson_date");
$hasLessonTime  = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "lesson_time");
$hasLocation    = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "location");
$hasCreatedBy   = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "created_by");
$hasClubId      = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "club_id");
$hasIsActive    = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "is_active");
$hasCreatedAt   = $lessonsTableExists && tableColumnExists($savienojums, "cm_lessons", "created_at");

if (!$lessonsTableExists) {
    $error = "Tabula cm_lessons vēl nav izveidota.";
}

if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs. Pārbaudi lietotāja club_id datubāzē.";
}

/* ===============================
   STATUSA MAIŅA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "toggle_status" && empty($error)) {
    $lessonId = (int)($_POST["lesson_id"] ?? 0);

    if (!$hasIsActive) {
        redirectWithMessage("error", "Tabulā cm_lessons nav is_active kolonnas.");
    }

    if ($lessonId <= 0) {
        redirectWithMessage("error", "Nederīgs nodarbības ID.");
    }

    $stmt = null;

    if ($hasClubId) {
        $sql = "
            UPDATE cm_lessons
            SET is_active = IF(is_active = 1, 0, 1)
            WHERE id = ?
              AND club_id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ii", $lessonId, $directorClubId);
        }
    } elseif ($hasCreatedBy) {
        $sql = "
            UPDATE cm_lessons
            SET is_active = IF(is_active = 1, 0, 1)
            WHERE id = ?
              AND created_by = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("ii", $lessonId, $directorId);
        }
    } else {
        $sql = "
            UPDATE cm_lessons
            SET is_active = IF(is_active = 1, 0, 1)
            WHERE id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("i", $lessonId);
        }
    }

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot nodarbības statusa maiņu.");
    }

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage("success", "Nodarbības statuss mainīts.");
    }

    $stmt->close();
    redirectWithMessage("error", "Neizdevās mainīt nodarbības statusu.");
}

/* ===============================
   JAUNAS NODARBĪBAS PIEVIENOŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "create" && empty($error)) {
    $title = trim($_POST["title"] ?? "");
    $ageGroup = trim($_POST["age_group"] ?? "");
    $topic = trim($_POST["topic"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $lessonDate = trim($_POST["lesson_date"] ?? "");
    $lessonTime = trim($_POST["lesson_time"] ?? "");
    $location = trim($_POST["location"] ?? "");

    $form = [
        "title" => $title,
        "age_group" => $ageGroup,
        "topic" => $topic,
        "description" => $description,
        "lesson_date" => $lessonDate,
        "lesson_time" => $lessonTime,
        "location" => $location,
    ];

    if ($title === "" || $lessonDate === "") {
        $error = "Aizpildi nodarbības nosaukumu un datumu.";
    }

    if (empty($error)) {
        $columns = [];
        $placeholders = [];
        $values = [];
        $types = "";

        if ($hasTitle) {
            $columns[] = "title";
            $placeholders[] = "?";
            $values[] = $title;
            $types .= "s";
        }

        if ($hasAgeGroup) {
            $columns[] = "age_group";
            $placeholders[] = "?";
            $values[] = $ageGroup;
            $types .= "s";
        }

        if ($hasTopic) {
            $columns[] = "topic";
            $placeholders[] = "?";
            $values[] = $topic;
            $types .= "s";
        }

        if ($hasDescription) {
            $columns[] = "description";
            $placeholders[] = "?";
            $values[] = $description;
            $types .= "s";
        }

        if ($hasLessonDate) {
            $columns[] = "lesson_date";
            $placeholders[] = "?";
            $values[] = $lessonDate;
            $types .= "s";
        }

        if ($hasLessonTime) {
            $columns[] = "lesson_time";
            $placeholders[] = "?";
            $values[] = $lessonTime !== "" ? $lessonTime : null;
            $types .= "s";
        }

        if ($hasLocation) {
            $columns[] = "location";
            $placeholders[] = "?";
            $values[] = $location;
            $types .= "s";
        }

        if ($hasCreatedBy) {
            $columns[] = "created_by";
            $placeholders[] = "?";
            $values[] = $directorId;
            $types .= "i";
        }

        if ($hasClubId) {
            $columns[] = "club_id";
            $placeholders[] = "?";
            $values[] = $directorClubId;
            $types .= "i";
        }

        if ($hasIsActive) {
            $columns[] = "is_active";
            $placeholders[] = "1";
        }

        if ($hasCreatedAt) {
            $columns[] = "created_at";
            $placeholders[] = "NOW()";
        }

        if (empty($columns)) {
            $error = "Tabulā cm_lessons nav atbilstošu kolonnu ievadei.";
        } else {
            $sql = "
                INSERT INTO cm_lessons
                    (" . implode(", ", $columns) . ")
                VALUES
                    (" . implode(", ", $placeholders) . ")
            ";

            $stmt = $savienojums->prepare($sql);

            if (!$stmt) {
                $error = "Neizdevās sagatavot nodarbības pievienošanu.";
            } else {
                if (!empty($values)) {
                    $stmt->bind_param($types, ...$values);
                }

                if ($stmt->execute()) {
                    $stmt->close();
                    redirectWithMessage("success", "Nodarbība veiksmīgi pievienota.");
                }

                $stmt->close();
                $error = "Neizdevās pievienot nodarbību.";
            }
        }
    }
}

/* ===============================
   NODARBĪBU SARAKSTS
================================ */
if ($lessonsTableExists && empty($error)) {
    $selectParts = [
        "l.id",
        $hasTitle ? "l.title" : "NULL AS title",
        $hasAgeGroup ? "l.age_group" : "NULL AS age_group",
        $hasTopic ? "l.topic" : "NULL AS topic",
        $hasDescription ? "l.description" : "NULL AS description",
        $hasLessonDate ? "l.lesson_date" : "NULL AS lesson_date",
        $hasLessonTime ? "l.lesson_time" : "NULL AS lesson_time",
        $hasLocation ? "l.location" : "NULL AS location",
        $hasClubId ? "l.club_id" : "NULL AS club_id",
        $hasIsActive ? "l.is_active" : "1 AS is_active",
        $hasCreatedAt ? "l.created_at" : "NULL AS created_at",
    ];

    $where = "WHERE 1";
    $params = [];
    $types = "";

    if ($hasClubId) {
        $where .= " AND l.club_id = ?";
        $params[] = $directorClubId;
        $types .= "i";
    } elseif ($hasCreatedBy) {
        $where .= " AND l.created_by = ?";
        $params[] = $directorId;
        $types .= "i";
    }

    $orderBy = $hasCreatedAt
        ? "ORDER BY l.created_at DESC, l.id DESC"
        : ($hasLessonDate ? "ORDER BY l.lesson_date DESC, l.lesson_time DESC" : "ORDER BY l.id DESC");

    $sql = "
        SELECT " . implode(", ", $selectParts) . "
        FROM cm_lessons l
        $where
        $orderBy
    ";

    $stmt = $savienojums->prepare($sql);

    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $lessons[] = $row;
        }

        $stmt->close();
    } else {
        $error = "Neizdevās ielādēt nodarbību plānus.";
    }
}

$lessonsCount = count($lessons);

require __DIR__ . "/../includes/templates/header-director.php";
?>

<style>
.director-lessons-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-lessons-hero {
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

.director-lessons-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-lessons-hero > * {
    position: relative;
    z-index: 1;
}

.director-lessons-kicker {
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

.director-lessons-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-lessons-hero p {
    max-width: 740px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.director-lessons-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.director-lessons-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.director-lessons-hero-card strong {
    display: block;
    font-size: 2.2rem;
    color: #f4c430;
    line-height: 1;
}

.director-lessons-hero-card span {
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

.director-lessons-layout {
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
.director-textarea:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.director-form-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
}

.director-lesson-list {
    display: grid;
    gap: .9rem;
    margin-top: 1rem;
}

.director-lesson-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    padding: 1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.director-lesson-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.director-lesson-card h3 {
    margin: 0 0 .45rem;
    color: #101828;
    font-size: 1.08rem;
}

.director-lesson-card p {
    margin: .55rem 0 0;
    color: #667085;
    line-height: 1.55;
}

.director-lesson-meta {
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

.director-pill.date {
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

.director-pill.topic {
    background: #fff8e6;
    color: #7a5517;
}

.director-pill.age {
    background: #f2f4f7;
    color: #344054;
}

.director-pill.created {
    background: #eef3ff;
    color: #173f84;
}

.director-lesson-side {
    width: 170px;
    display: grid;
    gap: .55rem;
    align-content: start;
}

.director-lesson-side .btn {
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
    .director-lessons-hero,
    .director-lessons-layout,
    .director-lesson-card {
        grid-template-columns: 1fr;
    }

    .director-lesson-side {
        width: 100%;
    }
}

@media (max-width: 640px) {
    .director-lessons-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-lessons-hero,
    .director-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-form-grid {
        grid-template-columns: 1fr;
    }

    .director-lessons-actions .btn,
    .director-form-actions .btn {
        width: 100%;
    }
}
</style>

<main class="director-lessons-page">
    <div class="container">

        <section class="director-lessons-hero">
            <div>
                <div class="director-lessons-kicker">
                    <i class="fas fa-clipboard-list"></i>
                    Kluba nodarbības
                </div>

                <h1>Nodarbību plāni</h1>

                <p>
                    Pievieno un pārskati sava kluba nodarbību plānus.
                    Nodarbībām vari norādīt vecuma grupu, tematu, aprakstu, datumu, laiku un vietu.
                </p>

                <div class="director-lessons-actions">
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

            <aside class="director-lessons-hero-card">
                <strong><?= (int)$lessonsCount; ?></strong>
                <span>Nodarbības šajā skatā.</span>
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

        <section class="director-lessons-layout">

            <article class="director-card">
                <h2>Pievienot nodarbību</h2>
                <p class="director-muted">
                    Izveido jaunu nodarbības plānu savam klubam.
                </p>

                <form method="post" class="director-form">
                    <input type="hidden" name="action" value="create">

                    <div class="director-form-group">
                        <label for="title">Nodarbības nosaukums</label>
                        <input
                            class="director-input"
                            type="text"
                            id="title"
                            name="title"
                            value="<?= htmlspecialchars($form["title"]); ?>"
                            required
                        >
                    </div>

                    <div class="director-form-grid">
                        <div class="director-form-group">
                            <label for="age_group">Vecuma grupa</label>
                            <input
                                class="director-input"
                                type="text"
                                id="age_group"
                                name="age_group"
                                value="<?= htmlspecialchars($form["age_group"]); ?>"
                                placeholder="Piemēram: 10–12 gadi"
                            >
                        </div>

                        <div class="director-form-group">
                            <label for="topic">Temats</label>
                            <input
                                class="director-input"
                                type="text"
                                id="topic"
                                name="topic"
                                value="<?= htmlspecialchars($form["topic"]); ?>"
                                placeholder="Piemēram: Uzticība Dievam"
                            >
                        </div>
                    </div>

                    <div class="director-form-group">
                        <label for="description">Apraksts / plāns</label>
                        <textarea
                            class="director-textarea"
                            id="description"
                            name="description"
                            placeholder="Īss nodarbības apraksts vai plāns..."
                        ><?= htmlspecialchars($form["description"]); ?></textarea>
                    </div>

                    <div class="director-form-grid">
                        <div class="director-form-group">
                            <label for="lesson_date">Datums</label>
                            <input
                                class="director-input"
                                type="date"
                                id="lesson_date"
                                name="lesson_date"
                                value="<?= htmlspecialchars($form["lesson_date"]); ?>"
                                required
                            >
                        </div>

                        <div class="director-form-group">
                            <label for="lesson_time">Laiks</label>
                            <input
                                class="director-input"
                                type="time"
                                id="lesson_time"
                                name="lesson_time"
                                value="<?= htmlspecialchars($form["lesson_time"]); ?>"
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
                            placeholder="Piemēram: kluba telpa, baznīca..."
                        >
                    </div>

                    <div class="director-form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i>
                            Saglabāt
                        </button>

                        <a href="lesson_plans.php" class="btn btn-outline">
                            Notīrīt
                        </a>
                    </div>
                </form>
            </article>

            <aside class="director-card">
                <h2>Nodarbību saraksts</h2>
                <p class="director-muted">
                    Pēdējās pievienotās nodarbības ir augšpusē.
                </p>

                <?php if (!empty($lessons)): ?>
                    <div class="director-lesson-list">
                        <?php foreach ($lessons as $lesson): ?>
                            <?php
                                $isActive = ((int)($lesson["is_active"] ?? 1) === 1);
                            ?>

                            <article class="director-lesson-card">
                                <div>
                                    <h3><?= htmlspecialchars($lesson["title"] ?? "Bez nosaukuma"); ?></h3>

                                    <div class="director-lesson-meta">
                                        <?php if (!empty($lesson["age_group"])): ?>
                                            <span class="director-pill age">
                                                <i class="fas fa-child"></i>
                                                <?= htmlspecialchars($lesson["age_group"]); ?>
                                            </span>
                                        <?php endif; ?>

                                        <?php if (!empty($lesson["topic"])): ?>
                                            <span class="director-pill topic">
                                                <i class="fas fa-lightbulb"></i>
                                                <?= htmlspecialchars($lesson["topic"]); ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="director-pill date">
                                            <i class="fas fa-calendar-day"></i>
                                            <?= htmlspecialchars(formatDateLv($lesson["lesson_date"] ?? null)); ?>
                                        </span>

                                        <span class="director-pill">
                                            <i class="fas fa-clock"></i>
                                            <?= htmlspecialchars(formatTimeLv($lesson["lesson_time"] ?? null)); ?>
                                        </span>

                                        <?php if (!empty($lesson["created_at"])): ?>
                                            <span class="director-pill created">
                                                <i class="fas fa-plus"></i>
                                                Izveidots: <?= htmlspecialchars(formatDateTimeLv($lesson["created_at"] ?? null)); ?>
                                            </span>
                                        <?php endif; ?>

                                        <span class="director-pill <?= $isActive ? 'active' : 'inactive'; ?>">
                                            <i class="fas <?= $isActive ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                                            <?= $isActive ? "Aktīva" : "Neaktīva"; ?>
                                        </span>
                                    </div>

                                    <p><?= htmlspecialchars(shortText($lesson["description"] ?? "")); ?></p>

                                    <?php if (!empty($lesson["location"])): ?>
                                        <p>
                                            <i class="fas fa-location-dot"></i>
                                            <?= htmlspecialchars($lesson["location"]); ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if (!empty($lesson["club_id"])): ?>
                                        <p>
                                            <i class="fas fa-people-roof"></i>
                                            Klubs ID: <?= (int)$lesson["club_id"]; ?>
                                        </p>
                                    <?php endif; ?>
                                </div>

                                <div class="director-lesson-side">
                                    <?php if ($hasIsActive): ?>
                                        <form method="post">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="lesson_id" value="<?= (int)$lesson["id"]; ?>">

                                            <button type="submit" class="btn btn-outline btn-sm">
                                                <?= $isActive ? "Deaktivizēt" : "Aktivizēt"; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="director-empty">
                        <h3>Nav pievienotu nodarbību</h3>
                        <p>Izveido pirmo nodarbību, izmantojot formu kreisajā pusē.</p>
                    </div>
                <?php endif; ?>
            </aside>

        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
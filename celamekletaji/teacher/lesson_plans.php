<?php
session_start();

$lapa  = "Nodarbību plāni";
$title = "Nodarbību plāni - Ceļa meklētāji";

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

$lessons = [];
$club = null;

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function tableExists(mysqli $db, string $table): bool
{
    $table = $db->real_escape_string($table);
    $result = $db->query("SHOW TABLES LIKE '{$table}'");

    return $result && $result->num_rows > 0;
}

function tableColumnExists(mysqli $db, string $table, string $column): bool
{
    $table = $db->real_escape_string($table);
    $column = $db->real_escape_string($column);

    $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");

    return $result && $result->num_rows > 0;
}

function firstExistingColumn(mysqli $db, string $table, array $columns): ?string
{
    foreach ($columns as $column) {
        if (tableColumnExists($db, $table, $column)) {
            return $column;
        }
    }

    return null;
}

function bindDynamicParams(mysqli_stmt $stmt, string $types, array &$params): bool
{
    $bindParams = [];
    $bindParams[] = $types;

    foreach ($params as $key => &$value) {
        $bindParams[] = &$value;
    }

    return call_user_func_array([$stmt, "bind_param"], $bindParams);
}

function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00" || $date === "0000-00-00 00:00:00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
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
   TABULAS UN KOLONNU PĀRBAUDE
================================ */
if (!tableExists($savienojums, "cm_lessons")) {
    $error = "Datubāzē nav atrasta tabula cm_lessons.";
}

$titleCol = null;
$descriptionCol = null;
$categoryCol = null;
$ageCol = null;
$dateCol = null;
$createdAtCol = null;
$clubIdCol = null;
$clubNameCol = null;
$createdByCol = null;

if ($error === "") {
    $titleCol = firstExistingColumn($savienojums, "cm_lessons", [
        "title",
        "temats",
        "topic",
        "name",
        "nosaukums"
    ]);

    $descriptionCol = firstExistingColumn($savienojums, "cm_lessons", [
        "description",
        "apraksts",
        "content",
        "saturs"
    ]);

    $categoryCol = firstExistingColumn($savienojums, "cm_lessons", [
        "category",
        "kategorija",
        "type"
    ]);

    $ageCol = firstExistingColumn($savienojums, "cm_lessons", [
        "age_group",
        "vecums",
        "age",
        "age_range"
    ]);

    $dateCol = firstExistingColumn($savienojums, "cm_lessons", [
        "lesson_date",
        "date",
        "datums"
    ]);

    $createdAtCol = firstExistingColumn($savienojums, "cm_lessons", [
        "created_at",
        "Izveides_datums",
        "created",
        "upload_date"
    ]);

    $clubIdCol = firstExistingColumn($savienojums, "cm_lessons", [
        "club_id"
    ]);

    $clubNameCol = firstExistingColumn($savienojums, "cm_lessons", [
        "klubs",
        "club"
    ]);

    $createdByCol = firstExistingColumn($savienojums, "cm_lessons", [
        "created_by",
        "teacher_id",
        "user_id",
        "lietotajs_id"
    ]);

    if (!$titleCol) {
        $error = "Tabulā cm_lessons nav atrasta nosaukuma/temata kolonna.";
    }
}

/* ===============================
   DZĒŠANA
================================ */
if ($error === "" && $_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "delete") {
    $lessonId = (int)($_POST["lesson_id"] ?? 0);

    if ($lessonId <= 0) {
        $error = "Nederīgs nodarbības ID.";
    } else {
        $where = "id = ?";
        $types = "i";
        $params = [$lessonId];

        if ($clubIdCol && $teacherClubId > 0) {
            $where .= " AND `{$clubIdCol}` = ?";
            $types .= "i";
            $params[] = $teacherClubId;
        } elseif ($createdByCol) {
            $where .= " AND `{$createdByCol}` = ?";
            $types .= "i";
            $params[] = $teacherId;
        }

        $sqlDelete = "
            DELETE FROM cm_lessons
            WHERE {$where}
            LIMIT 1
        ";

        if ($stmt = $savienojums->prepare($sqlDelete)) {
            bindDynamicParams($stmt, $types, $params);

            if ($stmt->execute()) {
                $success = "Nodarbības plāns veiksmīgi dzēsts.";
            } else {
                $error = "Neizdevās dzēst nodarbības plānu.";
            }

            $stmt->close();
        } else {
            $error = "Neizdevās sagatavot dzēšanas vaicājumu.";
        }
    }
}

/* ===============================
   PIEVIENOŠANA
================================ */
if ($error === "" && $_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "add") {
    $lessonTitle = trim($_POST["title"] ?? "");
    $description = trim($_POST["description"] ?? "");
    $category = trim($_POST["category"] ?? "");
    $age = trim($_POST["age"] ?? "");
    $lessonDate = trim($_POST["lesson_date"] ?? "");

    if ($lessonTitle === "") {
        $error = "Lūdzu ievadi nodarbības tematu.";
    } else {
        $fields = [];
        $placeholders = [];
        $types = "";
        $params = [];

        $fields[] = "`{$titleCol}`";
        $placeholders[] = "?";
        $types .= "s";
        $params[] = $lessonTitle;

        if ($descriptionCol) {
            $fields[] = "`{$descriptionCol}`";
            $placeholders[] = "?";
            $types .= "s";
            $params[] = $description;
        }

        if ($categoryCol) {
            $fields[] = "`{$categoryCol}`";
            $placeholders[] = "?";
            $types .= "s";
            $params[] = $category;
        }

        if ($ageCol) {
            $fields[] = "`{$ageCol}`";
            $placeholders[] = "?";
            $types .= "s";
            $params[] = $age;
        }

        if ($dateCol && $lessonDate !== "") {
            $fields[] = "`{$dateCol}`";
            $placeholders[] = "?";
            $types .= "s";
            $params[] = $lessonDate;
        }

        if ($clubIdCol && $teacherClubId > 0) {
            $fields[] = "`{$clubIdCol}`";
            $placeholders[] = "?";
            $types .= "i";
            $params[] = $teacherClubId;
        } elseif ($clubNameCol && $club) {
            $fields[] = "`{$clubNameCol}`";
            $placeholders[] = "?";
            $types .= "s";
            $params[] = $club["name"];
        }

        if ($createdByCol) {
            $fields[] = "`{$createdByCol}`";
            $placeholders[] = "?";
            $types .= "i";
            $params[] = $teacherId;
        }

        $sqlInsert = "
            INSERT INTO cm_lessons
            (" . implode(", ", $fields) . ")
            VALUES
            (" . implode(", ", $placeholders) . ")
        ";

        if ($stmt = $savienojums->prepare($sqlInsert)) {
            bindDynamicParams($stmt, $types, $params);

            if ($stmt->execute()) {
                $success = "Nodarbības plāns veiksmīgi pievienots.";
            } else {
                $error = "Neizdevās pievienot nodarbības plānu.";
            }

            $stmt->close();
        } else {
            $error = "Neizdevās sagatavot pievienošanas vaicājumu.";
        }
    }
}

/* ===============================
   NODARBĪBU SARAKSTS
================================ */
if ($error === "") {
    $where = "1";
    $types = "";
    $params = [];

    if ($clubIdCol && $teacherClubId > 0) {
        $where = "`{$clubIdCol}` = ?";
        $types = "i";
        $params[] = $teacherClubId;
    } elseif ($clubNameCol && $club) {
        $where = "`{$clubNameCol}` = ?";
        $types = "s";
        $params[] = $club["name"];
    } elseif ($createdByCol) {
        $where = "`{$createdByCol}` = ?";
        $types = "i";
        $params[] = $teacherId;
    }

    $selectDescription = $descriptionCol ? "`{$descriptionCol}` AS description" : "'' AS description";
    $selectCategory = $categoryCol ? "`{$categoryCol}` AS category" : "'' AS category";
    $selectAge = $ageCol ? "`{$ageCol}` AS age_group" : "'' AS age_group";
    $selectDate = $dateCol ? "`{$dateCol}` AS lesson_date" : "NULL AS lesson_date";
    $selectCreatedAt = $createdAtCol ? "`{$createdAtCol}` AS created_at" : "NULL AS created_at";

    $orderBy = "id DESC";

    if ($dateCol) {
        $orderBy = "`{$dateCol}` DESC, id DESC";
    } elseif ($createdAtCol) {
        $orderBy = "`{$createdAtCol}` DESC, id DESC";
    }

    $sqlLessons = "
        SELECT
            id,
            `{$titleCol}` AS title,
            {$selectDescription},
            {$selectCategory},
            {$selectAge},
            {$selectDate},
            {$selectCreatedAt}
        FROM cm_lessons
        WHERE {$where}
        ORDER BY {$orderBy}
    ";

    if ($stmt = $savienojums->prepare($sqlLessons)) {
        if ($types !== "") {
            bindDynamicParams($stmt, $types, $params);
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

require __DIR__ . "/../includes/templates/header-teacher.php";
?>

<style>
.teacher-lessons-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30,79,161,.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244,196,48,.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.teacher-lessons-hero {
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

.teacher-lessons-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.teacher-lessons-hero > * {
    position: relative;
    z-index: 1;
}

.teacher-lessons-kicker {
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

.teacher-lessons-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.teacher-lessons-hero p {
    max-width: 760px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.teacher-lessons-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.18);
    backdrop-filter: blur(8px);
}

.teacher-lessons-hero-card strong {
    display: block;
    font-size: 2.2rem;
    line-height: 1;
    color: #f4c430;
}

.teacher-lessons-hero-card span {
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
.teacher-textarea {
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
    min-height: 120px;
    resize: vertical;
}

.teacher-input:focus,
.teacher-textarea:focus {
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

.teacher-lessons-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
    gap: 1rem;
}

.teacher-lesson-card {
    display: flex;
    flex-direction: column;
    padding: 1.1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.teacher-lesson-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23,63,132,.08);
    border-color: #d7e5ff;
}

.teacher-lesson-card h3 {
    margin: 0 0 .45rem;
    color: #101828;
    line-height: 1.3;
}

.teacher-lesson-card p {
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

.teacher-lesson-actions {
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
    .teacher-lessons-hero {
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
    .teacher-lessons-page {
        padding: 1.5rem 0 2.5rem;
    }

    .teacher-lessons-hero,
    .teacher-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .teacher-form-actions .btn,
    .teacher-lesson-actions .btn,
    .teacher-lesson-actions button {
        width: 100%;
    }
}
</style>

<main class="teacher-lessons-page">
    <div class="container">

        <section class="teacher-lessons-hero">
            <div>
                <div class="teacher-lessons-kicker">
                    <i class="fas fa-clipboard-list"></i>
                    Skolotāja nodarbību plāni
                </div>

                <h1>Nodarbību plāni</h1>

                <p>
                    Šeit skolotājs var pievienot un pārvaldīt nodarbību plānus.
                    Nodarbības tiek piesaistītas skolotāja klubam, ja datubāzē ir pieejams lauks <strong>club_id</strong>.
                </p>
            </div>

            <aside class="teacher-lessons-hero-card">
                <strong><?= count($lessons); ?></strong>
                <span>Nodarbību plāni sistēmā</span>
            </aside>
        </section>

        <?php if ($success): ?>
            <div class="teacher-alert success">
                <?= htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="teacher-alert error">
                <?= htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <?php if ($error === "" || tableExists($savienojums, "cm_lessons")): ?>

            <section class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <h2>Pievienot nodarbību</h2>
                        <p>Izveido jaunu nodarbības plānu savam klubam.</p>
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
                            <label for="title">Temats *</label>
                            <input
                                class="teacher-input"
                                type="text"
                                name="title"
                                id="title"
                                required
                                placeholder="Piemēram: Jāzepa sapņi"
                            >
                        </div>

                        <?php if ($dateCol): ?>
                            <div class="teacher-form-group">
                                <label for="lesson_date">Datums</label>
                                <input
                                    class="teacher-input"
                                    type="date"
                                    name="lesson_date"
                                    id="lesson_date"
                                >
                            </div>
                        <?php endif; ?>

                        <?php if ($categoryCol): ?>
                            <div class="teacher-form-group">
                                <label for="category">Kategorija</label>
                                <input
                                    class="teacher-input"
                                    type="text"
                                    name="category"
                                    id="category"
                                    placeholder="Piemēram: Bībeles nodarbība"
                                >
                            </div>
                        <?php endif; ?>

                        <?php if ($ageCol): ?>
                            <div class="teacher-form-group">
                                <label for="age">Vecums</label>
                                <input
                                    class="teacher-input"
                                    type="text"
                                    name="age"
                                    id="age"
                                    placeholder="Piemēram: 7-9 gadi"
                                >
                            </div>
                        <?php endif; ?>

                        <?php if ($descriptionCol): ?>
                            <div class="teacher-form-group full">
                                <label for="description">Apraksts</label>
                                <textarea
                                    class="teacher-textarea"
                                    name="description"
                                    id="description"
                                    placeholder="Īss nodarbības apraksts..."
                                ></textarea>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="teacher-form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus"></i>
                            Pievienot nodarbību
                        </button>
                    </div>
                </form>
            </section>

            <section class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <h2>Nodarbību saraksts</h2>
                        <p>Visi pieejamie nodarbību plāni.</p>
                    </div>
                </div>

                <?php if (empty($lessons)): ?>

                    <div class="teacher-empty">
                        Pašlaik nav pievienotu nodarbību plānu.
                    </div>

                <?php else: ?>

                    <div class="teacher-lessons-grid">
                        <?php foreach ($lessons as $lesson): ?>
                            <article class="teacher-lesson-card">

                                <div class="teacher-meta">
                                    <?php if (!empty($lesson["category"])): ?>
                                        <span class="teacher-pill">
                                            <i class="fas fa-tag"></i>
                                            <?= htmlspecialchars($lesson["category"]); ?>
                                        </span>
                                    <?php endif; ?>

                                    <?php if (!empty($lesson["age_group"])): ?>
                                        <span class="teacher-pill">
                                            <i class="fas fa-child"></i>
                                            <?= htmlspecialchars($lesson["age_group"]); ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="teacher-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatDateLv($lesson["lesson_date"] ?? $lesson["created_at"] ?? null)); ?>
                                    </span>
                                </div>

                                <h3><?= htmlspecialchars($lesson["title"] ?? "Bez temata"); ?></h3>

                                <?php if (!empty($lesson["description"])): ?>
                                    <p>
                                        <?= nl2br(htmlspecialchars(mb_strimwidth($lesson["description"], 0, 180, "..."))); ?>
                                    </p>
                                <?php else: ?>
                                    <p>Apraksts nav pievienots.</p>
                                <?php endif; ?>

                                <div class="teacher-lesson-actions">
                                    <form method="post" onsubmit="return confirm('Tiešām dzēst šo nodarbības plānu?');">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="lesson_id" value="<?= (int)$lesson["id"]; ?>">

                                        <button type="submit" class="btn teacher-delete-btn">
                                            <i class="fas fa-trash"></i>
                                            Dzēst
                                        </button>
                                    </form>
                                </div>

                            </article>
                        <?php endforeach; ?>
                    </div>

                <?php endif; ?>
            </section>

        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
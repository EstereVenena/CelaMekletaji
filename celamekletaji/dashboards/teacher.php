<?php
session_start();

$lapa  = "Skolotāja panelis";
$title = "Skolotāja panelis - Ceļa meklētāji";

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

$teacher = null;
$club = null;
$error = null;

$stats = [
    "children" => 0,
    "lessons" => 0,
    "events" => 0,
    "applications" => 0,
];

$latestLessons = [];
$latestEvents = [];
$latestNews = [];

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

function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00" || $date === "0000-00-00 00:00:00") {
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

/* ===============================
   SKOLOTĀJA DATI
================================ */
$sqlTeacher = "
    SELECT
        u.lietotajs_id,
        u.lietotajvards,
        u.vards,
        u.uzvards,
        u.epasts,
        u.loma,
        u.statuss,
        u.club_id,
        u.Reg_datums,
        c.name AS club_name
    FROM cm_lietotaji u
    LEFT JOIN cm_clubs c ON u.club_id = c.id
    WHERE u.lietotajs_id = ?
    LIMIT 1
";

if ($stmt = $savienojums->prepare($sqlTeacher)) {
    $stmt->bind_param("i", $teacherId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows === 1) {
        $teacher = $result->fetch_assoc();
    } else {
        $error = "Skolotāja profils netika atrasts.";
    }

    $stmt->close();
} else {
    $error = "Neizdevās sagatavot skolotāja profila vaicājumu.";
}

if ($teacherClubId <= 0 && $teacher) {
    $teacherClubId = (int)($teacher["club_id"] ?? 0);
}

if ($teacherClubId <= 0) {
    $error = "Lūdzu, sazinies ar administratoru vai direktoru, lai skolotājam piešķirtu klubu.";
}

/* ===============================
   SKOLOTĀJA KLUBS
================================ */
if (!$error && $teacherClubId > 0) {
    $sqlClub = "
        SELECT 
            c.id,
            c.name,
            c.address,
            c.is_active,
            c.created_at,
            ch.name AS church_name,
            GROUP_CONCAT(p.label SEPARATOR ', ') AS programs
        FROM cm_clubs c
        LEFT JOIN cm_churches ch ON c.church_id = ch.id
        LEFT JOIN cm_club_programs cp ON c.id = cp.club_id
        LEFT JOIN cm_programs p ON cp.program_id = p.id
        WHERE c.id = ?
        GROUP BY 
            c.id,
            c.name,
            c.address,
            c.is_active,
            c.created_at,
            ch.name
        LIMIT 1
    ";

    if ($stmt = $savienojums->prepare($sqlClub)) {
        $stmt->bind_param("i", $teacherClubId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $club = $result->fetch_assoc();
        } else {
            $error = "Skolotājam piesaistītais klubs netika atrasts.";
        }

        $stmt->close();
    } else {
        $error = "Neizdevās sagatavot kluba vaicājumu.";
    }
}

/* ===============================
   STATISTIKA: BĒRNI KLUBĀ
================================ */
if ($club) {
    $clubId = (int)$club["id"];

    $sqlChildren = "
        SELECT COUNT(*) AS total
        FROM cm_lietotaji
        WHERE club_id = ?
          AND statuss <> 'dzēsts'
          AND loma IN ('Ceļameklētājs', 'Skolēns', 'Bērns', 'student', 'child')
    ";

    if ($stmt = $savienojums->prepare($sqlChildren)) {
        $stmt->bind_param("i", $clubId);
        $stmt->execute();

        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stats["children"] = (int)($row["total"] ?? 0);

        $stmt->close();
    }
}

/* ===============================
   STATISTIKA: NODARBĪBAS
================================ */
if (tableExists($savienojums, "cm_lessons")) {
    $lessonWhere = "1";
    $lessonTypes = "";
    $lessonParams = [];

    if (tableColumnExists($savienojums, "cm_lessons", "club_id") && $teacherClubId > 0) {
        $lessonWhere = "club_id = ?";
        $lessonTypes = "i";
        $lessonParams[] = $teacherClubId;
    }

    $sqlLessonCount = "
        SELECT COUNT(*) AS total
        FROM cm_lessons
        WHERE {$lessonWhere}
    ";

    if ($stmt = $savienojums->prepare($sqlLessonCount)) {
        if ($lessonTypes !== "") {
            $stmt->bind_param($lessonTypes, ...$lessonParams);
        }

        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        $stats["lessons"] = (int)($row["total"] ?? 0);

        $stmt->close();
    }

    $lessonDateColumn = tableColumnExists($savienojums, "cm_lessons", "date") ? "date" : null;
    $lessonCreatedColumn = tableColumnExists($savienojums, "cm_lessons", "created_at") ? "created_at" : null;

    $lessonOrder = "id DESC";

    if ($lessonDateColumn) {
        $lessonOrder = "`date` DESC";
    } elseif ($lessonCreatedColumn) {
        $lessonOrder = "created_at DESC";
    }

    $lessonTitleColumn = tableColumnExists($savienojums, "cm_lessons", "title") ? "title" : "id";
    $lessonDescriptionColumn = tableColumnExists($savienojums, "cm_lessons", "description") ? "description" : null;
    $lessonCategoryColumn = tableColumnExists($savienojums, "cm_lessons", "category") ? "category" : null;

    $selectLessonDescription = $lessonDescriptionColumn ? "`{$lessonDescriptionColumn}` AS description" : "'' AS description";
    $selectLessonCategory = $lessonCategoryColumn ? "`{$lessonCategoryColumn}` AS category" : "'' AS category";
    $selectLessonDate = $lessonDateColumn ? "`{$lessonDateColumn}` AS lesson_date" : "NULL AS lesson_date";

    $sqlLatestLessons = "
        SELECT
            id,
            `{$lessonTitleColumn}` AS title,
            {$selectLessonDescription},
            {$selectLessonCategory},
            {$selectLessonDate}
        FROM cm_lessons
        WHERE {$lessonWhere}
        ORDER BY {$lessonOrder}
        LIMIT 4
    ";

    if ($stmt = $savienojums->prepare($sqlLatestLessons)) {
        if ($lessonTypes !== "") {
            $stmt->bind_param($lessonTypes, ...$lessonParams);
        }

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $latestLessons[] = $row;
        }

        $stmt->close();
    }
}

/* ===============================
   STATISTIKA: PASĀKUMI
================================ */
if (tableExists($savienojums, "cm_events")) {
    $eventWhere = "is_active = 1";

    if (tableColumnExists($savienojums, "cm_events", "club_id") && $teacherClubId > 0) {
        $eventWhere .= " AND club_id = " . (int)$teacherClubId;
    }

    $sqlEventCount = "
        SELECT COUNT(*) AS total
        FROM cm_events
        WHERE {$eventWhere}
    ";

    if ($result = $savienojums->query($sqlEventCount)) {
        $row = $result->fetch_assoc();
        $stats["events"] = (int)($row["total"] ?? 0);
    }

    $eventOrderColumn = tableColumnExists($savienojums, "cm_events", "start_date") ? "start_date" : "id";

    $sqlLatestEvents = "
        SELECT
            id,
            title,
            description,
            start_date,
            end_date,
            start_time,
            end_time,
            location,
            event_type
        FROM cm_events
        WHERE {$eventWhere}
        ORDER BY {$eventOrderColumn} DESC
        LIMIT 4
    ";

    if ($result = $savienojums->query($sqlLatestEvents)) {
        while ($row = $result->fetch_assoc()) {
            $latestEvents[] = $row;
        }
    }
}

/* ===============================
   STATISTIKA: PIETEIKUMI
================================ */
if (tableExists($savienojums, "cm_event_applications")) {
    $applicationJoin = "";
    $applicationWhere = "1";

    if ($teacherClubId > 0 && tableColumnExists($savienojums, "cm_event_applications", "child_id")) {
        $applicationJoin = "
            INNER JOIN cm_lietotaji u ON ea.child_id = u.lietotajs_id
        ";
        $applicationWhere = "u.club_id = " . (int)$teacherClubId;
    }

    $sqlApplications = "
        SELECT COUNT(*) AS total
        FROM cm_event_applications ea
        {$applicationJoin}
        WHERE {$applicationWhere}
    ";

    if ($result = $savienojums->query($sqlApplications)) {
        $row = $result->fetch_assoc();
        $stats["applications"] = (int)($row["total"] ?? 0);
    }
}

/* ===============================
   JAUNĀKIE JAUNUMI
================================ */
if (tableExists($savienojums, "cm_news")) {
    $sqlNews = "
        SELECT 
            id,
            title,
            category,
            publish_date
        FROM cm_news
        WHERE is_active = 1
        ORDER BY publish_date DESC
        LIMIT 4
    ";

    if ($newsResult = $savienojums->query($sqlNews)) {
        while ($row = $newsResult->fetch_assoc()) {
            $latestNews[] = $row;
        }
    }
}

/* ===============================
   VĀRDS UZRUNAI
================================ */
$fullName = "Skolotāj";

if ($teacher) {
    $fullName = trim(($teacher["vards"] ?? "") . " " . ($teacher["uzvards"] ?? ""));

    if ($fullName === "") {
        $fullName = $teacher["lietotajvards"] ?? "Skolotāj";
    }
}

require __DIR__ . "/../includes/templates/header-teacher.php";
?>

<style>
.teacher-dashboard-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.teacher-hero {
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

.teacher-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.teacher-hero > * {
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

.teacher-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.teacher-hero p {
    max-width: 740px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.teacher-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.teacher-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.teacher-hero-card strong {
    display: block;
    font-size: 2.05rem;
    line-height: 1.1;
    color: #f4c430;
}

.teacher-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.teacher-alert {
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

.teacher-panel {
    padding: 1.35rem;
    margin-bottom: 1.2rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
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

.teacher-info-grid,
.teacher-actions-grid,
.teacher-news-grid,
.teacher-lessons-grid,
.teacher-events-grid {
    display: grid;
    gap: 1rem;
}

.teacher-info-grid {
    grid-template-columns: repeat(3, 1fr);
}

.teacher-actions-grid {
    grid-template-columns: repeat(4, 1fr);
}

.teacher-news-grid,
.teacher-lessons-grid,
.teacher-events-grid {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.teacher-info-card,
.teacher-action-card,
.teacher-news-card,
.teacher-lesson-card,
.teacher-event-card {
    padding: 1.1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.teacher-info-card:hover,
.teacher-action-card:hover,
.teacher-news-card:hover,
.teacher-lesson-card:hover,
.teacher-event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.teacher-info-icon,
.teacher-action-icon {
    width: 46px;
    height: 46px;
    display: grid;
    place-items: center;
    margin-bottom: .75rem;
    border-radius: 15px;
    background: #eef3ff;
    color: #173f84;
    font-size: 1.2rem;
}

.teacher-info-card h3,
.teacher-action-card h3,
.teacher-news-card h3,
.teacher-lesson-card h3,
.teacher-event-card h3 {
    margin: 0 0 .35rem;
    color: #101828;
    font-size: 1.05rem;
    line-height: 1.3;
}

.teacher-info-card p,
.teacher-action-card p,
.teacher-news-card p,
.teacher-lesson-card p,
.teacher-event-card p {
    margin: 0;
    color: #667085;
    line-height: 1.55;
}

.teacher-status-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .7rem;
    border-radius: 999px;
    background: #ecfff4;
    color: #17633a;
    font-weight: 950;
}

.teacher-status-pill.inactive {
    background: #fff8e6;
    color: #7a5517;
}

.teacher-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: .9rem;
}

.teacher-stat-card {
    position: relative;
    overflow: hidden;
    padding: 1.1rem;
    border-radius: 22px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    box-shadow: 0 10px 24px rgba(16, 24, 40, 0.04);
}

.teacher-stat-card::after {
    content: "";
    position: absolute;
    right: -28px;
    top: -28px;
    width: 84px;
    height: 84px;
    border-radius: 50%;
    background: rgba(244,196,48,.22);
}

.teacher-stat-number {
    position: relative;
    z-index: 1;
    display: block;
    color: #173f84;
    font-size: 2rem;
    font-weight: 1000;
    line-height: 1;
}

.teacher-stat-label {
    position: relative;
    z-index: 1;
    display: block;
    margin-top: .5rem;
    color: #667085;
    font-weight: 850;
    line-height: 1.35;
}

.teacher-action-card {
    display: flex;
    flex-direction: column;
    min-height: 220px;
}

.teacher-action-card p {
    flex: 1;
    margin-bottom: .9rem;
}

.teacher-meta {
    display: flex;
    gap: .4rem;
    flex-wrap: wrap;
    margin-bottom: .55rem;
    color: #667085;
    font-size: .86rem;
    font-weight: 800;
}

.teacher-empty {
    padding: 1.2rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
}

@media (max-width: 1100px) {
    .teacher-info-grid,
    .teacher-actions-grid,
    .teacher-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 900px) {
    .teacher-hero {
        grid-template-columns: 1fr;
    }

    .teacher-panel-head {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .teacher-dashboard-page {
        padding: 1.5rem 0 2.5rem;
    }

    .teacher-hero,
    .teacher-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .teacher-info-grid,
    .teacher-actions-grid,
    .teacher-stats-grid,
    .teacher-news-grid,
    .teacher-lessons-grid,
    .teacher-events-grid {
        grid-template-columns: 1fr;
    }

    .teacher-hero-actions .btn,
    .teacher-action-card .btn,
    .teacher-panel-head .btn {
        width: 100%;
    }
}
</style>

<main class="teacher-dashboard-page">
    <div class="container">

        <section class="teacher-hero">
            <div>
                <div class="teacher-kicker">
                    <i class="fas fa-chalkboard-user"></i>
                    Skolotāja piekļuve
                </div>

                <h1>Sveiki, <?= htmlspecialchars($fullName); ?>!</h1>

                <p>
                    <?php if ($club): ?>
                        Šeit vari apskatīt sava kluba informāciju, nodarbību plānus, pasākumus un pieteikumus.
                    <?php else: ?>
                        Skolotāja sadaļa. Lai redzētu pilnu informāciju, skolotājam jābūt piesaistītam klubam.
                    <?php endif; ?>
                </p>

                <div class="teacher-hero-actions">
                    <a class="btn btn-primary btn-sm" href="../teacher/lesson_plans.php">
                        <i class="fas fa-clipboard-list"></i>
                        Nodarbību plāni
                    </a>

                    <a class="btn btn-outline btn-sm" href="../teacher/activities.php">
                        <i class="fas fa-calendar-days"></i>
                        Pasākumi
                    </a>

                    <a class="btn btn-outline btn-sm" href="../teacher/applications.php">
                        <i class="fas fa-file-signature"></i>
                        Pieteikumi
                    </a>
                </div>
            </div>

            <aside class="teacher-hero-card">
                <strong><?= $club ? htmlspecialchars($club["name"]) : "Nav kluba"; ?></strong>
                <span>
                    <?= $club ? htmlspecialchars($club["address"] ?? "Adrese nav norādīta") : "Skolotājam nav piesaistīts klubs."; ?>
                </span>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="teacher-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($club): ?>

            <section class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <h2><?= htmlspecialchars($club["name"]); ?></h2>
                        <p>Kluba pamatinformācija un piesaistītās programmas.</p>
                    </div>

                    <a class="btn btn-primary btn-sm" href="../teacher/club.php">
                        <i class="fas fa-circle-info"></i>
                        Skatīt klubu
                    </a>
                </div>

                <div class="teacher-info-grid">
                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-church"></i>
                        </div>
                        <h3>Draudze</h3>
                        <p><?= htmlspecialchars($club["church_name"] ?? "Nav norādīta"); ?></p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h3>Programmas</h3>
                        <p><?= htmlspecialchars($club["programs"] ?? "Nav piesaistītas"); ?></p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-circle-check"></i>
                        </div>
                        <h3>Statuss</h3>
                        <?php $isActive = ((int)($club["is_active"] ?? 0) === 1); ?>
                        <p>
                            <span class="teacher-status-pill <?= $isActive ? '' : 'inactive'; ?>">
                                <i class="fas <?= $isActive ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                                <?= $isActive ? "Aktīvs" : "Neaktīvs"; ?>
                            </span>
                        </p>
                    </article>
                </div>
            </section>

            <section class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <h2>Skolotāja pārskats</h2>
                        <p>Īss pārskats par klubam un skolotājam svarīgākajiem datiem.</p>
                    </div>
                </div>

                <div class="teacher-stats-grid">
                    <article class="teacher-stat-card">
                        <span class="teacher-stat-number"><?= (int)$stats["children"]; ?></span>
                        <span class="teacher-stat-label">Bērni / Ceļameklētāji</span>
                    </article>

                    <article class="teacher-stat-card">
                        <span class="teacher-stat-number"><?= (int)$stats["lessons"]; ?></span>
                        <span class="teacher-stat-label">Nodarbības</span>
                    </article>

                    <article class="teacher-stat-card">
                        <span class="teacher-stat-number"><?= (int)$stats["events"]; ?></span>
                        <span class="teacher-stat-label">Aktīvie pasākumi</span>
                    </article>

                    <article class="teacher-stat-card">
                        <span class="teacher-stat-number"><?= (int)$stats["applications"]; ?></span>
                        <span class="teacher-stat-label">Pieteikumi</span>
                    </article>
                </div>
            </section>

            <section class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <h2>Ātrās darbības</h2>
                        <p>Biežāk izmantotās skolotāja funkcijas.</p>
                    </div>
                </div>

                <div class="teacher-actions-grid">
                    <article class="teacher-action-card">
                        <div class="teacher-action-icon">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <h3>Nodarbību plāni</h3>
                        <p>Pievieno, apskati vai labo nodarbību plānus savam klubam.</p>
                        <a class="btn btn-primary btn-sm" href="../teacher/lesson_plans.php">
                            Atvērt
                        </a>
                    </article>

                    <article class="teacher-action-card">
                        <div class="teacher-action-icon">
                            <i class="fas fa-calendar-days"></i>
                        </div>
                        <h3>Pasākumi</h3>
                        <p>Apskati pasākumus un kluba aktivitātes.</p>
                        <a class="btn btn-outline btn-sm" href="../teacher/activities.php">
                            Atvērt
                        </a>
                    </article>

                    <article class="teacher-action-card">
                        <div class="teacher-action-icon">
                            <i class="fas fa-file-signature"></i>
                        </div>
                        <h3>Pieteikumi</h3>
                        <p>Pārskati dalībnieku pieteikumus un to statusus.</p>
                        <a class="btn btn-outline btn-sm" href="../teacher/applications.php">
                            Atvērt
                        </a>
                    </article>

                    <article class="teacher-action-card">
                        <div class="teacher-action-icon">
                            <i class="fas fa-bell"></i>
                        </div>
                        <h3>Paziņojumi</h3>
                        <p>Apskati sistēmas paziņojumus un svarīgākās izmaiņas.</p>
                        <a class="btn btn-outline btn-sm" href="../dashboards/notifications.php">
                            Atvērt
                        </a>
                    </article>
                </div>
            </section>

        <?php endif; ?>

        <section class="teacher-panel">
            <div class="teacher-panel-head">
                <div>
                    <h2>Jaunākās nodarbības</h2>
                    <p>Pēdējie nodarbību ieraksti sistēmā.</p>
                </div>

                <a class="btn btn-outline btn-sm" href="../teacher/lesson_plans.php">
                    Skatīt visas
                </a>
            </div>

            <?php if (empty($latestLessons)): ?>
                <div class="teacher-empty">
                    Pašlaik nav pievienotu nodarbību.
                </div>
            <?php else: ?>
                <div class="teacher-lessons-grid">
                    <?php foreach ($latestLessons as $lesson): ?>
                        <article class="teacher-lesson-card">
                            <div class="teacher-meta">
                                <span><?= htmlspecialchars($lesson["category"] ?? "Nodarbība"); ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars(formatDateLv($lesson["lesson_date"] ?? null)); ?></span>
                            </div>

                            <h3><?= htmlspecialchars($lesson["title"] ?? "Bez nosaukuma"); ?></h3>

                            <p>
                                <?= htmlspecialchars(mb_strimwidth($lesson["description"] ?? "", 0, 120, "...")); ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="teacher-panel">
            <div class="teacher-panel-head">
                <div>
                    <h2>Jaunākie pasākumi</h2>
                    <p>Aktuālākie pasākumi un aktivitātes.</p>
                </div>

                <a class="btn btn-outline btn-sm" href="../teacher/activities.php">
                    Skatīt visus
                </a>
            </div>

            <?php if (empty($latestEvents)): ?>
                <div class="teacher-empty">
                    Pašlaik nav aktīvu pasākumu.
                </div>
            <?php else: ?>
                <div class="teacher-events-grid">
                    <?php foreach ($latestEvents as $event): ?>
                        <article class="teacher-event-card">
                            <div class="teacher-meta">
                                <span><?= htmlspecialchars($event["event_type"] ?? "Pasākums"); ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars(formatDateLv($event["start_date"] ?? null)); ?></span>
                            </div>

                            <h3><?= htmlspecialchars($event["title"] ?? "Bez nosaukuma"); ?></h3>

                            <p>
                                <?= htmlspecialchars($event["location"] ?? "Vieta nav norādīta"); ?>
                            </p>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="teacher-panel">
            <div class="teacher-panel-head">
                <div>
                    <h2>Jaunākie jaunumi</h2>
                    <p>Aktuālākie ieraksti mājaslapā.</p>
                </div>
            </div>

            <?php if (empty($latestNews)): ?>
                <div class="teacher-empty">
                    Pašlaik nav publicētu jaunumu.
                </div>
            <?php else: ?>
                <div class="teacher-news-grid">
                    <?php foreach ($latestNews as $item): ?>
                        <article class="teacher-news-card">
                            <div class="teacher-meta">
                                <span><?= htmlspecialchars($item["category"] ?? "—"); ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars(formatDateLv($item["publish_date"] ?? null)); ?></span>
                            </div>

                            <h3><?= htmlspecialchars($item["title"] ?? "Bez nosaukuma"); ?></h3>

                            <a class="link" href="../news/view.php?id=<?= (int)$item["id"]; ?>">
                                Lasīt vairāk
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
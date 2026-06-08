<?php
session_start();

$lapa  = "Nodarbības";
$title = "Nodarbības - Ceļa meklētāji";

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

$lessons = [];
$success = trim($_GET["success"] ?? "");
$error   = trim($_GET["error"] ?? "");

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";

    header("Location: lessons.php?" . $param . "=" . urlencode($message));
    exit();
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
    if (empty($time)) {
        return "—";
    }

    return date("H:i", strtotime($time));
}

function tableExists(mysqli $db, string $table): bool
{
    $sql = "
        SELECT COUNT(*) AS total
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ";

    $stmt = $db->prepare($sql);

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

/* ===============================
   TABULU PĀRBAUDE
================================ */
$lessonsTableExists = tableExists($savienojums, "cm_lessons");
$applicationsTableExists = tableExists($savienojums, "cm_lesson_applications");

if (!$lessonsTableExists) {
    $error = "Tabula cm_lessons nav atrasta.";
}

if (!$applicationsTableExists) {
    $error = "Tabula cm_lesson_applications nav atrasta. Izveido šo tabulu, lai pieteikšanās darbotos.";
}

/* ===============================
   PIETEIKŠANĀS NODARBĪBAI
================================ */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["lesson_id"])
) {
    if (!$lessonsTableExists || !$applicationsTableExists) {
        redirectWithMessage(
            "error",
            "Pieteikšanās nav iespējama, jo nepieciešamās datubāzes tabulas nav izveidotas."
        );
    }

    $lessonId = (int) $_POST["lesson_id"];

    if ($lessonId <= 0) {
        redirectWithMessage("error", "Nederīga nodarbība.");
    }

    $checkSql = "
        SELECT 
            id,
            max_participants
        FROM cm_lessons
        WHERE id = ?
          AND is_active = 1
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($checkSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot nodarbības pārbaudi.");
    }

    $stmt->bind_param("i", $lessonId);
    $stmt->execute();

    $lessonResult = $stmt->get_result();
    $lesson = $lessonResult->fetch_assoc();

    $stmt->close();

    if (!$lesson) {
        redirectWithMessage("error", "Nodarbība nav atrasta vai vairs nav aktīva.");
    }

    /* ===============================
   PĀRBAUDA, VAI PIETEIKUMS JAU EKSISTĒ
================================ */
$alreadySql = "
    SELECT id, status
    FROM cm_lesson_applications
    WHERE lesson_id = ?
      AND user_id = ?
    LIMIT 1
";

$stmt = $savienojums->prepare($alreadySql);

if (!$stmt) {
    redirectWithMessage("error", "Neizdevās pārbaudīt esošu pieteikumu.");
}

$stmt->bind_param("ii", $lessonId, $userId);
$stmt->execute();

$alreadyResult = $stmt->get_result();
$existingApplication = $alreadyResult->fetch_assoc();

$stmt->close();

if ($existingApplication) {
    $existingStatus = $existingApplication["status"] ?? "";

    if (in_array($existingStatus, ["pieteikts", "apstiprināts"], true)) {
        redirectWithMessage("error", "Tu jau esi pieteicies šai nodarbībai.");
    }

    /*
       Ja lietotājs agrāk atteicās / tika noraidīts,
       tad izmantojam esošo ierakstu un atjaunojam statusu.
    */
    $updateSql = "
        UPDATE cm_lesson_applications
        SET status = 'pieteikts'
        WHERE id = ?
          AND lesson_id = ?
          AND user_id = ?
    ";

    $stmt = $savienojums->prepare($updateSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot atkārtotu pieteikšanos.");
    }

    $existingApplicationId = (int)$existingApplication["id"];

    $stmt->bind_param("iii", $existingApplicationId, $lessonId, $userId);

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage("success", "Pieteikšanās veiksmīga.");
    }

    $stmt->close();

    redirectWithMessage("error", "Neizdevās atjaunot pieteikumu.");
}

/* ===============================
   BRĪVO VIETU PĀRBAUDE
================================ */
$maxParticipants = $lesson["max_participants"] ?? null;

if (!empty($maxParticipants)) {
    $countSql = "
        SELECT COUNT(*) AS total
        FROM cm_lesson_applications
        WHERE lesson_id = ?
          AND status IN ('pieteikts', 'apstiprināts')
    ";

    $stmt = $savienojums->prepare($countSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās pārbaudīt brīvās vietas.");
    }

    $stmt->bind_param("i", $lessonId);
    $stmt->execute();

    $countResult = $stmt->get_result();
    $countRow = $countResult->fetch_assoc();

    $stmt->close();

    $currentCount = (int)($countRow["total"] ?? 0);

    if ($currentCount >= (int)$maxParticipants) {
        redirectWithMessage("error", "Šai nodarbībai vairs nav brīvu vietu.");
    }
}

/* ===============================
   JAUNS PIETEIKUMS
================================ */
$insertSql = "
    INSERT INTO cm_lesson_applications 
        (lesson_id, user_id, status)
    VALUES 
        (?, ?, 'pieteikts')
";

$stmt = $savienojums->prepare($insertSql);

if (!$stmt) {
    redirectWithMessage("error", "Neizdevās sagatavot pieteikuma saglabāšanu.");
}

$stmt->bind_param("ii", $lessonId, $userId);

try {
    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage("success", "Pieteikšanās veiksmīga.");
    }

    $stmt->close();
    redirectWithMessage("error", "Neizdevās saglabāt pieteikumu.");

} catch (mysqli_sql_exception $e) {
    $stmt->close();

    if ((int)$e->getCode() === 1062) {
        redirectWithMessage("error", "Tu jau esi pieteicies šai nodarbībai.");
    }

    redirectWithMessage("error", "Neizdevās saglabāt pieteikumu.");
}

    $stmt->bind_param("ii", $lessonId, $userId);
    $stmt->execute();

    $alreadyResult = $stmt->get_result();
    $alreadyApplied = $alreadyResult->num_rows > 0;

    $stmt->close();

    if ($alreadyApplied) {
        redirectWithMessage("error", "Tu jau esi pieteicies šai nodarbībai.");
    }

    $maxParticipants = $lesson["max_participants"] ?? null;

    if (!empty($maxParticipants)) {
        $countSql = "
            SELECT COUNT(*) AS total
            FROM cm_lesson_applications
            WHERE lesson_id = ?
              AND status = 'pieteikts'
        ";

        $stmt = $savienojums->prepare($countSql);

        if (!$stmt) {
            redirectWithMessage("error", "Neizdevās pārbaudīt brīvās vietas.");
        }

        $stmt->bind_param("i", $lessonId);
        $stmt->execute();

        $countResult = $stmt->get_result();
        $countRow = $countResult->fetch_assoc();

        $stmt->close();

        $currentCount = (int) ($countRow["total"] ?? 0);

        if ($currentCount >= (int) $maxParticipants) {
            redirectWithMessage("error", "Šai nodarbībai vairs nav brīvu vietu.");
        }
    }

    $insertSql = "
        INSERT INTO cm_lesson_applications 
            (lesson_id, user_id, status)
        VALUES 
            (?, ?, 'pieteikts')
    ";

    $stmt = $savienojums->prepare($insertSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot pieteikuma saglabāšanu.");
    }

    $stmt->bind_param("ii", $lessonId, $userId);

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage("success", "Pieteikšanās veiksmīga.");
    }

    $stmt->close();

    if ($savienojums->errno === 1062) {
        redirectWithMessage("error", "Tu jau esi pieteicies šai nodarbībai.");
    }

    redirectWithMessage("error", "Neizdevās saglabāt pieteikumu.");
}

/* ===============================
   NODARBĪBU SARAKSTS
================================ */
if ($lessonsTableExists && $applicationsTableExists) {
    $lessonsSql = "
        SELECT 
            l.id,
            l.title,
            l.description,
            l.lesson_date,
            l.lesson_time,
            l.location,
            l.max_participants,

            (
                SELECT COUNT(*)
                FROM cm_lesson_applications la
                WHERE la.lesson_id = l.id
                  AND la.status = 'pieteikts'
            ) AS applied_count,

            (
                SELECT COUNT(*)
                FROM cm_lesson_applications la2
                WHERE la2.lesson_id = l.id
                  AND la2.user_id = ?
                  AND la2.status = 'pieteikts'
            ) AS user_applied

        FROM cm_lessons l
        WHERE l.is_active = 1
        ORDER BY l.lesson_date ASC, l.lesson_time ASC
    ";

    $stmt = $savienojums->prepare($lessonsSql);

    if ($stmt) {
        $stmt->bind_param("i", $userId);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $lessons[] = $row;
        }

        $stmt->close();
    } else {
        $error = "Neizdevās ielādēt nodarbību sarakstu.";
    }
} elseif ($lessonsTableExists) {
    $lessonsSql = "
        SELECT 
            id,
            title,
            description,
            lesson_date,
            lesson_time,
            location,
            max_participants,
            0 AS applied_count,
            0 AS user_applied
        FROM cm_lessons
        WHERE is_active = 1
        ORDER BY lesson_date ASC, lesson_time ASC
    ";

    $result = $savienojums->query($lessonsSql);

    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $lessons[] = $row;
        }
    } else {
        $error = "Neizdevās ielādēt nodarbību sarakstu.";
    }
}

$lessonsCount = count($lessons);

require __DIR__ . "/../includes/templates/header-student.php";
?>

<style>
.student-lessons-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.lessons-hero {
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

.lessons-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.lessons-hero > * {
    position: relative;
    z-index: 1;
}

.lessons-kicker {
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

.lessons-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.lessons-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.lessons-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.lessons-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.lessons-hero-card strong {
    display: block;
    font-size: 2.1rem;
    line-height: 1;
    color: #f4c430;
}

.lessons-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.lesson-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 800;
}

.lesson-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.lesson-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.lessons-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.lessons-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.lessons-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.lessons-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
}

.lessons-count {
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

.lessons-grid {
    display: grid;
    gap: .95rem;
}

.lesson-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 20px;
    background: #f8fbff;
    transition: .2s ease;
}

.lesson-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.lesson-card h3 {
    margin: 0 0 .45rem;
    color: #101828;
    font-size: 1.12rem;
    line-height: 1.25;
}

.lesson-meta {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    margin-bottom: .65rem;
}

.lesson-pill {
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

.lesson-pill i {
    color: #1e4fa1;
}

.lesson-description {
    margin: .65rem 0 0;
    color: #667085;
    line-height: 1.55;
}

.lesson-side {
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

.lesson-space {
    display: grid;
    gap: .25rem;
}

.lesson-space span {
    color: #667085;
    font-size: .88rem;
    font-weight: 800;
}

.lesson-space strong {
    color: #173f84;
    font-size: 1.05rem;
}

.lesson-progress {
    width: 100%;
    height: 9px;
    overflow: hidden;
    border-radius: 999px;
    background: #eef3ff;
}

.lesson-progress-bar {
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(135deg, #173f84, #1e4fa1);
}

.lesson-status {
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

.lesson-status.success {
    background: #ecfff4;
    color: #17633a;
}

.lesson-status.warning {
    background: #fff8e6;
    color: #7a5517;
}

.lesson-status.disabled {
    background: #f2f4f7;
    color: #667085;
}

.lesson-side form {
    margin: 0;
}

.lesson-side .btn {
    width: 100%;
}

.lessons-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.lessons-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

@media (max-width: 900px) {
    .lessons-hero,
    .lesson-card {
        grid-template-columns: 1fr;
    }

    .lessons-panel-head {
        flex-direction: column;
    }

    .lesson-side {
        width: 100%;
    }
}

@media (max-width: 640px) {
    .student-lessons-page {
        padding: 1.5rem 0 2.5rem;
    }

    .lessons-hero,
    .lessons-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .lessons-hero-actions .btn {
        width: 100%;
    }
}
</style>

<main class="student-lessons-page">
    <div class="container">

        <section class="lessons-hero">
            <div>
                <div class="lessons-kicker">
                    <i class="fas fa-book-open"></i>
                    Pieejamās nodarbības
                </div>

                <h1>Nodarbības</h1>

                <p>
                    Apskati pieejamās nodarbības, seko brīvajām vietām un piesakies tām,
                    kurās vēlies piedalīties.
                </p>

                <div class="lessons-hero-actions">
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

            <aside class="lessons-hero-card">
                <strong><?= (int)$lessonsCount; ?></strong>
                <span>
                    Aktīvas nodarbības, kuras pašlaik ir pieejamas apskatei.
                </span>
            </aside>
        </section>

        <?php if (!empty($success)): ?>
            <div class="lesson-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="lesson-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="lessons-panel">
            <div class="lessons-panel-head">
                <div>
                    <h2>Nodarbību saraksts</h2>
                    <p>Izvēlies nodarbību un piesakies, ja vēl ir brīvas vietas.</p>
                </div>

                <div class="lessons-count">
                    <i class="fas fa-list-check"></i>
                    Kopā: <?= (int)$lessonsCount; ?>
                </div>
            </div>

            <?php if (!empty($lessons)): ?>
                <div class="lessons-grid">

                    <?php foreach ($lessons as $lesson): ?>
                        <?php
                            $lessonId = (int) ($lesson["id"] ?? 0);
                            $alreadyApplied = (int) ($lesson["user_applied"] ?? 0) > 0;
                            $appliedCount = (int) ($lesson["applied_count"] ?? 0);

                            $maxParticipants = $lesson["max_participants"] ?? null;
                            $isFull = !empty($maxParticipants) && $appliedCount >= (int) $maxParticipants;

                            $description = trim($lesson["description"] ?? "");

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

                        <article class="lesson-card">
                            <div>
                                <h3>
                                    <?= htmlspecialchars($lesson["title"] ?? "Bez nosaukuma"); ?>
                                </h3>

                                <div class="lesson-meta">
                                    <span class="lesson-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatDateLv($lesson["lesson_date"] ?? null)); ?>
                                    </span>

                                    <span class="lesson-pill">
                                        <i class="fas fa-clock"></i>
                                        <?= htmlspecialchars(formatTimeLv($lesson["lesson_time"] ?? null)); ?>
                                    </span>

                                    <span class="lesson-pill">
                                        <i class="fas fa-location-dot"></i>
                                        <?= htmlspecialchars($lesson["location"] ?? "—"); ?>
                                    </span>
                                </div>

                                <p class="lesson-description">
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($description, 0, 260, "..."))); ?>
                                </p>
                            </div>

                            <aside class="lesson-side">
                                <div class="lesson-space">
                                    <span>Pieteikušies</span>
                                    <strong><?= htmlspecialchars($spaceText); ?></strong>

                                    <?php if (!empty($maxParticipants)): ?>
                                        <div class="lesson-progress">
                                            <div class="lesson-progress-bar" style="width: <?= (int)$progress; ?>%;"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$applicationsTableExists): ?>

                                    <div class="lesson-status disabled">
                                        <i class="fas fa-circle-info"></i>
                                        Pieteikšanās nav aktivizēta
                                    </div>

                                <?php elseif ($alreadyApplied): ?>

                                    <div class="lesson-status success">
                                        <i class="fas fa-circle-check"></i>
                                        Jau pieteicies
                                    </div>

                                <?php elseif ($isFull): ?>

                                    <div class="lesson-status warning">
                                        <i class="fas fa-triangle-exclamation"></i>
                                        Vietu nav
                                    </div>

                                <?php else: ?>

                                    <form method="post">
                                        <input 
                                            type="hidden" 
                                            name="lesson_id" 
                                            value="<?= $lessonId; ?>"
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
                <div class="lessons-empty">
                    <h3>Šobrīd nav pieejamu nodarbību</h3>
                    <p>Nodarbību saraksts pagaidām ir tukšs.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
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

/*
   Ziņojumi tiek paņemti no URL pēc redirect.
   Tas novērš Firefox "Document Expired" problēmu pēc POST formas.
*/
$success = trim($_GET["success"] ?? "");
$error   = trim($_GET["error"] ?? "");

/* ===============================
   PALĪGFUNKCIJA REDIRECT ZIŅOJUMIEM
================================ */
function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";

    header("Location: index.php?" . $param . "=" . urlencode($message));
    exit();
}

/* ===============================
   PIETEIKŠANĀS NODARBĪBAI
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["lesson_id"])) {
    $lessonId = (int) $_POST["lesson_id"];

    if ($lessonId <= 0) {
        redirectWithMessage("error", "Nederīga nodarbība.");
    }

    /*
       Pārbauda, vai nodarbība eksistē un ir aktīva.
    */
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

    /*
       Pārbauda, vai lietotājs jau nav pieteicies.
       Tehniski UNIQUE KEY to arī aizsargā, bet šādi var parādīt draudzīgāku ziņu.
    */
    $alreadySql = "
        SELECT id
        FROM cm_lesson_applications
        WHERE lesson_id = ?
          AND user_id = ?
          AND status = 'pieteikts'
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($alreadySql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās pārbaudīt esošu pieteikumu.");
    }

    $stmt->bind_param("ii", $lessonId, $userId);
    $stmt->execute();

    $alreadyResult = $stmt->get_result();
    $alreadyApplied = $alreadyResult->num_rows > 0;

    $stmt->close();

    if ($alreadyApplied) {
        redirectWithMessage("error", "Tu jau esi pieteicies šai nodarbībai.");
    }

    /*
       Pārbauda vietu limitu, ja max_participants ir norādīts.
    */
    $maxParticipants = $lesson["max_participants"];

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

    /*
       Saglabā pieteikumu.
    */
    $insertSql = "
        INSERT INTO cm_lesson_applications (lesson_id, user_id, status)
        VALUES (?, ?, 'pieteikts')
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

    /*
       Ja tomēr dubultklikšķis vai cita sacīkstes situācija.
    */
    if ($savienojums->errno === 1062) {
        redirectWithMessage("error", "Tu jau esi pieteicies šai nodarbībai.");
    }

    redirectWithMessage("error", "Neizdevās saglabāt pieteikumu.");
}

/* ===============================
   NODARBĪBU SARAKSTS
================================ */
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

/* ===============================
   DATUMA FORMATĒŠANA
================================ */
function formatDateLv(?string $date): string
{
    if (empty($date)) {
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

require __DIR__ . "/../includes/templates/header-student.php";
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <div class="page-hero-content">
            <h1>Nodarbības</h1>
            <p class="lead">
                Apskati pieejamās nodarbības un piesakies tām.
            </p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if (!empty($success)): ?>
            <div class="card" style="margin-bottom: 1rem; border-left: 4px solid #2e9e44;">
                <p class="muted">
                    <?= htmlspecialchars($success) ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="card" style="margin-bottom: 1rem; border-left: 4px solid #c0392b;">
                <p class="muted">
                    <?= htmlspecialchars($error) ?>
                </p>
            </div>
        <?php endif; ?>

        <?php if (!empty($lessons)): ?>
            <div class="lessons-grid">

                <?php foreach ($lessons as $lesson): ?>
                    <?php
                        $lessonId = (int) $lesson["id"];
                        $alreadyApplied = (int) $lesson["user_applied"] > 0;
                        $appliedCount = (int) $lesson["applied_count"];

                        $maxParticipants = $lesson["max_participants"];
                        $isFull = !empty($maxParticipants) && $appliedCount >= (int) $maxParticipants;

                        $description = trim($lesson["description"] ?? "");
                        if ($description === "") {
                            $description = "Apraksts nav pievienots.";
                        }
                    ?>

                    <article class="card news-card-page lesson-card">
                        <div class="news-meta">
                            <span class="news-tag">
                                Nodarbība
                            </span>

                            <span class="news-date">
                                <?= htmlspecialchars(formatDateLv($lesson["lesson_date"] ?? null)) ?>
                            </span>
                        </div>

                        <h2 class="news-card-title-page">
                            <?= htmlspecialchars($lesson["title"] ?? "Bez nosaukuma") ?>
                        </h2>

                        <p class="muted small">
                            <strong>Laiks:</strong>
                            <?= htmlspecialchars(formatTimeLv($lesson["lesson_time"] ?? null)) ?>
                            &nbsp;•&nbsp;
                            <strong>Vieta:</strong>
                            <?= htmlspecialchars($lesson["location"] ?? "—") ?>
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
                            <?php if ($alreadyApplied): ?>

                                <button class="btn btn-outline btn-sm" type="button" disabled>
                                    Jau pieteicies
                                </button>

                            <?php elseif ($isFull): ?>

                                <button class="btn btn-outline btn-sm" type="button" disabled>
                                    Vietu nav
                                </button>

                            <?php else: ?>

                                <form method="post">
                                    <input type="hidden" name="lesson_id" value="<?= $lessonId ?>">

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
                <p class="muted">Šobrīd nav pieejamu nodarbību.</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
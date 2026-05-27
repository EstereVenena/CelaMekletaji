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

    // SVARĪGI: fails atrodas kā student/lessons.php, nevis lessons/index.php
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
       Pārbauda vietu limitu.
    */
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

    /*
       Saglabā pieteikumu.
    */
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
    /*
       Ja pieteikumu tabulas vēl nav, nodarbības tāpat parāda,
       bet pieteikšanās pogu atslēdz.
    */
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
                        $lessonId = (int) ($lesson["id"] ?? 0);
                        $alreadyApplied = (int) ($lesson["user_applied"] ?? 0) > 0;
                        $appliedCount = (int) ($lesson["applied_count"] ?? 0);

                        $maxParticipants = $lesson["max_participants"] ?? null;
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
                            <?php if (!$applicationsTableExists): ?>

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
                                    <input 
                                        type="hidden" 
                                        name="lesson_id" 
                                        value="<?= $lessonId ?>"
                                    >

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
                <p class="muted">
                    Šobrīd nav pieejamu nodarbību.
                </p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
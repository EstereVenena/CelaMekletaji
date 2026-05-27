<?php
session_start();

$lapa  = "Mani pieteikumi";
$title = "Mani pieteikumi - Ceļa meklētāji";

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

$lessonApplications = [];
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

function statusLabel(string $status): string
{
    return match ($status) {
        "pieteikts" => "Pieteikts",
        "atcelts" => "Atcelts",
        "apstiprināts", "apstiprinats" => "Apstiprināts",
        "atteikts" => "Atteikts",
        default => ucfirst($status ?: "—"),
    };
}

function statusClass(string $status): string
{
    return match ($status) {
        "pieteikts", "apstiprināts", "apstiprinats" => "badge badge-blue",
        "atcelts", "atteikts" => "badge badge-gold",
        default => "badge",
    };
}

function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: applications.php?" . $param . "=" . urlencode($message));
    exit();
}

/* ===============================
   PIETEIKUMA ATCELŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["cancel_lesson_application_id"])) {
    $applicationId = (int) $_POST["cancel_lesson_application_id"];

    if ($applicationId <= 0) {
        redirectWithMessage("error", "Nederīgs pieteikums.");
    }

    $sql = "
        UPDATE cm_lesson_applications
        SET status = 'atcelts'
        WHERE id = ?
          AND user_id = ?
          AND status = 'pieteikts'
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($sql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot pieteikuma atcelšanu.");
    }

    $stmt->bind_param("ii", $applicationId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        redirectWithMessage("success", "Pieteikums veiksmīgi atcelts.");
    }

    $stmt->close();
    redirectWithMessage("error", "Pieteikumu neizdevās atcelt vai tas jau ir atcelts.");
}

/* ===============================
   NODARBĪBU PIETEIKUMI
================================ */
$sql = "
    SELECT
        la.id AS application_id,
        la.status,
        la.applied_at,
        l.id AS lesson_id,
        l.title,
        l.description,
        l.lesson_date,
        l.lesson_time,
        l.location
    FROM cm_lesson_applications la
    INNER JOIN cm_lessons l ON l.id = la.lesson_id
    WHERE la.user_id = ?
    ORDER BY la.applied_at DESC, l.lesson_date DESC
";

$stmt = $savienojums->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $lessonApplications[] = $row;
    }

    $stmt->close();
} else {
    $error = "Neizdevās ielādēt pieteikumus. Pārbaudi, vai eksistē tabula cm_lesson_applications.";
}

require __DIR__ . "/../includes/templates/header-student.php";
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <div class="page-hero-content">
            <h1>Mani pieteikumi</h1>
            <p class="lead">
                Šeit redzamas nodarbības, kurām esi pieteicies.
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

        <div class="section-title-row" style="margin-bottom:1rem;">
            <div>
                <h2>Aktīvie un iepriekšējie pieteikumi</h2>
                <p class="muted">Atcelšana pieejama tikai pieteikumiem ar statusu “Pieteikts”.</p>
            </div>

            <a class="btn btn-primary btn-sm" href="lessons.php">
                Skatīt nodarbības
            </a>
        </div>

        <?php if (!empty($lessonApplications)): ?>
            <div class="lessons-grid">

                <?php foreach ($lessonApplications as $app): ?>
                    <?php
                        $status = (string) ($app["status"] ?? "");
                        $canCancel = $status === "pieteikts";
                        $description = trim($app["description"] ?? "");
                        if ($description === "") {
                            $description = "Apraksts nav pievienots.";
                        }
                    ?>

                    <article class="card news-card-page lesson-card">
                        <div class="news-meta">
                            <span class="<?= htmlspecialchars(statusClass($status)) ?>">
                                <?= htmlspecialchars(statusLabel($status)) ?>
                            </span>

                            <span class="news-date">
                                Pieteikts:
                                <?= htmlspecialchars(formatDateLv($app["created_at"] ?? null)) ?>
                            </span>
                        </div>

                        <h2 class="news-card-title-page">
                            <?= htmlspecialchars($app["title"] ?? "Bez nosaukuma") ?>
                        </h2>

                        <p class="muted small">
                            <strong>Datums:</strong>
                            <?= htmlspecialchars(formatDateLv($app["lesson_date"] ?? null)) ?>
                            &nbsp;•&nbsp;
                            <strong>Laiks:</strong>
                            <?= htmlspecialchars(formatTimeLv($app["lesson_time"] ?? null)) ?>
                            &nbsp;•&nbsp;
                            <strong>Vieta:</strong>
                            <?= htmlspecialchars($app["location"] ?? "—") ?>
                        </p>

                        <p class="muted">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($description, 0, 220, "..."))) ?>
                        </p>

                        <div class="news-actions">
                            <?php if ($canCancel): ?>
                                <form method="post" onsubmit="return confirm('Tiešām atcelt šo pieteikumu?');">
                                    <input
                                        type="hidden"
                                        name="cancel_lesson_application_id"
                                        value="<?= (int) $app["application_id"] ?>"
                                    >

                                    <button class="btn btn-outline btn-sm" type="submit">
                                        Atcelt pieteikumu
                                    </button>
                                </form>
                            <?php else: ?>
                                <button class="btn btn-outline btn-sm" type="button" disabled>
                                    Nav aktīvs
                                </button>
                            <?php endif; ?>
                        </div>
                    </article>

                <?php endforeach; ?>

            </div>
        <?php else: ?>
            <div class="card">
                <h3>Vēl nav pieteikumu</h3>
                <p class="muted">
                    Izskatās, ka vēl neesi pieteicies nevienai nodarbībai.
                </p>

                <a class="btn btn-primary btn-sm" href="lessons.php">
                    Pieteikties nodarbībai
                </a>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>

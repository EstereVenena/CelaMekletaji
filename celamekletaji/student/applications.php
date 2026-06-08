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

    return date("H:i", strtotime($time));
}

function statusLabel(string $status): string
{
    return match ($status) {
        "pieteikts" => "Pieteikts",
        "atcelts" => "Atcelts",
        "apstiprināts", "apstiprinats" => "Apstiprināts",
        "atteikts" => "Atteikts",
        "noraidīts", "noraidits" => "Noraidīts",
        default => ucfirst($status ?: "—"),
    };
}

function statusTone(string $status): string
{
    return match ($status) {
        "pieteikts" => "blue",
        "apstiprināts", "apstiprinats" => "green",
        "atcelts", "atteikts", "noraidīts", "noraidits" => "gold",
        default => "gray",
    };
}

function statusIcon(string $status): string
{
    return match ($status) {
        "pieteikts" => "fa-paper-plane",
        "apstiprināts", "apstiprinats" => "fa-circle-check",
        "atcelts" => "fa-circle-xmark",
        "atteikts", "noraidīts", "noraidits" => "fa-ban",
        default => "fa-circle-info",
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
   PIETEIKTIES VĒLREIZ
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reapply_lesson_application_id"])) {
    $applicationId = (int) $_POST["reapply_lesson_application_id"];

    if ($applicationId <= 0) {
        redirectWithMessage("error", "Nederīgs pieteikums.");
    }

    $sql = "
        UPDATE cm_lesson_applications
        SET status = 'pieteikts',
            applied_at = NOW()
        WHERE id = ?
          AND user_id = ?
          AND status IN ('atcelts', 'atteikts', 'noraidīts', 'noraidits')
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($sql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot atkārtotu pieteikšanos.");
    }

    $stmt->bind_param("ii", $applicationId, $userId);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $stmt->close();
        redirectWithMessage("success", "Pieteikšanās veiksmīgi atjaunota.");
    }

    $stmt->close();
    redirectWithMessage("error", "Pieteikumu neizdevās atjaunot vai tas jau ir aktīvs.");
}

/* ===============================
   NODARBĪBU PIETEIKUMI
   RĀDA VISUS STATUSUS
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

$applicationsCount = count($lessonApplications);

require __DIR__ . "/../includes/templates/header-student.php";
?>

<style>
.student-applications-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.applications-hero {
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

.applications-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.applications-hero > * {
    position: relative;
    z-index: 1;
}

.applications-kicker {
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

.applications-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.applications-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.applications-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.applications-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.applications-hero-card strong {
    display: block;
    font-size: 2.1rem;
    line-height: 1;
    color: #f4c430;
}

.applications-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.application-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 800;
}

.application-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.application-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.applications-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.applications-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.applications-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.applications-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
}

.applications-count {
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

.applications-list {
    display: grid;
    gap: .95rem;
}

.application-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 20px;
    background: #f8fbff;
    transition: .2s ease;
}

.application-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.application-card h3 {
    margin: 0 0 .45rem;
    color: #101828;
    font-size: 1.12rem;
    line-height: 1.25;
}

.application-meta {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    margin-bottom: .65rem;
}

.application-pill {
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

.application-pill i {
    color: #1e4fa1;
}

.application-description {
    margin: .65rem 0 0;
    color: #667085;
    line-height: 1.55;
}

.application-side {
    width: 245px;
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

.application-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    padding: .75rem .9rem;
    border-radius: 999px;
    font-weight: 950;
    text-align: center;
}

.application-status.blue {
    background: #eef3ff;
    color: #173f84;
}

.application-status.green {
    background: #ecfff4;
    color: #17633a;
}

.application-status.gold {
    background: #fff8e6;
    color: #7a5517;
}

.application-status.gray {
    background: #f2f4f7;
    color: #667085;
}

.application-applied {
    display: grid;
    gap: .25rem;
    color: #667085;
    font-size: .9rem;
    font-weight: 800;
}

.application-applied strong {
    color: #173f84;
}

.application-side form {
    margin: 0;
}

.application-side .btn {
    width: 100%;
}

.application-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.application-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

@media (max-width: 900px) {
    .applications-hero,
    .application-card {
        grid-template-columns: 1fr;
    }

    .applications-panel-head {
        flex-direction: column;
    }

    .application-side {
        width: 100%;
    }
}

@media (max-width: 640px) {
    .student-applications-page {
        padding: 1.5rem 0 2.5rem;
    }

    .applications-hero,
    .applications-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .applications-hero-actions .btn,
    .application-side .btn {
        width: 100%;
    }
}
</style>

<main class="student-applications-page">
    <div class="container">

        <section class="applications-hero">
            <div>
                <div class="applications-kicker">
                    <i class="fas fa-clipboard-check"></i>
                    Pieteikumu pārskats
                </div>

                <h1>Mani pieteikumi</h1>

                <p>
                    Šeit redzami visi tavi nodarbību pieteikumi — aktīvie, apstiprinātie,
                    atceltie un atteiktie.
                </p>

                <div class="applications-hero-actions">
                    <a class="btn btn-primary btn-sm" href="lessons.php">
                        <i class="fas fa-book-open"></i>
                        Skatīt nodarbības
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/student.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="applications-hero-card">
                <strong><?= (int)$applicationsCount; ?></strong>
                <span>
                    Kopējais pieteikumu skaits tavā profilā.
                </span>
            </aside>
        </section>

        <?php if (!empty($success)): ?>
            <div class="application-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="application-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="applications-panel">
            <div class="applications-panel-head">
                <div>
                    <h2>Aktīvie un iepriekšējie pieteikumi</h2>
                    <p>
                        Aktīvus pieteikumus vari atcelt, bet atceltus vai atteiktus pieteikumus vari iesniegt vēlreiz.
                    </p>
                </div>

                <div class="applications-count">
                    <i class="fas fa-list-check"></i>
                    Kopā: <?= (int)$applicationsCount; ?>
                </div>
            </div>

            <?php if (!empty($lessonApplications)): ?>
                <div class="applications-list">

                    <?php foreach ($lessonApplications as $app): ?>
                        <?php
                            $status = (string) ($app["status"] ?? "");
                            $tone = statusTone($status);
                            $icon = statusIcon($status);

                            $canCancel = $status === "pieteikts";
                            $canReapply = in_array($status, ["atcelts", "atteikts", "noraidīts", "noraidits"], true);

                            $description = trim($app["description"] ?? "");
                            if ($description === "") {
                                $description = "Apraksts nav pievienots.";
                            }

                            $shortDescription = function_exists("mb_strimwidth")
                                ? mb_strimwidth($description, 0, 240, "...")
                                : (strlen($description) > 240 ? substr($description, 0, 240) . "..." : $description);
                        ?>

                        <article class="application-card">
                            <div>
                                <h3>
                                    <?= htmlspecialchars($app["title"] ?? "Bez nosaukuma"); ?>
                                </h3>

                                <div class="application-meta">
                                    <span class="application-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatDateLv($app["lesson_date"] ?? null)); ?>
                                    </span>

                                    <span class="application-pill">
                                        <i class="fas fa-clock"></i>
                                        <?= htmlspecialchars(formatTimeLv($app["lesson_time"] ?? null)); ?>
                                    </span>

                                    <span class="application-pill">
                                        <i class="fas fa-location-dot"></i>
                                        <?= htmlspecialchars($app["location"] ?? "—"); ?>
                                    </span>
                                </div>

                                <p class="application-description">
                                    <?= nl2br(htmlspecialchars($shortDescription)); ?>
                                </p>
                            </div>

                            <aside class="application-side">
                                <div class="application-status <?= htmlspecialchars($tone); ?>">
                                    <i class="fas <?= htmlspecialchars($icon); ?>"></i>
                                    <?= htmlspecialchars(statusLabel($status)); ?>
                                </div>

                                <div class="application-applied">
                                    <span>Pieteikts</span>
                                    <strong><?= htmlspecialchars(formatDateTimeLv($app["applied_at"] ?? null)); ?></strong>
                                </div>

                                <?php if ($canCancel): ?>

                                    <form method="post" onsubmit="return confirm('Tiešām atcelt šo pieteikumu?');">
                                        <input
                                            type="hidden"
                                            name="cancel_lesson_application_id"
                                            value="<?= (int) $app["application_id"]; ?>"
                                        >

                                        <button class="btn btn-outline btn-sm" type="submit">
                                            <i class="fas fa-xmark"></i>
                                            Atcelt pieteikumu
                                        </button>
                                    </form>

                                <?php elseif ($canReapply): ?>

                                    <form method="post" onsubmit="return confirm('Vai pieteikties vēlreiz?');">
                                        <input
                                            type="hidden"
                                            name="reapply_lesson_application_id"
                                            value="<?= (int) $app["application_id"]; ?>"
                                        >

                                        <button class="btn btn-primary btn-sm" type="submit">
                                            <i class="fas fa-rotate-right"></i>
                                            Pieteikties vēlreiz
                                        </button>
                                    </form>

                                <?php else: ?>

                                    <button class="btn btn-outline btn-sm" type="button" disabled>
                                        Nav aktīvs
                                    </button>

                                <?php endif; ?>
                            </aside>
                        </article>

                    <?php endforeach; ?>

                </div>
            <?php else: ?>
                <div class="application-empty">
                    <h3>Vēl nav pieteikumu</h3>
                    <p>
                        Izskatās, ka vēl neesi pieteicies nevienai nodarbībai.
                    </p>

                    <a class="btn btn-primary btn-sm" href="lessons.php">
                        <i class="fas fa-paper-plane"></i>
                        Pieteikties nodarbībai
                    </a>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
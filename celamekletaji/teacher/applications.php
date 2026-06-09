<?php
session_start();

$lapa  = "Pieteikumi";
$title = "Pieteikumi - Ceļa meklētāji";

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
$applications = [];

$selectedEventId = (int)($_GET["event_id"] ?? 0);

$stats = [
    "all" => 0,
    "pieteikts" => 0,
    "apstiprināts" => 0,
    "atteikts" => 0,
    "atcelts" => 0
];

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

function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00" || $date === "0000-00-00 00:00:00") {
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
   STATUSA MAIŅA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "update_status") {
    $applicationId = (int)($_POST["application_id"] ?? 0);
    $newStatus = trim($_POST["status"] ?? "");

    $allowedStatuses = ["pieteikts", "apstiprināts", "atteikts", "atcelts"];

    if ($applicationId <= 0) {
        $error = "Nederīgs pieteikuma ID.";
    } elseif (!in_array($newStatus, $allowedStatuses, true)) {
        $error = "Nederīgs pieteikuma statuss.";
    } else {
        $sqlUpdate = "
            UPDATE cm_event_applications ea
            INNER JOIN cm_lietotaji child ON ea.child_id = child.lietotajs_id
            SET ea.status = ?
            WHERE ea.id = ?
              AND child.club_id = ?
            LIMIT 1
        ";

        if ($stmt = $savienojums->prepare($sqlUpdate)) {
            $stmt->bind_param("sii", $newStatus, $applicationId, $teacherClubId);

            if ($stmt->execute()) {
                $success = "Pieteikuma statuss veiksmīgi atjaunots.";
            } else {
                $error = "Neizdevās atjaunot pieteikuma statusu.";
            }

            $stmt->close();
        } else {
            $error = "Neizdevās sagatavot statusa maiņas vaicājumu.";
        }
    }
}

/* ===============================
   PIETEIKUMU SARAKSTS
================================ */
if ($teacherClubId <= 0) {
    $error = "Skolotājam nav piesaistīts klubs. Sazinies ar administratoru vai direktoru.";
} else {
    $where = "
        child.club_id = ?
        AND child.statuss <> 'dzēsts'
    ";

    $types = "i";
    $params = [$teacherClubId];

    if ($selectedEventId > 0) {
        $where .= " AND e.id = ?";
        $types .= "i";
        $params[] = $selectedEventId;
    }

    $sqlApplications = "
        SELECT
            ea.id AS application_id,
            ea.event_id,
            ea.child_id,
            ea.status,
            ea.applied_at,

            child.lietotajvards AS child_username,
            child.vards AS child_name,
            child.uzvards AS child_surname,
            child.epasts AS child_email,

            e.title AS event_title,
            e.start_date,
            e.end_date,
            e.start_time,
            e.end_time,
            e.location,
            e.event_type
        FROM cm_event_applications ea
        INNER JOIN cm_lietotaji child ON ea.child_id = child.lietotajs_id
        INNER JOIN cm_events e ON ea.event_id = e.id
        WHERE {$where}
        ORDER BY ea.applied_at DESC, ea.id DESC
    ";

    if ($stmt = $savienojums->prepare($sqlApplications)) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $applications[] = $row;

            $stats["all"]++;

            $status = $row["status"] ?? "pieteikts";

            if (isset($stats[$status])) {
                $stats[$status]++;
            }
        }

        $stmt->close();
    } else {
        $error = "Neizdevās ielādēt pieteikumus.";
    }
}

require __DIR__ . "/../includes/templates/header-teacher.php";
?>

<style>
.teacher-applications-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30,79,161,.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244,196,48,.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.teacher-applications-hero {
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

.teacher-applications-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.teacher-applications-hero > * {
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

.teacher-applications-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.teacher-applications-hero p {
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

.teacher-stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: .9rem;
}

.teacher-stat-card {
    position: relative;
    overflow: hidden;
    padding: 1.1rem;
    border-radius: 22px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    box-shadow: 0 10px 24px rgba(16,24,40,.04);
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

.teacher-table-wrap {
    overflow-x: auto;
    border-radius: 18px;
    border: 1px solid #edf2fb;
    background: #fff;
}

.teacher-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 980px;
}

.teacher-table th,
.teacher-table td {
    padding: .9rem 1rem;
    text-align: left;
    border-bottom: 1px solid #edf2fb;
    vertical-align: middle;
}

.teacher-table th {
    background: #f8fbff;
    color: #173f84;
    font-weight: 950;
}

.teacher-table tr:last-child td {
    border-bottom: none;
}

.teacher-user-name {
    font-weight: 950;
    color: #101828;
}

.teacher-muted {
    display: block;
    margin-top: .15rem;
    color: #667085;
    font-size: .88rem;
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

.teacher-status {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem .65rem;
    border-radius: 999px;
    font-size: .84rem;
    font-weight: 900;
}

.teacher-status.pieteikts {
    background: #fff8e6;
    color: #8a650b;
}

.teacher-status.apstiprināts {
    background: #ecfdf3;
    color: #027a48;
}

.teacher-status.atteikts,
.teacher-status.atcelts {
    background: #fff0f0;
    color: #b42318;
}

.teacher-status-form {
    display: flex;
    gap: .45rem;
    align-items: center;
}

.teacher-status-form select {
    border: 1px solid #d0d8e8;
    border-radius: 12px;
    padding: .55rem .65rem;
    background: #fff;
    font-weight: 800;
}

.teacher-status-form button {
    border: none;
    cursor: pointer;
}

.teacher-empty {
    padding: 1.4rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    line-height: 1.6;
}

@media (max-width: 1100px) {
    .teacher-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 900px) {
    .teacher-applications-hero {
        grid-template-columns: 1fr;
    }

    .teacher-panel-head {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .teacher-applications-page {
        padding: 1.5rem 0 2.5rem;
    }

    .teacher-applications-hero,
    .teacher-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .teacher-stats-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="teacher-applications-page">
    <div class="container">

        <section class="teacher-applications-hero">
            <div>
                <div class="teacher-kicker">
                    <i class="fas fa-file-signature"></i>
                    Skolotāja pieteikumi
                </div>

                <h1>Pieteikumi</h1>

                <p>
                    Šeit skolotājs var apskatīt sava kluba bērnu pieteikumus pasākumiem
                    un mainīt pieteikuma statusu.
                </p>
            </div>

            <aside class="teacher-hero-card">
                <strong><?= (int)$stats["all"]; ?></strong>
                <span>Pieteikumi kopā</span>
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
                    <h2>Pieteikumu pārskats</h2>
                    <p>Statusu sadalījums skolotāja klubam.</p>
                </div>

                <a href="../dashboards/teacher.php" class="btn btn-outline btn-sm">
                    <i class="fas fa-arrow-left"></i>
                    Atpakaļ uz paneli
                </a>
            </div>

            <div class="teacher-stats-grid">
                <article class="teacher-stat-card">
                    <span class="teacher-stat-number"><?= (int)$stats["all"]; ?></span>
                    <span class="teacher-stat-label">Kopā</span>
                </article>

                <article class="teacher-stat-card">
                    <span class="teacher-stat-number"><?= (int)$stats["pieteikts"]; ?></span>
                    <span class="teacher-stat-label">Pieteikti</span>
                </article>

                <article class="teacher-stat-card">
                    <span class="teacher-stat-number"><?= (int)$stats["apstiprināts"]; ?></span>
                    <span class="teacher-stat-label">Apstiprināti</span>
                </article>

                <article class="teacher-stat-card">
                    <span class="teacher-stat-number"><?= (int)$stats["atteikts"]; ?></span>
                    <span class="teacher-stat-label">Atteikti</span>
                </article>

                <article class="teacher-stat-card">
                    <span class="teacher-stat-number"><?= (int)$stats["atcelts"]; ?></span>
                    <span class="teacher-stat-label">Atcelti</span>
                </article>
            </div>
        </section>

        <section class="teacher-panel">
            <div class="teacher-panel-head">
                <div>
                    <h2>Pieteikumu saraksts</h2>
                    <p>
                        <?php if ($selectedEventId > 0): ?>
                            Attēloti pieteikumi izvēlētajam pasākumam.
                        <?php else: ?>
                            Attēloti visi kluba bērnu pieteikumi.
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($selectedEventId > 0): ?>
                    <a href="applications.php" class="btn btn-outline btn-sm">
                        Rādīt visus
                    </a>
                <?php endif; ?>
            </div>

            <?php if (empty($applications)): ?>

                <div class="teacher-empty">
                    Pašlaik nav atrasts neviens pieteikums.
                </div>

            <?php else: ?>

                <div class="teacher-table-wrap">
                    <table class="teacher-table">
                        <thead>
                            <tr>
                                <th>Bērns</th>
                                <th>Pasākums</th>
                                <th>Datums</th>
                                <th>Vieta</th>
                                <th>Statuss</th>
                                <th>Pieteikts</th>
                                <th>Mainīt statusu</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($applications as $app): ?>
                                <?php
                                    $childFullName = trim(($app["child_name"] ?? "") . " " . ($app["child_surname"] ?? ""));

                                    if ($childFullName === "") {
                                        $childFullName = $app["child_username"] ?? "—";
                                    }

                                    $status = $app["status"] ?? "pieteikts";
                                ?>

                                <tr>
                                    <td>
                                        <span class="teacher-user-name">
                                            <?= htmlspecialchars($childFullName); ?>
                                        </span>

                                        <span class="teacher-muted">
                                            <?= htmlspecialchars($app["child_email"] ?? ""); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="teacher-user-name">
                                            <?= htmlspecialchars($app["event_title"] ?? "Bez nosaukuma"); ?>
                                        </span>

                                        <span class="teacher-muted">
                                            <?= htmlspecialchars($app["event_type"] ?? "Pasākums"); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="teacher-pill">
                                            <i class="fas fa-calendar-day"></i>
                                            <?= htmlspecialchars(formatDateLv($app["start_date"] ?? null)); ?>
                                        </span>

                                        <?php if (!empty($app["start_time"])): ?>
                                            <span class="teacher-muted">
                                                <?= htmlspecialchars(formatTimeLv($app["start_time"])); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars($app["location"] ?? "—"); ?>
                                    </td>

                                    <td>
                                        <span class="teacher-status <?= htmlspecialchars($status); ?>">
                                            <i class="fas fa-circle"></i>
                                            <?= htmlspecialchars($status); ?>
                                        </span>
                                    </td>

                                    <td>
                                        <?= htmlspecialchars(formatDateLv($app["applied_at"] ?? null)); ?>
                                    </td>

                                    <td>
                                        <form method="post" class="teacher-status-form">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="application_id" value="<?= (int)$app["application_id"]; ?>">

                                            <select name="status">
                                                <option value="pieteikts" <?= $status === "pieteikts" ? "selected" : ""; ?>>pieteikts</option>
                                                <option value="apstiprināts" <?= $status === "apstiprināts" ? "selected" : ""; ?>>apstiprināts</option>
                                                <option value="atteikts" <?= $status === "atteikts" ? "selected" : ""; ?>>atteikts</option>
                                                <option value="atcelts" <?= $status === "atcelts" ? "selected" : ""; ?>>atcelts</option>
                                            </select>

                                            <button type="submit" class="btn btn-primary btn-sm">
                                                Saglabāt
                                            </button>
                                        </form>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
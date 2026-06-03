<?php
session_start();

$lapa  = "Pieteikumi";
$title = "Pieteikumi - Ceļa meklētāji";

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

$applications = [];
$error = trim($_GET["error"] ?? "");
$success = trim($_GET["success"] ?? "");

$statusFilter = trim($_GET["status"] ?? "");
$search = trim($_GET["search"] ?? "");

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: applications.php?" . $param . "=" . urlencode($message));
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

/* ===============================
   TABULU PĀRBAUDE
================================ */
$eventsTableExists = tableExists($savienojums, "cm_events");
$appTableExists = tableExists($savienojums, "cm_event_applications");

$hasEventClubId = $eventsTableExists && tableColumnExists($savienojums, "cm_events", "club_id");
$hasEventCreatedBy = $eventsTableExists && tableColumnExists($savienojums, "cm_events", "created_by");

$hasChildId = $appTableExists && tableColumnExists($savienojums, "cm_event_applications", "child_id");
$hasUserId = $appTableExists && tableColumnExists($savienojums, "cm_event_applications", "user_id");
$hasAppliedAt = $appTableExists && tableColumnExists($savienojums, "cm_event_applications", "applied_at");

if (!$eventsTableExists) {
    $error = "Tabula cm_events nav atrasta.";
}

if (!$appTableExists) {
    $error = "Tabula cm_event_applications nav atrasta.";
}

if (!$hasChildId && !$hasUserId) {
    $error = "Pieteikumu tabulā nav atrasta child_id vai user_id kolonna.";
}

if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs. Pārbaudi lietotāja club_id datubāzē.";
}

/* ===============================
   STATUSA MAIŅA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["action"] ?? "") === "update_status" && empty($error)) {
    $applicationId = (int)($_POST["application_id"] ?? 0);
    $newStatus = trim($_POST["status"] ?? "");

    $allowedStatuses = ["pieteikts", "apstiprināts", "apstiprinats", "atcelts", "noraidīts", "noraidits"];

    if ($applicationId <= 0) {
        redirectWithMessage("error", "Nederīgs pieteikuma ID.");
    }

    if (!in_array($newStatus, $allowedStatuses, true)) {
        redirectWithMessage("error", "Nederīgs pieteikuma statuss.");
    }

    if ($hasEventClubId) {
        $sql = "
            UPDATE cm_event_applications ea
            INNER JOIN cm_events e ON e.id = ea.event_id
            SET ea.status = ?
            WHERE ea.id = ?
              AND e.club_id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sii", $newStatus, $applicationId, $directorClubId);
        }
    } else {
        $sql = "
            UPDATE cm_event_applications ea
            INNER JOIN cm_events e ON e.id = ea.event_id
            SET ea.status = ?
            WHERE ea.id = ?
              AND e.created_by = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if ($stmt) {
            $stmt->bind_param("sii", $newStatus, $applicationId, $directorId);
        }
    }

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot statusa maiņu.");
    }

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage("success", "Pieteikuma statuss veiksmīgi mainīts.");
    }

    $stmt->close();
    redirectWithMessage("error", "Neizdevās mainīt pieteikuma statusu.");
}

/* ===============================
   PIETEIKUMU SARAKSTS
================================ */
if (empty($error)) {
    $appUserColumn = $hasChildId ? "ea.child_id" : "ea.user_id";
    $appliedAtSelect = $hasAppliedAt ? "ea.applied_at" : "NULL AS applied_at";

    $where = "WHERE 1";
    $params = [];
    $types = "";

    if ($hasEventClubId) {
        $where .= " AND e.club_id = ?";
        $params[] = $directorClubId;
        $types .= "i";
    } elseif ($hasEventCreatedBy) {
        $where .= " AND e.created_by = ?";
        $params[] = $directorId;
        $types .= "i";
    }

    $allowedFilterStatuses = ["pieteikts", "apstiprināts", "apstiprinats", "atcelts", "noraidīts", "noraidits"];

    if ($statusFilter !== "" && in_array($statusFilter, $allowedFilterStatuses, true)) {
        $where .= " AND ea.status = ?";
        $params[] = $statusFilter;
        $types .= "s";
    }

    if ($search !== "") {
        $where .= "
            AND (
                e.title LIKE ?
                OR u.vards LIKE ?
                OR u.uzvards LIKE ?
                OR u.lietotajvards LIKE ?
                OR u.epasts LIKE ?
            )
        ";

        $searchLike = "%" . $search . "%";

        for ($i = 0; $i < 5; $i++) {
            $params[] = $searchLike;
            $types .= "s";
        }
    }

    $sql = "
        SELECT
            ea.id AS application_id,
            ea.event_id,
            ea.status AS application_status,
            $appliedAtSelect,
            e.title AS event_title,
            e.start_date,
            e.end_date,
            e.start_time,
            e.end_time,
            e.location,
            e.event_type,
            u.lietotajs_id,
            u.lietotajvards,
            u.vards,
            u.uzvards,
            u.epasts,
            u.loma
        FROM cm_event_applications ea
        INNER JOIN cm_events e ON e.id = ea.event_id
        LEFT JOIN cm_lietotaji u ON u.lietotajs_id = $appUserColumn
        $where
        ORDER BY 
            CASE ea.status
                WHEN 'pieteikts' THEN 1
                WHEN 'apstiprināts' THEN 2
                WHEN 'apstiprinats' THEN 2
                WHEN 'noraidīts' THEN 3
                WHEN 'noraidits' THEN 3
                WHEN 'atcelts' THEN 4
                ELSE 5
            END,
            e.start_date ASC,
            ea.id DESC
    ";

    $stmt = $savienojums->prepare($sql);

    if ($stmt) {
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $applications[] = $row;
        }

        $stmt->close();
    } else {
        $error = "Neizdevās ielādēt pieteikumus.";
    }
}

$applicationsCount = count($applications);

require __DIR__ . "/../includes/templates/header-director.php";
?>

<style>
.director-applications-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-applications-hero {
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

.director-applications-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-applications-hero > * {
    position: relative;
    z-index: 1;
}

.director-applications-kicker {
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

.director-applications-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-applications-hero p {
    max-width: 740px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.director-applications-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.director-applications-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.director-applications-hero-card strong {
    display: block;
    font-size: 2.2rem;
    color: #f4c430;
    line-height: 1;
}

.director-applications-hero-card span {
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

.director-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.director-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.director-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.director-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
    line-height: 1.6;
}

.director-filter-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.director-filter-tabs {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
}

.director-filter-tab {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .7rem .95rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    text-decoration: none;
    font-weight: 900;
    transition: .2s ease;
}

.director-filter-tab:hover {
    background: #dfeaff;
    transform: translateY(-1px);
}

.director-filter-tab.is-active {
    background: linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
    box-shadow: 0 12px 26px rgba(23, 63, 132, 0.16);
}

.director-search-form {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
}

.director-search-input {
    min-width: 240px;
    padding: .78rem .95rem;
    border-radius: 999px;
    border: 1px solid #d0d8e8;
    outline: none;
    font: inherit;
}

.director-search-input:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.director-app-list {
    display: grid;
    gap: .9rem;
}

.director-app-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    padding: 1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.director-app-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.director-app-card h3 {
    margin: 0 0 .45rem;
    color: #101828;
    font-size: 1.08rem;
}

.director-app-person {
    margin: 0 0 .7rem;
    color: #667085;
    line-height: 1.55;
}

.director-app-meta {
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

.director-pill.status-pieteikts {
    background: #fff8e6;
    color: #7a5517;
}

.director-pill.status-apstiprinats {
    background: #ecfff4;
    color: #17633a;
}

.director-pill.status-atcelts,
.director-pill.status-noraidits {
    background: #fff0f0;
    color: #9b1c1c;
}

.director-app-side {
    width: 220px;
    display: grid;
    gap: .55rem;
    align-content: start;
}

.director-status-form {
    display: grid;
    gap: .55rem;
}

.director-select {
    width: 100%;
    padding: .75rem .85rem;
    border-radius: 14px;
    border: 1px solid #d0d8e8;
    background: #fff;
    color: #101828;
    font: inherit;
    outline: none;
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

@media (max-width: 900px) {
    .director-applications-hero,
    .director-app-card {
        grid-template-columns: 1fr;
    }

    .director-panel-head,
    .director-filter-row {
        flex-direction: column;
    }

    .director-app-side {
        width: 100%;
    }
}

@media (max-width: 640px) {
    .director-applications-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-applications-hero,
    .director-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-applications-actions .btn,
    .director-search-form,
    .director-search-form .btn,
    .director-search-input {
        width: 100%;
    }
}
</style>

<main class="director-applications-page">
    <div class="container">

        <section class="director-applications-hero">
            <div>
                <div class="director-applications-kicker">
                    <i class="fas fa-file-signature"></i>
                    Kluba pieteikumi
                </div>

                <h1>Pieteikumi</h1>

                <p>
                    Pārskati skolēnu pieteikumus uz sava kluba aktivitātēm un maini to statusu.
                    Šeit tiek rādīti tikai direktora klubam piesaistītie pieteikumi.
                </p>

                <div class="director-applications-actions">
                    <a class="btn btn-primary btn-sm" href="activities.php">
                        <i class="fas fa-calendar-days"></i>
                        Aktivitātes
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/director.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="director-applications-hero-card">
                <strong><?= (int)$applicationsCount; ?></strong>
                <span>Pieteikumi šajā skatā.</span>
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

        <section class="director-panel">
            <div class="director-panel-head">
                <div>
                    <h2>Pieteikumu saraksts</h2>
                    <p>Filtrē pēc statusa vai meklē pēc dalībnieka un aktivitātes nosaukuma.</p>
                </div>
            </div>

            <div class="director-filter-row">
                <div class="director-filter-tabs">
                    <a href="applications.php" class="director-filter-tab <?= $statusFilter === '' ? 'is-active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        Visi
                    </a>

                    <a href="applications.php?status=pieteikts" class="director-filter-tab <?= $statusFilter === 'pieteikts' ? 'is-active' : ''; ?>">
                        <i class="fas fa-clock"></i>
                        Pieteikti
                    </a>

                    <a href="applications.php?status=apstiprināts" class="director-filter-tab <?= in_array($statusFilter, ['apstiprināts', 'apstiprinats'], true) ? 'is-active' : ''; ?>">
                        <i class="fas fa-circle-check"></i>
                        Apstiprināti
                    </a>

                    <a href="applications.php?status=atcelts" class="director-filter-tab <?= $statusFilter === 'atcelts' ? 'is-active' : ''; ?>">
                        <i class="fas fa-ban"></i>
                        Atcelti
                    </a>
                </div>

                <form method="get" class="director-search-form">
                    <?php if ($statusFilter !== ""): ?>
                        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter); ?>">
                    <?php endif; ?>

                    <input
                        class="director-search-input"
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search); ?>"
                        placeholder="Meklēt pieteikumu..."
                    >

                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fas fa-magnifying-glass"></i>
                        Meklēt
                    </button>

                    <?php if ($search !== ""): ?>
                        <a class="btn btn-outline btn-sm" href="applications.php<?= $statusFilter ? '?status=' . urlencode($statusFilter) : ''; ?>">
                            Notīrīt
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($applications)): ?>
                <div class="director-app-list">
                    <?php foreach ($applications as $app): ?>
                        <?php
                            $fullName = trim(($app["vards"] ?? "") . " " . ($app["uzvards"] ?? ""));
                            $displayName = $fullName !== "" ? $fullName : ($app["lietotajvards"] ?? "Lietotājs");

                            $status = trim($app["application_status"] ?? "—");
                            $statusClass = str_replace(
                                ["ā", "ī", "ē", "ģ", "ķ", "ļ", "ņ", "š", "ū", "ž"],
                                ["a", "i", "e", "g", "k", "l", "n", "s", "u", "z"],
                                mb_strtolower($status)
                            );
                        ?>

                        <article class="director-app-card">
                            <div>
                                <h3><?= htmlspecialchars($app["event_title"] ?? "Aktivitāte"); ?></h3>

                                <p class="director-app-person">
                                    <strong><?= htmlspecialchars($displayName); ?></strong>
                                    · <?= htmlspecialchars($app["epasts"] ?? "Nav e-pasta"); ?>
                                </p>

                                <div class="director-app-meta">
                                    <span class="director-pill type">
                                        <i class="fas fa-tag"></i>
                                        <?= htmlspecialchars($app["event_type"] ?? "pasākums"); ?>
                                    </span>

                                    <span class="director-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatDateLv($app["start_date"] ?? null)); ?>
                                    </span>

                                    <span class="director-pill">
                                        <i class="fas fa-clock"></i>
                                        <?= htmlspecialchars(formatTimeRangeLv($app["start_time"] ?? null, $app["end_time"] ?? null)); ?>
                                    </span>

                                    <span class="director-pill">
                                        <i class="fas fa-location-dot"></i>
                                        <?= htmlspecialchars($app["location"] ?? "—"); ?>
                                    </span>

                                    <span class="director-pill status-<?= htmlspecialchars($statusClass); ?>">
                                        <i class="fas fa-circle-info"></i>
                                        <?= htmlspecialchars($status); ?>
                                    </span>

                                    <span class="director-pill">
                                        <i class="fas fa-inbox"></i>
                                        <?= htmlspecialchars(formatDateTimeLv($app["applied_at"] ?? null)); ?>
                                    </span>
                                </div>
                            </div>

                            <aside class="director-app-side">
                                <form method="post" class="director-status-form">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="application_id" value="<?= (int)$app["application_id"]; ?>">

                                    <select class="director-select" name="status">
                                        <?php
                                            $statuses = ["pieteikts", "apstiprināts", "noraidīts", "atcelts"];
                                        ?>

                                        <?php foreach ($statuses as $itemStatus): ?>
                                            <option value="<?= htmlspecialchars($itemStatus); ?>" <?= $status === $itemStatus ? "selected" : ""; ?>>
                                                <?= htmlspecialchars(ucfirst($itemStatus)); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" class="btn btn-primary btn-sm">
                                        Saglabāt statusu
                                    </button>
                                </form>

                                <?php if (!empty($app["lietotajs_id"])): ?>
                                    <a class="btn btn-outline btn-sm" href="user_view.php?id=<?= (int)$app["lietotajs_id"]; ?>">
                                        Skatīt lietotāju
                                    </a>
                                <?php endif; ?>
                            </aside>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="director-empty">
                    <h3>Nav atrasts neviens pieteikums</h3>
                    <p>Šajā skatā vēl nav pieteikumu vai filtrs neko neatrada.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
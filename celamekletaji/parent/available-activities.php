<?php
session_start();

$lapa  = "Pieejamās aktivitātes";
$title = "Pieejamās aktivitātes - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION["lietotajs_id"];

$error = null;
$success = null;

$children = [];
$events = [];

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

/* ===============================
   PALĪGFUNKCIJAS
================================ */

function formatEventDateRange(?string $startDate, ?string $endDate): string
{
    if (empty($startDate)) {
        return "—";
    }

    $start = date("d.m.Y", strtotime($startDate));

    if (!empty($endDate) && $endDate !== $startDate) {
        $end = date("d.m.Y", strtotime($endDate));
        return $start . " - " . $end;
    }

    return $start;
}

function formatEventTimeRange(?string $startTime, ?string $endTime): string
{
    if (empty($startTime)) {
        return "";
    }

    $start = substr($startTime, 0, 5);

    if (!empty($endTime) && $endTime !== $startTime) {
        $end = substr($endTime, 0, 5);
        return "plkst. " . $start . " - " . $end;
    }

    return "plkst. " . $start;
}

/* ===============================
   VECĀKA BĒRNI
================================ */

$childrenSql = "
    SELECT 
        c.lietotajs_id,
        c.vards,
        c.uzvards,
        c.lietotajvards
    FROM cm_parent_children pc
    INNER JOIN cm_lietotaji c
        ON c.lietotajs_id = pc.child_id
    WHERE pc.parent_id = ?
      AND c.statuss <> 'dzēsts'
    ORDER BY c.vards ASC, c.uzvards ASC
";

if ($stmt = $savienojums->prepare($childrenSql)) {
    $stmt->bind_param("i", $parentId);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $children[] = $row;
    }

    $stmt->close();
} else {
    $error = "Neizdevās ielādēt bērnu sarakstu.";
}

/* ===============================
   PIETEIKŠANĀS AKTIVITĀTEI
================================ */

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["apply_event"])) {
    $csrf   = $_POST["csrf_token"] ?? "";
    $eventId = (int) ($_POST["event_id"] ?? 0);
    $childId = (int) ($_POST["child_id"] ?? 0);

    if (!hash_equals($_SESSION["csrf_token"], $csrf)) {
        $error = "Drošības pārbaude neizdevās.";
    } elseif ($eventId <= 0 || $childId <= 0) {
        $error = "Lūdzu izvēlieties bērnu un aktivitāti.";
    } else {
        /* Pārbauda, vai bērns pieder šim vecākam */
        $checkChildSql = "
            SELECT pc.child_id
            FROM cm_parent_children pc
            INNER JOIN cm_lietotaji c
                ON c.lietotajs_id = pc.child_id
            WHERE pc.parent_id = ?
              AND pc.child_id = ?
              AND c.statuss <> 'dzēsts'
            LIMIT 1
        ";

        $childBelongsToParent = false;

        if ($stmt = $savienojums->prepare($checkChildSql)) {
            $stmt->bind_param("ii", $parentId, $childId);
            $stmt->execute();
            $stmt->store_result();

            $childBelongsToParent = $stmt->num_rows > 0;

            $stmt->close();
        }

        if (!$childBelongsToParent) {
            $error = "Šis bērns nav piesaistīts jūsu profilam.";
        }
    }

    if (!$error) {
        /* Pārbauda, vai aktivitāte eksistē un ir aktīva */
        $checkEventSql = "
            SELECT id
            FROM cm_events
            WHERE id = ?
              AND is_active = 1
              AND (
                    start_date >= CURDATE()
                    OR (
                        end_date IS NOT NULL
                        AND end_date >= CURDATE()
                    )
              )
            LIMIT 1
        ";

        $eventIsAvailable = false;

        if ($stmt = $savienojums->prepare($checkEventSql)) {
            $stmt->bind_param("i", $eventId);
            $stmt->execute();
            $stmt->store_result();

            $eventIsAvailable = $stmt->num_rows > 0;

            $stmt->close();
        }

        if (!$eventIsAvailable) {
            $error = "Šī aktivitāte vairs nav pieejama pieteikšanai.";
        }
    }

    if (!$error) {
        /* Pārbauda, vai bērns jau nav pieteikts */
        $checkApplicationSql = "
            SELECT id
            FROM cm_event_applications
            WHERE event_id = ?
              AND child_id = ?
              AND status IN ('pieteikts', 'apstiprināts')
            LIMIT 1
        ";

        $alreadyApplied = false;

        if ($stmt = $savienojums->prepare($checkApplicationSql)) {
            $stmt->bind_param("ii", $eventId, $childId);
            $stmt->execute();
            $stmt->store_result();

            $alreadyApplied = $stmt->num_rows > 0;

            $stmt->close();
        }

        if ($alreadyApplied) {
            $error = "Šis bērns jau ir pieteikts šai aktivitātei.";
        }
    }

    if (!$error) {
        $insertSql = "
            INSERT INTO cm_event_applications
                (event_id, child_id, status, applied_at)
            VALUES
                (?, ?, 'pieteikts', NOW())
        ";

        if ($stmt = $savienojums->prepare($insertSql)) {
            $stmt->bind_param("ii", $eventId, $childId);

            if ($stmt->execute()) {
                $success = "Bērns veiksmīgi pieteikts aktivitātei.";
            } else {
                $error = "Neizdevās pieteikt bērnu aktivitātei.";
            }

            $stmt->close();
        } else {
            $error = "Neizdevās sagatavot pieteikšanās vaicājumu.";
        }
    }
}

/* ===============================
   PIEEJAMĀS AKTIVITĀTES
================================ */

$eventsSql = "
    SELECT
        e.id,
        e.title,
        e.description,
        e.start_date,
        e.end_date,
        e.start_time,
        e.end_time,
        e.location,
        e.event_type,
        e.is_active
    FROM cm_events e
    WHERE e.is_active = 1
      AND (
            e.start_date >= CURDATE()
            OR (
                e.end_date IS NOT NULL
                AND e.end_date >= CURDATE()
            )
      )
    ORDER BY e.start_date ASC, e.start_time ASC
";

if ($result = $savienojums->query($eventsSql)) {
    while ($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
} else {
    $error = "Neizdevās ielādēt pieejamās aktivitātes.";
}

$eventsCount = count($events);

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<style>
.available-activities-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.available-hero {
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

.available-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.available-hero > * {
    position: relative;
    z-index: 1;
}

.available-kicker {
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

.available-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.available-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.available-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.available-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.available-hero-card strong {
    display: block;
    font-size: 2.1rem;
    line-height: 1;
    color: #f4c430;
}

.available-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.available-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 800;
}

.available-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.available-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.available-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.available-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.available-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.available-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
}

.available-count {
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

.available-list {
    display: grid;
    gap: .9rem;
}

.available-card {
    display: grid;
    grid-template-columns: 1fr 320px;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 20px;
    background: #f8fbff;
    transition: .2s ease;
}

.available-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.available-card h3 {
    margin: 0 0 .4rem;
    color: #101828;
    font-size: 1.1rem;
}

.available-meta {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    margin-top: .55rem;
}

.available-pill {
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

.available-pill i {
    color: #1e4fa1;
}

.available-desc {
    margin: .75rem 0 0;
    color: #667085;
    line-height: 1.55;
}

.apply-box {
    align-self: start;
    padding: 1rem;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #edf2fb;
}

.apply-box h4 {
    margin: 0 0 .75rem;
    color: #173f84;
    font-size: 1rem;
}

.apply-form {
    display: grid;
    gap: .75rem;
}

.apply-form select {
    width: 100%;
    padding: .78rem .9rem;
    border-radius: 14px;
    border: 1px solid #d0d8e8;
    background: #fff;
    color: #101828;
    font: inherit;
    outline: none;
}

.apply-form select:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.available-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.available-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

@media (max-width: 980px) {
    .available-hero,
    .available-card {
        grid-template-columns: 1fr;
    }

    .available-panel-head {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .available-activities-page {
        padding: 1.5rem 0 2.5rem;
    }

    .available-hero,
    .available-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .available-hero-actions .btn,
    .apply-form .btn {
        width: 100%;
    }
}
</style>

<main class="available-activities-page">
    <div class="container">

        <section class="available-hero">
            <div>
                <div class="available-kicker">
                    <i class="fas fa-list-check"></i>
                    Aktivitāšu izvēle
                </div>

                <h1>Pieejamās aktivitātes</h1>

                <p>
                    Šeit redzamas visas aktīvās aktivitātes, kurām var pieteikt bērnus.
                    Izvēlies bērnu un piesaki viņu piemērotajai aktivitātei.
                </p>

                <div class="available-hero-actions">
                    <a class="btn btn-primary btn-sm" href="activities.php">
                        <i class="fas fa-calendar-check"></i>
                        Pieteiktās aktivitātes
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/parent.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="available-hero-card">
                <strong><?= (int)$eventsCount; ?></strong>
                <span>
                    Pašlaik pieejamas aktivitātes pieteikšanai.
                </span>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="available-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="available-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <section class="available-panel">
            <div class="available-panel-head">
                <div>
                    <h2>Aktivitāšu saraksts</h2>
                    <p>Izvēlies aktivitāti un piesaki vienu no saviem bērniem.</p>
                </div>

                <div class="available-count">
                    <i class="fas fa-list-check"></i>
                    Kopā: <?= (int)$eventsCount; ?>
                </div>
            </div>

            <?php if (empty($events)): ?>
                <div class="available-empty">
                    <h3>Nav pieejamu aktivitāšu</h3>
                    <p>Pašlaik nav aktīvu aktivitāšu, kurām var pieteikties.</p>
                </div>
            <?php elseif (empty($children)): ?>
                <div class="available-empty">
                    <h3>Nav pievienotu bērnu</h3>
                    <p>Lai pieteiktu aktivitātei, vispirms jāpievieno bērns savam profilam.</p>

                    <a class="btn btn-primary btn-sm" href="children/add.php">
                        <i class="fas fa-child-reaching"></i>
                        Pievienot bērnu
                    </a>
                </div>
            <?php else: ?>
                <div class="available-list">
                    <?php foreach ($events as $event): ?>
                        <?php
                            $timeText = formatEventTimeRange($event["start_time"], $event["end_time"]);
                        ?>

                        <article class="available-card">
                            <div>
                                <h3><?= htmlspecialchars($event["title"] ?? "Aktivitāte"); ?></h3>

                                <div class="available-meta">
                                    <span class="available-pill">
                                        <i class="fas fa-tag"></i>
                                        <?= htmlspecialchars($event["event_type"] ?? "pasākums"); ?>
                                    </span>

                                    <span class="available-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatEventDateRange($event["start_date"], $event["end_date"])); ?>
                                    </span>

                                    <?php if ($timeText !== ""): ?>
                                        <span class="available-pill">
                                            <i class="fas fa-clock"></i>
                                            <?= htmlspecialchars($timeText); ?>
                                        </span>
                                    <?php endif; ?>

                                    <span class="available-pill">
                                        <i class="fas fa-location-dot"></i>
                                        <?= htmlspecialchars($event["location"] ?? "—"); ?>
                                    </span>
                                </div>

                                <?php if (!empty($event["description"])): ?>
                                    <p class="available-desc">
                                        <?= htmlspecialchars($event["description"]); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <aside class="apply-box">
                                <h4>Pieteikt bērnu</h4>

                                <form method="post" class="apply-form">
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>">
                                    <input type="hidden" name="event_id" value="<?= (int)$event["id"]; ?>">

                                    <select name="child_id" required>
                                        <option value="">Izvēlies bērnu</option>

                                        <?php foreach ($children as $child): ?>
                                            <?php
                                                $childName = trim(($child["vards"] ?? "") . " " . ($child["uzvards"] ?? ""));
                                                $childName = $childName !== "" ? $childName : ($child["lietotajvards"] ?? "Bērns");
                                            ?>

                                            <option value="<?= (int)$child["lietotajs_id"]; ?>">
                                                <?= htmlspecialchars($childName); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>

                                    <button type="submit" name="apply_event" class="btn btn-primary btn-sm">
                                        <i class="fas fa-paper-plane"></i>
                                        Pieteikt
                                    </button>
                                </form>
                            </aside>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
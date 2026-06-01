<?php
session_start();

$lapa  = "Direktora panelis";
$title = "Direktora panelis - Ceļa meklētāji";

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

$club = null;
$error = null;

$stats = [
    "children" => 0,
    "parents" => 0,
    "teachers" => 0,
    "directors" => 0,
    "total" => 0,
];

$latestUsers = [];
$latestNews = [];

if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs. Pārbaudi lietotāja club_id datubāzē.";
}

/* ===============================
   DIREKTORA KLUBS
================================ */
if (!$error) {
    $sqlClub = "
        SELECT 
            c.id,
            c.name,
            c.address,
            c.location,
            c.director_id,
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
            c.location,
            c.director_id,
            c.is_active,
            c.created_at,
            ch.name
        LIMIT 1
    ";

    if ($stmt = $savienojums->prepare($sqlClub)) {
        $stmt->bind_param("i", $directorClubId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $club = $result->fetch_assoc();
        } else {
            $error = "Direktoram piesaistītais klubs netika atrasts.";
        }

        $stmt->close();
    } else {
        $error = "Neizdevās sagatavot kluba vaicājumu.";
    }
}

/* ===============================
   STATISTIKA PAR KLUBA LOMĀM
================================ */
if ($club) {
    $clubId = (int)$club["id"];

    $sqlStats = "
        SELECT 
            COALESCE(l.nosaukums, u.loma) AS role_name,
            COUNT(*) AS count_total
        FROM cm_lietotaji u
        LEFT JOIN cm_lomas l ON u.loma_id = l.loma_id
        WHERE u.club_id = ?
          AND u.statuss <> 'dzēsts'
        GROUP BY COALESCE(l.nosaukums, u.loma)
    ";

    if ($stmt = $savienojums->prepare($sqlStats)) {
        $stmt->bind_param("i", $clubId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $role = trim($row["role_name"] ?? "");
            $count = (int)$row["count_total"];

            $stats["total"] += $count;

            if (in_array($role, ["Ceļameklētājs", "Skolēns", "Bērns", "student", "child"], true)) {
                $stats["children"] += $count;
            }

            if (in_array($role, ["Vecāks", "parent"], true)) {
                $stats["parents"] += $count;
            }

            if (in_array($role, ["Skolotājs", "teacher"], true)) {
                $stats["teachers"] += $count;
            }

            if (in_array($role, ["Direktors", "direktors", "director"], true)) {
                $stats["directors"] += $count;
            }
        }

        $stmt->close();
    }

    /* ===============================
       JAUNĀKIE KLUBA LIETOTĀJI
    ================================ */
    $sqlLatestUsers = "
        SELECT 
            u.lietotajs_id,
            u.lietotajvards,
            u.vards,
            u.uzvards,
            u.epasts,
            COALESCE(l.nosaukums, u.loma) AS loma,
            u.statuss,
            u.Reg_datums
        FROM cm_lietotaji u
        LEFT JOIN cm_lomas l ON u.loma_id = l.loma_id
        WHERE u.club_id = ?
          AND u.statuss <> 'dzēsts'
        ORDER BY u.Reg_datums DESC
        LIMIT 6
    ";

    if ($stmt = $savienojums->prepare($sqlLatestUsers)) {
        $stmt->bind_param("i", $clubId);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $latestUsers[] = $row;
        }

        $stmt->close();
    }
}

/* ===============================
   JAUNĀKIE JAUNUMI
================================ */
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

function formatDateLv(?string $date): string
{
    if (empty($date)) {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

require __DIR__ . "/../includes/templates/header-director.php";
?>

<style>
.director-dashboard-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-hero {
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

.director-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-hero > * {
    position: relative;
    z-index: 1;
}

.director-kicker {
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

.director-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-hero p {
    max-width: 740px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.director-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.director-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.director-hero-card strong {
    display: block;
    font-size: 2.05rem;
    line-height: 1.1;
    color: #f4c430;
}

.director-hero-card span {
    display: block;
    margin-top: .5rem;
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
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
    font-weight: 800;
}

.director-panel {
    padding: 1.35rem;
    margin-bottom: 1.2rem;
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

.director-info-grid,
.director-actions-grid,
.director-news-grid {
    display: grid;
    gap: 1rem;
}

.director-info-grid {
    grid-template-columns: repeat(3, 1fr);
}

.director-actions-grid {
    grid-template-columns: repeat(4, 1fr);
}

.director-news-grid {
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
}

.director-info-card,
.director-action-card,
.director-news-card {
    padding: 1.1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.director-info-card:hover,
.director-action-card:hover,
.director-news-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.director-info-icon,
.director-action-icon {
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

.director-info-card h3,
.director-action-card h3,
.director-news-card h3 {
    margin: 0 0 .35rem;
    color: #101828;
    font-size: 1.05rem;
    line-height: 1.3;
}

.director-info-card p,
.director-action-card p,
.director-news-card p {
    margin: 0;
    color: #667085;
    line-height: 1.55;
}

.director-status-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .7rem;
    border-radius: 999px;
    background: #ecfff4;
    color: #17633a;
    font-weight: 950;
}

.director-status-pill.inactive {
    background: #fff8e6;
    color: #7a5517;
}

.director-stats-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: .9rem;
}

.director-stat-card {
    position: relative;
    overflow: hidden;
    padding: 1.1rem;
    border-radius: 22px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    box-shadow: 0 10px 24px rgba(16, 24, 40, 0.04);
}

.director-stat-card::after {
    content: "";
    position: absolute;
    right: -28px;
    top: -28px;
    width: 84px;
    height: 84px;
    border-radius: 50%;
    background: rgba(244,196,48,.22);
}

.director-stat-number {
    position: relative;
    z-index: 1;
    display: block;
    color: #173f84;
    font-size: 2rem;
    font-weight: 1000;
    line-height: 1;
}

.director-stat-label {
    position: relative;
    z-index: 1;
    display: block;
    margin-top: .5rem;
    color: #667085;
    font-weight: 850;
    line-height: 1.35;
}

.director-action-card {
    display: flex;
    flex-direction: column;
    min-height: 220px;
}

.director-action-card p {
    flex: 1;
    margin-bottom: .9rem;
}

.director-table-wrap {
    overflow-x: auto;
    border-radius: 18px;
    border: 1px solid #edf2fb;
    background: #fff;
}

.director-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 760px;
}

.director-table th,
.director-table td {
    padding: .9rem 1rem;
    text-align: left;
    border-bottom: 1px solid #edf2fb;
    vertical-align: middle;
}

.director-table th {
    background: #f8fbff;
    color: #173f84;
    font-weight: 950;
}

.director-table tr:last-child td {
    border-bottom: none;
}

.director-user-name {
    font-weight: 950;
    color: #101828;
}

.director-role-pill {
    display: inline-flex;
    padding: .35rem .65rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-weight: 900;
    font-size: .86rem;
}

.director-empty {
    padding: 1.2rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
}

.director-news-meta {
    display: flex;
    gap: .4rem;
    flex-wrap: wrap;
    margin-bottom: .55rem;
    color: #667085;
    font-size: .86rem;
    font-weight: 800;
}

@media (max-width: 1100px) {
    .director-info-grid,
    .director-actions-grid,
    .director-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 900px) {
    .director-hero {
        grid-template-columns: 1fr;
    }

    .director-panel-head {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .director-dashboard-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-hero,
    .director-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-info-grid,
    .director-actions-grid,
    .director-stats-grid,
    .director-news-grid {
        grid-template-columns: 1fr;
    }

    .director-hero-actions .btn,
    .director-action-card .btn,
    .director-panel-head .btn {
        width: 100%;
    }
}
</style>

<main class="director-dashboard-page">
    <div class="container">

        <section class="director-hero">
            <div>
                <div class="director-kicker">
                    <i class="fas fa-compass"></i>
                    Direktora piekļuve
                </div>

                <h1>Direktora panelis</h1>

                <p>
                    <?php if ($club): ?>
                        Šeit vari pārvaldīt savu klubu, lietotājus, aktivitātes un pārskatīt jaunāko informāciju.
                    <?php else: ?>
                        Direktora pārvaldības sadaļa. Piesaisti direktoram klubu, lai redzētu pilnu informāciju.
                    <?php endif; ?>
                </p>

                <div class="director-hero-actions">
                    <a class="btn btn-primary btn-sm" href="../director/users.php">
                        <i class="fas fa-users"></i>
                        Lietotāji
                    </a>

                    <a class="btn btn-outline btn-sm" href="../director/activities.php">
                        <i class="fas fa-calendar-days"></i>
                        Aktivitātes
                    </a>

                    <a class="btn btn-outline btn-sm" href="../director/add_user.php">
                        <i class="fas fa-user-plus"></i>
                        Pievienot lietotāju
                    </a>
                </div>
            </div>

            <aside class="director-hero-card">
                <strong><?= $club ? htmlspecialchars($club["name"]) : "Nav kluba"; ?></strong>
                <span>
                    <?= $club ? htmlspecialchars($club["address"] ?? "Adrese nav norādīta") : "Direktoram nav piesaistīts klubs."; ?>
                </span>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="director-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($club): ?>

            <section class="director-panel">
                <div class="director-panel-head">
                    <div>
                        <h2><?= htmlspecialchars($club["name"]); ?></h2>
                        <p>Kluba pamatinformācija un piesaistītās programmas.</p>
                    </div>

                    <a class="btn btn-primary btn-sm" href="../director/club.php">
                        <i class="fas fa-gear"></i>
                        Pārvaldīt klubu
                    </a>
                </div>

                <div class="director-info-grid">
                    <article class="director-info-card">
                        <div class="director-info-icon">
                            <i class="fas fa-church"></i>
                        </div>
                        <h3>Draudze</h3>
                        <p><?= htmlspecialchars($club["church_name"] ?? "Nav norādīta"); ?></p>
                    </article>

                    <article class="director-info-card">
                        <div class="director-info-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h3>Programmas</h3>
                        <p><?= htmlspecialchars($club["programs"] ?? "Nav piesaistītas"); ?></p>
                    </article>

                    <article class="director-info-card">
                        <div class="director-info-icon">
                            <i class="fas fa-circle-check"></i>
                        </div>
                        <h3>Statuss</h3>
                        <?php $isActive = ((int)($club["is_active"] ?? 0) === 1); ?>
                        <p>
                            <span class="director-status-pill <?= $isActive ? '' : 'inactive'; ?>">
                                <i class="fas <?= $isActive ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                                <?= $isActive ? "Aktīvs" : "Neaktīvs"; ?>
                            </span>
                        </p>
                    </article>
                </div>
            </section>

            <section class="director-panel">
                <div class="director-panel-head">
                    <div>
                        <h2>Kluba dalībnieku pārskats</h2>
                        <p>Cik lietotāju šobrīd piesaistīti šim klubam.</p>
                    </div>
                </div>

                <div class="director-stats-grid">
                    <article class="director-stat-card">
                        <span class="director-stat-number"><?= (int)$stats["children"]; ?></span>
                        <span class="director-stat-label">Bērni / Ceļameklētāji</span>
                    </article>

                    <article class="director-stat-card">
                        <span class="director-stat-number"><?= (int)$stats["parents"]; ?></span>
                        <span class="director-stat-label">Vecāki</span>
                    </article>

                    <article class="director-stat-card">
                        <span class="director-stat-number"><?= (int)$stats["teachers"]; ?></span>
                        <span class="director-stat-label">Skolotāji</span>
                    </article>

                    <article class="director-stat-card">
                        <span class="director-stat-number"><?= (int)$stats["directors"]; ?></span>
                        <span class="director-stat-label">Direktori</span>
                    </article>

                    <article class="director-stat-card">
                        <span class="director-stat-number"><?= (int)$stats["total"]; ?></span>
                        <span class="director-stat-label">Kopā klubā</span>
                    </article>
                </div>
            </section>

            <section class="director-panel">
                <div class="director-panel-head">
                    <div>
                        <h2>Ātrās darbības</h2>
                        <p>Biežāk izmantotās direktora funkcijas.</p>
                    </div>
                </div>

                <div class="director-actions-grid">
                    <article class="director-action-card">
                        <div class="director-action-icon">
                            <i class="fas fa-child-reaching"></i>
                        </div>
                        <h3>Pārvaldīt bērnus</h3>
                        <p>Apskati, pievieno un labo ceļameklētāju kontus.</p>
                        <a class="btn btn-primary btn-sm" href="../director/users.php?role=children">
                            Atvērt
                        </a>
                    </article>

                    <article class="director-action-card">
                        <div class="director-action-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <h3>Pārvaldīt vecākus</h3>
                        <p>Pārskati vecāku kontus un saistīto informāciju.</p>
                        <a class="btn btn-outline btn-sm" href="../director/users.php?role=parents">
                            Atvērt
                        </a>
                    </article>

                    <article class="director-action-card">
                        <div class="director-action-icon">
                            <i class="fas fa-chalkboard-user"></i>
                        </div>
                        <h3>Pārvaldīt skolotājus</h3>
                        <p>Pievieno vai labo skolotāju kontus savā klubā.</p>
                        <a class="btn btn-outline btn-sm" href="../director/users.php?role=teachers">
                            Atvērt
                        </a>
                    </article>

                    <article class="director-action-card">
                        <div class="director-action-icon">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3>Pievienot lietotāju</h3>
                        <p>Izveido bērna, vecāka vai skolotāja kontu.</p>
                        <a class="btn btn-outline btn-sm" href="../director/add_user.php">
                            Pievienot
                        </a>
                    </article>
                </div>
            </section>

            <section class="director-panel">
                <div class="director-panel-head">
                    <div>
                        <h2>Jaunākie kluba lietotāji</h2>
                        <p>Pēdējie pievienotie lietotāji šajā klubā.</p>
                    </div>

                    <a class="btn btn-outline btn-sm" href="../director/users.php">
                        Skatīt visus
                    </a>
                </div>

                <?php if (empty($latestUsers)): ?>
                    <div class="director-empty">
                        Šim klubam vēl nav piesaistītu lietotāju.
                    </div>
                <?php else: ?>
                    <div class="director-table-wrap">
                        <table class="director-table">
                            <thead>
                                <tr>
                                    <th>Vārds</th>
                                    <th>Loma</th>
                                    <th>E-pasts</th>
                                    <th>Statuss</th>
                                    <th>Pievienots</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($latestUsers as $user): ?>
                                    <?php
                                        $fullName = trim(($user["vards"] ?? "") . " " . ($user["uzvards"] ?? ""));
                                        $displayName = $fullName ?: ($user["lietotajvards"] ?? "—");
                                    ?>

                                    <tr>
                                        <td>
                                            <span class="director-user-name">
                                                <?= htmlspecialchars($displayName); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="director-role-pill">
                                                <?= htmlspecialchars($user["loma"] ?? "—"); ?>
                                            </span>
                                        </td>

                                        <td><?= htmlspecialchars($user["epasts"] ?? "—"); ?></td>
                                        <td><?= htmlspecialchars($user["statuss"] ?? "—"); ?></td>
                                        <td><?= htmlspecialchars(formatDateLv($user["Reg_datums"] ?? null)); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </section>

        <?php endif; ?>

        <section class="director-panel">
            <div class="director-panel-head">
                <div>
                    <h2>Jaunākie jaunumi</h2>
                    <p>Aktuālākie ieraksti mājaslapā.</p>
                </div>
            </div>

            <?php if (empty($latestNews)): ?>
                <div class="director-empty">
                    Pašlaik nav publicētu jaunumu.
                </div>
            <?php else: ?>
                <div class="director-news-grid">
                    <?php foreach ($latestNews as $item): ?>
                        <article class="director-news-card">
                            <div class="director-news-meta">
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
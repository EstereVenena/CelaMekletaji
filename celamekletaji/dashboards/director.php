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
   Ņemam pēc cm_lietotaji.club_id,
   nevis pēc cm_clubs.director_id
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
            COALESCE(l.lomas_nosaukums, l.nosaukums, u.loma) AS role_name,
            COUNT(*) AS count_total
        FROM cm_lietotaji u
        LEFT JOIN cm_lomas l ON u.loma_id = l.loma_id
        WHERE u.club_id = ?
          AND u.statuss <> 'dzēsts'
        GROUP BY COALESCE(l.lomas_nosaukums, l.nosaukums, u.loma)
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
            COALESCE(l.lomas_nosaukums, l.nosaukums, u.loma) AS loma,
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

<main class="dashboard-main">
    <div class="container">

        <div class="dashboard-header">
            <h2>Direktora panelis</h2>

            <?php if ($club): ?>
                <p class="lead">
                    Klubs: <strong><?= htmlspecialchars($club["name"]) ?></strong>
                </p>
            <?php else: ?>
                <p class="lead">
                    Direktora pārvaldības sadaļa.
                </p>
            <?php endif; ?>
        </div>

        <?php if ($error): ?>
            <div class="dashboard-card">
                <p class="muted"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($club): ?>

            <!-- KLUBA INFO -->
            <div class="dashboard-card">
                <div class="section-title-row">
                    <div>
                        <h3><?= htmlspecialchars($club["name"]) ?></h3>
                        <p class="muted">
                            <?= htmlspecialchars($club["address"] ?? "Adrese nav norādīta") ?>
                        </p>
                    </div>

                    <a class="btn btn-primary btn-sm" href="../director/club.php">
                        Pārvaldīt klubu
                    </a>
                </div>

                <div class="divider"></div>

                <div class="cards">
                    <div class="card">
                        <h4>Draudze</h4>
                        <p class="muted">
                            <?= htmlspecialchars($club["church_name"] ?? "Nav norādīta") ?>
                        </p>
                    </div>

                    <div class="card">
                        <h4>Programmas</h4>
                        <p class="muted">
                            <?= htmlspecialchars($club["programs"] ?? "Nav piesaistītas") ?>
                        </p>
                    </div>

                    <div class="card">
                        <h4>Statuss</h4>
                        <p class="muted">
                            <?= ((int)($club["is_active"] ?? 0) === 1) ? "Aktīvs" : "Neaktīvs" ?>
                        </p>
                    </div>
                </div>
            </div>

            <!-- STATISTIKA -->
            <div class="dashboard-card">
                <h3>Kluba dalībnieku pārskats</h3>
                <p class="muted">Cik lietotāju šobrīd piesaistīti šim klubam.</p>
                <div class="divider"></div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <span class="stat-number"><?= (int)$stats["children"] ?></span>
                        <span class="stat-label">Bērni / Ceļameklētāji</span>
                    </div>

                    <div class="stat-card">
                        <span class="stat-number"><?= (int)$stats["parents"] ?></span>
                        <span class="stat-label">Vecāki</span>
                    </div>

                    <div class="stat-card">
                        <span class="stat-number"><?= (int)$stats["teachers"] ?></span>
                        <span class="stat-label">Skolotāji</span>
                    </div>

                    <div class="stat-card">
                        <span class="stat-number"><?= (int)$stats["directors"] ?></span>
                        <span class="stat-label">Direktori</span>
                    </div>

                    <div class="stat-card">
                        <span class="stat-number"><?= (int)$stats["total"] ?></span>
                        <span class="stat-label">Kopā klubā</span>
                    </div>
                </div>
            </div>

            <!-- ĀTRĀS DARBĪBAS -->
            <div class="dashboard-card">
                <h3>Ātrās darbības</h3>
                <p class="muted">Biežāk izmantotās direktora funkcijas.</p>
                <div class="divider"></div>

                <div class="cards">
                    <div class="card">
                        <h4>Pārvaldīt bērnus</h4>
                        <p class="muted">Apskati, pievieno un labo ceļameklētāju kontus.</p>
                        <a class="btn btn-primary btn-sm" href="../director/users.php?role=children">
                            Atvērt
                        </a>
                    </div>

                    <div class="card">
                        <h4>Pārvaldīt vecākus</h4>
                        <p class="muted">Pārskati vecāku kontus un saistīto informāciju.</p>
                        <a class="btn btn-outline btn-sm" href="../director/users.php?role=parents">
                            Atvērt
                        </a>
                    </div>

                    <div class="card">
                        <h4>Pārvaldīt skolotājus</h4>
                        <p class="muted">Pievieno vai labo skolotāju kontus savā klubā.</p>
                        <a class="btn btn-outline btn-sm" href="../director/users.php?role=teachers">
                            Atvērt
                        </a>
                    </div>

                    <div class="card">
                        <h4>Pievienot lietotāju</h4>
                        <p class="muted">Izveido bērna, vecāka vai skolotāja kontu.</p>
                        <a class="btn btn-sm" href="../director/add_user.php">
                            Pievienot
                        </a>
                    </div>
                </div>
            </div>

            <!-- JAUNĀKIE LIETOTĀJI -->
            <div class="dashboard-card">
                <div class="section-title-row">
                    <div>
                        <h3>Jaunākie kluba lietotāji</h3>
                        <p class="muted">Pēdējie pievienotie lietotāji šajā klubā.</p>
                    </div>

                    <a class="btn btn-outline btn-sm" href="../director/users.php">
                        Skatīt visus
                    </a>
                </div>

                <div class="divider"></div>

                <?php if (empty($latestUsers)): ?>
                    <p class="muted">Šim klubam vēl nav piesaistītu lietotāju.</p>
                <?php else: ?>
                    <div class="table-wrap">
                        <table class="admin-table">
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
                                    <tr>
                                        <td>
                                            <?php
                                            $fullName = trim(($user["vards"] ?? "") . " " . ($user["uzvards"] ?? ""));
                                            echo htmlspecialchars($fullName ?: ($user["lietotajvards"] ?? "—"));
                                            ?>
                                        </td>
                                        <td><?= htmlspecialchars($user["loma"] ?? "—") ?></td>
                                        <td><?= htmlspecialchars($user["epasts"] ?? "—") ?></td>
                                        <td><?= htmlspecialchars($user["statuss"] ?? "—") ?></td>
                                        <td><?= htmlspecialchars(formatDateLv($user["Reg_datums"] ?? null)) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

        <?php endif; ?>

        <!-- JAUNUMI -->
        <div class="dashboard-card">
            <h3>Jaunākie jaunumi</h3>
            <p class="muted">Aktuālākie ieraksti mājaslapā.</p>
            <div class="divider"></div>

            <?php if (empty($latestNews)): ?>
                <p class="muted">Pašlaik nav publicētu jaunumu.</p>
            <?php else: ?>
                <div class="cards">
                    <?php foreach ($latestNews as $item): ?>
                        <div class="card">
                            <h4><?= htmlspecialchars($item["title"] ?? "Bez nosaukuma") ?></h4>

                            <p class="muted small">
                                <?= htmlspecialchars($item["category"] ?? "—") ?>
                                &nbsp;•&nbsp;
                                <?= htmlspecialchars(formatDateLv($item["publish_date"] ?? null)) ?>
                            </p>

                            <a class="link" href="../news/view.php?id=<?= (int)$item["id"] ?>">
                                Lasīt vairāk
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
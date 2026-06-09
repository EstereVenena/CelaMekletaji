<?php
session_start();

$lapa  = "Mans klubs";
$title = "Mans klubs - Ceļa meklētāji";

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

$error = "";
$club = null;
$children = [];

$stats = [
    "children" => 0,
    "parents" => 0,
    "teachers" => 0,
    "total" => 0
];

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function tableColumnExists(mysqli $db, string $table, string $column): bool
{
    $table = $db->real_escape_string($table);
    $column = $db->real_escape_string($column);

    $result = $db->query("SHOW COLUMNS FROM `{$table}` LIKE '{$column}'");

    return $result && $result->num_rows > 0;
}

function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00 00:00:00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

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

if ($teacherClubId <= 0) {
    $error = "Skolotājam nav piesaistīts klubs. Sazinies ar administratoru vai direktoru.";
}

/* ===============================
   KLUBA DATI
================================ */
if ($error === "") {
    $hasCity = tableColumnExists($savienojums, "cm_clubs", "city");
    $hasDescription = tableColumnExists($savienojums, "cm_clubs", "description");
    $hasImagePath = tableColumnExists($savienojums, "cm_clubs", "image_path");

    $citySelect = $hasCity ? "c.city" : "NULL AS city";
    $descriptionSelect = $hasDescription ? "c.description" : "NULL AS description";
    $imageSelect = $hasImagePath ? "c.image_path" : "NULL AS image_path";

    $sqlClub = "
        SELECT
            c.id,
            c.name,
            c.address,
            {$citySelect},
            {$descriptionSelect},
            {$imageSelect},
            c.director_id,
            c.church_id,
            c.is_active,
            c.created_at,
            ch.name AS church_name,
            d.name AS director_name,
            d.phone AS director_phone,
            d.email AS director_email,
            GROUP_CONCAT(p.label SEPARATOR ', ') AS programs
        FROM cm_clubs c
        LEFT JOIN cm_churches ch ON c.church_id = ch.id
        LEFT JOIN cm_directors d ON c.director_id = d.id
        LEFT JOIN cm_club_programs cp ON c.id = cp.club_id
        LEFT JOIN cm_programs p ON cp.program_id = p.id
        WHERE c.id = ?
        GROUP BY
            c.id,
            c.name,
            c.address,
            c.director_id,
            c.church_id,
            c.is_active,
            c.created_at,
            ch.name,
            d.name,
            d.phone,
            d.email
        LIMIT 1
    ";

    if ($stmt = $savienojums->prepare($sqlClub)) {
        $stmt->bind_param("i", $teacherClubId);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $club = $result->fetch_assoc();
        } else {
            $error = "Piesaistītais klubs netika atrasts.";
        }

        $stmt->close();
    } else {
        $error = "Neizdevās sagatavot kluba vaicājumu.";
    }
}

/* ===============================
   KLUBA STATISTIKA
================================ */
if ($club) {
    $clubId = (int)$club["id"];

    $sqlStats = "
        SELECT
            loma,
            COUNT(*) AS total
        FROM cm_lietotaji
        WHERE club_id = ?
          AND statuss <> 'dzēsts'
        GROUP BY loma
    ";

    if ($stmt = $savienojums->prepare($sqlStats)) {
        $stmt->bind_param("i", $clubId);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $role = trim($row["loma"] ?? "");
            $count = (int)($row["total"] ?? 0);

            $stats["total"] += $count;

            if (in_array($role, ["Ceļameklētājs", "Skolēns", "Bērns", "student", "child"], true)) {
                $stats["children"] += $count;
            }

            if (in_array($role, ["Vecāks", "parent"], true)) {
                $stats["parents"] += $count;
            }

            if (in_array($role, ["Skolotājs", "skolotājs", "teacher"], true)) {
                $stats["teachers"] += $count;
            }
        }

        $stmt->close();
    }
}

/* ===============================
   KLUBA BĒRNI / CEĻAMEKLĒTĀJI
================================ */
if ($club) {
    $clubId = (int)$club["id"];

    $sqlChildren = "
        SELECT
            lietotajs_id,
            lietotajvards,
            vards,
            uzvards,
            epasts,
            loma,
            statuss,
            Reg_datums
        FROM cm_lietotaji
        WHERE club_id = ?
          AND statuss <> 'dzēsts'
          AND loma IN ('Ceļameklētājs', 'Skolēns', 'Bērns', 'student', 'child')
        ORDER BY uzvards ASC, vards ASC, lietotajvards ASC
    ";

    if ($stmt = $savienojums->prepare($sqlChildren)) {
        $stmt->bind_param("i", $clubId);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $children[] = $row;
        }

        $stmt->close();
    }
}

require __DIR__ . "/../includes/templates/header-teacher.php";
?>

<style>
.teacher-club-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30,79,161,.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244,196,48,.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.teacher-club-hero {
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

.teacher-club-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.teacher-club-hero > * {
    position: relative;
    z-index: 1;
}

.teacher-club-kicker {
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

.teacher-club-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.teacher-club-hero p {
    max-width: 760px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.teacher-club-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,.14);
    border: 1px solid rgba(255,255,255,.18);
    backdrop-filter: blur(8px);
}

.teacher-club-hero-card strong {
    display: block;
    font-size: 2rem;
    line-height: 1.1;
    color: #f4c430;
}

.teacher-club-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.teacher-alert {
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

.teacher-club-image-wrap {
    margin-bottom: 1.2rem;
    border-radius: 22px;
    overflow: hidden;
    background: #eef3ff;
    border: 1px solid #e8eef8;
}

.teacher-club-image-wrap img {
    width: 100%;
    max-height: 360px;
    display: block;
    object-fit: cover;
}

.teacher-info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
}

.teacher-info-card {
    padding: 1.1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.teacher-info-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23,63,132,.08);
    border-color: #d7e5ff;
}

.teacher-info-icon {
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

.teacher-info-card h3 {
    margin: 0 0 .35rem;
    color: #101828;
    font-size: 1.05rem;
    line-height: 1.3;
}

.teacher-info-card p {
    margin: 0;
    color: #667085;
    line-height: 1.55;
    word-break: break-word;
}

.teacher-status-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .7rem;
    border-radius: 999px;
    background: #ecfff4;
    color: #17633a;
    font-weight: 950;
}

.teacher-status-pill.inactive {
    background: #fff8e6;
    color: #7a5517;
}

.teacher-stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
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
    min-width: 780px;
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

.teacher-role-pill,
.teacher-status-small {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem .65rem;
    border-radius: 999px;
    font-size: .86rem;
    font-weight: 900;
}

.teacher-role-pill {
    background: #eef3ff;
    color: #173f84;
}

.teacher-status-small {
    background: #ecfff4;
    color: #17633a;
}

.teacher-status-small.waiting {
    background: #fff8e6;
    color: #7a5517;
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
    .teacher-info-grid,
    .teacher-stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}

@media (max-width: 900px) {
    .teacher-club-hero {
        grid-template-columns: 1fr;
    }

    .teacher-panel-head {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .teacher-club-page {
        padding: 1.5rem 0 2.5rem;
    }

    .teacher-club-hero,
    .teacher-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .teacher-info-grid,
    .teacher-stats-grid {
        grid-template-columns: 1fr;
    }

    .teacher-panel-head .btn {
        width: 100%;
    }
}
</style>

<main class="teacher-club-page">
    <div class="container">

        <section class="teacher-club-hero">
            <div>
                <div class="teacher-club-kicker">
                    <i class="fas fa-people-roof"></i>
                    Skolotāja kluba pārskats
                </div>

                <h1><?= $club ? htmlspecialchars($club["name"]) : "Mans klubs"; ?></h1>

                <p>
                    Šeit skolotājs var apskatīt sava kluba pamatinformāciju,
                    piesaistītās programmas un bērnus, kuri ir šajā klubā.
                </p>
            </div>

            <aside class="teacher-club-hero-card">
                <strong><?= (int)$stats["children"]; ?></strong>
                <span>Klubā piesaistīti bērni / ceļameklētāji</span>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="teacher-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($club): ?>

            <?php if (!empty($club["image_path"])): ?>
                <div class="teacher-club-image-wrap">
                    <img
                        src="<?= htmlspecialchars("../" . $club["image_path"]); ?>"
                        alt="<?= htmlspecialchars($club["name"]); ?>"
                    >
                </div>
            <?php endif; ?>

            <section class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <h2>Kluba informācija</h2>
                        <p>Pamatinformācija par skolotājam piesaistīto klubu.</p>
                    </div>

                    <a href="../dashboards/teacher.php" class="btn btn-outline btn-sm">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>

                <div class="teacher-info-grid">

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-signature"></i>
                        </div>
                        <h3>Nosaukums</h3>
                        <p><?= htmlspecialchars($club["name"] ?? "Nav norādīts"); ?></p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-location-dot"></i>
                        </div>
                        <h3>Adrese</h3>
                        <p><?= htmlspecialchars($club["address"] ?? "Nav norādīta"); ?></p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-city"></i>
                        </div>
                        <h3>Pilsēta</h3>
                        <p><?= htmlspecialchars($club["city"] ?? "Nav norādīta"); ?></p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-church"></i>
                        </div>
                        <h3>Draudze</h3>
                        <p><?= htmlspecialchars($club["church_name"] ?? "Nav norādīta"); ?></p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <h3>Programmas</h3>
                        <p><?= htmlspecialchars($club["programs"] ?? "Nav piesaistītas"); ?></p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-user-tie"></i>
                        </div>
                        <h3>Direktors</h3>
                        <p>
                            <?= htmlspecialchars($club["director_name"] ?? "Nav norādīts"); ?>

                            <?php if (!empty($club["director_email"])): ?>
                                <span class="teacher-muted">
                                    <?= htmlspecialchars($club["director_email"]); ?>
                                </span>
                            <?php endif; ?>

                            <?php if (!empty($club["director_phone"])): ?>
                                <span class="teacher-muted">
                                    <?= htmlspecialchars($club["director_phone"]); ?>
                                </span>
                            <?php endif; ?>
                        </p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-circle-check"></i>
                        </div>
                        <h3>Statuss</h3>
                        <?php $isActive = ((int)($club["is_active"] ?? 0) === 1); ?>
                        <p>
                            <span class="teacher-status-pill <?= $isActive ? "" : "inactive"; ?>">
                                <i class="fas <?= $isActive ? "fa-circle-check" : "fa-circle-exclamation"; ?>"></i>
                                <?= $isActive ? "Aktīvs" : "Neaktīvs"; ?>
                            </span>
                        </p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-calendar-day"></i>
                        </div>
                        <h3>Izveidots</h3>
                        <p><?= htmlspecialchars(formatDateLv($club["created_at"] ?? null)); ?></p>
                    </article>

                    <article class="teacher-info-card">
                        <div class="teacher-info-icon">
                            <i class="fas fa-align-left"></i>
                        </div>
                        <h3>Apraksts</h3>
                        <p><?= nl2br(htmlspecialchars($club["description"] ?? "Nav apraksta")); ?></p>
                    </article>

                </div>
            </section>

            <section class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <h2>Kluba pārskats</h2>
                        <p>Lietotāju skaits šajā klubā.</p>
                    </div>
                </div>

                <div class="teacher-stats-grid">
                    <article class="teacher-stat-card">
                        <span class="teacher-stat-number"><?= (int)$stats["children"]; ?></span>
                        <span class="teacher-stat-label">Bērni / Ceļameklētāji</span>
                    </article>

                    <article class="teacher-stat-card">
                        <span class="teacher-stat-number"><?= (int)$stats["parents"]; ?></span>
                        <span class="teacher-stat-label">Vecāki</span>
                    </article>

                    <article class="teacher-stat-card">
                        <span class="teacher-stat-number"><?= (int)$stats["teachers"]; ?></span>
                        <span class="teacher-stat-label">Skolotāji</span>
                    </article>

                    <article class="teacher-stat-card">
                        <span class="teacher-stat-number"><?= (int)$stats["total"]; ?></span>
                        <span class="teacher-stat-label">Kopā klubā</span>
                    </article>
                </div>
            </section>

            <section class="teacher-panel">
                <div class="teacher-panel-head">
                    <div>
                        <h2>Kluba bērni</h2>
                        <p>Bērni un ceļameklētāji, kuri ir piesaistīti šim klubam.</p>
                    </div>

                    <div class="notifications-badge">
                        <?= count($children); ?> bērni
                    </div>
                </div>

                <?php if (empty($children)): ?>
                    <div class="teacher-empty">
                        Šim klubam pašlaik nav piesaistītu bērnu.
                    </div>
                <?php else: ?>

                    <div class="teacher-table-wrap">
                        <table class="teacher-table">
                            <thead>
                                <tr>
                                    <th>Vārds</th>
                                    <th>Lietotājvārds</th>
                                    <th>E-pasts</th>
                                    <th>Loma</th>
                                    <th>Statuss</th>
                                    <th>Pievienots</th>
                                </tr>
                            </thead>

                            <tbody>
                                <?php foreach ($children as $child): ?>
                                    <?php
                                        $childFullName = trim(($child["vards"] ?? "") . " " . ($child["uzvards"] ?? ""));

                                        if ($childFullName === "") {
                                            $childFullName = $child["lietotajvards"] ?? "—";
                                        }

                                        $childStatus = $child["statuss"] ?? "";
                                        $statusClass = "teacher-status-small";

                                        if ($childStatus === "gaida") {
                                            $statusClass .= " waiting";
                                        }
                                    ?>

                                    <tr>
                                        <td>
                                            <span class="teacher-user-name">
                                                <?= htmlspecialchars($childFullName); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($child["lietotajvards"] ?? "—"); ?>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars($child["epasts"] ?? "—"); ?>
                                        </td>

                                        <td>
                                            <span class="teacher-role-pill">
                                                <?= htmlspecialchars($child["loma"] ?? "—"); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <span class="<?= $statusClass; ?>">
                                                <i class="fas fa-circle"></i>
                                                <?= htmlspecialchars($childStatus ?: "—"); ?>
                                            </span>
                                        </td>

                                        <td>
                                            <?= htmlspecialchars(formatDateLv($child["Reg_datums"] ?? null)); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                <?php endif; ?>
            </section>

        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
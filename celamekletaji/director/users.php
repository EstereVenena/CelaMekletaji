<?php
session_start();

$lapa  = "Lietotāji";
$title = "Lietotāji - Ceļa meklētāji";

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

$roleFilter = trim($_GET["role"] ?? "");
$search = trim($_GET["search"] ?? "");

$users = [];
$error = null;

/* ===============================
   LOMU FILTRI
================================ */
$allowedFilters = ["children", "parents", "teachers"];

$roleGroups = [
    "children" => ["Ceļameklētājs", "Skolēns", "Bērns", "student", "child"],
    "parents"  => ["Vecāks", "parent"],
    "teachers" => ["Skolotājs", "teacher"],
];

$pageLabels = [
    "children" => "Bērni",
    "parents"  => "Vecāki",
    "teachers" => "Skolotāji",
    ""         => "Visi lietotāji",
];

if (!in_array($roleFilter, $allowedFilters, true)) {
    $roleFilter = "";
}

$currentLabel = $pageLabels[$roleFilter] ?? "Visi lietotāji";

if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs. Pārbaudi lietotāja club_id datubāzē.";
}

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00 00:00:00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

function getInitials(string $name): string
{
    $name = trim($name);

    if ($name === "") {
        return "L";
    }

    $parts = preg_split('/\s+/', $name);
    $initials = "";

    if (!empty($parts[0])) {
        $initials .= mb_strtoupper(mb_substr($parts[0], 0, 1));
    }

    if (!empty($parts[1])) {
        $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
    }

    return $initials ?: "L";
}

/* ===============================
   LIETOTĀJU SARAKSTS
================================ */
if (!$error) {
    $where = "
        WHERE u.club_id = ?
          AND u.statuss <> 'dzēsts'
    ";

    $params = [$directorClubId];
    $types = "i";

    if ($roleFilter !== "") {
        $roles = $roleGroups[$roleFilter];

        $placeholders = implode(",", array_fill(0, count($roles), "?"));
        $where .= " AND u.loma IN ($placeholders)";

        foreach ($roles as $role) {
            $params[] = $role;
            $types .= "s";
        }
    }

    if ($search !== "") {
        $where .= "
            AND (
                u.lietotajvards LIKE ?
                OR u.vards LIKE ?
                OR u.uzvards LIKE ?
                OR u.epasts LIKE ?
            )
        ";

        $searchLike = "%" . $search . "%";

        for ($i = 0; $i < 4; $i++) {
            $params[] = $searchLike;
            $types .= "s";
        }
    }

    $sql = "
        SELECT
            u.lietotajs_id,
            u.lietotajvards,
            u.vards,
            u.uzvards,
            u.epasts,
            u.loma,
            u.statuss,
            u.Reg_datums
        FROM cm_lietotaji u
        $where
        ORDER BY u.Reg_datums DESC
    ";

    $stmt = $savienojums->prepare($sql);

    if ($stmt) {
        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }

        $stmt->close();
    } else {
        $error = "Neizdevās ielādēt lietotājus.";
    }
}

$usersCount = count($users);

require __DIR__ . "/../includes/templates/header-director.php";
?>

<style>
.director-users-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-users-hero {
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

.director-users-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-users-hero > * {
    position: relative;
    z-index: 1;
}

.director-users-kicker {
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

.director-users-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-users-hero p {
    max-width: 740px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.director-users-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.director-users-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.director-users-hero-card strong {
    display: block;
    font-size: 2.2rem;
    color: #f4c430;
    line-height: 1;
}

.director-users-hero-card span {
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
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
    font-weight: 800;
}

.director-users-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.director-users-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1rem;
}

.director-users-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.director-users-panel-head p {
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

.director-users-grid {
    display: grid;
    gap: .9rem;
}

.director-user-card {
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
    align-items: center;
    padding: 1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.director-user-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.director-user-avatar {
    width: 52px;
    height: 52px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: linear-gradient(135deg, #173f84, #1e4fa1);
    color: #f4c430;
    font-weight: 1000;
    flex-shrink: 0;
}

.director-user-main h3 {
    margin: 0 0 .3rem;
    color: #101828;
    font-size: 1.05rem;
}

.director-user-main p {
    margin: 0;
    color: #667085;
    line-height: 1.45;
}

.director-user-meta {
    display: flex;
    gap: .5rem;
    flex-wrap: wrap;
    margin-top: .55rem;
}

.director-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .35rem .65rem;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #edf2fb;
    color: #667085;
    font-size: .84rem;
    font-weight: 850;
}

.director-pill.role {
    background: #eef3ff;
    color: #173f84;
}

.director-pill.active {
    background: #ecfff4;
    color: #17633a;
}

.director-pill.inactive {
    background: #fff8e6;
    color: #7a5517;
}

.director-user-actions {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    justify-content: flex-end;
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
    .director-users-hero,
    .director-user-card {
        grid-template-columns: 1fr;
    }

    .director-users-panel-head,
    .director-filter-row {
        flex-direction: column;
    }

    .director-user-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 640px) {
    .director-users-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-users-hero,
    .director-users-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-users-actions .btn,
    .director-search-form,
    .director-search-form .btn,
    .director-search-input {
        width: 100%;
    }
}
</style>

<main class="director-users-page">
    <div class="container">

        <section class="director-users-hero">
            <div>
                <div class="director-users-kicker">
                    <i class="fas fa-users-gear"></i>
                    Kluba lietotāji
                </div>

                <h1><?= htmlspecialchars($currentLabel); ?></h1>

                <p>
                    Pārskati savam klubam piesaistītos lietotājus pēc lomām.
                    Šeit redzami tikai tie lietotāji, kuri ir piesaistīti direktora klubam.
                </p>

                <div class="director-users-actions">
                    <a class="btn btn-primary btn-sm" href="add_user.php">
                        <i class="fas fa-user-plus"></i>
                        Pievienot lietotāju
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/director.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="director-users-hero-card">
                <strong><?= (int)$usersCount; ?></strong>
                <span>
                    Lietotāji šajā skatā.
                </span>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="director-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="director-users-panel">
            <div class="director-users-panel-head">
                <div>
                    <h2>Lietotāju saraksts</h2>
                    <p>Filtrē pēc lomām vai meklē pēc vārda, lietotājvārda un e-pasta.</p>
                </div>
            </div>

            <div class="director-filter-row">
                <div class="director-filter-tabs">
                    <a href="users.php" class="director-filter-tab <?= $roleFilter === '' ? 'is-active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        Visi
                    </a>

                    <a href="users.php?role=children" class="director-filter-tab <?= $roleFilter === 'children' ? 'is-active' : ''; ?>">
                        <i class="fas fa-child-reaching"></i>
                        Bērni
                    </a>

                    <a href="users.php?role=parents" class="director-filter-tab <?= $roleFilter === 'parents' ? 'is-active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        Vecāki
                    </a>

                    <a href="users.php?role=teachers" class="director-filter-tab <?= $roleFilter === 'teachers' ? 'is-active' : ''; ?>">
                        <i class="fas fa-chalkboard-user"></i>
                        Skolotāji
                    </a>
                </div>

                <form method="get" class="director-search-form">
                    <?php if ($roleFilter !== ""): ?>
                        <input type="hidden" name="role" value="<?= htmlspecialchars($roleFilter); ?>">
                    <?php endif; ?>

                    <input
                        class="director-search-input"
                        type="text"
                        name="search"
                        value="<?= htmlspecialchars($search); ?>"
                        placeholder="Meklēt lietotāju..."
                    >

                    <button class="btn btn-primary btn-sm" type="submit">
                        <i class="fas fa-magnifying-glass"></i>
                        Meklēt
                    </button>

                    <?php if ($search !== ""): ?>
                        <a class="btn btn-outline btn-sm" href="users.php<?= $roleFilter ? '?role=' . urlencode($roleFilter) : ''; ?>">
                            Notīrīt
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <?php if (!empty($users)): ?>
                <div class="director-users-grid">
                    <?php foreach ($users as $user): ?>
                        <?php
                            $fullName = trim(($user["vards"] ?? "") . " " . ($user["uzvards"] ?? ""));
                            $displayName = $fullName ?: ($user["lietotajvards"] ?? "Lietotājs");
                            $initials = getInitials($displayName);
                            $status = trim($user["statuss"] ?? "—");
                            $isActive = in_array(mb_strtolower($status), ["aktīvs", "aktivs", "active"], true);
                        ?>

                        <article class="director-user-card">
                            <div class="director-user-avatar">
                                <?= htmlspecialchars($initials); ?>
                            </div>

                            <div class="director-user-main">
                                <h3><?= htmlspecialchars($displayName); ?></h3>

                                <p>
                                    <?= htmlspecialchars($user["epasts"] ?? "Nav e-pasta"); ?>
                                </p>

                                <div class="director-user-meta">
                                    <span class="director-pill role">
                                        <i class="fas fa-id-badge"></i>
                                        <?= htmlspecialchars($user["loma"] ?? "—"); ?>
                                    </span>

                                    <span class="director-pill <?= $isActive ? 'active' : 'inactive'; ?>">
                                        <i class="fas <?= $isActive ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                                        <?= htmlspecialchars($status); ?>
                                    </span>

                                    <span class="director-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatDateLv($user["Reg_datums"] ?? null)); ?>
                                    </span>
                                </div>
                            </div>

                            <div class="director-user-actions">
                                <a class="btn btn-outline btn-sm" href="user_view.php?id=<?= (int)$user["lietotajs_id"]; ?>">
                                    <i class="fas fa-eye"></i>
                                    Skatīt
                                </a>

                                <a class="btn btn-primary btn-sm" href="user_edit.php?id=<?= (int)$user["lietotajs_id"]; ?>">
                                    <i class="fas fa-pen"></i>
                                    Labot
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="director-empty">
                    <h3>Nav atrasts neviens lietotājs</h3>
                    <p>Šajā skatā vēl nav lietotāju vai meklēšana neko neatrada.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
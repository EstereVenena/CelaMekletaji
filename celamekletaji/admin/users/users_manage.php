<?php
session_start();

$lapa  = "Lietotāju pārvaldība";
$title = "Lietotāju pārvaldība";

require_once __DIR__ . "/../../includes/config/database.php";

// Drošība — tikai adminam
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? "") !== "admin") {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

/* ===============================
   ZIŅOJUMI PĒC PĀRADRESĀCIJAS
================================ */
$success = $_SESSION["flash_success"] ?? "";
$error   = $_SESSION["flash_error"] ?? "";

unset($_SESSION["flash_success"], $_SESSION["flash_error"]);

/* ===============================
   PIEEJAMIE STATUSI UN LOMAS
================================ */
$availableStatuses = ["aktīvs", "gaida", "bloķēts", "dzēsts"];

$availableRoles = [
    "admin"          => 1,
    "Vecāks"         => 2,
    "Direktors"      => 3,
    "Skolotājs"      => 4,
    "Ceļameklētājs"  => 5,
    "Nenoteikts"     => 6,
    "Skolēns"        => 7
];

/* ===============================
   PĀRADRESĀCIJA ATPAKAĻ UZ SARAKSTU
================================ */
function redirectBackToUsersManage()
{
    $query = $_GET;

    if (!empty($query)) {
        header("Location: users_manage.php?" . http_build_query($query));
    } else {
        header("Location: users_manage.php");
    }

    exit();
}

/* ===============================
   DZĒŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user_id"])) {
    $deleteId = (int)$_POST["delete_user_id"];
    $currentUserId = (int)($_SESSION["lietotajs_id"] ?? 0);

    if ($deleteId === $currentUserId) {
        $_SESSION["flash_error"] = "Tu nevari dzēst pats savu kontu.";
        redirectBackToUsersManage();
    }

    $stmt = $savienojums->prepare("
        DELETE FROM cm_lietotaji
        WHERE lietotajs_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("i", $deleteId);

        if ($stmt->execute()) {
            $_SESSION["flash_success"] = "Lietotājs veiksmīgi dzēsts.";
        } else {
            $_SESSION["flash_error"] = "Neizdevās dzēst lietotāju.";
        }

        $stmt->close();
    } else {
        $_SESSION["flash_error"] = "Neizdevās sagatavot dzēšanas vaicājumu.";
    }

    redirectBackToUsersManage();
}

/* ===============================
   ĀTRĀ STATUSA MAIŅA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["quick_status_user_id"], $_POST["quick_status"])) {
    $quickUserId = (int)$_POST["quick_status_user_id"];
    $quickStatus = trim($_POST["quick_status"]);

    if ($quickUserId <= 0) {
        $_SESSION["flash_error"] = "Nederīgs lietotāja ID.";
        redirectBackToUsersManage();
    }

    if (!in_array($quickStatus, $availableStatuses, true)) {
        $_SESSION["flash_error"] = "Nederīgs statuss.";
        redirectBackToUsersManage();
    }

    $stmt = $savienojums->prepare("
        UPDATE cm_lietotaji
        SET statuss = ?
        WHERE lietotajs_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("si", $quickStatus, $quickUserId);

        if ($stmt->execute()) {
            $_SESSION["flash_success"] = "Lietotāja statuss veiksmīgi mainīts.";
        } else {
            $_SESSION["flash_error"] = "Neizdevās mainīt lietotāja statusu.";
        }

        $stmt->close();
    } else {
        $_SESSION["flash_error"] = "Neizdevās sagatavot statusa maiņas vaicājumu.";
    }

    redirectBackToUsersManage();
}

/* ===============================
   ĀTRĀ LOMAS MAIŅA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["quick_role_user_id"], $_POST["quick_role"])) {
    $quickUserId = (int)$_POST["quick_role_user_id"];
    $quickRole   = trim($_POST["quick_role"]);

    $currentUserId = (int)($_SESSION["lietotajs_id"] ?? 0);

    if ($quickUserId <= 0) {
        $_SESSION["flash_error"] = "Nederīgs lietotāja ID.";
        redirectBackToUsersManage();
    }

    if (!array_key_exists($quickRole, $availableRoles)) {
        $_SESSION["flash_error"] = "Nederīga lietotāja loma.";
        redirectBackToUsersManage();
    }

    // Drošība: lai admin nejauši pats sev nenoņem admin tiesības
    if ($quickUserId === $currentUserId && $quickRole !== "admin") {
        $_SESSION["flash_error"] = "Tu nevari noņemt admin lomu pats sev.";
        redirectBackToUsersManage();
    }

    $quickRoleId = $availableRoles[$quickRole];

    $stmt = $savienojums->prepare("
        UPDATE cm_lietotaji
        SET 
            loma = ?,
            loma_id = ?
        WHERE lietotajs_id = ?
    ");

    if ($stmt) {
        $stmt->bind_param("sii", $quickRole, $quickRoleId, $quickUserId);

        if ($stmt->execute()) {
            $_SESSION["flash_success"] = "Lietotāja loma veiksmīgi mainīta.";
        } else {
            $_SESSION["flash_error"] = "Neizdevās mainīt lietotāja lomu.";
        }

        $stmt->close();
    } else {
        $_SESSION["flash_error"] = "Neizdevās sagatavot lomas maiņas vaicājumu.";
    }

    redirectBackToUsersManage();
}

/* ===============================
   FILTRI
================================ */
$search = trim($_GET["search"] ?? "");
$role   = trim($_GET["role"] ?? "");
$status = trim($_GET["status"] ?? "");
$clubId = (int)($_GET["club_id"] ?? 0);

$where  = [];
$params = [];
$types  = "";

if ($search !== "") {
    $where[] = "(u.lietotajvards LIKE ? OR u.vards LIKE ? OR u.uzvards LIKE ? OR u.epasts LIKE ?)";
    $searchLike = "%" . $search . "%";

    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;

    $types .= "ssss";
}

if ($role !== "") {
    $where[] = "u.loma = ?";
    $params[] = $role;
    $types .= "s";
}

if ($status !== "") {
    $where[] = "u.statuss = ?";
    $params[] = $status;
    $types .= "s";
}

if ($clubId > 0) {
    $where[] = "u.club_id = ?";
    $params[] = $clubId;
    $types .= "i";
}

$whereSql = "";

if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

/* ===============================
   KLUBU SARAKSTS FILTRAM
================================ */
$clubs = [];

$clubResult = $savienojums->query("
    SELECT id, name
    FROM cm_clubs
    ORDER BY name ASC
");

if ($clubResult) {
    while ($club = $clubResult->fetch_assoc()) {
        $clubs[] = $club;
    }
}

/* ===============================
   STATISTIKA
================================ */
$totalUsers  = 0;
$totalAdmins = 0;
$totalActive = 0;

$result = $savienojums->query("
    SELECT COUNT(*) AS total
    FROM cm_lietotaji
");

if ($result && $row = $result->fetch_assoc()) {
    $totalUsers = (int)$row["total"];
}

$result = $savienojums->query("
    SELECT COUNT(*) AS total
    FROM cm_lietotaji
    WHERE loma = 'admin'
");

if ($result && $row = $result->fetch_assoc()) {
    $totalAdmins = (int)$row["total"];
}

$result = $savienojums->query("
    SELECT COUNT(*) AS total
    FROM cm_lietotaji
    WHERE statuss = 'aktīvs'
");

if ($result && $row = $result->fetch_assoc()) {
    $totalActive = (int)$row["total"];
}

/* ===============================
   LIETOTĀJU SARAKSTS
================================ */
$sql = "
    SELECT 
        u.lietotajs_id,
        u.lietotajvards,
        u.vards,
        u.uzvards,
        u.epasts,
        u.loma_id,
        u.loma,
        u.statuss,
        u.club_id,
        c.name AS club_name
    FROM cm_lietotaji u
    LEFT JOIN cm_clubs c ON u.club_id = c.id
    $whereSql
    ORDER BY u.lietotajs_id DESC
";

$users = [];

$stmt = $savienojums->prepare($sql);

if ($stmt) {
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    $stmt->close();
} else {
    $error = "Neizdevās ielādēt lietotāju sarakstu.";
}

$currentAction = htmlspecialchars($_SERVER["REQUEST_URI"] ?? "users_manage.php");

require __DIR__ . "/../../includes/templates/header-admin.php";
?>

<style>
    .users-dashboard {
        padding: 2rem 0 3rem;
        background:
            radial-gradient(circle at top right, rgba(30,79,161,0.08), transparent 30%),
            linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        min-height: calc(100vh - 140px);
    }

    .users-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.5rem;
        padding: 1.5rem;
        border-radius: 24px;
        background: linear-gradient(135deg, #173f84, #1e4fa1);
        color: #fff;
        box-shadow: 0 20px 50px rgba(23, 63, 132, 0.18);
    }

    .users-hero h1 {
        margin: 0 0 .35rem;
        font-size: clamp(1.7rem, 3vw, 2.4rem);
    }

    .users-hero p {
        margin: 0;
        opacity: .92;
    }

    .users-hero-actions {
        display: flex;
        gap: .8rem;
        flex-wrap: wrap;
    }

    .btn-admin {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .85rem 1.1rem;
        border-radius: 14px;
        text-decoration: none;
        font-weight: 700;
        border: 1px solid transparent;
        transition: .2s ease;
        cursor: pointer;
    }

    .btn-admin.primary {
        background: #fff;
        color: #173f84;
    }

    .btn-admin.primary:hover {
        transform: translateY(-2px);
    }

    .btn-admin.secondary {
        background: rgba(255,255,255,0.10);
        color: #fff;
        border-color: rgba(255,255,255,0.18);
    }

    .btn-admin.secondary:hover {
        background: rgba(255,255,255,0.16);
    }

    .users-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .stat-card {
        background: #fff;
        border: 1px solid #e8eef8;
        border-radius: 20px;
        padding: 1.25rem;
        box-shadow: 0 12px 28px rgba(16, 24, 40, 0.06);
    }

    .stat-label {
        margin: 0 0 .5rem;
        color: #667085;
        font-size: .95rem;
    }

    .stat-value {
        margin: 0;
        font-size: 2rem;
        font-weight: 800;
        color: #101828;
    }

    .panel {
        background: #fff;
        border: 1px solid #e8eef8;
        border-radius: 22px;
        padding: 1.25rem;
        box-shadow: 0 12px 28px rgba(16, 24, 40, 0.06);
        margin-bottom: 1.2rem;
    }

    .panel h2 {
        margin: 0 0 1rem;
        color: #173f84;
        font-size: 1.15rem;
    }

    .filter-form {
        display: grid;
        grid-template-columns: 2fr 1fr 1fr 1fr auto auto;
        gap: .8rem;
        align-items: end;
    }

    .form-group label {
        display: block;
        margin-bottom: .35rem;
        font-weight: 700;
        color: #344054;
        font-size: .92rem;
    }

    .form-control {
        width: 100%;
        padding: .85rem .95rem;
        border: 1px solid #d0d5dd;
        border-radius: 12px;
        font-size: .95rem;
        background: #fff;
    }

    .form-control:focus {
        outline: none;
        border-color: #1e4fa1;
        box-shadow: 0 0 0 4px rgba(30,79,161,0.10);
    }

    .btn-filter {
        padding: .85rem 1rem;
        border-radius: 12px;
        border: none;
        cursor: pointer;
        font-weight: 700;
        font-size: .95rem;
        min-height: 48px;
        white-space: nowrap;
    }

    .btn-filter.apply {
        background: #173f84;
        color: #fff;
    }

    .btn-filter.reset {
        background: #eef3ff;
        color: #173f84;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }

    .alert {
        padding: 1rem 1.1rem;
        border-radius: 14px;
        margin-bottom: 1rem;
        font-weight: 600;
    }

    .alert.success {
        background: #ecfdf3;
        color: #027a48;
        border: 1px solid #abefc6;
    }

    .alert.error {
        background: #fef3f2;
        color: #b42318;
        border: 1px solid #fecdca;
    }

    .table-wrap {
        overflow-x: auto;
    }

    .users-table {
        width: 100%;
        border-collapse: collapse;
        min-width: 1100px;
    }

    .users-table th,
    .users-table td {
        padding: .95rem .9rem;
        border-bottom: 1px solid #edf2fb;
        text-align: left;
        vertical-align: middle;
    }

    .users-table th {
        color: #344054;
        font-size: .92rem;
        background: #f8fbff;
    }

    .users-table td {
        color: #101828;
        font-size: .94rem;
    }

    .badge {
        display: inline-block;
        padding: .4rem .7rem;
        border-radius: 999px;
        font-size: .82rem;
        font-weight: 700;
    }

    .badge.club {
        background: #fff8e6;
        color: #7a5b00;
    }

    .muted {
        color: #98a2b3;
        font-style: italic;
    }

    .quick-role-form,
    .quick-status-form {
        margin: 0;
    }

    .quick-role-select,
    .quick-status-select {
        padding: .45rem .65rem;
        border-radius: 999px;
        border: 1px solid #d0d5dd;
        font-size: .82rem;
        font-weight: 800;
        cursor: pointer;
        outline: none;
    }

    .quick-role-select {
        width: 155px;
        background: #eef3ff;
        color: #173f84;
        border-color: #cfe0ff;
    }

    .quick-status-select {
        width: 130px;
    }

    .quick-role-select:focus,
    .quick-status-select:focus {
        border-color: #1e4fa1;
        box-shadow: 0 0 0 4px rgba(30,79,161,0.10);
    }

    .quick-status-select.status-aktīvs {
        background: #ecfdf3;
        color: #027a48;
        border-color: #abefc6;
    }

    .quick-status-select.status-gaida {
        background: #fff8e6;
        color: #7a5b00;
        border-color: #f5df9f;
    }

    .quick-status-select.status-bloķēts {
        background: #fee4e2;
        color: #b42318;
        border-color: #fecdca;
    }

    .quick-status-select.status-dzēsts {
        background: #f2f4f7;
        color: #344054;
        border-color: #d0d5dd;
    }

    .action-buttons {
        display: flex;
        gap: .5rem;
        flex-wrap: wrap;
    }

    .btn-small {
        padding: .6rem .8rem;
        border-radius: 10px;
        border: none;
        cursor: pointer;
        font-size: .88rem;
        font-weight: 700;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: .35rem;
    }

    .btn-edit {
        background: #eef3ff;
        color: #173f84;
    }

    .btn-delete {
        background: #fee4e2;
        color: #b42318;
    }

    .empty-state {
        text-align: center;
        padding: 2rem 1rem;
        color: #667085;
    }

    @media (max-width: 1200px) {
        .filter-form {
            grid-template-columns: 1fr 1fr 1fr;
        }

        .filter-form .btn-filter,
        .filter-form .btn-filter.reset {
            width: 100%;
        }
    }

    @media (max-width: 980px) {
        .filter-form {
            grid-template-columns: 1fr;
        }

        .users-stats {
            grid-template-columns: 1fr;
        }

        .users-hero {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<main class="users-dashboard">
    <div class="container">

        <section class="users-hero">
            <div>
                <h1>Lietotāju pārvaldība</h1>
                <p>Pārskati, meklē, filtrē un administrē sistēmas lietotāju kontus vienuviet.</p>
            </div>

            <div class="users-hero-actions">
                <a href="<?= BASE_URL ?>admin/users/create_user.php" class="btn-admin primary">
                    <i class="fas fa-user-plus"></i> Pievienot lietotāju
                </a>

                <a href="<?= BASE_URL ?>dashboards/admin.php" class="btn-admin secondary">
                    <i class="fas fa-arrow-left"></i> Atpakaļ uz paneli
                </a>
            </div>
        </section>

        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <section class="users-stats">
            <div class="stat-card">
                <p class="stat-label">Kopā lietotāji</p>
                <p class="stat-value"><?= $totalUsers ?></p>
            </div>

            <div class="stat-card">
                <p class="stat-label">Administratori</p>
                <p class="stat-value"><?= $totalAdmins ?></p>
            </div>

            <div class="stat-card">
                <p class="stat-label">Aktīvie lietotāji</p>
                <p class="stat-value"><?= $totalActive ?></p>
            </div>
        </section>

        <section class="panel">
            <h2>Meklēšana un filtrēšana</h2>

            <form method="GET" class="filter-form">
                <div class="form-group">
                    <label for="search">Meklēt</label>
                    <input
                        type="text"
                        id="search"
                        name="search"
                        class="form-control"
                        placeholder="Lietotājvārds, vārds, uzvārds vai e-pasts"
                        value="<?= htmlspecialchars($search) ?>"
                    >
                </div>

                <div class="form-group">
                    <label for="role">Loma</label>
                    <select id="role" name="role" class="form-control">
                        <option value="">Visas</option>

                        <?php foreach ($availableRoles as $roleOption => $roleId): ?>
                            <option
                                value="<?= htmlspecialchars($roleOption) ?>"
                                <?= $role === $roleOption ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($roleOption) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Statuss</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Visi</option>

                        <?php foreach ($availableStatuses as $statusOption): ?>
                            <option
                                value="<?= htmlspecialchars($statusOption) ?>"
                                <?= $status === $statusOption ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars(ucfirst($statusOption)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="club_id">Klubs</label>
                    <select id="club_id" name="club_id" class="form-control">
                        <option value="0">Visi klubi</option>

                        <?php foreach ($clubs as $club): ?>
                            <option
                                value="<?= (int)$club["id"] ?>"
                                <?= $clubId === (int)$club["id"] ? "selected" : "" ?>
                            >
                                <?= htmlspecialchars($club["name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <button type="submit" class="btn-filter apply">
                    <i class="fas fa-magnifying-glass"></i> Meklēt
                </button>

                <a href="users_manage.php" class="btn-filter reset">
                    <i class="fas fa-rotate-left"></i> Notīrīt
                </a>
            </form>
        </section>

        <section class="panel">
            <h2>Lietotāju saraksts</h2>

            <?php if (!empty($users)): ?>
                <div class="table-wrap">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Lietotājvārds</th>
                                <th>Vārds, uzvārds</th>
                                <th>E-pasts</th>
                                <th>Loma</th>
                                <th>Klubs</th>
                                <th>Statuss</th>
                                <th>Darbības</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                    $statusClass = mb_strtolower((string)$user["statuss"]);
                                    $fullName = trim(($user["vards"] ?? "") . " " . ($user["uzvards"] ?? ""));
                                    $currentUserRole = (string)($user["loma"] ?? "");
                                ?>

                                <tr>
                                    <td>#<?= (int)$user["lietotajs_id"] ?></td>

                                    <td><?= htmlspecialchars($user["lietotajvards"]) ?></td>

                                    <td>
                                        <?= $fullName !== "" ? htmlspecialchars($fullName) : '<span class="muted">Nav norādīts</span>' ?>
                                    </td>

                                    <td><?= htmlspecialchars($user["epasts"]) ?></td>

                                    <td>
                                        <form method="POST" action="<?= $currentAction ?>" class="quick-role-form">
                                            <input
                                                type="hidden"
                                                name="quick_role_user_id"
                                                value="<?= (int)$user["lietotajs_id"] ?>"
                                            >

                                            <select
                                                name="quick_role"
                                                class="quick-role-select"
                                                onchange="this.form.submit()"
                                            >
                                                <?php if ($currentUserRole !== "" && !array_key_exists($currentUserRole, $availableRoles)): ?>
                                                    <option value="<?= htmlspecialchars($currentUserRole) ?>" selected>
                                                        <?= htmlspecialchars($currentUserRole) ?>
                                                    </option>
                                                <?php endif; ?>

                                                <?php foreach ($availableRoles as $roleOption => $roleId): ?>
                                                    <option
                                                        value="<?= htmlspecialchars($roleOption) ?>"
                                                        <?= $currentUserRole === $roleOption ? "selected" : "" ?>
                                                    >
                                                        <?= htmlspecialchars($roleOption) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>

                                    <td>
                                        <?php if (!empty($user["club_name"])): ?>
                                            <span class="badge club"><?= htmlspecialchars($user["club_name"]) ?></span>
                                        <?php else: ?>
                                            <span class="muted">Nav piesaistīts</span>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <form method="POST" action="<?= $currentAction ?>" class="quick-status-form">
                                            <input
                                                type="hidden"
                                                name="quick_status_user_id"
                                                value="<?= (int)$user["lietotajs_id"] ?>"
                                            >

                                            <select
                                                name="quick_status"
                                                class="quick-status-select status-<?= htmlspecialchars($statusClass) ?>"
                                                onchange="this.form.submit()"
                                            >
                                                <?php foreach ($availableStatuses as $statusOption): ?>
                                                    <option
                                                        value="<?= htmlspecialchars($statusOption) ?>"
                                                        <?= $user["statuss"] === $statusOption ? "selected" : "" ?>
                                                    >
                                                        <?= htmlspecialchars(ucfirst($statusOption)) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </form>
                                    </td>

                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?= BASE_URL ?>admin/users/edit_user.php?id=<?= (int)$user["lietotajs_id"] ?>" class="btn-small btn-edit">
                                                <i class="fas fa-pen"></i> Rediģēt
                                            </a>

                                            <form method="POST" action="<?= $currentAction ?>" onsubmit="return confirm('Vai tiešām dzēst šo lietotāju?');" style="display:inline;">
                                                <input type="hidden" name="delete_user_id" value="<?= (int)$user["lietotajs_id"] ?>">

                                                <button type="submit" class="btn-small btn-delete">
                                                    <i class="fas fa-trash"></i> Dzēst
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>

                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            <?php else: ?>

                <div class="empty-state">
                    <i class="fas fa-users-slash" style="font-size:2rem; margin-bottom:.7rem; color:#98a2b3;"></i>
                    <p>Neviens lietotājs neatbilst izvēlētajiem filtriem.</p>
                </div>

            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../../includes/templates/footer.php"; ?>
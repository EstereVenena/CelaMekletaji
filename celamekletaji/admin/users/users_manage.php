<?php
session_start();

$lapa  = "Lietotāju pārvaldība";
$title = "Lietotāju pārvaldība";

require __DIR__ . "/../../includes/templates/header-admin.php";
require_once __DIR__ . "/../../includes/config/database.php";

// Drošība — tikai adminam
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? "") !== "admin") {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$success = "";
$error   = "";

/* ===============================
   DZĒŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_user_id"])) {
    $deleteId = (int)$_POST["delete_user_id"];
    $currentUserId = (int)($_SESSION["lietotajs_id"] ?? 0);

    if ($deleteId === $currentUserId) {
        $error = "Tu nevari dzēst pats savu kontu.";
    } else {
        $stmt = $savienojums->prepare("DELETE FROM cm_lietotaji WHERE lietotajs_id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $deleteId);

            if ($stmt->execute()) {
                $success = "Lietotājs veiksmīgi dzēsts.";
            } else {
                $error = "Neizdevās dzēst lietotāju.";
            }

            $stmt->close();
        } else {
            $error = "Neizdevās sagatavot dzēšanas vaicājumu.";
        }
    }
}

/* ===============================
   FILTRI
================================ */
$search = trim($_GET["search"] ?? "");
$role   = trim($_GET["role"] ?? "");
$status = trim($_GET["status"] ?? "");

$where = [];
$params = [];
$types  = "";

if ($search !== "") {
    $where[] = "(lietotajvards LIKE ? OR vards LIKE ? OR uzvards LIKE ? OR epasts LIKE ?)";
    $searchLike = "%" . $search . "%";
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $params[] = $searchLike;
    $types .= "ssss";
}

if ($role !== "") {
    $where[] = "loma = ?";
    $params[] = $role;
    $types .= "s";
}

if ($status !== "") {
    $where[] = "statuss = ?";
    $params[] = $status;
    $types .= "s";
}

$whereSql = "";
if (!empty($where)) {
    $whereSql = "WHERE " . implode(" AND ", $where);
}

/* ===============================
   STATISTIKA
================================ */
$totalUsers = 0;
$totalAdmins = 0;
$totalActive = 0;

$result = $savienojums->query("SELECT COUNT(*) AS total FROM cm_lietotaji");
if ($result && $row = $result->fetch_assoc()) {
    $totalUsers = (int)$row["total"];
}

$result = $savienojums->query("SELECT COUNT(*) AS total FROM cm_lietotaji WHERE loma = 'admin'");
if ($result && $row = $result->fetch_assoc()) {
    $totalAdmins = (int)$row["total"];
}

$result = $savienojums->query("SELECT COUNT(*) AS total FROM cm_lietotaji WHERE statuss = 'aktīvs' OR statuss = 'aktivs'");
if ($result && $row = $result->fetch_assoc()) {
    $totalActive = (int)$row["total"];
}

/* ===============================
   LIETOTĀJU SARAKSTS
================================ */
$sql = "
    SELECT 
        lietotajs_id,
        lietotajvards,
        vards,
        uzvards,
        epasts,
        loma,
        statuss
    FROM cm_lietotaji
    $whereSql
    ORDER BY lietotajs_id DESC
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
        grid-template-columns: 2fr 1fr 1fr auto auto;
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
        min-width: 900px;
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

    .badge.role {
        background: #eef3ff;
        color: #173f84;
    }

    .badge.status {
        background: #f2f4f7;
        color: #344054;
    }

    .badge.status.active,
    .badge.status.aktīvs,
    .badge.status.aktivs {
        background: #ecfdf3;
        color: #027a48;
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
                        <option value="admin" <?= $role === 'admin' ? 'selected' : '' ?>>Admin</option>
                        <option value="direktors" <?= $role === 'direktors' ? 'selected' : '' ?>>Direktors</option>
                        <option value="skolotajs" <?= $role === 'skolotajs' ? 'selected' : '' ?>>Skolotājs</option>
                        <option value="Vecāks" <?= $role === 'Vecāks' ? 'selected' : '' ?>>Vecāks</option>
                        <option value="berns" <?= $role === 'berns' ? 'selected' : '' ?>>Bērns</option>
                        <option value="moderators" <?= $role === 'moderators' ? 'selected' : '' ?>>Moderators</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Statuss</label>
                    <select id="status" name="status" class="form-control">
                        <option value="">Visi</option>
                        <option value="aktīvs" <?= $status === 'aktīvs' ? 'selected' : '' ?>>Aktīvs</option>
                        <option value="aktivs" <?= $status === 'aktivs' ? 'selected' : '' ?>>Aktīvs (bez garumzīmes)</option>
                        <option value="neaktīvs" <?= $status === 'neaktīvs' ? 'selected' : '' ?>>Neaktīvs</option>
                        <option value="bloķēts" <?= $status === 'bloķēts' ? 'selected' : '' ?>>Bloķēts</option>
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
                                <th>Statuss</th>
                                <th>Darbības</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                                <?php
                                    $statusClass = mb_strtolower((string)$user["statuss"]);
                                ?>
                                <tr>
                                    <td>#<?= (int)$user["lietotajs_id"] ?></td>
                                    <td><?= htmlspecialchars($user["lietotajvards"]) ?></td>
                                    <td>
                                        <?= htmlspecialchars(trim(($user["vards"] ?? "") . " " . ($user["uzvards"] ?? ""))) ?>
                                    </td>
                                    <td><?= htmlspecialchars($user["epasts"]) ?></td>
                                    <td>
                                        <span class="badge role"><?= htmlspecialchars($user["loma"]) ?></span>
                                    </td>
                                    <td>
                                        <span class="badge status <?= htmlspecialchars($statusClass) ?>">
                                            <?= htmlspecialchars($user["statuss"]) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="<?= BASE_URL ?>admin/users/edit_user.php?id=<?= (int)$user["lietotajs_id"] ?>" class="btn-small btn-edit">
                                                <i class="fas fa-pen"></i> Rediģēt
                                            </a>

                                            <form method="POST" onsubmit="return confirm('Vai tiešām dzēst šo lietotāju?');" style="display:inline;">
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
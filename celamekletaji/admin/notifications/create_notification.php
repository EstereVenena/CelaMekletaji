<?php
$lapa = "Izveidot paziņojumu";
$title = "Izveidot paziņojumu";

require_once __DIR__ . "/../../includes/config/database.php";
require_once __DIR__ . "/../../includes/templates/header-admin.php";

/* ===============================
   PIEKĻUVE TIKAI ADMINAM
================================ */
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$success = "";
$error = "";

/* ===============================
   DATU IELĀDE FORMAI
================================ */
$clubs = [];
$users = [];

$clubsSql = "
    SELECT id, name
    FROM cm_clubs
    WHERE is_active = 1 OR is_active IS NULL
    ORDER BY name ASC
";

$clubsResult = $savienojums->query($clubsSql);

if ($clubsResult) {
    while ($row = $clubsResult->fetch_assoc()) {
        $clubs[] = $row;
    }
}

$usersSql = "
    SELECT lietotajs_id, lietotajvards, vards, uzvards, epasts, loma, club_id
    FROM cm_lietotaji
    WHERE statuss = 'aktīvs'
    ORDER BY vards ASC, uzvards ASC, lietotajvards ASC
";

$usersResult = $savienojums->query($usersSql);

if ($usersResult) {
    while ($row = $usersResult->fetch_assoc()) {
        $users[] = $row;
    }
}

/* ===============================
   PAZIŅOJUMA IZVEIDE
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $targetType = $_POST["target_type"] ?? "";
    $clubId = isset($_POST["club_id"]) ? (int)$_POST["club_id"] : 0;
    $userId = isset($_POST["user_id"]) ? (int)$_POST["user_id"] : 0;

    $notificationTitle = trim($_POST["title"] ?? "");
    $message = trim($_POST["message"] ?? "");
    $type = trim($_POST["type"] ?? "system");

    if ($notificationTitle === "" || $message === "") {
        $error = "Lūdzu aizpildi paziņojuma tēmu un saturu.";
    } elseif (!in_array($targetType, ["all", "club", "user"], true)) {
        $error = "Lūdzu izvēlies paziņojuma saņēmēju.";
    } else {
        $recipientIds = [];

        if ($targetType === "all") {
            $sql = "
                SELECT lietotajs_id
                FROM cm_lietotaji
                WHERE statuss = 'aktīvs'
            ";

            $result = $savienojums->query($sql);

            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $recipientIds[] = (int)$row["lietotajs_id"];
                }
            }
        }

        if ($targetType === "club") {
            if ($clubId <= 0) {
                $error = "Lūdzu izvēlies klubu.";
            } else {
                $sql = "
                    SELECT lietotajs_id
                    FROM cm_lietotaji
                    WHERE club_id = ?
                    AND statuss = 'aktīvs'
                ";

                $stmt = $savienojums->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param("i", $clubId);
                    $stmt->execute();

                    $result = $stmt->get_result();

                    while ($row = $result->fetch_assoc()) {
                        $recipientIds[] = (int)$row["lietotajs_id"];
                    }
                }
            }
        }

        if ($targetType === "user") {
            if ($userId <= 0) {
                $error = "Lūdzu izvēlies lietotāju.";
            } else {
                $sql = "
                    SELECT lietotajs_id
                    FROM cm_lietotaji
                    WHERE lietotajs_id = ?
                    AND statuss = 'aktīvs'
                    LIMIT 1
                ";

                $stmt = $savienojums->prepare($sql);

                if ($stmt) {
                    $stmt->bind_param("i", $userId);
                    $stmt->execute();

                    $result = $stmt->get_result();

                    if ($row = $result->fetch_assoc()) {
                        $recipientIds[] = (int)$row["lietotajs_id"];
                    }
                }
            }
        }

        if ($error === "") {
            $recipientIds = array_values(array_unique($recipientIds));

            if (empty($recipientIds)) {
                $error = "Nav atrasts neviens saņēmējs.";
            } else {
                $insertSql = "
                    INSERT INTO cm_notifications
                    (user_id, title, message, type, related_table, related_id, is_read)
                    VALUES (?, ?, ?, ?, NULL, NULL, 0)
                ";

                $insertStmt = $savienojums->prepare($insertSql);

                if (!$insertStmt) {
                    $error = "Neizdevās sagatavot paziņojuma saglabāšanu.";
                } else {
                    $createdCount = 0;

                    foreach ($recipientIds as $recipientId) {
                        $insertStmt->bind_param(
                            "isss",
                            $recipientId,
                            $notificationTitle,
                            $message,
                            $type
                        );

                        if ($insertStmt->execute()) {
                            $createdCount++;
                        }
                    }

                    if ($createdCount > 0) {
                        $success = "Paziņojums veiksmīgi nosūtīts. Saņēmēju skaits: " . $createdCount . ".";
                    } else {
                        $error = "Paziņojumu neizdevās nosūtīt.";
                    }
                }
            }
        }
    }
}
?>

<style>
    .notification-page {
        max-width: 1100px;
        margin: 2rem auto;
        padding: 0 1rem;
    }

    .notification-hero {
        background: linear-gradient(135deg, #173f84, #1e4fa1);
        color: #fff;
        border-radius: 1.5rem;
        padding: 2rem;
        box-shadow: 0 18px 45px rgba(23, 63, 132, 0.18);
        margin-bottom: 1.5rem;
    }

    .notification-hero h1 {
        margin: 0 0 .5rem;
        font-size: 2rem;
        letter-spacing: -0.04em;
    }

    .notification-hero p {
        margin: 0;
        color: rgba(255,255,255,.85);
        font-weight: 700;
    }

    .notification-layout {
        display: grid;
        grid-template-columns: 1.5fr .8fr;
        gap: 1.25rem;
        align-items: start;
    }

    .notification-card {
        background: #fff;
        border-radius: 1.4rem;
        padding: 1.5rem;
        box-shadow: 0 14px 35px rgba(16, 24, 40, 0.08);
        border: 1px solid rgba(23, 63, 132, 0.08);
    }

    .notification-card h2 {
        margin: 0 0 1rem;
        color: #173f84;
        font-size: 1.25rem;
    }

    .form-group {
        margin-bottom: 1rem;
    }

    .form-group label {
        display: block;
        margin-bottom: .45rem;
        font-weight: 900;
        color: #344054;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        border: 1px solid #d0d5dd;
        border-radius: .9rem;
        padding: .85rem 1rem;
        font: inherit;
        outline: none;
        transition: .2s ease;
        background: #fff;
    }

    .form-group textarea {
        min-height: 150px;
        resize: vertical;
    }

    .form-group input:focus,
    .form-group select:focus,
    .form-group textarea:focus {
        border-color: #173f84;
        box-shadow: 0 0 0 4px rgba(23, 63, 132, 0.10);
    }

    .target-box {
        display: none;
    }

    .target-box.show {
        display: block;
    }

    .btn-row {
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
        margin-top: 1.25rem;
    }

    .btn-primary {
        border: none;
        background: #173f84;
        color: #fff;
        padding: .9rem 1.2rem;
        border-radius: 999px;
        font-weight: 1000;
        cursor: pointer;
        transition: .2s ease;
    }

    .btn-primary:hover {
        background: #102f66;
        transform: translateY(-1px);
    }

    .btn-secondary {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        text-decoration: none;
        background: #eef3ff;
        color: #173f84;
        padding: .9rem 1.2rem;
        border-radius: 999px;
        font-weight: 1000;
        transition: .2s ease;
    }

    .btn-secondary:hover {
        background: #dfeaff;
        transform: translateY(-1px);
    }

    .alert {
        padding: 1rem 1.2rem;
        border-radius: 1rem;
        font-weight: 900;
        margin-bottom: 1rem;
    }

    .alert-success {
        background: #ecfdf3;
        color: #027a48;
        border: 1px solid #abefc6;
    }

    .alert-error {
        background: #fff1f3;
        color: #c01048;
        border: 1px solid #fecdd6;
    }

    .info-list {
        display: grid;
        gap: .85rem;
    }

    .info-item {
        display: flex;
        gap: .8rem;
        align-items: flex-start;
        padding: .9rem;
        border-radius: 1rem;
        background: #f8fbff;
    }

    .info-item i {
        color: #173f84;
        margin-top: .15rem;
    }

    .info-item strong {
        display: block;
        color: #101828;
        margin-bottom: .2rem;
    }

    .info-item span {
        color: #667085;
        font-size: .92rem;
        font-weight: 700;
    }

    @media (max-width: 850px) {
        .notification-layout {
            grid-template-columns: 1fr;
        }

        .notification-hero h1 {
            font-size: 1.55rem;
        }
    }
</style>

<main class="notification-page">

    <section class="notification-hero">
        <h1>Izveidot paziņojumu</h1>
        <p>Nosūti paziņojumu visiem lietotājiem, konkrētam klubam vai vienam lietotājam.</p>
    </section>

    <?php if ($success !== ""): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ""): ?>
        <div class="alert alert-error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="notification-layout">

        <section class="notification-card">
            <h2>Paziņojuma dati</h2>

            <form method="post">

                <div class="form-group">
                    <label for="target_type">Saņēmējs</label>
                    <select name="target_type" id="target_type" required>
                        <option value="">-- Izvēlies saņēmēju --</option>
                        <option value="all">Visi aktīvie lietotāji</option>
                        <option value="club">Konkrēts klubs</option>
                        <option value="user">Konkrēts lietotājs</option>
                    </select>
                </div>

                <div class="form-group target-box" id="clubBox">
                    <label for="club_id">Klubs</label>
                    <select name="club_id" id="club_id">
                        <option value="0">-- Izvēlies klubu --</option>

                        <?php foreach ($clubs as $club): ?>
                            <option value="<?= (int)$club["id"] ?>">
                                <?= htmlspecialchars($club["name"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group target-box" id="userBox">
                    <label for="user_id">Lietotājs</label>
                    <select name="user_id" id="user_id">
                        <option value="0">-- Izvēlies lietotāju --</option>

                        <?php foreach ($users as $user): ?>
                            <?php
                                $fullName = trim(($user["vards"] ?? "") . " " . ($user["uzvards"] ?? ""));
                                if ($fullName === "") {
                                    $fullName = $user["lietotajvards"] ?? "Lietotājs";
                                }

                                $userLabel = $fullName;

                                if (!empty($user["loma"])) {
                                    $userLabel .= " — " . $user["loma"];
                                }

                                if (!empty($user["epasts"])) {
                                    $userLabel .= " — " . $user["epasts"];
                                }
                            ?>

                            <option value="<?= (int)$user["lietotajs_id"] ?>">
                                <?= htmlspecialchars($userLabel) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="type">Paziņojuma tips</label>
                    <select name="type" id="type">
                        <option value="system">Sistēmas paziņojums</option>
                        <option value="event">Pasākums</option>
                        <option value="news">Jaunums</option>
                        <option value="badge">Nozīmīte</option>
                        <option value="warning">Svarīgi</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="title">Tēma</label>
                    <input 
                        type="text" 
                        name="title" 
                        id="title" 
                        maxlength="255" 
                        required 
                        placeholder="Piemēram: Jauns pasākums sestdien"
                    >
                </div>

                <div class="form-group">
                    <label for="message">Saturs</label>
                    <textarea 
                        name="message" 
                        id="message" 
                        required 
                        placeholder="Ievadi paziņojuma tekstu..."
                    ></textarea>
                </div>

                <div class="btn-row">
                    <button type="submit" class="btn-primary">
                        <i class="fas fa-paper-plane"></i>
                        Nosūtīt paziņojumu
                    </button>

                    <a href="<?= BASE_URL ?>dashboards/notifications.php" class="btn-secondary">
                        <i class="fas fa-bell"></i>
                        Skatīt paziņojumus
                    </a>
                </div>

            </form>
        </section>

        <aside class="notification-card">
            <h2>Ko šī sadaļa dara?</h2>

            <div class="info-list">
                <div class="info-item">
                    <i class="fas fa-users"></i>
                    <div>
                        <strong>Visiem lietotājiem</strong>
                        <span>Paziņojums tiek izveidots katram aktīvajam lietotājam.</span>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-location-dot"></i>
                    <div>
                        <strong>Konkrētam klubam</strong>
                        <span>Paziņojumu saņem tikai lietotāji, kuri piesaistīti izvēlētajam klubam.</span>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-user"></i>
                    <div>
                        <strong>Vienam lietotājam</strong>
                        <span>Der individuāliem paziņojumiem vai īpašām situācijām.</span>
                    </div>
                </div>

                <div class="info-item">
                    <i class="fas fa-eye"></i>
                    <div>
                        <strong>Statuss</strong>
                        <span>Sākumā paziņojums ir nelasīts. Lietotājs to vēlāk var atzīmēt kā lasītu.</span>
                    </div>
                </div>
            </div>
        </aside>

    </div>
</main>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const targetType = document.getElementById("target_type");
    const clubBox = document.getElementById("clubBox");
    const userBox = document.getElementById("userBox");

    function updateTargetBoxes() {
        const value = targetType.value;

        clubBox.classList.remove("show");
        userBox.classList.remove("show");

        if (value === "club") {
            clubBox.classList.add("show");
        }

        if (value === "user") {
            userBox.classList.add("show");
        }
    }

    targetType.addEventListener("change", updateTargetBoxes);
    updateTargetBoxes();
});
</script>
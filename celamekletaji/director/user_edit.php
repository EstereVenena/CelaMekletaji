<?php
session_start();

$lapa  = "Labot lietotāju";
$title = "Labot lietotāju - Ceļa meklētāji";

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

$directorClubId = (int)($_SESSION["club_id"] ?? 0);
$userId = (int)($_GET["id"] ?? $_POST["id"] ?? 0);

$user = null;
$error = trim($_GET["error"] ?? "");
$success = trim($_GET["success"] ?? "");

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function redirectWithMessage(int $id, string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: user_edit.php?id=" . $id . "&" . $param . "=" . urlencode($message));
    exit();
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

function getRoleId(mysqli $db, string $roleName): ?int
{
    $hasLomaId = tableColumnExists($db, "cm_lomas", "loma_id");
    $hasNosaukums = tableColumnExists($db, "cm_lomas", "nosaukums");
    $hasLomasNosaukums = tableColumnExists($db, "cm_lomas", "lomas_nosaukums");

    if (!$hasLomaId) {
        return null;
    }

    if ($hasNosaukums) {
        $stmt = $db->prepare("
            SELECT loma_id
            FROM cm_lomas
            WHERE nosaukums = ?
            LIMIT 1
        ");
    } elseif ($hasLomasNosaukums) {
        $stmt = $db->prepare("
            SELECT loma_id
            FROM cm_lomas
            WHERE lomas_nosaukums = ?
            LIMIT 1
        ");
    } else {
        return null;
    }

    if (!$stmt) {
        return null;
    }

    $stmt->bind_param("s", $roleName);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return $row ? (int)$row["loma_id"] : null;
}

function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00 00:00:00") {
        return "—";
    }

    return date("d.m.Y H:i", strtotime($date));
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
   DROŠĪBAS PĀRBAUDE
================================ */
if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs.";
} elseif ($userId <= 0) {
    $error = "Nederīgs lietotāja ID.";
}

/* ===============================
   SAGLABĀŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($error)) {
    $lietotajvards = trim($_POST["lietotajvards"] ?? "");
    $vards = trim($_POST["vards"] ?? "");
    $uzvards = trim($_POST["uzvards"] ?? "");
    $epasts = trim($_POST["epasts"] ?? "");
    $loma = trim($_POST["loma"] ?? "");
    $statuss = trim($_POST["statuss"] ?? "");
    $parole = trim($_POST["parole"] ?? "");
    $paroleAtkartoti = trim($_POST["parole_atkartoti"] ?? "");

    $allowedNewRoles = [
        "Ceļameklētājs",
        "Bērns",
        "Skolēns",
        "Vecāks",
        "Skolotājs",
    ];

    $allowedStatuses = [
        "aktīvs",
        "neaktīvs",
        "bloķēts",
    ];

    if ($lietotajvards === "" || $vards === "" || $uzvards === "" || $epasts === "" || $loma === "" || $statuss === "") {
        redirectWithMessage($userId, "error", "Aizpildi visus obligātos laukus.");
    }

    if (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage($userId, "error", "Ievadi korektu e-pasta adresi.");
    }

    if (!in_array($loma, $allowedNewRoles, true)) {
        redirectWithMessage($userId, "error", "Izvēlēta nederīga loma.");
    }

    if (!in_array($statuss, $allowedStatuses, true)) {
        redirectWithMessage($userId, "error", "Izvēlēts nederīgs statuss.");
    }

    if ($parole !== "" || $paroleAtkartoti !== "") {
        if ($parole !== $paroleAtkartoti) {
            redirectWithMessage($userId, "error", "Paroles nesakrīt.");
        }

        if (mb_strlen($parole) < 8) {
            redirectWithMessage($userId, "error", "Parolei jābūt vismaz 8 rakstzīmēm garai.");
        }
    }

    /*
       E-pastu speciāli nepārbaudām kā unikālu,
       jo sistēmā viens e-pasts drīkst būt vairākiem kontiem.
    */

    $lomaId = getRoleId($savienojums, $loma);

    if ($parole !== "") {
        $passwordHash = password_hash($parole, PASSWORD_DEFAULT);

        $sql = "
            UPDATE cm_lietotaji
            SET
                lietotajvards = ?,
                vards = ?,
                uzvards = ?,
                epasts = ?,
                parole = ?,
                loma_id = ?,
                loma = ?,
                statuss = ?
            WHERE lietotajs_id = ?
              AND club_id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if (!$stmt) {
            redirectWithMessage($userId, "error", "Neizdevās sagatavot saglabāšanu.");
        }

        $stmt->bind_param(
            "sssssissii",
            $lietotajvards,
            $vards,
            $uzvards,
            $epasts,
            $passwordHash,
            $lomaId,
            $loma,
            $statuss,
            $userId,
            $directorClubId
        );
    } else {
        $sql = "
            UPDATE cm_lietotaji
            SET
                lietotajvards = ?,
                vards = ?,
                uzvards = ?,
                epasts = ?,
                loma_id = ?,
                loma = ?,
                statuss = ?
            WHERE lietotajs_id = ?
              AND club_id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if (!$stmt) {
            redirectWithMessage($userId, "error", "Neizdevās sagatavot saglabāšanu.");
        }

        $stmt->bind_param(
            "ssssissii",
            $lietotajvards,
            $vards,
            $uzvards,
            $epasts,
            $lomaId,
            $loma,
            $statuss,
            $userId,
            $directorClubId
        );
    }

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage($userId, "success", "Lietotāja dati veiksmīgi saglabāti.");
    }

    $stmt->close();

    if ($savienojums->errno === 1062) {
        redirectWithMessage($userId, "error", "Šāds lietotājvārds jau eksistē.");
    }

    redirectWithMessage($userId, "error", "Neizdevās saglabāt lietotāja datus.");
}

/* ===============================
   LIETOTĀJA IELĀDE
================================ */
if (empty($error)) {
    $sql = "
        SELECT
            u.lietotajs_id,
            u.lietotajvards,
            u.vards,
            u.uzvards,
            u.epasts,
            u.loma,
            u.loma_id,
            u.statuss,
            u.club_id,
            u.Reg_datums,
            c.name AS club_name
        FROM cm_lietotaji u
        LEFT JOIN cm_clubs c ON u.club_id = c.id
        WHERE u.lietotajs_id = ?
          AND u.club_id = ?
          AND u.statuss <> 'dzēsts'
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("ii", $userId, $directorClubId);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        if (!$user) {
            $error = "Lietotājs nav atrasts vai nav piesaistīts tavam klubam.";
        }
    } else {
        $error = "Neizdevās ielādēt lietotāja datus.";
    }
}

$fullName = "";

if ($user) {
    $fullName = trim(($user["vards"] ?? "") . " " . ($user["uzvards"] ?? ""));
    $fullName = $fullName !== "" ? $fullName : ($user["lietotajvards"] ?? "Lietotājs");
}

$initials = getInitials($fullName);

require __DIR__ . "/../includes/templates/header-director.php";
?>

<style>
.director-edit-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-edit-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1.3rem;
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

.director-edit-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-edit-hero > * {
    position: relative;
    z-index: 1;
}

.director-edit-avatar {
    width: 86px;
    height: 86px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(244,196,48,.55);
    color: #f4c430;
    font-size: 2rem;
    font-weight: 1000;
}

.director-edit-hero h1 {
    margin: 0 0 .45rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-edit-hero p {
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.7;
}

.director-edit-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    justify-content: flex-end;
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

.director-edit-layout {
    display: grid;
    grid-template-columns: 1.15fr .85fr;
    gap: 1.1rem;
    align-items: start;
}

.director-edit-card {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.director-edit-card h2 {
    margin: 0 0 .35rem;
    color: #173f84;
    font-size: 1.35rem;
}

.director-muted {
    margin: 0;
    color: #667085;
    line-height: 1.6;
}

.director-form {
    display: grid;
    gap: 1rem;
    margin-top: 1.2rem;
}

.director-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .9rem;
}

.director-form-group {
    display: grid;
    gap: .4rem;
}

.director-form-group label {
    color: #344054;
    font-weight: 900;
}

.director-input,
.director-select {
    width: 100%;
    padding: .86rem .95rem;
    border-radius: 14px;
    border: 1px solid #d0d8e8;
    background: #fff;
    color: #101828;
    font: inherit;
    outline: none;
    transition: .2s ease;
}

.director-input:focus,
.director-select:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.director-note {
    display: flex;
    gap: .7rem;
    padding: 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    color: #667085;
    line-height: 1.55;
}

.director-note i {
    color: #1e4fa1;
    margin-top: .2rem;
}

.director-form-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .3rem;
}

.director-info-list {
    display: grid;
    gap: .85rem;
    margin-top: 1rem;
}

.director-info-item {
    padding: 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
}

.director-info-item span {
    display: block;
    margin-bottom: .25rem;
    color: #667085;
    font-size: .88rem;
    font-weight: 850;
}

.director-info-item strong {
    display: block;
    color: #101828;
    overflow-wrap: anywhere;
}

.director-profile-top {
    display: flex;
    align-items: center;
    gap: .9rem;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 20px;
    background:
        radial-gradient(circle at top right, rgba(244,196,48,.22), transparent 38%),
        linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
}

.director-profile-avatar {
    width: 58px;
    height: 58px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(244,196,48,.55);
    color: #f4c430;
    font-weight: 1000;
}

.director-profile-top strong {
    display: block;
    color: #fff;
    line-height: 1.25;
}

.director-profile-top span {
    display: block;
    margin-top: .15rem;
    color: rgba(255,255,255,.82);
    font-size: .92rem;
}

@media (max-width: 900px) {
    .director-edit-hero,
    .director-edit-layout {
        grid-template-columns: 1fr;
    }

    .director-edit-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 640px) {
    .director-edit-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-edit-hero,
    .director-edit-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-form-grid {
        grid-template-columns: 1fr;
    }

    .director-edit-actions .btn,
    .director-form-actions .btn {
        width: 100%;
    }
}
</style>

<main class="director-edit-page">
    <div class="container">

        <section class="director-edit-hero">
            <div class="director-edit-avatar">
                <?= htmlspecialchars($initials); ?>
            </div>

            <div>
                <h1><?= $user ? htmlspecialchars($fullName) : "Labot lietotāju"; ?></h1>
                <p>
                    Labot lietotāja profila datus, lomu, statusu un pēc vajadzības nomainīt paroli.
                </p>
            </div>

            <div class="director-edit-actions">
                <?php if ($user): ?>
                    <a class="btn btn-primary btn-sm" href="user_view.php?id=<?= (int)$user["lietotajs_id"]; ?>">
                        <i class="fas fa-eye"></i>
                        Skatīt
                    </a>
                <?php endif; ?>

                <a class="btn btn-outline btn-sm" href="users.php">
                    <i class="fas fa-arrow-left"></i>
                    Atpakaļ
                </a>
            </div>
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

        <?php if ($user): ?>
            <section class="director-edit-layout">

                <article class="director-edit-card">
                    <h2>Rediģēt datus</h2>
                    <p class="director-muted">
                        E-pasts drīkst atkārtoties vairākiem kontiem. Lietotājvārdam gan jābūt unikālam.
                    </p>

                    <form method="post" class="director-form">
                        <input type="hidden" name="id" value="<?= (int)$user["lietotajs_id"]; ?>">

                        <div class="director-form-group">
                            <label for="lietotajvards">Lietotājvārds</label>
                            <input
                                class="director-input"
                                type="text"
                                id="lietotajvards"
                                name="lietotajvards"
                                value="<?= htmlspecialchars($user["lietotajvards"] ?? ""); ?>"
                                required
                            >
                        </div>

                        <div class="director-form-grid">
                            <div class="director-form-group">
                                <label for="vards">Vārds</label>
                                <input
                                    class="director-input"
                                    type="text"
                                    id="vards"
                                    name="vards"
                                    value="<?= htmlspecialchars($user["vards"] ?? ""); ?>"
                                    required
                                >
                            </div>

                            <div class="director-form-group">
                                <label for="uzvards">Uzvārds</label>
                                <input
                                    class="director-input"
                                    type="text"
                                    id="uzvards"
                                    name="uzvards"
                                    value="<?= htmlspecialchars($user["uzvards"] ?? ""); ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="director-form-group">
                            <label for="epasts">E-pasts</label>
                            <input
                                class="director-input"
                                type="email"
                                id="epasts"
                                name="epasts"
                                value="<?= htmlspecialchars($user["epasts"] ?? ""); ?>"
                                required
                            >
                        </div>

                        <div class="director-form-grid">
                            <div class="director-form-group">
                                <label for="loma">Loma</label>
                                <select class="director-select" id="loma" name="loma" required>
                                    <?php
                                        $roles = ["Ceļameklētājs", "Bērns", "Skolēns", "Vecāks", "Skolotājs"];
                                        $currentRole = $user["loma"] ?? "";
                                    ?>

                                    <?php foreach ($roles as $role): ?>
                                        <option value="<?= htmlspecialchars($role); ?>" <?= $currentRole === $role ? "selected" : ""; ?>>
                                            <?= htmlspecialchars($role); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="director-form-group">
                                <label for="statuss">Statuss</label>
                                <select class="director-select" id="statuss" name="statuss" required>
                                    <?php
                                        $statuses = ["aktīvs", "neaktīvs", "bloķēts"];
                                        $currentStatus = $user["statuss"] ?? "";
                                    ?>

                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= htmlspecialchars($status); ?>" <?= $currentStatus === $status ? "selected" : ""; ?>>
                                            <?= htmlspecialchars(ucfirst($status)); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="director-note">
                            <i class="fas fa-key"></i>
                            <span>
                                Ja paroli nevēlies mainīt, atstāj abus paroles laukus tukšus.
                                Ja maini, parolei jābūt vismaz 8 rakstzīmēm.
                            </span>
                        </div>

                        <div class="director-form-grid">
                            <div class="director-form-group">
                                <label for="parole">Jauna parole</label>
                                <input
                                    class="director-input"
                                    type="password"
                                    id="parole"
                                    name="parole"
                                    placeholder="Atstāj tukšu, ja nemaini"
                                >
                            </div>

                            <div class="director-form-group">
                                <label for="parole_atkartoti">Atkārtot paroli</label>
                                <input
                                    class="director-input"
                                    type="password"
                                    id="parole_atkartoti"
                                    name="parole_atkartoti"
                                    placeholder="Atkārto jauno paroli"
                                >
                            </div>
                        </div>

                        <div class="director-form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-floppy-disk"></i>
                                Saglabāt izmaiņas
                            </button>

                            <a href="user_view.php?id=<?= (int)$user["lietotajs_id"]; ?>" class="btn btn-outline">
                                Atcelt
                            </a>
                        </div>
                    </form>
                </article>

                <aside class="director-edit-card">
                    <div class="director-profile-top">
                        <div class="director-profile-avatar">
                            <?= htmlspecialchars($initials); ?>
                        </div>

                        <div>
                            <strong><?= htmlspecialchars($fullName); ?></strong>
                            <span><?= htmlspecialchars($user["loma"] ?? "—"); ?></span>
                        </div>
                    </div>

                    <h2>Konta informācija</h2>
                    <p class="director-muted">
                        Pārskats par kontu un piesaistīto klubu.
                    </p>

                    <div class="director-info-list">
                        <div class="director-info-item">
                            <span>Klubs</span>
                            <strong><?= htmlspecialchars($user["club_name"] ?? "Nav norādīts"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Reģistrēts</span>
                            <strong><?= htmlspecialchars(formatDateLv($user["Reg_datums"] ?? null)); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Lietotāja ID</span>
                            <strong><?= (int)$user["lietotajs_id"]; ?></strong>
                        </div>
                    </div>
                </aside>

            </section>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
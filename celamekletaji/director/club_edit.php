<?php
session_start();

$lapa  = "Labot klubu";
$title = "Labot klubu - Ceļa meklētāji";

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

$club = null;
$churches = [];

$error = trim($_GET["error"] ?? "");
$success = trim($_GET["success"] ?? "");

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: club_edit.php?" . $param . "=" . urlencode($message));
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

/* ===============================
   KOLONNU PĀRBAUDE
================================ */
$clubsTableExists = tableExists($savienojums, "cm_clubs");

$hasName      = $clubsTableExists && tableColumnExists($savienojums, "cm_clubs", "name");
$hasAddress   = $clubsTableExists && tableColumnExists($savienojums, "cm_clubs", "address");
$hasLocation  = $clubsTableExists && tableColumnExists($savienojums, "cm_clubs", "location");
$hasPhone     = $clubsTableExists && tableColumnExists($savienojums, "cm_clubs", "phone");
$hasEmail     = $clubsTableExists && tableColumnExists($savienojums, "cm_clubs", "email");
$hasChurchId  = $clubsTableExists && tableColumnExists($savienojums, "cm_clubs", "church_id");
$hasIsActive  = $clubsTableExists && tableColumnExists($savienojums, "cm_clubs", "is_active");
$hasUpdatedAt = $clubsTableExists && tableColumnExists($savienojums, "cm_clubs", "updated_at");

$churchesTableExists = tableExists($savienojums, "cm_churches");
$hasChurchName = $churchesTableExists && tableColumnExists($savienojums, "cm_churches", "name");

if (!$clubsTableExists) {
    $error = "Tabula cm_clubs nav atrasta.";
}

if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs. Pārbaudi lietotāja club_id datubāzē.";
}

/* ===============================
   DRAUDŽU SARAKSTS
================================ */
if ($churchesTableExists && $hasChurchName) {
    $churchSql = "
        SELECT id, name
        FROM cm_churches
        ORDER BY name ASC
    ";

    if ($result = $savienojums->query($churchSql)) {
        while ($row = $result->fetch_assoc()) {
            $churches[] = $row;
        }
    }
}

/* ===============================
   SAGLABĀŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($error)) {
    $name     = trim($_POST["name"] ?? "");
    $address  = trim($_POST["address"] ?? "");
    $location = trim($_POST["location"] ?? "");
    $phone    = trim($_POST["phone"] ?? "");
    $email    = trim($_POST["email"] ?? "");
    $churchId = (int)($_POST["church_id"] ?? 0);
    $isActive = isset($_POST["is_active"]) ? 1 : 0;

    if ($hasName && $name === "") {
        redirectWithMessage("error", "Kluba nosaukums ir obligāts.");
    }

    if ($hasEmail && $email !== "" && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage("error", "Ievadi korektu e-pasta adresi.");
    }

    $setParts = [];
    $values = [];
    $types = "";

    if ($hasName) {
        $setParts[] = "name = ?";
        $values[] = $name;
        $types .= "s";
    }

    if ($hasAddress) {
        $setParts[] = "address = ?";
        $values[] = $address;
        $types .= "s";
    }

    if ($hasLocation) {
        $setParts[] = "location = ?";
        $values[] = $location;
        $types .= "s";
    }

    if ($hasPhone) {
        $setParts[] = "phone = ?";
        $values[] = $phone;
        $types .= "s";
    }

    if ($hasEmail) {
        $setParts[] = "email = ?";
        $values[] = $email;
        $types .= "s";
    }

    if ($hasChurchId) {
        $setParts[] = "church_id = ?";
        $values[] = $churchId > 0 ? $churchId : null;
        $types .= "i";
    }

    if ($hasIsActive) {
        $setParts[] = "is_active = ?";
        $values[] = $isActive;
        $types .= "i";
    }

    if ($hasUpdatedAt) {
        $setParts[] = "updated_at = NOW()";
    }

    if (empty($setParts)) {
        redirectWithMessage("error", "Nav neviena lauka, ko saglabāt.");
    }

    $values[] = $directorClubId;
    $types .= "i";

    $sql = "
        UPDATE cm_clubs
        SET " . implode(", ", $setParts) . "
        WHERE id = ?
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($sql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot kluba saglabāšanu.");
    }

    $stmt->bind_param($types, ...$values);

    if ($stmt->execute()) {
        $stmt->close();
        redirectWithMessage("success", "Kluba informācija veiksmīgi saglabāta.");
    }

    $stmt->close();

    redirectWithMessage("error", "Neizdevās saglabāt kluba informāciju.");
}

/* ===============================
   KLUBA DATU IELĀDE
================================ */
if (empty($error)) {
    $selectParts = ["c.id"];

    $selectParts[] = $hasName ? "c.name" : "NULL AS name";
    $selectParts[] = $hasAddress ? "c.address" : "NULL AS address";
    $selectParts[] = $hasLocation ? "c.location" : "NULL AS location";
    $selectParts[] = $hasPhone ? "c.phone" : "NULL AS phone";
    $selectParts[] = $hasEmail ? "c.email" : "NULL AS email";
    $selectParts[] = $hasChurchId ? "c.church_id" : "NULL AS church_id";
    $selectParts[] = $hasIsActive ? "c.is_active" : "1 AS is_active";

    if ($hasChurchId && $churchesTableExists && $hasChurchName) {
        $selectParts[] = "ch.name AS church_name";

        $sql = "
            SELECT " . implode(", ", $selectParts) . "
            FROM cm_clubs c
            LEFT JOIN cm_churches ch ON c.church_id = ch.id
            WHERE c.id = ?
            LIMIT 1
        ";
    } else {
        $selectParts[] = "NULL AS church_name";

        $sql = "
            SELECT " . implode(", ", $selectParts) . "
            FROM cm_clubs c
            WHERE c.id = ?
            LIMIT 1
        ";
    }

    $stmt = $savienojums->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $directorClubId);
        $stmt->execute();

        $result = $stmt->get_result();
        $club = $result->fetch_assoc();

        $stmt->close();

        if (!$club) {
            $error = "Klubs netika atrasts.";
        }
    } else {
        $error = "Neizdevās ielādēt kluba datus.";
    }
}

require __DIR__ . "/../includes/templates/header-director.php";
?>

<style>
.director-club-edit-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-club-edit-hero {
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

.director-club-edit-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-club-edit-hero > * {
    position: relative;
    z-index: 1;
}

.director-club-edit-kicker {
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

.director-club-edit-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-club-edit-hero p {
    max-width: 740px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.director-club-edit-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.director-club-edit-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.director-club-edit-hero-card strong {
    display: block;
    font-size: 2rem;
    color: #f4c430;
    line-height: 1.1;
}

.director-club-edit-hero-card span {
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

.director-edit-layout {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
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

.director-check {
    display: flex;
    gap: .65rem;
    align-items: center;
    padding: 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    color: #344054;
    font-weight: 900;
}

.director-check input {
    width: 18px;
    height: 18px;
    accent-color: #173f84;
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

.director-summary {
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

.director-summary-icon {
    width: 58px;
    height: 58px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 18px;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(244,196,48,.55);
    color: #f4c430;
    font-size: 1.4rem;
}

.director-summary strong {
    display: block;
    color: #fff;
    line-height: 1.25;
}

.director-summary span {
    display: block;
    margin-top: .15rem;
    color: rgba(255,255,255,.82);
    font-size: .92rem;
}

@media (max-width: 900px) {
    .director-club-edit-hero,
    .director-edit-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .director-club-edit-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-club-edit-hero,
    .director-edit-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-form-grid {
        grid-template-columns: 1fr;
    }

    .director-club-edit-actions .btn,
    .director-form-actions .btn {
        width: 100%;
    }
}
</style>

<main class="director-club-edit-page">
    <div class="container">

        <section class="director-club-edit-hero">
            <div>
                <div class="director-club-edit-kicker">
                    <i class="fas fa-pen-to-square"></i>
                    Kluba rediģēšana
                </div>

                <h1>Labot klubu</h1>

                <p>
                    Atjauno sava kluba pamatinformāciju — nosaukumu, adresi,
                    kontaktus, draudzi un statusu.
                </p>

                <div class="director-club-edit-actions">
                    <a class="btn btn-primary btn-sm" href="club.php">
                        <i class="fas fa-circle-info"></i>
                        Kluba informācija
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/director.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="director-club-edit-hero-card">
                <strong><?= $club ? htmlspecialchars($club["name"] ?? "Klubs") : "Klubs"; ?></strong>
                <span>
                    Rediģē tikai savam kontam piesaistīto klubu.
                </span>
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

        <?php if ($club): ?>
            <section class="director-edit-layout">

                <article class="director-edit-card">
                    <h2>Kluba dati</h2>
                    <p class="director-muted">
                        Aizpildi vai labo informāciju, kas attiecas uz klubu.
                    </p>

                    <form method="post" class="director-form">

                        <?php if ($hasName): ?>
                            <div class="director-form-group">
                                <label for="name">Kluba nosaukums</label>
                                <input
                                    class="director-input"
                                    type="text"
                                    id="name"
                                    name="name"
                                    value="<?= htmlspecialchars($club["name"] ?? ""); ?>"
                                    required
                                >
                            </div>
                        <?php endif; ?>

                        <?php if ($hasAddress || $hasLocation): ?>
                            <div class="director-form-grid">
                                <?php if ($hasAddress): ?>
                                    <div class="director-form-group">
                                        <label for="address">Adrese</label>
                                        <input
                                            class="director-input"
                                            type="text"
                                            id="address"
                                            name="address"
                                            value="<?= htmlspecialchars($club["address"] ?? ""); ?>"
                                        >
                                    </div>
                                <?php endif; ?>

                                <?php if ($hasLocation): ?>
                                    <div class="director-form-group">
                                        <label for="location">Atrašanās vieta</label>
                                        <input
                                            class="director-input"
                                            type="text"
                                            id="location"
                                            name="location"
                                            value="<?= htmlspecialchars($club["location"] ?? ""); ?>"
                                            placeholder="Piemēram: Grobiņa"
                                        >
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($hasPhone || $hasEmail): ?>
                            <div class="director-form-grid">
                                <?php if ($hasPhone): ?>
                                    <div class="director-form-group">
                                        <label for="phone">Tālrunis</label>
                                        <input
                                            class="director-input"
                                            type="text"
                                            id="phone"
                                            name="phone"
                                            value="<?= htmlspecialchars($club["phone"] ?? ""); ?>"
                                        >
                                    </div>
                                <?php endif; ?>

                                <?php if ($hasEmail): ?>
                                    <div class="director-form-group">
                                        <label for="email">E-pasts</label>
                                        <input
                                            class="director-input"
                                            type="email"
                                            id="email"
                                            name="email"
                                            value="<?= htmlspecialchars($club["email"] ?? ""); ?>"
                                        >
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($hasChurchId && !empty($churches)): ?>
                            <div class="director-form-group">
                                <label for="church_id">Draudze</label>
                                <select class="director-select" id="church_id" name="church_id">
                                    <option value="0">Nav norādīta</option>

                                    <?php foreach ($churches as $church): ?>
                                        <option
                                            value="<?= (int)$church["id"]; ?>"
                                            <?= ((int)($club["church_id"] ?? 0) === (int)$church["id"]) ? "selected" : ""; ?>
                                        >
                                            <?= htmlspecialchars($church["name"]); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <?php if ($hasIsActive): ?>
                            <label class="director-check">
                                <input
                                    type="checkbox"
                                    name="is_active"
                                    value="1"
                                    <?= ((int)($club["is_active"] ?? 0) === 1) ? "checked" : ""; ?>
                                >
                                Klubs ir aktīvs
                            </label>
                        <?php endif; ?>

                        <div class="director-form-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-floppy-disk"></i>
                                Saglabāt izmaiņas
                            </button>

                            <a href="club.php" class="btn btn-outline">
                                Atcelt
                            </a>
                        </div>
                    </form>
                </article>

                <aside class="director-edit-card">
                    <div class="director-summary">
                        <div class="director-summary-icon">
                            <i class="fas fa-people-roof"></i>
                        </div>

                        <div>
                            <strong><?= htmlspecialchars($club["name"] ?? "Klubs"); ?></strong>
                            <span><?= htmlspecialchars($club["address"] ?? "Adrese nav norādīta"); ?></span>
                        </div>
                    </div>

                    <h2>Pašreizējā informācija</h2>
                    <p class="director-muted">
                        Šeit redzi, kas pašlaik saglabāts par klubu.
                    </p>

                    <div class="director-info-list">
                        <div class="director-info-item">
                            <span>Nosaukums</span>
                            <strong><?= htmlspecialchars($club["name"] ?? "—"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Adrese</span>
                            <strong><?= htmlspecialchars($club["address"] ?? "Nav norādīta"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Draudze</span>
                            <strong><?= htmlspecialchars($club["church_name"] ?? "Nav norādīta"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Statuss</span>
                            <strong>
                                <?= ((int)($club["is_active"] ?? 0) === 1) ? "Aktīvs" : "Neaktīvs"; ?>
                            </strong>
                        </div>
                    </div>
                </aside>

            </section>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
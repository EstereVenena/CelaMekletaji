<?php
session_start();

$lapa  = "Pievienot lietotāju";
$title = "Pievienot lietotāju - Ceļa meklētāji";

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

$success = trim($_GET["success"] ?? "");
$error   = trim($_GET["error"] ?? "");

$form = [
    "lietotajvards" => "",
    "vards" => "",
    "uzvards" => "",
    "epasts" => "",
    "loma" => "Ceļameklētājs",
    "statuss" => "aktīvs",
];

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: add_user.php?" . $param . "=" . urlencode($message));
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
    $hasNosaukums = tableColumnExists($db, "cm_lomas", "nosaukums");
    $hasLomasNosaukums = tableColumnExists($db, "cm_lomas", "lomas_nosaukums");
    $hasLomaId = tableColumnExists($db, "cm_lomas", "loma_id");

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

/* ===============================
   KLUBA PĀRBAUDE
================================ */
if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs. Pārbaudi lietotāja club_id datubāzē.";
}

/* ===============================
   LIETOTĀJA PIEVIENOŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($error)) {
    $lietotajvards = trim($_POST["lietotajvards"] ?? "");
    $vards         = trim($_POST["vards"] ?? "");
    $uzvards       = trim($_POST["uzvards"] ?? "");
    $epasts        = trim($_POST["epasts"] ?? "");
    $parole        = trim($_POST["parole"] ?? "");
    $loma          = trim($_POST["loma"] ?? "");
    $statuss       = trim($_POST["statuss"] ?? "aktīvs");

    $form = [
        "lietotajvards" => $lietotajvards,
        "vards" => $vards,
        "uzvards" => $uzvards,
        "epasts" => $epasts,
        "loma" => $loma,
        "statuss" => $statuss,
    ];

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
    ];

    if ($lietotajvards === "" || $vards === "" || $uzvards === "" || $epasts === "" || $parole === "" || $loma === "") {
        $error = "Aizpildi visus obligātos laukus.";
    } elseif (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        $error = "Ievadi korektu e-pasta adresi.";
    } elseif (mb_strlen($parole) < 8) {
        $error = "Parolei jābūt vismaz 8 rakstzīmēm garai.";
    } elseif (!in_array($loma, $allowedNewRoles, true)) {
        $error = "Izvēlēta nederīga loma.";
    } elseif (!in_array($statuss, $allowedStatuses, true)) {
        $error = "Izvēlēts nederīgs statuss.";
    }

    /*
       E-pastu speciāli nepārbaudām kā unikālu,
       jo sistēmā viens e-pasts drīkst būt vairākiem kontiem.
    */

    if (empty($error)) {
        $passwordHash = password_hash($parole, PASSWORD_DEFAULT);
        $lomaId = getRoleId($savienojums, $loma);

        $hasRegDatums = tableColumnExists($savienojums, "cm_lietotaji", "Reg_datums");

        if ($hasRegDatums) {
            $sql = "
                INSERT INTO cm_lietotaji
                    (lietotajvards, vards, uzvards, epasts, parole, loma_id, loma, statuss, club_id, Reg_datums)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ";
        } else {
            $sql = "
                INSERT INTO cm_lietotaji
                    (lietotajvards, vards, uzvards, epasts, parole, loma_id, loma, statuss, club_id)
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";
        }

        $stmt = $savienojums->prepare($sql);

        if (!$stmt) {
            $error = "Neizdevās sagatavot lietotāja pievienošanu.";
        } else {
            $stmt->bind_param(
                "sssssissi",
                $lietotajvards,
                $vards,
                $uzvards,
                $epasts,
                $passwordHash,
                $lomaId,
                $loma,
                $statuss,
                $directorClubId
            );

            if ($stmt->execute()) {
                $stmt->close();
                redirectWithMessage("success", "Lietotājs veiksmīgi pievienots.");
            }

            $stmt->close();

            if ($savienojums->errno === 1062) {
                $error = "Šāds lietotājvārds jau eksistē. Izvēlies citu.";
            } else {
                $error = "Neizdevās pievienot lietotāju.";
            }
        }
    }
}

require __DIR__ . "/../includes/templates/header-director.php";
?>

<style>
.director-add-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-add-hero {
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

.director-add-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-add-hero > * {
    position: relative;
    z-index: 1;
}

.director-add-kicker {
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

.director-add-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-add-hero p {
    max-width: 740px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.director-add-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.director-add-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.director-add-hero-card strong {
    display: block;
    font-size: 2rem;
    color: #f4c430;
    line-height: 1.1;
}

.director-add-hero-card span {
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

.director-form-layout {
    display: grid;
    grid-template-columns: 1.15fr .85fr;
    gap: 1.1rem;
    align-items: start;
}

.director-form-card {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.director-form-card h2 {
    margin: 0 0 .35rem;
    color: #173f84;
    font-size: 1.35rem;
}

.director-form-card p {
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

.director-help-list {
    display: grid;
    gap: .8rem;
    margin-top: 1rem;
}

.director-help-item {
    display: flex;
    gap: .75rem;
    padding: 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
}

.director-help-icon {
    width: 42px;
    height: 42px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 14px;
    background: #eef3ff;
    color: #173f84;
}

.director-help-item strong {
    display: block;
    color: #101828;
    margin-bottom: .2rem;
}

.director-help-item span {
    display: block;
    color: #667085;
    line-height: 1.5;
}

@media (max-width: 900px) {
    .director-add-hero,
    .director-form-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .director-add-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-add-hero,
    .director-form-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-form-grid {
        grid-template-columns: 1fr;
    }

    .director-add-actions .btn,
    .director-form-actions .btn {
        width: 100%;
    }
}
</style>

<main class="director-add-page">
    <div class="container">

        <section class="director-add-hero">
            <div>
                <div class="director-add-kicker">
                    <i class="fas fa-user-plus"></i>
                    Lietotāja pievienošana
                </div>

                <h1>Pievienot lietotāju</h1>

                <p>
                    Izveido jaunu bērna, vecāka vai skolotāja kontu savā klubā.
                    Jaunais lietotājs automātiski tiks piesaistīts tavam klubam.
                </p>

                <div class="director-add-actions">
                    <a class="btn btn-primary btn-sm" href="users.php">
                        <i class="fas fa-users"></i>
                        Lietotāju saraksts
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/director.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="director-add-hero-card">
                <strong>Jauns konts</strong>
                <span>
                    E-pasts drīkst atkārtoties, bet lietotājvārdam jābūt unikālam.
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

        <section class="director-form-layout">

            <article class="director-form-card">
                <h2>Lietotāja dati</h2>
                <p>Aizpildi konta informāciju. Parole tiks droši saglabāta kā hash.</p>

                <form method="post" class="director-form">

                    <div class="director-form-group">
                        <label for="lietotajvards">Lietotājvārds</label>
                        <input
                            class="director-input"
                            type="text"
                            id="lietotajvards"
                            name="lietotajvards"
                            value="<?= htmlspecialchars($form["lietotajvards"]); ?>"
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
                                value="<?= htmlspecialchars($form["vards"]); ?>"
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
                                value="<?= htmlspecialchars($form["uzvards"]); ?>"
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
                            value="<?= htmlspecialchars($form["epasts"]); ?>"
                            required
                        >
                    </div>

                    <div class="director-form-grid">
                        <div class="director-form-group">
                            <label for="parole">Parole</label>
                            <input
                                class="director-input"
                                type="password"
                                id="parole"
                                name="parole"
                                minlength="8"
                                placeholder="Vismaz 8 rakstzīmes"
                                required
                            >
                        </div>

                        <div class="director-form-group">
                            <label for="statuss">Statuss</label>
                            <select class="director-select" id="statuss" name="statuss">
                                <option value="aktīvs" <?= $form["statuss"] === "aktīvs" ? "selected" : ""; ?>>
                                    Aktīvs
                                </option>
                                <option value="neaktīvs" <?= $form["statuss"] === "neaktīvs" ? "selected" : ""; ?>>
                                    Neaktīvs
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="director-form-group">
                        <label for="loma">Loma</label>
                        <select class="director-select" id="loma" name="loma" required>
                            <option value="Ceļameklētājs" <?= $form["loma"] === "Ceļameklētājs" ? "selected" : ""; ?>>
                                Ceļameklētājs
                            </option>

                            <option value="Bērns" <?= $form["loma"] === "Bērns" ? "selected" : ""; ?>>
                                Bērns
                            </option>

                            <option value="Skolēns" <?= $form["loma"] === "Skolēns" ? "selected" : ""; ?>>
                                Skolēns
                            </option>

                            <option value="Vecāks" <?= $form["loma"] === "Vecāks" ? "selected" : ""; ?>>
                                Vecāks
                            </option>

                            <option value="Skolotājs" <?= $form["loma"] === "Skolotājs" ? "selected" : ""; ?>>
                                Skolotājs
                            </option>
                        </select>
                    </div>

                    <div class="director-note">
                        <i class="fas fa-circle-info"></i>
                        <span>
                            Lietotājs tiks piesaistīts tavam klubam pēc <code>club_id</code>.
                            E-pasts sistēmā drīkst atkārtoties, bet lietotājvārdam vēlams būt unikālam.
                        </span>
                    </div>

                    <div class="director-form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i>
                            Saglabāt lietotāju
                        </button>

                        <a href="users.php" class="btn btn-outline">
                            Atcelt
                        </a>
                    </div>
                </form>
            </article>

            <aside class="director-form-card">
                <h2>Ko izvēlēties?</h2>
                <p>Lomu izvēle nosaka, kurā sadaļā lietotājs būs redzams.</p>

                <div class="director-help-list">
                    <div class="director-help-item">
                        <div class="director-help-icon">
                            <i class="fas fa-child-reaching"></i>
                        </div>
                        <div>
                            <strong>Ceļameklētājs / Bērns / Skolēns</strong>
                            <span>Parādīsies bērnu jeb ceļameklētāju sadaļā.</span>
                        </div>
                    </div>

                    <div class="director-help-item">
                        <div class="director-help-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div>
                            <strong>Vecāks</strong>
                            <span>Parādīsies vecāku sadaļā un varēs pārvaldīt bērnu informāciju.</span>
                        </div>
                    </div>

                    <div class="director-help-item">
                        <div class="director-help-icon">
                            <i class="fas fa-chalkboard-user"></i>
                        </div>
                        <div>
                            <strong>Skolotājs</strong>
                            <span>Parādīsies skolotāju sadaļā.</span>
                        </div>
                    </div>
                </div>
            </aside>

        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
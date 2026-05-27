<?php
session_start();

$lapa  = "Mans profils";
$title = "Mans profils - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   PIEKĻUVES PĀRBAUDE
================================ */
$allowedRoles = ["Skolēns", "Ceļameklētājs", "Bērns", "student", "child"];

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), $allowedRoles, true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$userId = (int) $_SESSION["lietotajs_id"];
$user = null;

$success = trim($_GET["success"] ?? "");
$error   = trim($_GET["error"] ?? "");

function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: profile.php?" . $param . "=" . urlencode($message));
    exit();
}

function formatDateTimeLv(?string $date): string
{
    if (empty($date)) {
        return "—";
    }

    return date("d.m.Y H:i", strtotime($date));
}

/* ===============================
   PROFILA SAGLABĀŠANA
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $vards = trim($_POST["vards"] ?? "");
    $uzvards = trim($_POST["uzvards"] ?? "");
    $epasts = trim($_POST["epasts"] ?? "");
    $parole = trim($_POST["parole"] ?? "");
    $paroleAtkartoti = trim($_POST["parole_atkartoti"] ?? "");

    if ($vards === "" || $uzvards === "" || $epasts === "") {
        redirectWithMessage("error", "Aizpildi vārdu, uzvārdu un e-pastu.");
    }

    if (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        redirectWithMessage("error", "Ievadi korektu e-pasta adresi.");
    }

    if ($parole !== "" || $paroleAtkartoti !== "") {
        if ($parole !== $paroleAtkartoti) {
            redirectWithMessage("error", "Paroles nesakrīt.");
        }

        if (mb_strlen($parole) < 8) {
            redirectWithMessage("error", "Parolei jābūt vismaz 8 rakstzīmēm garai.");
        }

        $passwordHash = password_hash($parole, PASSWORD_DEFAULT);

        $sql = "
            UPDATE cm_lietotaji
            SET vards = ?, uzvards = ?, epasts = ?, parole = ?
            WHERE lietotajs_id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if (!$stmt) {
            redirectWithMessage("error", "Neizdevās sagatavot profila saglabāšanu.");
        }

        $stmt->bind_param("ssssi", $vards, $uzvards, $epasts, $passwordHash, $userId);
    } else {
        $sql = "
            UPDATE cm_lietotaji
            SET vards = ?, uzvards = ?, epasts = ?
            WHERE lietotajs_id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($sql);

        if (!$stmt) {
            redirectWithMessage("error", "Neizdevās sagatavot profila saglabāšanu.");
        }

        $stmt->bind_param("sssi", $vards, $uzvards, $epasts, $userId);
    }

    if ($stmt->execute()) {
        $_SESSION["lietotajvards"] = trim($vards . " " . $uzvards);
        $stmt->close();
        redirectWithMessage("success", "Profils veiksmīgi atjaunots.");
    }

    $stmt->close();
    redirectWithMessage("error", "Neizdevās saglabāt profilu.");
}

/* ===============================
   PROFILA DATI
================================ */
$sql = "
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
    WHERE lietotajs_id = ?
    LIMIT 1
";

$stmt = $savienojums->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    $stmt->close();

    if (!$user) {
        $error = "Lietotāja dati nav atrasti.";
    }
} else {
    $error = "Neizdevās ielādēt profilu.";
}

require __DIR__ . "/../includes/templates/header-student.php";
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <div class="page-hero-content">
            <h1>Mans profils</h1>
            <p class="lead">
                Atjauno savu kontaktinformāciju un, ja nepieciešams, nomaini paroli.
            </p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if (!empty($success)): ?>
            <div class="card" style="margin-bottom:1rem; border-left:4px solid #2e9e44;">
                <p class="muted"><?= htmlspecialchars($success) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="card" style="margin-bottom:1rem; border-left:4px solid #c0392b;">
                <p class="muted"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <div class="dashboard-content">

                <div class="dashboard-card">
                    <h3>Profila dati</h3>
                    <p class="muted">
                        Lietotājvārdu un lomu šeit nemaina — lai nesākas datubāzes rodeo.
                    </p>
                    <div class="divider"></div>

                    <form method="post" class="auth-form">

                        <div class="form-row">
                            <div class="form-group">
                                <label for="vards">Vārds</label>
                                <input
                                    type="text"
                                    id="vards"
                                    name="vards"
                                    value="<?= htmlspecialchars($user["vards"] ?? "") ?>"
                                    required
                                >
                            </div>

                            <div class="form-group">
                                <label for="uzvards">Uzvārds</label>
                                <input
                                    type="text"
                                    id="uzvards"
                                    name="uzvards"
                                    value="<?= htmlspecialchars($user["uzvards"] ?? "") ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="epasts">E-pasts</label>
                            <input
                                type="email"
                                id="epasts"
                                name="epasts"
                                value="<?= htmlspecialchars($user["epasts"] ?? "") ?>"
                                required
                            >
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="parole">Jauna parole</label>
                                <input
                                    type="password"
                                    id="parole"
                                    name="parole"
                                    placeholder="Atstāj tukšu, ja nemaini"
                                >
                            </div>

                            <div class="form-group">
                                <label for="parole_atkartoti">Atkārtot paroli</label>
                                <input
                                    type="password"
                                    id="parole_atkartoti"
                                    name="parole_atkartoti"
                                    placeholder="Atkārto jauno paroli"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            Saglabāt izmaiņas
                        </button>
                    </form>
                </div>

                <div class="dashboard-card">
                    <h3>Konta informācija</h3>
                    <p class="muted">Īss pārskats par tavu kontu.</p>
                    <div class="divider"></div>

                    <div class="cards">
                        <div class="card">
                            <p class="muted small">Lietotājvārds</p>
                            <h4><?= htmlspecialchars($user["lietotajvards"] ?? "—") ?></h4>
                        </div>

                        <div class="card">
                            <p class="muted small">Loma</p>
                            <h4><?= htmlspecialchars($user["loma"] ?? "—") ?></h4>
                        </div>

                        <div class="card">
                            <p class="muted small">Statuss</p>
                            <h4><?= htmlspecialchars($user["statuss"] ?? "—") ?></h4>
                        </div>

                        <div class="card">
                            <p class="muted small">Reģistrēts</p>
                            <h4><?= htmlspecialchars(formatDateTimeLv($user["Reg_datums"] ?? null)) ?></h4>
                        </div>
                    </div>
                </div>

            </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>

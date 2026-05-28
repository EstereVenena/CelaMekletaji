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

/* ===============================
   PALĪGFUNKCIJAS
================================ */
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

$fullName = trim(($user["vards"] ?? "") . " " . ($user["uzvards"] ?? ""));
$fullName = $fullName !== "" ? $fullName : ($user["lietotajvards"] ?? "Ceļameklētājs");

$initials = "C";

if ($fullName !== "") {
    $parts = preg_split('/\s+/', $fullName);

    if (!empty($parts[0])) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));

        if (!empty($parts[1])) {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }
    }
}

require __DIR__ . "/../includes/templates/header-student.php";
?>

<style>
.student-profile-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.profile-hero {
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

.profile-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.profile-hero > * {
    position: relative;
    z-index: 1;
}

.profile-avatar-big {
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

.profile-hero h1 {
    margin: 0 0 .45rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.profile-hero p {
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.7;
}

.profile-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.profile-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 800;
}

.profile-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.profile-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.profile-layout {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 1.1rem;
    align-items: start;
}

.profile-card {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.profile-card h2 {
    margin: 0 0 .35rem;
    color: #173f84;
    font-size: 1.35rem;
}

.profile-card p {
    margin-top: 0;
}

.profile-muted {
    color: #667085;
    line-height: 1.6;
}

.profile-form {
    display: grid;
    gap: 1rem;
    margin-top: 1rem;
}

.profile-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .85rem;
}

.profile-form-group {
    display: grid;
    gap: .4rem;
}

.profile-form-group label {
    color: #344054;
    font-weight: 900;
}

.profile-input {
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

.profile-input:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.profile-password-note {
    display: flex;
    gap: .6rem;
    padding: .95rem 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #e8eef8;
    color: #667085;
    line-height: 1.55;
}

.profile-password-note i {
    color: #1e4fa1;
    margin-top: .2rem;
}

.profile-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .3rem;
}

.profile-info-grid {
    display: grid;
    gap: .8rem;
    margin-top: 1rem;
}

.profile-info-item {
    padding: 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
}

.profile-info-item span {
    display: block;
    margin-bottom: .25rem;
    color: #667085;
    font-size: .88rem;
    font-weight: 800;
}

.profile-info-item strong {
    display: block;
    color: #101828;
    overflow-wrap: anywhere;
}

.profile-status-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .7rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-weight: 950;
}

.profile-side-top {
    display: flex;
    align-items: center;
    gap: .8rem;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 20px;
    background:
        radial-gradient(circle at top right, rgba(244,196,48,.22), transparent 38%),
        linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
}

.profile-side-avatar {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(244,196,48,.55);
    color: #f4c430;
    font-weight: 1000;
}

.profile-side-top strong {
    display: block;
    color: #fff;
    line-height: 1.25;
}

.profile-side-top span {
    display: block;
    margin-top: .15rem;
    color: rgba(255,255,255,.82);
    font-size: .9rem;
}

@media (max-width: 900px) {
    .profile-hero {
        grid-template-columns: 1fr;
    }

    .profile-hero-actions {
        justify-content: flex-start;
    }

    .profile-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .student-profile-page {
        padding: 1.5rem 0 2.5rem;
    }

    .profile-hero,
    .profile-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .profile-form-grid {
        grid-template-columns: 1fr;
    }

    .profile-actions .btn,
    .profile-hero-actions .btn {
        width: 100%;
    }
}
</style>

<main class="student-profile-page">
    <div class="container">

        <section class="profile-hero">
            <div class="profile-avatar-big">
                <?= htmlspecialchars($initials); ?>
            </div>

            <div>
                <h1>Mans profils</h1>
                <p>
                    Atjauno kontaktinformāciju, pārbaudi konta statusu un, ja nepieciešams,
                    nomaini paroli.
                </p>
            </div>

            <div class="profile-hero-actions">
                <a class="btn btn-primary btn-sm" href="../dashboards/student.php">
                    <i class="fas fa-arrow-left"></i>
                    Atpakaļ uz paneli
                </a>
            </div>
        </section>

        <?php if (!empty($success)): ?>
            <div class="profile-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="profile-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <section class="profile-layout">

                <article class="profile-card">
                    <h2>Rediģēt profilu</h2>
                    <p class="profile-muted">
                        Lietotājvārdu un lomu šeit nemaina. Tos pārvalda sistēmas administrators.
                    </p>

                    <form method="post" class="profile-form">

                        <div class="profile-form-grid">
                            <div class="profile-form-group">
                                <label for="vards">Vārds</label>
                                <input
                                    class="profile-input"
                                    type="text"
                                    id="vards"
                                    name="vards"
                                    value="<?= htmlspecialchars($user["vards"] ?? ""); ?>"
                                    required
                                >
                            </div>

                            <div class="profile-form-group">
                                <label for="uzvards">Uzvārds</label>
                                <input
                                    class="profile-input"
                                    type="text"
                                    id="uzvards"
                                    name="uzvards"
                                    value="<?= htmlspecialchars($user["uzvards"] ?? ""); ?>"
                                    required
                                >
                            </div>
                        </div>

                        <div class="profile-form-group">
                            <label for="epasts">E-pasts</label>
                            <input
                                class="profile-input"
                                type="email"
                                id="epasts"
                                name="epasts"
                                value="<?= htmlspecialchars($user["epasts"] ?? ""); ?>"
                                required
                            >
                        </div>

                        <div class="profile-password-note">
                            <i class="fas fa-key"></i>
                            <span>
                                Ja paroli nevēlies mainīt, atstāj abus paroles laukus tukšus.
                                Ja maini, parolei jābūt vismaz 8 rakstzīmēm.
                            </span>
                        </div>

                        <div class="profile-form-grid">
                            <div class="profile-form-group">
                                <label for="parole">Jauna parole</label>
                                <input
                                    class="profile-input"
                                    type="password"
                                    id="parole"
                                    name="parole"
                                    placeholder="Atstāj tukšu, ja nemaini"
                                >
                            </div>

                            <div class="profile-form-group">
                                <label for="parole_atkartoti">Atkārtot paroli</label>
                                <input
                                    class="profile-input"
                                    type="password"
                                    id="parole_atkartoti"
                                    name="parole_atkartoti"
                                    placeholder="Atkārto jauno paroli"
                                >
                            </div>
                        </div>

                        <div class="profile-actions">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-floppy-disk"></i>
                                Saglabāt izmaiņas
                            </button>

                            <a href="../dashboards/student.php" class="btn btn-outline">
                                Atcelt
                            </a>
                        </div>
                    </form>
                </article>

                <aside class="profile-card">
                    <div class="profile-side-top">
                        <div class="profile-side-avatar">
                            <?= htmlspecialchars($initials); ?>
                        </div>

                        <div>
                            <strong><?= htmlspecialchars($fullName); ?></strong>
                            <span><?= htmlspecialchars($user["loma"] ?? "Ceļameklētājs"); ?></span>
                        </div>
                    </div>

                    <h2>Konta informācija</h2>
                    <p class="profile-muted">Īss pārskats par tavu kontu.</p>

                    <div class="profile-info-grid">
                        <div class="profile-info-item">
                            <span>Lietotājvārds</span>
                            <strong><?= htmlspecialchars($user["lietotajvards"] ?? "—"); ?></strong>
                        </div>

                        <div class="profile-info-item">
                            <span>Loma</span>
                            <strong><?= htmlspecialchars($user["loma"] ?? "—"); ?></strong>
                        </div>

                        <div class="profile-info-item">
                            <span>Statuss</span>
                            <strong>
                                <span class="profile-status-pill">
                                    <i class="fas fa-circle-check"></i>
                                    <?= htmlspecialchars($user["statuss"] ?? "—"); ?>
                                </span>
                            </strong>
                        </div>

                        <div class="profile-info-item">
                            <span>Reģistrēts</span>
                            <strong><?= htmlspecialchars(formatDateTimeLv($user["Reg_datums"] ?? null)); ?></strong>
                        </div>
                    </div>
                </aside>

            </section>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
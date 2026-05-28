<?php
session_start();

$lapa  = "Mans profils";
$title = "Mans profils - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION["lietotajs_id"];
$error = null;
$success = null;

$user = [
    "lietotajvards" => "",
    "vards" => "",
    "uzvards" => "",
    "epasts" => "",
    "loma" => "",
    "statuss" => "",
    "Reg_datums" => ""
];

/* ===============================
   SAGLABĀ PROFILU
================================ */
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lietotajvards = trim($_POST["lietotajvards"] ?? "");
    $vards         = trim($_POST["vards"] ?? "");
    $uzvards       = trim($_POST["uzvards"] ?? "");
    $epasts        = trim($_POST["epasts"] ?? "");
    $parole        = trim($_POST["parole"] ?? "");
    $parole2       = trim($_POST["parole2"] ?? "");

    if ($lietotajvards === "" || $vards === "" || $uzvards === "" || $epasts === "") {
        $error = "Lūdzu aizpildiet visus obligātos laukus.";
    } elseif (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        $error = "Lūdzu ievadiet derīgu e-pasta adresi.";
    } elseif ($parole !== "" && strlen($parole) < 6) {
        $error = "Jaunajai parolei jābūt vismaz 6 simboliem.";
    } elseif ($parole !== "" && $parole !== $parole2) {
        $error = "Paroles nesakrīt.";
    } else {
        $checkSql = "
            SELECT lietotajs_id 
            FROM cm_lietotaji 
            WHERE lietotajvards = ? 
              AND lietotajs_id <> ?
            LIMIT 1
        ";

        if ($stmt = $savienojums->prepare($checkSql)) {
            $stmt->bind_param("si", $lietotajvards, $parentId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Šāds lietotājvārds jau eksistē.";
            }

            $stmt->close();
        } else {
            $error = "Neizdevās pārbaudīt lietotājvārdu.";
        }

        if (!$error) {
            if ($parole !== "") {
                $hashedPassword = password_hash($parole, PASSWORD_DEFAULT);

                $updateSql = "
                    UPDATE cm_lietotaji
                    SET lietotajvards = ?,
                        vards = ?,
                        uzvards = ?,
                        epasts = ?,
                        parole = ?
                    WHERE lietotajs_id = ?
                ";

                $stmt = $savienojums->prepare($updateSql);

                if ($stmt) {
                    $stmt->bind_param(
                        "sssssi",
                        $lietotajvards,
                        $vards,
                        $uzvards,
                        $epasts,
                        $hashedPassword,
                        $parentId
                    );
                }
            } else {
                $updateSql = "
                    UPDATE cm_lietotaji
                    SET lietotajvards = ?,
                        vards = ?,
                        uzvards = ?,
                        epasts = ?
                    WHERE lietotajs_id = ?
                ";

                $stmt = $savienojums->prepare($updateSql);

                if ($stmt) {
                    $stmt->bind_param(
                        "ssssi",
                        $lietotajvards,
                        $vards,
                        $uzvards,
                        $epasts,
                        $parentId
                    );
                }
            }

            if (!$stmt) {
                $error = "Neizdevās sagatavot profila saglabāšanu.";
            } elseif ($stmt->execute()) {
                $_SESSION["lietotajvards"] = $lietotajvards;
                $success = "Profils veiksmīgi atjaunināts.";
            } else {
                $error = "Neizdevās saglabāt izmaiņas.";
            }

            if ($stmt) {
                $stmt->close();
            }
        }
    }
}

/* ===============================
   IELĀDĒ PROFILU
================================ */
$sql = "
    SELECT 
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

if ($stmt = $savienojums->prepare($sql)) {
    $stmt->bind_param("i", $parentId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user = $row;
    } else {
        $error = "Profila dati netika atrasti.";
    }

    $stmt->close();
} else {
    $error = "Neizdevās sagatavot profila vaicājumu.";
}

$fullName = trim(($user["vards"] ?? "") . " " . ($user["uzvards"] ?? ""));
$fullName = $fullName !== "" ? $fullName : ($user["lietotajvards"] ?? "Vecāks");
$initials = mb_strtoupper(mb_substr($fullName, 0, 1));

$registered = !empty($user["Reg_datums"])
    ? date("d.m.Y H:i", strtotime($user["Reg_datums"]))
    : "—";

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<style>
.parent-profile-page {
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
    gap: 1.2rem;
    align-items: center;
    margin-bottom: 1.4rem;
    padding: 1.8rem;
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

.profile-avatar {
    width: 82px;
    height: 82px;
    display: grid;
    place-items: center;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(244,196,48,.55);
    color: #f4c430;
    font-size: 2.1rem;
    font-weight: 1000;
}

.profile-hero h1 {
    margin: 0 0 .35rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.profile-hero p {
    margin: 0;
    color: rgba(255,255,255,.88);
    line-height: 1.6;
}

.profile-hero-actions {
    display: flex;
    gap: .65rem;
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

.profile-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.profile-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.profile-layout {
    display: grid;
    grid-template-columns: .85fr 1.15fr;
    gap: 1.2rem;
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
    font-size: 1.3rem;
}

.profile-sub {
    margin: 0 0 1rem;
    color: #667085;
}

.profile-info-list {
    display: grid;
    gap: .75rem;
}

.profile-info-row {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: .9rem 1rem;
    border-radius: 16px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
}

.profile-info-row span {
    color: #667085;
    font-weight: 800;
}

.profile-info-row strong {
    color: #101828;
    text-align: right;
    overflow-wrap: anywhere;
}

.profile-form {
    display: grid;
    gap: 1rem;
}

.profile-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .9rem;
}

.profile-form-group {
    display: grid;
    gap: .4rem;
}

.profile-form-group label {
    color: #344054;
    font-weight: 900;
}

.profile-form-control {
    width: 100%;
    padding: .85rem .95rem;
    border-radius: 14px;
    border: 1px solid #d0d8e8;
    background: #fff;
    color: #101828;
    font: inherit;
    outline: none;
    transition: .2s ease;
}

.profile-form-control:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.profile-section-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: .4rem 0 0;
    color: #173f84;
    font-size: 1.05rem;
    font-weight: 1000;
}

.profile-note {
    padding: .95rem 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #e6eefb;
    color: #667085;
    line-height: 1.55;
}

.profile-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .3rem;
}

.profile-actions .btn {
    min-width: 160px;
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
    .parent-profile-page {
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

    .profile-info-row {
        flex-direction: column;
    }

    .profile-info-row strong {
        text-align: left;
    }

    .profile-actions .btn,
    .profile-hero-actions .btn {
        width: 100%;
    }
}
</style>

<main class="parent-profile-page">
    <div class="container">

        <section class="profile-hero">
            <div class="profile-avatar">
                <?= htmlspecialchars($initials); ?>
            </div>

            <div>
                <h1><?= htmlspecialchars($fullName); ?></h1>
                <p>
                    Vecāka profila informācija, kontaktinformācija un paroles maiņa.
                </p>
            </div>

            <div class="profile-hero-actions">
                <a href="<?= BASE_URL ?>dashboards/parent.php" class="btn btn-primary btn-sm">
                    <i class="fas fa-arrow-left"></i>
                    Atpakaļ uz paneli
                </a>
            </div>
        </section>

        <?php if ($error): ?>
            <div class="profile-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="profile-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <section class="profile-layout">

            <aside class="profile-card">
                <h2>Profila informācija</h2>
                <p class="profile-sub">Pamata konta dati un sistēmas statuss.</p>

                <div class="profile-info-list">
                    <div class="profile-info-row">
                        <span>Lietotājvārds</span>
                        <strong><?= htmlspecialchars($user["lietotajvards"] ?? "—"); ?></strong>
                    </div>

                    <div class="profile-info-row">
                        <span>E-pasts</span>
                        <strong><?= htmlspecialchars($user["epasts"] ?? "—"); ?></strong>
                    </div>

                    <div class="profile-info-row">
                        <span>Loma</span>
                        <strong><?= htmlspecialchars($user["loma"] ?? "—"); ?></strong>
                    </div>

                    <div class="profile-info-row">
                        <span>Statuss</span>
                        <strong><?= htmlspecialchars($user["statuss"] ?? "—"); ?></strong>
                    </div>

                    <div class="profile-info-row">
                        <span>Reģistrēts</span>
                        <strong><?= htmlspecialchars($registered); ?></strong>
                    </div>
                </div>
            </aside>

            <section class="profile-card">
                <h2>Rediģēt profilu</h2>
                <p class="profile-sub">Maini tikai tos datus, kurus nepieciešams atjaunināt.</p>

                <form method="post" class="profile-form">

                    <div class="profile-form-group">
                        <label for="lietotajvards">Lietotājvārds *</label>
                        <input
                            class="profile-form-control"
                            type="text"
                            id="lietotajvards"
                            name="lietotajvards"
                            value="<?= htmlspecialchars($user["lietotajvards"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="profile-form-grid">
                        <div class="profile-form-group">
                            <label for="vards">Vārds *</label>
                            <input
                                class="profile-form-control"
                                type="text"
                                id="vards"
                                name="vards"
                                value="<?= htmlspecialchars($user["vards"] ?? ""); ?>"
                                required
                            >
                        </div>

                        <div class="profile-form-group">
                            <label for="uzvards">Uzvārds *</label>
                            <input
                                class="profile-form-control"
                                type="text"
                                id="uzvards"
                                name="uzvards"
                                value="<?= htmlspecialchars($user["uzvards"] ?? ""); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="profile-form-group">
                        <label for="epasts">E-pasts *</label>
                        <input
                            class="profile-form-control"
                            type="email"
                            id="epasts"
                            name="epasts"
                            value="<?= htmlspecialchars($user["epasts"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="profile-section-title">
                        <i class="fas fa-key"></i>
                        Mainīt paroli
                    </div>

                    <div class="profile-note">
                        Ja paroli nevēlies mainīt, atstāj abus paroles laukus tukšus.
                    </div>

                    <div class="profile-form-grid">
                        <div class="profile-form-group">
                            <label for="parole">Jaunā parole</label>
                            <input
                                class="profile-form-control"
                                type="password"
                                id="parole"
                                name="parole"
                            >
                        </div>

                        <div class="profile-form-group">
                            <label for="parole2">Atkārtot jauno paroli</label>
                            <input
                                class="profile-form-control"
                                type="password"
                                id="parole2"
                                name="parole2"
                            >
                        </div>
                    </div>

                    <div class="profile-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i>
                            Saglabāt izmaiņas
                        </button>

                        <a href="<?= BASE_URL ?>dashboards/parent.php" class="btn btn-outline">
                            Atcelt
                        </a>
                    </div>

                </form>
            </section>

        </section>
    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
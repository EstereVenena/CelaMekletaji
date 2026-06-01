<?php
session_start();

$lapa  = "Lietotāja profils";
$title = "Lietotāja profils - Ceļa meklētāji";

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
$userId = (int)($_GET["id"] ?? 0);

$user = null;
$error = null;

/* ===============================
   PALĪGFUNKCIJAS
================================ */
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
   DATU PĀRBAUDE
================================ */
if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs.";
} elseif ($userId <= 0) {
    $error = "Nederīgs lietotāja ID.";
}

/* ===============================
   LIETOTĀJA IELĀDE
================================ */
if (!$error) {
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
LIMIT 1";

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
.director-view-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-view-hero {
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

.director-view-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-view-hero > * {
    position: relative;
    z-index: 1;
}

.director-view-avatar {
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

.director-view-hero h1 {
    margin: 0 0 .45rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-view-hero p {
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.7;
}

.director-view-actions {
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
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
    font-weight: 800;
}

.director-view-layout {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 1.1rem;
    align-items: start;
}

.director-view-card {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.director-view-card h2 {
    margin: 0 0 .35rem;
    color: #173f84;
    font-size: 1.35rem;
}

.director-muted {
    margin: 0;
    color: #667085;
    line-height: 1.6;
}

.director-info-grid {
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

.director-status-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: .45rem .7rem;
    border-radius: 999px;
    font-weight: 950;
    background: #ecfff4;
    color: #17633a;
}

.director-status-pill.inactive {
    background: #fff8e6;
    color: #7a5517;
}

.director-profile-card {
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

.director-profile-card strong {
    display: block;
    color: #fff;
    line-height: 1.25;
}

.director-profile-card span {
    display: block;
    margin-top: .15rem;
    color: rgba(255,255,255,.82);
    font-size: .92rem;
}

.director-side-actions {
    display: grid;
    gap: .75rem;
    margin-top: 1rem;
}

.director-side-actions .btn {
    justify-content: center;
}

@media (max-width: 900px) {
    .director-view-hero,
    .director-view-layout {
        grid-template-columns: 1fr;
    }

    .director-view-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 640px) {
    .director-view-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-view-hero,
    .director-view-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-view-actions .btn,
    .director-side-actions .btn {
        width: 100%;
    }
}
</style>

<main class="director-view-page">
    <div class="container">

        <section class="director-view-hero">
            <div class="director-view-avatar">
                <?= htmlspecialchars($initials); ?>
            </div>

            <div>
                <h1><?= $user ? htmlspecialchars($fullName) : "Lietotājs"; ?></h1>
                <p>
                    Lietotāja informācijas pārskats. Direktors redz tikai sava kluba lietotājus.
                </p>
            </div>

            <div class="director-view-actions">
                <?php if ($user): ?>
                    <a class="btn btn-primary btn-sm" href="user_edit.php?id=<?= (int)$user["lietotajs_id"]; ?>">
                        <i class="fas fa-pen"></i>
                        Labot
                    </a>
                <?php endif; ?>

                <a class="btn btn-outline btn-sm" href="users.php">
                    <i class="fas fa-arrow-left"></i>
                    Atpakaļ
                </a>
            </div>
        </section>

        <?php if ($error): ?>
            <div class="director-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($user): ?>
            <?php
                $status = trim($user["statuss"] ?? "—");
                $isActive = in_array(mb_strtolower($status), ["aktīvs", "aktivs", "active"], true);
            ?>

            <section class="director-view-layout">

                <article class="director-view-card">
                    <h2>Pamatinformācija</h2>
                    <p class="director-muted">
                        Galvenie lietotāja dati, kas saglabāti sistēmā.
                    </p>

                    <div class="director-info-grid">
                        <div class="director-info-item">
                            <span>Vārds</span>
                            <strong><?= htmlspecialchars($user["vards"] ?? "—"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Uzvārds</span>
                            <strong><?= htmlspecialchars($user["uzvards"] ?? "—"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>E-pasts</span>
                            <strong><?= htmlspecialchars($user["epasts"] ?? "—"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Lietotājvārds</span>
                            <strong><?= htmlspecialchars($user["lietotajvards"] ?? "—"); ?></strong>
                        </div>
                    </div>
                </article>

                <aside class="director-view-card">
                    <div class="director-profile-card">
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
                        Konta statuss, loma un reģistrācijas dati.
                    </p>

                    <div class="director-info-grid">
                        <div class="director-info-item">
                            <span>Loma</span>
                            <strong><?= htmlspecialchars($user["loma"] ?? "—"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Statuss</span>
                            <strong>
                                <span class="director-status-pill <?= $isActive ? '' : 'inactive'; ?>">
                                    <i class="fas <?= $isActive ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                                    <?= htmlspecialchars($status); ?>
                                </span>
                            </strong>
                        </div>

                        <div class="director-info-item">
                            <span>Klubs</span>
                            <strong><?= htmlspecialchars($user["club_name"] ?? "Nav norādīts"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Reģistrēts</span>
                            <strong><?= htmlspecialchars(formatDateLv($user["Reg_datums"] ?? null)); ?></strong>
                        </div>
                    </div>

                    <div class="director-side-actions">
                        <a class="btn btn-primary" href="user_edit.php?id=<?= (int)$user["lietotajs_id"]; ?>">
                            <i class="fas fa-user-pen"></i>
                            Labot lietotāju
                        </a>

                        <a class="btn btn-outline" href="users.php">
                            Atpakaļ uz sarakstu
                        </a>
                    </div>
                </aside>

            </section>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
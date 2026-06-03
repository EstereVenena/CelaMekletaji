<?php
session_start();

$lapa  = "Kluba informācija";
$title = "Kluba informācija - Ceļa meklētāji";

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
$error = null;

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00 00:00:00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

/* ===============================
   KLUBA DATI
================================ */
if ($directorClubId <= 0) {
    $error = "Direktoram nav piesaistīts klubs. Pārbaudi lietotāja club_id datubāzē.";
} else {
    $sql = "
        SELECT
            c.id,
            c.name,
            c.address,
            c.location,
            c.director_id,
            c.is_active,
            c.created_at,
            ch.name AS church_name,
            GROUP_CONCAT(p.label SEPARATOR ', ') AS programs
        FROM cm_clubs c
        LEFT JOIN cm_churches ch ON c.church_id = ch.id
        LEFT JOIN cm_club_programs cp ON c.id = cp.club_id
        LEFT JOIN cm_programs p ON cp.program_id = p.id
        WHERE c.id = ?
        GROUP BY
            c.id,
            c.name,
            c.address,
            c.location,
            c.director_id,
            c.is_active,
            c.created_at,
            ch.name
        LIMIT 1
    ";

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
        $error = "Neizdevās ielādēt kluba informāciju.";
    }
}

require __DIR__ . "/../includes/templates/header-director.php";
?>

<style>
.director-club-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.director-club-hero {
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

.director-club-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.director-club-hero > * {
    position: relative;
    z-index: 1;
}

.director-club-kicker {
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

.director-club-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.director-club-hero p {
    max-width: 740px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.director-club-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.director-club-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.director-club-hero-card strong {
    display: block;
    font-size: 2rem;
    color: #f4c430;
    line-height: 1.1;
}

.director-club-hero-card span {
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
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
    font-weight: 800;
}

.director-club-layout {
    display: grid;
    grid-template-columns: 1.1fr .9fr;
    gap: 1.1rem;
    align-items: start;
}

.director-club-card {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.director-club-card h2 {
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

.director-club-summary {
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

.director-club-summary-icon {
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

.director-club-summary strong {
    display: block;
    color: #fff;
    line-height: 1.25;
}

.director-club-summary span {
    display: block;
    margin-top: .15rem;
    color: rgba(255,255,255,.82);
    font-size: .92rem;
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
    .director-club-hero,
    .director-club-layout {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .director-club-page {
        padding: 1.5rem 0 2.5rem;
    }

    .director-club-hero,
    .director-club-card {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .director-club-actions .btn {
        width: 100%;
    }
}
</style>

<main class="director-club-page">
    <div class="container">

        <section class="director-club-hero">
            <div>
                <div class="director-club-kicker">
                    <i class="fas fa-people-roof"></i>
                    Mans klubs
                </div>

                <h1><?= $club ? htmlspecialchars($club["name"]) : "Kluba informācija"; ?></h1>

                <p>
                    Šeit redzama informācija par direktoram piesaistīto klubu,
                    tā atrašanās vietu, draudzi, programmām un statusu.
                </p>

                <div class="director-club-actions">
    <a class="btn btn-primary btn-sm" href="club_edit.php">
        <i class="fas fa-pen-to-square"></i>
        Labot klubu
    </a>

    <a class="btn btn-outline btn-sm" href="users.php">
        <i class="fas fa-users"></i>
        Kluba lietotāji
    </a>

    <a class="btn btn-outline btn-sm" href="../dashboards/director.php">
        <i class="fas fa-arrow-left"></i>
        Atpakaļ uz paneli
    </a>
</div>
            </div>

            <aside class="director-club-hero-card">
                <strong><?= $club ? htmlspecialchars($club["name"]) : "Nav kluba"; ?></strong>
                <span>
                    <?= $club ? htmlspecialchars($club["address"] ?? "Adrese nav norādīta") : "Direktoram nav piesaistīts klubs."; ?>
                </span>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="director-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($club): ?>
            <?php $isActive = ((int)($club["is_active"] ?? 0) === 1); ?>

            <section class="director-club-layout">

                <article class="director-club-card">
                    <h2>Kluba dati</h2>
                    <p class="director-muted">
                        Pamatinformācija par klubu un tā atrašanās vietu.
                    </p>

                    <div class="director-info-grid">
                        <div class="director-info-item">
                            <span>Nosaukums</span>
                            <strong><?= htmlspecialchars($club["name"] ?? "—"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Adrese</span>
                            <strong><?= htmlspecialchars($club["address"] ?? "Nav norādīta"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Atrašanās vieta</span>
                            <strong><?= htmlspecialchars($club["location"] ?? "Nav norādīta"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Draudze</span>
                            <strong><?= htmlspecialchars($club["church_name"] ?? "Nav norādīta"); ?></strong>
                        </div>
                    </div>
                </article>

                <aside class="director-club-card">
                    <div class="director-club-summary">
                        <div class="director-club-summary-icon">
                            <i class="fas fa-compass"></i>
                        </div>

                        <div>
                            <strong><?= htmlspecialchars($club["name"] ?? "Klubs"); ?></strong>
                            <span><?= htmlspecialchars($club["address"] ?? "Adrese nav norādīta"); ?></span>
                        </div>
                    </div>

                    <h2>Pārskats</h2>
                    <p class="director-muted">
                        Īss kluba statusa un programmu pārskats.
                    </p>

                    <div class="director-info-grid">
                        <div class="director-info-item">
                            <span>Statuss</span>
                            <strong>
                                <span class="director-status-pill <?= $isActive ? '' : 'inactive'; ?>">
                                    <i class="fas <?= $isActive ? 'fa-circle-check' : 'fa-circle-exclamation'; ?>"></i>
                                    <?= $isActive ? "Aktīvs" : "Neaktīvs"; ?>
                                </span>
                            </strong>
                        </div>

                        <div class="director-info-item">
                            <span>Programmas</span>
                            <strong><?= htmlspecialchars($club["programs"] ?? "Nav piesaistītas"); ?></strong>
                        </div>

                        <div class="director-info-item">
                            <span>Izveidots</span>
                            <strong><?= htmlspecialchars(formatDateLv($club["created_at"] ?? null)); ?></strong>
                        </div>
                    </div>

                    <div class="director-help-list">
                        <div class="director-help-item">
                            <div class="director-help-icon">
                                <i class="fas fa-circle-info"></i>
                            </div>

                            <div>
                                <strong>Kluba dati</strong>
                                <span>
                                    Ja vajag labot kluba nosaukumu, adresi vai programmas,
                                    to varēs pieslēgt vēlāk kā atsevišķu rediģēšanas formu.
                                </span>
                            </div>
                        </div>
                    </div>
                </aside>

            </section>
        <?php endif; ?>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
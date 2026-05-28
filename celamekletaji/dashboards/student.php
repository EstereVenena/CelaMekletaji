<?php
session_start();

$lapa  = "Ceļameklētāja panelis";
$title = "Ceļameklētāja panelis - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/app.php";
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

$studentId = (int) ($_SESSION["lietotajs_id"] ?? 0);
$student = null;
$news = [];
$error = null;

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function formatDateLv(?string $date): string
{
    if (empty($date)) {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

/* ===============================
   SKOLĒNA / CEĻAMEKLĒTĀJA DATI
================================ */
$sqlStudent = "
    SELECT 
        l.lietotajs_id,
        l.lietotajvards,
        l.vards,
        l.uzvards,
        l.loma,
        l.statuss,
        c.name AS club_name
    FROM cm_lietotaji l
    LEFT JOIN cm_clubs c ON l.club_id = c.id
    WHERE l.lietotajs_id = ?
    LIMIT 1
";

if ($stmt = $savienojums->prepare($sqlStudent)) {
    $stmt->bind_param("i", $studentId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $student = $result->fetch_assoc();
        } else {
            $error = "Lietotāja dati netika atrasti.";
        }
    } else {
        $error = "Neizdevās ielādēt lietotāja datus.";
    }

    $stmt->close();
} else {
    $error = "Neizdevās sagatavot lietotāja vaicājumu.";
}

/* ===============================
   JAUNUMI
================================ */
$sqlNews = "
    SELECT 
        id,
        title,
        description,
        category,
        publish_date
    FROM cm_news
    WHERE is_active = 1
    ORDER BY publish_date DESC
    LIMIT 3
";

if ($newsResult = $savienojums->query($sqlNews)) {
    while ($row = $newsResult->fetch_assoc()) {
        $news[] = $row;
    }
}

/* ===============================
   ATTĒLOŠANAS DATI
================================ */
$displayName = trim(($student["vards"] ?? "") . " " . ($student["uzvards"] ?? ""));
$displayName = $displayName !== ""
    ? $displayName
    : ($_SESSION["lietotajvards"] ?? "Ceļameklētāj");

$firstName = $student["vards"] ?? ($_SESSION["lietotajvards"] ?? "Ceļameklētāj");

$clubName = $student["club_name"] ?? "Nav piešķirts";
$userRole = $student["loma"] ?? ($_SESSION["loma"] ?? "Ceļameklētājs");
$userStatus = $student["statuss"] ?? "—";

$initial = mb_strtoupper(mb_substr($displayName, 0, 1));

$lessonsUrl      = BASE_URL . "student/lessons.php";
$applicationsUrl = BASE_URL . "student/applications.php";
$eventsUrl       = BASE_URL . "student/events.php";
$profileUrl      = BASE_URL . "student/profile.php";
$newsUrl         = BASE_URL . "student/news.php";

require __DIR__ . "/../includes/templates/header-student.php";
?>

<style>
.student-dashboard-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.student-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1.2fr .8fr;
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

.student-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.student-hero > * {
    position: relative;
    z-index: 1;
}

.student-hero-kicker {
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

.student-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.student-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

/* ===============================
   HERO BUTTONS - VIENĀDS STILS
================================ */
.student-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.student-hero-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .55rem;
    min-width: 175px;
    padding: .85rem 1.1rem;
    border-radius: 999px;
    text-decoration: none;
    font-weight: 950;
    color: #ffffff;
    background: rgba(255, 255, 255, 0.14);
    border: 1px solid rgba(255, 255, 255, 0.22);
    box-shadow: 0 12px 26px rgba(0, 0, 0, 0.12);
    transition: .2s ease;
}

.student-hero-btn i {
    color: #f4c430;
}

.student-hero-btn:hover {
    transform: translateY(-2px);
    background: rgba(255, 255, 255, 0.20);
    box-shadow: 0 18px 38px rgba(0, 0, 0, 0.18);
}

.student-hero-btn--gold {
    color: #173f84;
    background: linear-gradient(135deg, #f4c430, #e1aa16);
    border-color: rgba(244, 196, 48, 0.4);
    box-shadow: 0 12px 26px rgba(244, 196, 48, 0.26);
}

.student-hero-btn--gold i {
    color: #173f84;
}

.student-hero-btn--gold:hover {
    background: linear-gradient(135deg, #ffd84c, #e1aa16);
    box-shadow: 0 18px 38px rgba(244, 196, 48, 0.34);
}

.student-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.student-avatar-big {
    width: 82px;
    height: 82px;
    display: grid;
    place-items: center;
    margin-bottom: 1rem;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(244,196,48,.55);
    color: #f4c430;
    font-size: 2.1rem;
    font-weight: 1000;
}

.student-info-list {
    display: grid;
    gap: .7rem;
}

.student-info-row {
    padding: .85rem;
    border-radius: 16px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.16);
}

.student-info-row span {
    display: block;
    color: rgba(255,255,255,.72);
    font-size: .86rem;
    margin-bottom: .2rem;
}

.student-info-row strong {
    display: block;
    color: #fff;
    overflow-wrap: anywhere;
}

.student-alert {
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

.student-quick-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-bottom: 1.4rem;
}

.student-action-card {
    display: flex;
    flex-direction: column;
    gap: .85rem;
    padding: 1.25rem;
    border-radius: 22px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
    transition: .2s ease;
}

.student-action-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 20px 46px rgba(16, 24, 40, 0.10);
    border-color: #d7e5ff;
}

.student-action-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    border-radius: 15px;
    background: #eef3ff;
    color: #173f84;
    font-size: 1.25rem;
}

.student-action-card h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.18rem;
}

.student-action-card p {
    margin: 0;
    color: #667085;
    line-height: 1.55;
    flex: 1;
}

.student-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.student-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.student-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.student-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
}

.student-news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 340px));
    gap: .9rem;
    justify-content: start;
}

.student-news-card {
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 20px;
    background: #f8fbff;
    transition: .2s ease;
}

.student-news-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.student-news-card h3 {
    margin: 0 0 .35rem;
    color: #101828;
    font-size: 1.05rem;
    line-height: 1.25;
}

.student-news-meta {
    display: flex;
    gap: .4rem;
    flex-wrap: wrap;
    margin-bottom: .55rem;
    color: #667085;
    font-size: .86rem;
    font-weight: 800;
}

.student-news-card p {
    margin: 0 0 .7rem;
    color: #667085;
    line-height: 1.5;
}

.student-empty {
    padding: 1.2rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
}

@media (max-width: 980px) {
    .student-hero {
        grid-template-columns: 1fr;
    }

    .student-quick-grid,
    .student-news-grid {
        grid-template-columns: 1fr;
    }

    .student-panel-head {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .student-dashboard-page {
        padding: 1.5rem 0 2.5rem;
    }

    .student-hero,
    .student-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .student-hero-btn,
    .student-action-card .btn,
    .student-panel-head .btn {
        width: 100%;
    }
}
</style>

<main class="student-dashboard-page">
    <div class="container">

        <section class="student-hero">
            <div>
                <div class="student-hero-kicker">
                    <i class="fas fa-compass"></i>
                    Ceļameklētāja piekļuve
                </div>

                <h1>Sveiki, <?= htmlspecialchars($firstName); ?>!</h1>

                <p>
                    Šeit vari apskatīt savas nodarbības, pieteikumus, pasākumus un jaunāko informāciju.
                    Viss vienā vietā — bez klikšķu ekspedīcijas pa digitālajiem džungļiem.
                </p>

                <div class="student-hero-actions">
                    <a class="student-hero-btn student-hero-btn--gold" href="<?= $lessonsUrl; ?>">
                        <i class="fas fa-book-open"></i>
                        <span>Skatīt nodarbības</span>
                    </a>

                    <a class="student-hero-btn" href="<?= $applicationsUrl; ?>">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Mani pieteikumi</span>
                    </a>

                    <a class="student-hero-btn" href="<?= $profileUrl; ?>">
                        <i class="fas fa-user-gear"></i>
                        <span>Mans profils</span>
                    </a>
                </div>
            </div>

            <aside class="student-hero-card">
                <div class="student-avatar-big">
                    <?= htmlspecialchars($initial); ?>
                </div>

                <div class="student-info-list">
                    <div class="student-info-row">
                        <span>Mans klubs</span>
                        <strong><?= htmlspecialchars($clubName); ?></strong>
                    </div>

                    <div class="student-info-row">
                        <span>Loma</span>
                        <strong><?= htmlspecialchars($userRole); ?></strong>
                    </div>

                    <div class="student-info-row">
                        <span>Statuss</span>
                        <strong><?= htmlspecialchars($userStatus); ?></strong>
                    </div>
                </div>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="student-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="student-quick-grid">
            <article class="student-action-card">
                <div class="student-action-icon">
                    <i class="fas fa-book-open"></i>
                </div>

                <h2>Pieejamās nodarbības</h2>

                <p>
                    Apskati nodarbības un izvēlies tās, kurās vēlies piedalīties.
                </p>

                <a class="btn btn-primary btn-sm" href="<?= $lessonsUrl; ?>">
                    Atvērt nodarbības
                </a>
            </article>

            <article class="student-action-card">
                <div class="student-action-icon">
                    <i class="fas fa-clipboard-check"></i>
                </div>

                <h2>Mani pieteikumi</h2>

                <p>
                    Pārskati nodarbības vai pasākumus, kuriem jau esi pieteicies.
                </p>

                <a class="btn btn-outline btn-sm" href="<?= $applicationsUrl; ?>">
                    Skatīt pieteikumus
                </a>
            </article>

            <article class="student-action-card">
                <div class="student-action-icon">
                    <i class="fas fa-calendar-days"></i>
                </div>

                <h2>Pasākumi</h2>

                <p>
                    Apskati gaidāmos pasākumus un informāciju par aktivitātēm.
                </p>

                <a class="btn btn-outline btn-sm" href="<?= $eventsUrl; ?>">
                    Skatīt pasākumus
                </a>
            </article>
        </section>

        <section class="student-panel">
            <div class="student-panel-head">
                <div>
                    <h2>Jaunumi</h2>
                    <p>Aktuālā informācija no “Ceļa meklētāji”.</p>
                </div>

                <a class="btn btn-outline btn-sm" href="<?= $newsUrl; ?>">
                    Visi jaunumi
                </a>
            </div>

            <?php if (empty($news)): ?>
                <div class="student-empty">
                    Pašlaik nav pieejamu jaunumu.
                </div>
            <?php else: ?>
                <div class="student-news-grid">
                    <?php foreach ($news as $item): ?>
                        <?php
                            $desc = trim($item["description"] ?? "");
                        ?>

                        <article class="student-news-card">
                            <h3>
                                <?= htmlspecialchars($item["title"] ?? "Bez nosaukuma"); ?>
                            </h3>

                            <div class="student-news-meta">
                                <span><?= htmlspecialchars($item["category"] ?? "Jaunumi"); ?></span>
                                <span>•</span>
                                <span><?= htmlspecialchars(formatDateLv($item["publish_date"] ?? null)); ?></span>
                            </div>

                            <p>
                                <?= htmlspecialchars(mb_strimwidth($desc, 0, 130, "...")); ?>
                            </p>

                            <a class="link" href="<?= $newsUrl; ?>?id=<?= (int) ($item["id"] ?? 0); ?>">
                                Lasīt vairāk
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
<?php
session_start();

$lapa  = "Jaunumi";
$title = "Jaunumi - Ceļa meklētāji";

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

$news = [];
$error = null;

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

/* ===============================
   JAUNUMU SARAKSTS
================================ */
$sql = "
    SELECT
        id,
        title,
        description,
        category,
        publish_date
    FROM cm_news
    WHERE is_active = 1
    ORDER BY publish_date DESC
";

if ($result = $savienojums->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
} else {
    $error = "Neizdevās ielādēt jaunumus.";
}

$newsCount = count($news);

require __DIR__ . "/../includes/templates/header-student.php";
?>

<style>
.student-news-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.news-hero {
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

.news-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.news-hero > * {
    position: relative;
    z-index: 1;
}

.news-kicker {
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

.news-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.news-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.news-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.news-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.news-hero-card strong {
    display: block;
    font-size: 2.1rem;
    line-height: 1;
    color: #f4c430;
}

.news-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.news-alert {
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

.news-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.news-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.news-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.news-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
}

.news-count {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .75rem 1rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-weight: 950;
    white-space: nowrap;
}

.student-news-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(260px, 340px));
    gap: 1rem;
    justify-content: center;
}

.student-news-card {
    display: flex;
    flex-direction: column;
    min-height: 230px;
    padding: 1.1rem;
    border: 1px solid #edf2fb;
    border-radius: 22px;
    background: #f8fbff;
    box-shadow: 0 10px 24px rgba(16, 24, 40, 0.04);
    transition: .2s ease;
}

.student-news-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 36px rgba(23, 63, 132, 0.10);
    border-color: #d7e5ff;
}

.student-news-meta {
    display: flex;
    justify-content: space-between;
    gap: .65rem;
    align-items: center;
    flex-wrap: wrap;
    margin-bottom: .75rem;
}

.student-news-tag {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .34rem .65rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-size: .82rem;
    font-weight: 900;
}

.student-news-date {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    color: #667085;
    font-size: .84rem;
    font-weight: 800;
}

.student-news-card h3 {
    margin: 0 0 .6rem;
    color: #101828;
    font-size: 1.08rem;
    line-height: 1.25;
}

.student-news-card p {
    margin: 0;
    color: #667085;
    line-height: 1.55;
}

.student-news-footer {
    margin-top: auto;
    padding-top: .9rem;
    color: #173f84;
    font-weight: 900;
    font-size: .9rem;
}

.news-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.news-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

@media (max-width: 900px) {
    .news-hero {
        grid-template-columns: 1fr;
    }

    .news-panel-head {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .student-news-page {
        padding: 1.5rem 0 2.5rem;
    }

    .news-hero,
    .news-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .news-hero-actions .btn {
        width: 100%;
    }

    .student-news-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<main class="student-news-page">
    <div class="container">

        <section class="news-hero">
            <div>
                <div class="news-kicker">
                    <i class="fas fa-newspaper"></i>
                    Aktuālā informācija
                </div>

                <h1>Jaunumi</h1>

                <p>
                    Šeit vari apskatīt aktuālos paziņojumus, jaunumus un svarīgāko informāciju
                    no “Ceļa meklētāji”.
                </p>

                <div class="news-hero-actions">
                    <a class="btn btn-primary btn-sm" href="../dashboards/student.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>

                    <a class="btn btn-outline btn-sm" href="calendar.php">
                        <i class="fas fa-calendar-week"></i>
                        Mans kalendārs
                    </a>
                </div>
            </div>

            <aside class="news-hero-card">
                <strong><?= (int)$newsCount; ?></strong>
                <span>
                    Aktīvi jaunumi un paziņojumi.
                </span>
            </aside>
        </section>

        <?php if (!empty($error)): ?>
            <div class="news-alert">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="news-panel">
            <div class="news-panel-head">
                <div>
                    <h2>Jaunumu saraksts</h2>
                    <p>Aktuālā informācija sakārtota pēc publicēšanas datuma.</p>
                </div>

                <div class="news-count">
                    <i class="fas fa-list-check"></i>
                    Kopā: <?= (int)$newsCount; ?>
                </div>
            </div>

            <?php if (!empty($news)): ?>
                <div class="student-news-grid">

                    <?php foreach ($news as $item): ?>
                        <?php
                            $description = trim($item["description"] ?? "");

                            if ($description === "") {
                                $description = "Apraksts nav pievienots.";
                            }

                            $shortDescription = mb_strimwidth($description, 0, 230, "...");
                        ?>

                        <article class="student-news-card">
                            <div class="student-news-meta">
                                <span class="student-news-tag">
                                    <i class="fas fa-tag"></i>
                                    <?= htmlspecialchars($item["category"] ?? "Jaunumi"); ?>
                                </span>

                                <span class="student-news-date">
                                    <i class="fas fa-calendar-day"></i>
                                    <?= htmlspecialchars(formatDateLv($item["publish_date"] ?? null)); ?>
                                </span>
                            </div>

                            <h3>
                                <?= htmlspecialchars($item["title"] ?? "Bez nosaukuma"); ?>
                            </h3>

                            <p>
                                <?= nl2br(htmlspecialchars($shortDescription)); ?>
                            </p>

                            <div class="student-news-footer">
                                <i class="fas fa-circle-info"></i>
                                Paziņojums
                            </div>
                        </article>
                    <?php endforeach; ?>

                </div>
            <?php else: ?>
                <div class="news-empty">
                    <h3>Pašlaik nav pieejamu jaunumu</h3>
                    <p>Jaunumi un paziņojumi šeit parādīsies, kad tie būs publicēti.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
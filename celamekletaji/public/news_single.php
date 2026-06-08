<?php
require_once __DIR__ . "/../includes/config/database.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: news.php");
    exit();
}

$newsItem = null;

$sql = "
    SELECT id, title, description, category, publish_date
    FROM cm_news
    WHERE id = ?
      AND is_active = 1
      AND (start_date IS NULL OR start_date <= CURDATE())
      AND (end_date IS NULL OR end_date >= CURDATE())
    LIMIT 1
";

$stmt = $savienojums->prepare($sql);

if ($stmt) {
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $newsItem = $result->fetch_assoc();
    }

    $stmt->close();
}

if (!$newsItem) {
    $lapa  = "Jaunums nav atrasts";
    $title = "Jaunums nav atrasts | Ceļa meklētāji";

    require __DIR__ . "/../includes/templates/header.php";
    ?>

    <style>
    .news-single-page {
        min-height: calc(100vh - 160px);
        padding: 4rem 0;
        background:
            radial-gradient(circle at top right, rgba(244, 197, 66, 0.13), transparent 30%),
            linear-gradient(180deg, #fbfaf5 0%, #ffffff 100%);
    }

    .news-not-found {
        max-width: 760px;
        margin: 0 auto;
        padding: 2.2rem;
        border-radius: 2rem;
        background: #ffffff;
        border: 1px solid rgba(23,54,38,0.08);
        box-shadow: 0 18px 50px rgba(0,0,0,0.08);
        text-align: center;
    }

    .news-not-found-icon {
        width: 70px;
        height: 70px;
        display: grid;
        place-items: center;
        margin: 0 auto 1rem;
        border-radius: 1.3rem;
        background: #f8f4e7;
        color: #d6a823;
        font-size: 1.8rem;
    }

    .news-not-found h1 {
        margin: 0 0 .7rem;
        color: #173626;
        letter-spacing: -0.04em;
    }

    .news-not-found p {
        margin: 0;
        color: #667085;
        line-height: 1.7;
    }

    .news-not-found-actions {
        margin-top: 1.4rem;
    }
    </style>

    <main class="news-single-page">
        <div class="container">
            <article class="news-not-found">
                <div class="news-not-found-icon">
                    <i class="fa-solid fa-inbox"></i>
                </div>

                <h1>Jaunums nav atrasts</h1>

                <p>
                    Iespējams, šis jaunums vairs nav pieejams vai arī saite nav pareiza.
                </p>

                <div class="news-not-found-actions">
                    <a href="news.php" class="btn btn-primary">
                        Atpakaļ uz jaunumiem
                    </a>
                </div>
            </article>
        </div>
    </main>

    <?php
    require __DIR__ . "/../includes/templates/footer.php";
    exit();
}

$category = trim($newsItem['category'] ?? '');
$category = $category !== '' ? $category : 'Jaunums';

$description = trim($newsItem['description'] ?? '');
$description = $description !== '' ? $description : 'Apraksts nav pievienots.';

$publishDate = !empty($newsItem['publish_date'])
    ? date('d.m.Y', strtotime($newsItem['publish_date']))
    : '—';

$lapa  = $newsItem['title'];
$title = htmlspecialchars($newsItem['title']) . " | Ceļa meklētāji";

require __DIR__ . "/../includes/templates/header.php";
?>

<style>
/* ===============================
   NEWS SINGLE PAGE
================================ */

.news-single-hero {
    position: relative;
    overflow: hidden;
    padding: 4.7rem 0 3.8rem;
    background:
        radial-gradient(circle at top left, rgba(244, 197, 66, 0.26), transparent 35%),
        radial-gradient(circle at bottom right, rgba(45, 106, 79, 0.45), transparent 40%),
        linear-gradient(135deg, #10241b 0%, #173626 58%, #224e38 100%);
    color: #ffffff;
}

.news-single-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: 0.45;
}

.news-single-hero-inner {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 3rem;
    align-items: center;
}

.news-single-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.48rem 0.9rem;
    margin-bottom: 1.1rem;
    border-radius: 999px;
    background: rgba(255,255,255,0.13);
    color: #f4c542;
    font-weight: 850;
    backdrop-filter: blur(10px);
}

.news-single-hero h1 {
    margin: 0;
    color: #fff;
    font-size: clamp(2.15rem, 5vw, 4.4rem);
    line-height: 1;
    letter-spacing: -0.055em;
}

.news-single-hero .lead {
    max-width: 720px;
    margin: 1.3rem 0 0;
    color: rgba(255,255,255,0.87);
    font-size: 1.08rem;
    line-height: 1.8;
}

.news-single-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 2rem;
}

.news-single-hero-card {
    padding: 2rem;
    border-radius: 2rem;
    background: rgba(255,255,255,0.9);
    color: #173626;
    border: 1px solid rgba(255,255,255,0.5);
    box-shadow: 0 28px 70px rgba(0,0,0,0.22);
    backdrop-filter: blur(14px);
}

.news-single-hero-card-icon {
    width: 62px;
    height: 62px;
    display: grid;
    place-items: center;
    margin-bottom: 1rem;
    border-radius: 1.2rem;
    background: #173626;
    color: #f4c542;
    font-size: 1.55rem;
}

.news-single-hero-card h3 {
    margin: 0 0 0.8rem;
    font-size: 1.35rem;
    letter-spacing: -0.03em;
}

.news-single-hero-card p {
    margin: 0;
    color: #526358;
    line-height: 1.75;
}

.news-single-page {
    padding: 3.2rem 0 4rem;
    background:
        radial-gradient(circle at top right, rgba(244, 197, 66, 0.13), transparent 30%),
        linear-gradient(180deg, #fbfaf5 0%, #ffffff 100%);
}

.news-single-layout {
    display: grid;
    grid-template-columns: minmax(0, 1fr) 320px;
    gap: 1.5rem;
    align-items: start;
}

.news-single-article {
    position: relative;
    overflow: hidden;
    padding: 2rem;
    border-radius: 2rem;
    background:
        linear-gradient(135deg, rgba(255,255,255,0.98), rgba(255,250,235,0.96));
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 18px 50px rgba(0,0,0,0.08);
}

.news-single-article::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(90deg, #173626, #f4c542);
}

.news-single-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .65rem;
    margin-bottom: 1.3rem;
}

.news-single-pill {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    padding: 0.45rem 0.8rem;
    border-radius: 999px;
    background: rgba(244,197,66,0.18);
    border: 1px solid rgba(244,197,66,0.38);
    color: #8a650b;
    font-size: 0.86rem;
    font-weight: 900;
}

.news-single-pill.date {
    background: #f8f6ef;
    color: #526358;
    border-color: rgba(23,54,38,0.08);
}

.news-single-title {
    margin: 0 0 1rem;
    color: #173626;
    font-size: clamp(1.7rem, 4vw, 2.6rem);
    line-height: 1.08;
    letter-spacing: -0.045em;
}

.news-single-divider {
    height: 1px;
    margin: 1.3rem 0;
    background: linear-gradient(90deg, rgba(23,54,38,0.14), transparent);
}

.news-single-content {
    color: #36463c;
    font-size: 1.03rem;
    line-height: 1.9;
}

.news-single-content p {
    margin: 0 0 1rem;
}

.news-single-bottom {
    display: flex;
    flex-wrap: wrap;
    gap: .75rem;
    margin-top: 2rem;
}

.news-single-side {
    position: sticky;
    top: 96px;
    padding: 1.4rem;
    border-radius: 1.6rem;
    background: #ffffff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 16px 45px rgba(0,0,0,0.07);
}

.news-single-side-icon {
    width: 52px;
    height: 52px;
    display: grid;
    place-items: center;
    margin-bottom: 1rem;
    border-radius: 1rem;
    background: #f8f4e7;
    color: #d6a823;
    font-size: 1.25rem;
}

.news-single-side h3 {
    margin: 0 0 .65rem;
    color: #173626;
    letter-spacing: -0.03em;
}

.news-single-side p {
    margin: 0;
    color: #667085;
    line-height: 1.7;
}

.news-single-side .btn {
    width: 100%;
    margin-top: 1rem;
    justify-content: center;
}

@media (max-width: 980px) {
    .news-single-hero-inner,
    .news-single-layout {
        grid-template-columns: 1fr;
    }

    .news-single-side {
        position: static;
    }
}

@media (max-width: 640px) {
    .news-single-hero {
        padding: 3.6rem 0 3rem;
    }

    .news-single-actions {
        flex-direction: column;
    }

    .news-single-actions .btn,
    .news-single-bottom .btn {
        width: 100%;
        justify-content: center;
    }

    .news-single-article,
    .news-single-hero-card,
    .news-single-side {
        border-radius: 1.4rem;
    }

    .news-single-article {
        padding: 1.35rem;
    }
}
</style>

<section class="news-single-hero">
    <div class="container">
        <div class="news-single-hero-inner">
            <div>
                <div class="news-single-kicker">
                    <i class="fa-solid fa-newspaper"></i>
                    <?= htmlspecialchars($category); ?>
                </div>

                <h1><?= htmlspecialchars($newsItem['title']); ?></h1>

                <p class="lead">
                    Publicēts <?= htmlspecialchars($publishDate); ?>.
                    Šeit vari izlasīt pilnu Jaunumu aprakstu un svarīgāko informāciju.
                </p>

                <div class="news-single-actions">
                    <a class="btn btn-primary" href="#saturs">
                        Lasīt saturu
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <a class="btn btn-outline" href="news.php">
                        Atpakaļ uz jaunumiem
                    </a>
                </div>
            </div>

            <aside class="news-single-hero-card">
                <div class="news-single-hero-card-icon">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>

                <h3>Jaunumu informācija</h3>

                <p>
                    Šis ieraksts ir daļa no Ceļa meklētāju jaunumiem, paziņojumiem un notikumu informācijas.
                </p>
            </aside>
        </div>
    </div>
</section>

<main id="saturs" class="news-single-page">
    <div class="container">

        <div class="news-single-layout">

            <article class="news-single-article">

                <div class="news-single-meta">
                    <span class="news-single-pill">
                        <i class="fa-solid fa-tag"></i>
                        <?= htmlspecialchars($category); ?>
                    </span>

                    <span class="news-single-pill date">
                        <i class="fa-solid fa-calendar-day"></i>
                        <?= htmlspecialchars($publishDate); ?>
                    </span>
                </div>

                <h2 class="news-single-title">
                    <?= htmlspecialchars($newsItem['title']); ?>
                </h2>

                <div class="news-single-divider"></div>

                <div class="news-single-content">
                    <?php
                        $paragraphs = preg_split("/\R{2,}/", $description);

                        foreach ($paragraphs as $paragraph):
                            $paragraph = trim($paragraph);
                            if ($paragraph === '') {
                                continue;
                            }
                    ?>
                        <p><?= nl2br(htmlspecialchars($paragraph)); ?></p>
                    <?php endforeach; ?>
                </div>

                <div class="news-single-bottom">
                    <a href="news.php" class="btn btn-outline">
                        <i class="fa-solid fa-arrow-left"></i>
                        Atpakaļ uz visām jaunumiem
                    </a>
                </div>

            </article>

            <aside class="news-single-side">
                <div class="news-single-side-icon">
                    <i class="fa-solid fa-compass"></i>
                </div>

                <h3>Nepalaid garām</h3>

                <p>
                    Apskati arī citus jaunumus un pasākumu informāciju, lai sekotu līdzi tam,
                    kas notiek Ceļa meklētāju kopienā.
                </p>

                <a href="news.php" class="btn btn-primary btn-sm">
                    Visi jaunumi
                </a>
            </aside>

        </div>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
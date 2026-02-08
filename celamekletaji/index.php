<?php
$lapa  = "Ceļa meklētāji";
$title = "Ceļa meklētāji";

require "assets/header.php";
require_once "assets/database.php";

/* ===============================
   AKTUALITĀTES
================================ */
$news = [];
$newsSql = "
    SELECT id, title, description, category, publish_date
    FROM cm_news
    WHERE is_active = 1
    ORDER BY publish_date DESC
    LIMIT 5
";
$result = $savienojums->query($newsSql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
}

/* ===============================
   KLUBI
================================ */
$clubs = [];
$clubsSql = "
    SELECT id, name, address, programm
FROM cm_clubs
WHERE is_active = 1
ORDER BY address
LIMIT 4
";
$result = $savienojums->query($clubsSql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clubs[] = $row;
    }
}
?>

<!-- HERO -->
<section class="hero">
    <div class="container">
        <div class="hero-inner">
            <div class="hero-content">
                <h1>Ceļa meklētāji</h1>
                <p class="lead">
                    Bērnu un jauniešu programmas visā Latvijā — piedzīvojumi, prasmes, draudzība un vērtības.
                </p>
                <div class="hero-actions">
                    <a class="btn btn-primary" href="about.php">Uzzināt par programmām</a>
                    <a class="btn btn-outline" href="clubs.php">Atrast klubu</a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- AKTUALITĀTES -->
<section id="jaunumi" class="section section-alt">
    <div class="container">
        <header class="section-title section-title-row">
            <div>
                <h2>Aktualitātes</h2>
                <p class="muted">Jaunākie notikumi, nometnes un projekti.</p>
            </div>
            <a class="btn btn-outline btn-sm" href="news.php">
                Skatīt visas <i class="fa-solid fa-angles-right"></i>
            </a>
        </header>

        <div class="carousel one" id="newsCarousel">
            <div class="carousel-viewport">
                <div class="carousel-track" id="carouselTrack">

                    <?php foreach ($news as $item): ?>
                        <article class="carousel-slide">
                            <div class="news-card">
                                <div class="news-meta">
                                    <span class="news-tag">
                                        <?= htmlspecialchars($item['category']) ?>
                                    </span>
                                    <span class="news-date">
                                        <?= htmlspecialchars($item['publish_date']) ?>
                                    </span>
                                </div>
                                <h3><?= htmlspecialchars($item['title']) ?></h3>
                                <p class="muted">
                                    <?= htmlspecialchars($item['description']) ?>
                                </p>
                                <div class="news-actions">
                                    <a href="news.php?id=<?= $item['id'] ?>"
                                       class="btn btn-primary btn-sm">
                                        Lasīt
                                    </a>
                                </div>
                            </div>
                        </article>
                    <?php endforeach; ?>

                </div>
            </div>

            <button class="carousel-btn prev" type="button">
                <i class="fas fa-chevron-left"></i>
            </button>
            <button class="carousel-btn next" type="button">
                <i class="fas fa-chevron-right"></i>
            </button>

            <div class="carousel-dots" id="carouselDots"></div>
        </div>
    </div>
</section>

<!-- PAR BIEDRĪBU -->
<section class="section">
    <div class="container">
        <header class="section-title">
            <h2>Par biedrību</h2>
            <p class="muted">Īsumā — kas mēs esam un ko darām.</p>
        </header>

        <div class="card card-wide">
            <p>
                “Ceļa meklētāji” ir kristīga biedrība, kas nodarbojas ar bērnu un
                jauniešu izglītošanu, organizējot nodarbības, nometnes un pasākumus
                visā Latvijā.
            </p>

            <div class="divider"></div>

            <div class="info-grid">
                <div class="info">
                    <h4>Programmas</h4>
                    <p class="muted">Strukturētas aktivitātes dažādām vecuma grupām.</p>
                </div>
                <div class="info">
                    <h4>Pasākumi</h4>
                    <p class="muted">Nometnes, pārgājieni un radošas nodarbības.</p>
                </div>
                <div class="info">
                    <h4>Kopiena</h4>
                    <p class="muted">Draudzība, atbildība un vērtības.</p>
                </div>
            </div>

            <div style="margin-top:1.25rem;text-align:center;">
                <a class="btn btn-outline" href="about.php">Lasīt vairāk</a>
            </div>
        </div>
    </div>
</section>

<!-- KLUBI -->
<section class="section section-alt">
    <div class="container">
        <header class="section-title section-title-row">
            <div>
                <h2>Klubi Latvijā</h2>
                <p class="muted">Atrodi tuvāko klubu un pievienojies.</p>
            </div>
            <a class="btn btn-outline btn-sm" href="clubs.php">
                Skatīt visus <i class="fa-solid fa-angles-right"></i>
            </a>
        </header>

        <div class="cards club-cards">
            <?php foreach ($clubs as $club): ?>
                <article class="card club-card">

    <div class="club-top">
        <h3><?= htmlspecialchars($club['name']) ?></h3>

        <span class="badge badge-gold">
            <?= htmlspecialchars($club['programm']) ?>
        </span>

        <a class="link" href="clubs.php?id=<?= $club['id'] ?>">
            Apskatīt →
        </a>
    </div>

    <p class="muted club-address">
        <i class="fas fa-location-dot"></i>
        <?= htmlspecialchars($club['address']) ?>
    </p>

</article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<?php require "assets/footer.php"; ?>

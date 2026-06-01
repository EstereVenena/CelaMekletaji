<?php
$lapa  = "Ceļa meklētāji";
$title = "Ceļa meklētāji";

require __DIR__ . "/includes/templates/header.php";
require_once __DIR__ . "/includes/config/database.php";

/* ===============================
   AKTUALITĀTES
================================ */
$news = [];
$newsSql = "
    SELECT id, title, description, category, publish_date
    FROM cm_news
    WHERE is_active = 1
    ORDER BY publish_date DESC
    LIMIT 8
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
    SELECT 
        c.id,
        c.name,
        c.address,
        GROUP_CONCAT(p.label SEPARATOR ', ') AS programs
    FROM cm_clubs c
    LEFT JOIN cm_club_programs cp ON c.id = cp.club_id
    LEFT JOIN cm_programs p ON cp.program_id = p.id
    WHERE c.is_active = 1
    GROUP BY c.id, c.name, c.address
    ORDER BY c.address
    LIMIT 4
";
$result = $savienojums->query($clubsSql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clubs[] = $row;
    }
}
?>

<style>
/* ===============================
   HOMEPAGE UZLABOJUMI
================================ */

.home-hero {
    position: relative;
    overflow: hidden;
    padding: 6rem 0 5rem;
    background:
        radial-gradient(circle at top left, rgba(255, 198, 93, 0.28), transparent 35%),
        radial-gradient(circle at bottom right, rgba(48, 92, 68, 0.32), transparent 40%),
        linear-gradient(135deg, #10241b 0%, #173626 48%, #f4efe3 48%, #f8f6ef 100%);
}

.home-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.04) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.04) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: 0.45;
    pointer-events: none;
}

.home-hero-inner {
    position: relative;
    display: grid;
    grid-template-columns: 1.2fr 0.8fr;
    gap: 3rem;
    align-items: center;
}

.home-hero-content {
    color: #fff;
}

.hero-kicker {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.45rem 0.85rem;
    margin-bottom: 1.2rem;
    border-radius: 999px;
    background: rgba(255, 255, 255, 0.13);
    backdrop-filter: blur(8px);
    color: #f7d985;
    font-weight: 700;
    font-size: 0.9rem;
}

.home-hero h1 {
    max-width: 760px;
    margin: 0;
    font-size: clamp(2.6rem, 6vw, 5.3rem);
    line-height: 0.95;
    letter-spacing: -0.05em;
}

.home-hero .lead {
    max-width: 620px;
    margin: 1.5rem 0 0;
    font-size: 1.22rem;
    line-height: 1.8;
    color: rgba(255, 255, 255, 0.88);
}

.hero-actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    margin-top: 2rem;
}

/* ===============================
   HERO PHOTO CARD
================================ */

.hero-photo-card {
    position: relative;
    overflow: hidden;
    min-height: 430px;
    border-radius: 2rem;
    background: #173626;
    box-shadow: 0 28px 70px rgba(0,0,0,0.24);
    border: 1px solid rgba(255,255,255,0.45);
}

.hero-photo-card img {
    width: 100%;
    height: 100%;
    min-height: 430px;
    display: block;
    object-fit: cover;
    object-position: center;
    transform: scale(1.03);
}

.hero-photo-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(180deg, rgba(16,36,27,0.06), rgba(16,36,27,0.55)),
        radial-gradient(circle at top left, rgba(244,197,66,0.22), transparent 35%);
    pointer-events: none;
}

.hero-photo-caption {
    position: absolute;
    left: 1.2rem;
    right: 1.2rem;
    bottom: 1.2rem;
    z-index: 2;
    padding: 1rem 1.1rem;
    border-radius: 1.2rem;
    background: rgba(255,255,255,0.88);
    backdrop-filter: blur(12px);
    box-shadow: 0 14px 34px rgba(0,0,0,0.18);
}

.hero-photo-caption strong {
    display: block;
    color: #173626;
    font-size: 1.1rem;
    margin-bottom: .25rem;
}

.hero-photo-caption span {
    display: block;
    color: #55645a;
    line-height: 1.45;
    font-size: .92rem;
}

/* Vecā hero card klase paliek, ja citur vēl vajag */
.hero-card {
    position: relative;
    padding: 2rem;
    border-radius: 2rem;
    background: rgba(255,255,255,0.88);
    box-shadow: 0 28px 70px rgba(0,0,0,0.22);
    backdrop-filter: blur(14px);
    border: 1px solid rgba(255,255,255,0.55);
}

.hero-card::before {
    content: "";
    position: absolute;
    top: -18px;
    right: -18px;
    width: 88px;
    height: 88px;
    border-radius: 50%;
    background: #f4c542;
    opacity: 0.85;
    z-index: -1;
}

.hero-card-icon {
    width: 62px;
    height: 62px;
    display: grid;
    place-items: center;
    border-radius: 1.2rem;
    background: #173626;
    color: #f4c542;
    font-size: 1.55rem;
    margin-bottom: 1rem;
}

.hero-card h3 {
    margin: 0 0 0.75rem;
    font-size: 1.55rem;
    color: #173626;
}

.hero-card p {
    margin: 0;
    color: #55645a;
    line-height: 1.7;
}

.hero-card-image {
    width: 100%;
    height: 220px;
    overflow: hidden;
    margin-bottom: 1.2rem;
    border-radius: 1.4rem;
    background: #173626;
    box-shadow: 0 14px 34px rgba(0,0,0,0.14);
}

.hero-card-image img {
    width: 100%;
    height: 100%;
    display: block;
    object-fit: cover;
    object-position: center;
}

@media (max-width: 640px) {
    .hero-card-image {
        height: 190px;
        border-radius: 1.1rem;
    }
}

.hero-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1.5rem;
}

.stat-pill {
    padding: 1rem;
    border-radius: 1.2rem;
    background: #fff;
    text-align: center;
    box-shadow: 0 12px 30px rgba(0,0,0,0.08);
}

.stat-pill strong {
    display: block;
    color: #173626;
    font-size: 1.4rem;
}

.stat-pill span {
    font-size: 0.85rem;
    color: #6d7b70;
}

/* Section polish */
.home-section {
    padding: 4.5rem 0;
}

.section-title-row {
    align-items: end;
}

.section-title h2 {
    font-size: clamp(2rem, 4vw, 3rem);
    letter-spacing: -0.035em;
}

.section-title .muted {
    max-width: 650px;
}

/* News carousel */
.carousel.one {
    position: relative;
    margin-top: 2rem;
}

.carousel-viewport {
    overflow: hidden;
    border-radius: 2rem;
}

.carousel-track {
    display: flex;
}

.carousel-slide {
    min-width: 100%;
    padding: 0.3rem;
}

.news-card {
    min-height: 320px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    padding: 2.2rem;
    border-radius: 2rem;
    background:
        linear-gradient(135deg, rgba(255,255,255,0.98), rgba(255,250,235,0.96));
    border: 1px solid rgba(20, 50, 34, 0.08);
    box-shadow: 0 18px 55px rgba(0,0,0,0.08);
}

.news-meta {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    margin-bottom: 1.2rem;
}

.news-tag {
    display: inline-flex;
    align-items: center;
    padding: 0.42rem 0.8rem;
    border-radius: 999px;
    background: #173626;
    color: #f4c542;
    font-size: 0.8rem;
    font-weight: 800;
}

.news-date {
    color: #7b857d;
    font-size: 0.9rem;
}

.news-card h3 {
    margin: 0 0 0.8rem;
    color: #173626;
    font-size: clamp(1.45rem, 3vw, 2.25rem);
    letter-spacing: -0.03em;
}

.news-card p {
    line-height: 1.75;
}

.news-actions {
    margin-top: 1.5rem;
}

.carousel-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    width: 46px;
    height: 46px;
    border: none;
    border-radius: 50%;
    background: #173626;
    color: #f4c542;
    box-shadow: 0 14px 30px rgba(0,0,0,0.18);
    cursor: pointer;
    transition: 0.2s ease;
}

.carousel-btn:hover {
    transform: translateY(-50%) scale(1.08);
    background: #224e38;
}

.carousel-btn.prev {
    left: -18px;
}

.carousel-btn.next {
    right: -18px;
}

.carousel-dots {
    display: flex;
    justify-content: center;
    gap: 0.55rem;
    margin-top: 1.3rem;
}

.carousel-dots .dot {
    width: 10px;
    height: 10px;
    border: none;
    border-radius: 999px;
    background: #c9d2c7;
    cursor: pointer;
    transition: 0.2s ease;
}

.carousel-dots .dot.active {
    width: 28px;
    background: #173626;
}

/* About */
.about-card {
    position: relative;
    overflow: hidden;
    padding: 2.5rem;
    border-radius: 2rem;
    background:
        linear-gradient(135deg, #173626 0%, #224e38 55%, #2d6a4f 100%);
    color: #fff;
    box-shadow: 0 24px 60px rgba(0,0,0,0.16);
}

.about-card::after {
    content: "";
    position: absolute;
    right: -80px;
    bottom: -80px;
    width: 240px;
    height: 240px;
    border-radius: 50%;
    background: rgba(244, 197, 66, 0.22);
}

.about-card > * {
    position: relative;
    z-index: 1;
}

.about-card p {
    max-width: 900px;
    line-height: 1.8;
    color: rgba(255,255,255,0.9);
}

.about-card .divider {
    background: rgba(255,255,255,0.18);
}

.info-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1rem;
    margin-top: 1.8rem;
}

.info {
    padding: 1.25rem;
    border-radius: 1.4rem;
    background: rgba(255,255,255,0.11);
    border: 1px solid rgba(255,255,255,0.12);
}

.info h4 {
    margin: 0 0 0.5rem;
    color: #f4c542;
}

.info p {
    margin: 0;
    color: rgba(255,255,255,0.82);
}

/* Clubs */
.club-cards {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.2rem;
    margin-top: 2rem;
}

.club-card {
    position: relative;
    overflow: hidden;
    padding: 1.4rem;
    border-radius: 1.6rem;
    background: #fff;
    border: 1px solid rgba(20, 50, 34, 0.08);
    box-shadow: 0 16px 45px rgba(0,0,0,0.07);
    transition: 0.25s ease;
}

.club-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 24px 60px rgba(0,0,0,0.12);
}

.club-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(90deg, #173626, #f4c542);
}

.club-card-head {
    display: flex;
    flex-direction: column;
    gap: 0.8rem;
}

.club-card-title {
    margin: 0;
    color: #173626;
    font-size: 1.25rem;
}

.club-badge {
    align-self: flex-start;
    max-width: 100%;
    line-height: 1.4;
}

.club-address {
    display: flex;
    gap: 0.55rem;
    margin: 1.1rem 0;
    line-height: 1.55;
}

.club-address i {
    color: #d6a823;
    margin-top: 0.2rem;
}

.club-card-actions {
    margin-top: auto;
}

.club-link {
    font-weight: 800;
    color: #173626;
    text-decoration: none;
}

.club-link:hover {
    color: #d6a823;
}

/* Empty state */
.empty-state {
    padding: 2rem;
    border-radius: 1.5rem;
    background: #fff;
    border: 1px dashed rgba(23, 54, 38, 0.25);
    text-align: center;
}

/* Responsive */
@media (max-width: 980px) {
    .home-hero {
        padding: 4rem 0;
        background:
            radial-gradient(circle at top left, rgba(255, 198, 93, 0.25), transparent 35%),
            linear-gradient(135deg, #10241b 0%, #173626 100%);
    }

    .home-hero-inner {
        grid-template-columns: 1fr;
    }

    .hero-card,
    .hero-photo-card {
        max-width: 620px;
    }

    .hero-photo-card {
        min-height: 360px;
    }

    .hero-photo-card img {
        min-height: 360px;
    }

    .club-cards {
        grid-template-columns: repeat(2, 1fr);
    }

    .info-grid {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 640px) {
    .home-hero {
        padding: 3.2rem 0;
    }

    .hero-actions {
        flex-direction: column;
    }

    .hero-actions .btn {
        width: 100%;
        justify-content: center;
    }

    .hero-stats {
        grid-template-columns: 1fr;
    }

    .hero-photo-card {
        min-height: 300px;
        border-radius: 1.5rem;
    }

    .hero-photo-card img {
        min-height: 300px;
    }

    .hero-photo-caption {
        left: .85rem;
        right: .85rem;
        bottom: .85rem;
    }

    .section-title-row {
        flex-direction: column;
        align-items: flex-start;
        gap: 1rem;
    }

    .club-cards {
        grid-template-columns: 1fr;
    }

    .carousel-btn {
        display: none;
    }

    .news-card,
    .about-card {
        padding: 1.5rem;
        border-radius: 1.5rem;
    }
}
</style>

<!-- HERO -->
<section class="home-hero">
    <div class="container">
        <div class="home-hero-inner">
            <div class="home-hero-content">
                <div class="hero-kicker">
                    <i class="fa-solid fa-compass"></i>
                    Piedzīvojumi. Prasmes. Vērtības.
                </div>

                <h1>Ceļa meklētāji</h1>

                <p class="lead">
                    Bērnu un jauniešu programmas visā Latvijā — vieta, kur aug draudzība,
                    praktiskas prasmes, atbildība un drosme darīt labu.
                </p>

                <div class="hero-actions">
                    <a class="btn btn-primary" href="public/about.php">
                        Uzzināt par programmām
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <a class="btn btn-outline" href="public/clubs.php">
                        Atrast tuvāko klubu
                    </a>
                </div>
            </div>

   <aside class="hero-card">
    <div class="hero-card-image">
        <img 
            src="<?= BASE_URL ?>assets/images/hero/hero.jpg" 
            alt="Ceļa meklētāju pasākuma dalībnieki"
        >
    </div>

    <div class="hero-card-icon">
        <i class="fa-solid fa-mountain-sun"></i>
    </div>

    <h3>Vairāk nekā tikai nodarbības</h3>

    <p>
        Nometnes, pārgājieni, praktiski uzdevumi un komandas darbs —
        viss vienā kopienā, kur bērni un jaunieši var augt drošā vidē.
    </p>

    <div class="hero-stats">
        <div class="stat-pill">
            <strong><?= count($clubs) ?>+</strong>
            <span>klubi</span>
        </div>

        <div class="stat-pill">
            <strong><?= count($news) ?>+</strong>
            <span>aktualitātes</span>
        </div>

        <div class="stat-pill">
            <strong>4–16</strong>
            <span>gadi</span>
        </div>
    </div>
</aside>
        </div>
    </div>
</section>

<!-- AKTUALITĀTES -->
<section id="jaunumi" class="home-section section-alt">
    <div class="container">
        <header class="section-title section-title-row">
            <div>
                <h2>Aktualitātes</h2>
                <p class="muted">
                    Jaunākie notikumi, nometnes, nodarbības un projekti vienuviet.
                </p>
            </div>

            <a class="btn btn-outline btn-sm" href="public/news.php">
                Skatīt visas <i class="fa-solid fa-angles-right"></i>
            </a>
        </header>

        <?php if (!empty($news)): ?>
            <div class="carousel one" id="newsCarousel">
                <div class="carousel-viewport">
                    <div class="carousel-track" id="carouselTrack">
                        <?php foreach ($news as $item): ?>
                            <article class="carousel-slide">
                                <div class="news-card">
                                    <div>
                                        <div class="news-meta">
                                            <span class="news-tag">
                                                <?= htmlspecialchars($item['category']) ?>
                                            </span>

                                            <span class="news-date">
                                                <i class="fa-regular fa-calendar"></i>
                                                <?= htmlspecialchars(date('d.m.Y', strtotime($item['publish_date']))) ?>
                                            </span>
                                        </div>

                                        <h3><?= htmlspecialchars($item['title']) ?></h3>

                                        <p class="muted">
                                            <?= htmlspecialchars(mb_strimwidth($item['description'], 0, 190, '...')) ?>
                                        </p>
                                    </div>

                                    <div class="news-actions">
                                        <a href="public/news_single.php?id=<?= (int)$item['id'] ?>" class="btn btn-primary btn-sm">
                                            Lasīt vairāk
                                            <i class="fa-solid fa-arrow-right"></i>
                                        </a>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button class="carousel-btn prev" type="button" aria-label="Iepriekšējā aktualitāte">
                    <i class="fas fa-chevron-left"></i>
                </button>

                <button class="carousel-btn next" type="button" aria-label="Nākamā aktualitāte">
                    <i class="fas fa-chevron-right"></i>
                </button>

                <div class="carousel-dots" id="carouselDots"></div>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p class="muted">Šobrīd nav pieejamu aktualitāšu.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- PAR BIEDRĪBU -->
<section class="home-section">
    <div class="container">
        <header class="section-title">
            <h2>Par biedrību</h2>
            <p class="muted">Īsumā — kas mēs esam un ko darām.</p>
        </header>

        <div class="about-card">
            <p>
                “Ceļa meklētāji” ir kristīga biedrība, kas nodarbojas ar bērnu un jauniešu
                izglītošanu, organizējot nodarbības, nometnes un pasākumus visā Latvijā.
                Mērķis ir palīdzēt jauniešiem attīstīt praktiskas prasmes, draudzību,
                atbildību un vērtības.
            </p>

            <div class="divider"></div>

            <div class="info-grid">
                <div class="info">
                    <h4><i class="fa-solid fa-map"></i> Programmas</h4>
                    <p>Strukturētas aktivitātes dažādām vecuma grupām.</p>
                </div>

                <div class="info">
                    <h4><i class="fa-solid fa-campground"></i> Pasākumi</h4>
                    <p>Nometnes, pārgājieni un radošas nodarbības.</p>
                </div>

                <div class="info">
                    <h4><i class="fa-solid fa-people-group"></i> Kopiena</h4>
                    <p>Draudzība, atbildība un vērtības ikdienā.</p>
                </div>
            </div>

            <div style="margin-top:1.5rem;text-align:center;">
                <a class="btn btn-primary" href="public/about.php">
                    Lasīt vairāk
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- KLUBI -->
<section class="home-section section-alt">
    <div class="container">
        <header class="section-title section-title-row">
            <div>
                <h2>Klubi Latvijā</h2>
                <p class="muted">Atrodi tuvāko klubu un pievienojies aktivitātēm.</p>
            </div>

            <a class="btn btn-outline btn-sm" href="public/clubs.php">
                Skatīt visus <i class="fa-solid fa-angles-right"></i>
            </a>
        </header>

        <?php if (!empty($clubs)): ?>
            <div class="club-cards">
                <?php foreach ($clubs as $club): ?>
                    <article class="club-card">
                        <div class="club-card-head">
                            <h3 class="club-card-title">
                                <?= htmlspecialchars($club['name']) ?>
                            </h3>

                            <span class="badge badge-gold club-badge">
                                <?= htmlspecialchars($club['programs'] ?: 'Nav programmas') ?>
                            </span>
                        </div>

                        <p class="muted club-address">
                            <i class="fas fa-location-dot"></i>
                            <span><?= htmlspecialchars($club['address']) ?></span>
                        </p>

                        <div class="club-card-actions">
                            <a class="club-link" href="public/clubs.php?id=<?= (int)$club['id'] ?>">
                                Apskatīt klubu →
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <p class="muted">Šobrīd nav pieejamu klubu.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const carousel = document.getElementById("newsCarousel");
    const track = document.getElementById("carouselTrack");
    const dotsContainer = document.getElementById("carouselDots");

    if (!carousel || !track || !dotsContainer) return;

    let slides = Array.from(track.children);
    const prevBtn = carousel.querySelector(".prev");
    const nextBtn = carousel.querySelector(".next");

    if (slides.length <= 1) {
        if (prevBtn) prevBtn.style.display = "none";
        if (nextBtn) nextBtn.style.display = "none";
        return;
    }

    let index = 1;
    let autoplay = null;

    const firstClone = slides[0].cloneNode(true);
    const lastClone = slides[slides.length - 1].cloneNode(true);

    track.appendChild(firstClone);
    track.insertBefore(lastClone, slides[0]);

    slides = Array.from(track.children);

    function setSlideWidths() {
        slides.forEach(slide => {
            slide.style.flex = "0 0 100%";
        });
    }

    function moveToIndex(withTransition = true) {
        track.style.transition = withTransition ? "transform 0.45s ease" : "none";
        track.style.transform = `translateX(-${index * 100}%)`;
    }

    function renderDots() {
        dotsContainer.innerHTML = "";

        for (let i = 0; i < slides.length - 2; i++) {
            const dot = document.createElement("button");
            dot.type = "button";
            dot.className = "dot" + (i === index - 1 ? " active" : "");
            dot.setAttribute("aria-label", `Aktualitāte ${i + 1}`);

            dot.addEventListener("click", function () {
                index = i + 1;
                moveToIndex();
                restartAutoplay();
            });

            dotsContainer.appendChild(dot);
        }
    }

    function nextSlide() {
        index++;
        moveToIndex();
    }

    function prevSlide() {
        index--;
        moveToIndex();
    }

    track.addEventListener("transitionend", function () {
        if (index === slides.length - 1) {
            index = 1;
            moveToIndex(false);
        }

        if (index === 0) {
            index = slides.length - 2;
            moveToIndex(false);
        }

        renderDots();
    });

    function startAutoplay() {
        stopAutoplay();
        autoplay = setInterval(nextSlide, 4500);
    }

    function stopAutoplay() {
        if (autoplay) {
            clearInterval(autoplay);
            autoplay = null;
        }
    }

    function restartAutoplay() {
        stopAutoplay();
        startAutoplay();
    }

    if (nextBtn) {
        nextBtn.addEventListener("click", function () {
            nextSlide();
            restartAutoplay();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", function () {
            prevSlide();
            restartAutoplay();
        });
    }

    carousel.addEventListener("mouseenter", stopAutoplay);
    carousel.addEventListener("mouseleave", startAutoplay);

    setSlideWidths();
    moveToIndex(false);
    renderDots();
    startAutoplay();
});
</script>

<?php require __DIR__ . "/includes/templates/footer.php"; ?>
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
                    <a class="btn btn-primary" href="public/about.php">Uzzināt par programmām</a>
                    <a class="btn btn-outline" href="public/clubs.php">Atrast klubu</a>
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
                                    <div class="news-meta">
                                        <span class="news-tag">
                                            <?= htmlspecialchars($item['category']) ?>
                                        </span>
                                        <span class="news-date">
                                            <?= htmlspecialchars(date('d.m.Y', strtotime($item['publish_date']))) ?>
                                        </span>
                                    </div>

                                    <h3><?= htmlspecialchars($item['title']) ?></h3>

                                    <p class="muted">
                                        <?= htmlspecialchars(mb_strimwidth($item['description'], 0, 170, '...')) ?>
                                    </p>

                                    <div class="news-actions">
                                        <a href="public/news.php?id=<?= (int)$item['id'] ?>" class="btn btn-primary btn-sm">
                                            Lasīt vairāk
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
            <div class="card">
                <p class="muted">Šobrīd nav pieejamu aktualitāšu.</p>
            </div>
        <?php endif; ?>
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
                <a class="btn btn-outline" href="public/about.php">Lasīt vairāk</a>
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
            <a class="btn btn-outline btn-sm" href="public/clubs.php">
                Skatīt visus <i class="fa-solid fa-angles-right"></i>
            </a>
        </header>

        <div class="cards club-cards">
            <?php foreach ($clubs as $club): ?>
                <article class="card club-card">
                    <div class="club-card-head">
                        <h3 class="club-card-title"><?= htmlspecialchars($club['name']) ?></h3>

                        <span class="badge badge-gold club-badge">
                            <?= htmlspecialchars($club['programs'] ?: 'Nav programmas') ?>
                        </span>
                    </div>

                    <p class="muted club-address">
                        <i class="fas fa-location-dot"></i>
                        <span><?= htmlspecialchars($club['address']) ?></span>
                    </p>

                    <div class="club-card-actions">
                        <a class="link club-link" href="public/clubs.php?id=<?= (int)$club['id'] ?>">
                            Apskatīt →
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const carousel = document.getElementById("newsCarousel");
    const track = document.getElementById("carouselTrack");
    const dotsContainer = document.getElementById("carouselDots");

    if (!carousel || !track) return;

    let slides = Array.from(track.children);
    const prevBtn = carousel.querySelector(".prev");
    const nextBtn = carousel.querySelector(".next");

    let index = 1;
    let autoplay = null;

    // 🔁 Clone first & last
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
        if (!withTransition) {
            track.style.transition = "none";
        } else {
            track.style.transition = "transform 0.45s ease";
        }

        track.style.transform = `translateX(-${index * 100}%)`;
    }

    function renderDots() {
        dotsContainer.innerHTML = "";

        for (let i = 0; i < slides.length - 2; i++) {
            const dot = document.createElement("button");
            dot.className = "dot" + (i === index - 1 ? " active" : "");

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

    track.addEventListener("transitionend", () => {
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
        nextBtn.addEventListener("click", () => {
            nextSlide();
            restartAutoplay();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener("click", () => {
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
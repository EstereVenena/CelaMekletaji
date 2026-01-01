<?php
    $lapa = "Ceļa meklētāji";
    $title = "Ceļa meklētāji";
    require "assets/header.php";
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

<!-- AKTUALITĀTES (ONE-SLIDE CAROUSEL) -->
<section id="jaunumi" class="section section-alt">
    <div class="container">
        <header class="section-title section-title-row">
            <div>
                <h2>Aktualitātes</h2>
                <p class="muted">Jaunākie notikumi, nometnes un projekti.</p>
            </div>
            <a class="btn btn-outline btn-sm" href="news.html">
                Skatīt visas <i class="fa-solid fa-angles-right"></i>
            </a>
        </header>

        <div class="carousel one" id="newsCarousel" aria-label="Aktualitāšu karuselis">
            <div class="carousel-viewport">
                <div class="carousel-track" id="carouselTrack">

                    <!-- Slide 1 -->
                    <article class="carousel-slide">
                        <div class="news-card">
                            <div class="news-meta">
                                <span class="news-tag">Projekts</span>
                                <span class="news-date">2026-03-12</span>
                            </div>
                            <h3>GENERATION Z BE READY</h3>
                            <p class="muted">Jauniešu personīgās izaugsmes programma</p>
                            <div class="news-actions">
                                <a href="news.html" class="btn btn-primary btn-sm">Lasīt</a>
                            </div>
                        </div>
                    </article>

                    <!-- Slide 2 -->
                    <article class="carousel-slide">
                        <div class="news-card">
                            <div class="news-meta">
                                <span class="news-tag">Nometnes</span>
                                <span class="news-date">2026-04-15</span>
                            </div>
                            <h3>Pavasara nometne 2026</h3>
                            <p class="muted">Aicinām bērnus un jauniešus piedalīties</p>
                            <div class="news-actions">
                                <a href="news.html" class="btn btn-primary btn-sm">Lasīt</a>
                            </div>
                        </div>
                    </article>

                    <!-- Slide 3 -->
                    <article class="carousel-slide">
                        <div class="news-card">
                            <div class="news-meta">
                                <span class="news-tag">Pasākumi</span>
                                <span class="news-date">2026-05-02</span>
                            </div>
                            <h3>Reģionālais pārgājiens</h3>
                            <p class="muted">Daba, komandas darbs un praktiskās prasmes vienā dienā</p>
                            <div class="news-actions">
                                <a href="news.html" class="btn btn-primary btn-sm">Lasīt</a>
                            </div>
                        </div>
                    </article>

                </div>
            </div>

            <button class="carousel-btn prev" type="button" aria-label="Iepriekšējais">
                <i class="fas fa-chevron-left" aria-hidden="true"></i>
            </button>
            <button class="carousel-btn next" type="button" aria-label="Nākamais">
                <i class="fas fa-chevron-right" aria-hidden="true"></i>
            </button>

            <div class="carousel-dots" id="carouselDots" aria-label="Karuseļa punkti"></div>
        </div>
    </div>
</section>

<!-- PAR BIEDRĪBU -->
<section id="pakalpojumi" class="section">
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
                    <p class="muted">No mazākajiem līdz jauniešiem — strukturētas aktivitātes un izaugsme.</p>
                </div>
                <div class="info">
                    <h4>Pasākumi</h4>
                    <p class="muted">Nometnes, pārgājieni, komandu uzdevumi un radošas nodarbības.</p>
                </div>
                <div class="info">
                    <h4>Kopiena</h4>
                    <p class="muted">Draudzība, atbildība, kalpošana un vērtības, kas turas arī dzīvē.</p>
                </div>
            </div>

            <div style="margin-top: 1.25rem; text-align:center;">
                <a class="btn btn-outline" href="about.php">Lasīt vairāk</a>
            </div>
        </div>
    </div>
</section>

<!-- KLUBI -->
<section id="vakances" class="section section-alt">
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
            <article class="card club-card">
                <div class="club-top">
                    <h3>Rīga CM</h3>
                    <span class="badge badge-gold">10–16</span>
                </div>
                <p class="muted"><i class="fas fa-location-dot"></i> Rīga</p>
                <a class="link" href="clubs.php">Apskatīt →</a>
            </article>

            <article class="card club-card">
                <div class="club-top">
                    <h3>Liepāja PM</h3>
                    <span class="badge badge-gold">4–9</span>
                </div>
                <p class="muted"><i class="fas fa-location-dot"></i> Liepāja</p>
                <a class="link" href="clubs.php">Apskatīt →</a>
            </article>

            <article class="card club-card">
                <div class="club-top">
                    <h3>Valmiera CM</h3>
                    <span class="badge badge-gold">10–16</span>
                </div>
                <p class="muted"><i class="fas fa-location-dot"></i> Valmiera</p>
                <a class="link" href="clubs.php">Apskatīt →</a>
            </article>
        </div>
    </div>
</section>

<!-- CAROUSEL SCRIPT MUST BE BEFORE footer.php (because footer closes body/html) -->
<script>
(function(){
    const root = document.getElementById('newsCarousel');
    if (!root) return;

    const track = document.getElementById('carouselTrack');
    const dotsWrap = document.getElementById('carouselDots');
    const prevBtn = root.querySelector('.carousel-btn.prev');
    const nextBtn = root.querySelector('.carousel-btn.next');
    const slides = Array.from(track.querySelectorAll('.carousel-slide'));

    if (!slides.length) return;

    let index = 0;
    let startX = null;

    // Build dots
    dotsWrap.innerHTML = '';
    slides.forEach((_, i) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'dot';
        b.setAttribute('aria-label', `Iet uz ierakstu ${i + 1}`);
        b.addEventListener('click', () => go(i));
        dotsWrap.appendChild(b);
    });

    const dots = Array.from(dotsWrap.querySelectorAll('.dot'));

    function slideWidth(){
        return root.querySelector('.carousel-viewport').getBoundingClientRect().width;
    }

    function update(){
        const w = slideWidth();
        track.style.transform = `translateX(-${w * index}px)`;

        dots.forEach((d, i) => d.classList.toggle('active', i === index));
        prevBtn.disabled = index === 0;
        nextBtn.disabled = index === slides.length - 1;
    }

    function go(i){
        index = Math.max(0, Math.min(slides.length - 1, i));
        update();
    }

    prevBtn.addEventListener('click', () => go(index - 1));
    nextBtn.addEventListener('click', () => go(index + 1));

    // Keyboard support when carousel is in view
    document.addEventListener('keydown', (e) => {
        const rect = root.getBoundingClientRect();
        const inView = rect.top < window.innerHeight && rect.bottom > 0;
        if (!inView) return;

        if (e.key === 'ArrowLeft') go(index - 1);
        if (e.key === 'ArrowRight') go(index + 1);
    });

    // Touch swipe
    root.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    }, { passive: true });

    root.addEventListener('touchend', (e) => {
        if (startX === null) return;
        const dx = e.changedTouches[0].clientX - startX;
        startX = null;

        if (Math.abs(dx) > 50) {
            dx > 0 ? go(index - 1) : go(index + 1);
        }
    });

    window.addEventListener('resize', update);

    update();
})();
</script>

<?php
    require "assets/footer.php";
?>

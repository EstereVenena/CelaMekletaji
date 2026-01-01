<?php
    $lapa = "Ceļa meklētāju galerija";
    $title = "Galerija | Ceļa meklētāji";
    require "assets/header.php";
?>

<section class="section">
    <div class="container">
        <header class="section-title">
            <h2>Galerija</h2>
            <p class="muted">Atmiņas pa gadiem — uzklikšķini, lai atvērtu pilnā izmērā.</p>
        </header>

        <div class="gallery-grid" id="galleryGrid">
            <button class="gallery-card" type="button" data-full="images/2020.jpg" data-caption="2020">
                <img src="images/2020.jpg" alt="2020">
                <span class="gallery-badge">2020</span>
            </button>

            <button class="gallery-card" type="button" data-full="images/2021.jpg" data-caption="2021">
                <img src="images/2021.jpg" alt="2021">
                <span class="gallery-badge">2021</span>
            </button>

            <button class="gallery-card" type="button" data-full="images/2022.jpg" data-caption="2022">
                <img src="images/2022.jpg" alt="2022">
                <span class="gallery-badge">2022</span>
            </button>

            <button class="gallery-card" type="button" data-full="images/2023.jpg" data-caption="2023">
                <img src="images/2023.jpg" alt="2023">
                <span class="gallery-badge">2023</span>
            </button>
        </div>
    </div>
</section>

<!-- LIGHTBOX / SHADOWBOX -->
<div class="lightbox" id="lightbox" aria-hidden="true">
    <div class="lightbox-overlay" id="lightboxOverlay"></div>

    <div class="lightbox-panel" role="dialog" aria-modal="true" aria-label="Galerijas attēls">
        <button class="lightbox-close" id="lightboxClose" type="button" aria-label="Aizvērt">
            <i class="fas fa-xmark" aria-hidden="true"></i>
        </button>

        <button class="lightbox-nav prev" id="lightboxPrev" type="button" aria-label="Iepriekšējais">
            <i class="fas fa-chevron-left" aria-hidden="true"></i>
        </button>

        <figure class="lightbox-figure">
            <img class="lightbox-img" id="lightboxImg" alt="">
            <figcaption class="lightbox-caption" id="lightboxCaption"></figcaption>
        </figure>

        <button class="lightbox-nav next" id="lightboxNext" type="button" aria-label="Nākamais">
            <i class="fas fa-chevron-right" aria-hidden="true"></i>
        </button>

        <!-- Filmstrip thumbnails -->
        <div class="lightbox-strip" id="lightboxStrip" aria-label="Sīktēlu josla"></div>
    </div>
</div>

<script>
(function () {
    const grid = document.getElementById('galleryGrid');
    const cards = Array.from(grid.querySelectorAll('.gallery-card'));

    const lb = document.getElementById('lightbox');
    const overlay = document.getElementById('lightboxOverlay');
    const img = document.getElementById('lightboxImg');
    const caption = document.getElementById('lightboxCaption');
    const btnClose = document.getElementById('lightboxClose');
    const btnPrev = document.getElementById('lightboxPrev');
    const btnNext = document.getElementById('lightboxNext');
    const strip = document.getElementById('lightboxStrip');

    let index = 0;
    let startX = null;

    const items = cards.map((btn) => ({
        full: btn.dataset.full,
        thumb: btn.querySelector('img').getAttribute('src'),
        alt: btn.querySelector('img').getAttribute('alt') || '',
        caption: btn.dataset.caption || ''
    }));

    function buildStrip() {
        strip.innerHTML = '';
        items.forEach((it, i) => {
            const t = document.createElement('button');
            t.type = 'button';
            t.className = 'strip-thumb';
            t.setAttribute('aria-label', `Atvērt ${it.caption || it.alt || (i+1)}`);
            t.innerHTML = `<img src="${it.thumb}" alt="">`;
            t.addEventListener('click', () => show(i));
            strip.appendChild(t);
        });
    }

    function highlightStrip() {
        const thumbs = Array.from(strip.querySelectorAll('.strip-thumb'));
        thumbs.forEach((t, i) => t.classList.toggle('active', i === index));

        // Auto scroll active into view
        const active = thumbs[index];
        if (active) active.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
    }

    function open(i) {
        index = i;
        lb.classList.add('open');
        lb.setAttribute('aria-hidden', 'false');
        document.body.classList.add('nav-lock'); // reuse lock class
        show(index);
    }

    function close() {
        lb.classList.remove('open');
        lb.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('nav-lock');
    }

    function show(i) {
        index = (i + items.length) % items.length;
        const it = items[index];
        img.src = it.full;
        img.alt = it.alt;
        caption.textContent = it.caption;
        highlightStrip();
    }

    function prev() { show(index - 1); }
    function next() { show(index + 1); }

    // Open from grid
    cards.forEach((btn, i) => btn.addEventListener('click', () => open(i)));

    // Controls
    btnClose.addEventListener('click', close);
    overlay.addEventListener('click', close);
    btnPrev.addEventListener('click', prev);
    btnNext.addEventListener('click', next);

    // Keyboard
    document.addEventListener('keydown', (e) => {
        if (!lb.classList.contains('open')) return;
        if (e.key === 'Escape') close();
        if (e.key === 'ArrowLeft') prev();
        if (e.key === 'ArrowRight') next();
    });

    // Swipe (mobile)
    img.addEventListener('touchstart', (e) => {
        startX = e.touches[0].clientX;
    }, { passive: true });

    img.addEventListener('touchend', (e) => {
        if (startX === null) return;
        const endX = e.changedTouches[0].clientX;
        const dx = endX - startX;
        startX = null;
        if (Math.abs(dx) > 50) {
            dx > 0 ? prev() : next();
        }
    });

    // Build filmstrip once
    buildStrip();
})();
</script>

<?php
    require "assets/footer.php";
?>

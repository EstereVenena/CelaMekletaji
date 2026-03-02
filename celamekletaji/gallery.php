<?php
    $lapa = "Ceļa meklētāju galerija";
    $title = "Galerija | Ceļa meklētāji";
    require "assets/header.php";
    require_once "assets/database.php";

    $sort = $_GET['sort'] ?? 'upload_date';
    $order = 'DESC';
    if ($sort == 'year') $order = 'ASC';

    $images = [];
    $sql = "SELECT id, filename, path, year, creator, category FROM cm_gallery_images ORDER BY $sort $order";
    $result = $savienojums->query($sql);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
    }
?>

<section class="section">
    <div class="container">
        <header class="section-title">
            <h2>Galerija</h2>
            <p class="muted">Atmiņas pa gadiem — uzklikšķini, lai atvērtu pilnā izmērā.</p>
            <div class="sort-options" style="margin-top: 1rem;">
                <a href="?sort=upload_date" class="btn btn-outline btn-sm">Jaunākie</a>
                <a href="?sort=year" class="btn btn-outline btn-sm">Pēc gada</a>
                <a href="?sort=creator" class="btn btn-outline btn-sm">Pēc autora</a>
                <a href="?sort=category" class="btn btn-outline btn-sm">Pēc kategorijas</a>
            </div>
        </header>

        <div class="gallery-grid" id="galleryGrid">
            <?php foreach ($images as $img): ?>
                <button class="gallery-card" type="button" data-full="<?= htmlspecialchars($img['path']) ?>" data-caption="<?= htmlspecialchars($img['year'] . ' - ' . $img['creator'] . ' (' . $img['category'] . ')') ?>">
                    <img src="<?= htmlspecialchars($img['path']) ?>" alt="<?= htmlspecialchars($img['filename']) ?>">
                    <span class="gallery-badge"><?= htmlspecialchars($img['year']) ?></span>
                </button>
            <?php endforeach; ?>
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

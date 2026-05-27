<?php
$lapa  = "Galerija";
$title = "Galerija | Ceļa meklētāji";

require __DIR__ . "/../includes/templates/header.php";
require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   DROŠA ŠĶIROŠANA
================================ */

$allowedSorts = [
    'upload_date' => ['column' => 'upload_date', 'order' => 'DESC', 'label' => 'Jaunākie'],
    'year'        => ['column' => 'year',        'order' => 'ASC',  'label' => 'Pēc gada'],
    'creator'     => ['column' => 'creator',     'order' => 'ASC',  'label' => 'Pēc autora'],
    'category'    => ['column' => 'category',    'order' => 'ASC',  'label' => 'Pēc kategorijas'],
];

$sort = $_GET['sort'] ?? 'upload_date';

if (!array_key_exists($sort, $allowedSorts)) {
    $sort = 'upload_date';
}

$sortColumn = $allowedSorts[$sort]['column'];
$order      = $allowedSorts[$sort]['order'];

/* ===============================
   ATTĒLI
================================ */

$images = [];

$sql = "
    SELECT id, filename, path, year, creator, category
    FROM cm_gallery_images
    ORDER BY $sortColumn $order
";

$result = $savienojums->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
}
?>

<style>
/* ===============================
   GALLERY PAGE
================================ */

.gallery-hero {
    position: relative;
    overflow: hidden;
    padding: 4.8rem 0 3.8rem;
    background:
        radial-gradient(circle at top left, rgba(244, 197, 66, 0.28), transparent 35%),
        radial-gradient(circle at bottom right, rgba(45, 106, 79, 0.45), transparent 42%),
        linear-gradient(135deg, #10241b 0%, #173626 58%, #224e38 100%);
    color: #ffffff;
}

.gallery-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: 0.45;
}

.gallery-hero .container {
    position: relative;
    z-index: 1;
}

.gallery-kicker {
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

.gallery-hero h1 {
    margin: 0;
    font-size: clamp(2.5rem, 5vw, 4.6rem);
    line-height: 1;
    letter-spacing: -0.055em;
}

.gallery-hero p {
    max-width: 720px;
    margin: 1.2rem 0 0;
    color: rgba(255,255,255,0.86);
    font-size: 1.1rem;
    line-height: 1.75;
}

/* Filter bar */

.gallery-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
    padding: 1rem;
    border-radius: 1.4rem;
    background: #ffffff;
    border: 1px solid rgba(23,54,38,0.08);
    box-shadow: 0 14px 36px rgba(0,0,0,0.06);
}

.gallery-count {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    color: #526358;
    font-weight: 800;
}

.gallery-count i {
    color: #d6a823;
}

.sort-options {
    display: flex;
    gap: 0.55rem;
    flex-wrap: wrap;
}

.sort-options .btn.active {
    background: #173626;
    color: #f4c542;
    border-color: #173626;
}

/* Grid */

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 1.15rem;
}

.gallery-card {
    position: relative;
    overflow: hidden;
    min-height: 250px;
    padding: 0;
    border: none;
    border-radius: 1.6rem;
    background: #e9e3d1;
    box-shadow: 0 16px 45px rgba(0,0,0,0.09);
    cursor: pointer;
    transition: 0.25s ease;
}

.gallery-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 28px 70px rgba(0,0,0,0.16);
}

.gallery-card img {
    width: 100%;
    height: 100%;
    min-height: 250px;
    object-fit: cover;
    display: block;
    transition: 0.35s ease;
}

.gallery-card:hover img {
    transform: scale(1.08);
}

.gallery-card::after {
    content: "";
    position: absolute;
    inset: 0;
    background:
        linear-gradient(to top, rgba(0,0,0,0.62), transparent 58%);
    opacity: 0.95;
}

.gallery-badge {
    position: absolute;
    top: 0.9rem;
    left: 0.9rem;
    z-index: 2;
    padding: 0.42rem 0.75rem;
    border-radius: 999px;
    background: rgba(23,54,38,0.9);
    color: #f4c542;
    font-size: 0.82rem;
    font-weight: 900;
    backdrop-filter: blur(8px);
}

.gallery-meta {
    position: absolute;
    left: 1rem;
    right: 1rem;
    bottom: 1rem;
    z-index: 2;
    color: #fff;
    text-align: left;
}

.gallery-meta strong {
    display: block;
    font-size: 1rem;
    margin-bottom: 0.25rem;
}

.gallery-meta span {
    display: block;
    color: rgba(255,255,255,0.8);
    font-size: 0.88rem;
}

.gallery-empty {
    padding: 2.5rem;
    border-radius: 1.5rem;
    background: #fff;
    border: 1px dashed rgba(23,54,38,0.22);
    text-align: center;
    color: #526358;
}

/* ===============================
   LIGHTBOX
================================ */

.lightbox {
    position: fixed;
    inset: 0;
    z-index: 2500;
    display: none;
}

.lightbox.open {
    display: block;
}

.lightbox-overlay {
    position: absolute;
    inset: 0;
    background: rgba(8, 18, 13, 0.78);
    backdrop-filter: blur(8px);
}

.lightbox-panel {
    position: relative;
    z-index: 1;
    width: min(1100px, calc(100% - 2rem));
    height: min(780px, calc(100vh - 2rem));
    margin: 1rem auto;
    top: 50%;
    transform: translateY(-50%);
    display: grid;
    grid-template-rows: 1fr auto;
    border-radius: 2rem;
    background: #0f1f17;
    box-shadow: 0 34px 100px rgba(0,0,0,0.42);
    overflow: hidden;
}

.lightbox-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    z-index: 5;
    width: 46px;
    height: 46px;
    border: none;
    border-radius: 1rem;
    background: rgba(255,255,255,0.13);
    color: #ffffff;
    cursor: pointer;
    font-size: 1.1rem;
    backdrop-filter: blur(8px);
    transition: 0.2s ease;
}

.lightbox-close:hover {
    background: #f4c542;
    color: #173626;
}

.lightbox-figure {
    position: relative;
    display: grid;
    place-items: center;
    margin: 0;
    padding: 1.5rem;
    min-height: 0;
}

.lightbox-img {
    max-width: 100%;
    max-height: calc(100vh - 210px);
    object-fit: contain;
    border-radius: 1.2rem;
    box-shadow: 0 20px 60px rgba(0,0,0,0.4);
}

.lightbox-caption {
    position: absolute;
    left: 1.5rem;
    bottom: 1.5rem;
    max-width: calc(100% - 3rem);
    padding: 0.7rem 1rem;
    border-radius: 999px;
    background: rgba(0,0,0,0.55);
    color: rgba(255,255,255,0.9);
    font-weight: 800;
    backdrop-filter: blur(8px);
}

.lightbox-nav {
    position: absolute;
    top: 50%;
    z-index: 4;
    transform: translateY(-50%);
    width: 52px;
    height: 52px;
    border: none;
    border-radius: 50%;
    background: rgba(255,255,255,0.14);
    color: #ffffff;
    cursor: pointer;
    font-size: 1.15rem;
    backdrop-filter: blur(8px);
    transition: 0.2s ease;
}

.lightbox-nav:hover {
    background: #f4c542;
    color: #173626;
}

.lightbox-nav.prev {
    left: 1rem;
}

.lightbox-nav.next {
    right: 1rem;
}

.lightbox-strip {
    display: flex;
    gap: 0.65rem;
    overflow-x: auto;
    padding: 0.9rem;
    background: rgba(0,0,0,0.25);
    border-top: 1px solid rgba(255,255,255,0.08);
}

.strip-thumb {
    width: 74px;
    height: 58px;
    min-width: 74px;
    overflow: hidden;
    padding: 0;
    border: 2px solid transparent;
    border-radius: 0.8rem;
    background: transparent;
    cursor: pointer;
    opacity: 0.6;
    transition: 0.2s ease;
}

.strip-thumb.active {
    opacity: 1;
    border-color: #f4c542;
}

.strip-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}

/* Responsive */

@media (max-width: 1100px) {
    .gallery-grid {
        grid-template-columns: repeat(3, 1fr);
    }
}

@media (max-width: 820px) {
    .gallery-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .lightbox-nav {
        width: 44px;
        height: 44px;
    }
}

@media (max-width: 560px) {
    .gallery-hero {
        padding: 3.5rem 0 2.8rem;
    }

    .gallery-toolbar {
        align-items: stretch;
    }

    .sort-options {
        width: 100%;
    }

    .sort-options .btn {
        flex: 1 1 calc(50% - 0.55rem);
        justify-content: center;
    }

    .gallery-grid {
        grid-template-columns: 1fr;
    }

    .lightbox-panel {
        width: calc(100% - 0.75rem);
        height: calc(100vh - 0.75rem);
        border-radius: 1.3rem;
    }

    .lightbox-caption {
        position: static;
        margin-top: 1rem;
        border-radius: 1rem;
        text-align: center;
    }

    .lightbox-img {
        max-height: calc(100vh - 240px);
    }
}
</style>

<section class="gallery-hero">
    <div class="container">
        <div class="gallery-kicker">
            <i class="fa-solid fa-images"></i>
            Atmiņas un notikumi
        </div>

        <h1>Galerija</h1>

        <p>
            Apskati mirkļus no nodarbībām, nometnēm, pārgājieniem un citiem “Ceļa meklētāju”
            notikumiem. Uzklikšķini uz attēla, lai to atvērtu pilnā izmērā.
        </p>
    </div>
</section>

<section class="section section-alt">
    <div class="container">

        <div class="gallery-toolbar">
            <div class="gallery-count">
                <i class="fa-solid fa-camera-retro"></i>
                <span>
                    Attēli galerijā:
                    <strong><?= count($images); ?></strong>
                </span>
            </div>

            <div class="sort-options">
                <?php foreach ($allowedSorts as $key => $option): ?>
                    <a
                        href="?sort=<?= htmlspecialchars($key); ?>"
                        class="btn btn-outline btn-sm <?= $sort === $key ? 'active' : ''; ?>"
                    >
                        <?= htmlspecialchars($option['label']); ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (!empty($images)): ?>
            <div class="gallery-grid" id="galleryGrid">
                <?php foreach ($images as $img): ?>
                    <?php
                        $year     = htmlspecialchars($img['year'] ?? '');
                        $creator  = htmlspecialchars($img['creator'] ?? 'Nezināms autors');
                        $category = htmlspecialchars($img['category'] ?? 'Bez kategorijas');
                        $filename = htmlspecialchars($img['filename'] ?? 'Galerijas attēls');
                        $path     = htmlspecialchars($img['path'] ?? '');

                        $caption = trim(($img['year'] ?? '') . ' - ' . ($img['creator'] ?? '') . ' (' . ($img['category'] ?? '') . ')');
                    ?>

                    <button
                        class="gallery-card"
                        type="button"
                        data-full="<?= $path; ?>"
                        data-caption="<?= htmlspecialchars($caption); ?>"
                    >
                        <img src="<?= $path; ?>" alt="<?= $filename; ?>">

                        <?php if ($year !== ''): ?>
                            <span class="gallery-badge"><?= $year; ?></span>
                        <?php endif; ?>

                        <span class="gallery-meta">
                            <strong><?= $category; ?></strong>
                            <span><?= $creator; ?></span>
                        </span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="gallery-empty">
                <h3>Galerijā vēl nav attēlu</h3>
                <p class="muted">
                    Kad tiks pievienoti attēli, tie parādīsies šajā sadaļā.
                </p>
            </div>
        <?php endif; ?>

    </div>
</section>

<!-- LIGHTBOX -->
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

        <div class="lightbox-strip" id="lightboxStrip" aria-label="Sīktēlu josla"></div>
    </div>
</div>

<script>
(function () {
    const grid = document.getElementById('galleryGrid');
    if (!grid) return;

    const cards = Array.from(grid.querySelectorAll('.gallery-card'));

    const lb = document.getElementById('lightbox');
    const overlay = document.getElementById('lightboxOverlay');
    const img = document.getElementById('lightboxImg');
    const caption = document.getElementById('lightboxCaption');
    const btnClose = document.getElementById('lightboxClose');
    const btnPrev = document.getElementById('lightboxPrev');
    const btnNext = document.getElementById('lightboxNext');
    const strip = document.getElementById('lightboxStrip');

    if (!lb || !overlay || !img || !caption || !btnClose || !btnPrev || !btnNext || !strip) return;

    let index = 0;
    let startX = null;

    const items = cards.map((btn) => {
        const image = btn.querySelector('img');

        return {
            full: btn.dataset.full,
            thumb: image ? image.getAttribute('src') : '',
            alt: image ? image.getAttribute('alt') : '',
            caption: btn.dataset.caption || ''
        };
    });

    function buildStrip() {
        strip.innerHTML = '';

        items.forEach((item, i) => {
            const thumb = document.createElement('button');
            thumb.type = 'button';
            thumb.className = 'strip-thumb';
            thumb.setAttribute('aria-label', `Atvērt attēlu ${i + 1}`);

            const thumbImg = document.createElement('img');
            thumbImg.src = item.thumb;
            thumbImg.alt = '';

            thumb.appendChild(thumbImg);
            thumb.addEventListener('click', () => show(i));

            strip.appendChild(thumb);
        });
    }

    function highlightStrip() {
        const thumbs = Array.from(strip.querySelectorAll('.strip-thumb'));

        thumbs.forEach((thumb, i) => {
            thumb.classList.toggle('active', i === index);
        });

        const active = thumbs[index];

        if (active) {
            active.scrollIntoView({
                behavior: 'smooth',
                inline: 'center',
                block: 'nearest'
            });
        }
    }

    function openLightbox(i) {
        index = i;

        lb.classList.add('open');
        lb.setAttribute('aria-hidden', 'false');
        document.body.classList.add('nav-lock');

        show(index);
    }

    function closeLightbox() {
        lb.classList.remove('open');
        lb.setAttribute('aria-hidden', 'true');
        document.body.classList.remove('nav-lock');
    }

    function show(i) {
        if (items.length === 0) return;

        index = (i + items.length) % items.length;

        const item = items[index];

        img.src = item.full;
        img.alt = item.alt;
        caption.textContent = item.caption;

        highlightStrip();
    }

    function prev() {
        show(index - 1);
    }

    function next() {
        show(index + 1);
    }

    cards.forEach((button, i) => {
        button.addEventListener('click', () => openLightbox(i));
    });

    btnClose.addEventListener('click', closeLightbox);
    overlay.addEventListener('click', closeLightbox);
    btnPrev.addEventListener('click', prev);
    btnNext.addEventListener('click', next);

    document.addEventListener('keydown', (event) => {
        if (!lb.classList.contains('open')) return;

        if (event.key === 'Escape') closeLightbox();
        if (event.key === 'ArrowLeft') prev();
        if (event.key === 'ArrowRight') next();
    });

    img.addEventListener('touchstart', (event) => {
        startX = event.touches[0].clientX;
    }, { passive: true });

    img.addEventListener('touchend', (event) => {
        if (startX === null) return;

        const endX = event.changedTouches[0].clientX;
        const dx = endX - startX;

        startX = null;

        if (Math.abs(dx) > 50) {
            dx > 0 ? prev() : next();
        }
    });

    buildStrip();
})();
</script>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
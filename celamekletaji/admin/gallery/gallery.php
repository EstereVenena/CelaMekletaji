<?php
$lapa = "Galerija";
$title = "Galerijas pārvaldība";

require __DIR__ . "/../../includes/templates/header-admin.php";
require __DIR__ . "/../../includes/config/database.php";

/* ======================
   FILTERI
====================== */

$year = $_GET['year'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT * FROM cm_gallery_images WHERE 1";

if ($year !== '') {
    $sql .= " AND year = '" . $savienojums->real_escape_string($year) . "'";
}

if ($category !== '') {
    $sql .= " AND category = '" . $savienojums->real_escape_string($category) . "'";
}

$sql .= " ORDER BY upload_date DESC";

$result = $savienojums->query($sql);

$images = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $images[] = $row;
    }
}

/* ======================
   FILTRU OPCIJAS
====================== */

$years = $savienojums->query("
    SELECT DISTINCT year 
    FROM cm_gallery_images 
    WHERE year IS NOT NULL AND year != ''
    ORDER BY year DESC
");

$categories = $savienojums->query("
    SELECT DISTINCT category 
    FROM cm_gallery_images 
    WHERE category IS NOT NULL AND category != ''
    ORDER BY category ASC
");
?>

<section class="section">
    <div class="container">

        <header class="section-title">
            <h1>Galerijas pārvaldība</h1>
            <p class="muted">
                Augšupielādē jaunus attēlus vai ZIP failu ar attēliem, kā arī dzēs esošos attēlus.
            </p>
        </header>

        <?php if (isset($_GET['success']) || isset($_GET['errors'])): ?>
            <?php
                $success = (int)($_GET['success'] ?? 0);
                $errors  = (int)($_GET['errors'] ?? 0);
            ?>

            <div class="<?= $errors > 0 ? 'gallery-alert warning' : 'gallery-alert success' ?>">
                <strong>Augšupielāde pabeigta.</strong><br>
                Veiksmīgi pievienoti attēli: <?= $success ?> |
                Kļūdas: <?= $errors ?>
            </div>
        <?php endif; ?>

        <!-- AUGŠUPIELĀDES FORMA -->
        <form action="upload_gallery.php"
              method="post"
              enctype="multipart/form-data"
              class="gallery-upload-form">

            <div class="upload-row">

                <div class="form-group">
                    <label for="images">Izvēlies attēlus vai ZIP:</label>
                    <input
                        id="images"
                        type="file"
                        name="images[]"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.bmp,.zip,image/*"
                        multiple
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="year">Gads:</label>
                    <input
                        id="year"
                        type="number"
                        name="year"
                        placeholder="2024"
                        min="2000"
                        max="2100"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="creator">Autors:</label>
                    <input
                        id="creator"
                        type="text"
                        name="creator"
                        placeholder="Autora vārds"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="category">Kategorija:</label>
                    <input
                        id="category"
                        type="text"
                        name="category"
                        placeholder="Piemēram: Nometne"
                    >
                </div>

                <button type="submit" name="upload_gallery" class="btn btn-primary">
                    Augšupielādēt
                </button>

            </div>
        </form>

    </div>
</section>

<section class="section">
    <div class="container">

        <h2>Galerija</h2>

        <!-- FILTRI -->
        <form method="get" class="gallery-filter-form">

            <select name="year">
                <option value="">Visi gadi</option>

                <?php if ($years): ?>
                    <?php while ($y = $years->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($y['year']) ?>"
                            <?= (string)$year === (string)$y['year'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($y['year']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <select name="category">
                <option value="">Visas kategorijas</option>

                <?php if ($categories): ?>
                    <?php while ($c = $categories->fetch_assoc()): ?>
                        <option value="<?= htmlspecialchars($c['category']) ?>"
                            <?= (string)$category === (string)$c['category'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['category']) ?>
                        </option>
                    <?php endwhile; ?>
                <?php endif; ?>
            </select>

            <button type="submit" class="btn btn-primary">
                Filtrēt
            </button>

            <a href="gallery.php" class="btn btn-secondary">
                Notīrīt
            </a>

        </form>

        <!-- GALERIJA -->
        <?php if (empty($images)): ?>

            <p class="muted">
                Nav atrasts neviens attēls.
            </p>

        <?php else: ?>

            <div class="gallery-grid">

                <?php foreach ($images as $img): ?>
                    <?php
                        $imagePath = "../" . $img['path'];
                    ?>

                    <div class="gallery-item">

                        <img
                            src="<?= htmlspecialchars($imagePath) ?>"
                            alt="<?= htmlspecialchars($img['filename'] ?? 'Galerijas attēls') ?>"
                            loading="lazy"
                            onclick="openLightbox('<?= htmlspecialchars($imagePath) ?>')"
                        >

                        <a
                            href="delete_image.php?id=<?= (int)$img['id'] ?>"
                            class="delete-btn"
                            onclick="return confirm('Dzēst attēlu?')"
                            title="Dzēst attēlu"
                        >
                            <i class="fas fa-trash"></i>
                        </a>

                        <div class="gallery-meta">
                            <?php if (!empty($img['year'])): ?>
                                <span><?= htmlspecialchars($img['year']) ?></span>
                            <?php endif; ?>

                            <?php if (!empty($img['category'])): ?>
                                <span><?= htmlspecialchars($img['category']) ?></span>
                            <?php endif; ?>
                        </div>

                    </div>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </div>
</section>

<!-- LIGHTBOX -->
<div id="lightbox" onclick="closeLightbox()">
    <img id="lightbox-img" alt="Palielināts galerijas attēls">
</div>

<script>
function openLightbox(src) {
    document.getElementById("lightbox-img").src = src;
    document.getElementById("lightbox").style.display = "flex";
}

function closeLightbox() {
    document.getElementById("lightbox").style.display = "none";
}
</script>

<style>
.gallery-upload-form {
    margin-bottom: 2rem;
}

.upload-row,
.gallery-filter-form {
    display: flex;
    gap: 1rem;
    align-items: end;
    flex-wrap: wrap;
}

.form-group {
    display: flex;
    flex-direction: column;
    gap: 0.35rem;
}

.form-group label {
    font-weight: 600;
}

.form-group input,
.gallery-filter-form select {
    padding: 0.65rem 0.75rem;
    border: 1px solid #ddd;
    border-radius: 8px;
    min-height: 42px;
}

.gallery-alert {
    padding: 14px 16px;
    margin: 1rem 0 1.5rem;
    border-radius: 10px;
    font-weight: 500;
}

.gallery-alert.success {
    background: #e9f7ef;
    color: #1b5e20;
    border: 1px solid #b7e4c7;
}

.gallery-alert.warning {
    background: #fff3cd;
    color: #856404;
    border: 1px solid #ffeeba;
}

.gallery-filter-form {
    margin-bottom: 20px;
}

.gallery-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
    gap: 20px;
}

.gallery-item {
    position: relative;
    aspect-ratio: 1 / 1;
    overflow: hidden;
    border-radius: 12px;
    background: #f4f4f4;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
}

.gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    cursor: pointer;
    transition: transform .3s ease;
}

.gallery-item img:hover {
    transform: scale(1.05);
}

.delete-btn {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #e63946;
    color: white;
    padding: 7px 9px;
    border-radius: 8px;
    text-decoration: none;
    z-index: 2;
}

.delete-btn:hover {
    background: #c1121f;
}

.gallery-meta {
    position: absolute;
    left: 10px;
    bottom: 10px;
    display: flex;
    gap: 6px;
    flex-wrap: wrap;
}

.gallery-meta span {
    background: rgba(0, 0, 0, 0.65);
    color: #fff;
    padding: 4px 7px;
    border-radius: 999px;
    font-size: 0.75rem;
}

#lightbox {
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, .85);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    padding: 20px;
}

#lightbox img {
    max-width: 90%;
    max-height: 90%;
    border-radius: 12px;
}

.btn-secondary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 42px;
    padding: 0.65rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    background: #e5e5e5;
    color: #222;
}

.btn-secondary:hover {
    background: #d4d4d4;
}

@media (max-width: 700px) {
    .upload-row,
    .gallery-filter-form {
        align-items: stretch;
        flex-direction: column;
    }

    .form-group input,
    .gallery-filter-form select,
    .gallery-filter-form button,
    .gallery-filter-form a,
    .upload-row button {
        width: 100%;
    }
}
</style>

<?php require __DIR__ . "/../../includes/templates/footer.php"; ?>
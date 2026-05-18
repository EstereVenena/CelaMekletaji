<?php
session_start();

$lapa  = "Admin Panelis";
$title = "Admin Panelis";

require_once "../assets/database.php";

// Tikai admin lietotājam
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? "") !== "admin") {
    header("Location: ../auth/login.php");
    exit();
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
    GROUP BY c.id, c.name, c.address
    ORDER BY c.address
";
$result = $savienojums->query($clubsSql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clubs[] = $row;
    }
}

/* ===============================
   GALERIJA
================================ */
$gallery = [];
$gallerySql = "
    SELECT id, filename, path, year, creator, category, upload_date
    FROM cm_gallery_images
    ORDER BY upload_date DESC
    LIMIT 10
";
$result = $savienojums->query($gallerySql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $gallery[] = $row;
    }
}

require "header.php";
?>

<!-- ===============================
     KLUBI
================================ -->
<section class="section section-alt">
    <div class="container">
        <header class="section-title">
            <h2>Klubi</h2>
            <p class="muted">Pārvaldi klubus: rediģē, dzēs vai pievieno jaunu.</p>
        </header>

        <div class="cards club-cards">
            <?php if (!empty($clubs)): ?>
                <?php foreach ($clubs as $club): ?>
                    <article class="card club-card">

                        <h3><?= htmlspecialchars($club['name']) ?></h3>

                        <span class="badge badge-gold">
                            <?= htmlspecialchars($club['programs'] ?? 'Nav programmas') ?>
                        </span>

                        <p class="muted">
                            <i class="fas fa-location-dot"></i>
                            <?= htmlspecialchars($club['address']) ?>
                        </p>

                        <div class="news-actions">
                            <a href="edit_club.php?id=<?= (int)$club['id'] ?>" 
                               class="btn btn-outline btn-sm">
                               Rediģēt
                            </a>

                            <a href="delete_club.php?id=<?= (int)$club['id'] ?>" 
                               class="btn btn-red btn-sm"
                               onclick="return confirm('Vai tiešām dzēst šo klubu?')">
                               Dzēst
                            </a>
                        </div>

                    </article>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="card">
                    <p class="muted">Nav pievienotu klubu.</p>
                </div>
            <?php endif; ?>
        </div>

        <div style="margin-top:1rem;">
            <a href="add_club.php" class="btn btn-primary">
                Pievienot klubu
            </a>
        </div>
    </div>
</section>

<!-- ===============================
     GALERIJA
================================ -->
<section class="section">
    <div class="container">
        <header class="section-title">
            <h2>Galerija</h2>
            <p class="muted">
                Augšupielādē jaunus attēlus vai dzēs esošos.
            </p>
        </header>

        <form action="upload_gallery.php" 
              method="post" 
              enctype="multipart/form-data" 
              style="margin-bottom: 2rem;">

            <div style="display:flex; gap:1rem; align-items:end; flex-wrap:wrap;">

                <div>
                    <label>Izvēlies attēlus:</label>
                    <input type="file" name="images[]" accept="image/*" multiple required>
                </div>

                <div>
                    <label>Gads:</label>
                    <input type="number" name="year" placeholder="2026" required>
                </div>

                <div>
                    <label>Autors:</label>
                    <input type="text" name="creator" placeholder="Autora vārds" required>
                </div>

                <div>
                    <label>Kategorija:</label>
                    <input type="text" name="category" placeholder="Kategorija">
                </div>

                <button type="submit" class="btn btn-primary">
                    Augšupielādēt
                </button>

            </div>
        </form>

        <?php if (!empty($gallery)): ?>
            <div class="cards">
                <?php foreach ($gallery as $image): ?>
                    <article class="card">
                        <img 
                            src="<?= htmlspecialchars($image['path']) ?>" 
                            alt="<?= htmlspecialchars($image['filename']) ?>"
                            style="width:100%; max-height:220px; object-fit:cover; border-radius:12px;"
                        >

                        <h3><?= htmlspecialchars($image['filename']) ?></h3>

                        <p class="muted">
                            <?= htmlspecialchars($image['year']) ?> |
                            <?= htmlspecialchars($image['creator']) ?> |
                            <?= htmlspecialchars($image['category'] ?? '') ?>
                        </p>

                        <a href="delete_gallery.php?id=<?= (int)$image['id'] ?>"
                           class="btn btn-red btn-sm"
                           onclick="return confirm('Vai tiešām dzēst šo attēlu?')">
                           Dzēst
                        </a>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <p class="muted">Galerijā vēl nav attēlu.</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require "../assets/footer.php"; ?>
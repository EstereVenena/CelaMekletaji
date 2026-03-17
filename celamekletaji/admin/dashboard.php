<?php
$lapa  = "Admin Panelis";
$title = "Admin Panelis";

require "header.php";
require_once "../assets/database.php";

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
    GROUP BY c.id
    ORDER BY c.address
";
$result = $savienojums->query($clubsSql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clubs[] = $row;
    }
}

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<header class="prof-header">
    <div class="header-container">

        <!-- Brand block: clickable to admin home -->
        <a href="index.php" class="brand-block" aria-label="Uz admin sākumu">
            <div class="brand-logo">
                <img src="../images/logo.png" class="logo" alt="Ceļa meklētāji">
            </div>
            <div class="brand-meta">
                <div class="brand-name">Admin</div>
                <div class="brand-sub">Ceļa meklētāji</div>
            </div>
        </a>

        <!-- Page title -->
        <h1 class="header-title"><?php echo $lapa; ?></h1>

        <nav class="main-nav" id="mainNav" aria-label="Admin navigācija">
            <a href="index.php" class="nav-link">Sākums</a>
            <a href="news_manage.php" class="nav-link">Jaunumi</a>
            <a href="news.php">Aktualitātes</a>
            <a href="clubs_manage.php" class="nav-link">Klubi</a>
            <a href="gallery.php" class="nav-link">Galerija</a>
            <a href="users_manage.php" class="nav-link">Lietotāji</a>
            <a href="../index.php" class="nav-link nav-cta" aria-label="Iziet">
                <i class="fas fa-sign-out-alt"></i>
                <span class="nav-cta-text">Iziet</span>
            </a>
        </nav>

        <button id="menu-btn"
                class="menu-btn"
                type="button"
                aria-label="Atvērt izvēlni"
                aria-controls="mainNav"
                aria-expanded="false">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
    </div>
</header>

<!-- Backdrop for mobile menu -->
<div class="nav-backdrop" id="navBackdrop" hidden></div>

<script>
(function () {
    const btn = document.getElementById('menu-btn');
    const nav = document.getElementById('mainNav');
    const backdrop = document.getElementById('navBackdrop');

    if (!btn || !nav) return;

    function openMenu() {
        nav.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        btn.setAttribute('aria-label', 'Aizvērt izvēlni');
        btn.innerHTML = '<i class="fas fa-xmark" aria-hidden="true"></i>';
        if (backdrop) { backdrop.hidden = false; backdrop.classList.add('show'); }
        document.body.classList.add('nav-lock');
    }

    function closeMenu() {
        nav.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-label', 'Atvērt izvēlni');
        btn.innerHTML = '<i class="fas fa-bars" aria-hidden="true"></i>';
        if (backdrop) { backdrop.classList.remove('show'); backdrop.hidden = true; }
        document.body.classList.remove('nav-lock');
    }

    btn.addEventListener('click', () => {
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        expanded ? closeMenu() : openMenu();
    });

    if (backdrop) backdrop.addEventListener('click', closeMenu);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('is-open')) closeMenu();
    });

    nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 768px)').matches) closeMenu();
    }));

    window.addEventListener('resize', () => {
        if (!window.matchMedia('(max-width: 768px)').matches) closeMenu();
    });
})();
</script>


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
                        <a href="edit_club.php?id=<?= $club['id'] ?>" 
                           class="btn btn-outline btn-sm">
                           Rediģēt
                        </a>

                        <a href="delete_club.php?id=<?= $club['id'] ?>" 
                           class="btn btn-red btn-sm"
                           onclick="return confirm('Vai tiešām dzēst šo klubu?')">
                           Dzēst
                        </a>
                    </div>

                </article>
            <?php endforeach; ?>
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
                    <input type="number" name="year" placeholder="2024" required>
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
    </div>
</section>

<?php require "../assets/footer.php";?>
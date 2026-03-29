<?php
$lapa  = "Admin Panelis";
$title = "Admin Panelis";

require "../includes/templates/header.php";
require_once "../includes/config/database.php";

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

<!--- <header class="prof-header">
    <div class="header-container">

        <a href="index.php" class="brand-block">
            <div class="brand-logo">
                <img src="../images/logo.png" class="logo" alt="Ceļa meklētāji">
            </div>
            <div class="brand-meta">
                <div class="brand-name">Admin</div>
                <div class="brand-sub">Ceļa meklētāji</div>
            </div>
        </a>

        <h1 class="header-title"><?php /* echo $lapa; */ ?></h1>

        <nav class="main-nav" id="mainNav">
            <a href="index.php">Sākums</a>
            <a href="news_manage.php">Jaunumi</a>
            <a href="news.php">Aktualitātes</a>
            <a href="clubs_manage.php">Klubi</a>
            <a href="gallery.php">Galerija</a>
            <a href="users_manage.php">Lietotāji</a>
            <a href="../index.php">Iziet</a>
        </nav>

        <button id="menu-btn"><i class="fas fa-bars"></i></button>
    </div>
</header> --->

<!-- KLUBI -->
<section class="section section-alt">
    <div class="container">
        <h2>Klubi</h2>

        <div class="cards">
            <?php foreach ($clubs as $club): ?>
                <div class="card">
                    <h3><?= htmlspecialchars($club['name']) ?></h3>
                    <p><?= htmlspecialchars($club['programs'] ?? 'Nav programmas') ?></p>
                    <p><?= htmlspecialchars($club['address']) ?></p>

                    <a href="edit_club.php?id=<?= $club['id'] ?>">Rediģēt</a>
                    <a href="delete_club.php?id=<?= $club['id'] ?>" onclick="return confirm('Dzēst?')">Dzēst</a>
                </div>
            <?php endforeach; ?>
        </div>

        <a href="add_club.php">+ Pievienot klubu</a>
    </div>
</section>

<!-- GALERIJA -->
<section class="section">
    <div class="container">
        <h2>Galerija</h2>

        <form action="upload_gallery.php" method="post" enctype="multipart/form-data">
            <input type="file" name="images[]" multiple required>
            <input type="number" name="year" placeholder="Gads" required>
            <input type="text" name="creator" placeholder="Autors" required>
            <input type="text" name="category" placeholder="Kategorija">
            <button type="submit">Augšupielādēt</button>
        </form>
    </div>
</section>

<?php require "../assets/footer.php"; ?>

</body>
</html>

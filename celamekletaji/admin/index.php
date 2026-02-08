<?php
$lapa  = "Admin Panelis";
$title = "Admin Panelis";

require "header.php";
require_once "../assets/database.php";

/* ===============================
   AKTUALITĀTES
================================ */
$news = [];
$newsSql = "
    SELECT id, title, description, category, publish_date
    FROM cm_news
    ORDER BY publish_date DESC
    LIMIT 10
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
    SELECT id, name, address, programm
    FROM cm_clubs
    ORDER BY address
";
$result = $savienojums->query($clubsSql);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clubs[] = $row;
    }
}
?>

<section class="section">
    <div class="container">
        <header class="section-title">
            <h2>Jaunākās aktualitātes</h2>
            <p class="muted">Pārvaldi ziņas: rediģē vai dzēs.</p>
        </header>

        <div class="cards">
            <?php foreach ($news as $item): ?>
                <article class="card news-card">
                    <div class="news-meta">
                        <span class="news-tag"><?= htmlspecialchars($item['category']) ?></span>
                        <span class="news-date"><?= htmlspecialchars($item['publish_date']) ?></span>
                    </div>
                    <h3><?= htmlspecialchars($item['title']) ?></h3>
                    <p class="muted"><?= htmlspecialchars($item['description']) ?></p>
                    <div class="news-actions">
                        <a href="admin/edit_news.php?id=<?= $item['id'] ?>" class="btn btn-outline btn-sm">Rediģēt</a>
                        <a href="admin/delete_news.php?id=<?= $item['id'] ?>" class="btn btn-red btn-sm">Dzēst</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:1rem;">
            <a href="admin/add_news.php" class="btn btn-primary">Pievienot jaunu</a>
        </div>
    </div>
</section>

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
                    <span class="badge badge-gold"><?= htmlspecialchars($club['programm']) ?></span>
                    <p class="muted"><i class="fas fa-location-dot"></i> <?= htmlspecialchars($club['address']) ?></p>
                    <div class="news-actions">
                        <a href="admin/edit_club.php?id=<?= $club['id'] ?>" class="btn btn-outline btn-sm">Rediģēt</a>
                        <a href="admin/delete_club.php?id=<?= $club['id'] ?>" class="btn btn-red btn-sm">Dzēst</a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <div style="margin-top:1rem;">
            <a href="admin/add_club.php" class="btn btn-primary">Pievienot klubu</a>
        </div>
    </div>
</section>

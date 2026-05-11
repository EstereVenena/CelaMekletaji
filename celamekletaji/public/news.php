<?php
$lapa  = "Aktualitātes";
$title = "Aktualitātes - Ceļa meklētāji";

require __DIR__ . "/../includes/templates/header.php";
require_once __DIR__ . "/../includes/config/database.php";

$news = [];

$newsSql = "
    SELECT id, title, description, category, publish_date
    FROM cm_news
    WHERE is_active = 1
      AND (start_date IS NULL OR start_date <= CURDATE())
      AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY publish_date DESC
";

$stmt = $savienojums->prepare($newsSql);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $news[] = $row;
}

$stmt->close();
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <div class="page-hero-content">
            <h1>Aktualitātes</h1>
            <p class="lead">Visi jaunumi, notikumi, nometnes un svarīgākā informācija vienuviet.</p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">
        <?php if (!empty($news)): ?>
            <div class="news-grid-page">
                <?php foreach ($news as $item): ?>
                    <article class="card news-card-page">
                        <div class="news-meta">
                            <span class="news-tag">
                                <?= htmlspecialchars($item['category']) ?>
                            </span>
                            <span class="news-date">
                                <?= htmlspecialchars(date('d.m.Y', strtotime($item['publish_date']))) ?>
                            </span>
                        </div>

                        <h2 class="news-card-title-page">
                            <?= htmlspecialchars($item['title']) ?>
                        </h2>

                        <p class="muted">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($item['description'], 0, 240, '...'))) ?>
                        </p>

                        <div class="news-actions">
                            <a href="news_single.php?id=<?= (int)$item['id'] ?>" class="btn btn-primary btn-sm">
                                Lasīt vairāk
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="card">
                <p class="muted">Šobrīd nav pieejamu aktualitāšu.</p>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
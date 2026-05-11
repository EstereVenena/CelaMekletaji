<?php
require_once __DIR__ . "/../includes/config/database.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id <= 0) {
    header("Location: news.php");
    exit();
}

$newsItem = null;

$sql = "
    SELECT id, title, description, category, publish_date
    FROM cm_news
    WHERE id = ?
      AND is_active = 1
      AND (start_date IS NULL OR start_date <= CURDATE())
      AND (end_date IS NULL OR end_date >= CURDATE())
    LIMIT 1
";

$stmt = $savienojums->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows > 0) {
    $newsItem = $result->fetch_assoc();
}

$stmt->close();

if (!$newsItem) {
    $lapa  = "Aktualitāte nav atrasta";
    $title = "Aktualitāte nav atrasta - Ceļa meklētāji";

    require __DIR__ . "/../includes/templates/header.php";
    ?>
    <section class="section">
        <div class="container">
            <div class="card card-wide" style="text-align:center;">
                <h1>Aktualitāte nav atrasta</h1>
                <p class="muted">Iespējams, šī aktualitāte vairs nav pieejama vai arī saite nav pareiza.</p>
                <div style="margin-top: 1rem;">
                    <a href="news.php" class="btn btn-primary">Atpakaļ uz aktualitātēm</a>
                </div>
            </div>
        </div>
    </section>
    <?php
    require __DIR__ . "/../includes/templates/footer.php";
    exit();
}

$lapa  = $newsItem['title'];
$title = htmlspecialchars($newsItem['title']) . " - Ceļa meklētāji";

require __DIR__ . "/../includes/templates/header.php";
?>

<section class="section">
    <div class="container">
        <article class="card card-wide news-single-card">
            <div class="news-meta">
                <span class="news-tag">
                    <?= htmlspecialchars($newsItem['category']) ?>
                </span>
                <span class="news-date">
                    <?= htmlspecialchars(date('d.m.Y', strtotime($newsItem['publish_date']))) ?>
                </span>
            </div>

            <h1 class="news-single-title">
                <?= htmlspecialchars($newsItem['title']) ?>
            </h1>

            <div class="divider"></div>

            <div class="news-single-content">
                <?= nl2br(htmlspecialchars($newsItem['description'])) ?>
            </div>

            <div style="margin-top: 2rem;">
                <a href="news.php" class="btn btn-outline">
                    ← Atpakaļ uz visām aktualitātēm
                </a>
            </div>
        </article>
    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
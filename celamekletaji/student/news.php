<?php
session_start();

$lapa  = "Jaunumi";
$title = "Jaunumi - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   PIEKĻUVES PĀRBAUDE
================================ */
$allowedRoles = ["Skolēns", "Ceļameklētājs", "Bērns", "student", "child"];

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), $allowedRoles, true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$news = [];
$error = null;

function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

/* ===============================
   JAUNUMU SARAKSTS
================================ */
$sql = "
    SELECT
        id,
        title,
        description,
        category,
        publish_date
    FROM cm_news
    WHERE is_active = 1
    ORDER BY publish_date DESC
";

if ($result = $savienojums->query($sql)) {
    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
} else {
    $error = "Neizdevās ielādēt jaunumus.";
}

require __DIR__ . "/../includes/templates/header-student.php";
?>

<section class="page-hero page-hero-sm">
    <div class="container">
        <div class="page-hero-content">
            <h1>Jaunumi</h1>
            <p class="lead">
                Aktuālā informācija, paziņojumi un jaunākie ieraksti.
            </p>
        </div>
    </div>
</section>

<section class="section">
    <div class="container">

        <?php if (!empty($error)): ?>
            <div class="card" style="margin-bottom:1rem; border-left:4px solid #c0392b;">
                <p class="muted"><?= htmlspecialchars($error) ?></p>
            </div>
        <?php endif; ?>

        <?php if (!empty($news)): ?>
            <div class="lessons-grid">

                <?php foreach ($news as $item): ?>
                    <?php
                        $description = trim($item["description"] ?? "");
                        if ($description === "") {
                            $description = "Apraksts nav pievienots.";
                        }
                    ?>

                    <article class="card news-card-page">
                        <div class="news-meta">
                            <span class="news-tag">
                                <?= htmlspecialchars($item["category"] ?? "Jaunumi") ?>
                            </span>

                            <span class="news-date">
                                <?= htmlspecialchars(formatDateLv($item["publish_date"] ?? null)) ?>
                            </span>
                        </div>

                        <h2 class="news-card-title-page">
                            <?= htmlspecialchars($item["title"] ?? "Bez nosaukuma") ?>
                        </h2>

                        <p class="muted">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($description, 0, 260, "..."))) ?>
                        </p>
                    </article>

                <?php endforeach; ?>

            </div>
        <?php else: ?>
            <div class="card">
                <p class="muted">Pašlaik nav pieejamu jaunumu.</p>
            </div>
        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>

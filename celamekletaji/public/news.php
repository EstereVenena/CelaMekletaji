<?php
$lapa  = "Aktualitātes";
$title = "Aktualitātes | Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

$news = [];
$error = null;

$newsSql = "
    SELECT 
        id, 
        title, 
        description, 
        category, 
        publish_date
    FROM cm_news
    WHERE is_active = 1
      AND (start_date IS NULL OR start_date <= CURDATE())
      AND (end_date IS NULL OR end_date >= CURDATE())
    ORDER BY publish_date DESC
";

$stmt = $savienojums->prepare($newsSql);

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }

    $stmt->close();
} else {
    $error = "Neizdevās ielādēt aktualitātes.";
}

require __DIR__ . "/../includes/templates/header.php";
?>

<style>
/* ===============================
   AKTUALITĀTES PAGE
================================ */

.aktualitates-hero {
    position: relative;
    overflow: hidden;
    padding: 5.5rem 0 4.5rem;
    background:
        radial-gradient(circle at top left, rgba(244, 197, 66, 0.26), transparent 35%),
        radial-gradient(circle at bottom right, rgba(45, 106, 79, 0.45), transparent 40%),
        linear-gradient(135deg, #10241b 0%, #173626 58%, #224e38 100%);
    color: #ffffff;
}

.aktualitates-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,0.045) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,0.045) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: 0.45;
}

.aktualitates-hero-inner {
    position: relative;
    z-index: 1;
    display: grid;
    grid-template-columns: 1.15fr 0.85fr;
    gap: 3rem;
    align-items: center;
}

.aktualitates-kicker {
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

.aktualitates-hero h1 {
    margin: 0;
    color: #fff;
    font-size: clamp(2.6rem, 6vw, 5rem);
    line-height: 0.95;
    letter-spacing: -0.055em;
}

.aktualitates-hero .lead {
    max-width: 680px;
    margin: 1.4rem 0 0;
    color: rgba(255,255,255,0.87);
    font-size: 1.16rem;
    line-height: 1.8;
}

.aktualitates-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    margin-top: 2rem;
}

.aktualitates-hero-card {
    padding: 2rem;
    border-radius: 2rem;
    background: rgba(255,255,255,0.9);
    color: #173626;
    border: 1px solid rgba(255,255,255,0.5);
    box-shadow: 0 28px 70px rgba(0,0,0,0.22);
    backdrop-filter: blur(14px);
}

.aktualitates-hero-card-icon {
    width: 62px;
    height: 62px;
    display: grid;
    place-items: center;
    margin-bottom: 1rem;
    border-radius: 1.2rem;
    background: #173626;
    color: #f4c542;
    font-size: 1.55rem;
}

.aktualitates-hero-card h3 {
    margin: 0 0 0.8rem;
    font-size: 1.55rem;
    letter-spacing: -0.03em;
}

.aktualitates-hero-card p {
    margin: 0;
    color: #526358;
    line-height: 1.75;
}

.aktualitates-section {
    padding: 3.2rem 0 4rem;
    background:
        radial-gradient(circle at top right, rgba(244, 197, 66, 0.13), transparent 30%),
        linear-gradient(180deg, #fbfaf5 0%, #ffffff 100%);
}

.aktualitates-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
    margin-bottom: 2rem;
}

.aktualitates-top h2 {
    margin: 0;
    color: #173626;
    font-size: clamp(1.7rem, 4vw, 2.4rem);
    letter-spacing: -0.04em;
}

.aktualitates-top p {
    margin: 0.55rem 0 0;
    max-width: 700px;
    color: #667085;
    line-height: 1.7;
}

.aktualitates-count {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .75rem 1rem;
    border-radius: 999px;
    background: #f8f4e7;
    border: 1px solid rgba(244,197,66,0.35);
    color: #8a650b;
    font-weight: 900;
    white-space: nowrap;
}

.aktualitates-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 1.25rem;
}

.aktualitate-card {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 285px;
    padding: 1.5rem;
    border: 1px solid rgba(23,54,38,0.08);
    border-radius: 1.8rem;
    background:
        linear-gradient(135deg, rgba(255,255,255,0.98), rgba(255,250,235,0.96));
    box-shadow: 0 18px 50px rgba(0,0,0,0.08);
    transition: 0.25s ease;
}

.aktualitate-card:hover {
    transform: translateY(-7px);
    box-shadow: 0 28px 70px rgba(0,0,0,0.13);
}

.aktualitate-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(90deg, #173626, #f4c542);
}

.aktualitate-card::after {
    content: "";
    position: absolute;
    right: -55px;
    bottom: -55px;
    width: 150px;
    height: 150px;
    border-radius: 50%;
    background: rgba(244, 197, 66, 0.16);
    transition: 0.25s ease;
}

.aktualitate-card:hover::after {
    transform: scale(1.18);
}

.aktualitate-meta {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: .9rem;
    margin-bottom: 1.15rem;
}

.aktualitate-tag {
    display: inline-flex;
    align-items: center;
    gap: .4rem;
    max-width: 70%;
    padding: 0.42rem 0.75rem;
    border-radius: 999px;
    background: rgba(244,197,66,0.18);
    border: 1px solid rgba(244,197,66,0.38);
    color: #8a650b;
    font-size: 0.82rem;
    font-weight: 900;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.aktualitate-date {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    color: #7b887e;
    font-size: .86rem;
    font-weight: 850;
    white-space: nowrap;
}

.aktualitate-title {
    position: relative;
    z-index: 1;
    margin: 0 0 0.8rem;
    color: #173626;
    font-size: 1.35rem;
    line-height: 1.25;
    letter-spacing: -0.03em;
}

.aktualitate-text {
    position: relative;
    z-index: 1;
    margin: 0;
    color: #526358;
    line-height: 1.75;
}

.aktualitate-actions {
    position: relative;
    z-index: 1;
    display: flex;
    justify-content: flex-start;
    margin-top: auto;
    padding-top: 1.4rem;
}

.aktualitate-link {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    color: #173626;
    font-weight: 900;
    text-decoration: none;
    transition: 0.2s ease;
}

.aktualitate-card:hover .aktualitate-link {
    color: #d6a823;
}

.aktualitates-alert {
    display: flex;
    gap: .75rem;
    align-items: flex-start;
    padding: 1rem 1.1rem;
    margin-bottom: 1.2rem;
    border-radius: 1.2rem;
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
    font-weight: 800;
}

.aktualitates-empty {
    padding: 2.2rem;
    border-radius: 1.8rem;
    background: #ffffff;
    border: 1px dashed rgba(23,54,38,0.18);
    box-shadow: 0 16px 45px rgba(0,0,0,0.07);
    text-align: center;
}

.aktualitates-empty-icon {
    width: 66px;
    height: 66px;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    border-radius: 1.2rem;
    background: #f8f4e7;
    color: #d6a823;
    font-size: 1.7rem;
}

.aktualitates-empty h3 {
    margin: 0 0 .45rem;
    color: #173626;
    font-size: 1.35rem;
}

.aktualitates-empty p {
    margin: 0;
    color: #667085;
}

@media (max-width: 980px) {
    .aktualitates-hero-inner {
        grid-template-columns: 1fr;
    }

    .aktualitates-grid {
        grid-template-columns: repeat(2, 1fr);
    }

    .aktualitates-top {
        flex-direction: column;
        align-items: flex-start;
    }
}

@media (max-width: 640px) {
    .aktualitates-hero {
        padding: 3.6rem 0 3rem;
    }

    .aktualitates-actions {
        flex-direction: column;
    }

    .aktualitates-actions .btn {
        width: 100%;
        justify-content: center;
    }

    .aktualitates-grid {
        grid-template-columns: 1fr;
    }

    .aktualitates-count {
        width: 100%;
        justify-content: center;
    }

    .aktualitate-card {
        border-radius: 1.4rem;
    }

    .aktualitate-meta {
        flex-direction: column;
    }

    .aktualitate-tag {
        max-width: 100%;
    }
}
</style>

<section class="aktualitates-hero">
    <div class="container">
        <div class="aktualitates-hero-inner">
            <div>
                <div class="aktualitates-kicker">
                    <i class="fa-solid fa-newspaper"></i>
                    Jaunumi un notikumi
                </div>

                <h1>Aktualitātes</h1>

                <p class="lead">
                    Visi jaunumi, notikumi, nometnes un svarīgākā informācija vienuviet.
                    Šeit atradīsi aktuālo informāciju par Ceļa meklētāju dzīvi, pasākumiem un iespējām iesaistīties.
                </p>

                <div class="aktualitates-actions">
                    <a class="btn btn-primary" href="#jaunumi">
                        Skatīt jaunākos
                        <i class="fa-solid fa-arrow-right"></i>
                    </a>

                    <a class="btn btn-outline" href="<?= BASE_URL ?>public/klubi.php">
                        Apskatīt klubus
                    </a>
                </div>
            </div>

            <aside class="aktualitates-hero-card">
                <div class="aktualitates-hero-card-icon">
                    <i class="fa-solid fa-bullhorn"></i>
                </div>

                <h3>Kas jauns?</h3>

                <p>
                    Šeit tiek publicēti svarīgākie paziņojumi, nometņu informācija,
                    pasākumu jaunumi un citas aktualitātes vecākiem, bērniem un vadītājiem.
                </p>
            </aside>
        </div>
    </div>
</section>

<section id="jaunumi" class="aktualitates-section">
    <div class="container">

        <div class="aktualitates-top">
            <div>
                <h2>Jaunākās aktualitātes</h2>
                <p>
                    Publicētie jaunumi, kuri pašlaik ir aktīvi un pieejami apskatei.
                </p>
            </div>

            <div class="aktualitates-count">
                <i class="fa-solid fa-layer-group"></i>
                <?= count($news); ?> ieraksti
            </div>
        </div>

        <?php if (!empty($error)): ?>
            <div class="aktualitates-alert">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($news)): ?>

            <div class="aktualitates-grid">

                <?php foreach ($news as $item): ?>
                    <?php
                        $category = trim($item['category'] ?? '');
                        $category = $category !== '' ? $category : 'Aktualitāte';

                        $description = trim($item['description'] ?? '');
                        $description = $description !== '' ? $description : 'Apraksts nav pievienots.';

                        $publishDate = !empty($item['publish_date'])
                            ? date('d.m.Y', strtotime($item['publish_date']))
                            : '—';
                    ?>

                    <article class="aktualitate-card">

                        <div class="aktualitate-meta">
                            <span class="aktualitate-tag">
                                <i class="fa-solid fa-tag"></i>
                                <?= htmlspecialchars($category); ?>
                            </span>

                            <span class="aktualitate-date">
                                <i class="fa-solid fa-calendar-day"></i>
                                <?= htmlspecialchars($publishDate); ?>
                            </span>
                        </div>

                        <h2 class="aktualitate-title">
                            <?= htmlspecialchars($item['title'] ?? 'Bez nosaukuma'); ?>
                        </h2>

                        <p class="aktualitate-text">
                            <?= nl2br(htmlspecialchars(mb_strimwidth($description, 0, 240, '...'))); ?>
                        </p>

                        <div class="aktualitate-actions">
                            <a href="news_single.php?id=<?= (int)$item['id']; ?>" class="aktualitate-link">
                                Lasīt vairāk
                                <i class="fa-solid fa-arrow-right"></i>
                            </a>
                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php else: ?>

            <div class="aktualitates-empty">
                <div class="aktualitates-empty-icon">
                    <i class="fa-solid fa-inbox"></i>
                </div>

                <h3>Šobrīd nav pieejamu aktualitāšu</h3>
                <p>Kad tiks pievienoti jauni ieraksti, tie parādīsies šeit.</p>
            </div>

        <?php endif; ?>

    </div>
</section>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>0999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999999klllllllllllllllllllllllll,m
<?php
session_start();

$lapa  = "Ceļameklētāja panelis";
$title = "Ceļameklētāja panelis - Ceļa meklētāji";

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

$studentId = (int) ($_SESSION["lietotajs_id"] ?? 0);
$student = null;
$news = [];
$error = null;

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function formatDateLv(?string $date): string
{
    if (empty($date)) {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

/* ===============================
   SKOLĒNA / CEĻAMEKLĒTĀJA DATI
================================ */
$sqlStudent = "
    SELECT 
        l.lietotajs_id,
        l.lietotajvards,
        l.vards,
        l.uzvards,
        l.loma,
        l.statuss,
        c.name AS club_name
    FROM cm_lietotaji l
    LEFT JOIN cm_clubs c ON l.club_id = c.id
    WHERE l.lietotajs_id = ?
    LIMIT 1
";

if ($stmt = $savienojums->prepare($sqlStudent)) {
    $stmt->bind_param("i", $studentId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $student = $result->fetch_assoc();
        } else {
            $error = "Lietotāja dati netika atrasti.";
        }
    } else {
        $error = "Neizdevās ielādēt lietotāja datus.";
    }

    $stmt->close();
} else {
    $error = "Neizdevās sagatavot lietotāja vaicājumu.";
}

/* ===============================
   JAUNUMI
================================ */
$sqlNews = "
    SELECT 
        id,
        title,
        description,
        category,
        publish_date
    FROM cm_news
    WHERE is_active = 1
    ORDER BY publish_date DESC
    LIMIT 3
";

if ($newsResult = $savienojums->query($sqlNews)) {
    while ($row = $newsResult->fetch_assoc()) {
        $news[] = $row;
    }
}

/* ===============================
   ATTĒLOŠANAS DATI
================================ */
$displayName = $student["vards"]
    ?? ($_SESSION["lietotajvards"] ?? "Ceļameklētāj");

$clubName = $student["club_name"] ?? "Nav piešķirts";

require __DIR__ . "/../includes/templates/header-student.php";
?>

<style>
    .student-dashboard {
        padding: 2.5rem 0;
        min-height: 80vh;
        background:
            radial-gradient(circle at top left, rgba(202, 162, 89, .18), transparent 32rem),
            radial-gradient(circle at bottom right, rgba(59, 91, 152, .14), transparent 28rem);
    }

    .student-hero {
        display: block;
        margin-bottom: 1.5rem;
    }

    .hero-card,
    .dashboard-panel {
        background: rgba(255, 255, 255, .94);
        border: 1px solid rgba(0, 0, 0, .06);
        border-radius: 24px;
        box-shadow: 0 16px 40px rgba(0, 0, 0, .08);
    }

    .hero-card {
        padding: 2rem;
        position: relative;
        overflow: hidden;
    }

    .hero-card::after {
        content: "";
        position: absolute;
        width: 190px;
        height: 190px;
        right: -70px;
        top: -70px;
        background: rgba(202, 162, 89, .18);
        border-radius: 50%;
        pointer-events: none;
    }

    .hero-label {
        display: inline-flex;
        align-items: center;
        gap: .4rem;
        padding: .35rem .8rem;
        border-radius: 999px;
        background: rgba(202, 162, 89, .16);
        color: #7a5517;
        font-size: .85rem;
        font-weight: 700;
        margin-bottom: .85rem;
    }

    .hero-card h2 {
        margin: 0 0 .6rem;
        font-size: clamp(1.8rem, 4vw, 3rem);
        line-height: 1.05;
        position: relative;
        z-index: 1;
    }

    .hero-card .lead {
        max-width: 720px;
        margin-bottom: 1rem;
        position: relative;
        z-index: 1;
    }

    .club-info {
        display: inline-flex;
        align-items: center;
        gap: .45rem;
        padding: .6rem .95rem;
        margin: .25rem 0 1.25rem;
        border-radius: 999px;
        background: rgba(59, 91, 152, .10);
        color: #2f4f8f;
        font-size: .95rem;
        position: relative;
        z-index: 1;
    }

    .club-info span {
        color: #666;
    }

    .club-info strong {
        color: #263f73;
    }

    .hero-actions {
        display: flex;
        gap: .75rem;
        flex-wrap: wrap;
        position: relative;
        z-index: 1;
    }

    .quick-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.5rem;
    }

    .action-card {
        padding: 1.5rem;
        border-radius: 22px;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .06);
        box-shadow: 0 12px 28px rgba(0, 0, 0, .06);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .action-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 16px 36px rgba(0, 0, 0, .1);
    }

    .action-icon {
        width: 46px;
        height: 46px;
        border-radius: 16px;
        display: grid;
        place-items: center;
        background: rgba(202, 162, 89, .16);
        font-size: 1.35rem;
        margin-bottom: .85rem;
    }

    .action-card h3,
    .dashboard-panel h3 {
        margin-top: 0;
    }

    .dashboard-panel {
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .news-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
    }

    .news-card {
        padding: 1.1rem;
        border-radius: 18px;
        background: #fff;
        border: 1px solid rgba(0, 0, 0, .06);
        transition: transform .18s ease, box-shadow .18s ease;
    }

    .news-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, .08);
    }

    .news-card h4 {
        margin-top: 0;
        margin-bottom: .35rem;
    }

    .news-meta {
        font-size: .85rem;
        color: #777;
        margin-bottom: .55rem;
    }

    .empty-message {
        padding: 1rem;
        border-radius: 16px;
        background: rgba(0, 0, 0, .03);
    }

    @media (max-width: 850px) {
        .quick-grid,
        .news-grid {
            grid-template-columns: 1fr;
        }

        .hero-card {
            padding: 1.5rem;
        }

        .hero-actions .btn {
            width: 100%;
            justify-content: center;
            text-align: center;
        }

        .club-info {
            border-radius: 16px;
            align-items: flex-start;
        }
    }
</style>

<main class="student-dashboard">
    <div class="container">

        <!-- GALVENE -->
        <section class="student-hero">
            <div class="hero-card">
                <span class="hero-label">Ceļameklētāja panelis</span>

                <h2>Sveiki, <?php echo htmlspecialchars($displayName); ?>!</h2>

                <p class="lead">
                    Šeit vari ātri apskatīt pieejamās nodarbības, pārbaudīt savus pieteikumus
                    un sekot jaunākajai informācijai.
                </p>

                <div class="club-info">
                    <span>📍 Mans klubs:</span>
                    <strong><?php echo htmlspecialchars($clubName); ?></strong>
                </div>

                <div class="hero-actions">
                    <a class="btn btn-primary" href="../lessons/index.php">
                        Skatīt nodarbības
                    </a>

                    <a class="btn btn-outline" href="../applications/index.php">
                        Mani pieteikumi
                    </a>
                </div>
            </div>
        </section>

        <!-- KĻŪDAS PAZIŅOJUMS -->
        <?php if ($error): ?>
            <div class="dashboard-panel">
                <p class="muted">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            </div>
        <?php endif; ?>

        <!-- ĀTRĀ PIEKĻUVE -->
        <section class="quick-grid">
            <article class="action-card">
                <div class="action-icon">📚</div>

                <h3>Pieejamās nodarbības</h3>

                <p class="muted">
                    Apskati nodarbības un izvēlies tās, kurās vēlies piedalīties.
                </p>

                <a class="btn btn-primary btn-sm" href="../lessons/index.php">
                    Atvērt nodarbības
                </a>
            </article>

            <article class="action-card">
                <div class="action-icon">✅</div>

                <h3>Mani pieteikumi</h3>

                <p class="muted">
                    Pārskati nodarbības vai pasākumus, kuriem jau esi pieteicies.
                </p>

                <a class="btn btn-outline btn-sm" href="../applications/index.php">
                    Skatīt pieteikumus
                </a>
            </article>
        </section>

        <!-- JAUNUMI -->
        <section class="dashboard-panel">
            <div class="section-title-row" style="margin-bottom:1rem;">
                <div>
                    <h3>Jaunākie jaunumi</h3>
                    <p class="muted">Aktuālā informācija no “Ceļa meklētāji”.</p>
                </div>
            </div>

            <?php if (empty($news)): ?>
                <p class="muted empty-message">Pašlaik nav pieejamu jaunumu.</p>
            <?php else: ?>
                <div class="news-grid">
                    <?php foreach ($news as $item): ?>
                        <article class="news-card">
                            <h4>
                                <?php echo htmlspecialchars($item["title"] ?? "Bez nosaukuma"); ?>
                            </h4>

                            <div class="news-meta">
                                <?php echo htmlspecialchars($item["category"] ?? "Jaunumi"); ?>
                                &nbsp;•&nbsp;
                                <?php echo htmlspecialchars(formatDateLv($item["publish_date"] ?? null)); ?>
                            </div>

                            <p class="muted">
                                <?php
                                    $desc = trim($item["description"] ?? "");
                                    echo htmlspecialchars(mb_strimwidth($desc, 0, 130, "..."));
                                ?>
                            </p>

                            <a class="link" href="../news/view.php?id=<?php echo (int) ($item["id"] ?? 0); ?>">
                                Lasīt vairāk
                            </a>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
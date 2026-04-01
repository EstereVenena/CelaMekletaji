<?php
$lapa  = "Admin Panelis";
$title = "Admin Panelis";

require __DIR__ . "/../includes/templates/header-admin.php";
require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   ĀTRĀ STATISTIKA
================================ */
$stats = [
    'users' => 0,
    'news' => 0,
    'events' => 0,
];

// Lietotāji
$userSql = "SELECT COUNT(*) AS total FROM cm_lietotaji";
$result = $savienojums->query($userSql);
if ($result && $row = $result->fetch_assoc()) {
    $stats['users'] = (int)$row['total'];
}

// Jaunumi
$newsSql = "SELECT COUNT(*) AS total FROM cm_news";
$result = $savienojums->query($newsSql);
if ($result && $row = $result->fetch_assoc()) {
    $stats['news'] = (int)$row['total'];
}

// Pasākumi — tikai ja tabula eksistē
$eventsCheck = $savienojums->query("SHOW TABLES LIKE 'cm_events'");
if ($eventsCheck && $eventsCheck->num_rows > 0) {
    $eventsSql = "SELECT COUNT(*) AS total FROM cm_events";
    $result = $savienojums->query($eventsSql);
    if ($result && $row = $result->fetch_assoc()) {
        $stats['events'] = (int)$row['total'];
    }
}
?>

<style>
    .admin-dashboard {
        padding: 2rem 0 3rem;
        background:
            radial-gradient(circle at top right, rgba(30,79,161,0.08), transparent 30%),
            linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
        min-height: calc(100vh - 140px);
    }

    .admin-hero {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1.5rem;
        margin-bottom: 2rem;
        padding: 1.5rem;
        border-radius: 24px;
        background: linear-gradient(135deg, #173f84, #1e4fa1);
        color: #fff;
        box-shadow: 0 20px 50px rgba(23, 63, 132, 0.18);
    }

    .admin-hero h1 {
        margin: 0 0 .4rem;
        font-size: clamp(1.8rem, 3vw, 2.5rem);
        line-height: 1.1;
    }

    .admin-hero p {
        margin: 0;
        opacity: .92;
        max-width: 700px;
    }

    .admin-hero-badge {
        padding: .75rem 1rem;
        border-radius: 999px;
        background: rgba(255,255,255,0.14);
        border: 1px solid rgba(255,255,255,0.18);
        font-weight: 700;
        white-space: nowrap;
    }

    .admin-stats {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 2rem;
    }

    .admin-stat-card {
        background: #fff;
        border: 1px solid #e8eef8;
        border-radius: 20px;
        padding: 1.25rem;
        box-shadow: 0 12px 28px rgba(16, 24, 40, 0.06);
    }

    .admin-stat-top {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: .8rem;
    }

    .admin-stat-icon {
        width: 46px;
        height: 46px;
        border-radius: 14px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: #eef3ff;
        color: #173f84;
        font-size: 1.1rem;
    }

    .admin-stat-label {
        margin: 0;
        color: #667085;
        font-size: .95rem;
    }

    .admin-stat-value {
        margin: 0;
        font-size: 2rem;
        font-weight: 800;
        color: #101828;
    }

    .admin-grid {
        display: grid;
        grid-template-columns: 1.3fr .9fr;
        gap: 1.2rem;
    }

    .admin-panel {
        background: #fff;
        border: 1px solid #e8eef8;
        border-radius: 22px;
        padding: 1.3rem;
        box-shadow: 0 12px 28px rgba(16, 24, 40, 0.06);
    }

    .admin-panel h2 {
        margin: 0 0 1rem;
        font-size: 1.2rem;
        color: #173f84;
    }

    .admin-actions {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: .9rem;
    }

    .admin-action {
        display: flex;
        align-items: flex-start;
        gap: .9rem;
        text-decoration: none;
        background: #f8fbff;
        border: 1px solid #e6eefb;
        border-radius: 18px;
        padding: 1rem;
        transition: .2s ease;
        color: inherit;
    }

    .admin-action:hover {
        transform: translateY(-2px);
        box-shadow: 0 14px 26px rgba(23, 63, 132, 0.10);
        border-color: #cfe0ff;
    }

    .admin-action-icon {
        width: 44px;
        height: 44px;
        border-radius: 14px;
        flex-shrink: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, #173f84, #1e4fa1);
        color: #fff;
        font-size: 1rem;
    }

    .admin-action h3 {
        margin: 0 0 .25rem;
        font-size: 1rem;
        color: #101828;
    }

    .admin-action p {
        margin: 0;
        color: #667085;
        font-size: .93rem;
        line-height: 1.45;
    }

    .admin-list {
        list-style: none;
        margin: 0;
        padding: 0;
        display: grid;
        gap: .8rem;
    }

    .admin-list li {
        display: flex;
        align-items: center;
        gap: .8rem;
        padding: .85rem 1rem;
        border-radius: 16px;
        background: #f8fbff;
        border: 1px solid #edf2fb;
        color: #344054;
    }

    .admin-list i {
        color: #173f84;
        width: 18px;
        text-align: center;
    }

    .admin-note {
        margin-top: 1rem;
        padding: 1rem;
        border-radius: 16px;
        background: #fff8e6;
        border: 1px solid #f5df9f;
        color: #6b5a18;
        font-size: .95rem;
    }

    @media (max-width: 900px) {
        .admin-stats {
            grid-template-columns: 1fr;
        }

        .admin-grid {
            grid-template-columns: 1fr;
        }

        .admin-actions {
            grid-template-columns: 1fr;
        }

        .admin-hero {
            flex-direction: column;
            align-items: flex-start;
        }
    }
</style>

<main class="admin-dashboard">
    <div class="container">

        <section class="admin-hero">
            <div>
                <h1>Sveiks, administrator!</h1>
                <p>
                    Šeit vari ātri pārvaldīt sistēmas saturu, lietotājus un svarīgākās sadaļas.
                </p>
            </div>
            <div class="admin-hero-badge">
                <i class="fas fa-shield-halved"></i> Admin piekļuve
            </div>
        </section>

        <section class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <p class="admin-stat-label">Lietotāji</p>
                        <p class="admin-stat-value"><?= $stats['users'] ?></p>
                    </div>
                    <span class="admin-stat-icon">
                        <i class="fas fa-users"></i>
                    </span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <p class="admin-stat-label">Jaunumi</p>
                        <p class="admin-stat-value"><?= $stats['news'] ?></p>
                    </div>
                    <span class="admin-stat-icon">
                        <i class="fas fa-newspaper"></i>
                    </span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <p class="admin-stat-label">Pasākumi</p>
                        <p class="admin-stat-value"><?= $stats['events'] ?></p>
                    </div>
                    <span class="admin-stat-icon">
                        <i class="fas fa-calendar-days"></i>
                    </span>
                </div>
            </div>
        </section>

        <section class="admin-grid">
            <div class="admin-panel">
                <h2>Ātrās darbības</h2>

                <div class="admin-actions">
                    <a class="admin-action" href="<?= BASE_URL ?>admin/news/news.php">
                        <span class="admin-action-icon"><i class="fas fa-newspaper"></i></span>
                        <div>
                            <h3>Pārvaldīt jaunumus</h3>
                            <p>Skatīt, pievienot un labot aktuālo informāciju.</p>
                        </div>
                    </a>

                    <a class="admin-action" href="<?= BASE_URL ?>admin/users/users_manage.php">
                        <span class="admin-action-icon"><i class="fas fa-users-gear"></i></span>
                        <div>
                            <h3>Pārvaldīt lietotājus</h3>
                            <p>Rediģēt kontus, lomas un piekļuves tiesības.</p>
                        </div>
                    </a>

                    <a class="admin-action" href="<?= BASE_URL ?>index.php">
                        <span class="admin-action-icon"><i class="fas fa-house"></i></span>
                        <div>
                            <h3>Uz sākumlapu</h3>
                            <p>Pārbaudīt publisko lapas izskatu un saturu.</p>
                        </div>
                    </a>

                    <a class="admin-action" href="<?= BASE_URL ?>auth/logout.php">
                        <span class="admin-action-icon"><i class="fas fa-right-from-bracket"></i></span>
                        <div>
                            <h3>Iziet no sistēmas</h3>
                            <p>Droši aizvērt administrēšanas sesiju.</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="admin-panel">
                <h2>Atgādinājumi</h2>
                <ul class="admin-list">
                    <li><i class="fas fa-triangle-exclamation"></i> Pārliecinies, ka regulāri dublē datubāzi.</li>
                    <li><i class="fas fa-triangle-exclamation"></i> Atjaunini paroles un piekļuves tiesības pēc nepieciešamības.</li>
                    <li><i class="fas fa-triangle-exclamation"></i> Pārbaudi sistēmas atjauninājumus un drošības ielāpus.</li>
            </div>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
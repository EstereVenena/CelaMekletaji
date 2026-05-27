<?php
$lapa  = "Admin Panelis";
$title = "Admin Panelis";

require __DIR__ . "/../includes/templates/header-admin.php";
require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   ĀTRĀ STATISTIKA
================================ */
$stats = [
    'users'   => 0,
    'news'    => 0,
    'events'  => 0,
    'clubs'   => 0,
    'gallery' => 0,
];

// Lietotāji
$result = $savienojums->query("SELECT COUNT(*) AS total FROM cm_lietotaji");
if ($result && $row = $result->fetch_assoc()) {
    $stats['users'] = (int)$row['total'];
}

// Jaunumi
$result = $savienojums->query("SELECT COUNT(*) AS total FROM cm_news");
if ($result && $row = $result->fetch_assoc()) {
    $stats['news'] = (int)$row['total'];
}

// Klubi
$clubsCheck = $savienojums->query("SHOW TABLES LIKE 'cm_clubs'");
if ($clubsCheck && $clubsCheck->num_rows > 0) {
    $result = $savienojums->query("SELECT COUNT(*) AS total FROM cm_clubs");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['clubs'] = (int)$row['total'];
    }
}

// Galerija
$galleryCheck = $savienojums->query("SHOW TABLES LIKE 'cm_gallery_images'");
if ($galleryCheck && $galleryCheck->num_rows > 0) {
    $result = $savienojums->query("SELECT COUNT(*) AS total FROM cm_gallery_images");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['gallery'] = (int)$row['total'];
    }
}

// Pasākumi
$eventsCheck = $savienojums->query("SHOW TABLES LIKE 'cm_events'");
if ($eventsCheck && $eventsCheck->num_rows > 0) {
    $result = $savienojums->query("SELECT COUNT(*) AS total FROM cm_events");
    if ($result && $row = $result->fetch_assoc()) {
        $stats['events'] = (int)$row['total'];
    }
}
?>

<style>
    .admin-dashboard {
        padding: 2.5rem 0 3.5rem;
        min-height: calc(100vh - 140px);
        background:
            radial-gradient(circle at top right, rgba(30,79,161,0.10), transparent 30%),
            radial-gradient(circle at bottom left, rgba(244,196,48,0.18), transparent 26%),
            linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    }

    .admin-hero {
        position: relative;
        overflow: hidden;
        display: grid;
        grid-template-columns: 1.2fr .8fr;
        gap: 1.5rem;
        align-items: center;
        margin-bottom: 1.6rem;
        padding: 2rem;
        border-radius: 28px;
        background:
            radial-gradient(circle at top right, rgba(244,196,48,.28), transparent 34%),
            linear-gradient(135deg, #173f84, #1e4fa1);
        color: #fff;
        box-shadow: 0 24px 60px rgba(23, 63, 132, 0.22);
    }

    .admin-hero::before {
        content: "";
        position: absolute;
        inset: 0;
        background-image:
            linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
            linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
        background-size: 42px 42px;
        opacity: .35;
    }

    .admin-hero > * {
        position: relative;
        z-index: 1;
    }

    .admin-hero-kicker {
        display: inline-flex;
        align-items: center;
        gap: .5rem;
        padding: .45rem .8rem;
        margin-bottom: 1rem;
        border-radius: 999px;
        background: rgba(255,255,255,.14);
        color: #f4c430;
        font-weight: 900;
    }

    .admin-hero h1 {
        margin: 0 0 .6rem;
        font-size: clamp(2rem, 4vw, 3rem);
        line-height: 1.05;
        letter-spacing: -0.045em;
        color: #fff;
    }

    .admin-hero p {
        margin: 0;
        opacity: .9;
        max-width: 700px;
        line-height: 1.7;
    }

    .admin-hero-box {
        padding: 1.35rem;
        border-radius: 22px;
        background: rgba(255,255,255,0.14);
        border: 1px solid rgba(255,255,255,0.18);
        backdrop-filter: blur(8px);
    }

    .admin-hero-box strong {
        display: block;
        font-size: 1.2rem;
        margin-bottom: .35rem;
    }

    .admin-hero-box span {
        display: block;
        opacity: .86;
        line-height: 1.55;
    }

    .admin-stats {
        display: grid;
        grid-template-columns: repeat(5, minmax(0, 1fr));
        gap: 1rem;
        margin-bottom: 1.4rem;
    }

    .admin-stat-card {
        position: relative;
        overflow: hidden;
        background: #fff;
        border: 1px solid #e8eef8;
        border-radius: 22px;
        padding: 1.25rem;
        box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
        transition: .2s ease;
    }

    .admin-stat-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 20px 46px rgba(16, 24, 40, 0.10);
    }

    .admin-stat-card::after {
        content: "";
        position: absolute;
        right: -40px;
        bottom: -40px;
        width: 110px;
        height: 110px;
        border-radius: 50%;
        background: rgba(30,79,161,0.06);
    }

    .admin-stat-top {
        position: relative;
        z-index: 1;
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: .8rem;
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
        flex-shrink: 0;
    }

    .admin-stat-label {
        margin: 0;
        color: #667085;
        font-size: .92rem;
        font-weight: 800;
    }

    .admin-stat-value {
        margin: .15rem 0 0;
        font-size: 2rem;
        font-weight: 1000;
        color: #101828;
        line-height: 1;
    }

    .admin-grid {
        display: grid;
        grid-template-columns: 1.35fr .9fr;
        gap: 1.2rem;
    }

    .admin-panel {
        background: #fff;
        border: 1px solid #e8eef8;
        border-radius: 24px;
        padding: 1.4rem;
        box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
    }

    .admin-panel-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1rem;
    }

    .admin-panel h2 {
        margin: 0;
        font-size: 1.25rem;
        color: #173f84;
    }

    .admin-panel-sub {
        margin: .3rem 0 0;
        color: #667085;
        font-size: .95rem;
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
        transform: translateY(-3px);
        box-shadow: 0 16px 32px rgba(23, 63, 132, 0.12);
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
        align-items: flex-start;
        gap: .8rem;
        padding: .9rem 1rem;
        border-radius: 16px;
        background: #f8fbff;
        border: 1px solid #edf2fb;
        color: #344054;
        line-height: 1.5;
    }

    .admin-list i {
        color: #f4a100;
        width: 18px;
        text-align: center;
        margin-top: .2rem;
    }

    .admin-note {
        margin-top: 1rem;
        padding: 1rem;
        border-radius: 16px;
        background: #fff8e6;
        border: 1px solid #f5df9f;
        color: #6b5a18;
        font-size: .95rem;
        line-height: 1.55;
    }

    @media (max-width: 1100px) {
        .admin-stats {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 900px) {
        .admin-hero {
            grid-template-columns: 1fr;
        }

        .admin-stats {
            grid-template-columns: 1fr 1fr;
        }

        .admin-grid {
            grid-template-columns: 1fr;
        }

        .admin-actions {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 560px) {
        .admin-dashboard {
            padding: 1.5rem 0 2.5rem;
        }

        .admin-hero {
            padding: 1.4rem;
            border-radius: 22px;
        }

        .admin-stats {
            grid-template-columns: 1fr;
        }

        .admin-panel {
            padding: 1rem;
            border-radius: 20px;
        }
    }
</style>

<main class="admin-dashboard">
    <div class="container">

        <section class="admin-hero">
            <div>
                <div class="admin-hero-kicker">
                    <i class="fas fa-shield-halved"></i>
                    Admin piekļuve
                </div>

                <h1>Sveiks, administrator!</h1>

                <p>
                    Šeit vari pārvaldīt sistēmas saturu, lietotājus, klubus, galeriju un citas svarīgākās sadaļas.
                    Viss vienuviet — mazāk klikšķu, mazāk ciešanu.
                </p>
            </div>

            <aside class="admin-hero-box">
                <strong>Sistēmas pārvaldība</strong>
                <span>
                    Pārbaudi jaunumus, lietotāju kontus, galerijas attēlus un klubu informāciju.
                </span>
            </aside>
        </section>

        <section class="admin-stats">
            <div class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <p class="admin-stat-label">Lietotāji</p>
                        <p class="admin-stat-value"><?= (int)$stats['users'] ?></p>
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
                        <p class="admin-stat-value"><?= (int)$stats['news'] ?></p>
                    </div>
                    <span class="admin-stat-icon">
                        <i class="fas fa-newspaper"></i>
                    </span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <p class="admin-stat-label">Klubi</p>
                        <p class="admin-stat-value"><?= (int)$stats['clubs'] ?></p>
                    </div>
                    <span class="admin-stat-icon">
                        <i class="fas fa-location-dot"></i>
                    </span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <p class="admin-stat-label">Galerija</p>
                        <p class="admin-stat-value"><?= (int)$stats['gallery'] ?></p>
                    </div>
                    <span class="admin-stat-icon">
                        <i class="fas fa-images"></i>
                    </span>
                </div>
            </div>

            <div class="admin-stat-card">
                <div class="admin-stat-top">
                    <div>
                        <p class="admin-stat-label">Pasākumi</p>
                        <p class="admin-stat-value"><?= (int)$stats['events'] ?></p>
                    </div>
                    <span class="admin-stat-icon">
                        <i class="fas fa-calendar-days"></i>
                    </span>
                </div>
            </div>
        </section>

        <section class="admin-grid">
            <div class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Ātrās darbības</h2>
                        <p class="admin-panel-sub">
                            Biežāk lietotās pārvaldības sadaļas.
                        </p>
                    </div>
                </div>

                <div class="admin-actions">
                    <a class="admin-action" href="<?= BASE_URL ?>admin/news/news.php">
                        <span class="admin-action-icon">
                            <i class="fas fa-newspaper"></i>
                        </span>
                        <div>
                            <h3>Pārvaldīt jaunumus</h3>
                            <p>Skatīt, pievienot, labot un dzēst aktuālo informāciju.</p>
                        </div>
                    </a>

                    <a class="admin-action" href="<?= BASE_URL ?>admin/users/users_manage.php">
                        <span class="admin-action-icon">
                            <i class="fas fa-users-gear"></i>
                        </span>
                        <div>
                            <h3>Pārvaldīt lietotājus</h3>
                            <p>Rediģēt kontus, lomas un piekļuves tiesības.</p>
                        </div>
                    </a>

                    <a class="admin-action" href="<?= BASE_URL ?>admin/clubs/clubs.php">
                        <span class="admin-action-icon">
                            <i class="fas fa-location-dot"></i>
                        </span>
                        <div>
                            <h3>Pārvaldīt klubus</h3>
                            <p>Pievienot vai labot klubu informāciju un programmas.</p>
                        </div>
                    </a>

                    <a class="admin-action" href="<?= BASE_URL ?>admin/gallery/gallery.php">
                        <span class="admin-action-icon">
                            <i class="fas fa-images"></i>
                        </span>
                        <div>
                            <h3>Pārvaldīt galeriju</h3>
                            <p>Augšupielādēt un sakārtot attēlus pa kategorijām.</p>
                        </div>
                    </a>

                    <a class="admin-action" href="<?= BASE_URL ?>index.php">
                        <span class="admin-action-icon">
                            <i class="fas fa-house"></i>
                        </span>
                        <div>
                            <h3>Uz sākumlapu</h3>
                            <p>Pārbaudīt publisko lapas izskatu un saturu.</p>
                        </div>
                    </a>

                    <a class="admin-action" href="<?= BASE_URL ?>auth/logout.php">
                        <span class="admin-action-icon">
                            <i class="fas fa-right-from-bracket"></i>
                        </span>
                        <div>
                            <h3>Iziet no sistēmas</h3>
                            <p>Droši aizvērt administrēšanas sesiju.</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="admin-panel">
                <div class="admin-panel-head">
                    <div>
                        <h2>Atgādinājumi</h2>
                        <p class="admin-panel-sub">
                            Mazās lietas, kas glābj lielas galvassāpes.
                        </p>
                    </div>
                </div>

                <ul class="admin-list">
                    <li>
                        <i class="fas fa-triangle-exclamation"></i>
                        <span>Pārliecinies, ka regulāri tiek veidotas datubāzes rezerves kopijas.</span>
                    </li>

                    <li>
                        <i class="fas fa-user-shield"></i>
                        <span>Regulāri pārbaudi lietotāju lomas un piekļuves tiesības.</span>
                    </li>

                    <li>
                        <i class="fas fa-lock"></i>
                        <span>Pārbaudi, vai administrācijas sadaļām nav publiskas piekļuves.</span>
                    </li>

                    <li>
                        <i class="fas fa-image"></i>
                        <span>Pirms publicēšanas pārbaudi attēlu izmērus un kvalitāti galerijā.</span>
                    </li>
                </ul>

                <div class="admin-note">
                    <strong>Padoms:</strong>
                    ja kaut kas pēkšņi “pats no sevis” nestrādā, vispirms pārbaudi ceļus,
                    sesiju un datubāzes tabulu nosaukumus. PHP dzīves trijstūris.
                </div>
            </div>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
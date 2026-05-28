<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

$isLoggedIn = isset($_SESSION["lietotajs_id"]);

$username = trim($_SESSION["lietotajvards"] ?? '');
$userRole = trim($_SESSION["loma"] ?? '');

$lomasAtlautas = ["Skolēns", "Ceļameklētājs", "Bērns", "student", "child"];

if (!$isLoggedIn || !in_array($userRole, $lomasAtlautas, true)) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

/* ===============================
   SAITES
================================ */
$studentBase = BASE_URL . "student/";

$studentPanelUrl        = BASE_URL . "dashboards/student.php";
$studentLessonsUrl      = $studentBase . "lessons.php";
$studentApplicationsUrl = $studentBase . "applications.php";
$studentEventsUrl       = $studentBase . "events.php";
$studentCalendarUrl     = $studentBase . "calendar.php";
$studentProfileUrl      = $studentBase . "profile.php";
$studentNewsUrl         = $studentBase . "news.php";
$logoutUrl              = BASE_URL . "auth/logout.php";
$homeUrl                = BASE_URL . "auth/logout.php?redirect=home";

/* ===============================
   AKTĪVĀ LAPA
================================ */
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'student.php');

function studentNavActive(array $pages, string $currentPage): string
{
    return in_array($currentPage, $pages, true) ? 'is-active' : '';
}

/* ===============================
   AVATAR INICIĀĻI
================================ */
$initials = 'C';

if ($username !== '') {
    $parts = preg_split('/\s+/', $username);

    if (!empty($parts[0])) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));

        if (!empty($parts[1])) {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Ceļameklētāja panelis'); ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .student-header {
            position: sticky;
            top: 0;
            z-index: 1500;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(23, 63, 132, 0.10);
            box-shadow: 0 10px 30px rgba(16, 24, 40, 0.05);
        }

        .student-nav-container {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .student-brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            text-decoration: none;
            color: #173f84;
            min-width: 0;
            font-weight: 1000;
        }

        .student-brand-logo {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            flex-shrink: 0;
            border-radius: 1rem;
            background: linear-gradient(135deg, #173f84, #1e4fa1);
            box-shadow: 0 12px 28px rgba(23, 63, 132, 0.18);
            overflow: hidden;
        }

        .student-brand-logo img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .student-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
            min-width: 0;
        }

        .student-brand-text strong {
            font-size: 1.05rem;
            color: #173f84;
            white-space: nowrap;
        }

        .student-brand-text span {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .82rem;
            color: #667085;
            font-weight: 800;
            margin-top: .12rem;
        }

        .student-nav {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-left: auto;
        }

        .student-nav a,
        .student-club-toggle {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            text-decoration: none;
            color: #344054;
            font-weight: 900;
            padding: .7rem .9rem;
            border-radius: 999px;
            transition: .2s ease;
            white-space: nowrap;
            border: none;
            background: transparent;
            font: inherit;
            cursor: pointer;
        }

        .student-nav a i,
        .student-club-toggle i {
            color: #1e4fa1;
        }

        .student-nav a:hover,
        .student-club-toggle:hover {
            background: #eef3ff;
            color: #173f84;
            transform: translateY(-1px);
        }

        .student-nav a.is-active,
        .student-club-toggle.is-active {
            background: linear-gradient(135deg, #1e4fa1, #173f84);
            color: #fff;
            box-shadow: 0 12px 26px rgba(23, 63, 132, 0.18);
        }

        .student-nav a.is-active i,
        .student-club-toggle.is-active i {
            color: #f4c430;
        }

        .student-club-dropdown {
            position: relative;
        }

        .student-club-menu {
            position: absolute;
            top: calc(100% + .75rem);
            left: 0;
            min-width: 245px;
            padding: .7rem;
            border-radius: 1.25rem;
            background: #fff;
            border: 1px solid rgba(23, 63, 132, 0.10);
            box-shadow: 0 24px 65px rgba(16, 24, 40, 0.16);
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px) scale(.98);
            transition: .18s ease;
        }

        .student-club-menu.is-open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .student-club-menu a {
            display: flex;
            width: 100%;
            border-radius: .9rem;
            padding: .8rem .85rem;
        }

        .student-club-menu a:hover {
            background: #eef3ff;
        }

        .dropdown-arrow {
            font-size: .75rem;
            transition: .2s ease;
        }

        .student-club-toggle.is-open .dropdown-arrow {
            transform: rotate(180deg);
        }

        .student-right {
            display: flex;
            align-items: center;
            gap: .7rem;
            position: relative;
        }

        .student-quick-home {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            text-decoration: none;
            color: #173f84;
            background: #eef3ff;
            padding: .68rem .9rem;
            border-radius: 999px;
            font-weight: 900;
            transition: .2s ease;
            white-space: nowrap;
        }

        .student-quick-home:hover {
            background: #dfeaff;
            transform: translateY(-1px);
        }

        .student-user-menu {
            position: relative;
        }

        .student-avatar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 0;
        }

        .student-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: linear-gradient(135deg, #f4c430, #e1aa16);
            color: #173f84;
            font-weight: 1000;
            box-shadow: 0 10px 24px rgba(244, 196, 48, 0.24);
            border: 2px solid rgba(255,255,255,.75);
            transition: .2s ease;
        }

        .student-avatar-btn:hover .student-avatar {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(244, 196, 48, 0.32);
        }

        .student-dropdown {
            position: absolute;
            top: calc(100% + .85rem);
            right: 0;
            width: 290px;
            background: #fff;
            border: 1px solid rgba(23, 63, 132, 0.10);
            border-radius: 1.25rem;
            box-shadow: 0 24px 65px rgba(16, 24, 40, 0.16);
            padding: .75rem;
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px) scale(.98);
            transition: .18s ease;
        }

        .student-user-menu.open .student-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .student-dropdown-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem;
            border-radius: 1rem;
            background: #f8fbff;
            margin-bottom: .5rem;
        }

        .student-dropdown-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #173f84;
            color: #f4c430;
            font-weight: 1000;
            flex-shrink: 0;
        }

        .student-dropdown-name {
            font-weight: 1000;
            color: #101828;
            line-height: 1.2;
        }

        .student-dropdown-role {
            font-size: .86rem;
            color: #667085;
            font-weight: 800;
            margin-top: .1rem;
        }

        .student-dropdown-link {
            display: flex;
            align-items: center;
            gap: .7rem;
            text-decoration: none;
            color: #344054;
            padding: .8rem .85rem;
            border-radius: .9rem;
            font-weight: 900;
            transition: .2s ease;
        }

        .student-dropdown-link i {
            width: 20px;
            color: #1e4fa1;
            text-align: center;
        }

        .student-dropdown-link:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .student-dropdown-link--danger {
            color: #b42318;
        }

        .student-dropdown-link--danger i {
            color: #b42318;
        }

        .student-dropdown-link--danger:hover {
            background: #fff0f0;
            color: #b42318;
        }

        .student-menu-btn {
            display: none;
            width: 44px;
            height: 44px;
            border: none;
            background: #173f84;
            color: #f4c430;
            border-radius: 1rem;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .student-mobile-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            z-index: 1200;
            opacity: 0;
            visibility: hidden;
            transition: .2s ease;
        }

        .student-mobile-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 1120px) {
            .student-brand-text span,
            .student-quick-home span {
                display: none;
            }
        }

        @media (max-width: 980px) {
            .student-menu-btn {
                display: grid;
                place-items: center;
            }

            .student-nav {
                position: fixed;
                top: 86px;
                right: 1rem;
                left: 1rem;
                z-index: 1300;
                display: flex;
                flex-direction: column;
                align-items: stretch;
                gap: .45rem;
                margin-left: 0;
                padding: 1rem;
                border-radius: 1.4rem;
                background: #fff;
                box-shadow: 0 24px 70px rgba(0,0,0,.2);
                opacity: 0;
                visibility: hidden;
                transform: translateY(-10px);
                transition: .2s ease;
            }

            .student-nav.is-open {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .student-nav a,
            .student-club-toggle {
                justify-content: flex-start;
                border-radius: 1rem;
                padding: .95rem 1rem;
                width: 100%;
            }

            .student-club-menu {
                position: static;
                min-width: 100%;
                margin-top: .35rem;
                box-shadow: none;
                border-radius: 1rem;
                background: #f8fbff;
                display: none;
                opacity: 1;
                visibility: visible;
                transform: none;
            }

            .student-club-menu.is-open {
                display: grid;
                gap: .25rem;
            }

            body.nav-lock {
                overflow: hidden;
            }
        }

        @media (max-width: 560px) {
            .student-nav-container {
                min-height: 68px;
            }

            .student-brand-text {
                display: none;
            }

            .student-brand-logo {
                width: 44px;
                height: 44px;
            }

            .student-quick-home {
                display: none;
            }

            .student-dropdown {
                position: fixed;
                top: 76px;
                right: 1rem;
                left: 1rem;
                width: auto;
            }
        }
    </style>
</head>

<body>

<header class="student-header">
    <div class="container student-nav-container">

        <a href="<?= $studentPanelUrl ?>" class="student-brand">
            <span class="student-brand-logo">
                <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            </span>

            <span class="student-brand-text">
                <strong>Ceļameklētāja panelis</strong>
                <span><?= htmlspecialchars($lapa ?? 'Ceļa meklētāji') ?></span>
            </span>
        </a>

        <nav class="student-nav" id="studentNav" aria-label="Ceļameklētāja navigācija">

            <a href="<?= $studentPanelUrl ?>" class="<?= studentNavActive(['student.php'], $currentPage) ?>">
                <i class="fas fa-gauge-high"></i>
                <span>Panelis</span>
            </a>

            <div class="student-club-dropdown" id="studentClubDropdown">
                <button
                    class="student-club-toggle <?= studentNavActive(['lessons.php', 'applications.php', 'events.php', 'calendar.php'], $currentPage) ?>"
                    id="studentClubToggle"
                    type="button"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="fas fa-campground"></i>
                    <span>Mans klubs</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div class="student-club-menu" id="studentClubMenu">
                <a href="<?= $studentCalendarUrl ?>" class="<?= studentNavActive(['calendar.php'], $currentPage) ?>">
                        <i class="fas fa-calendar-week"></i>
                        <span>Kalendārs</span>
                    </a>

                
                    <a href="<?= $studentLessonsUrl ?>" class="<?= studentNavActive(['lessons.php'], $currentPage) ?>">
                        <i class="fas fa-book-open"></i>
                        <span>Nodarbības</span>
                    </a>

                    <a href="<?= $studentApplicationsUrl ?>" class="<?= studentNavActive(['applications.php'], $currentPage) ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Mani pieteikumi</span>
                    </a>

                    <a href="<?= $studentEventsUrl ?>" class="<?= studentNavActive(['events.php'], $currentPage) ?>">
                        <i class="fas fa-calendar-days"></i>
                        <span>Pasākumi</span>
                    </a>

                </div>
            </div>

            <a href="<?= $studentNewsUrl ?>" class="<?= studentNavActive(['news.php'], $currentPage) ?>">
                <i class="fas fa-newspaper"></i>
                <span>Jaunumi</span>
            </a>

        </nav>

        <div class="student-right">

            <a href="<?= $homeUrl ?>" class="student-quick-home">
                <i class="fas fa-arrow-left"></i>
                <span>Uz sākumlapu</span>
            </a>

            <div class="student-user-menu" id="studentUserMenu">
                <button
                    class="student-avatar-btn"
                    id="studentAvatarBtn"
                    type="button"
                    aria-label="Atvērt lietotāja izvēlni"
                    aria-expanded="false"
                >
                    <span class="student-avatar">
                        <?= htmlspecialchars($initials); ?>
                    </span>
                </button>

               <div class="student-dropdown">
    <div class="student-dropdown-head">
        <span class="student-dropdown-avatar">
            <?= htmlspecialchars($initials); ?>
        </span>

        <div>
            <div class="student-dropdown-name">
                <?= htmlspecialchars($username ?: 'Ceļameklētājs'); ?>
            </div>

            <div class="student-dropdown-role">
                <?= htmlspecialchars($userRole ?: 'Ceļameklētājs'); ?>
            </div>
        </div>
    </div>

    <a href="<?= $studentProfileUrl ?>" class="student-dropdown-link">
        <i class="fas fa-user-gear"></i>
        <span>Mans profils</span>
    </a>

    <a href="<?= $homeUrl ?>" class="student-dropdown-link">
        <i class="fas fa-house"></i>
        <span>Uz sākumlapu</span>
    </a>

    <a href="<?= $logoutUrl ?>" class="student-dropdown-link student-dropdown-link--danger">
        <i class="fas fa-right-from-bracket"></i>
        <span>Iziet</span>
    </a>
</div>
            <button
                id="studentMenuBtn"
                class="student-menu-btn"
                type="button"
                aria-label="Atvērt izvēlni"
                aria-expanded="false"
            >
                <i class="fas fa-bars"></i>
            </button>

        </div>
    </div>
</header>

<div class="student-mobile-backdrop" id="studentNavBackdrop"></div>

<script>
(function () {
    const menuBtn = document.getElementById('studentMenuBtn');
    const nav = document.getElementById('studentNav');
    const backdrop = document.getElementById('studentNavBackdrop');

    const avatarBtn = document.getElementById('studentAvatarBtn');
    const userMenu = document.getElementById('studentUserMenu');

    const clubDropdown = document.getElementById('studentClubDropdown');
    const clubToggle = document.getElementById('studentClubToggle');
    const clubMenu = document.getElementById('studentClubMenu');

    function closeClubMenu() {
        if (!clubToggle || !clubMenu) return;

        clubToggle.classList.remove('is-open');
        clubMenu.classList.remove('is-open');
        clubToggle.setAttribute('aria-expanded', 'false');
    }

    function closeUserMenu() {
        if (!avatarBtn || !userMenu) return;

        userMenu.classList.remove('open');
        avatarBtn.setAttribute('aria-expanded', 'false');
    }

    function openMenu() {
        if (!menuBtn || !nav || !backdrop) return;

        nav.classList.add('is-open');
        backdrop.classList.add('show');
        document.body.classList.add('nav-lock');

        menuBtn.innerHTML = '<i class="fas fa-xmark"></i>';
        menuBtn.setAttribute('aria-expanded', 'true');
        menuBtn.setAttribute('aria-label', 'Aizvērt izvēlni');

        closeUserMenu();
    }

    function closeMenu() {
        if (!menuBtn || !nav || !backdrop) return;

        nav.classList.remove('is-open');
        backdrop.classList.remove('show');
        document.body.classList.remove('nav-lock');

        menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.setAttribute('aria-label', 'Atvērt izvēlni');

        closeClubMenu();
    }

    menuBtn?.addEventListener('click', function () {
        nav.classList.contains('is-open') ? closeMenu() : openMenu();
    });

    backdrop?.addEventListener('click', function () {
        closeMenu();
        closeUserMenu();
    });

    avatarBtn?.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = userMenu.classList.toggle('open');
        avatarBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        closeMenu();
        closeClubMenu();
    });

    clubToggle?.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = clubMenu.classList.toggle('is-open');
        clubToggle.classList.toggle('is-open', isOpen);
        clubToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        closeUserMenu();
    });

    document.addEventListener('click', function (event) {
        if (userMenu && !userMenu.contains(event.target)) {
            closeUserMenu();
        }

        if (clubDropdown && !clubDropdown.contains(event.target)) {
            closeClubMenu();
        }
    });

    nav?.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            closeMenu();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
            closeClubMenu();
            closeUserMenu();
        }
    });
})();
</script>
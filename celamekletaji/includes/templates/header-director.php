<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

$allowedRoles = ['Direktors', 'direktors'];

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ''), $allowedRoles, true)
) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$username = trim($_SESSION["lietotajvards"] ?? 'Direktors');
$userRole = trim($_SESSION["loma"] ?? 'Direktors');

$pageTitle = $title ?? 'Direktora panelis';
$pageName  = $lapa ?? 'Direktora panelis';

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'director.php');
$currentUrl  = $_SERVER['REQUEST_URI'] ?? '';

function directorNavActive(array $pages, string $currentPage): string
{
    return in_array($currentPage, $pages, true) ? 'is-active' : '';
}

function directorUrlActive(string $needle, string $currentUrl): string
{
    return str_contains($currentUrl, $needle) ? 'is-active' : '';
}

/* ===============================
   SAITES
================================ */
$dashboardUrl = BASE_URL . 'dashboards/director.php';

$clubUrl             = BASE_URL . 'director/club.php';
$clubActivitiesUrl   = BASE_URL . 'director/activities.php';
$clubApplicationsUrl = BASE_URL . 'director/applications.php';
$clubLessonsUrl      = BASE_URL . 'director/lesson_plans.php';

$childrenUrl = BASE_URL . 'director/users.php?role=children';
$parentsUrl  = BASE_URL . 'director/users.php?role=parents';
$teachersUrl = BASE_URL . 'director/users.php?role=teachers';
$allUsersUrl = BASE_URL . 'director/users.php';

$addUserUrl = BASE_URL . 'director/add_user.php';
$profileUrl = BASE_URL . 'profile.php';
$homeUrl    = BASE_URL . 'auth/logout.php?redirect=home';
$logoutUrl  = BASE_URL . 'auth/logout.php';

$clubDropdownActive = (
    str_contains($currentUrl, '/director/club.php') ||
    str_contains($currentUrl, '/director/activities.php') ||
    str_contains($currentUrl, '/director/applications.php') ||
    str_contains($currentUrl, '/director/lesson_plans.php')
);

$usersDropdownActive = (
    str_contains($currentUrl, 'role=children') ||
    str_contains($currentUrl, 'role=parents') ||
    str_contains($currentUrl, 'role=teachers') ||
    str_contains($currentUrl, '/director/users.php')
);

/* ===============================
   AVATAR INICIĀĻI
================================ */
$initials = 'D';

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
    <title><?= htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        .director-header {
            position: sticky;
            top: 0;
            z-index: 1500;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(23, 63, 132, 0.10);
            box-shadow: 0 10px 30px rgba(16, 24, 40, 0.05);
        }

        .director-nav-container {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .director-brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            text-decoration: none;
            color: #173f84;
            min-width: 0;
            font-weight: 1000;
        }

        .director-brand-logo {
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

        .director-brand-logo img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .director-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
            min-width: 0;
        }

        .director-brand-text strong {
            font-size: 1.05rem;
            color: #173f84;
            white-space: nowrap;
        }

        .director-brand-text span {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .82rem;
            color: #667085;
            font-weight: 800;
            margin-top: .12rem;
        }

        .director-nav {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-left: auto;
        }

        .director-nav a,
        .director-dropdown-toggle {
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

        .director-nav a i,
        .director-dropdown-toggle i {
            color: #1e4fa1;
        }

        .director-nav a:hover,
        .director-dropdown-toggle:hover {
            background: #eef3ff;
            color: #173f84;
            transform: translateY(-1px);
        }

        .director-nav a.is-active,
        .director-dropdown-toggle.is-active {
            background: linear-gradient(135deg, #1e4fa1, #173f84);
            color: #fff;
            box-shadow: 0 12px 26px rgba(23, 63, 132, 0.18);
        }

        .director-nav a.is-active i,
        .director-dropdown-toggle.is-active i {
            color: #f4c430;
        }

        .director-dropdown {
            position: relative;
        }

        .director-dropdown-menu {
            position: absolute;
            top: calc(100% + .75rem);
            left: 0;
            min-width: 255px;
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

        .director-dropdown-menu.is-open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .director-dropdown-menu a {
            display: flex;
            width: 100%;
            border-radius: .9rem;
            padding: .8rem .85rem;
        }

        .director-dropdown-menu a:hover {
            background: #eef3ff;
        }

        .dropdown-arrow {
            font-size: .75rem;
            transition: .2s ease;
        }

        .director-dropdown-toggle.is-open .dropdown-arrow {
            transform: rotate(180deg);
        }

        .director-add-btn {
            background: linear-gradient(135deg, #f4c430, #e1aa16) !important;
            color: #173f84 !important;
            box-shadow: 0 12px 26px rgba(244, 196, 48, 0.24);
        }

        .director-add-btn i {
            color: #173f84 !important;
        }

        .director-add-btn:hover {
            background: linear-gradient(135deg, #ffd84c, #e1aa16) !important;
        }

        .director-right {
            display: flex;
            align-items: center;
            gap: .7rem;
            position: relative;
        }

        .director-quick-home {
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

        .director-quick-home:hover {
            background: #dfeaff;
            transform: translateY(-1px);
        }

        .director-user-menu {
            position: relative;
        }

        .director-avatar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 0;
        }

        .director-avatar {
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

        .director-avatar-btn:hover .director-avatar {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(244, 196, 48, 0.32);
        }

        .director-account-menu {
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

        .director-user-menu.open .director-account-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .director-account-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem;
            border-radius: 1rem;
            background: #f8fbff;
            margin-bottom: .5rem;
        }

        .director-account-avatar-small {
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

        .director-account-name {
            font-weight: 1000;
            color: #101828;
            line-height: 1.2;
        }

        .director-account-role {
            font-size: .86rem;
            color: #667085;
            font-weight: 800;
            margin-top: .1rem;
        }

        .director-account-link {
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

        .director-account-link i {
            width: 20px;
            color: #1e4fa1;
            text-align: center;
        }

        .director-account-link:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .director-account-link--danger {
            color: #b42318;
        }

        .director-account-link--danger i {
            color: #b42318;
        }

        .director-account-link--danger:hover {
            background: #fff0f0;
            color: #b42318;
        }

        .director-menu-btn {
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

        .director-mobile-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            z-index: 1200;
            opacity: 0;
            visibility: hidden;
            transition: .2s ease;
        }

        .director-mobile-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 1180px) {
            .director-brand-text span,
            .director-quick-home span,
            .director-add-btn span {
                display: none;
            }
        }

        @media (max-width: 980px) {
            .director-menu-btn {
                display: grid;
                place-items: center;
            }

            .director-nav {
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

            .director-nav.is-open {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .director-nav a,
            .director-dropdown-toggle {
                justify-content: flex-start;
                border-radius: 1rem;
                padding: .95rem 1rem;
                width: 100%;
            }

            .director-dropdown-menu {
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

            .director-dropdown-menu.is-open {
                display: grid;
                gap: .25rem;
            }

            body.nav-lock {
                overflow: hidden;
            }
        }

        @media (max-width: 560px) {
            .director-nav-container {
                min-height: 68px;
            }

            .director-brand-text {
                display: none;
            }

            .director-brand-logo {
                width: 44px;
                height: 44px;
            }

            .director-quick-home {
                display: none;
            }

            .director-account-menu {
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

<header class="director-header">
    <div class="container director-nav-container">

        <a href="<?= $dashboardUrl ?>" class="director-brand">
            <span class="director-brand-logo">
                <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            </span>

            <span class="director-brand-text">
                <strong>Direktora panelis</strong>
                <span><?= htmlspecialchars($pageName); ?></span>
            </span>
        </a>

        <nav class="director-nav" id="directorNav" aria-label="Direktora navigācija">

            <a href="<?= $dashboardUrl ?>" class="<?= directorNavActive(['director.php'], $currentPage); ?>">
                <i class="fas fa-gauge-high"></i>
                <span>Panelis</span>
            </a>

            <div class="director-dropdown" id="directorClubDropdown">
                <button
                    class="director-dropdown-toggle <?= $clubDropdownActive ? 'is-active' : ''; ?>"
                    type="button"
                    id="directorClubToggle"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="fas fa-people-roof"></i>
                    <span>Mans klubs</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div class="director-dropdown-menu" id="directorClubMenu">
                    <a href="<?= $clubUrl ?>" class="<?= directorNavActive(['club.php'], $currentPage); ?>">
                        <i class="fas fa-circle-info"></i>
                        <span>Kluba informācija</span>
                    </a>

                    <a href="<?= $clubActivitiesUrl ?>" class="<?= directorNavActive(['activities.php'], $currentPage); ?>">
                        <i class="fas fa-calendar-days"></i>
                        <span>Aktivitātes</span>
                    </a>

                    <a href="<?= $clubApplicationsUrl ?>" class="<?= directorNavActive(['applications.php'], $currentPage); ?>">
                        <i class="fas fa-file-signature"></i>
                        <span>Pieteikumi</span>
                    </a>

                    <a href="<?= $clubLessonsUrl ?>" class="<?= directorNavActive(['lesson_plans.php'], $currentPage); ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Nodarbību plāni</span>
                    </a>
                </div>
            </div>

            <div class="director-dropdown" id="directorUsersDropdown">
                <button
                    class="director-dropdown-toggle <?= $usersDropdownActive ? 'is-active' : ''; ?>"
                    type="button"
                    id="directorUsersToggle"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="fas fa-users-gear"></i>
                    <span>Lietotāji</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div class="director-dropdown-menu" id="directorUsersMenu">
                    <a href="<?= $childrenUrl ?>" class="<?= str_contains($currentUrl, 'role=children') ? 'is-active' : ''; ?>">
                        <i class="fas fa-child-reaching"></i>
                        <span>Bērni</span>
                    </a>

                    <a href="<?= $parentsUrl ?>" class="<?= str_contains($currentUrl, 'role=parents') ? 'is-active' : ''; ?>">
                        <i class="fas fa-users"></i>
                        <span>Vecāki</span>
                    </a>

                    <a href="<?= $teachersUrl ?>" class="<?= str_contains($currentUrl, 'role=teachers') ? 'is-active' : ''; ?>">
                        <i class="fas fa-chalkboard-user"></i>
                        <span>Skolotāji</span>
                    </a>

                    <a href="<?= $allUsersUrl ?>" class="<?= ($currentPage === 'users.php' && !str_contains($currentUrl, 'role=')) ? 'is-active' : ''; ?>">
                        <i class="fas fa-list"></i>
                        <span>Visi lietotāji</span>
                    </a>
                </div>
            </div>

            <a href="<?= $addUserUrl ?>" class="director-add-btn <?= directorNavActive(['add_user.php'], $currentPage); ?>">
                <i class="fas fa-user-plus"></i>
                <span>Pievienot</span>
            </a>

        </nav>

        <div class="director-right">

            <a href="<?= $homeUrl ?>" class="director-quick-home">
                <i class="fas fa-arrow-left"></i>
                <span>Uz sākumlapu</span>
            </a>

            <div class="director-user-menu" id="directorUserMenu">
                <button
                    class="director-avatar-btn"
                    id="directorAvatarBtn"
                    type="button"
                    aria-label="Atvērt lietotāja izvēlni"
                    aria-expanded="false"
                    title="<?= htmlspecialchars($username); ?>"
                >
                    <span class="director-avatar">
                        <?= htmlspecialchars($initials); ?>
                    </span>
                </button>

                <div class="director-account-menu" id="directorAccountMenu">
                    <div class="director-account-head">
                        <span class="director-account-avatar-small">
                            <?= htmlspecialchars($initials); ?>
                        </span>

                        <div>
                            <div class="director-account-name">
                                <?= htmlspecialchars($username ?: 'Direktors'); ?>
                            </div>

                            <div class="director-account-role">
                                <?= htmlspecialchars($userRole ?: 'Direktors'); ?>
                            </div>
                        </div>
                    </div>

                    <a href="<?= $profileUrl ?>" class="director-account-link">
                        <i class="fas fa-user-pen"></i>
                        <span>Labot profilu</span>
                    </a>

                    <a href="<?= $homeUrl ?>" class="director-account-link">
                        <i class="fas fa-house"></i>
                        <span>Uz sākumlapu</span>
                    </a>

                    <a href="<?= $logoutUrl ?>" class="director-account-link director-account-link--danger">
                        <i class="fas fa-right-from-bracket"></i>
                        <span>Iziet</span>
                    </a>
                </div>
            </div>

            <button
                id="directorMenuBtn"
                class="director-menu-btn"
                type="button"
                aria-label="Atvērt izvēlni"
                aria-expanded="false"
            >
                <i class="fas fa-bars"></i>
            </button>

        </div>
    </div>
</header>

<div class="director-mobile-backdrop" id="directorNavBackdrop"></div>

<script>
(function () {
    const menuBtn = document.getElementById('directorMenuBtn');
    const nav = document.getElementById('directorNav');
    const backdrop = document.getElementById('directorNavBackdrop');

    const avatarBtn = document.getElementById('directorAvatarBtn');
    const userMenu = document.getElementById('directorUserMenu');

    const clubDropdown = document.getElementById('directorClubDropdown');
    const clubToggle = document.getElementById('directorClubToggle');
    const clubMenu = document.getElementById('directorClubMenu');

    const usersDropdown = document.getElementById('directorUsersDropdown');
    const usersToggle = document.getElementById('directorUsersToggle');
    const usersMenu = document.getElementById('directorUsersMenu');

    function closeClubMenu() {
        if (!clubToggle || !clubMenu) return;

        clubToggle.classList.remove('is-open');
        clubMenu.classList.remove('is-open');
        clubToggle.setAttribute('aria-expanded', 'false');
    }

    function closeUsersMenu() {
        if (!usersToggle || !usersMenu) return;

        usersToggle.classList.remove('is-open');
        usersMenu.classList.remove('is-open');
        usersToggle.setAttribute('aria-expanded', 'false');
    }

    function closeUserMenu() {
        if (!avatarBtn || !userMenu) return;

        userMenu.classList.remove('open');
        avatarBtn.setAttribute('aria-expanded', 'false');
    }

    function closeAllDropdowns() {
        closeClubMenu();
        closeUsersMenu();
        closeUserMenu();
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
        closeUsersMenu();
    }

    menuBtn?.addEventListener('click', function () {
        nav.classList.contains('is-open') ? closeMenu() : openMenu();
    });

    backdrop?.addEventListener('click', function () {
        closeMenu();
        closeAllDropdowns();
    });

    avatarBtn?.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = userMenu.classList.toggle('open');
        avatarBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        closeMenu();
        closeClubMenu();
        closeUsersMenu();
    });

    clubToggle?.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = clubMenu.classList.toggle('is-open');
        clubToggle.classList.toggle('is-open', isOpen);
        clubToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        closeUsersMenu();
        closeUserMenu();
    });

    usersToggle?.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = usersMenu.classList.toggle('is-open');
        usersToggle.classList.toggle('is-open', isOpen);
        usersToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        closeClubMenu();
        closeUserMenu();
    });

    document.addEventListener('click', function (event) {
        if (userMenu && !userMenu.contains(event.target)) {
            closeUserMenu();
        }

        if (clubDropdown && !clubDropdown.contains(event.target)) {
            closeClubMenu();
        }

        if (usersDropdown && !usersDropdown.contains(event.target)) {
            closeUsersMenu();
        }
    });

    nav?.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            closeMenu();
            closeAllDropdowns();
        });
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
            closeAllDropdowns();
        }
    });
})();
</script>
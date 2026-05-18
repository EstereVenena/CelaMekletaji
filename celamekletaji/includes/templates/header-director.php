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

$currentUrl = $_SERVER['REQUEST_URI'] ?? '';

function directorIsActive(string $needle, string $currentUrl): string
{
    return str_contains($currentUrl, $needle) ? 'is-active' : '';
}

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
$homeUrl = BASE_URL . 'auth/logout.php?redirect=home';
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

$avatarLetter = 'D';

if ($username !== '') {
    $avatarLetter = mb_strtoupper(mb_substr($username, 0, 1));
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<header class="prof-header director-prof-header">
    <div class="header-container">

        <a href="<?= $dashboardUrl ?>" class="brand-block">
            <span class="brand-logo">
                <img class="logo" src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            </span>

            <span class="brand-meta">
                <span class="brand-name">Ceļa meklētāji</span>
                <span class="brand-sub">Direktora panelis</span>
            </span>
        </a>

        <h1 class="header-title">
            <?= htmlspecialchars($pageName) ?>
        </h1>

        <nav class="main-nav director-main-nav" id="directorMainNav">

            <a class="nav-link <?= directorIsActive('/dashboards/director.php', $currentUrl) ?>"
               href="<?= $dashboardUrl ?>">
                <i class="fas fa-gauge"></i>
                <span>Panelis</span>
            </a>

            <!-- MANS KLUBS DROPDOWN -->
            <div class="director-club-dropdown">
                <button
                    class="nav-link director-club-toggle <?= $clubDropdownActive ? 'is-active' : '' ?>"
                    type="button"
                    id="directorClubToggle"
                    aria-expanded="false"
                    aria-haspopup="true"
                >
                    <i class="fas fa-people-roof"></i>
                    <span>Mans klubs</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div class="director-club-menu" id="directorClubMenu">
                    <a href="<?= $clubUrl ?>">
                        <i class="fas fa-circle-info"></i>
                        <span>Kluba informācija</span>
                    </a>

                    <a href="<?= $clubActivitiesUrl ?>">
                        <i class="fas fa-calendar-days"></i>
                        <span>Aktivitātes</span>
                    </a>

                    <a href="<?= $clubApplicationsUrl ?>">
                        <i class="fas fa-file-signature"></i>
                        <span>Pieteikumi</span>
                    </a>

                    <a href="<?= $clubLessonsUrl ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Nodarbību plāni</span>
                    </a>
                </div>
            </div>

            <!-- LIETOTĀJI DROPDOWN -->
            <div class="director-users-dropdown">
                <button
                    class="nav-link director-users-toggle <?= $usersDropdownActive ? 'is-active' : '' ?>"
                    type="button"
                    id="directorUsersToggle"
                    aria-expanded="false"
                    aria-haspopup="true"
                >
                    <i class="fas fa-users-gear"></i>
                    <span>Lietotāji</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div class="director-users-menu" id="directorUsersMenu">
                    <a href="<?= $childrenUrl ?>">
                        <i class="fas fa-child-reaching"></i>
                        <span>Bērni</span>
                    </a>

                    <a href="<?= $parentsUrl ?>">
                        <i class="fas fa-users"></i>
                        <span>Vecāki</span>
                    </a>

                    <a href="<?= $teachersUrl ?>">
                        <i class="fas fa-chalkboard-user"></i>
                        <span>Skolotāji</span>
                    </a>

                    <a href="<?= $allUsersUrl ?>">
                        <i class="fas fa-list"></i>
                        <span>Visi lietotāji</span>
                    </a>
                </div>
            </div>

            <a class="nav-link nav-cta <?= directorIsActive('/director/add_user.php', $currentUrl) ?>"
               href="<?= $addUserUrl ?>">
                <i class="fas fa-user-plus"></i>
                <span class="nav-cta-text">Pievienot</span>
            </a>

            <!-- PROFILA IKONA DROPDOWN -->
            <div class="director-account-dropdown">
                <button
                    class="director-account-toggle"
                    type="button"
                    id="directorAccountToggle"
                    aria-expanded="false"
                    aria-haspopup="true"
                    title="<?= htmlspecialchars($username) ?>"
                >
                    <span class="director-account-avatar">
                        <?= htmlspecialchars($avatarLetter) ?>
                    </span>
                </button>

                <div class="director-account-menu" id="directorAccountMenu">
                    <div class="director-account-head">
                        <strong><?= htmlspecialchars($username) ?></strong>
                        <small><?= htmlspecialchars($userRole) ?></small>
                    </div>

                    <a href="<?= $profileUrl ?>">
                        <i class="fas fa-user-pen"></i>
                        <span>Labot profilu</span>
                    </a>

                    <a href="<?= $homeUrl ?>">
                        <i class="fas fa-house"></i>
                        <span>Uz sākuma lapu</span>
                    </a>

                    <a class="danger" href="<?= $logoutUrl ?>">
                        <i class="fas fa-right-from-bracket"></i>
                        <span>Iziet</span>
                    </a>
                </div>
            </div>
        </nav>

        <button class="menu-btn" id="directorMenuBtn" type="button" aria-label="Atvērt izvēlni" aria-expanded="false">
            <i class="fas fa-bars"></i>
        </button>

    </div>
</header>

<div class="nav-backdrop" id="directorNavBackdrop" hidden></div>

<script>
(function () {
    const menuBtn = document.getElementById('directorMenuBtn');
    const nav = document.getElementById('directorMainNav');
    const backdrop = document.getElementById('directorNavBackdrop');

    const clubToggle = document.getElementById('directorClubToggle');
    const clubMenu = document.getElementById('directorClubMenu');

    const usersToggle = document.getElementById('directorUsersToggle');
    const usersMenu = document.getElementById('directorUsersMenu');

    const accountToggle = document.getElementById('directorAccountToggle');
    const accountMenu = document.getElementById('directorAccountMenu');

    function openMobileMenu() {
        if (!menuBtn || !nav || !backdrop) return;

        nav.classList.add('is-open');
        backdrop.hidden = false;

        requestAnimationFrame(function () {
            backdrop.classList.add('show');
        });

        document.body.classList.add('nav-lock');
        menuBtn.setAttribute('aria-expanded', 'true');
        menuBtn.innerHTML = '<i class="fas fa-xmark"></i>';
    }

    function closeMobileMenu() {
        if (!menuBtn || !nav || !backdrop) return;

        nav.classList.remove('is-open');
        backdrop.classList.remove('show');
        document.body.classList.remove('nav-lock');

        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.innerHTML = '<i class="fas fa-bars"></i>';

        setTimeout(function () {
            if (!backdrop.classList.contains('show')) {
                backdrop.hidden = true;
            }
        }, 220);
    }

    function closeClubDropdown() {
        if (!clubToggle || !clubMenu) return;

        clubMenu.classList.remove('is-open');
        clubToggle.classList.remove('is-open');
        clubToggle.setAttribute('aria-expanded', 'false');
    }

    function closeUsersDropdown() {
        if (!usersToggle || !usersMenu) return;

        usersMenu.classList.remove('is-open');
        usersToggle.classList.remove('is-open');
        usersToggle.setAttribute('aria-expanded', 'false');
    }

    function closeAccountDropdown() {
        if (!accountToggle || !accountMenu) return;

        accountMenu.classList.remove('is-open');
        accountToggle.classList.remove('is-open');
        accountToggle.setAttribute('aria-expanded', 'false');
    }

    function closeAllDropdowns() {
        closeClubDropdown();
        closeUsersDropdown();
        closeAccountDropdown();
    }

    function toggleClubDropdown(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!clubToggle || !clubMenu) return;

        closeUsersDropdown();
        closeAccountDropdown();

        const isOpen = clubMenu.classList.toggle('is-open');
        clubToggle.classList.toggle('is-open', isOpen);
        clubToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function toggleUsersDropdown(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!usersToggle || !usersMenu) return;

        closeClubDropdown();
        closeAccountDropdown();

        const isOpen = usersMenu.classList.toggle('is-open');
        usersToggle.classList.toggle('is-open', isOpen);
        usersToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function toggleAccountDropdown(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!accountToggle || !accountMenu) return;

        closeClubDropdown();
        closeUsersDropdown();

        const isOpen = accountMenu.classList.toggle('is-open');
        accountToggle.classList.toggle('is-open', isOpen);
        accountToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    if (menuBtn && nav && backdrop) {
        menuBtn.addEventListener('click', function () {
            if (nav.classList.contains('is-open')) {
                closeMobileMenu();
            } else {
                openMobileMenu();
            }
        });

        backdrop.addEventListener('click', function () {
            closeMobileMenu();
            closeAllDropdowns();
        });

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 768px)').matches) {
                    closeMobileMenu();
                }

                closeAllDropdowns();
            });
        });
    }

    if (clubToggle && clubMenu) {
        clubToggle.addEventListener('click', toggleClubDropdown);

        clubMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    }

    if (usersToggle && usersMenu) {
        usersToggle.addEventListener('click', toggleUsersDropdown);

        usersMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    }

    if (accountToggle && accountMenu) {
        accountToggle.addEventListener('click', toggleAccountDropdown);

        accountMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    }

    document.addEventListener('click', function () {
        closeAllDropdowns();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileMenu();
            closeAllDropdowns();
        }
    });
})();
</script>
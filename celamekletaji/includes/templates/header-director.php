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
$clubUrl      = BASE_URL . 'director/club.php';
$childrenUrl  = BASE_URL . 'director/users.php?role=children';
$parentsUrl   = BASE_URL . 'director/users.php?role=parents';
$teachersUrl  = BASE_URL . 'director/users.php?role=teachers';
$allUsersUrl   = BASE_URL . 'director/users.php';
$addUserUrl   = BASE_URL . 'director/add_user.php';
$profileUrl   = BASE_URL . 'profile.php';
$homeUrl      = BASE_URL . 'index.php';
$logoutUrl    = BASE_URL . 'auth/logout.php';

$usersDropdownActive = (
    str_contains($currentUrl, 'role=children') ||
    str_contains($currentUrl, 'role=parents') ||
    str_contains($currentUrl, 'role=teachers') ||
    str_contains($currentUrl, '/director/users.php')
);
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

            <a class="nav-link <?= directorIsActive('/director/club.php', $currentUrl) ?>"
               href="<?= $clubUrl ?>">
                <i class="fas fa-people-roof"></i>
                <span>Mans klubs</span>
            </a>

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

            <a class="nav-link director-profile-link" href="<?= $profileUrl ?>">
                <i class="fas fa-user"></i>
                <span><?= htmlspecialchars($username) ?></span>
            </a>

            <a class="nav-link director-home-link" href="<?= $homeUrl ?>">
                <i class="fas fa-house"></i>
                <span>Sākums</span>
            </a>

            <a class="nav-link director-logout-link" href="<?= $logoutUrl ?>">
                <i class="fas fa-right-from-bracket"></i>
                <span>Iziet</span>
            </a>
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

    const usersToggle = document.getElementById('directorUsersToggle');
    const usersMenu = document.getElementById('directorUsersMenu');

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

    function closeUsersDropdown() {
        if (!usersToggle || !usersMenu) return;

        usersMenu.classList.remove('is-open');
        usersToggle.classList.remove('is-open');
        usersToggle.setAttribute('aria-expanded', 'false');
    }

    function toggleUsersDropdown(event) {
        event.preventDefault();
        event.stopPropagation();

        if (!usersToggle || !usersMenu) return;

        const isOpen = usersMenu.classList.toggle('is-open');

        usersToggle.classList.toggle('is-open', isOpen);
        usersToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
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
            closeUsersDropdown();
        });

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 768px)').matches) {
                    closeMobileMenu();
                }

                closeUsersDropdown();
            });
        });
    }

    if (usersToggle && usersMenu) {
        usersToggle.addEventListener('click', toggleUsersDropdown);

        usersMenu.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    }

    document.addEventListener('click', function () {
        closeUsersDropdown();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMobileMenu();
            closeUsersDropdown();
        }
    });
})();
</script>
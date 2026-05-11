<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$username = trim($_SESSION["lietotajvards"] ?? 'Administrators');
$userRole = trim($_SESSION["loma"] ?? 'admin');

$initials = 'A';
if ($username !== '') {
    $parts = preg_split('/\s+/', $username);
    if (!empty($parts[0])) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
        if (!empty($parts[1])) {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }
    }
}

$currentPath = $_SERVER['PHP_SELF'] ?? '';

function adminNavActive(string $needle, string $currentPath): string
{
    return strpos($currentPath, $needle) !== false ? 'is-active' : '';
}

$dashboardUrl = BASE_URL . 'dashboards/admin.php';
$newsUrl      = BASE_URL . 'admin/news/news.php';
$clubsUrl     = BASE_URL . 'admin/clubs/clubs.php';
$galleryUrl   = BASE_URL . 'admin/gallery/gallery.php';
$usersUrl     = BASE_URL . 'admin/users/users_manage.php';
$homeUrl      = BASE_URL . 'index.php';
$logoutUrl    = BASE_URL . 'auth/logout.php';
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Admin Panelis') ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        .admin-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(30,79,161,0.12);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        .admin-nav-container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: .7rem;
            text-decoration: none;
            color: #173f84;
            font-weight: 800;
            white-space: nowrap;
        }

        .admin-logo img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .admin-logo span {
            font-size: 1.05rem;
        }

        .admin-title {
            font-weight: 800;
            color: #1e4fa1;
            background: #eef3ff;
            padding: .45rem .85rem;
            border-radius: 999px;
            font-size: .95rem;
            white-space: nowrap;
        }

        .admin-nav {
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .admin-nav a {
            text-decoration: none;
            color: #263238;
            padding: .6rem .85rem;
            border-radius: 999px;
            font-weight: 700;
            transition: .2s ease;
            font-size: .95rem;
        }

        .admin-nav a:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .admin-nav a.is-active {
            background: #173f84;
            color: #fff;
            box-shadow: 0 6px 18px rgba(23,63,132,.22);
        }

        .admin-right {
            display: flex;
            align-items: center;
            gap: .65rem;
            position: relative;
        }

        .admin-home {
            display: inline-flex;
            align-items: center;
            gap: .4rem;
            text-decoration: none;
            background: #f4c430;
            color: #1d1d1d;
            padding: .55rem .85rem;
            border-radius: 999px;
            font-weight: 800;
            transition: .2s ease;
            white-space: nowrap;
        }

        .admin-home:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(244,196,48,.28);
        }

        .admin-user-menu {
            position: relative;
        }

        .admin-avatar-btn {
            border: none;
            background: transparent;
            padding: 0;
            cursor: pointer;
        }

        .admin-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: linear-gradient(135deg, #173f84, #1e4fa1);
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 900;
            box-shadow: 0 8px 20px rgba(23,63,132,.25);
        }

        .admin-dropdown {
            position: absolute;
            top: 55px;
            right: 0;
            width: 245px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(0,0,0,.13);
            border: 1px solid #eef0f4;
            display: none;
            overflow: hidden;
        }

        .admin-user-menu.open .admin-dropdown {
            display: block;
        }

        .admin-dropdown-head {
            padding: .9rem 1rem;
            background: #f7f9fc;
            border-bottom: 1px solid #edf0f5;
        }

        .admin-dropdown-name {
            font-weight: 900;
            color: #173f84;
        }

        .admin-dropdown-role {
            font-size: .85rem;
            color: #6b7280;
            margin-top: .15rem;
        }

        .admin-dropdown-link {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .75rem 1rem;
            color: #263238;
            text-decoration: none;
            font-weight: 700;
            transition: .2s;
        }

        .admin-dropdown-link:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .admin-dropdown-link--danger {
            color: #c0392b;
        }

        .admin-dropdown-link--danger:hover {
            background: #fff1f1;
            color: #a5281d;
        }

        .admin-menu-btn {
            display: none;
            border: none;
            background: #eef3ff;
            color: #173f84;
            width: 42px;
            height: 42px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .admin-nav-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 900;
            display: none;
        }

        .admin-nav-backdrop.show {
            display: block;
        }

        body.nav-lock {
            overflow: hidden;
        }

        @media (max-width: 900px) {
            .admin-title {
                display: none;
            }

            .admin-nav {
                position: fixed;
                top: 0;
                right: -100%;
                width: 280px;
                height: 100vh;
                background: #fff;
                z-index: 1001;
                flex-direction: column;
                align-items: stretch;
                padding: 5rem 1.3rem 1.3rem;
                transition: .3s ease;
                box-shadow: -12px 0 35px rgba(0,0,0,.16);
            }

            .admin-nav.is-open {
                right: 0;
            }

            .admin-nav a {
                border-radius: 12px;
                padding: .9rem 1rem;
            }

            .admin-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                position: relative;
                z-index: 1002;
            }

            .admin-home span {
                display: none;
            }
        }

        @media (max-width: 520px) {
            .admin-logo span {
                display: none;
            }

            .admin-nav-container {
                padding: .65rem .8rem;
            }
        }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="admin-nav-container">

        <a href="<?= $dashboardUrl ?>" class="admin-logo" aria-label="Uz admin paneli">
            <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            <span>Ceļa meklētāji</span>
        </a>

        <?php if (!empty($lapa)): ?>
            <div class="admin-title"><?= htmlspecialchars($lapa) ?></div>
        <?php endif; ?>

        <nav class="admin-nav" id="adminNav" aria-label="Admin navigācija">
            <a href="<?= $dashboardUrl ?>" class="<?= adminNavActive('/dashboards/admin.php', $currentPath) ?>">
                <i class="fas fa-gauge"></i> Dashboard
            </a>
            <a href="<?= $newsUrl ?>" class="<?= adminNavActive('/admin/news/', $currentPath) ?>">
                <i class="fas fa-newspaper"></i> Jaunumi
            </a>
            <a href="<?= $clubsUrl ?>" class="<?= adminNavActive('/admin/clubs/', $currentPath) ?>">
                <i class="fas fa-people-group"></i> Klubi
            </a>
            <a href="<?= $galleryUrl ?>" class="<?= adminNavActive('/admin/gallery/', $currentPath) ?>">
                <i class="fas fa-images"></i> Galerija
            </a>
            <a href="<?= $usersUrl ?>" class="<?= adminNavActive('/admin/users/', $currentPath) ?>">
                <i class="fas fa-users"></i> Lietotāji
            </a>
        </nav>

        <div class="admin-right">
            <a href="<?= $homeUrl ?>" class="admin-home">
                <i class="fas fa-house"></i>
                <span>Sākums</span>
            </a>

            <div class="admin-user-menu" id="adminUserMenu">
                <button class="admin-avatar-btn" id="adminAvatarBtn" type="button" aria-haspopup="true" aria-expanded="false">
                    <span class="admin-avatar"><?= htmlspecialchars($initials) ?></span>
                </button>

                <div class="admin-dropdown" id="adminDropdown">
                    <div class="admin-dropdown-head">
                        <div class="admin-dropdown-name"><?= htmlspecialchars($username) ?></div>
                        <div class="admin-dropdown-role"><?= htmlspecialchars($userRole) ?></div>
                    </div>

                    <a href="<?= $dashboardUrl ?>" class="admin-dropdown-link">
                        <i class="fas fa-gauge"></i>
                        <span>Admin panelis</span>
                    </a>

                    <a href="<?= $usersUrl ?>" class="admin-dropdown-link">
                        <i class="fas fa-users"></i>
                        <span>Lietotāji</span>
                    </a>

                    <a href="<?= $logoutUrl ?>" class="admin-dropdown-link admin-dropdown-link--danger">
                        <i class="fas fa-right-from-bracket"></i>
                        <span>Iziet</span>
                    </a>
                </div>
            </div>

            <button id="adminMenuBtn" class="admin-menu-btn" type="button" aria-label="Atvērt izvēlni" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>

    </div>
</header>

<div class="admin-nav-backdrop" id="adminNavBackdrop"></div>

<script>
(function () {
    const menuBtn = document.getElementById('adminMenuBtn');
    const nav = document.getElementById('adminNav');
    const backdrop = document.getElementById('adminNavBackdrop');

    function openMenu() {
        nav.classList.add('is-open');
        menuBtn.setAttribute('aria-expanded', 'true');
        menuBtn.innerHTML = '<i class="fas fa-xmark"></i>';
        backdrop.classList.add('show');
        document.body.classList.add('nav-lock');
    }

    function closeMenu() {
        nav.classList.remove('is-open');
        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
        backdrop.classList.remove('show');
        document.body.classList.remove('nav-lock');
    }

    if (menuBtn && nav && backdrop) {
        menuBtn.addEventListener('click', function () {
            nav.classList.contains('is-open') ? closeMenu() : openMenu();
        });

        backdrop.addEventListener('click', closeMenu);

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 900px)').matches) {
                    closeMenu();
                }
            });
        });
    }

    const avatarBtn = document.getElementById('adminAvatarBtn');
    const userMenu = document.getElementById('adminUserMenu');

    if (avatarBtn && userMenu) {
        avatarBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            const isOpen = userMenu.classList.toggle('open');
            avatarBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
        });

        document.addEventListener('click', function (e) {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.remove('open');
                avatarBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (nav && nav.classList.contains('is-open')) closeMenu();

            if (userMenu) {
                userMenu.classList.remove('open');
                avatarBtn.setAttribute('aria-expanded', 'false');
            }
        }
    });
})();
</script>
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

$currentPath = $_SERVER['PHP_SELF'] ?? '';

function directorNavActive(string $needle, string $currentPath): string
{
    return strpos($currentPath, $needle) !== false ? 'is-active' : '';
}

$dashboardUrl = BASE_URL . 'dashboards/director.php';
$clubUrl      = BASE_URL . 'director/club.php';
$childrenUrl  = BASE_URL . 'director/users.php?role=children';
$parentsUrl   = BASE_URL . 'director/users.php?role=parents';
$teachersUrl  = BASE_URL . 'director/users.php?role=teachers';
$addUserUrl   = BASE_URL . 'director/add_user.php';
$usersUrl     = BASE_URL . 'director/users.php';
$homeUrl      = BASE_URL . 'index.php';
$logoutUrl    = BASE_URL . 'auth/logout.php';
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Direktora panelis') ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        .director-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,0.92);
            backdrop-filter: blur(14px);
            border-bottom: 1px solid rgba(30,79,161,0.12);
            box-shadow: 0 4px 20px rgba(0,0,0,0.04);
        }

        .director-nav-container {
            max-width: 1180px;
            margin: 0 auto;
            padding: 0.75rem 1rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .director-logo {
            display: flex;
            align-items: center;
            gap: .7rem;
            text-decoration: none;
            color: #173f84;
            font-weight: 800;
            white-space: nowrap;
        }

        .director-logo img {
            width: 42px;
            height: 42px;
            object-fit: contain;
        }

        .director-logo span {
            font-size: 1.05rem;
        }

        .director-title {
            font-weight: 800;
            color: #1e4fa1;
            background: #eef3ff;
            padding: .45rem .85rem;
            border-radius: 999px;
            font-size: .95rem;
            white-space: nowrap;
        }

        .director-nav {
            display: flex;
            align-items: center;
            gap: .35rem;
        }

        .director-nav a {
            text-decoration: none;
            color: #263238;
            padding: .6rem .85rem;
            border-radius: 999px;
            font-weight: 700;
            transition: .2s ease;
            font-size: .95rem;
        }

        .director-nav a:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .director-nav a.is-active {
            background: #173f84;
            color: #fff;
            box-shadow: 0 6px 18px rgba(23,63,132,.22);
        }

        .director-right {
            display: flex;
            align-items: center;
            gap: .65rem;
            position: relative;
        }

        .director-home {
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

        .director-home:hover {
            transform: translateY(-1px);
            box-shadow: 0 8px 18px rgba(244,196,48,.28);
        }

        .director-user-menu {
            position: relative;
        }

        .director-avatar-btn {
            border: none;
            background: transparent;
            padding: 0;
            cursor: pointer;
        }

        .director-avatar {
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

        .director-dropdown {
            position: absolute;
            top: 55px;
            right: 0;
            width: 250px;
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 18px 45px rgba(0,0,0,.13);
            border: 1px solid #eef0f4;
            display: none;
            overflow: hidden;
        }

        .director-user-menu.open .director-dropdown {
            display: block;
        }

        .director-dropdown-head {
            padding: .9rem 1rem;
            background: #f7f9fc;
            border-bottom: 1px solid #edf0f5;
        }

        .director-dropdown-name {
            font-weight: 900;
            color: #173f84;
        }

        .director-dropdown-role {
            font-size: .85rem;
            color: #6b7280;
            margin-top: .15rem;
        }

        .director-dropdown-link {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .75rem 1rem;
            color: #263238;
            text-decoration: none;
            font-weight: 700;
            transition: .2s;
        }

        .director-dropdown-link:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .director-dropdown-link--danger {
            color: #c0392b;
        }

        .director-dropdown-link--danger:hover {
            background: #fff1f1;
            color: #a5281d;
        }

        .director-menu-btn {
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

        .director-nav-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(0,0,0,.45);
            z-index: 900;
            display: none;
        }

        .director-nav-backdrop.show {
            display: block;
        }

        body.nav-lock {
            overflow: hidden;
        }

        @media (max-width: 1000px) {
            .director-title {
                display: none;
            }

            .director-nav {
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

            .director-nav.is-open {
                right: 0;
            }

            .director-nav a {
                border-radius: 12px;
                padding: .9rem 1rem;
            }

            .director-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                position: relative;
                z-index: 1002;
            }

            .director-home span {
                display: none;
            }
        }

        @media (max-width: 520px) {
            .director-logo span {
                display: none;
            }

            .director-nav-container {
                padding: .65rem .8rem;
            }
        }
    </style>
</head>
<body>

<header class="director-header">
    <div class="director-nav-container">

        <a href="<?= $dashboardUrl ?>" class="director-logo" aria-label="Uz direktora paneli">
            <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            <span>Ceļa meklētāji</span>
        </a>

        <?php if (!empty($lapa)): ?>
            <div class="director-title"><?= htmlspecialchars($lapa) ?></div>
        <?php endif; ?>

        <nav class="director-nav" id="directorNav" aria-label="Direktora navigācija">
            <a href="<?= $dashboardUrl ?>" class="<?= directorNavActive('/dashboards/director.php', $currentPath) ?>">
                <i class="fas fa-gauge"></i> Panelis
            </a>

            <a href="<?= $clubUrl ?>" class="<?= directorNavActive('/director/club.php', $currentPath) ?>">
                <i class="fas fa-people-roof"></i> Mans klubs
            </a>

            <a href="<?= $childrenUrl ?>" class="<?= directorNavActive('/director/users.php', $currentPath) ?>">
                <i class="fas fa-child-reaching"></i> Lietotāji
            </a>

            <a href="<?= $addUserUrl ?>" class="<?= directorNavActive('/director/add_user.php', $currentPath) ?>">
                <i class="fas fa-user-plus"></i> Pievienot
            </a>
        </nav>

        <div class="director-right">
            <a href="<?= $homeUrl ?>" class="director-home">
                <i class="fas fa-house"></i>
                <span>Sākums</span>
            </a>

            <div class="director-user-menu" id="directorUserMenu">
                <button class="director-avatar-btn" id="directorAvatarBtn" type="button" aria-haspopup="true" aria-expanded="false">
                    <span class="director-avatar"><?= htmlspecialchars($initials) ?></span>
                </button>

                <div class="director-dropdown" id="directorDropdown">
                    <div class="director-dropdown-head">
                        <div class="director-dropdown-name"><?= htmlspecialchars($username) ?></div>
                        <div class="director-dropdown-role"><?= htmlspecialchars($userRole) ?></div>
                    </div>

                    <a href="<?= $dashboardUrl ?>" class="director-dropdown-link">
                        <i class="fas fa-gauge"></i>
                        <span>Direktora panelis</span>
                    </a>

                    <a href="<?= $clubUrl ?>" class="director-dropdown-link">
                        <i class="fas fa-people-roof"></i>
                        <span>Mans klubs</span>
                    </a>

                    <a href="<?= $childrenUrl ?>" class="director-dropdown-link">
                        <i class="fas fa-child-reaching"></i>
                        <span>Bērni</span>
                    </a>

                    <a href="<?= $parentsUrl ?>" class="director-dropdown-link">
                        <i class="fas fa-users"></i>
                        <span>Vecāki</span>
                    </a>

                    <a href="<?= $teachersUrl ?>" class="director-dropdown-link">
                        <i class="fas fa-chalkboard-user"></i>
                        <span>Skolotāji</span>
                    </a>

                    <a href="<?= $logoutUrl ?>" class="director-dropdown-link director-dropdown-link--danger">
                        <i class="fas fa-right-from-bracket"></i>
                        <span>Iziet</span>
                    </a>
                </div>
            </div>

            <button id="directorMenuBtn" class="director-menu-btn" type="button" aria-label="Atvērt izvēlni" aria-expanded="false">
                <i class="fas fa-bars"></i>
            </button>
        </div>

    </div>
</header>

<div class="director-nav-backdrop" id="directorNavBackdrop"></div>

<script>
(function () {
    const menuBtn = document.getElementById('directorMenuBtn');
    const nav = document.getElementById('directorNav');
    const backdrop = document.getElementById('directorNavBackdrop');

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
                if (window.matchMedia('(max-width: 1000px)').matches) {
                    closeMenu();
                }
            });
        });
    }

    const avatarBtn = document.getElementById('directorAvatarBtn');
    const userMenu = document.getElementById('directorUserMenu');

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
            if (nav && nav.classList.contains('is-open')) {
                closeMenu();
            }

            if (userMenu) {
                userMenu.classList.remove('open');

                if (avatarBtn) {
                    avatarBtn.setAttribute('aria-expanded', 'false');
                }
            }
        }
    });
})();
</script>
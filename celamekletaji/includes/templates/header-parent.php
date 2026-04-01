<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

// Drošība: tikai vecākam
if (!isset($_SESSION["lietotajs_id"]) || !in_array(($_SESSION["loma"] ?? ''), ['Vecāks', 'parent'], true)) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$username = trim($_SESSION["lietotajvards"] ?? 'Vecāks');
$userRole = trim($_SESSION["loma"] ?? 'Vecāks');

// Iniciāļi avataram
$initials = 'V';
if ($username !== '') {
    $parts = preg_split('/\s+/', $username);
    if (!empty($parts[0])) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
        if (isset($parts[1]) && $parts[1] !== '') {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }
    }
}

// Aktīvā lapa
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'parent.php');

function parentNavActive(array $pages, string $currentPage): string
{
    return in_array($currentPage, $pages, true) ? 'is-active' : '';
}

// Saistes
$dashboardUrl    = BASE_URL . 'dashboards/parent.php';
$childrenUrl     = BASE_URL . 'dashboards/parent-children.php';
$activitiesUrl   = BASE_URL . 'dashboards/parent-activities.php';
$paymentsUrl     = BASE_URL . 'dashboards/parent-payments.php';
$notificationsUrl= BASE_URL . 'dashboards/parent-notifications.php';
$profileUrl      = BASE_URL . 'dashboards/parent-profile.php';
$logoutUrl       = BASE_URL . 'auth/logout.php';
$homeUrl         = BASE_URL . 'index.php';
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Vecāku panelis - Ceļa meklētāji') ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <style>
        .parent-header {
            position: sticky;
            top: 0;
            z-index: 1000;
            background: rgba(255,255,255,.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(23, 63, 132, 0.10);
            box-shadow: 0 10px 30px rgba(16, 24, 40, 0.06);
        }

        .parent-nav-container {
            min-height: 78px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .parent-brand {
            display: flex;
            align-items: center;
            gap: .85rem;
            text-decoration: none;
            color: #173f84;
            min-width: 0;
        }

        .parent-brand img {
            width: 44px;
            height: 44px;
            object-fit: contain;
            flex-shrink: 0;
        }

        .parent-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .parent-brand-text strong {
            font-size: 1rem;
            color: #173f84;
        }

        .parent-brand-text span {
            font-size: .84rem;
            color: #5b6475;
        }

        .parent-nav {
            display: flex;
            align-items: center;
            gap: .35rem;
            flex-wrap: wrap;
        }

        .parent-nav a {
            text-decoration: none;
            color: #24324a;
            font-weight: 600;
            padding: .7rem .95rem;
            border-radius: 999px;
            transition: all .2s ease;
        }

        .parent-nav a:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .parent-nav a.is-active {
            background: linear-gradient(135deg, #1e4fa1, #173f84);
            color: #fff;
            box-shadow: 0 8px 18px rgba(23, 63, 132, 0.22);
        }

        .parent-right {
            display: flex;
            align-items: center;
            gap: .75rem;
            position: relative;
        }

        .parent-quick-home {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            text-decoration: none;
            color: #173f84;
            background: #edf3ff;
            padding: .65rem .9rem;
            border-radius: 999px;
            font-weight: 700;
            transition: .2s ease;
        }

        .parent-quick-home:hover {
            background: #dfe9ff;
        }

        .parent-avatar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: .55rem;
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 0;
        }

        .parent-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f4c430, #e1aa16);
            color: #173f84;
            font-weight: 800;
            box-shadow: 0 8px 18px rgba(244, 196, 48, 0.28);
        }

        .parent-dropdown {
            position: absolute;
            top: calc(100% + 10px);
            right: 0;
            width: 280px;
            background: #fff;
            border: 1px solid rgba(23, 63, 132, 0.10);
            border-radius: 18px;
            box-shadow: 0 20px 50px rgba(15, 23, 42, 0.14);
            padding: .75rem;
            display: none;
        }

        .parent-user-menu.open .parent-dropdown {
            display: block;
        }

        .parent-dropdown-head {
            padding: .75rem;
            border-bottom: 1px solid #eef1f6;
            margin-bottom: .45rem;
        }

        .parent-dropdown-name {
            font-weight: 800;
            color: #173f84;
            margin-bottom: .2rem;
        }

        .parent-dropdown-role {
            font-size: .9rem;
            color: #697386;
        }

        .parent-dropdown-link {
            display: flex;
            align-items: center;
            gap: .7rem;
            text-decoration: none;
            color: #24324a;
            padding: .8rem .75rem;
            border-radius: 12px;
            transition: .2s ease;
        }

        .parent-dropdown-link:hover {
            background: #f6f8fc;
        }

        .parent-dropdown-link--danger {
            color: #b42318;
        }

        .parent-menu-btn {
            display: none;
            border: none;
            background: #173f84;
            color: #fff;
            width: 42px;
            height: 42px;
            border-radius: 12px;
            cursor: pointer;
        }

        .parent-mobile-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.4);
            z-index: 998;
            opacity: 0;
            pointer-events: none;
            transition: .2s ease;
        }

        .parent-mobile-backdrop.show {
            opacity: 1;
            pointer-events: auto;
        }

        @media (max-width: 980px) {
            .parent-menu-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
            }

            .parent-nav {
                position: fixed;
                top: 0;
                right: 0;
                height: 100vh;
                width: min(86vw, 340px);
                background: #ffffff;
                box-shadow: -20px 0 50px rgba(0,0,0,.12);
                padding: 6rem 1rem 1.25rem;
                flex-direction: column;
                align-items: stretch;
                gap: .45rem;
                transform: translateX(100%);
                transition: transform .25s ease;
                z-index: 999;
                flex-wrap: nowrap;
            }

            .parent-nav.is-open {
                transform: translateX(0);
            }

            .parent-nav a {
                border-radius: 14px;
                padding: .95rem 1rem;
            }

            .parent-quick-home {
                display: none;
            }

            body.nav-lock {
                overflow: hidden;
            }
        }

        @media (max-width: 640px) {
            .parent-brand-text span {
                display: none;
            }
        }
    </style>
</head>
<body>

<header class="parent-header">
    <div class="container parent-nav-container">
        <a href="<?= $dashboardUrl ?>" class="parent-brand" aria-label="Uz vecāku paneli">
            <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            <div class="parent-brand-text">
                <strong>Ceļa meklētāji</strong>
                <span><?= htmlspecialchars($lapa ?? 'Vecāku panelis') ?></span>
            </div>
        </a>

        <nav class="parent-nav" id="parentNav" aria-label="Vecāku navigācija">
            <a href="<?= $dashboardUrl ?>" class="<?= parentNavActive(['parent.php'], $currentPage) ?>">
                <i class="fas fa-house"></i> Pārskats
            </a>
            <a href="<?= $childrenUrl ?>" class="<?= parentNavActive(['parent-children.php'], $currentPage) ?>">
                <i class="fas fa-children"></i> Mani bērni
            </a>
            <a href="<?= $activitiesUrl ?>" class="<?= parentNavActive(['parent-activities.php'], $currentPage) ?>">
                <i class="fas fa-calendar-check"></i> Aktivitātes
            </a>
            <a href="<?= $paymentsUrl ?>" class="<?= parentNavActive(['parent-payments.php'], $currentPage) ?>">
                <i class="fas fa-credit-card"></i> Maksājumi
            </a>
            <a href="<?= $notificationsUrl ?>" class="<?= parentNavActive(['parent-notifications.php'], $currentPage) ?>">
                <i class="fas fa-bell"></i> Paziņojumi
            </a>
        </nav>

        <div class="parent-right">
            <a href="<?= $homeUrl ?>" class="parent-quick-home">
                <i class="fas fa-arrow-left"></i>
                <span>Uz sākumlapu</span>
            </a>

            <div class="parent-user-menu" id="parentUserMenu">
                <button
                    class="parent-avatar-btn"
                    id="parentAvatarBtn"
                    type="button"
                    aria-haspopup="true"
                    aria-expanded="false"
                    aria-label="Lietotāja izvēlne"
                >
                    <span class="parent-avatar"><?= htmlspecialchars($initials) ?></span>
                </button>

                <div class="parent-dropdown" id="parentDropdown">
                    <div class="parent-dropdown-head">
                        <div class="parent-dropdown-name"><?= htmlspecialchars($username) ?></div>
                        <div class="parent-dropdown-role"><?= htmlspecialchars($userRole) ?></div>
                    </div>

                    <a href="<?= $profileUrl ?>" class="parent-dropdown-link">
                        <i class="fas fa-user-gear"></i>
                        <span>Mans profils</span>
                    </a>

                    <a href="<?= $childrenUrl ?>" class="parent-dropdown-link">
                        <i class="fas fa-children"></i>
                        <span>Mani bērni</span>
                    </a>

                    <a href="<?= $paymentsUrl ?>" class="parent-dropdown-link">
                        <i class="fas fa-wallet"></i>
                        <span>Maksājumi</span>
                    </a>

                    <a href="<?= $logoutUrl ?>" class="parent-dropdown-link parent-dropdown-link--danger">
                        <i class="fas fa-right-from-bracket"></i>
                        <span>Iziet</span>
                    </a>
                </div>
            </div>

            <button
                id="parentMenuBtn"
                class="parent-menu-btn"
                type="button"
                aria-label="Atvērt izvēlni"
                aria-controls="parentNav"
                aria-expanded="false"
            >
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </div>
</header>

<div class="parent-mobile-backdrop" id="parentNavBackdrop"></div>

<script>
(function () {
    const menuBtn = document.getElementById('parentMenuBtn');
    const nav = document.getElementById('parentNav');
    const backdrop = document.getElementById('parentNavBackdrop');

    function openMenu() {
        nav.classList.add('is-open');
        backdrop.classList.add('show');
        document.body.classList.add('nav-lock');
        menuBtn.setAttribute('aria-expanded', 'true');
        menuBtn.innerHTML = '<i class="fas fa-xmark"></i>';
    }

    function closeMenu() {
        nav.classList.remove('is-open');
        backdrop.classList.remove('show');
        document.body.classList.remove('nav-lock');
        menuBtn.setAttribute('aria-expanded', 'false');
        menuBtn.innerHTML = '<i class="fas fa-bars"></i>';
    }

    if (menuBtn && nav) {
        menuBtn.addEventListener('click', function () {
            const isOpen = nav.classList.contains('is-open');
            isOpen ? closeMenu() : openMenu();
        });

        backdrop.addEventListener('click', closeMenu);

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeMenu();
            }
        });

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 980px)').matches) {
                    closeMenu();
                }
            });
        });

        window.addEventListener('resize', function () {
            if (!window.matchMedia('(max-width: 980px)').matches) {
                closeMenu();
            }
        });
    }

    const avatarBtn = document.getElementById('parentAvatarBtn');
    const userMenu = document.getElementById('parentUserMenu');

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

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                userMenu.classList.remove('open');
                avatarBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }
})();
</script>
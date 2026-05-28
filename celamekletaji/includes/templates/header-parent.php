<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

/* ===============================
   DROŠĪBA: TIKAI VECĀKAM
================================ */
if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ''), ['Vecāks', 'parent'], true)
) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$username = trim($_SESSION["lietotajvards"] ?? 'Vecāks');
$userRole = trim($_SESSION["loma"] ?? 'Vecāks');

/* ===============================
   INICIĀĻI AVATARAM
================================ */
$initials = 'V';

if ($username !== '') {
    $parts = preg_split('/\s+/', $username);

    if (!empty($parts[0])) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));

        if (!empty($parts[1])) {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }
    }
}

/* ===============================
   AKTĪVĀ LAPA
================================ */
$currentPage = basename($_SERVER['PHP_SELF'] ?? 'parent.php');

function parentNavActive(array $pages, string $currentPage): string
{
    return in_array($currentPage, $pages, true) ? 'is-active' : '';
}

/* ===============================
   SAITES
================================ */
$dashboardUrl           = BASE_URL . 'dashboards/parent.php';
$childrenUrl            = BASE_URL . 'parent/children/manage.php';

/*
   Pieteiktās aktivitātes = aktivitātes, kur bērni jau ir pieteikti
   Pieejamās aktivitātes = visas aktivitātes, uz kurām var pieteikties
*/
$signedActivitiesUrl    = BASE_URL . 'parent/activities.php';
$availableActivitiesUrl = BASE_URL . 'parent/available-activities.php';

$paymentsUrl            = BASE_URL . 'parent/payments.php';
$profileUrl             = BASE_URL . 'parent/profile.php';
$logoutUrl              = BASE_URL . 'auth/logout.php';
$homeUrl                = BASE_URL . 'auth/logout.php?redirect=home';
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
            z-index: 1500;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(23, 63, 132, 0.10);
            box-shadow: 0 10px 30px rgba(16, 24, 40, 0.05);
        }

        .parent-nav-container {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .parent-brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            text-decoration: none;
            color: #173f84;
            min-width: 0;
            font-weight: 1000;
        }

        .parent-brand-logo {
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

        .parent-brand-logo img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .parent-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
            min-width: 0;
        }

        .parent-brand-text strong {
            font-size: 1.05rem;
            color: #173f84;
            white-space: nowrap;
        }

        .parent-brand-text span {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .82rem;
            color: #667085;
            font-weight: 800;
            margin-top: .12rem;
        }

        .parent-nav {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-left: auto;
        }

        .parent-nav a {
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
        }

        .parent-nav a i {
            color: #1e4fa1;
        }

        .parent-nav a:hover {
            background: #eef3ff;
            color: #173f84;
            transform: translateY(-1px);
        }

        .parent-nav a.is-active {
            background: linear-gradient(135deg, #1e4fa1, #173f84);
            color: #fff;
            box-shadow: 0 12px 26px rgba(23, 63, 132, 0.18);
        }

        .parent-nav a.is-active i {
            color: #f4c430;
        }

        .parent-right {
            display: flex;
            align-items: center;
            gap: .7rem;
            position: relative;
        }

        .parent-quick-home {
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

        .parent-quick-home:hover {
            background: #dfeaff;
            transform: translateY(-1px);
        }

        .parent-user-menu {
            position: relative;
        }

        .parent-avatar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 0;
        }

        .parent-avatar {
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

        .parent-avatar-btn:hover .parent-avatar {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(244, 196, 48, 0.32);
        }

        .parent-dropdown {
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

        .parent-user-menu.open .parent-dropdown {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .parent-dropdown-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem;
            border-radius: 1rem;
            background: #f8fbff;
            margin-bottom: .5rem;
        }

        .parent-dropdown-avatar {
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

        .parent-dropdown-name {
            font-weight: 1000;
            color: #101828;
            line-height: 1.2;
        }

        .parent-dropdown-role {
            font-size: .86rem;
            color: #667085;
            font-weight: 800;
            margin-top: .1rem;
        }

        .parent-dropdown-link {
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

        .parent-dropdown-link i {
            width: 20px;
            color: #1e4fa1;
            text-align: center;
        }

        .parent-dropdown-link:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .parent-dropdown-link--danger {
            color: #b42318;
        }

        .parent-dropdown-link--danger i {
            color: #b42318;
        }

        .parent-dropdown-link--danger:hover {
            background: #fff0f0;
            color: #b42318;
        }

        .parent-menu-btn {
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

        .parent-mobile-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            z-index: 1200;
            opacity: 0;
            visibility: hidden;
            transition: .2s ease;
        }

        .parent-mobile-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 1060px) {
            .parent-brand-text span {
                display: none;
            }

            .parent-quick-home span {
                display: none;
            }
        }

        @media (max-width: 980px) {
            .parent-menu-btn {
                display: grid;
                place-items: center;
            }

            .parent-nav {
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

            .parent-nav.is-open {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .parent-nav a {
                justify-content: flex-start;
                border-radius: 1rem;
                padding: .95rem 1rem;
            }

            body.nav-lock {
                overflow: hidden;
            }
        }

        @media (max-width: 560px) {
            .parent-nav-container {
                min-height: 68px;
            }

            .parent-brand-text {
                display: none;
            }

            .parent-brand-logo {
                width: 44px;
                height: 44px;
            }

            .parent-quick-home {
                display: none;
            }

            .parent-dropdown {
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

<header class="parent-header">
    <div class="container parent-nav-container">

        <a href="<?= $dashboardUrl ?>" class="parent-brand">
            <span class="parent-brand-logo">
                <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            </span>

            <span class="parent-brand-text">
                <strong>Vecāku panelis</strong>
                <span><?= htmlspecialchars($lapa ?? 'Ceļa meklētāji') ?></span>
            </span>
        </a>

        <nav class="parent-nav" id="parentNav" aria-label="Vecāku navigācija">

            <a href="<?= $dashboardUrl ?>" class="<?= parentNavActive(['parent.php'], $currentPage) ?>">
                <i class="fas fa-chart-line"></i>
                <span>Pārskats</span>
            </a>

            <a href="<?= $childrenUrl ?>" class="<?= parentNavActive(['manage.php'], $currentPage) ?>">
                <i class="fas fa-children"></i>
                <span>Mani bērni</span>
            </a>

                    <a href="<?= $signedActivitiesUrl ?>" class="<?= parentNavActive(['activities.php'], $currentPage) ?>">
                <i class="fas fa-calendar-check"></i>
                <span>Pieteiktās aktivitātes</span>
            </a>

            <a href="<?= $availableActivitiesUrl ?>" class="<?= parentNavActive(['available-activities.php'], $currentPage) ?>">
                <i class="fas fa-list-check"></i>
                <span>Pieejamās aktivitātes</span>
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
                    aria-label="Atvērt lietotāja izvēlni"
                    aria-expanded="false"
                >
                    <span class="parent-avatar">
                        <?= htmlspecialchars($initials) ?>
                    </span>
                </button>

                <div class="parent-dropdown">
                    <div class="parent-dropdown-head">
                        <span class="parent-dropdown-avatar">
                            <?= htmlspecialchars($initials) ?>
                        </span>

                        <div>
                            <div class="parent-dropdown-name">
                                <?= htmlspecialchars($username) ?>
                            </div>

                            <div class="parent-dropdown-role">
                                <?= htmlspecialchars($userRole) ?>
                            </div>
                        </div>
                    </div>

                    <a href="<?= $profileUrl ?>" class="parent-dropdown-link">
                        <i class="fas fa-user-gear"></i>
                        <span>Mans profils</span>
                    </a>

                    <a href="<?= $childrenUrl ?>" class="parent-dropdown-link">
                        <i class="fas fa-children"></i>
                        <span>Mani bērni</span>
                    </a>

                    <a href="<?= $signedActivitiesUrl ?>" class="parent-dropdown-link">
                        <i class="fas fa-calendar-check"></i>
                        <span>Pieteiktās aktivitātes</span>
                    </a>

                    <a href="<?= $availableActivitiesUrl ?>" class="parent-dropdown-link">
                        <i class="fas fa-list-check"></i>
                        <span>Pieejamās aktivitātes</span>
                    </a>

                    <a href="<?= $homeUrl ?>" class="parent-dropdown-link">
                        <i class="fas fa-house"></i>
                        <span>Uz sākumlapu</span>
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

    const avatarBtn = document.getElementById('parentAvatarBtn');
    const userMenu = document.getElementById('parentUserMenu');

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
    }

    function closeUserMenu() {
        if (!avatarBtn || !userMenu) return;

        userMenu.classList.remove('open');
        avatarBtn.setAttribute('aria-expanded', 'false');
    }

    function toggleUserMenu(event) {
        if (!avatarBtn || !userMenu) return;

        event.stopPropagation();

        const isOpen = userMenu.classList.toggle('open');
        avatarBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        closeMenu();
    }

    menuBtn?.addEventListener('click', function () {
        nav.classList.contains('is-open') ? closeMenu() : openMenu();
    });

    backdrop?.addEventListener('click', function () {
        closeMenu();
        closeUserMenu();
    });

    avatarBtn?.addEventListener('click', toggleUserMenu);

    nav?.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            closeMenu();
        });
    });

    document.addEventListener('click', function (event) {
        if (userMenu && !userMenu.contains(event.target)) {
            closeUserMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
            closeUserMenu();
        }
    });
})();
</script>
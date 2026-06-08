<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

/* ===============================
   PIEKĻUVE TIKAI ADMINAM
================================ */
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$username = trim($_SESSION["lietotajvards"] ?? 'Administrators');
$userRole = trim($_SESSION["loma"] ?? 'admin');

/* ===============================
   INICIĀĻI
================================ */
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

/* ===============================
   AKTĪVĀ SADAĻA
================================ */
$currentPath = $_SERVER['PHP_SELF'] ?? '';

function adminNavActive(string $needle, string $currentPath): string
{
    return strpos($currentPath, $needle) !== false ? 'is-active' : '';
}

/* ===============================
   URL
================================ */
$dashboardUrl = BASE_URL . 'dashboards/admin.php';
$newsUrl      = BASE_URL . 'admin/news/news.php';
$clubsUrl     = BASE_URL . 'admin/clubs/clubs.php';
$galleryUrl   = BASE_URL . 'admin/gallery/gallery.php';
$usersUrl     = BASE_URL . 'admin/users/users_manage.php';
$profileUrl   = BASE_URL . 'profile.php';
$homeUrl      = BASE_URL . 'auth/logout.php?redirect=home';
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
            z-index: 1500;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(23, 63, 132, 0.10);
            box-shadow: 0 10px 30px rgba(16, 24, 40, 0.05);
        }

        .admin-container {
            max-width: 1280px;
            margin: 0 auto;
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
            padding: 0 1.25rem;
        }

        .admin-logo {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            text-decoration: none;
            color: #173f84;
            font-weight: 1000;
            letter-spacing: -0.03em;
            white-space: nowrap;
        }

        .admin-logo-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 1rem;
            background: linear-gradient(135deg, #173f84, #1e4fa1);
            box-shadow: 0 12px 28px rgba(23, 63, 132, 0.18);
            overflow: hidden;
        }

        .admin-logo-icon img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .admin-logo-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
        }

        .admin-logo-text strong {
            font-size: 1.05rem;
        }

        .admin-logo-text small {
            color: #667085;
            font-weight: 800;
            font-size: .78rem;
            letter-spacing: 0;
        }

        .admin-nav {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-left: auto;
        }

        .admin-nav a {
            display: inline-flex;
            align-items: center;
            gap: .5rem;
            text-decoration: none;
            padding: .7rem .9rem;
            border-radius: 999px;
            font-weight: 900;
            color: #344054;
            transition: .2s ease;
        }

        .admin-nav a i {
            color: #1e4fa1;
        }

        .admin-nav a:hover {
            background: #eef3ff;
            color: #173f84;
            transform: translateY(-1px);
        }

        .admin-nav a.is-active {
            background: #173f84;
            color: #fff;
            box-shadow: 0 12px 26px rgba(23, 63, 132, 0.18);
        }

        .admin-nav a.is-active i {
            color: #f4c430;
        }

        .admin-right {
            display: flex;
            align-items: center;
            gap: .7rem;
            position: relative;
        }

        .admin-home {
            display: inline-flex;
            align-items: center;
            gap: .45rem;
            text-decoration: none;
            background: #eef3ff;
            padding: .68rem .9rem;
            border-radius: 999px;
            color: #173f84;
            font-weight: 900;
            transition: .2s ease;
        }

        .admin-home:hover {
            background: #dfeaff;
            transform: translateY(-1px);
        }

        .admin-avatar {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: linear-gradient(135deg, #173f84, #1e4fa1);
            color: #fff;
            display: grid;
            place-items: center;
            font-weight: 1000;
            cursor: pointer;
            border: 2px solid rgba(244, 196, 48, 0.65);
            box-shadow: 0 10px 24px rgba(23, 63, 132, 0.18);
            transition: .2s ease;
        }

        .admin-avatar:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(23, 63, 132, 0.24);
        }

        .admin-dropdown {
            position: absolute;
            top: calc(100% + .8rem);
            right: 0;
            width: 260px;
            background: #fff;
            border: 1px solid rgba(23, 63, 132, 0.10);
            border-radius: 1.25rem;
            box-shadow: 0 24px 65px rgba(16, 24, 40, 0.16);
            display: none;
            padding: .75rem;
        }

        .admin-dropdown.show {
            display: block;
        }

        .admin-dropdown-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .7rem;
            border-radius: 1rem;
            background: #f8fbff;
            margin-bottom: .5rem;
        }

        .admin-dropdown-avatar {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            display: grid;
            place-items: center;
            background: #173f84;
            color: #f4c430;
            font-weight: 1000;
        }

        .admin-dropdown-name {
            font-weight: 1000;
            color: #101828;
        }

        .admin-dropdown-role {
            color: #667085;
            font-size: .86rem;
            font-weight: 800;
            margin-top: .1rem;
        }

        .admin-dropdown a {
            display: flex;
            align-items: center;
            gap: .65rem;
            padding: .78rem .85rem;
            text-decoration: none;
            color: #344054;
            border-radius: .9rem;
            font-weight: 900;
            transition: .2s ease;
        }

        .admin-dropdown a i {
            width: 20px;
            color: #1e4fa1;
            text-align: center;
        }

        .admin-dropdown a:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .admin-dropdown a.danger {
            color: #c62828;
        }

        .admin-dropdown a.danger i {
            color: #c62828;
        }

        .admin-dropdown a.danger:hover {
            background: #fff0f0;
        }

        .admin-menu-btn {
            display: none;
            width: 44px;
            height: 44px;
            border: none;
            border-radius: 1rem;
            background: #173f84;
            color: #f4c430;
            cursor: pointer;
            font-size: 1.1rem;
        }

        .admin-backdrop {
            position: fixed;
            inset: 0;
            z-index: 1200;
            background: rgba(0,0,0,.38);
            opacity: 0;
            visibility: hidden;
            transition: .2s ease;
        }

        .admin-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 980px) {
            .admin-logo-text small {
                display: none;
            }

            .admin-home span {
                display: none;
            }
        }

        @media (max-width: 900px) {
            .admin-nav {
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

            .admin-nav.open {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .admin-nav a {
                justify-content: flex-start;
                border-radius: 1rem;
                padding: .95rem 1rem;
            }

            .admin-menu-btn {
                display: grid;
                place-items: center;
            }
        }

        @media (max-width: 520px) {
            .admin-logo-text {
                display: none;
            }

            .admin-container {
                min-height: 68px;
            }

            .admin-logo-icon {
                width: 44px;
                height: 44px;
            }

            .admin-dropdown {
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

<header class="admin-header">
    <div class="admin-container">

        <a href="<?= $dashboardUrl ?>" class="admin-logo">
            <span class="admin-logo-icon">
                <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            </span>

            <span class="admin-logo-text">
                <strong>Admin panelis</strong>
                <small>Ceļa meklētāji</small>
            </span>
        </a>

        <nav class="admin-nav" id="adminNav" aria-label="Admin navigācija">
            <a href="<?= $dashboardUrl ?>" class="<?= adminNavActive('/dashboards/admin.php', $currentPath) ?>">
                <i class="fas fa-gauge"></i>
                Dashboard
            </a>

            <a href="<?= $newsUrl ?>" class="<?= adminNavActive('/admin/news/', $currentPath) ?>">
                <i class="fas fa-newspaper"></i>
                Jaunumi
            </a>

            <a href="<?= $clubsUrl ?>" class="<?= adminNavActive('/admin/clubs/', $currentPath) ?>">
                <i class="fas fa-location-dot"></i>
                Klubi
            </a>

            <a href="<?= $galleryUrl ?>" class="<?= adminNavActive('/admin/gallery/', $currentPath) ?>">
                <i class="fas fa-images"></i>
                Galerija
            </a>

            <a href="<?= $usersUrl ?>" class="<?= adminNavActive('/admin/users/', $currentPath) ?>">
                <i class="fas fa-users-gear"></i>
                Lietotāji
            </a>
        </nav>

        <div class="admin-right">
            <a href="<?= $homeUrl ?>" class="admin-home">
                <i class="fas fa-house"></i>
                <span>Sākums</span>
            </a>

            <button class="admin-avatar" id="avatarBtn" type="button" aria-label="Atvērt admin izvēlni">
                <?= htmlspecialchars($initials) ?>
            </button>

            <div class="admin-dropdown" id="dropdown">
                <div class="admin-dropdown-head">
                    <span class="admin-dropdown-avatar">
                        <?= htmlspecialchars($initials) ?>
                    </span>

                    <div>
                        <div class="admin-dropdown-name">
                            <?= htmlspecialchars($username) ?>
                        </div>

                        <div class="admin-dropdown-role">
                            <?= htmlspecialchars($userRole) ?>
                        </div>
                    </div>
                </div>

                <a href="<?= $dashboardUrl ?>">
                    <i class="fas fa-gauge"></i>
                    Panelis
                </a>

                <a href="<?= $usersUrl ?>">
                    <i class="fas fa-users"></i>
                    Lietotāji
                </a>

                <a href="<?= $profileUrl ?>">
                    <i class="fas fa-user-pen"></i>
                    Profils
                </a>

                <a href="<?= $logoutUrl ?>" class="danger">
                    <i class="fas fa-right-from-bracket"></i>
                    Iziet
                </a>
            </div>

            <button class="admin-menu-btn" id="menuBtn" type="button" aria-label="Atvērt izvēlni">
                <i class="fas fa-bars"></i>
            </button>
        </div>

    </div>
</header>

<div class="admin-backdrop" id="adminBackdrop"></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const avatar = document.getElementById('avatarBtn');
    const dropdown = document.getElementById('dropdown');
    const btn = document.getElementById('menuBtn');
    const nav = document.getElementById('adminNav');
    const backdrop = document.getElementById('adminBackdrop');

    function closeAdminNav() {
        if (!nav || !btn || !backdrop) return;

        nav.classList.remove('open');
        backdrop.classList.remove('show');
        btn.innerHTML = '<i class="fas fa-bars"></i>';
        btn.setAttribute('aria-label', 'Atvērt izvēlni');
    }

    if (avatar && dropdown) {
        avatar.addEventListener('click', function (e) {
            e.stopPropagation();
            dropdown.classList.toggle('show');
        });

        document.addEventListener('click', function (e) {
            if (!avatar.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
    }

    if (btn && nav && backdrop) {
        btn.addEventListener('click', function () {
            const isOpen = nav.classList.toggle('open');

            backdrop.classList.toggle('show', isOpen);
            btn.innerHTML = isOpen
                ? '<i class="fas fa-xmark"></i>'
                : '<i class="fas fa-bars"></i>';

            btn.setAttribute('aria-label', isOpen ? 'Aizvērt izvēlni' : 'Atvērt izvēlni');
        });

        backdrop.addEventListener('click', closeAdminNav);

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeAdminNav);
        });
    }

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            if (dropdown) dropdown.classList.remove('show');
            closeAdminNav();
        }
    });
});
</script>
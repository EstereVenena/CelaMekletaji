<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

// Piekļuve tikai adminam
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? '') !== 'admin') {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$username = trim($_SESSION["lietotajvards"] ?? 'Administrators');
$userRole = trim($_SESSION["loma"] ?? 'admin');

// Iniciāļi
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

// Aktīvā sadaļa
$currentPath = $_SERVER['PHP_SELF'] ?? '';

function adminNavActive(string $needle, string $currentPath): string
{
    return strpos($currentPath, $needle) !== false ? 'is-active' : '';
}

// URL
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
            background: #ffffffee;
            backdrop-filter: blur(10px);
            border-bottom: 1px solid #e5e7eb;
        }

        .admin-container {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: .8rem 1rem;
            gap: 1rem;
        }

        .admin-logo {
            display: flex;
            align-items: center;
            gap: .6rem;
            text-decoration: none;
            color: #173f84;
            font-weight: bold;
        }

        .admin-logo img {
            width: 40px;
            height: 40px;
            object-fit: contain;
        }

        .admin-nav {
            display: flex;
            gap: .5rem;
        }

        .admin-nav a {
            text-decoration: none;
            padding: .6rem .9rem;
            border-radius: 999px;
            font-weight: 600;
            color: #333;
            transition: .2s;
        }

        .admin-nav a:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .admin-nav a.is-active {
            background: #173f84;
            color: #fff;
        }

        .admin-right {
            display: flex;
            align-items: center;
            gap: .6rem;
            position: relative;
        }

        .admin-home {
            text-decoration: none;
            background: #eef3ff;
            padding: .5rem .8rem;
            border-radius: 999px;
            color: #173f84;
            font-weight: 600;
        }

        .admin-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #173f84;
            color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            cursor: pointer;
            border: none;
        }

        .admin-dropdown {
            position: absolute;
            top: 55px;
            right: 0;
            width: 220px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,.1);
            display: none;
            padding: .5rem;
        }

        .admin-dropdown a {
            display: block;
            padding: .6rem;
            text-decoration: none;
            color: #333;
            border-radius: 6px;
        }

        .admin-dropdown a:hover {
            background: #f3f4f6;
        }

        .admin-dropdown.show {
            display: block;
        }

        .admin-menu-btn {
            display: none;
            border: none;
            background: transparent;
            cursor: pointer;
            font-size: 1.2rem;
        }

        @media (max-width: 900px) {
            .admin-nav {
                position: fixed;
                right: -100%;
                top: 0;
                height: 100vh;
                width: 250px;
                background: white;
                flex-direction: column;
                padding: 2rem;
                transition: .3s;
                box-shadow: -10px 0 30px rgba(0,0,0,.1);
            }

            .admin-nav.open {
                right: 0;
            }

            .admin-menu-btn {
                display: block;
            }
        }
    </style>
</head>
<body>

<header class="admin-header">
    <div class="admin-container">

        <a href="<?= $dashboardUrl ?>" class="admin-logo">
            <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Logo">
            <span>Admin</span>
        </a>

        <nav class="admin-nav" id="adminNav">
            <a href="<?= $dashboardUrl ?>" class="<?= adminNavActive('/dashboards/', $currentPath) ?>">Dashboard</a>
            <a href="<?= $newsUrl ?>" class="<?= adminNavActive('/admin/news/', $currentPath) ?>">Jaunumi</a>
            <a href="<?= $clubsUrl ?>" class="<?= adminNavActive('/admin/clubs/', $currentPath) ?>">Klubi</a>
            <a href="<?= $galleryUrl ?>" class="<?= adminNavActive('/admin/gallery/', $currentPath) ?>">Galerija</a>
            <a href="<?= $usersUrl ?>" class="<?= adminNavActive('/admin/users/', $currentPath) ?>">Lietotāji</a>
        </nav>

        <div class="admin-right">
            <a href="<?= $homeUrl ?>" class="admin-home">Sākums</a>

            <button class="admin-avatar" id="avatarBtn" type="button">
                <?= htmlspecialchars($initials) ?>
            </button>

            <div class="admin-dropdown" id="dropdown">
                <a href="<?= $dashboardUrl ?>">Panelis</a>
                <a href="<?= $usersUrl ?>">Lietotāji</a>
                <a href="<?= $logoutUrl ?>">Iziet</a>
            </div>

            <button class="admin-menu-btn" id="menuBtn" type="button">
                <i class="fas fa-bars"></i>
            </button>
        </div>

    </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const avatar = document.getElementById('avatarBtn');
    const dropdown = document.getElementById('dropdown');
    const btn = document.getElementById('menuBtn');
    const nav = document.getElementById('adminNav');

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

    if (btn && nav) {
        btn.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }
});
</script>
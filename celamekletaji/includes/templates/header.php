<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

$isLoggedIn = isset($_SESSION["lietotajs_id"]);
$username = trim($_SESSION["lietotajvards"] ?? '');
$userRole = trim($_SESSION["loma"] ?? '');

// Iniciāļi avataram
$initials = 'U';
if ($username !== '') {
    $parts = preg_split('/\s+/', $username);
    if (!empty($parts[0])) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));
        if (isset($parts[1]) && $parts[1] !== '') {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }
    }
}

// Profila saite pēc lomas
$profileUrl = BASE_URL . 'dashboards/user.php';

if ($userRole === 'admin') {
    $profileUrl = BASE_URL . 'dashboards/admin.php';
} elseif ($userRole === 'moderators') {
    $profileUrl = BASE_URL . 'dashboards/moderator.php';
} elseif ($userRole === 'Vecāks' || $userRole === 'parent') {
    $profileUrl = BASE_URL . 'dashboards/parent.php';
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($title ?? 'Ceļa meklētāji'); ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>

<header class="main-header">
    <div class="container nav-container">

        <!-- LOGO -->
        <a href="<?= BASE_URL ?>index.php" class="logo" aria-label="Uz sākumu">
            <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            <span>Ceļa meklētāji</span>
        </a>

        <!-- PAGE TITLE -->
        <?php if (!empty($lapa) && $lapa !== "Ceļa meklētāji"): ?>
            <div class="header-title"><?php echo htmlspecialchars($lapa); ?></div>
        <?php endif; ?>

        <!-- NAV -->
        <nav class="main-nav" id="mainNav" aria-label="Galvenā navigācija">
            <a href="<?= BASE_URL ?>index.php">Sākums</a>
            <a href="<?= BASE_URL ?>public/about.php">Par mums</a>
            <a href="<?= BASE_URL ?>public/gallery.php">Galerija</a>
            <a href="<?= BASE_URL ?>public/clubs.php">Klubi</a>
        </nav>

        <!-- RIGHT SIDE -->
        <div class="nav-right">

            <?php if (!$isLoggedIn): ?>
                <a href="<?= BASE_URL ?>auth/login.php" class="nav-link nav-cta" aria-label="Pieslēgties">
                    <i class="fas fa-user"></i>
                    <span class="nav-cta-text">Pievienoties</span>
                </a>
            <?php else: ?>
                <div class="user-menu" id="userMenu">
                    <button
                        class="user-avatar-btn"
                        id="userAvatarBtn"
                        type="button"
                        aria-haspopup="true"
                        aria-expanded="false"
                        aria-label="Lietotāja izvēlne"
                    >
                        <span class="user-avatar">
                            <?php echo htmlspecialchars($initials); ?>
                        </span>
                    </button>

                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-head">
                            <div class="user-dropdown-name">
                                <?php echo htmlspecialchars($username ?: 'Lietotājs'); ?>
                            </div>
                            <div class="user-dropdown-role">
                                <?php echo htmlspecialchars($userRole ?: ''); ?>
                            </div>
                        </div>

                        <a href="<?php echo $profileUrl; ?>" class="user-dropdown-link">
                            <i class="fas fa-gear"></i>
                            <span>Profila opcijas</span>
                        </a>

                        <a href="<?= BASE_URL ?>auth/logout.php" class="user-dropdown-link user-dropdown-link--danger">
                            <i class="fas fa-right-from-bracket"></i>
                            <span>Iziet</span>
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- MOBILE MENU BUTTON -->
            <button
                id="menu-btn"
                class="menu-btn"
                type="button"
                aria-label="Atvērt izvēlni"
                aria-controls="mainNav"
                aria-expanded="false"
            >
                <i class="fas fa-bars" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</header>

<div class="nav-backdrop" id="navBackdrop" hidden></div>

<script>
(function () {
    // MOBILE MENU
    const btn = document.getElementById('menu-btn');
    const nav = document.getElementById('mainNav');
    const backdrop = document.getElementById('navBackdrop');

    function openMenu() {
        if (!btn || !nav) return;
        nav.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        btn.setAttribute('aria-label', 'Aizvērt izvēlni');
        btn.innerHTML = '<i class="fas fa-xmark" aria-hidden="true"></i>';
        if (backdrop) {
            backdrop.hidden = false;
            backdrop.classList.add('show');
        }
        document.body.classList.add('nav-lock');
    }

    function closeMenu() {
        if (!btn || !nav) return;
        nav.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-label', 'Atvērt izvēlni');
        btn.innerHTML = '<i class="fas fa-bars" aria-hidden="true"></i>';
        if (backdrop) {
            backdrop.classList.remove('show');
            backdrop.hidden = true;
        }
        document.body.classList.remove('nav-lock');
    }

    if (btn && nav) {
        btn.addEventListener('click', function () {
            const expanded = btn.getAttribute('aria-expanded') === 'true';
            expanded ? closeMenu() : openMenu();
        });

        if (backdrop) {
            backdrop.addEventListener('click', closeMenu);
        }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && nav.classList.contains('is-open')) {
                closeMenu();
            }
        });

        nav.querySelectorAll('a').forEach(function (a) {
            a.addEventListener('click', function () {
                if (window.matchMedia('(max-width: 768px)').matches) {
                    closeMenu();
                }
            });
        });

        window.addEventListener('resize', function () {
            if (!window.matchMedia('(max-width: 768px)').matches) {
                closeMenu();
            }
        });
    }

    // USER DROPDOWN
    const avatarBtn = document.getElementById('userAvatarBtn');
    const userMenu = document.getElementById('userMenu');

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
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

$isLoggedIn = isset($_SESSION["lietotajs_id"]);
$username   = trim($_SESSION["lietotajvards"] ?? '');
$userRole   = trim($_SESSION["loma"] ?? '');
$currentUrl = $_SERVER['REQUEST_URI'] ?? '';

/* ===============================
   AVATAR INICIĀĻI
================================ */
$initials = 'U';

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
   PROFILA SAITE PĒC LOMAS
================================ */
$roleLower = mb_strtolower($userRole);

$profileMap = [
    'admin'       => 'dashboards/admin.php',
    'administrators' => 'dashboards/admin.php',
    'moderators' => 'dashboards/moderator.php',
    'moderator'  => 'dashboards/moderator.php',
    'vecāks'     => 'dashboards/parent.php',
    'parent'     => 'dashboards/parent.php',
    'skolēns'    => 'dashboards/student.php',
    'student'    => 'dashboards/student.php',
    'direktors'  => 'dashboards/director.php',
    'director'   => 'dashboards/director.php',
    'skolotājs'  => 'dashboards/teacher.php',
    'teacher'    => 'dashboards/teacher.php',
];

$profileUrl = BASE_URL . ($profileMap[$roleLower] ?? 'dashboards/user.php');

/* ===============================
   ACTIVE LINK FUNKCIJA
================================ */
function isActivePage(string $path, string $currentUrl): string
{
    return str_contains($currentUrl, $path) ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Ceļa meklētāji'); ?></title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <script defer>
        window.BASE_URL = "<?= BASE_URL ?>";
    </script>
</head>
<body>

<header class="main-header" id="mainHeader">
    <div class="container nav-container">

        <!-- LOGO -->
        <a href="<?= BASE_URL ?>index.php" class="logo" aria-label="Uz sākumlapu">
            <span class="logo-img-wrap">
                <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            </span>
            <span class="logo-text">Ceļa meklētāji</span>
        </a>

        <!-- PAGE TITLE -->
        <?php if (!empty($lapa)): ?>
            <div class="header-title">
                <?= htmlspecialchars($lapa); ?>
            </div>
        <?php endif; ?>

        <!-- NAV -->
        <nav class="main-nav" id="mainNav" aria-label="Galvenā navigācija">
            <a class="<?= isActivePage('/index.php', $currentUrl); ?>" href="<?= BASE_URL ?>index.php">
                Sākums
            </a>

            <a class="<?= isActivePage('/public/about.php', $currentUrl); ?>" href="<?= BASE_URL ?>public/about.php">
                Par mums
            </a>

            <a class="<?= isActivePage('/public/gallery.php', $currentUrl); ?>" href="<?= BASE_URL ?>public/gallery.php">
                Galerija
            </a>

            <a class="<?= isActivePage('/public/clubs.php', $currentUrl); ?>" href="<?= BASE_URL ?>public/clubs.php">
                Klubi
            </a>
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
                        aria-label="Atvērt lietotāja izvēlni"
                    >
                        <span class="user-avatar">
                            <?= htmlspecialchars($initials); ?>
                        </span>
                        <span class="user-menu-text">
                            <strong><?= htmlspecialchars($username ?: 'Lietotājs'); ?></strong>
                            <small><?= htmlspecialchars($userRole ?: 'Profils'); ?></small>
                        </span>
                        <i class="fa-solid fa-chevron-down user-menu-arrow"></i>
                    </button>

                    <div class="user-dropdown" id="userDropdown">
                        <div class="user-dropdown-head">
                            <span class="user-dropdown-avatar">
                                <?= htmlspecialchars($initials); ?>
                            </span>

                            <div>
                                <div class="user-dropdown-name">
                                    <?= htmlspecialchars($username ?: 'Lietotājs'); ?>
                                </div>

                                <?php if ($userRole !== ''): ?>
                                    <div class="user-dropdown-role">
                                        <?= htmlspecialchars($userRole); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="user-dropdown-divider"></div>

                        <a href="<?= htmlspecialchars($profileUrl); ?>" class="user-dropdown-link">
                            <i class="fas fa-gauge"></i>
                            <span>Mans panelis</span>
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
    const header = document.getElementById('mainHeader');

    window.addEventListener('scroll', function () {
        if (!header) return;

        if (window.scrollY > 12) {
            header.classList.add('is-scrolled');
        } else {
            header.classList.remove('is-scrolled');
        }
    });

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
            requestAnimationFrame(() => backdrop.classList.add('show'));
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
            setTimeout(() => {
                backdrop.hidden = true;
            }, 180);
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

        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', function () {
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
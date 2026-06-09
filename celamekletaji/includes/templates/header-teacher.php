<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';

$allowedRoles = ['Skolotājs', 'skolotājs', 'teacher'];

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ''), $allowedRoles, true)
) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$username = trim($_SESSION["lietotajvards"] ?? 'Skolotājs');
$userRole = trim($_SESSION["loma"] ?? 'Skolotājs');

/* ===============================
   NELASĪTIE PAZIŅOJUMI
================================ */
$unreadCount = 0;

if (isset($_SESSION["lietotajs_id"])) {
    $userId = (int)$_SESSION["lietotajs_id"];

    $sqlUnread = "
        SELECT COUNT(*) AS total
        FROM cm_notifications
        WHERE user_id = ?
          AND is_read = 0
    ";

    $stmtUnread = $savienojums->prepare($sqlUnread);

    if ($stmtUnread) {
        $stmtUnread->bind_param("i", $userId);
        $stmtUnread->execute();

        $resultUnread = $stmtUnread->get_result();
        $rowUnread = $resultUnread->fetch_assoc();

        $unreadCount = (int)($rowUnread['total'] ?? 0);

        $stmtUnread->close();
    }
}

$pageTitle = $title ?? 'Skolotāja panelis';
$pageName  = $lapa ?? 'Skolotāja panelis';

$currentPage = basename($_SERVER['PHP_SELF'] ?? 'teacher.php');
$currentUrl  = $_SERVER['REQUEST_URI'] ?? '';

function teacherNavActive(array $pages, string $currentPage): string
{
    return in_array($currentPage, $pages, true) ? 'is-active' : '';
}

/* ===============================
   SAITES
================================ */
$dashboardUrl      = BASE_URL . 'dashboards/teacher.php';
$notificationsUrl = BASE_URL . 'dashboards/notifications.php';

$lessonsUrl        = BASE_URL . 'teacher/lesson_plans.php';
$activitiesUrl     = BASE_URL . 'teacher/activities.php';
$applicationsUrl   = BASE_URL . 'teacher/applications.php';
$clubUrl           = BASE_URL . 'teacher/club.php';

$profileUrl = BASE_URL . 'profile.php';

$homeUrl           = BASE_URL . 'auth/logout.php?redirect=home';
$logoutUrl         = BASE_URL . 'auth/logout.php';

$teacherDropdownActive = (
    str_contains($currentUrl, '/teacher/lesson_plans.php') ||
    str_contains($currentUrl, '/teacher/activities.php') ||
    str_contains($currentUrl, '/teacher/applications.php') ||
    str_contains($currentUrl, '/teacher/club.php')
);

/* ===============================
   AVATAR INICIĀĻI
================================ */
$initials = 'S';

if ($username !== '') {
    $parts = preg_split('/\s+/', $username);

    if (!empty($parts[0])) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));

        if (!empty($parts[1])) {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($pageTitle); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">

    <style>
        .teacher-header {
            position: sticky;
            top: 0;
            z-index: 1500;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(23, 63, 132, 0.10);
            box-shadow: 0 10px 30px rgba(16, 24, 40, 0.05);
        }

        .teacher-nav-container {
            min-height: 76px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .teacher-brand {
            display: inline-flex;
            align-items: center;
            gap: .75rem;
            text-decoration: none;
            color: #173f84;
            min-width: 0;
            font-weight: 1000;
        }

        .teacher-brand-logo {
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

        .teacher-brand-logo img {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .teacher-brand-text {
            display: flex;
            flex-direction: column;
            line-height: 1.1;
            min-width: 0;
        }

        .teacher-brand-text strong {
            font-size: 1.05rem;
            color: #173f84;
            white-space: nowrap;
        }

        .teacher-brand-text span {
            max-width: 180px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: .82rem;
            color: #667085;
            font-weight: 800;
            margin-top: .12rem;
        }

        .teacher-nav {
            display: flex;
            align-items: center;
            gap: .35rem;
            margin-left: auto;
        }

        .teacher-nav a,
        .teacher-dropdown-toggle {
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
            border: none;
            background: transparent;
            font: inherit;
            cursor: pointer;
        }

        .teacher-nav a i,
        .teacher-dropdown-toggle i {
            color: #1e4fa1;
        }

        .teacher-nav a:hover,
        .teacher-dropdown-toggle:hover {
            background: #eef3ff;
            color: #173f84;
            transform: translateY(-1px);
        }

        .teacher-nav a.is-active,
        .teacher-dropdown-toggle.is-active {
            background: linear-gradient(135deg, #1e4fa1, #173f84);
            color: #fff;
            box-shadow: 0 12px 26px rgba(23, 63, 132, 0.18);
        }

        .teacher-nav a.is-active i,
        .teacher-dropdown-toggle.is-active i {
            color: #f4c430;
        }

        .teacher-dropdown {
            position: relative;
        }

        .teacher-dropdown-menu {
            position: absolute;
            top: calc(100% + .75rem);
            left: 0;
            min-width: 255px;
            padding: .7rem;
            border-radius: 1.25rem;
            background: #fff;
            border: 1px solid rgba(23, 63, 132, 0.10);
            box-shadow: 0 24px 65px rgba(16, 24, 40, 0.16);
            opacity: 0;
            visibility: hidden;
            transform: translateY(8px) scale(.98);
            transition: .18s ease;
            z-index: 2400;
        }

        .teacher-dropdown-menu.is-open {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .teacher-dropdown-menu a {
            display: flex;
            width: 100%;
            border-radius: .9rem;
            padding: .8rem .85rem;
        }

        .teacher-dropdown-menu a:hover {
            background: #eef3ff;
        }

        .dropdown-arrow {
            font-size: .75rem;
            transition: .2s ease;
        }

        .teacher-dropdown-toggle.is-open .dropdown-arrow {
            transform: rotate(180deg);
        }

        .teacher-right {
            display: flex;
            align-items: center;
            gap: .7rem;
            position: relative;
        }

        .teacher-quick-home {
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

        .teacher-quick-home:hover {
            background: #dfeaff;
            transform: translateY(-1px);
        }

        .teacher-notification {
            position: relative;
            width: 44px;
            height: 44px;
            display: grid;
            place-items: center;
            text-decoration: none;
            border-radius: 50%;
            background: #eef3ff;
            color: #173f84;
            font-size: 1.1rem;
            transition: .2s ease;
            flex-shrink: 0;
        }

        .teacher-notification:hover {
            background: #dfeaff;
            transform: translateY(-1px);
        }

        .teacher-notification i {
            color: #173f84;
        }

        .teacher-notification.is-active {
            background: #173f84;
        }

        .teacher-notification.is-active i {
            color: #f4c430;
        }

        .teacher-notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            min-width: 20px;
            height: 20px;
            padding: 0 6px;
            border-radius: 999px;
            background: #d62828;
            color: #fff;
            font-size: .72rem;
            font-weight: 1000;
            display: grid;
            place-items: center;
            border: 2px solid #fff;
            line-height: 1;
        }

        .teacher-user-menu {
            position: relative;
        }

        .teacher-avatar-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: none;
            background: transparent;
            cursor: pointer;
            padding: 0;
        }

        .teacher-avatar {
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

        .teacher-avatar-btn:hover .teacher-avatar {
            transform: translateY(-2px);
            box-shadow: 0 16px 34px rgba(244, 196, 48, 0.32);
        }

        .teacher-dropdown-profile {
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
            z-index: 3000;
        }

        .teacher-user-menu.open .teacher-dropdown-profile {
            opacity: 1;
            visibility: visible;
            transform: translateY(0) scale(1);
        }

        .teacher-dropdown-head {
            display: flex;
            align-items: center;
            gap: .75rem;
            padding: .75rem;
            border-radius: 1rem;
            background: #f8fbff;
            margin-bottom: .5rem;
        }

        .teacher-dropdown-avatar {
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

        .teacher-dropdown-name {
            font-weight: 1000;
            color: #101828;
            line-height: 1.2;
        }

        .teacher-dropdown-role {
            font-size: .86rem;
            color: #667085;
            font-weight: 800;
            margin-top: .1rem;
        }

        .teacher-dropdown-link {
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

        .teacher-dropdown-link i {
            width: 20px;
            color: #1e4fa1;
            text-align: center;
        }

        .teacher-dropdown-link:hover {
            background: #eef3ff;
            color: #173f84;
        }

        .teacher-dropdown-link--danger {
            color: #b42318;
        }

        .teacher-dropdown-link--danger i {
            color: #b42318;
        }

        .teacher-dropdown-link--danger:hover {
            background: #fff0f0;
            color: #b42318;
        }

        .teacher-menu-btn {
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

        .teacher-mobile-backdrop {
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.42);
            z-index: 1200;
            opacity: 0;
            visibility: hidden;
            transition: .2s ease;
        }

        .teacher-mobile-backdrop.show {
            opacity: 1;
            visibility: visible;
        }

        @media (max-width: 1180px) {
            .teacher-brand-text span,
            .teacher-quick-home span {
                display: none;
            }
        }

        @media (max-width: 980px) {
            .teacher-menu-btn {
                display: grid;
                place-items: center;
            }

            .teacher-nav {
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

            .teacher-nav.is-open {
                opacity: 1;
                visibility: visible;
                transform: translateY(0);
            }

            .teacher-nav a,
            .teacher-dropdown-toggle {
                justify-content: flex-start;
                border-radius: 1rem;
                padding: .95rem 1rem;
                width: 100%;
            }

            .teacher-dropdown-menu {
                position: static;
                min-width: 100%;
                margin-top: .35rem;
                box-shadow: none;
                border-radius: 1rem;
                background: #f8fbff;
                display: none;
                opacity: 1;
                visibility: visible;
                transform: none;
            }

            .teacher-dropdown-menu.is-open {
                display: grid;
                gap: .25rem;
            }

            body.nav-lock {
                overflow: hidden;
            }
        }

        @media (max-width: 560px) {
            .teacher-nav-container {
                min-height: 68px;
            }

            .teacher-brand-text {
                display: none;
            }

            .teacher-brand-logo {
                width: 44px;
                height: 44px;
            }

            .teacher-notification,
            .teacher-avatar,
            .teacher-menu-btn {
                width: 42px;
                height: 42px;
            }

            .teacher-quick-home {
                display: none;
            }

            .teacher-dropdown-profile {
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

<header class="teacher-header">
    <div class="container teacher-nav-container">

        <a href="<?= $dashboardUrl ?>" class="teacher-brand">
            <span class="teacher-brand-logo">
                <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            </span>

            <span class="teacher-brand-text">
                <strong>Skolotāja panelis</strong>
                <span><?= htmlspecialchars($pageName); ?></span>
            </span>
        </a>

        <nav class="teacher-nav" id="teacherNav" aria-label="Skolotāja navigācija">

            <a href="<?= $dashboardUrl ?>" class="<?= teacherNavActive(['teacher.php'], $currentPage); ?>">
                <i class="fas fa-gauge-high"></i>
                <span>Panelis</span>
            </a>

            <div class="teacher-dropdown" id="teacherWorkDropdown">
                <button
                    class="teacher-dropdown-toggle <?= $teacherDropdownActive ? 'is-active' : ''; ?>"
                    type="button"
                    id="teacherWorkToggle"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="fas fa-chalkboard-user"></i>
                    <span>Mans darbs</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div class="teacher-dropdown-menu" id="teacherWorkMenu">
                    <a href="<?= $clubUrl ?>" class="<?= teacherNavActive(['club.php'], $currentPage); ?>">
                        <i class="fas fa-circle-info"></i>
                        <span>Mans klubs</span>
                    </a>

                    <a href="<?= $lessonsUrl ?>" class="<?= teacherNavActive(['lesson_plans.php'], $currentPage); ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Nodarbību plāni</span>
                    </a>

                    <a href="<?= $activitiesUrl ?>" class="<?= teacherNavActive(['activities.php'], $currentPage); ?>">
                        <i class="fas fa-calendar-days"></i>
                        <span>Pasākumi</span>
                    </a>

                    <a href="<?= $applicationsUrl ?>" class="<?= teacherNavActive(['applications.php'], $currentPage); ?>">
                        <i class="fas fa-file-signature"></i>
                        <span>Pieteikumi</span>
                    </a>
                </div>
            </div>

        </nav>

        <div class="teacher-right">

            <a href="<?= $homeUrl ?>" class="teacher-quick-home">
                <i class="fas fa-arrow-left"></i>
                <span>Uz sākumlapu</span>
            </a>

            <a href="<?= $notificationsUrl ?>"
               class="teacher-notification <?= str_contains($currentUrl, '/dashboards/notifications.php') ? 'is-active' : ''; ?>"
               title="Paziņojumi"
               aria-label="Paziņojumi">
                <i class="fas fa-bell"></i>

                <?php if ($unreadCount > 0): ?>
                    <span class="teacher-notification-badge">
                        <?= $unreadCount > 99 ? '99+' : $unreadCount ?>
                    </span>
                <?php endif; ?>
            </a>

            <div class="teacher-user-menu" id="teacherUserMenu">

                <button
                    class="teacher-avatar-btn"
                    id="teacherAvatarBtn"
                    type="button"
                    aria-label="Atvērt lietotāja izvēlni"
                    aria-expanded="false"
                >
                    <span class="teacher-avatar">
                        <?= htmlspecialchars($initials); ?>
                    </span>
                </button>

                <div class="teacher-dropdown-profile">
                    <div class="teacher-dropdown-head">
                        <span class="teacher-dropdown-avatar">
                            <?= htmlspecialchars($initials); ?>
                        </span>

                        <div>
                            <div class="teacher-dropdown-name">
                                <?= htmlspecialchars($username ?: 'Skolotājs'); ?>
                            </div>

                            <div class="teacher-dropdown-role">
                                <?= htmlspecialchars($userRole ?: 'Skolotājs'); ?>
                            </div>
                        </div>
                    </div>

                    <a href="<?= $profileUrl ?>" class="teacher-dropdown-link">
                        <i class="fas fa-user-pen"></i>
                        <span>Labot profilu</span>
                    </a>

                    <a href="<?= $notificationsUrl ?>" class="teacher-dropdown-link">
                        <i class="fas fa-bell"></i>
                        <span>
                            Paziņojumi
                            <?php if ($unreadCount > 0): ?>
                                (<?= $unreadCount > 99 ? '99+' : $unreadCount ?>)
                            <?php endif; ?>
                        </span>
                    </a>

                    <a href="<?= $homeUrl ?>" class="teacher-dropdown-link">
                        <i class="fas fa-house"></i>
                        <span>Uz sākumlapu</span>
                    </a>

                    <a href="<?= $logoutUrl ?>" class="teacher-dropdown-link teacher-dropdown-link--danger">
                        <i class="fas fa-right-from-bracket"></i>
                        <span>Iziet</span>
                    </a>
                </div>
            </div>

            <button
                id="teacherMenuBtn"
                class="teacher-menu-btn"
                type="button"
                aria-label="Atvērt izvēlni"
                aria-expanded="false"
            >
                <i class="fas fa-bars"></i>
            </button>

        </div>
    </div>
</header>

<div class="teacher-mobile-backdrop" id="teacherNavBackdrop"></div>

<script>
(function () {
    const menuBtn = document.getElementById('teacherMenuBtn');
    const nav = document.getElementById('teacherNav');
    const backdrop = document.getElementById('teacherNavBackdrop');

    const avatarBtn = document.getElementById('teacherAvatarBtn');
    const userMenu = document.getElementById('teacherUserMenu');

    const workDropdown = document.getElementById('teacherWorkDropdown');
    const workToggle = document.getElementById('teacherWorkToggle');
    const workMenu = document.getElementById('teacherWorkMenu');

    function closeWorkMenu() {
        if (!workToggle || !workMenu) return;

        workToggle.classList.remove('is-open');
        workMenu.classList.remove('is-open');
        workToggle.setAttribute('aria-expanded', 'false');
    }

    function closeUserMenu() {
        if (!avatarBtn || !userMenu) return;

        userMenu.classList.remove('open');
        avatarBtn.setAttribute('aria-expanded', 'false');
    }

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

        closeWorkMenu();
    }

    menuBtn?.addEventListener('click', function () {
        if (!nav) return;

        if (nav.classList.contains('is-open')) {
            closeMenu();
        } else {
            openMenu();
        }
    });

    backdrop?.addEventListener('click', function () {
        closeMenu();
        closeUserMenu();
        closeWorkMenu();
    });

    avatarBtn?.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = userMenu.classList.toggle('open');
        avatarBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        closeMenu();
        closeWorkMenu();
    });

    workToggle?.addEventListener('click', function (event) {
        event.stopPropagation();

        const isOpen = workMenu.classList.toggle('is-open');
        workToggle.classList.toggle('is-open', isOpen);
        workToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

        closeUserMenu();
    });

    nav?.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            closeMenu();
            closeUserMenu();
            closeWorkMenu();
        });
    });

    document.addEventListener('click', function (event) {
        if (userMenu && !userMenu.contains(event.target)) {
            closeUserMenu();
        }

        if (workDropdown && !workDropdown.contains(event.target)) {
            closeWorkMenu();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeMenu();
            closeUserMenu();
            closeWorkMenu();
        }
    });
})();
</script>
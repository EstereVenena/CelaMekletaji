<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/app.php';

$isLoggedIn = isset($_SESSION["lietotajs_id"]);

$username = trim($_SESSION["lietotajvards"] ?? '');
$userRole = trim($_SESSION["loma"] ?? '');

$lomasAtlautas = ["Skolēns", "Ceļameklētājs", "Bērns", "student", "child"];

if (!$isLoggedIn || !in_array($userRole, $lomasAtlautas, true)) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

/* ===============================
   STUDENT MAPES LINKI
================================ */
$studentBase = BASE_URL . "student/";

$studentPanelUrl        = BASE_URL . "dashboards/student.php";
$studentLessonsUrl      = $studentBase . "lessons.php";
$studentApplicationsUrl = $studentBase . "applications.php";
$studentEventsUrl       = $studentBase . "events.php";
$studentProfileUrl      = $studentBase . "profile.php";
$studentNewsUrl         = $studentBase . "news.php";

/* ===============================
   AVATAR INICIĀĻI
================================ */
$initials = 'C';

if ($username !== '') {
    $parts = preg_split('/\s+/', $username);

    if (!empty($parts[0])) {
        $initials = mb_strtoupper(mb_substr($parts[0], 0, 1));

        if (isset($parts[1]) && $parts[1] !== '') {
            $initials .= mb_strtoupper(mb_substr($parts[1], 0, 1));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?php echo htmlspecialchars($title ?? 'Ceļameklētāja panelis'); ?>
    </title>

    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<body>

<header class="main-header">
    <div class="container nav-container">

        <!-- LOGO -->
        <a href="<?= $studentPanelUrl ?>" class="logo" aria-label="Ceļameklētāja panelis">
            <img src="<?= BASE_URL ?>assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            <span>Ceļa meklētāji</span>
        </a>

        <!-- PAGE TITLE -->
        <?php if (!empty($lapa)): ?>
            <div class="header-title">
                <?php echo htmlspecialchars($lapa); ?>
            </div>
        <?php endif; ?>

        <!-- NAV -->
        <nav class="main-nav" id="mainNav" aria-label="Ceļameklētāja navigācija">

            <a href="<?= $studentPanelUrl ?>">
                <i class="fas fa-gauge-high"></i>
                <span>Panelis</span>
            </a>

            <!-- MANS KLUBS DROPDOWN -->
            <div class="director-club-dropdown" id="studentClubDropdown">

                <button
                    class="nav-link director-club-toggle"
                    id="studentClubToggle"
                    type="button"
                    aria-haspopup="true"
                    aria-expanded="false"
                >
                    <i class="fas fa-campground"></i>
                    <span>Mans klubs</span>
                    <i class="fas fa-chevron-down dropdown-arrow"></i>
                </button>

                <div class="director-club-menu" id="studentClubMenu">

                    <a href="<?= $studentLessonsUrl ?>">
                        <i class="fas fa-book-open"></i>
                        <span>Nodarbības</span>
                    </a>

                    <a href="<?= $studentApplicationsUrl ?>">
                        <i class="fas fa-clipboard-list"></i>
                        <span>Mani pieteikumi</span>
                    </a>

                    <a href="<?= $studentEventsUrl ?>">
                        <i class="fas fa-calendar-days"></i>
                        <span>Pasākumi</span>
                    </a>

                </div>
            </div>

            <a href="<?= $studentNewsUrl ?>">
                <i class="fas fa-newspaper"></i>
                <span>Jaunumi</span>
            </a>

        </nav>

        <!-- RIGHT SIDE -->
        <div class="nav-right">

            <!-- USER MENU -->
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
                            <?php echo htmlspecialchars($username ?: 'Ceļameklētājs'); ?>
                        </div>

                        <div class="user-dropdown-role">
                            <?php echo htmlspecialchars($userRole ?: 'Ceļameklētājs'); ?>
                        </div>
                    </div>

                    <a href="<?= $studentProfileUrl ?>" class="user-dropdown-link">
                        <i class="fas fa-user-gear"></i>
                        <span>Mans profils</span>
                    </a>

                    <a href="<?= $studentApplicationsUrl ?>" class="user-dropdown-link">
                        <i class="fas fa-clipboard-check"></i>
                        <span>Mani pieteikumi</span>
                    </a>

                    <a href="<?= $studentLessonsUrl ?>" class="user-dropdown-link">
                        <i class="fas fa-book-open-reader"></i>
                        <span>Nodarbības</span>
                    </a>

                    <a href="<?= BASE_URL ?>auth/logout.php" class="user-dropdown-link user-dropdown-link--danger">
                        <i class="fas fa-right-from-bracket"></i>
                        <span>Iziet</span>
                    </a>

                </div>
            </div>

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
    /* ===============================
       MOBILE MENU
    ================================ */
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

    /* ===============================
       USER DROPDOWN
    ================================ */
    const avatarBtn = document.getElementById('userAvatarBtn');
    const userMenu = document.getElementById('userMenu');

    if (avatarBtn && userMenu) {
        avatarBtn.addEventListener('click', function (e) {
            e.stopPropagation();

            const isOpen = userMenu.classList.toggle('open');
            avatarBtn.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            closeStudentClubDropdown();
        });

        document.addEventListener('click', function (e) {
            if (!userMenu.contains(e.target)) {
                userMenu.classList.remove('open');
                avatarBtn.setAttribute('aria-expanded', 'false');
            }
        });
    }

    /* ===============================
       STUDENT CLUB DROPDOWN
    ================================ */
    const clubDropdown = document.getElementById('studentClubDropdown');
    const clubToggle = document.getElementById('studentClubToggle');
    const clubMenu = document.getElementById('studentClubMenu');

    function closeStudentClubDropdown() {
        if (!clubToggle || !clubMenu) return;

        clubToggle.classList.remove('is-open');
        clubMenu.classList.remove('is-open');
        clubToggle.setAttribute('aria-expanded', 'false');
    }

    if (clubToggle && clubMenu && clubDropdown) {
        clubToggle.addEventListener('click', function (e) {
            e.stopPropagation();

            const isOpen = clubMenu.classList.toggle('is-open');
            clubToggle.classList.toggle('is-open', isOpen);
            clubToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');

            if (userMenu && avatarBtn) {
                userMenu.classList.remove('open');
                avatarBtn.setAttribute('aria-expanded', 'false');
            }
        });

        document.addEventListener('click', function (e) {
            if (!clubDropdown.contains(e.target)) {
                closeStudentClubDropdown();
            }
        });
    }

    /* ===============================
       ESC CLOSE
    ================================ */
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeMenu();
            closeStudentClubDropdown();

            if (userMenu && avatarBtn) {
                userMenu.classList.remove('open');
                avatarBtn.setAttribute('aria-expanded', 'false');
            }
        }
    });
})();
</script>
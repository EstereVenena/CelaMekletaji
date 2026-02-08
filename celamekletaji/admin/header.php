<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title; ?></title>

    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
</head>
<body>

<header class="prof-header">
    <div class="header-container">

        <!-- Brand block: clickable to admin home -->
        <a href="index.php" class="brand-block" aria-label="Uz admin sākumu">
            <div class="brand-logo">
                <img src="../images/logo.png" class="logo" alt="Ceļa meklētāji">
            </div>
            <div class="brand-meta">
                <div class="brand-name">Admin</div>
                <div class="brand-sub">Ceļa meklētāji</div>
            </div>
        </a>

        <!-- Page title -->
        <h1 class="header-title"><?php echo $lapa; ?></h1>

        <nav class="main-nav" id="mainNav" aria-label="Admin navigācija">
            <a href="index.php" class="nav-link">Sākums</a>
            <a href="news_manage.php" class="nav-link">Ziņas</a>
            <a href="clubs_manage.php" class="nav-link">Klubi</a>
            <a href="users_manage.php" class="nav-link">Lietotāji</a>
            <a href="../index.php" class="nav-link nav-cta" aria-label="Iziet">
                <i class="fas fa-sign-out-alt"></i>
                <span class="nav-cta-text">Iziet</span>
            </a>
        </nav>

        <button id="menu-btn"
                class="menu-btn"
                type="button"
                aria-label="Atvērt izvēlni"
                aria-controls="mainNav"
                aria-expanded="false">
            <i class="fas fa-bars" aria-hidden="true"></i>
        </button>
    </div>
</header>

<!-- Backdrop for mobile menu -->
<div class="nav-backdrop" id="navBackdrop" hidden></div>

<script>
(function () {
    const btn = document.getElementById('menu-btn');
    const nav = document.getElementById('mainNav');
    const backdrop = document.getElementById('navBackdrop');

    if (!btn || !nav) return;

    function openMenu() {
        nav.classList.add('is-open');
        btn.setAttribute('aria-expanded', 'true');
        btn.setAttribute('aria-label', 'Aizvērt izvēlni');
        btn.innerHTML = '<i class="fas fa-xmark" aria-hidden="true"></i>';
        if (backdrop) { backdrop.hidden = false; backdrop.classList.add('show'); }
        document.body.classList.add('nav-lock');
    }

    function closeMenu() {
        nav.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
        btn.setAttribute('aria-label', 'Atvērt izvēlni');
        btn.innerHTML = '<i class="fas fa-bars" aria-hidden="true"></i>';
        if (backdrop) { backdrop.classList.remove('show'); backdrop.hidden = true; }
        document.body.classList.remove('nav-lock');
    }

    btn.addEventListener('click', () => {
        const expanded = btn.getAttribute('aria-expanded') === 'true';
        expanded ? closeMenu() : openMenu();
    });

    if (backdrop) backdrop.addEventListener('click', closeMenu);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && nav.classList.contains('is-open')) closeMenu();
    });

    nav.querySelectorAll('a').forEach(a => a.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 768px)').matches) closeMenu();
    }));

    window.addEventListener('resize', () => {
        if (!window.matchMedia('(max-width: 768px)').matches) closeMenu();
    });
})();
</script>
<?php
$baseUrl = defined('BASE_URL') ? BASE_URL : '/celamekletaji/';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isLoggedIn = isset($_SESSION["lietotajs_id"]);

$currentUri = $_SERVER["REQUEST_URI"] ?? "";

/*
   Ja footer tiek rādīts profila/paneļa zonā,
   publiskās saites ved caur logout.php.
*/
$isProfileArea =
    str_contains($currentUri, '/dashboards/') ||
    str_contains($currentUri, '/admin/') ||
    str_contains($currentUri, '/director/') ||
    str_contains($currentUri, '/parent/') ||
    str_contains($currentUri, '/student/') ||
    str_contains($currentUri, '/children/');

function footerUrl(string $page, string $normalUrl, bool $isLoggedIn, bool $isProfileArea, string $baseUrl): string
{
    if ($isLoggedIn && $isProfileArea) {
        return $baseUrl . "auth/logout.php?redirect=" . urlencode($page);
    }

    return $normalUrl;
}

$footerHomeUrl    = footerUrl("home",    $baseUrl . "index.php",                     $isLoggedIn, $isProfileArea, $baseUrl);
$footerAboutUrl   = footerUrl("about",   $baseUrl . "public/about.php",              $isLoggedIn, $isProfileArea, $baseUrl);
$footerGalleryUrl = footerUrl("gallery", $baseUrl . "public/gallery.php",            $isLoggedIn, $isProfileArea, $baseUrl);
$footerClubsUrl   = footerUrl("clubs",   $baseUrl . "public/clubs.php",              $isLoggedIn, $isProfileArea, $baseUrl);
$footerPrivacyUrl = footerUrl("privacy", $baseUrl . "public/privatumapolitika.php",  $isLoggedIn, $isProfileArea, $baseUrl);
?>

<footer class="site-footer">
    <div class="footer-wave"></div>

    <div class="footer-inner container">

        <div class="footer-col footer-brand">
            <a href="<?= htmlspecialchars($footerHomeUrl) ?>" class="footer-logo" aria-label="Uz sākumlapu">
                <span class="footer-logo-icon">
                    <i class="fa-solid fa-compass"></i>
                </span>
                <span>Ceļa meklētāji</span>
            </a>

            <p class="footer-muted">
                Kristīga bērnu un jauniešu kustība Latvijā, kurā caur nodarbībām,
                nometnēm un piedzīvojumiem tiek attīstītas prasmes, draudzība un vērtības.
            </p>
        </div>

        <div class="footer-col">
            <h4>Kontakti</h4>

            <ul class="footer-list">
                <li>
                    <span class="footer-list-icon">
                        <i class="fas fa-phone"></i>
                    </span>

                    <a class="footer-link" href="tel:+37129000000">
                        +371 29 000 000
                    </a>
                </li>

                <li>
                    <span class="footer-list-icon">
                        <i class="fas fa-envelope"></i>
                    </span>

                    <a class="footer-link" href="mailto:info@celamekletaji.lv">
                        info@celamekletaji.lv
                    </a>
                </li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Atrašanās vieta</h4>

            <ul class="footer-list">
                <li>
                    <span class="footer-list-icon">
                        <i class="fas fa-location-dot"></i>
                    </span>

                    <span class="footer-muted">
                        Latvija<br>
                        Rīga
                    </span>
                </li>
            </ul>
        </div>

        <div class="footer-col">
            <h4>Saites</h4>

            <ul class="footer-links">
                <li>
                    <a href="<?= htmlspecialchars($footerHomeUrl) ?>">
                        <i class="fa-solid fa-angle-right"></i>
                        Sākums
                    </a>
                </li>

                <li>
                    <a href="<?= htmlspecialchars($footerAboutUrl) ?>">
                        <i class="fa-solid fa-angle-right"></i>
                        Par mums
                    </a>
                </li>

                <li>
                    <a href="<?= htmlspecialchars($footerGalleryUrl) ?>">
                        <i class="fa-solid fa-angle-right"></i>
                        Galerija
                    </a>
                </li>

                <li>
                    <a href="<?= htmlspecialchars($footerClubsUrl) ?>">
                        <i class="fa-solid fa-angle-right"></i>
                        Klubi
                    </a>
                </li>

                <li>
                    <a href="<?= htmlspecialchars($footerPrivacyUrl) ?>">
                        <i class="fa-solid fa-angle-right"></i>
                        Privātuma politika
                    </a>
                </li>
            </ul>
        </div>

    </div>

    <div class="footer-bottom">
        <div class="container footer-bottom-inner">
            <p>
                Ceļa meklētāji © <?= date('Y'); ?> · Visas tiesības aizsargātas
            </p>

            <a href="<?= htmlspecialchars($footerPrivacyUrl) ?>">
                Privātuma politika
            </a>
        </div>
    </div>
</footer>

</body>
</html>
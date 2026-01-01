<?php
    $lapa = "Ceļa meklētāju klubi Latvijā";
    $title = "Klubi | Ceļa meklētāji";
    require "assets/header.php";
?> 

<body>
<section class="section">
    <div class="map-wrapper">

        <!-- RĪGA -->
        <div class="map-pin" style="top:48%; left:52%;">
            <i class="fas fa-map-marker-alt"></i>
            <span>Rīga CM “7”</span>
        </div>

        <!-- LIEPĀJA -->
        <div class="map-pin" style="top:70%; left:15%;">
            <i class="fas fa-map-marker-alt"></i>
            <span>Liepāja CM</span>
        </div>

        <!-- CĒSIS -->
        <div class="map-pin" style="top:30%; left:55%;">
            <i class="fas fa-map-marker-alt"></i>
            <span>Cēsis CM</span>
        </div>

        <!-- VALMIERA -->
        <div class="map-pin" style="top:22%; left:60%;">
            <i class="fas fa-map-marker-alt"></i>
            <span>Valmiera CM</span>
        </div>

        <!-- DAUGAVPILS -->
        <div class="map-pin" style="top:65%; left:80%;">
            <i class="fas fa-map-marker-alt"></i>
            <span>Daugavpils CM</span>
        </div>

    </div>
</section>

<?php
    require "assets/footer.php";
?>
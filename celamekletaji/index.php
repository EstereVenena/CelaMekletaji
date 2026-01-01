<?php
    $lapa = "Ceļa meklētāji";
    $title = "Ceļa meklētāji";
    require "assets/header.php";
?>  

<!-- ================= JAUNUMI ================= -->
<section id="jaunumi">
    <a href="news.html">
        <h2 class="jaunumi">
            <span class="vairak">
                Aktualitātes <i class="fa-solid fa-angles-right"></i>
            </span>
        </h2>
    </a>

    <div class="news-carousel">
        <div class="carousel-container">

            <div class="carousel-slide">
                <div class="box">
                    <h1>GENERATION Z BE READY</h1>
                    <p class="category">Projekts</p>
                    <p>Jauniešu personīgās izaugsmes programma</p>
                    <p class="date">2026-03-12</p>
                    <div class="buttons">
                        <a href="news.html" class="btn">Lasīt</a>
                    </div>
                </div>
            </div>

            <div class="carousel-slide">
                <div class="box">
                    <h1>Pavasara nometne 2026</h1>
                    <p class="category">Nometnes</p>
                    <p>Aicinām bērnus un jauniešus piedalīties</p>
                    <p class="date">2026-04-15</p>
                    <div class="buttons">
                        <a href="news.html" class="btn">Lasīt</a>
                    </div>
                </div>
            </div>

        </div>

        <div class="carousel-controls">
            <button class="carousel-prev"><i class="fas fa-chevron-left"></i></button>
            <button class="carousel-next"><i class="fas fa-chevron-right"></i></button>
        </div>

        <div class="carousel-dots"></div>
    </div>
</section>

<!-- ================= PAR MUMS ================= -->
<section id="pakalpojumi">
    <h2><span>Par biedrību</span></h2>

    <div class="box">
        <p>
            “Ceļa meklētāji” ir kristīga biedrība, kas nodarbojas ar bērnu un
            jauniešu izglītošanu, organizējot nodarbības, nometnes un pasākumus
            visā Latvijā.
        </p>
    </div>
</section>

<!-- ================= KLUBI ================= -->
<section id="vakances">
    <a href="clubs.html">
        <h2>
            <span class="vairak">
                Klubi Latvijā <i class="fa-solid fa-angles-right"></i>
            </span>
        </h2>
    </a>

    <div class="box-container">
        <div class="box">
            <h3>Rīga CM</h3>
            <p class="vieta">Rīga</p>
            <p class="alga">Vecums: 10–16</p>
        </div>

        <div class="box">
            <h3>Liepāja PM</h3>
            <p class="vieta">Liepāja</p>
            <p class="alga">Vecums: 4–9</p>
        </div>

        <div class="box">
            <h3>Valmiera CM</h3>
            <p class="vieta">Valmiera</p>
            <p class="alga">Vecums: 10–16</p>
        </div>
    </div>
</section>

<!-- ================= KONTAKTI ================= -->
<section id="kontakti">
    <a href="#">
        <h2>
            <h2><span>Kontakti</span></h2>
        </h2>
    </a>

    <div class="box-container">

        <div class="contact-box">
            <i class="fas fa-phone"></i>
            <div>
                <p>+371 29 000 000</p>
                <p>+371 29 000 001</p>
            </div>
        </div>

        <div class="contact-box">
            <i class="fas fa-envelope"></i>
            <div>
                <p>info@celamekletaji.lv</p>
            </div>
        </div>

        <div class="contact-box">
            <i class="fas fa-map-marker-alt"></i>
            <div>
                <p>Latvija</p>
            </div>
        </div>

    </div>
</section>

<!-- ================= FOOTER ================= -->
<footer>
    Ceļa meklētāji © 2026
</footer>

</body>
</html>

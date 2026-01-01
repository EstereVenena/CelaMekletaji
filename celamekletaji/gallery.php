<?php
    $lapa = "Ceļa meklētāju galerija";
    $title = "Galerija | Ceļa meklētāji";
    require "assets/header.php";
?> 

<section class="section">

    <div class="gallery-container">
        <div class="gallery-item">
            <img src="images/2020.jpg" alt="2020">
            <span class="year-label">2020</span>
        </div>
        <div class="gallery-item">
            <img src="images/2021.jpg" alt="2021">
            <span class="year-label">2021</span>
        </div>
        <div class="gallery-item">
            <img src="images/2022.jpg" alt="2022">
            <span class="year-label">2022</span>
        </div>
        <div class="gallery-item">
            <img src="images/2023.jpg" alt="2023">
            <span class="year-label">2023</span>
        </div>
    </div>
</section>

<?php
    require "assets/footer.php";
?> 

<script>
// Toggle mobile menu
const menuBtn = document.getElementById('menu-btn');
const mainNav = document.querySelector('.main-nav');

menuBtn.addEventListener('click', () => {
    mainNav.classList.toggle('active');
});
</script>
</body>
</html>

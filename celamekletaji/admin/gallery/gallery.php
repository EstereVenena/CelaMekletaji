<?php
$lapa = "Galerija";
$title = "Galerijas pārvaldība";

require "header.php";
require_once "../assets/database.php";


?>
        <section class="section">
    <div class="container">
        <header class="section-title">
            <p class="muted">
                Augšupielādē jaunus attēlus vai dzēs esošos.
            </p>
        </header>

        <form action="upload_gallery.php" 
              method="post" 
              enctype="multipart/form-data" 
              style="margin-bottom: 2rem;">

            <div style="display:flex; gap:1rem; align-items:end; flex-wrap:wrap;">

                <div>
                    <label>Izvēlies attēlus:</label>
                    <input type="file" name="images[]" accept="image/*" multiple required>
                </div>

                <div>
                    <label>Gads:</label>
                    <input type="number" name="year" placeholder="2024" required>
                </div>

                <div>
                    <label>Autors:</label>
                    <input type="text" name="creator" placeholder="Autora vārds" required>
                </div>

                <div>
                    <label>Kategorija:</label>
                    <input type="text" name="category" placeholder="Kategorija">
                </div>

                <button type="submit" class="btn btn-primary">
                    Augšupielādēt
                </button>

            </div>
        </form>
    </div>
</section>
<?php
/* ======================
FILTERI
====================== */

$year = $_GET['year'] ?? '';
$category = $_GET['category'] ?? '';

$sql = "SELECT * FROM cm_gallery_images WHERE 1";

if($year != ''){
$sql .= " AND year='". $savienojums->real_escape_string($year) ."'";
}

if($category != ''){
$sql .= " AND category='". $savienojums->real_escape_string($category) ."'";
}

$sql .= " ORDER BY upload_date DESC";

$result = $savienojums->query($sql);

$images = [];
while($row = $result->fetch_assoc()){
$images[] = $row;
}

/* GET FILTER OPTIONS */

$years = $savienojums->query("SELECT DISTINCT year FROM cm_gallery_images ORDER BY year DESC");
$categories = $savienojums->query("SELECT DISTINCT category FROM cm_gallery_images ORDER BY category");
?>

<section class="section">
<div class="container">

<h2>Galerija</h2>

<!-- FILTRI -->

<form method="get" style="margin-bottom:20px;display:flex;gap:10px;flex-wrap:wrap;">

<select name="year">
<option value="">Visi gadi</option>
<?php while($y = $years->fetch_assoc()): ?>
<option value="<?= $y['year'] ?>" <?= $year==$y['year']?'selected':'' ?>>
<?= $y['year'] ?>
</option>
<?php endwhile; ?>
</select>

<select name="category">
<option value="">Visas kategorijas</option>
<?php while($c = $categories->fetch_assoc()): ?>
<option value="<?= $c['category'] ?>" <?= $category==$c['category']?'selected':'' ?>>
<?= $c['category'] ?>
</option>
<?php endwhile; ?>
</select>

<button class="btn btn-primary">Filtrēt</button>

</form>

<!-- GALERIJA -->

<div class="gallery-grid">

<?php foreach($images as $img): ?>

<div class="gallery-item">

<img 
src="../<?= htmlspecialchars($img['path']) ?>" 
loading="lazy"
onclick="openLightbox('../<?= htmlspecialchars($img['path']) ?>')">

<a 
href="delete_image.php?id=<?= $img['id'] ?>"
class="delete-btn"
onclick="return confirm('Dzēst attēlu?')"> <i class="fas fa-trash"></i> </a>

</div>

<?php endforeach; ?>

</div>

</div>
</section>

<!-- LIGHTBOX -->

<div id="lightbox" onclick="closeLightbox()">
<img id="lightbox-img">
</div>

<script>

function openLightbox(src){
document.getElementById("lightbox-img").src = src;
document.getElementById("lightbox").style.display="flex";
}

function closeLightbox(){
document.getElementById("lightbox").style.display="none";
}

</script>

<style>

.gallery-grid{
display:grid;
grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
gap:20px;
}

.gallery-item{
position:relative;
aspect-ratio:1/1;
overflow:hidden;
border-radius:10px;
}

.gallery-item img{
width:100%;
height:100%;
object-fit:cover;
cursor:pointer;
transition:.3s;
}

.gallery-item img:hover{
transform:scale(1.05);
}

.delete-btn{
position:absolute;
top:10px;
right:10px;
background:#e63946;
color:white;
padding:6px 8px;
border-radius:6px;
}

#lightbox{
position:fixed;
inset:0;
background:rgba(0,0,0,.85);
display:none;
align-items:center;
justify-content:center;
z-index:999;
}

#lightbox img{
max-width:90%;
max-height:90%;
border-radius:10px;
}

</style>

<?php require "../assets/footer.php"; ?>

<?php
$lapa  = "Aktualitātes";
$title = "Aktualitātes";

require "header.php";
require_once "../assets/database.php";

$news = [];

$sql = "SELECT * FROM cm_news ORDER BY publish_date DESC";
$result = $savienojums->query($sql);

while ($row = $result->fetch_assoc()) {
    $news[] = $row;
}
?>

<section class="section">
<div class="container">

<h2>Aktualitātes</h2>

<button class="btn btn-primary" onclick="openAddModal()">
Pievienot ziņu
</button>

<div class="cards">

<?php foreach ($news as $item): ?>

<article class="card">

<h3><?= htmlspecialchars($item['title']) ?></h3>

<p><?= htmlspecialchars($item['description']) ?></p>

<p>
<?= htmlspecialchars($item['category']) ?> |
<?= $item['publish_date'] ?>
</p>

<p>
Aktīvs: <?= $item['is_active'] ? "Jā" : "Nē" ?>
</p>

<button 
class="btn btn-outline"
onclick="openEditModal(
'<?= $item['id'] ?>',
'<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>',
'<?= htmlspecialchars($item['description'], ENT_QUOTES) ?>',
'<?= htmlspecialchars($item['category'], ENT_QUOTES) ?>',
'<?= $item['publish_date'] ?>'
)">
Rediģēt
</button>

<a href="delete_news.php?id=<?= $item['id'] ?>"
class="btn btn-red"
onclick="return confirm('Dzēst ziņu?')">
Dzēst
</a>

</article>

<?php endforeach; ?>

</div>
</div>
</section>


<!-- POPUP MODAL -->

<div id="newsModal" class="modal">

<div class="modal-content">

<span class="close" onclick="closeModal()">&times;</span>

<h3 id="modalTitle">Pievienot ziņu</h3>

<form method="POST" action="save_news.php">

<input type="hidden" name="id" id="news_id">

<label>Nosaukums</label>
<input type="text" name="title" id="title" required>

<label>Apraksts</label>
<textarea name="description" id="description"></textarea>

<label>Kategorija</label>
<input type="text" name="category" id="category">

<label>Datums</label>
<input type="date" name="publish_date" id="publish_date">

<button type="submit" class="btn btn-primary">
Saglabāt
</button>

</form>

</div>
</div>


<style>

.modal{
display:none;
position:fixed;
left:0;
top:0;
width:100%;
height:100%;
background:rgba(0,0,0,0.6);
z-index:1000;
}

.modal-content{
background:white;
padding:25px;
width:400px;
margin:10% auto;
border-radius:8px;
}

.close{
float:right;
font-size:22px;
cursor:pointer;
}

</style>


<script>

function openAddModal(){

document.getElementById("modalTitle").innerText="Pievienot ziņu";

document.getElementById("news_id").value="";
document.getElementById("title").value="";
document.getElementById("description").value="";
document.getElementById("category").value="";
document.getElementById("publish_date").value="";

document.getElementById("newsModal").style.display="block";
}

function openEditModal(id,title,description,category,date){

document.getElementById("modalTitle").innerText="Rediģēt ziņu";

document.getElementById("news_id").value=id;
document.getElementById("title").value=title;
document.getElementById("description").value=description;
document.getElementById("category").value=category;
document.getElementById("publish_date").value=date;

document.getElementById("newsModal").style.display="block";
}

function closeModal(){
document.getElementById("newsModal").style.display="none";
}

</script>

<?php require "../assets/footer.php"; ?>
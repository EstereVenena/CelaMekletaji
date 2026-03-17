<?php
$lapa  = "Klubi";
$title = "Klubi";

require "header.php";
require_once "../assets/database.php";

$clubs = [];

$sql = "
SELECT 
c.id,
c.name,
c.address,
GROUP_CONCAT(p.label SEPARATOR ', ') AS programs
FROM cm_clubs c
LEFT JOIN cm_club_programs cp ON c.id = cp.club_id
LEFT JOIN cm_programs p ON cp.program_id = p.id
GROUP BY c.id
ORDER BY c.address
";

$result = $savienojums->query($sql);

while ($row = $result->fetch_assoc()) {
    $clubs[] = $row;
}
?>

<section class="section">
<div class="container">

<h2>Klubi</h2>

<button class="btn btn-primary" onclick="openAddModal()">
Pievienot klubu
</button>

<div class="cards">

<?php foreach ($clubs as $club): ?>

<article class="card">

<h3><?= htmlspecialchars($club['name']) ?></h3>

<p>
<i class="fas fa-location-dot"></i>
<?= htmlspecialchars($club['address']) ?>
</p>

<p>
Programmas:
<?= htmlspecialchars($club['programs'] ?? 'Nav programmas') ?>
</p>

<button 
class="btn btn-outline"
onclick="openEditModal(
'<?= $club['id'] ?>',
'<?= htmlspecialchars($club['name'], ENT_QUOTES) ?>',
'<?= htmlspecialchars($club['address'], ENT_QUOTES) ?>'
)">
Rediģēt
</button>

<a href="delete_club.php?id=<?= $club['id'] ?>"
class="btn btn-red"
onclick="return confirm('Dzēst klubu?')">
Dzēst
</a>

</article>

<?php endforeach; ?>

</div>
</div>
</section>


<!-- MODAL -->

<div id="clubModal" class="modal">

<div class="modal-content">

<span class="close" onclick="closeModal()">&times;</span>

<h3 id="modalTitle">Pievienot klubu</h3>

<form method="POST" action="save_club.php">

<input type="hidden" name="id" id="club_id">

<label>Nosaukums</label>
<input type="text" name="name" id="name" required>

<label>Adrese</label>
<input type="text" name="address" id="address" required>

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

document.getElementById("modalTitle").innerText="Pievienot klubu";

document.getElementById("club_id").value="";
document.getElementById("name").value="";
document.getElementById("address").value="";

document.getElementById("clubModal").style.display="block";
}

function openEditModal(id,name,address){

document.getElementById("modalTitle").innerText="Rediģēt klubu";

document.getElementById("club_id").value=id;
document.getElementById("name").value=name;
document.getElementById("address").value=address;

document.getElementById("clubModal").style.display="block";
}

function closeModal(){
document.getElementById("clubModal").style.display="none";
}

</script>

<?php require "../assets/footer.php"; ?>
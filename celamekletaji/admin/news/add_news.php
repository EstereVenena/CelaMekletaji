<?php
require_once "../assets/database.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

$title = $_POST['title'];
$description = $_POST['description'];
$category = $_POST['category'];
$publish_date = $_POST['publish_date'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$is_active = isset($_POST['is_active']) ? 1 : 0;

$sql = "INSERT INTO cm_news 
(title, description, category, publish_date, start_date, end_date, is_active)
VALUES
('$title','$description','$category','$publish_date','$start_date','$end_date','$is_active')";

$savienojums->query($sql);

header("Location: news.php");
exit;
}
?>

<form method="POST">

<label>Nosaukums</label>
<input type="text" name="title" required>

<label>Apraksts</label>
<textarea name="description"></textarea>

<label>Kategorija</label>
<input type="text" name="category">

<label>Publicēšanas datums</label>
<input type="date" name="publish_date">

<label>Sākuma datums</label>
<input type="date" name="start_date" required>

<label>Beigu datums</label>
<input type="date" name="end_date">

<label>
<input type="checkbox" name="is_active" checked>
Aktīva ziņa
</label>

<button type="submit">Saglabāt</button>

</form>
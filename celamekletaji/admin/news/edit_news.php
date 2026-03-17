<?php
require_once "../assets/database.php";

$id = $_GET['id'];

$sql = "SELECT * FROM cm_news WHERE id=$id";
$result = $savienojums->query($sql);
$news = $result->fetch_assoc();

if ($_SERVER["REQUEST_METHOD"] == "POST") {

$title = $_POST['title'];
$description = $_POST['description'];
$category = $_POST['category'];
$publish_date = $_POST['publish_date'];
$start_date = $_POST['start_date'];
$end_date = $_POST['end_date'];
$is_active = isset($_POST['is_active']) ? 1 : 0;

$sql = "UPDATE cm_news SET
title='$title',
description='$description',
category='$category',
publish_date='$publish_date',
start_date='$start_date',
end_date='$end_date',
is_active='$is_active'
WHERE id=$id";

$savienojums->query($sql);

header("Location: news.php");
exit;
}
?>

<form method="POST">

<label>Nosaukums</label>
<input type="text" name="title" value="<?= $news['title'] ?>">

<label>Apraksts</label>
<textarea name="description"><?= $news['description'] ?></textarea>

<label>Kategorija</label>
<input type="text" name="category" value="<?= $news['category'] ?>">

<label>Publicēšanas datums</label>
<input type="date" name="publish_date" value="<?= $news['publish_date'] ?>">

<label>Sākuma datums</label>
<input type="date" name="start_date" value="<?= $news['start_date'] ?>">

<label>Beigu datums</label>
<input type="date" name="end_date" value="<?= $news['end_date'] ?>">

<label>
<input type="checkbox" name="is_active" <?= $news['is_active'] ? "checked" : "" ?>>
Aktīva
</label>

<button type="submit">Saglabāt</button>

</form>
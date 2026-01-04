<?php
require_once "assets/database.php";

$kluda = "";
$veiksmigi = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $vards     = trim($_POST["vards"]);
    $uzvards   = trim($_POST["uzvards"]);
    $epasts    = trim($_POST["epasts"]);
    $parole1   = $_POST["parole"];
    $parole2   = $_POST["parole2"];

    // Vienkāršs lietotājvārds no e-pasta
    $lietotajvards = explode("@", $epasts)[0];

    if ($parole1 !== $parole2) {
        $kluda = "Paroles nesakrīt";
    } else {

        // Pārbauda, vai e-pasts vai lietotājvārds jau eksistē
        $checkSql = "
            SELECT lietotajs_id 
            FROM cm_lietotaji 
            WHERE epasts = ? OR lietotajvards = ?
            LIMIT 1
        ";
        $stmt = $savienojums->prepare($checkSql);
        $stmt->bind_param("ss", $epasts, $lietotajvards);
        $stmt->execute();
        $rez = $stmt->get_result();

        if ($rez->num_rows > 0) {
            $kluda = "Lietotājs ar šādu e-pastu jau eksistē";
        } else {

            $hash = password_hash($parole1, PASSWORD_DEFAULT);

            $sql = "
                INSERT INTO cm_lietotaji
                (lietotajvards, vards, uzvards, epasts, parole, loma, statuss)
                VALUES (?, ?, ?, ?, ?, 'Vecāks', 'gaida')
            ";

            $stmt = $savienojums->prepare($sql);
            $stmt->bind_param(
                "sssss",
                $lietotajvards,
                $vards,
                $uzvards,
                $epasts,
                $hash
            );

            if ($stmt->execute()) {
                $veiksmigi = "Reģistrācija veiksmīga! Pagaidi apstiprinājumu.";
            } else {
                $kluda = "Kļūda reģistrējot lietotāju";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reģistrācija | Ceļa meklētāji</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header class="prof-header">
    <div class="header-container">
        <div class="logo">Ceļa meklētāji</div>
        <nav class="main-nav">
            <a href="index.php" class="nav-link">Sākums</a>
            <a href="login.php" class="nav-link">Pieslēgties</a>
        </nav>
    </div>
</header>

<main>
<section class="section">
    <h2>Reģistrēties</h2>

    <div class="box" style="max-width:450px;margin:2rem auto;">

        <?php if ($kluda): ?>
            <p class="error"><?= htmlspecialchars($kluda) ?></p>
        <?php endif; ?>

        <?php if ($veiksmigi): ?>
            <p class="success"><?= htmlspecialchars($veiksmigi) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label>Vārds</label>
            <input type="text" name="vards" required>

            <label>Uzvārds</label>
            <input type="text" name="uzvards" required>

            <label>E-pasts</label>
            <input type="email" name="epasts" required>

            <label>Parole</label>
            <input type="password" name="parole" required>

            <label>Atkārtot paroli</label>
            <input type="password" name="parole2" required>

            <button type="submit" class="btn">Reģistrēties</button>
        </form>

        <p style="margin-top:1rem;">
            Jau ir konts?
            <a href="login.php" style="color:var(--red);font-weight:700;">
                Pieslēgties
            </a>
        </p>

    </div>
</section>
</main>

<footer>
    © 2026 Ceļa meklētāji
</footer>

</body>
</html>

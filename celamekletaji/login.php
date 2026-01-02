<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pieslēgties | Ceļa meklētāji</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<main class="login-page">

    <div class="login-card">

        <!-- Virsraksts augšā -->
        <div class="login-title">
            <h1>CEĻA MEKLĒTĀJI</h1>
        </div>

        <!-- Logo -->
        <div class="login-logo">
            <img src="images/logo.png" alt="Ceļa meklētāji logo">
        </div>

        <!-- Forma -->
        <form class="login-form">
            <input type="text" placeholder="E-pasts vai lietotājvārds">
            
            <div class="password-field">
                <input type="password" placeholder="Parole">
                <span class="eye">👁</span>
            </div>

            <button class="btn">Pieslēgties</button>

            <a href="index.html" class="btn outline">
                Doties uz sākumlapu
            </a>
        </form>

        <!-- Apakša -->
        <p class="login-footer">
            Ceļa meklētāji © 2026
        </p>

    </div>

</main>

</body>
</html>






<?php
session_start();
require_once("../database.php");

$kluda = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $lietotajvards = $_POST['lietotajvards'];
    $parole = $_POST['parole'];

    $sql = "SELECT * FROM viesnicas_darbinieki WHERE lietotajvards = ?";
    $stmt = $savienojums->prepare($sql);
    $stmt->bind_param("s", $lietotajvards);
    $stmt->execute();
    $rezultats = $stmt->get_result();

    if ($rezultats->num_rows === 1) {
        $lietotajs = $rezultats->fetch_assoc();
        if (password_verify($parole, $lietotajs['parole'])) {
            $_SESSION['lietotajvards'] = $lietotajvards;
            header("Location: index.php");
            exit();
        } else {
            $kluda = "Nepareiza parole!";
        }
    } else {
        $kluda = "Lietotājs netika atrasts!";
    }
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Pieslēgšanās — Viesnīcas pārvaldība</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <div class="login-hero">
        <div class="login-card">
            <h2>Pieslēgšanās sistēmai</h2>

            <?php if ($kluda): ?>
                <p class="error"><?php echo $kluda; ?></p>
            <?php endif; ?>

            <form method="POST" action="">
                <label for="lietotajvards">Lietotājvārds</label>
                <input type="text" name="lietotajvards" id="lietotajvards" required>

                <label for="parole">Parole</label>
                <input type="password" name="parole" id="parole" required>

                <button type="submit" class="btn btn-primary">Ieiet</button>
            </form>
        </div>
    </div>
</body>
</html>

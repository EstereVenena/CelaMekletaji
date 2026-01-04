<?php
session_start();
require_once "assets/database.php";

$kluda = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["lietotajvards"]);
    $parole = $_POST["parole"];

    $sql = "
        SELECT lietotajs_id, lietotajvards, parole, loma, statuss
        FROM cm_lietotaji
        WHERE (lietotajvards = ? OR epasts = ?)
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($sql);
    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $rez = $stmt->get_result();

    if ($rez->num_rows === 1) {
        $lietotajs = $rez->fetch_assoc();

        if ($lietotajs["statuss"] !== "aktīvs") {
            $kluda = "Konts nav apstiprināts";
        } elseif (password_verify($parole, $lietotajs["parole"])) {

            $_SESSION["lietotajs_id"] = $lietotajs["lietotajs_id"];
            $_SESSION["lietotajvards"] = $lietotajs["lietotajvards"];
            $_SESSION["loma"] = $lietotajs["loma"];

            header("Location: dashboard.php");
            exit;
        } else {
            $kluda = "Nepareiza parole";
        }
    } else {
        $kluda = "Lietotājs netika atrasts";
    }
}
?>
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

        <div class="login-title">
            <h1>CEĻA MEKLĒTĀJI</h1>
        </div>

        <div class="login-logo">
            <img src="images/logo.png" alt="Ceļa meklētāji logo">
        </div>

        <?php if (!empty($kluda)): ?>
            <p class="error"><?= htmlspecialchars($kluda) ?></p>
        <?php endif; ?>

        <form class="login-form" method="POST">
            <input
                type="text"
                name="lietotajvards"
                placeholder="E-pasts vai lietotājvārds"
                required
            >

            <div class="password-field">
                <input
                    type="password"
                    name="parole"
                    placeholder="Parole"
                    required
                >
                <span class="eye">👁</span>
            </div>

            <button type="submit" class="btn">Pieslēgties</button>

            <a href="index.php" class="btn outline">
                Doties uz sākumlapu
            </a>
            <a href="register.php" class="btn outline">
                Reģistrēties
            </a>
        </form>

        <p class="login-footer">
            Ceļa meklētāji © 2026
        </p>

    </div>
</main>

</body>
</html>

<?php
require_once __DIR__ . "/../includes/config/database.php";

$kluda = "";
$veiksmigi = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $vards   = trim($_POST["vards"] ?? "");
    $uzvards = trim($_POST["uzvards"] ?? "");
    $epasts  = trim($_POST["epasts"] ?? "");
    $parole1 = $_POST["parole"] ?? "";
    $parole2 = $_POST["parole2"] ?? "";

    if (!$vards || !$uzvards || !$epasts || !$parole1 || !$parole2) {
        $kluda = "Aizpildi visus laukus";
    } elseif (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        $kluda = "Nederīgs e-pasta formāts";
    } elseif ($parole1 !== $parole2) {
        $kluda = "Paroles nesakrīt";
    } elseif (strlen($parole1) < 8) {
        $kluda = "Parolei jābūt vismaz 8 simbolus garai";
    } else {

        // Lietotājvārds no e-pasta
        $base = explode("@", $epasts)[0];
        $lietotajvards = $base;
        $i = 1;

        // Nodrošina unikālu lietotājvārdu
        while (true) {
            $stmt = $savienojums->prepare(
                "SELECT 1 FROM cm_lietotaji WHERE lietotajvards = ? LIMIT 1"
            );
            $stmt->bind_param("s", $lietotajvards);
            $stmt->execute();
            if ($stmt->get_result()->num_rows === 0) {
                break;
            }
            $lietotajvards = $base . $i++;
        }

        // Pārbauda e-pastu
        $stmt = $savienojums->prepare(
            "SELECT 1 FROM cm_lietotaji WHERE epasts = ? LIMIT 1"
        );
        $stmt->bind_param("s", $epasts);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $kluda = "Lietotājs ar šādu e-pastu jau eksistē";
        } else {

            $hash = password_hash($parole1, PASSWORD_DEFAULT);

            $stmt = $savienojums->prepare("
                INSERT INTO cm_lietotaji
                (lietotajvards, vards, uzvards, epasts, parole, loma, statuss)
                VALUES (?, ?, ?, ?, ?, 'Vecāks', 'gaida')
            ");

            $stmt->bind_param(
                "sssss",
                $lietotajvards,
                $vards,
                $uzvards,
                $epasts,
                $hash
            );

            if ($stmt->execute()) {
                $veiksmigi = "Reģistrācija veiksmīga! Pagaidi administratora apstiprinājumu.";
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
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="auth-page">
    <div class="auth-card">

        <div class="auth-head">
            <img src="../assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            <h1>Reģistrācija</h1>
            <p class="muted small">
                Izveido kontu un gaidi apstiprinājumu
            </p>
        </div>

        <?php if ($kluda): ?>
            <div class="error"><?= htmlspecialchars($kluda) ?></div>
        <?php endif; ?>

        <?php if ($veiksmigi): ?>
            <div class="success"><?= htmlspecialchars($veiksmigi) ?></div>
        <?php endif; ?>

        <form method="POST" class="auth-form">

            <input type="text" name="vards" placeholder="Vārds" required>
            <input type="text" name="uzvards" placeholder="Uzvārds" required>

            <input type="email" name="epasts" placeholder="E-pasts" required>

            <div style="position:relative;">
                <input
                    type="password"
                    name="parole"
                    placeholder="Parole"
                    required
                    id="parole1"
                >
                <span class="eye" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:1.2rem;">👁</span>
            </div>
            <div style="position:relative;">
                <input
                    type="password"
                    name="parole2"
                    placeholder="Atkārtot paroli"
                    required
                    id="parole2"
                >
                <span class="eye" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:1.2rem;">👁</span>
            </div>

            <button type="submit" class="btn btn-primary">
                Reģistrēties
            </button>

            <div class="auth-links">
                <a href="login.php">Jau ir konts? Pieslēgties</a>
                <a href="../index.php">← Atpakaļ uz sākumu</a>
            </div>

        </form>

        <p class="small muted" style="margin-top:1.5rem;text-align:center;">
            Ceļa meklētāji © 2026
        </p>

    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const eyes = document.querySelectorAll('.eye');
    eyes.forEach(eye => {
        eye.addEventListener('click', function() {
            const pwd = this.previousElementSibling;
            if (pwd.type === 'password') {
                pwd.type = 'text';
                this.textContent = '🙈';
            } else {
                pwd.type = 'password';
                this.textContent = '👁';
            }
        });
    });
});
</script>
</body>
</html>

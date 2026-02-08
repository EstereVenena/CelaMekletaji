<?php
require_once "assets/database.php";

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
<main class="auth-page auth-page--compact">
    <div class="auth-card auth-card--narrow">

        <div class="auth-head">
            <img src="images/logo.png" alt="Ceļa meklētāji logo">
            <h1>Reģistrācija</h1>
            <p class="muted small">
                Izveido kontu un gaidi apstiprinājumu
            </p>
        </div>

        <?php if ($kluda): ?>
            <p class="error"><?= htmlspecialchars($kluda) ?></p>
        <?php endif; ?>

        <?php if ($veiksmigi): ?>
            <p class="success"><?= htmlspecialchars($veiksmigi) ?></p>
        <?php endif; ?>

        <form method="POST" class="auth-form auth-form--compact">

            <input type="text" name="vards" placeholder="Vārds" required>
            <input type="text" name="uzvards" placeholder="Uzvārds" required>

            <input type="email" name="epasts" placeholder="E-pasts" required>

            <input type="password" name="parole" placeholder="Parole" required>
            <input type="password" name="parole2" placeholder="Atkārtot paroli" required>

            <button type="submit" class="btn btn-primary">
                Reģistrēties
            </button>

            <div class="auth-links">
                <a href="login.php">Jau ir konts? Pieslēgties</a>
                <a href="index.php">← Atpakaļ uz sākumu</a>
            </div>

        </form>

        <p class="login-footer">
            Ceļa meklētāji © 2026
        </p>

    </div>
</main>

</body>
</html>

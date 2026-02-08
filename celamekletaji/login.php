<?php
session_start();
require_once "assets/database.php";
require_once "assets/functions.php";


$kluda = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $login = trim($_POST["lietotajvards"]);
    $parole = $_POST["parole"];

    $sql = "
        SELECT lietotajs_id, lietotajvards, parole, loma, statuss,
               login_meginajumi, blokets_lidz
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

        // 🔒 1. Konts bloķēts?
        if ($lietotajs["blokets_lidz"] && strtotime($lietotajs["blokets_lidz"]) > time()) {
            
            logLogin($savienojums, $login, "konts_bloķēts");
            $kluda = "Konts īslaicīgi bloķēts. Mēģini vēlāk.";

        // ❌ 2. Konts nav aktīvs
        } elseif ($lietotajs["statuss"] !== "aktīvs") {
           
            logLogin($savienojums, $login, "konts_neaktivs");
             $kluda = "Konts nav apstiprināts";

        // ✅ 3. Parole pareiza
        } elseif (password_verify($parole, $lietotajs["parole"])) {

        logLogin($savienojums, $login, "veiksmigs");

            // reset mēģinājumiem
$stmt = $savienojums->prepare("
    UPDATE cm_lietotaji
    SET login_meginajumi = 0, blokets_lidz = NULL
    WHERE lietotajs_id = ?
");
$stmt->bind_param("i", $lietotajs["lietotajs_id"]);
$stmt->execute();

session_regenerate_id(true);

$_SESSION["lietotajs_id"] = $lietotajs["lietotajs_id"];
$_SESSION["lietotajvards"] = $lietotajs["lietotajvards"];
$_SESSION["loma"] = $lietotajs["loma"];

/* ⬇⬇⬇ TE IET REMEMBER ME ⬇⬇⬇ */

if (!empty($_POST["remember"])) {

    $token = bin2hex(random_bytes(32));

    setcookie(
        "remember_token",
        $token,
        time() + (60 * 60 * 24 * 30), // 30 dienas
        "/",
        "",
        true,
        true
    );

    $stmt = $savienojums->prepare("
        UPDATE cm_lietotaji
        SET remember_token = ?
        WHERE lietotajs_id = ?
    ");
    $stmt->bind_param("si", $token, $lietotajs["lietotajs_id"]);
    $stmt->execute();
}

/* ⬆⬆⬆ REMEMBER ME BEIGAS ⬆⬆⬆ */

header("Location: dashboard.php");
exit;

        // ❌ 4. Parole nepareiza
        } else {

        logLogin($savienojums, $login, "nepareiza_parole");
            $stmt = $savienojums->prepare("
                UPDATE cm_lietotaji
                SET login_meginajumi = login_meginajumi + 1,
                    blokets_lidz = IF(login_meginajumi >= 4, DATE_ADD(NOW(), INTERVAL 15 MINUTE), blokets_lidz)
                WHERE lietotajs_id = ?
            ");
            $stmt->bind_param("i", $lietotajs["lietotajs_id"]);
            $stmt->execute();

            $kluda = "Nepareizi pieslēgšanās dati";
        }

    } else {
        logLogin($savienojums, $login, "lietotajs_neeksiste");
        // drošs kļūdas ziņojums
        $kluda = "Nepareizi pieslēgšanās dati";
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
            <label class="remember">
            <input type="checkbox" name="remember">
            Atcerēties mani
        </label>

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

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

            // Redirect based on role
            $redirect = "dashboards/user.php"; // default
            if ($lietotajs["loma"] === "admin") {
                $redirect = "admin/index.php";
            } elseif ($lietotajs["loma"] === "moderators") {
                $redirect = "dashboards/moderator.php";
            } elseif ($lietotajs["loma"] === "Vecāks") {
                $redirect = "dashboards/parent.php";
            }

            header("Location: $redirect");
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
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<main class="auth-page">
    <div class="auth-card">
        <div class="auth-head">
            <img src="images/logo.png" alt="Ceļa meklētāji logo">
            <h1>Ceļa meklētāji</h1>
        </div>
        <?php if (!empty($kluda)): ?>
            <div class="error"><?= htmlspecialchars($kluda) ?></div>
        <?php endif; ?>
        <form class="auth-form" method="POST">
            <input
                type="text"
                name="lietotajvards"
                placeholder="E-pasts vai lietotājvārds"
                required
                autocomplete="username"
            >
            <div style="position:relative;">
                <input
                    type="password"
                    name="parole"
                    placeholder="Parole"
                    required
                    autocomplete="current-password"
                    id="parole"
                >
                <span class="eye" style="position:absolute;right:14px;top:50%;transform:translateY(-50%);cursor:pointer;font-size:1.2rem;">👁</span>
            </div>
            <label class="remember" style="margin-top:.5rem;">
                <input type="checkbox" name="remember">
                Atcerēties mani
            </label>
            <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Pieslēgties</button>
        </form>
        <div class="auth-links">
            <a href="register.php">Reģistrēties</a>
            <a href="index.php">Doties uz sākumlapu</a>
        </div>
        <p class="small muted" style="margin-top:1.5rem;text-align:center;">
            Ceļa meklētāji © 2026
        </p>
    </div>
</main>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const eye = document.querySelector('.eye');
    if(eye) {
        eye.addEventListener('click', function() {
            const pwd = document.getElementById('parole');
            if (pwd.type === 'password') {
                pwd.type = 'text';
                eye.textContent = '🙈';
            } else {
                pwd.type = 'password';
                eye.textContent = '👁';
            }
        });
    }
});
</script>
</body>
</html>

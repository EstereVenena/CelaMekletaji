<?php
session_start();

require_once __DIR__ . "/../includes/config/database.php";
require_once __DIR__ . "/../includes/functions/functions.php";

$kluda = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = trim($_POST["lietotajvards"] ?? "");
    $parole = $_POST["parole"] ?? "";

    $sql = "
        SELECT lietotajs_id, lietotajvards, epasts, parole, loma, statuss,
               login_meginajumi, blokets_lidz
        FROM cm_lietotaji
        WHERE (lietotajvards = ? OR epasts = ?)
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($sql);

    if (!$stmt) {
        die("Kļūda sagatavojot vaicājumu.");
    }

    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $rez = $stmt->get_result();

    if ($rez && $rez->num_rows === 1) {
        $lietotajs = $rez->fetch_assoc();

        if (!empty($lietotajs["blokets_lidz"]) && strtotime($lietotajs["blokets_lidz"]) > time()) {
            if (function_exists('logLogin')) {
                logLogin($savienojums, $login, "konts_blokets");
            }
            $kluda = "Konts īslaicīgi bloķēts. Mēģini vēlāk.";

        } elseif (($lietotajs["statuss"] ?? "") !== "aktīvs") {
            if (function_exists('logLogin')) {
                logLogin($savienojums, $login, "konts_neaktivs");
            }
            $kluda = "Konts nav apstiprināts.";

        } elseif (password_verify($parole, $lietotajs["parole"])) {
            if (function_exists('logLogin')) {
                logLogin($savienojums, $login, "veiksmigs");
            }

            $resetStmt = $savienojums->prepare("
                UPDATE cm_lietotaji
                SET login_meginajumi = 0, blokets_lidz = NULL
                WHERE lietotajs_id = ?
            ");

            if ($resetStmt) {
                $resetStmt->bind_param("i", $lietotajs["lietotajs_id"]);
                $resetStmt->execute();
                $resetStmt->close();
            }

            session_regenerate_id(true);

            $_SESSION["lietotajs_id"] = $lietotajs["lietotajs_id"];
            $_SESSION["lietotajvards"] = $lietotajs["lietotajvards"];
            $_SESSION["loma"] = $lietotajs["loma"];

            if (!empty($_POST["remember"])) {
                $token = bin2hex(random_bytes(32));

                setcookie(
                    "remember_token",
                    $token,
                    [
                        "expires"  => time() + (60 * 60 * 24 * 30),
                        "path"     => "/",
                        "secure"   => true,
                        "httponly" => true,
                        "samesite" => "Lax"
                    ]
                );

                $tokenStmt = $savienojums->prepare("
                    UPDATE cm_lietotaji
                    SET remember_token = ?
                    WHERE lietotajs_id = ?
                ");

                if ($tokenStmt) {
                    $tokenStmt->bind_param("si", $token, $lietotajs["lietotajs_id"]);
                    $tokenStmt->execute();
                    $tokenStmt->close();
                }
            }

            $redirect = "../dashboards/user.php";

            if (($lietotajs["loma"] ?? "") === "admin") {
                $redirect = "../dashboards/admin.php";
            } elseif (($lietotajs["loma"] ?? "") === "moderators") {
                $redirect = "../dashboards/moderator.php";
            } elseif (($lietotajs["loma"] ?? "") === "parent" || ($lietotajs["loma"] ?? "") === "Vecāks") {
                $redirect = "../dashboards/parent.php";
            }

            header("Location: " . $redirect);
            exit;

        } else {
            if (function_exists('logLogin')) {
                logLogin($savienojums, $login, "nepareiza_parole");
            }

            $failStmt = $savienojums->prepare("
                UPDATE cm_lietotaji
                SET login_meginajumi = login_meginajumi + 1,
                    blokets_lidz = IF(login_meginajumi >= 4, DATE_ADD(NOW(), INTERVAL 15 MINUTE), blokets_lidz)
                WHERE lietotajs_id = ?
            ");

            if ($failStmt) {
                $failStmt->bind_param("i", $lietotajs["lietotajs_id"]);
                $failStmt->execute();
                $failStmt->close();
            }

            $kluda = "Nepareizi pieslēgšanās dati.";
        }
    } else {
        if (function_exists('logLogin')) {
            logLogin($savienojums, $login, "lietotajs_neeksiste");
        }
        $kluda = "Nepareizi pieslēgšanās dati.";
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pieslēgties | Ceļa meklētāji</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<main class="auth-page">
    <div class="auth-card">
        <div class="auth-head">
            <img src="../assets/images/logos/logo.png" alt="Ceļa meklētāji logo">
            <h1>Ceļa meklētāji</h1>
        </div>

        <?php if (!empty($kluda)): ?>
            <div class="error" style="margin-bottom:1rem; padding:.85rem 1rem; border-radius:12px; background:rgba(198,40,40,.10); border:1px solid rgba(198,40,40,.20); color:#8b1e1e;">
                <?= htmlspecialchars($kluda) ?>
            </div>
        <?php endif; ?>

        <form class="auth-form" method="POST" action="">
            <input
                type="text"
                name="lietotajvards"
                placeholder="E-pasts vai lietotājvārds"
                required
                autocomplete="username"
                value="<?= htmlspecialchars($_POST['lietotajvards'] ?? '') ?>"
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
                <span class="eye" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer; font-size:1.2rem;">👁</span>
            </div>

            <label class="remember" style="margin-top:.5rem; display:flex; align-items:center; gap:.5rem;">
                <input type="checkbox" name="remember" <?= !empty($_POST['remember']) ? 'checked' : '' ?>>
                Atcerēties mani
            </label>

            <button type="submit" class="btn btn-primary" style="margin-top:1rem;">Pieslēgties</button>
        </form>

        <div class="auth-links">
            <a href="register.php">Reģistrēties</a>
            <a href="../index.php">Doties uz sākumlapu</a>
        </div>

        <p class="small muted" style="margin-top:1.5rem; text-align:center;">
            Ceļa meklētāji © <?php echo date('Y'); ?>
        </p>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const eye = document.querySelector('.eye');
    const pwd = document.getElementById('parole');

    if (eye && pwd) {
        eye.addEventListener('click', function () {
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
<?php
session_start();

require_once __DIR__ . "/../includes/config/database.php";
require_once __DIR__ . "/../includes/functions/functions.php";

$kluda = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $login = trim($_POST["lietotajvards"] ?? "");
    $parole = $_POST["parole"] ?? "";
    $sql = "
        SELECT 
            u.lietotajs_id,
            u.lietotajvards,
            u.epasts,
            u.parole,
            COALESCE(l.nosaukums, u.loma) AS loma,
            u.statuss,
            u.login_meginajumi,
            u.blokets_lidz
        FROM cm_lietotaji u
        LEFT JOIN cm_lomas l ON u.loma_id = l.loma_id
        WHERE (u.lietotajvards = ? OR u.epasts = ?)
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($sql);

    if (!$stmt) {
        die("Kļūda sagatavojot vaicājumu: " . $savienojums->error);
    }

    $stmt->bind_param("ss", $login, $login);
    $stmt->execute();
    $rez = $stmt->get_result();

    if ($rez && $rez->num_rows === 1) {
        $lietotajs = $rez->fetch_assoc();

        if (!empty($lietotajs["blokets_lidz"]) && strtotime($lietotajs["blokets_lidz"]) > time()) {
            $kluda = "Konts īslaicīgi bloķēts. Mēģini vēlāk.";

        } elseif (($lietotajs["statuss"] ?? "") !== "aktīvs") {
            $kluda = "Konts nav apstiprināts.";

        } elseif (password_verify($parole, $lietotajs["parole"])) {

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
            $_SESSION["club_id"] = $user["club_id"];

            $redirect = "../dashboards/user.php";

            $loma = trim($lietotajs["loma"]);

switch ($loma) {
    case "admin":
    case "Administrators":
        $redirect = "../dashboards/admin.php";
        break;

    case "Direktors":
    case "direktors":
        $redirect = "../dashboards/director.php";
        break;

    case "Skolotājs":
    case "teacher":
        $redirect = "../dashboards/teacher.php";
        break;

    case "Vecāks":
    case "parent":
        $redirect = "../dashboards/parent.php";
        break;

    case "Ceļameklētājs":
    case "Skolēns":
    case "Bērns":
    case "student":
    case "child":
        $redirect = "../dashboards/student.php";
        break;

    default:
        $redirect = "../dashboards/user.php";
        break;
}

            header("Location: " . $redirect);
            exit();

        } else {
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
        $kluda = "Nepareizi pieslēgšanās dati.";
    }

    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="lv">
<head>
    <meta charset="UTF-8">
    <title>Pieslēgties | Ceļa meklētāji</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
            <div class="error" style="margin-bottom:1rem;">
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
                <span class="eye" style="position:absolute; right:14px; top:50%; transform:translateY(-50%); cursor:pointer;">
                    👁
                </span>
            </div>

            <label class="remember">
                <input type="checkbox" name="remember">
                Atcerēties mani
            </label>

            <button type="submit" class="btn btn-primary">
                Pieslēgties
            </button>
        </form>

        <div class="auth-links">
            <a href="register.php">Reģistrēties</a>
            <a href="../index.php">Doties uz sākumlapu</a>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const eye = document.querySelector('.eye');
    const pwd = document.getElementById('parole');

    if (eye && pwd) {
        eye.addEventListener('click', function () {
            pwd.type = pwd.type === 'password' ? 'text' : 'password';
            eye.textContent = pwd.type === 'password' ? '👁' : '🙈';
        });
    }
});
</script>

</body>
</html>
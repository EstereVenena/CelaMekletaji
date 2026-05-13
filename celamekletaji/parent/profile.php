<?php
session_start();

$lapa  = "Mans profils";
$title = "Mans profils - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

if (!isset($_SESSION["lietotajs_id"]) || !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION["lietotajs_id"];
$error = null;
$success = null;

$user = [
    "lietotajvards" => "",
    "vards" => "",
    "uzvards" => "",
    "epasts" => "",
    "loma" => "",
    "statuss" => "",
    "Reg_datums" => ""
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lietotajvards = trim($_POST["lietotajvards"] ?? "");
    $vards         = trim($_POST["vards"] ?? "");
    $uzvards       = trim($_POST["uzvards"] ?? "");
    $epasts        = trim($_POST["epasts"] ?? "");
    $parole        = trim($_POST["parole"] ?? "");
    $parole2       = trim($_POST["parole2"] ?? "");

    if ($lietotajvards === "" || $vards === "" || $uzvards === "" || $epasts === "") {
        $error = "Lūdzu aizpildiet visus obligātos laukus.";
    } elseif (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        $error = "Lūdzu ievadiet derīgu e-pasta adresi.";
    } elseif ($parole !== "" && $parole !== $parole2) {
        $error = "Paroles nesakrīt.";
    } else {
        $checkSql = "
            SELECT lietotajs_id 
            FROM cm_lietotaji 
            WHERE lietotajvards = ? 
              AND lietotajs_id <> ?
            LIMIT 1
        ";

        if ($stmt = $savienojums->prepare($checkSql)) {
            $stmt->bind_param("si", $lietotajvards, $parentId);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $error = "Šāds lietotājvārds jau eksistē.";
            }

            $stmt->close();
        } else {
            $error = "Neizdevās pārbaudīt lietotājvārdu.";
        }

        if (!$error) {
            if ($parole !== "") {
                $hashedPassword = password_hash($parole, PASSWORD_DEFAULT);

                $updateSql = "
                    UPDATE cm_lietotaji
                    SET lietotajvards = ?,
                        vards = ?,
                        uzvards = ?,
                        epasts = ?,
                        parole = ?
                    WHERE lietotajs_id = ?
                ";

                $stmt = $savienojums->prepare($updateSql);

                if ($stmt) {
                    $stmt->bind_param(
                        "sssssi",
                        $lietotajvards,
                        $vards,
                        $uzvards,
                        $epasts,
                        $hashedPassword,
                        $parentId
                    );
                }
            } else {
                $updateSql = "
                    UPDATE cm_lietotaji
                    SET lietotajvards = ?,
                        vards = ?,
                        uzvards = ?,
                        epasts = ?
                    WHERE lietotajs_id = ?
                ";

                $stmt = $savienojums->prepare($updateSql);

                if ($stmt) {
                    $stmt->bind_param(
                        "ssssi",
                        $lietotajvards,
                        $vards,
                        $uzvards,
                        $epasts,
                        $parentId
                    );
                }
            }

            if (!$stmt) {
                $error = "Neizdevās sagatavot profila saglabāšanu.";
            } elseif ($stmt->execute()) {
                $_SESSION["lietotajvards"] = $lietotajvards;
                $success = "Profils veiksmīgi atjaunināts.";
            } else {
                $error = "Neizdevās saglabāt izmaiņas.";
            }

            if ($stmt) {
                $stmt->close();
            }
        }
    }
}

$sql = "
    SELECT 
        lietotajvards,
        vards,
        uzvards,
        epasts,
        loma,
        statuss,
        Reg_datums
    FROM cm_lietotaji
    WHERE lietotajs_id = ?
    LIMIT 1
";

if ($stmt = $savienojums->prepare($sql)) {
    $stmt->bind_param("i", $parentId);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $user = $row;
    } else {
        $error = "Profila dati netika atrasti.";
    }

    $stmt->close();
} else {
    $error = "Neizdevās sagatavot profila vaicājumu.";
}

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<main class="dashboard-main">
    <div class="container">

        <div class="dashboard-header">
            <h2>Mans profils</h2>
            <p class="lead">Šeit varat apskatīt un mainīt sava vecāka profila informāciju.</p>
        </div>

        <?php if ($error): ?>
            <div class="dashboard-card" style="border-left:4px solid #c0392b;">
                <p class="muted"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="dashboard-card" style="border-left:4px solid #2ecc71;">
                <p class="muted"><?php echo htmlspecialchars($success); ?></p>
            </div>
        <?php endif; ?>

        <div class="dashboard-content">

            <div class="dashboard-card">
                <h3>Profila informācija</h3>
                <p class="muted">Pamata konta dati un sistēmas statuss.</p>

                <div class="divider"></div>

                <p><strong>Loma:</strong> <?php echo htmlspecialchars($user["loma"] ?? "—"); ?></p>
                <p><strong>Statuss:</strong> <?php echo htmlspecialchars($user["statuss"] ?? "—"); ?></p>
                <p>
                    <strong>Reģistrēts:</strong>
                    <?php
                    echo !empty($user["Reg_datums"])
                        ? htmlspecialchars(date("d.m.Y H:i", strtotime($user["Reg_datums"])))
                        : "—";
                    ?>
                </p>
            </div>

            <div class="dashboard-card">
                <h3>Rediģēt profilu</h3>
                <p class="muted">Mainiet tikai tos datus, kurus nepieciešams atjaunināt.</p>

                <div class="divider"></div>

                <form method="post" class="form">

                    <div class="form-group">
                        <label for="lietotajvards">Lietotājvārds *</label>
                        <input
                            type="text"
                            id="lietotajvards"
                            name="lietotajvards"
                            value="<?php echo htmlspecialchars($user["lietotajvards"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="vards">Vārds *</label>
                        <input
                            type="text"
                            id="vards"
                            name="vards"
                            value="<?php echo htmlspecialchars($user["vards"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="uzvards">Uzvārds *</label>
                        <input
                            type="text"
                            id="uzvards"
                            name="uzvards"
                            value="<?php echo htmlspecialchars($user["uzvards"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="form-group">
                        <label for="epasts">E-pasts *</label>
                        <input
                            type="email"
                            id="epasts"
                            name="epasts"
                            value="<?php echo htmlspecialchars($user["epasts"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="divider"></div>

                    <h4>Mainīt paroli</h4>
                    <p class="muted small">Ja paroli nevēlaties mainīt, atstājiet abus laukus tukšus.</p>

                    <div class="form-group">
                        <label for="parole">Jaunā parole</label>
                        <input
                            type="password"
                            id="parole"
                            name="parole"
                        >
                    </div>

                    <div class="form-group">
                        <label for="parole2">Atkārtot jauno paroli</label>
                        <input
                            type="password"
                            id="parole2"
                            name="parole2"
                        >
                    </div>

                    <div style="display:flex; gap:.6rem; flex-wrap:wrap; margin-top:1rem;">
                        <button type="submit" class="btn btn-primary">
                            Saglabāt izmaiņas
                        </button>

                        <a href="<?php echo BASE_URL; ?>dashboards/parent.php" class="btn btn-outline">
                            Atpakaļ
                        </a>
                    </div>

                </form>
            </div>

        </div>
    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
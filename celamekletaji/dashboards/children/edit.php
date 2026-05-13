<?php
session_start();

$lapa  = "Rediģēt bērnu";
$title = "Rediģēt bērnu - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

if (!isset($_SESSION["lietotajs_id"]) || !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)) {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION["lietotajs_id"];
$childId  = (int) ($_GET["id"] ?? 0);

$error = null;
$success = null;

$child = [
    "lietotajs_id" => "",
    "lietotajvards" => "",
    "vards" => "",
    "uzvards" => "",
    "epasts" => "",
    "loma" => "",
    "statuss" => "",
    "relationship" => "aizbildnis"
];

if ($childId <= 0) {
    header("Location: manage.php");
    exit();
}

/* Pārbauda, vai bērns tiešām pieder šim vecākam */
$checkSql = "
    SELECT 
        c.lietotajs_id,
        c.lietotajvards,
        c.vards,
        c.uzvards,
        c.epasts,
        c.loma,
        c.statuss,
        pc.relationship
    FROM cm_parent_children pc
    INNER JOIN cm_lietotaji c
        ON c.lietotajs_id = pc.child_id
    WHERE pc.parent_id = ?
      AND pc.child_id = ?
      AND c.statuss <> 'dzēsts'
    LIMIT 1
";

if ($stmt = $savienojums->prepare($checkSql)) {
    $stmt->bind_param("ii", $parentId, $childId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {
        $child = $row;
    } else {
        header("Location: manage.php");
        exit();
    }

    $stmt->close();
} else {
    $error = "Neizdevās ielādēt bērna datus.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $lietotajvards = trim($_POST["lietotajvards"] ?? "");
    $vards         = trim($_POST["vards"] ?? "");
    $uzvards       = trim($_POST["uzvards"] ?? "");
    $epasts        = trim($_POST["epasts"] ?? "");
    $relationship  = trim($_POST["relationship"] ?? "aizbildnis");
    $parole        = trim($_POST["parole"] ?? "");
    $parole2       = trim($_POST["parole2"] ?? "");

    $allowedRelationships = ["māte", "tēvs", "aizbildnis", "ģimenes loceklis"];

    if ($lietotajvards === "" || $vards === "" || $uzvards === "" || $epasts === "") {
        $error = "Lūdzu aizpildiet visus obligātos laukus.";
    } elseif (!filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        $error = "Lūdzu ievadiet derīgu e-pasta adresi.";
    } elseif (!in_array($relationship, $allowedRelationships, true)) {
        $error = "Nederīgs radniecības veids.";
    } elseif ($parole !== "" && $parole !== $parole2) {
        $error = "Paroles nesakrīt.";
    } else {
        $usernameSql = "
            SELECT lietotajs_id
            FROM cm_lietotaji
            WHERE lietotajvards = ?
              AND lietotajs_id <> ?
            LIMIT 1
        ";

        if ($stmt = $savienojums->prepare($usernameSql)) {
            $stmt->bind_param("si", $lietotajvards, $childId);
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

                $updateChildSql = "
                    UPDATE cm_lietotaji
                    SET lietotajvards = ?,
                        vards = ?,
                        uzvards = ?,
                        epasts = ?,
                        parole = ?
                    WHERE lietotajs_id = ?
                ";

                $stmt = $savienojums->prepare($updateChildSql);

                if ($stmt) {
                    $stmt->bind_param(
                        "sssssi",
                        $lietotajvards,
                        $vards,
                        $uzvards,
                        $epasts,
                        $hashedPassword,
                        $childId
                    );
                }
            } else {
                $updateChildSql = "
                    UPDATE cm_lietotaji
                    SET lietotajvards = ?,
                        vards = ?,
                        uzvards = ?,
                        epasts = ?
                    WHERE lietotajs_id = ?
                ";

                $stmt = $savienojums->prepare($updateChildSql);

                if ($stmt) {
                    $stmt->bind_param(
                        "ssssi",
                        $lietotajvards,
                        $vards,
                        $uzvards,
                        $epasts,
                        $childId
                    );
                }
            }

            if (!$stmt) {
                $error = "Neizdevās sagatavot bērna datu saglabāšanu.";
            } elseif (!$stmt->execute()) {
                $error = "Neizdevās saglabāt bērna datus.";
            }

            if ($stmt) {
                $stmt->close();
            }

            if (!$error) {
                $updateRelationSql = "
                    UPDATE cm_parent_children
                    SET relationship = ?,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE parent_id = ?
                      AND child_id = ?
                ";

                if ($stmt = $savienojums->prepare($updateRelationSql)) {
                    $stmt->bind_param("sii", $relationship, $parentId, $childId);

                    if ($stmt->execute()) {
                        $success = "Bērna dati veiksmīgi atjaunināti.";
                    } else {
                        $error = "Bērna dati saglabāti, bet neizdevās atjaunināt radniecību.";
                    }

                    $stmt->close();
                } else {
                    $error = "Bērna dati saglabāti, bet neizdevās sagatavot radniecības atjaunošanu.";
                }
            }
        }
    }

    if (!$error) {
        $child["lietotajvards"] = $lietotajvards;
        $child["vards"] = $vards;
        $child["uzvards"] = $uzvards;
        $child["epasts"] = $epasts;
        $child["relationship"] = $relationship;
    }
}

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<main class="dashboard-main">
    <div class="container">

        <div class="dashboard-header">
            <h2>Rediģēt bērnu</h2>
            <p class="lead">Šeit varat labot bērna profila informāciju.</p>
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

        <div class="dashboard-card">
            <h3>Bērna dati</h3>
            <p class="muted">Mainiet bērna informāciju un saglabājiet izmaiņas.</p>

            <div class="divider"></div>

            <form method="post" class="form">

                <div class="form-group">
                    <label for="lietotajvards">Lietotājvārds *</label>
                    <input
                        type="text"
                        id="lietotajvards"
                        name="lietotajvards"
                        value="<?php echo htmlspecialchars($child["lietotajvards"] ?? ""); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="vards">Vārds *</label>
                    <input
                        type="text"
                        id="vards"
                        name="vards"
                        value="<?php echo htmlspecialchars($child["vards"] ?? ""); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="uzvards">Uzvārds *</label>
                    <input
                        type="text"
                        id="uzvards"
                        name="uzvards"
                        value="<?php echo htmlspecialchars($child["uzvards"] ?? ""); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="epasts">E-pasts *</label>
                    <input
                        type="email"
                        id="epasts"
                        name="epasts"
                        value="<?php echo htmlspecialchars($child["epasts"] ?? ""); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="relationship">Radniecība / saistība</label>
                    <select id="relationship" name="relationship">
                        <?php
                        $relationships = ["māte", "tēvs", "aizbildnis", "ģimenes loceklis"];
                        foreach ($relationships as $item):
                        ?>
                            <option value="<?php echo htmlspecialchars($item); ?>" <?php echo (($child["relationship"] ?? "") === $item) ? "selected" : ""; ?>>
                                <?php echo htmlspecialchars(ucfirst($item)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="divider"></div>

                <h4>Mainīt bērna paroli</h4>
                <p class="muted small">Ja paroli nevēlaties mainīt, atstājiet abus laukus tukšus.</p>

                <div class="form-group">
                    <label for="parole">Jaunā parole</label>
                    <input type="password" id="parole" name="parole">
                </div>

                <div class="form-group">
                    <label for="parole2">Atkārtot jauno paroli</label>
                    <input type="password" id="parole2" name="parole2">
                </div>

                <div style="display:flex; gap:.6rem; flex-wrap:wrap; margin-top:1rem;">
                    <button type="submit" class="btn btn-primary">
                        Saglabāt izmaiņas
                    </button>

                    <a href="manage.php" class="btn btn-outline">
                        Atpakaļ
                    </a>
                </div>

            </form>
        </div>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
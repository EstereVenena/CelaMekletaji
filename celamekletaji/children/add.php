<?php
session_start();

$lapa  = "Pievienot bērnu";
$title = "Pievienot bērnu - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

// Tikai vecākiem
if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? "") !== "Vecāks") {
    header("Location: ../auth/login.php");
    exit();
}

$errors = [];

// Formas vērtības
$lietotajvards = "";
$vards = "";
$uzvards = "";
$epasts = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $parentId = (int)$_SESSION["lietotajs_id"];

    $lietotajvards = trim($_POST["lietotajvards"] ?? "");
    $vards         = trim($_POST["vards"] ?? "");
    $uzvards       = trim($_POST["uzvards"] ?? "");
    $epasts        = trim($_POST["epasts"] ?? "");
    $parole        = $_POST["parole"] ?? "";
    $parole2       = $_POST["parole2"] ?? "";

    $loma = "Ceļameklētājs";
    $statuss = "aktīvs";

    // ===== VALIDĀCIJA =====
    if ($lietotajvards === "") $errors[] = "Lietotājvārds ir obligāts.";
    if ($vards === "") $errors[] = "Vārds ir obligāts.";
    if ($uzvards === "") $errors[] = "Uzvārds ir obligāts.";

    if ($epasts === "" || !filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Nederīgs e-pasts.";
    }

    if ($parole === "" || strlen($parole) < 6) {
        $errors[] = "Parolei jābūt vismaz 6 simboliem.";
    }

    if ($parole !== $parole2) {
        $errors[] = "Paroles nesakrīt.";
    }

    // ===== UNIQUE CHECK =====
    if (empty($errors)) {
        $sql = "SELECT lietotajs_id FROM cm_lietotaji WHERE lietotajvards = ? OR epasts = ? LIMIT 1";
        $stmt = $savienojums->prepare($sql);
        $stmt->bind_param("ss", $lietotajvards, $epasts);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "Lietotājvārds vai e-pasts jau eksistē.";
        }

        $stmt->close();
    }

    // ===== INSERT =====
    if (empty($errors)) {

        $paroleHash = password_hash($parole, PASSWORD_DEFAULT);

        $savienojums->begin_transaction();

        try {

            // 1. izveido bērna kontu
            $sql = "INSERT INTO cm_lietotaji 
                    (lietotajvards, vards, uzvards, epasts, parole, loma, statuss)
                    VALUES (?, ?, ?, ?, ?, ?, ?)";

            $stmt = $savienojums->prepare($sql);
            $stmt->bind_param(
                "sssssss",
                $lietotajvards,
                $vards,
                $uzvards,
                $epasts,
                $paroleHash,
                $loma,
                $statuss
            );

            if (!$stmt->execute()) {
                throw new Exception("Neizdevās izveidot bērna kontu.");
            }

            $childId = $savienojums->insert_id;
            $stmt->close();

            // 2. sasaista ar vecāku
            $sql = "INSERT INTO cm_parent_children (parent_id, child_id) VALUES (?, ?)";
            $stmt = $savienojums->prepare($sql);
            $stmt->bind_param("ii", $parentId, $childId);

            if (!$stmt->execute()) {
                throw new Exception("Neizdevās sasaistīt bērnu ar vecāku.");
            }

            $stmt->close();

            $savienojums->commit();

            // redirect
            header("Location: ../dashboards/parent.php?child_added=1");
            exit();

        } catch (Exception $e) {
            $savienojums->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<main class="dashboard-main">
    <div class="container" style="max-width:700px;">
        
        <div class="dashboard-header">
            <h2>Pievienot bērnu</h2>
            <p class="lead">Izveido bērna kontu un piesaisti to savam profilam.</p>
        </div>

        <div class="dashboard-card">

            <?php if (!empty($errors)): ?>
                <div style="background:#ffecec; padding:1rem; border-radius:10px; margin-bottom:1rem;">
                    <?php foreach ($errors as $error): ?>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST">

                <div style="margin-bottom:1rem;">
                    <label>Lietotājvārds</label>
                    <input type="text" name="lietotajvards" class="form-control"
                        value="<?php echo htmlspecialchars($lietotajvards); ?>" required>
                </div>

                <div style="margin-bottom:1rem;">
                    <label>Vārds</label>
                    <input type="text" name="vards" class="form-control"
                        value="<?php echo htmlspecialchars($vards); ?>" required>
                </div>

                <div style="margin-bottom:1rem;">
                    <label>Uzvārds</label>
                    <input type="text" name="uzvards" class="form-control"
                        value="<?php echo htmlspecialchars($uzvards); ?>" required>
                </div>

                <div style="margin-bottom:1rem;">
                    <label>E-pasts</label>
                    <input type="email" name="epasts" class="form-control"
                        value="<?php echo htmlspecialchars($epasts); ?>" required>
                </div>

                <div style="margin-bottom:1rem;">
                    <label>Parole</label>
                    <input type="password" name="parole" class="form-control" required>
                </div>

                <div style="margin-bottom:1.2rem;">
                    <label>Atkārtot paroli</label>
                    <input type="password" name="parole2" class="form-control" required>
                </div>

                <div style="display:flex; gap:.6rem;">
                    <button type="submit" class="btn btn-primary">Pievienot bērnu</button>
                    <a href="../dashboards/parent.php" class="btn btn-outline">Atcelt</a>
                </div>

            </form>

        </div>
    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
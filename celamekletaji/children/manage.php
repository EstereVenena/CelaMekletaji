<?php
session_start();

$lapa  = "Bērnu pārvaldība";
$title = "Bērnu pārvaldība - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? "") !== "Vecāks") {
    header("Location: ../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION["lietotajs_id"];
$children = [];
$error = null;
$success = null;

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["remove_child"])) {
    $csrf = $_POST["csrf_token"] ?? "";
    $childId = (int) ($_POST["child_id"] ?? 0);

    if (!hash_equals($_SESSION["csrf_token"], $csrf)) {
        $error = "Drošības pārbaude neizdevās.";
    } elseif ($childId <= 0) {
        $error = "Nederīgs bērna ID.";
    } else {
        $deleteSql = "
            DELETE FROM cm_parent_children
            WHERE parent_id = ?
              AND child_id = ?
        ";

        if ($stmt = $savienojums->prepare($deleteSql)) {
            $stmt->bind_param("ii", $parentId, $childId);

            if ($stmt->execute()) {
                $success = "Bērns noņemts no jūsu saraksta.";
            } else {
                $error = "Neizdevās noņemt bērnu.";
            }

            $stmt->close();
        } else {
            $error = "Neizdevās sagatavot dzēšanas vaicājumu.";
        }
    }
}

$search = trim($_GET["search"] ?? "");

$sql = "
    SELECT 
        c.lietotajs_id,
        c.lietotajvards,
        c.vards,
        c.uzvards,
        c.epasts,
        c.loma,
        c.statuss,
        c.Reg_datums
    FROM cm_parent_children pc
    INNER JOIN cm_lietotaji c 
        ON c.lietotajs_id = pc.child_id
    WHERE pc.parent_id = ?
      AND c.statuss <> 'dzēsts'
";

$params = [$parentId];
$types = "i";

if ($search !== "") {
    $sql .= "
        AND (
            c.vards LIKE ?
            OR c.uzvards LIKE ?
            OR c.lietotajvards LIKE ?
            OR c.epasts LIKE ?
        )
    ";

    $like = "%" . $search . "%";
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $types .= "ssss";
}

$sql .= " ORDER BY c.vards ASC, c.uzvards ASC";

if ($stmt = $savienojums->prepare($sql)) {
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $children[] = $row;
        }
    } else {
        $error = "Neizdevās ielādēt bērnu sarakstu.";
    }

    $stmt->close();
} else {
    $error = "Neizdevās sagatavot SQL vaicājumu.";
}

require __DIR__ . "/../includes/templates/header-parent.php";
?>

<main class="dashboard-main">
    <div class="container">

        <div class="dashboard-header">
            <h2>Bērnu pārvaldība</h2>
            <p class="lead">Šeit varat apskatīt, rediģēt un pārvaldīt savus bērnus.</p>
        </div>

        <div class="section-title-row" style="margin-bottom:1rem;">
            <div>
                <h3 class="small">Mani bērni</h3>
                <p class="muted small">Kopā atrasti: <strong><?php echo count($children); ?></strong></p>
            </div>

            <div style="display:flex; gap:.6rem; align-items:center;">
                <a class="btn btn-outline btn-sm" href="../dashboards/parent.php">Atpakaļ</a>
                <a class="btn btn-primary btn-sm" href="../children/add.php">Pievienot bērnu</a>
            </div>
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

        <div class="dashboard-card" style="margin-bottom:1rem;">
            <form method="get" style="display:flex; gap:.6rem; flex-wrap:wrap;">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Meklēt pēc vārda, uzvārda, lietotājvārda vai e-pasta"
                    value="<?php echo htmlspecialchars($search); ?>"
                    style="flex:1; min-width:240px;"
                >

                <button class="btn btn-primary btn-sm" type="submit">Meklēt</button>

                <?php if ($search !== ""): ?>
                    <a class="btn btn-outline btn-sm" href="manage.php">Notīrīt</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="dashboard-card">
            <?php if (empty($children)): ?>
                <p class="muted">Nav atrasts neviens bērns.</p>
                <a class="btn btn-primary btn-sm" href="../children/add.php">Pievienot bērnu</a>
            <?php else: ?>
                <div class="cards">
                    <?php foreach ($children as $child): ?>
                        <div class="card">
                            <div class="program-head">
                                <div style="display:flex; gap:.75rem; align-items:center;">
                                    <div class="program-logo">
                                        <img src="../assets/images/avatar-placeholder.png" alt="avatar">
                                    </div>

                                    <div>
                                        <h4 style="margin:0;">
                                            <?php echo htmlspecialchars(trim(($child["vards"] ?? "") . " " . ($child["uzvards"] ?? ""))); ?>
                                        </h4>

                                        <p class="muted small" style="margin:.2rem 0 0;">
                                            Lietotājvārds: <?php echo htmlspecialchars($child["lietotajvards"] ?? "—"); ?>
                                        </p>

                                        <p class="muted small" style="margin:.2rem 0 0;">
                                            E-pasts: <?php echo htmlspecialchars($child["epasts"] ?? "—"); ?>
                                        </p>

                                        <p class="muted small" style="margin:.2rem 0 0;">
                                            Statuss: <?php echo htmlspecialchars($child["statuss"] ?? "—"); ?>
                                        </p>

                                        <p class="muted small" style="margin:.2rem 0 0;">
                                            Reģistrēts:
                                            <?php
                                            echo !empty($child["Reg_datums"])
                                                ? htmlspecialchars(date("d.m.Y H:i", strtotime($child["Reg_datums"])))
                                                : "—";
                                            ?>
                                        </p>
                                    </div>
                                </div>

                                <div style="display:flex; gap:.5rem; align-items:center; flex-wrap:wrap;">
                                    <a class="btn btn-outline btn-sm" href="../children/view.php?id=<?php echo (int)$child["lietotajs_id"]; ?>">
                                        Skatīt
                                    </a>

                                    <a class="btn btn-sm" href="../children/edit.php?id=<?php echo (int)$child["lietotajs_id"]; ?>">
                                        Rediģēt
                                    </a>

                                    <form method="post" onsubmit="return confirm('Vai tiešām noņemt šo bērnu no saraksta?');">
                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"]); ?>">
                                        <input type="hidden" name="child_id" value="<?php echo (int)$child["lietotajs_id"]; ?>">
                                        <button class="btn btn-outline btn-sm" type="submit" name="remove_child">
                                            Noņemt
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
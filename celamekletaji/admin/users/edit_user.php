<?php
session_start();

$lapa  = "Rediģēt lietotāju";
$title = "Rediģēt lietotāju";

require __DIR__ . "/../../includes/templates/header-admin.php";
require_once __DIR__ . "/../../includes/config/database.php";

if (!isset($_SESSION["lietotajs_id"]) || ($_SESSION["loma"] ?? "") !== "admin") {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$success = "";
$error = "";

$availableRoles = ["admin", "Vecāks", "Direktors", "Ceļameklētājs"];
$availableStatuses = ["aktīvs", "gaida", "dzēsts"];

$userId = (int)($_GET["id"] ?? 0);

if ($userId <= 0) {
    header("Location: " . BASE_URL . "admin/users/users_manage.php");
    exit();
}

$stmt = $savienojums->prepare("
    SELECT lietotajs_id, lietotajvards, vards, uzvards, epasts, loma, statuss
    FROM cm_lietotaji
    WHERE lietotajs_id = ?
    LIMIT 1
");

if (!$stmt) {
    die("Kļūda: neizdevās sagatavot vaicājumu.");
}

$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if (!$user) {
    header("Location: " . BASE_URL . "admin/users/users_manage.php");
    exit();
}

$formData = [
    "lietotajvards" => $user["lietotajvards"],
    "vards"         => $user["vards"],
    "uzvards"       => $user["uzvards"],
    "epasts"        => $user["epasts"],
    "loma"          => $user["loma"],
    "statuss"       => $user["statuss"]
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $formData["lietotajvards"] = trim($_POST["lietotajvards"] ?? "");
    $formData["vards"]         = trim($_POST["vards"] ?? "");
    $formData["uzvards"]       = trim($_POST["uzvards"] ?? "");
    $formData["epasts"]        = trim($_POST["epasts"] ?? "");
    $formData["loma"]          = trim($_POST["loma"] ?? "");
    $formData["statuss"]       = trim($_POST["statuss"] ?? "gaida");

    $newPassword       = $_POST["parole"] ?? "";
    $newPasswordRepeat = $_POST["parole_repeat"] ?? "";

    if (
        $formData["lietotajvards"] === "" ||
        $formData["vards"] === "" ||
        $formData["uzvards"] === "" ||
        $formData["epasts"] === "" ||
        $formData["loma"] === ""
    ) {
        $error = "Lūdzu aizpildi visus obligātos laukus.";
    } elseif (!filter_var($formData["epasts"], FILTER_VALIDATE_EMAIL)) {
        $error = "Lūdzu ievadi korektu e-pasta adresi.";
    } elseif (!in_array($formData["loma"], $availableRoles, true)) {
        $error = "Izvēlēta nederīga lietotāja loma.";
    } elseif (!in_array($formData["statuss"], $availableStatuses, true)) {
        $error = "Izvēlēts nederīgs lietotāja statuss.";
    } elseif ($newPassword !== "" || $newPasswordRepeat !== "") {
        if ($newPassword !== $newPasswordRepeat) {
            $error = "Paroles nesakrīt.";
        } elseif (mb_strlen($newPassword) < 6) {
            $error = "Jaunajai parolei jābūt vismaz 6 simbolus garai.";
        }
    }

    if ($error === "") {
        $checkStmt = $savienojums->prepare("
            SELECT lietotajs_id
            FROM cm_lietotaji
            WHERE lietotajvards = ? AND lietotajs_id != ?
            LIMIT 1
        ");

        if ($checkStmt) {
            $checkStmt->bind_param("si", $formData["lietotajvards"], $userId);
            $checkStmt->execute();
            $checkResult = $checkStmt->get_result();

            if ($checkResult && $checkResult->num_rows > 0) {
                $error = "Citam lietotājam jau ir šāds lietotājvārds.";
            }

            $checkStmt->close();
        } else {
            $error = "Neizdevās pārbaudīt lietotājvārdu.";
        }
    }

    if ($error === "") {
        $checkEmailStmt = $savienojums->prepare("
            SELECT lietotajs_id
            FROM cm_lietotaji
            WHERE epasts = ? AND lietotajs_id != ?
            LIMIT 1
        ");

        if ($checkEmailStmt) {
            $checkEmailStmt->bind_param("si", $formData["epasts"], $userId);
            $checkEmailStmt->execute();
            $checkEmailResult = $checkEmailStmt->get_result();

            if ($checkEmailResult && $checkEmailResult->num_rows > 0) {
                $error = "Citam lietotājam jau ir šāds e-pasts.";
            }

            $checkEmailStmt->close();
        } else {
            $error = "Neizdevās pārbaudīt e-pastu.";
        }
    }

    if ($error === "") {
        if ($newPassword !== "") {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $updateStmt = $savienojums->prepare("
                UPDATE cm_lietotaji
                SET lietotajvards = ?, vards = ?, uzvards = ?, epasts = ?, loma = ?, statuss = ?, parole = ?
                WHERE lietotajs_id = ?
            ");

            if ($updateStmt) {
                $updateStmt->bind_param(
                    "sssssssi",
                    $formData["lietotajvards"],
                    $formData["vards"],
                    $formData["uzvards"],
                    $formData["epasts"],
                    $formData["loma"],
                    $formData["statuss"],
                    $hashedPassword,
                    $userId
                );

                if ($updateStmt->execute()) {
                    $success = "Lietotāja dati veiksmīgi atjaunināti.";
                } else {
                    $error = "Neizdevās saglabāt izmaiņas.";
                }

                $updateStmt->close();
            } else {
                $error = "Neizdevās sagatavot atjaunināšanas vaicājumu.";
            }
        } else {
            $updateStmt = $savienojums->prepare("
                UPDATE cm_lietotaji
                SET lietotajvards = ?, vards = ?, uzvards = ?, epasts = ?, loma = ?, statuss = ?
                WHERE lietotajs_id = ?
            ");

            if ($updateStmt) {
                $updateStmt->bind_param(
                    "ssssssi",
                    $formData["lietotajvards"],
                    $formData["vards"],
                    $formData["uzvards"],
                    $formData["epasts"],
                    $formData["loma"],
                    $formData["statuss"],
                    $userId
                );

                if ($updateStmt->execute()) {
                    $success = "Lietotāja dati veiksmīgi atjaunināti.";
                } else {
                    $error = "Neizdevās saglabāt izmaiņas.";
                }

                $updateStmt->close();
            } else {
                $error = "Neizdevās sagatavot atjaunināšanas vaicājumu.";
            }
        }
    }
}
?>

<main class="user-form-page">
    <div class="container">
        <div class="form-shell">

            <section class="page-hero">
                <div>
                    <h1>Rediģēt lietotāju</h1>
                    <p>Maini lietotāja datus, statusu, lomu un paroli, ja vajag.</p>
                </div>
                <div class="hero-actions">
                    <a href="<?= BASE_URL ?>admin/users/users_manage.php">
                        ← Atpakaļ
                    </a>
                </div>
            </section>

            <section class="panel">
                <h2>Lietotāja dati</h2>

                <?php if ($success): ?>
                    <div class="alert success"><?= htmlspecialchars($success) ?></div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <form method="POST">
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="lietotajvards">Lietotājvārds *</label>
                            <input type="text" id="lietotajvards" name="lietotajvards" class="form-control"
                                   value="<?= htmlspecialchars($formData["lietotajvards"]) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="epasts">E-pasts *</label>
                            <input type="email" id="epasts" name="epasts" class="form-control"
                                   value="<?= htmlspecialchars($formData["epasts"]) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="vards">Vārds *</label>
                            <input type="text" id="vards" name="vards" class="form-control"
                                   value="<?= htmlspecialchars($formData["vards"]) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="uzvards">Uzvārds *</label>
                            <input type="text" id="uzvards" name="uzvards" class="form-control"
                                   value="<?= htmlspecialchars($formData["uzvards"]) ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="loma">Loma *</label>
                            <select id="loma" name="loma" class="form-control" required>
                                <?php foreach ($availableRoles as $role): ?>
                                    <option value="<?= htmlspecialchars($role) ?>" <?= $formData["loma"] === $role ? "selected" : "" ?>>
                                        <?= htmlspecialchars($role) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="statuss">Statuss *</label>
                            <select id="statuss" name="statuss" class="form-control" required>
                                <?php foreach ($availableStatuses as $status): ?>
                                    <option value="<?= htmlspecialchars($status) ?>" <?= $formData["statuss"] === $status ? "selected" : "" ?>>
                                        <?= htmlspecialchars(ucfirst($status)) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="parole">Jaunā parole</label>
                            <input type="password" id="parole" name="parole" class="form-control">
                            <small>Atstāj tukšu, ja paroli nevajag mainīt.</small>
                        </div>

                        <div class="form-group">
                            <label for="parole_repeat">Atkārtot jauno paroli</label>
                            <input type="password" id="parole_repeat" name="parole_repeat" class="form-control">
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Saglabāt izmaiņas</button>
                        <a href="<?= BASE_URL ?>admin/users/users_manage.php" class="btn btn-secondary">Atcelt</a>
                    </div>
                </form>
            </section>
        </div>
    </div>
</main>

<style>
.user-form-page {
    padding: 2rem 0 3rem;
    background:
        radial-gradient(circle at top right, rgba(30,79,161,0.08), transparent 30%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
    min-height: calc(100vh - 140px);
}
.form-shell { max-width: 900px; margin: 0 auto; }
.page-hero {
    display:flex; justify-content:space-between; align-items:center; gap:1rem;
    margin-bottom:1.5rem; padding:1.5rem; border-radius:24px;
    background:linear-gradient(135deg, #173f84, #1e4fa1); color:#fff;
}
.hero-actions a {
    display:inline-block; padding:.85rem 1rem; border-radius:14px;
    text-decoration:none; font-weight:700; color:#fff;
    background:rgba(255,255,255,.12); border:1px solid rgba(255,255,255,.18);
}
.panel {
    background:#fff; border:1px solid #e8eef8; border-radius:22px;
    padding:1.4rem; box-shadow:0 12px 28px rgba(16,24,40,.06);
}
.alert { padding:1rem 1.1rem; border-radius:14px; margin-bottom:1rem; font-weight:600; }
.alert.success { background:#ecfdf3; color:#027a48; border:1px solid #abefc6; }
.alert.error { background:#fef3f2; color:#b42318; border:1px solid #fecdca; }
.form-grid { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:1rem; }
.form-group label { display:block; margin-bottom:.4rem; font-weight:700; color:#344054; }
.form-control {
    width:100%; padding:.9rem 1rem; border:1px solid #d0d5dd;
    border-radius:12px; font-size:.95rem; box-sizing:border-box;
}
.form-actions { margin-top:1.3rem; display:flex; gap:.8rem; flex-wrap:wrap; }
.btn {
    display:inline-flex; align-items:center; gap:.5rem; padding:.9rem 1.1rem;
    border-radius:12px; border:none; cursor:pointer; text-decoration:none; font-weight:700;
}
.btn-primary { background:#173f84; color:#fff; }
.btn-secondary { background:#eef3ff; color:#173f84; }
@media (max-width: 768px) {
    .form-grid { grid-template-columns:1fr; }
    .page-hero { flex-direction:column; align-items:flex-start; }
}
</style>

<?php require __DIR__ . "/../../includes/templates/footer.php"; ?>
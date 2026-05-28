<?php
session_start();

$lapa  = "Pievienot bērnu";
$title = "Pievienot bērnu - Ceļa meklētāji";

require_once __DIR__ . "/../../includes/config/database.php";

/* ===============================
   DROŠĪBA: TIKAI VECĀKIEM
================================ */
if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$errors = [];

$lietotajvards = "";
$vards = "";
$uzvards = "";
$epasts = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $parentId = (int) $_SESSION["lietotajs_id"];

    $lietotajvards = trim($_POST["lietotajvards"] ?? "");
    $vards         = trim($_POST["vards"] ?? "");
    $uzvards       = trim($_POST["uzvards"] ?? "");
    $epasts        = trim($_POST["epasts"] ?? "");
    $parole        = $_POST["parole"] ?? "";
    $parole2       = $_POST["parole2"] ?? "";

    $loma = "Ceļameklētājs";
    $statuss = "aktīvs";

    /* ===============================
       VALIDĀCIJA
    ================================ */
    if ($lietotajvards === "") {
        $errors[] = "Lietotājvārds ir obligāts.";
    }

    if ($vards === "") {
        $errors[] = "Vārds ir obligāts.";
    }

    if ($uzvards === "") {
        $errors[] = "Uzvārds ir obligāts.";
    }

    if ($epasts === "" || !filter_var($epasts, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Nederīgs e-pasts.";
    }

    if ($parole === "" || strlen($parole) < 6) {
        $errors[] = "Parolei jābūt vismaz 6 simboliem.";
    }

    if ($parole !== $parole2) {
        $errors[] = "Paroles nesakrīt.";
    }

    /* ===============================
       lietotajvarda UNIKALITĀTES PĀRBAUDE
    ================================ */
    $sql = "
    SELECT lietotajs_id 
    FROM cm_lietotaji 
    WHERE lietotajvards = ? 
    LIMIT 1
";

if ($stmt = $savienojums->prepare($sql)) {
    $stmt->bind_param("s", $lietotajvards);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $errors[] = "Šāds lietotājvārds jau eksistē.";
    }

    $stmt->close();
} else {
    $errors[] = "Neizdevās pārbaudīt lietotājvārdu.";
}

    /* ===============================
       INSERT + SASAISTE
    ================================ */
    if (empty($errors)) {
        $paroleHash = password_hash($parole, PASSWORD_DEFAULT);

        $savienojums->begin_transaction();

        try {
            $sql = "
                INSERT INTO cm_lietotaji 
                    (lietotajvards, vards, uzvards, epasts, parole, loma, statuss)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $savienojums->prepare($sql);

            if (!$stmt) {
                throw new Exception("Neizdevās sagatavot bērna konta izveidi.");
            }

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

            $childId = (int) $savienojums->insert_id;
            $stmt->close();

            $sql = "
                INSERT INTO cm_parent_children 
                    (parent_id, child_id) 
                VALUES (?, ?)
            ";

            $stmt = $savienojums->prepare($sql);

            if (!$stmt) {
                throw new Exception("Neizdevās sagatavot bērna sasaisti ar vecāku.");
            }

            $stmt->bind_param("ii", $parentId, $childId);

            if (!$stmt->execute()) {
                throw new Exception("Neizdevās sasaistīt bērnu ar vecāku.");
            }

            $stmt->close();

            $savienojums->commit();

            header("Location: ../dashboards/parent.php?child_added=1");
            exit();

        } catch (Exception $e) {
            $savienojums->rollback();
            $errors[] = $e->getMessage();
        }
    }
}

require __DIR__ . "/../../includes/templates/header-parent.php";
?>

<style>
/* ===============================
   ADD CHILD PAGE
================================ */

.add-child-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.add-child-layout {
    display: grid;
    grid-template-columns: .9fr 1.1fr;
    gap: 1.4rem;
    align-items: start;
}

.add-child-info {
    position: sticky;
    top: 100px;
    overflow: hidden;
    padding: 1.8rem;
    border-radius: 28px;
    background:
        radial-gradient(circle at top right, rgba(244,196,48,.28), transparent 34%),
        linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
    box-shadow: 0 24px 60px rgba(23, 63, 132, 0.22);
}

.add-child-info::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.add-child-info > * {
    position: relative;
    z-index: 1;
}

.add-child-kicker {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .45rem .85rem;
    margin-bottom: 1rem;
    border-radius: 999px;
    background: rgba(255,255,255,.14);
    color: #f4c430;
    font-weight: 900;
}

.add-child-info h1 {
    margin: 0 0 .75rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.add-child-info p {
    margin: 0;
    color: rgba(255,255,255,.88);
    line-height: 1.75;
}

.add-child-checklist {
    display: grid;
    gap: .75rem;
    margin-top: 1.4rem;
}

.add-child-check {
    display: flex;
    gap: .75rem;
    align-items: flex-start;
    padding: .9rem;
    border-radius: 16px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.16);
}

.add-child-check i {
    color: #f4c430;
    margin-top: .18rem;
}

.add-child-check span {
    color: rgba(255,255,255,.9);
    line-height: 1.45;
}

.add-child-card {
    padding: 1.4rem;
    border-radius: 28px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 18px 42px rgba(16, 24, 40, 0.08);
}

.add-child-card-head {
    margin-bottom: 1.2rem;
}

.add-child-card-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.45rem;
}

.add-child-card-head p {
    margin: .35rem 0 0;
    color: #667085;
}

.add-child-errors {
    display: grid;
    gap: .45rem;
    margin-bottom: 1rem;
    padding: 1rem;
    border-radius: 18px;
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
    font-weight: 800;
}

.add-child-errors div {
    display: flex;
    gap: .55rem;
    align-items: flex-start;
}

.add-child-form {
    display: grid;
    gap: 1rem;
}

.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .9rem;
}

.form-group {
    display: grid;
    gap: .4rem;
}

.form-group label {
    color: #344054;
    font-weight: 900;
}

.form-group small {
    color: #667085;
    line-height: 1.4;
}

.form-control {
    width: 100%;
    padding: .85rem .95rem;
    border-radius: 14px;
    border: 1px solid #d0d8e8;
    background: #fff;
    color: #101828;
    font: inherit;
    outline: none;
    transition: .2s ease;
}

.form-control:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.password-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .9rem;
}

.form-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .4rem;
}

.form-actions .btn {
    min-width: 160px;
}

.add-child-note {
    margin-top: 1rem;
    padding: .95rem 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #e6eefb;
    color: #667085;
    line-height: 1.55;
}

.add-child-note strong {
    color: #173f84;
}

@media (max-width: 900px) {
    .add-child-layout {
        grid-template-columns: 1fr;
    }

    .add-child-info {
        position: relative;
        top: auto;
    }
}

@media (max-width: 640px) {
    .add-child-page {
        padding: 1.5rem 0 2.5rem;
    }

    .add-child-info,
    .add-child-card {
        border-radius: 22px;
    }

    .add-child-info,
    .add-child-card {
        padding: 1.2rem;
    }

    .form-row,
    .password-grid {
        grid-template-columns: 1fr;
    }

    .form-actions .btn {
        width: 100%;
    }
}
</style>

<main class="add-child-page">
    <div class="container">

        <div class="add-child-layout">

            <aside class="add-child-info">
                <div class="add-child-kicker">
                    <i class="fas fa-child-reaching"></i>
                    Bērna konta izveide
                </div>

                <h1>Pievienot bērnu</h1>

                <p>
                    Izveido bērna kontu un piesaisti to savam vecāka profilam.
                    Pēc izveides bērns būs redzams vecāku panelī.
                </p>

                <div class="add-child-checklist">
                    <div class="add-child-check">
                        <i class="fas fa-circle-check"></i>
                        <span>Bērnam tiek izveidots atsevišķs lietotāja konts.</span>
                    </div>

                    <div class="add-child-check">
                        <i class="fas fa-circle-check"></i>
                        <span>Konts automātiski tiek piesaistīts vecāka profilam.</span>
                    </div>

                    <div class="add-child-check">
                        <i class="fas fa-circle-check"></i>
                        <span>Vecāks vēlāk var pārvaldīt bērna informāciju.</span>
                    </div>
                </div>
            </aside>

            <section class="add-child-card">
                <div class="add-child-card-head">
                    <h2>Bērna dati</h2>
                    <p>Aizpildi pamatinformāciju, lai izveidotu jaunu bērna kontu.</p>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="add-child-errors">
                        <?php foreach ($errors as $error): ?>
                            <div>
                                <i class="fas fa-triangle-exclamation"></i>
                                <span><?= htmlspecialchars($error); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" class="add-child-form" autocomplete="off">

                    <div class="form-group">
                        <label for="lietotajvards">Lietotājvārds</label>
                        <input
                            type="text"
                            id="lietotajvards"
                            name="lietotajvards"
                            class="form-control"
                            value="<?= htmlspecialchars($lietotajvards); ?>"
                            required
                        >
                        <small>Šis būs bērna lietotājvārds pieslēgšanās sistēmai.</small>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="vards">Vārds</label>
                            <input
                                type="text"
                                id="vards"
                                name="vards"
                                class="form-control"
                                value="<?= htmlspecialchars($vards); ?>"
                                required
                            >
                        </div>

                        <div class="form-group">
                            <label for="uzvards">Uzvārds</label>
                            <input
                                type="text"
                                id="uzvards"
                                name="uzvards"
                                class="form-control"
                                value="<?= htmlspecialchars($uzvards); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="epasts">E-pasts</label>
                        <input
                            type="email"
                            id="epasts"
                            name="epasts"
                            class="form-control"
                            value="<?= htmlspecialchars($epasts); ?>"
                            required
                        >
                    </div>

                    <div class="password-grid">
                        <div class="form-group">
                            <label for="parole">Parole</label>
                            <input
                                type="password"
                                id="parole"
                                name="parole"
                                class="form-control"
                                required
                            >
                            <small>Vismaz 6 simboli.</small>
                        </div>

                        <div class="form-group">
                            <label for="parole2">Atkārtot paroli</label>
                            <input
                                type="password"
                                id="parole2"
                                name="parole2"
                                class="form-control"
                                required
                            >
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i>
                            Pievienot bērnu
                        </button>

                        <a href="../dashboards/parent.php" class="btn btn-outline">
                            Atcelt
                        </a>
                    </div>

                    <div class="add-child-note">
                        <strong>Piezīme:</strong>
                        bērna parole tiek saglabāta šifrētā veidā. Vecākam jānodod bērnam pieslēgšanās dati drošā veidā.
                    </div>

                </form>
            </section>

        </div>

    </div>
</main>

<?php require __DIR__ . "/../../includes/templates/footer.php"; ?>
<?php
session_start();

$lapa  = "Rediģēt bērnu";
$title = "Rediģēt bērnu - Ceļa meklētāji";

require_once __DIR__ . "/../../includes/config/database.php";

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)
) {
    header("Location: ../../auth/login.php");
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

/* ===============================
   PĀRBAUDA, VAI BĒRNS PIEDER VECĀKAM
================================ */
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

/* ===============================
   SAGLABĀŠANA
================================ */
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
    } elseif ($parole !== "" && strlen($parole) < 6) {
        $error = "Jaunajai parolei jābūt vismaz 6 simboliem.";
    } elseif ($parole !== "" && $parole !== $parole2) {
        $error = "Paroles nesakrīt.";
    } else {
        /* ===============================
           LIETOTĀJVĀRDA UNIKALITĀTE
        ================================ */
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

$childName = trim(($child["vards"] ?? "") . " " . ($child["uzvards"] ?? ""));
$childName = $childName !== "" ? $childName : "Bērns";
$childInitial = mb_strtoupper(mb_substr($childName, 0, 1));

require __DIR__ . "/../../includes/templates/header-parent.php";
?>

<style>
.child-edit-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.child-edit-layout {
    display: grid;
    grid-template-columns: .85fr 1.15fr;
    gap: 1.4rem;
    align-items: start;
}

.child-edit-side {
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

.child-edit-side::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.child-edit-side > * {
    position: relative;
    z-index: 1;
}

.child-kicker {
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

.child-avatar-big {
    width: 82px;
    height: 82px;
    display: grid;
    place-items: center;
    margin-bottom: 1rem;
    border-radius: 50%;
    background: rgba(255,255,255,.15);
    border: 2px solid rgba(244,196,48,.55);
    color: #f4c430;
    font-size: 2.1rem;
    font-weight: 1000;
}

.child-edit-side h1 {
    margin: 0 0 .5rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.child-edit-side p {
    margin: 0;
    color: rgba(255,255,255,.88);
    line-height: 1.7;
}

.child-side-info {
    display: grid;
    gap: .75rem;
    margin-top: 1.4rem;
}

.child-side-row {
    padding: .85rem;
    border-radius: 16px;
    background: rgba(255,255,255,.12);
    border: 1px solid rgba(255,255,255,.16);
}

.child-side-row span {
    display: block;
    color: rgba(255,255,255,.72);
    font-size: .86rem;
    margin-bottom: .2rem;
}

.child-side-row strong {
    display: block;
    color: #fff;
    overflow-wrap: anywhere;
}

.child-edit-card {
    padding: 1.4rem;
    border-radius: 28px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 18px 42px rgba(16, 24, 40, 0.08);
}

.child-card-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1.2rem;
}

.child-card-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.45rem;
}

.child-card-head p {
    margin: .35rem 0 0;
    color: #667085;
}

.child-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 800;
}

.child-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.child-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.child-form {
    display: grid;
    gap: 1rem;
}

.child-form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .9rem;
}

.child-form-group {
    display: grid;
    gap: .4rem;
}

.child-form-group label {
    color: #344054;
    font-weight: 900;
}

.child-form-group small {
    color: #667085;
    line-height: 1.4;
}

.child-form-control {
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

.child-form-control:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.child-section-title {
    display: flex;
    align-items: center;
    gap: .5rem;
    margin: .6rem 0 0;
    color: #173f84;
    font-size: 1.05rem;
    font-weight: 1000;
}

.child-note {
    padding: .95rem 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #e6eefb;
    color: #667085;
    line-height: 1.55;
}

.child-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: .3rem;
}

.child-actions .btn {
    min-width: 160px;
}

@media (max-width: 900px) {
    .child-edit-layout {
        grid-template-columns: 1fr;
    }

    .child-edit-side {
        position: relative;
        top: auto;
    }
}

@media (max-width: 640px) {
    .child-edit-page {
        padding: 1.5rem 0 2.5rem;
    }

    .child-edit-side,
    .child-edit-card {
        border-radius: 22px;
        padding: 1.2rem;
    }

    .child-card-head,
    .child-form-grid {
        grid-template-columns: 1fr;
        flex-direction: column;
    }

    .child-actions .btn {
        width: 100%;
    }
}
</style>

<main class="child-edit-page">
    <div class="container">
        <div class="child-edit-layout">

            <aside class="child-edit-side">
                <div class="child-kicker">
                    <i class="fas fa-pen-to-square"></i>
                    Bērna profila labošana
                </div>

                <div class="child-avatar-big">
                    <?= htmlspecialchars($childInitial); ?>
                </div>

                <h1><?= htmlspecialchars($childName); ?></h1>

                <p>
                    Šeit vari atjaunināt bērna pamatinformāciju, radniecības statusu un, ja nepieciešams, nomainīt paroli.
                </p>

                <div class="child-side-info">
                    <div class="child-side-row">
                        <span>Lietotājvārds</span>
                        <strong><?= htmlspecialchars($child["lietotajvards"] ?? "—"); ?></strong>
                    </div>

                    <div class="child-side-row">
                        <span>E-pasts</span>
                        <strong><?= htmlspecialchars($child["epasts"] ?? "—"); ?></strong>
                    </div>

                    <div class="child-side-row">
                        <span>Statuss</span>
                        <strong><?= htmlspecialchars($child["statuss"] ?? "—"); ?></strong>
                    </div>
                </div>
            </aside>

            <section class="child-edit-card">
                <div class="child-card-head">
                    <div>
                        <h2>Bērna dati</h2>
                        <p>Labojiet informāciju un saglabājiet izmaiņas.</p>
                    </div>

                    <a href="view.php?id=<?= (int)$childId; ?>" class="btn btn-outline btn-sm">
                        <i class="fas fa-eye"></i>
                        Skatīt profilu
                    </a>
                </div>

                <?php if ($error): ?>
                    <div class="child-alert error">
                        <i class="fas fa-triangle-exclamation"></i>
                        <span><?= htmlspecialchars($error); ?></span>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="child-alert success">
                        <i class="fas fa-circle-check"></i>
                        <span><?= htmlspecialchars($success); ?></span>
                    </div>
                <?php endif; ?>

                <form method="post" class="child-form">
                    <div class="child-form-group">
                        <label for="lietotajvards">Lietotājvārds *</label>
                        <input
                            class="child-form-control"
                            type="text"
                            id="lietotajvards"
                            name="lietotajvards"
                            value="<?= htmlspecialchars($child["lietotajvards"] ?? ""); ?>"
                            required
                        >
                    </div>

                    <div class="child-form-grid">
                        <div class="child-form-group">
                            <label for="vards">Vārds *</label>
                            <input
                                class="child-form-control"
                                type="text"
                                id="vards"
                                name="vards"
                                value="<?= htmlspecialchars($child["vards"] ?? ""); ?>"
                                required
                            >
                        </div>

                        <div class="child-form-group">
                            <label for="uzvards">Uzvārds *</label>
                            <input
                                class="child-form-control"
                                type="text"
                                id="uzvards"
                                name="uzvards"
                                value="<?= htmlspecialchars($child["uzvards"] ?? ""); ?>"
                                required
                            >
                        </div>
                    </div>

                    <div class="child-form-grid">
                        <div class="child-form-group">
                            <label for="epasts">E-pasts *</label>
                            <input
                                class="child-form-control"
                                type="email"
                                id="epasts"
                                name="epasts"
                                value="<?= htmlspecialchars($child["epasts"] ?? ""); ?>"
                                required
                            >
                            <small>E-pasts drīkst atkārtoties vairākiem bērniem.</small>
                        </div>

                        <div class="child-form-group">
                            <label for="relationship">Radniecība / saistība</label>
                            <select class="child-form-control" id="relationship" name="relationship">
                                <?php
                                $relationships = ["māte", "tēvs", "aizbildnis", "ģimenes loceklis"];
                                foreach ($relationships as $item):
                                ?>
                                    <option value="<?= htmlspecialchars($item); ?>" <?= (($child["relationship"] ?? "") === $item) ? "selected" : ""; ?>>
                                        <?= htmlspecialchars(ucfirst($item)); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="child-section-title">
                        <i class="fas fa-key"></i>
                        Mainīt bērna paroli
                    </div>

                    <div class="child-note">
                        Ja paroli nevēlies mainīt, atstāj abus paroles laukus tukšus.
                    </div>

                    <div class="child-form-grid">
                        <div class="child-form-group">
                            <label for="parole">Jaunā parole</label>
                            <input
                                class="child-form-control"
                                type="password"
                                id="parole"
                                name="parole"
                            >
                        </div>

                        <div class="child-form-group">
                            <label for="parole2">Atkārtot jauno paroli</label>
                            <input
                                class="child-form-control"
                                type="password"
                                id="parole2"
                                name="parole2"
                            >
                        </div>
                    </div>

                    <div class="child-actions">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-floppy-disk"></i>
                            Saglabāt izmaiņas
                        </button>

                        <a href="manage.php" class="btn btn-outline">
                            Atpakaļ
                        </a>
                    </div>
                </form>
            </section>

        </div>
    </div>
</main>

<?php require __DIR__ . "/../../includes/templates/footer.php"; ?>
<?php
session_start();

$lapa  = "Bērnu pārvaldība";
$title = "Bērnu pārvaldība - Ceļa meklētāji";

require_once __DIR__ . "/../../includes/config/database.php";

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["Vecāks", "parent"], true)
) {
    header("Location: ../../auth/login.php");
    exit();
}

$parentId = (int) $_SESSION["lietotajs_id"];
$children = [];
$error = null;
$success = null;

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

/* ===============================
   NOŅEM BĒRNU NO VECĀKA SARAKSTA
================================ */
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

/* ===============================
   BĒRNU SARAKSTS
================================ */
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

$childrenCount = count($children);

require __DIR__ . "/../../includes/templates/header-parent.php";
?>

<style>
.children-manage-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.children-hero {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: 1.2fr .8fr;
    gap: 1.5rem;
    align-items: center;
    margin-bottom: 1.4rem;
    padding: 2rem;
    border-radius: 28px;
    background:
        radial-gradient(circle at top right, rgba(244,196,48,.28), transparent 34%),
        linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
    box-shadow: 0 24px 60px rgba(23, 63, 132, 0.22);
}

.children-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.children-hero > * {
    position: relative;
    z-index: 1;
}

.children-kicker {
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

.children-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.children-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.children-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.children-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.children-hero-card strong {
    display: block;
    font-size: 2.1rem;
    line-height: 1;
    color: #f4c430;
}

.children-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.children-toolbar {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: center;
    margin-bottom: 1.2rem;
    padding: 1.1rem;
    border-radius: 22px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.children-search {
    display: flex;
    gap: .65rem;
    flex-wrap: wrap;
}

.children-search input {
    flex: 1;
    min-width: 260px;
    padding: .85rem .95rem;
    border-radius: 14px;
    border: 1px solid #d0d8e8;
    background: #fff;
    color: #101828;
    font: inherit;
    outline: none;
    transition: .2s ease;
}

.children-search input:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.children-count {
    display: inline-flex;
    align-items: center;
    gap: .5rem;
    padding: .75rem 1rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-weight: 950;
    white-space: nowrap;
}

.children-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 800;
}

.children-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.children-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.children-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.children-grid {
    display: grid;
    gap: .9rem;
}

.child-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    align-items: center;
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 20px;
    background: #f8fbff;
    transition: .2s ease;
}

.child-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.child-main {
    display: flex;
    align-items: center;
    gap: .9rem;
    min-width: 0;
}

.child-avatar {
    width: 56px;
    height: 56px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 50%;
    background: linear-gradient(135deg, #173f84, #1e4fa1);
    color: #f4c430;
    font-weight: 1000;
    font-size: 1.25rem;
}

.child-info {
    min-width: 0;
}

.child-info h3 {
    margin: 0 0 .25rem;
    color: #101828;
    font-size: 1.08rem;
    line-height: 1.2;
    overflow-wrap: anywhere;
}

.child-meta {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    margin-top: .45rem;
}

.child-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .32rem .62rem;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #edf2fb;
    color: #667085;
    font-size: .84rem;
    font-weight: 800;
}

.child-pill i {
    color: #1e4fa1;
}

.child-actions {
    display: flex;
    gap: .5rem;
    align-items: center;
    flex-wrap: wrap;
    justify-content: flex-end;
}

.child-remove-form {
    margin: 0;
}

.child-danger-btn {
    border-color: #ffd0d0 !important;
    color: #b42318 !important;
    background: #fff !important;
}

.child-danger-btn:hover {
    background: #fff0f0 !important;
}

.children-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.children-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

@media (max-width: 900px) {
    .children-hero {
        grid-template-columns: 1fr;
    }

    .children-toolbar {
        grid-template-columns: 1fr;
    }

    .children-count {
        justify-content: center;
    }

    .child-card {
        grid-template-columns: 1fr;
    }

    .child-actions {
        justify-content: flex-start;
    }
}

@media (max-width: 640px) {
    .children-manage-page {
        padding: 1.5rem 0 2.5rem;
    }

    .children-hero,
    .children-panel,
    .children-toolbar {
        border-radius: 20px;
    }

    .children-hero,
    .children-panel,
    .children-toolbar {
        padding: 1.2rem;
    }

    .children-hero-actions .btn,
    .children-search .btn,
    .child-actions .btn,
    .child-remove-form,
    .child-remove-form button {
        width: 100%;
    }

    .children-search input {
        min-width: 100%;
    }

    .child-main {
        align-items: flex-start;
    }
}
</style>

<main class="children-manage-page">
    <div class="container">

        <section class="children-hero">
            <div>
                <div class="children-kicker">
                    <i class="fas fa-children"></i>
                    Vecāka bērnu saraksts
                </div>

                <h1>Bērnu pārvaldība</h1>

                <p>
                    Šeit vari apskatīt bērnu profilus, labot datus un noņemt bērna piesaisti no sava vecāka konta.
                </p>

                <div class="children-hero-actions">
                    <a class="btn btn-primary btn-sm" href="add.php">
                        <i class="fas fa-child-reaching"></i>
                        Pievienot bērnu
                    </a>

                    <a class="btn btn-outline btn-sm" href="../../dashboards/parent.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="children-hero-card">
                <strong><?= (int)$childrenCount; ?></strong>
                <span>
                    Atrasti bērni pēc pašreizējā meklēšanas filtra.
                </span>
            </aside>
        </section>

        <?php if ($error): ?>
            <div class="children-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="children-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <section class="children-toolbar">
            <form method="get" class="children-search">
                <input 
                    type="text" 
                    name="search" 
                    placeholder="Meklēt pēc vārda, uzvārda, lietotājvārda vai e-pasta"
                    value="<?= htmlspecialchars($search); ?>"
                >

                <button class="btn btn-primary btn-sm" type="submit">
                    <i class="fas fa-magnifying-glass"></i>
                    Meklēt
                </button>

                <?php if ($search !== ""): ?>
                    <a class="btn btn-outline btn-sm" href="manage.php">
                        Notīrīt
                    </a>
                <?php endif; ?>
            </form>

            <div class="children-count">
                <i class="fas fa-list-check"></i>
                Kopā: <?= (int)$childrenCount; ?>
            </div>
        </section>

        <section class="children-panel">
            <?php if (empty($children)): ?>
                <div class="children-empty">
                    <h3>Nav atrasts neviens bērns</h3>
                    <p>
                        Pievieno bērnu vai maini meklēšanas kritērijus.
                    </p>

                    <a class="btn btn-primary btn-sm" href="add.php">
                        <i class="fas fa-user-plus"></i>
                        Pievienot bērnu
                    </a>
                </div>
            <?php else: ?>
                <div class="children-grid">
                    <?php foreach ($children as $child): ?>
                        <?php
                            $childName = trim(($child["vards"] ?? "") . " " . ($child["uzvards"] ?? ""));
                            $childName = $childName !== "" ? $childName : ($child["lietotajvards"] ?? "Bērns");
                            $childInitial = mb_strtoupper(mb_substr($childName, 0, 1));
                            $registered = !empty($child["Reg_datums"])
                                ? date("d.m.Y H:i", strtotime($child["Reg_datums"]))
                                : "—";
                        ?>

                        <article class="child-card">
                            <div class="child-main">
                                <span class="child-avatar">
                                    <?= htmlspecialchars($childInitial); ?>
                                </span>

                                <div class="child-info">
                                    <h3><?= htmlspecialchars($childName); ?></h3>

                                    <div class="child-meta">
                                        <span class="child-pill">
                                            <i class="fas fa-user"></i>
                                            <?= htmlspecialchars($child["lietotajvards"] ?? "—"); ?>
                                        </span>

                                        <span class="child-pill">
                                            <i class="fas fa-envelope"></i>
                                            <?= htmlspecialchars($child["epasts"] ?? "—"); ?>
                                        </span>

                                        <span class="child-pill">
                                            <i class="fas fa-circle-check"></i>
                                            <?= htmlspecialchars($child["statuss"] ?? "—"); ?>
                                        </span>

                                        <span class="child-pill">
                                            <i class="fas fa-calendar-plus"></i>
                                            <?= htmlspecialchars($registered); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <div class="child-actions">
                                <a class="btn btn-outline btn-sm" href="view.php?id=<?= (int)$child["lietotajs_id"]; ?>">
                                    <i class="fas fa-eye"></i>
                                    Skatīt
                                </a>

                                <a class="btn btn-sm" href="edit.php?id=<?= (int)$child["lietotajs_id"]; ?>">
                                    <i class="fas fa-pen"></i>
                                    Rediģēt
                                </a>

                                <form
                                    class="child-remove-form"
                                    method="post"
                                    onsubmit="return confirm('Vai tiešām noņemt šo bērnu no saraksta?');"
                                >
                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION["csrf_token"]); ?>">
                                    <input type="hidden" name="child_id" value="<?= (int)$child["lietotajs_id"]; ?>">

                                    <button class="btn btn-outline btn-sm child-danger-btn" type="submit" name="remove_child">
                                        <i class="fas fa-link-slash"></i>
                                        Noņemt
                                    </button>
                                </form>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<?php require __DIR__ . "/../../includes/templates/footer.php"; ?>
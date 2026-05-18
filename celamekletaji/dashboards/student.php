<?php
session_start();

$lapa  = "Ceļameklētāja panelis";
$title = "Ceļameklētāja panelis - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

/* ===============================
   PIEKĻUVES PĀRBAUDE
================================ */
$allowedRoles = ["Skolēns", "Ceļameklētājs", "Bērns", "student", "child"];

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), $allowedRoles, true)
) {
    header("Location: ../auth/login.php");
    exit();
}

$studentId = (int) ($_SESSION["lietotajs_id"] ?? 0);
$student = null;
$news = [];
$error = null;

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function formatDateTimeLv(?string $date): string
{
    if (empty($date)) {
        return "—";
    }

    return date("d.m.Y H:i", strtotime($date));
}

function formatDateLv(?string $date): string
{
    if (empty($date)) {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

/* ===============================
   SKOLĒNA / CEĻAMEKLĒTĀJA DATI
================================ */
$sqlStudent = "
    SELECT 
        lietotajs_id,
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

if ($stmt = $savienojums->prepare($sqlStudent)) {
    $stmt->bind_param("i", $studentId);

    if ($stmt->execute()) {
        $result = $stmt->get_result();

        if ($result && $result->num_rows === 1) {
            $student = $result->fetch_assoc();
        } else {
            $error = "Lietotāja dati netika atrasti.";
        }
    } else {
        $error = "Neizdevās ielādēt lietotāja datus.";
    }

    $stmt->close();
} else {
    $error = "Neizdevās sagatavot lietotāja vaicājumu.";
}

/* ===============================
   JAUNUMI
================================ */
$sqlNews = "
    SELECT 
        id,
        title,
        description,
        category,
        publish_date
    FROM cm_news
    WHERE is_active = 1
    ORDER BY publish_date DESC
    LIMIT 3
";

if ($newsResult = $savienojums->query($sqlNews)) {
    while ($row = $newsResult->fetch_assoc()) {
        $news[] = $row;
    }
}

/* ===============================
   ATTĒLOŠANAS DATI
================================ */
$displayName = $student["vards"]
    ?? ($_SESSION["lietotajvards"] ?? "Ceļameklētāj");

$userRole = $_SESSION["loma"] ?? ($student["loma"] ?? "Ceļameklētājs");

require __DIR__ . "/../includes/templates/header-student.php";
?>

<main class="dashboard-main">
    <div class="container">

        <!-- GALVENE -->
        <div class="dashboard-header">
            <h2>Sveiki, <?php echo htmlspecialchars($displayName); ?>!</h2>
            <p class="lead">
                Šis ir tavs ceļameklētāja panelis — apskati nodarbības, pārvaldi savu profilu un seko jaunumiem.
            </p>
        </div>

        <!-- KONTA ĪSĀ INFO -->
        <div class="section-title-row" style="margin-bottom:1rem;">
            <div>
                <h3 class="small">Konts</h3>
                <p class="muted small">
                    Loma:
                    <strong><?php echo htmlspecialchars($userRole); ?></strong>
                </p>
            </div>

            <div style="display:flex; gap:.6rem; align-items:center;">
                <a class="btn btn-outline btn-sm" href="../profile.php">
                    Mans profils
                </a>

                <a class="btn btn-primary btn-sm" href="../lessons/index.php">
                    Nodarbības
                </a>
            </div>
        </div>

        <!-- KĻŪDAS PAZIŅOJUMS -->
        <?php if ($error): ?>
            <div class="dashboard-card">
                <p class="muted">
                    <?php echo htmlspecialchars($error); ?>
                </p>
            </div>
        <?php endif; ?>

        <div class="dashboard-content">

            <!-- PROFILS -->
            <div class="dashboard-card">
                <h3>Mans profils</h3>
                <p class="muted">Tava pamatinformācija un konta statuss.</p>
                <div class="divider"></div>

                <?php if ($student): ?>
                    <div class="cards">
                        <div class="card">
                            <div class="program-head">
                                <div style="display:flex; gap:.75rem; align-items:center;">

                                    <div class="program-logo">
                                        <img src="../assets/images/avatar-placeholder.png" alt="avatar">
                                    </div>

                                    <div>
                                        <h4 style="margin:0;">
                                            <?php
                                                echo htmlspecialchars(
                                                    trim(($student["vards"] ?? "") . " " . ($student["uzvards"] ?? ""))
                                                    ?: ($student["lietotajvards"] ?? "Ceļameklētājs")
                                                );
                                            ?>
                                        </h4>

                                        <p class="muted small" style="margin:.2rem 0 0 0;">
                                            Lietotājvārds:
                                            <?php echo htmlspecialchars($student["lietotajvards"] ?? "—"); ?>
                                        </p>

                                        <p class="muted small" style="margin:.2rem 0 0 0;">
                                            E-pasts:
                                            <?php echo htmlspecialchars($student["epasts"] ?? "—"); ?>
                                        </p>

                                        <p class="muted small" style="margin:.2rem 0 0 0;">
                                            Loma:
                                            <?php echo htmlspecialchars($student["loma"] ?? "—"); ?>
                                            &nbsp;•&nbsp;
                                            Statuss:
                                            <?php echo htmlspecialchars($student["statuss"] ?? "—"); ?>
                                        </p>

                                        <p class="muted small" style="margin:.2rem 0 0 0;">
                                            Reģistrēts:
                                            <?php echo htmlspecialchars(formatDateTimeLv($student["Reg_datums"] ?? null)); ?>
                                        </p>
                                    </div>
                                </div>

                                <div style="display:flex; gap:.5rem; align-items:center;">
                                    <a class="btn btn-sm" href="../profile.php">
                                        Rediģēt profilu
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <p class="muted">Lietotāja dati nav atrasti.</p>
                <?php endif; ?>
            </div>

            <!-- ĀTRĀ PIEKĻUVE -->
            <div class="dashboard-card">
                <h3>Ātrās darbības</h3>
                <p class="muted">Svarīgākās sadaļas ātrākai piekļuvei.</p>
                <div class="divider"></div>

                <div class="cards">

                    <div class="card">
                        <h4>Pieejamās nodarbības</h4>
                        <p class="muted">
                            Apskati visas pieejamās nodarbības un izvēlies sev interesējošās.
                        </p>

                        <a class="btn btn-primary btn-sm" href="../lessons/index.php">
                            Skatīt nodarbības
                        </a>
                    </div>

                    <div class="card">
                        <h4>Mani pieteikumi</h4>
                        <p class="muted">
                            Pārskati nodarbības vai pasākumus, kuriem jau esi pieteicies.
                        </p>

                        <a class="btn btn-outline btn-sm" href="../applications/index.php">
                            Skatīt pieteikumus
                        </a>
                    </div>

                    <div class="card">
                        <h4>Mans profils</h4>
                        <p class="muted">
                            Atjauno savu informāciju un pārvaldi konta datus.
                        </p>

                        <a class="btn btn-sm" href="../profile.php">
                            Atvērt profilu
                        </a>
                    </div>

                </div>
            </div>

            <!-- JAUNUMI -->
            <div class="dashboard-card">
                <h3>Jaunākie jaunumi</h3>
                <p class="muted">Aktuālā informācija no “Ceļa meklētāji”.</p>
                <div class="divider"></div>

                <?php if (empty($news)): ?>
                    <p class="muted">Pašlaik nav pieejamu jaunumu.</p>
                <?php else: ?>
                    <div class="cards">
                        <?php foreach ($news as $item): ?>
                            <div class="card">
                                <h4 style="margin-bottom:.35rem;">
                                    <?php echo htmlspecialchars($item["title"] ?? "Bez nosaukuma"); ?>
                                </h4>

                                <p class="muted small" style="margin-bottom:.5rem;">
                                    Kategorija:
                                    <?php echo htmlspecialchars($item["category"] ?? "—"); ?>
                                    &nbsp;•&nbsp;
                                    Datums:
                                    <?php echo htmlspecialchars(formatDateLv($item["publish_date"] ?? null)); ?>
                                </p>

                                <p class="muted">
                                    <?php
                                        $desc = trim($item["description"] ?? "");
                                        echo htmlspecialchars(mb_strimwidth($desc, 0, 160, "..."));
                                    ?>
                                </p>

                                <a class="link" href="../news/view.php?id=<?php echo (int) ($item["id"] ?? 0); ?>">
                                    Lasīt vairāk
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- STATUSA BLOKS -->
            <div class="dashboard-card">
                <h3>Mans statuss</h3>
                <p class="muted">Īss pārskats par tavu aktivitāti sistēmā.</p>
                <div class="divider"></div>

                <ul class="footer-list" style="margin-top:.5rem;">
                    <li>
                        <i class="badge badge-blue">Profils</i>
                        Konts aktīvs un pieejams lietošanai
                    </li>

                    <li>
                        <i class="badge badge-gold">Nodarbības</i>
                        Vari pieteikties pieejamajām nodarbībām
                    </li>

                    <li>
                        <i class="badge badge-blue">Jaunumi</i>
                        Redzami aktuālākie biedrības ieraksti
                    </li>
                </ul>
            </div>

        </div>
    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
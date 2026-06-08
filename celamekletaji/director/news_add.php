<?php
session_start();

$lapa  = "Pievienot jaunumu";
$title = "Pievienot jaunumu";

require_once __DIR__ . "/../includes/config/app.php";
require_once __DIR__ . "/../includes/config/database.php";

if (
    !isset($_SESSION["lietotajs_id"]) ||
    !in_array(($_SESSION["loma"] ?? ""), ["Direktors", "direktors"], true)
) {
    header("Location: " . BASE_URL . "auth/login.php");
    exit();
}

$directorId = (int)($_SESSION["lietotajs_id"] ?? 0);
$clubId     = $_SESSION["club_id"] ?? null;

$error = "";
$success = "";

$formData = [
    "title"        => "",
    "description"  => "",
    "category"     => "Jaunums",
    "publish_date" => date("Y-m-d"),
    "start_date"   => date("Y-m-d"),
    "end_date"     => date("Y-m-d", strtotime("+30 days")),
    "is_active"    => "1"
];

if (empty($clubId)) {
    $error = "Jūsu profilam pašlaik nav piesaistīts klubs. Lūdzu, sazinieties ar administratoru, lai varētu publicēt jaunumu.";
}

if ($_SERVER["REQUEST_METHOD"] === "POST" && empty($error)) {

    $formData["title"]        = trim($_POST["title"] ?? "");
    $formData["description"]  = trim($_POST["description"] ?? "");
    $formData["category"]     = trim($_POST["category"] ?? "Jaunums");
    $formData["publish_date"] = trim($_POST["publish_date"] ?? date("Y-m-d"));
    $formData["start_date"]   = trim($_POST["start_date"] ?? $formData["publish_date"]);
    $formData["end_date"]     = trim($_POST["end_date"] ?? date("Y-m-d", strtotime("+30 days")));
    $formData["is_active"]    = isset($_POST["is_active"]) ? "1" : "0";

    if ($formData["title"] === "") {
        $error = "Lūdzu ievadi jaunuma virsrakstu.";
    } elseif ($formData["description"] === "") {
        $error = "Lūdzu ievadi jaunuma saturu.";
    } elseif (
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $formData["publish_date"]) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $formData["start_date"]) ||
        !preg_match('/^\d{4}-\d{2}-\d{2}$/', $formData["end_date"])
    ) {
        $error = "Lūdzu pārbaudi datumu formātu.";
    } elseif (strtotime($formData["end_date"]) < strtotime($formData["start_date"])) {
        $error = "Beigu datums nevar būt pirms sākuma datuma.";
    }

    $imagePath = null;

    if (empty($error) && !empty($_FILES["image"]["name"])) {

        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png"  => "png",
            "image/webp" => "webp"
        ];

        $fileTmp  = $_FILES["image"]["tmp_name"] ?? "";
        $fileSize = (int)($_FILES["image"]["size"] ?? 0);
        $fileType = "";

        if ($fileTmp && is_uploaded_file($fileTmp)) {
            $fileType = mime_content_type($fileTmp);
        }

        if (!array_key_exists($fileType, $allowedTypes)) {
            $error = "Atļauti tikai JPG, PNG vai WEBP attēli.";
        } } elseif ($fileSize > 10 * 1024 * 1024) {
    $error = "Attēls ir pārāk liels. Maksimālais izmērs ir 10 MB.";
}
        } else {
            $extension = $allowedTypes[$fileType];
            $newName = "news_" . time() . "_" . bin2hex(random_bytes(4)) . "." . $extension;

            $uploadDir = __DIR__ . "/../assets/images/news/";

            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0775, true);
            }

            $targetPath = $uploadDir . $newName;

            if (move_uploaded_file($fileTmp, $targetPath)) {
                $imagePath = "assets/images/news/" . $newName;
            } else {
                $error = "Neizdevās augšupielādēt attēlu.";
            }
        }
        
    if (empty($error)) {
        $isActive = (int)$formData["is_active"];

        $sql = "
            INSERT INTO cm_news
            (
                title,
                description,
                image,
                author_id,
                club_id,
                category,
                publish_date,
                is_active,
                created_at,
                start_date,
                end_date
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)
        ";

        $stmt = $savienojums->prepare($sql);

        if (!$stmt) {
            $error = "Neizdevās sagatavot jaunuma saglabāšanu: " . $savienojums->error;
        } else {
            $stmt->bind_param(
                "sssiississ",
                $formData["title"],
                $formData["description"],
                $imagePath,
                $directorId,
                $clubId,
                $formData["category"],
                $formData["publish_date"],
                $isActive,
                $formData["start_date"],
                $formData["end_date"]
            );

            if ($stmt->execute()) {
                $success = "Jaunums veiksmīgi pievienots.";

                $formData = [
                    "title"        => "",
                    "description"  => "",
                    "category"     => "Jaunums",
                    "publish_date" => date("Y-m-d"),
                    "start_date"   => date("Y-m-d"),
                    "end_date"     => date("Y-m-d", strtotime("+30 days")),
                    "is_active"    => "1"
                ];
            } else {
                $error = "Neizdevās saglabāt jaunumu: " . $stmt->error;
            }

            $stmt->close();
        }
    }

require __DIR__ . "/../includes/templates/header-director.php";
?>

<main class="director-news-page">
    <div class="container">

        <section class="page-hero">
            <div>
                <h1>Pievienot jaunumu</h1>
                <p>Izveido jaunumu savam klubam. Autors un klubs tiks pievienoti automātiski.</p>
            </div>

            <div class="hero-actions">
                <a href="<?= BASE_URL ?>dashboards/director.php" class="btn btn-secondary">
                    ← Atpakaļ uz paneli
                </a>
            </div>
        </section>

        <?php if ($error): ?>
            <div class="alert error">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <section class="panel">
            <h2>Jaunuma dati</h2>

            <form method="POST" enctype="multipart/form-data" class="news-form">

                <div class="form-group">
                    <label for="title">Virsraksts *</label>
                    <input
                        type="text"
                        id="title"
                        name="title"
                        class="form-control"
                        required
                        value="<?= htmlspecialchars($formData["title"]) ?>"
                        placeholder="Piemēram: Kluba pārgājiens sestdien"
                    >
                </div>

                <div class="form-group">
                    <label for="description">Saturs *</label>
                    <textarea
                        id="description"
                        name="description"
                        class="form-control"
                        rows="8"
                        required
                        placeholder="Ievadi jaunuma aprakstu..."
                    ><?= htmlspecialchars($formData["description"]) ?></textarea>
                </div>

                <div class="form-grid">
                    <div class="form-group">
                        <label for="category">Kategorija</label>
                        <input
                            type="text"
                            id="category"
                            name="category"
                            class="form-control"
                            value="<?= htmlspecialchars($formData["category"]) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="publish_date">Publicēšanas datums *</label>
                        <input
                            type="date"
                            id="publish_date"
                            name="publish_date"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($formData["publish_date"]) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="start_date">Rādīt no *</label>
                        <input
                            type="date"
                            id="start_date"
                            name="start_date"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($formData["start_date"]) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="end_date">Rādīt līdz *</label>
                        <input
                            type="date"
                            id="end_date"
                            name="end_date"
                            class="form-control"
                            required
                            value="<?= htmlspecialchars($formData["end_date"]) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="image">Attēls</label>
                        <input
                            type="file"
                            id="image"
                            name="image"
                            class="form-control"
                            accept="image/jpeg,image/png,image/webp"
                        >
                        <small>Atļauts: JPG, PNG, WEBP. Maksimums 5 MB.</small>
                    </div>
                </div>

                <label class="checkbox-row">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        <?= $formData["is_active"] === "1" ? "checked" : "" ?>
                    >
                    Publicēt jaunumu uzreiz
                </label>

                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        Saglabāt jaunumu
                    </button>

                    <a href="<?= BASE_URL ?>dashboards/director.php" class="btn btn-secondary">
                        Atcelt
                    </a>
                </div>
            </form>
        </section>

    </div>
</main>

<style>
.director-news-page {
    min-height: calc(100vh - 150px);
    padding: 2rem 0 3rem;
    background:
        radial-gradient(circle at top right, rgba(30,79,161,0.09), transparent 32%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.page-hero {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1.4rem;
    padding: 1.6rem;
    border-radius: 24px;
    background: linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
    box-shadow: 0 18px 45px rgba(23,63,132,.18);
}

.page-hero h1 {
    margin: 0 0 .35rem;
    color: #fff;
}

.page-hero p {
    margin: 0;
    color: rgba(255,255,255,.86);
}

.panel {
    background: #fff;
    border: 1px solid #e8eef8;
    border-radius: 22px;
    padding: 1.4rem;
    box-shadow: 0 12px 28px rgba(16,24,40,.06);
}

.panel h2 {
    margin-top: 0;
    color: #173f84;
}

.alert {
    padding: 1rem 1.1rem;
    border-radius: 14px;
    margin-bottom: 1rem;
    font-weight: 700;
}

.alert.success {
    background: #ecfdf3;
    color: #027a48;
    border: 1px solid #abefc6;
}

.alert.error {
    background: #fef3f2;
    color: #b42318;
    border: 1px solid #fecdca;
}

.news-form {
    display: grid;
    gap: 1rem;
}

.form-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 1rem;
}

.form-group label {
    display: block;
    margin-bottom: .4rem;
    font-weight: 800;
    color: #344054;
}

.form-group small {
    display: block;
    margin-top: .35rem;
    color: #667085;
}

.form-control {
    width: 100%;
    padding: .9rem 1rem;
    border: 1px solid #d0d5dd;
    border-radius: 12px;
    font-size: .95rem;
    box-sizing: border-box;
    font-family: inherit;
}

textarea.form-control {
    resize: vertical;
}

.checkbox-row {
    display: flex;
    align-items: center;
    gap: .55rem;
    font-weight: 800;
    color: #344054;
}

.form-actions {
    margin-top: .5rem;
    display: flex;
    gap: .8rem;
    flex-wrap: wrap;
}

.btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .5rem;
    padding: .9rem 1.1rem;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    text-decoration: none;
    font-weight: 800;
}

.btn-primary {
    background: #173f84;
    color: #fff;
}

.btn-secondary {
    background: #eef3ff;
    color: #173f84;
}

@media (max-width: 768px) {
    .page-hero {
        flex-direction: column;
        align-items: flex-start;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
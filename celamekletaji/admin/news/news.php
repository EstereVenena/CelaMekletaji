<?php
$lapa  = "Aktualitātes";
$title = "Aktualitātes";

require __DIR__ . "/../../includes/templates/header-admin.php";
require_once __DIR__ . "/../../includes/config/database.php";

$news = [];

$sql = "SELECT * FROM cm_news ORDER BY publish_date DESC";
$result = $savienojums->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $news[] = $row;
    }
}
?>

<section class="section">
    <div class="container">

        <button class="btn btn-primary" onclick="openAddModal()">
            <i class="fa-solid fa-plus"></i>
            Pievienot ziņu
        </button>

        <div class="table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Nosaukums</th>
                        <th>Apraksts</th>
                        <th>Kategorija</th>
                        <th>Datums</th>
                        <th>Aktīvs</th>
                        <th>Darbības</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($news as $item): ?>
                        <tr>
                            <td><?= htmlspecialchars($item['title']) ?></td>
                            <td><?= htmlspecialchars($item['description']) ?></td>
                            <td><?= htmlspecialchars($item['category']) ?></td>
                            <td><?= htmlspecialchars($item['publish_date']) ?></td>
                            <td>
                                <span class="<?= $item['is_active'] ? 'status-active' : 'status-inactive' ?>">
                                    <?= $item['is_active'] ? "Jā" : "Nē" ?>
                                </span>
                            </td>

                            <td class="actions">
                                <button 
                                    class="icon-btn edit"
                                    type="button"
                                    title="Rediģēt"
                                    onclick="openEditModal(
                                        '<?= $item['id'] ?>',
                                        '<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($item['description'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($item['category'], ENT_QUOTES) ?>',
                                        '<?= $item['publish_date'] ?>'
                                    )">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </button>

                                <a 
                                    href="delete_news.php?id=<?= $item['id'] ?>"
                                    class="icon-btn delete"
                                    title="Dzēst"
                                    onclick="return confirm('Dzēst ziņu?')">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (empty($news)): ?>
                        <tr>
                            <td colspan="6" class="empty-row">Nav pievienota neviena aktualitāte.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</section>

<div id="newsModal" class="news-modal">
    <div class="news-modal-overlay" onclick="closeModal()"></div>

    <div class="news-modal-panel">
        <button class="news-modal-close" type="button" onclick="closeModal()">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <h3 id="modalTitle">Pievienot ziņu</h3>

        <form method="POST" action="save_news.php" class="news-form">
            <input type="hidden" name="id" id="news_id">

            <label>Nosaukums</label>
            <input type="text" name="title" id="title" required>

            <label>Apraksts</label>
            <textarea name="description" id="description"></textarea>

            <label>Kategorija</label>
            <input type="text" name="category" id="category">

            <label>Datums</label>
            <input type="date" name="publish_date" id="publish_date">

            <button type="submit" class="btn btn-primary">
                <i class="fa-solid fa-floppy-disk"></i>
                Saglabāt
            </button>
        </form>
    </div>
</div>

<style>
.table-wrap {
    width: 100%;
    overflow-x: auto;
    margin-top: 25px;
}

.admin-table {
    width: 100%;
    border-collapse: collapse;
    background: #fff;
    border-radius: 14px;
    overflow: hidden;
    box-shadow: 0 4px 15px rgba(0,0,0,0.08);
}

.admin-table th,
.admin-table td {
    padding: 14px 16px;
    text-align: left;
    border-bottom: 1px solid #eee;
    vertical-align: top;
}

.admin-table th {
    background: #1e4fa1;
    color: white;
    font-weight: 800;
}

.admin-table tr:hover {
    background: #f7f9fc;
}

.actions {
    display: flex;
    gap: 10px;
}

.icon-btn {
    border: none;
    background: none;
    cursor: pointer;
    font-size: 18px;
    text-decoration: none;
    padding: 7px;
    border-radius: 8px;
}

.icon-btn.edit {
    color: #1e4fa1;
}

.icon-btn.delete {
    color: #c0392b;
}

.icon-btn:hover {
    background: #eee;
    transform: scale(1.1);
}

.status-active {
    color: #1b7f3a;
    font-weight: 900;
}

.status-inactive {
    color: #c62828;
    font-weight: 900;
}

.empty-row {
    text-align: center;
    padding: 25px;
    font-weight: 800;
    color: #666;
}

.news-modal {
    display: none;
    position: fixed;
    inset: 0;
    z-index: 3000;
}

.news-modal.open {
    display: block;
}

.news-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(0,0,0,.72);
}

.news-modal-panel {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);

    max-width: 820px;
    width: calc(100% - 2rem);

    background: #fff;
    border-radius: 18px;
    padding: 2rem;
    box-shadow: 0 30px 90px rgba(0,0,0,.35);
}

.news-modal-panel h3 {
    font-size: 1.8rem;
    font-weight: 1000;
    color: #173f84;
    margin-bottom: 1.25rem;
}

.news-modal-close {
    position: absolute;
    top: 14px;
    right: 14px;
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 12px;
    background: #eef3ff;
    color: #173f84;
    cursor: pointer;
    font-size: 1.2rem;
}

.news-modal-close:hover {
    background: #ffe9e9;
    color: #c62828;
}

.news-form {
    display: grid;
    gap: .75rem;
}

.news-form label {
    font-weight: 900;
    color: #333;
    margin-top: .35rem;
}

.news-form input,
.news-form textarea {
    width: 100%;
    padding: .9rem 1rem;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,.16);
    font-size: 1rem;
    background: #f9fbff;
}

.news-form textarea {
    min-height: 190px;
    resize: vertical;
}

.news-form input:focus,
.news-form textarea:focus {
    outline: none;
    border-color: #1e4fa1;
    box-shadow: 0 0 0 3px rgba(30,79,161,.14);
    background: #fff;
}

.news-form button {
    margin-top: .8rem;
    width: fit-content;
    gap: .45rem;
}

@media (max-width: 600px) {
    .news-modal-panel {
        margin: 2rem auto;
        padding: 1.3rem;
    }

    .news-modal-panel h3 {
        font-size: 1.45rem;
    }
}
</style>

<script>
function openAddModal() {
    document.getElementById("modalTitle").innerText = "Pievienot ziņu";

    document.getElementById("news_id").value = "";
    document.getElementById("title").value = "";
    document.getElementById("description").value = "";
    document.getElementById("category").value = "";
    document.getElementById("publish_date").value = "";

    document.getElementById("newsModal").classList.add("open");
}

function openEditModal(id, title, description, category, date) {
    document.getElementById("modalTitle").innerText = "Rediģēt ziņu";

    document.getElementById("news_id").value = id;
    document.getElementById("title").value = title;
    document.getElementById("description").value = description;
    document.getElementById("category").value = category;
    document.getElementById("publish_date").value = date;

    document.getElementById("newsModal").classList.add("open");
}

function closeModal() {
    document.getElementById("newsModal").classList.remove("open");
}

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        closeModal();
    }
});
</script>

<?php require __DIR__ . "/../../includes/templates/footer.php"; ?>
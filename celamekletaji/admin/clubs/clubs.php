<?php
$lapa  = "Klubi";
$title = "Klubi";

require_once __DIR__ . "/../../includes/config/database.php";
require_once __DIR__ . "/../../includes/templates/header-admin.php";

$clubs = [];

$sql = "
    SELECT 
        c.id,
        c.name,
        c.address,
        GROUP_CONCAT(p.label SEPARATOR ', ') AS programs
    FROM cm_clubs c
    LEFT JOIN cm_club_programs cp ON c.id = cp.club_id
    LEFT JOIN cm_programs p ON cp.program_id = p.id
    GROUP BY c.id, c.name, c.address
    ORDER BY c.address
";

$result = $savienojums->query($sql);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $clubs[] = $row;
    }
}
?>

<style>
.admin-clubs-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.admin-clubs-hero {
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

.admin-clubs-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.admin-clubs-hero > * {
    position: relative;
    z-index: 1;
}

.admin-clubs-kicker {
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

.admin-clubs-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.admin-clubs-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.admin-clubs-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.admin-clubs-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.admin-clubs-hero-card strong {
    display: block;
    font-size: 2.2rem;
    line-height: 1;
    color: #f4c430;
}

.admin-clubs-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.admin-clubs-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.admin-clubs-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.1rem;
}

.admin-clubs-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.admin-clubs-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
    line-height: 1.6;
}

.admin-clubs-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
    gap: 1rem;
}

.admin-club-card {
    position: relative;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    min-height: 250px;
    padding: 1.15rem;
    border-radius: 22px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.admin-club-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 18px 36px rgba(23, 63, 132, 0.10);
    border-color: #d7e5ff;
}

.admin-club-card::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(90deg, #173f84, #f4c430);
}

.admin-club-top {
    display: flex;
    align-items: flex-start;
    gap: .85rem;
    margin-bottom: .9rem;
}

.admin-club-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 16px;
    background: linear-gradient(135deg, #173f84, #1e4fa1);
    color: #f4c430;
    font-size: 1.25rem;
    box-shadow: 0 12px 24px rgba(23, 63, 132, 0.16);
}

.admin-club-title {
    margin: 0;
    color: #101828;
    font-size: 1.12rem;
    line-height: 1.25;
}

.admin-club-meta {
    display: grid;
    gap: .65rem;
    margin-top: .75rem;
}

.admin-club-meta-item {
    display: flex;
    gap: .55rem;
    color: #667085;
    line-height: 1.5;
}

.admin-club-meta-item i {
    width: 18px;
    margin-top: .2rem;
    color: #1e4fa1;
    text-align: center;
}

.admin-club-programs {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    width: fit-content;
    max-width: 100%;
    margin-top: .35rem;
    padding: .42rem .7rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-size: .86rem;
    font-weight: 900;
    line-height: 1.35;
}

.admin-club-actions {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    margin-top: auto;
    padding-top: 1rem;
}

.btn-red {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    padding: .7rem .95rem;
    border-radius: 999px;
    background: #fff0f0;
    color: #b42318;
    border: 1px solid #ffd0d0;
    text-decoration: none;
    font-weight: 900;
    transition: .2s ease;
}

.btn-red:hover {
    background: #ffe3e3;
    transform: translateY(-1px);
}

.admin-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.admin-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

/* MODAL */
.club-modal {
    position: fixed;
    inset: 0;
    z-index: 3000;
    display: none;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    background: rgba(15, 23, 42, 0.58);
    backdrop-filter: blur(6px);
}

.club-modal.show {
    display: flex;
}

.club-modal-content {
    position: relative;
    width: min(520px, 100%);
    overflow: hidden;
    border-radius: 26px;
    background: #fff;
    box-shadow: 0 30px 90px rgba(0,0,0,.28);
    animation: modalIn .18s ease;
}

@keyframes modalIn {
    from {
        opacity: 0;
        transform: translateY(10px) scale(.98);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

.club-modal-head {
    position: relative;
    padding: 1.5rem;
    background:
        radial-gradient(circle at top right, rgba(244,196,48,.25), transparent 38%),
        linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
}

.club-modal-head h3 {
    margin: 0;
    color: #fff;
    font-size: 1.45rem;
}

.club-modal-head p {
    margin: .35rem 0 0;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.club-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 40px;
    height: 40px;
    display: grid;
    place-items: center;
    border: none;
    border-radius: 50%;
    background: rgba(255,255,255,.14);
    color: #fff;
    font-size: 1.35rem;
    cursor: pointer;
    transition: .2s ease;
}

.club-modal-close:hover {
    background: rgba(255,255,255,.24);
    transform: rotate(90deg);
}

.club-modal-body {
    padding: 1.4rem;
}

.club-form {
    display: grid;
    gap: 1rem;
}

.club-form-group {
    display: grid;
    gap: .42rem;
}

.club-form-group label {
    color: #344054;
    font-weight: 900;
}

.club-input {
    width: 100%;
    padding: .9rem 1rem;
    border-radius: 15px;
    border: 1px solid #d0d8e8;
    background: #fff;
    color: #101828;
    font: inherit;
    outline: none;
    transition: .2s ease;
}

.club-input:focus {
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.12);
}

.club-modal-note {
    display: flex;
    gap: .65rem;
    padding: .9rem 1rem;
    border-radius: 18px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    color: #667085;
    line-height: 1.55;
}

.club-modal-note i {
    color: #1e4fa1;
    margin-top: .2rem;
}

.club-modal-actions {
    display: flex;
    justify-content: flex-end;
    gap: .65rem;
    flex-wrap: wrap;
    margin-top: .4rem;
}

@media (max-width: 900px) {
    .admin-clubs-hero {
        grid-template-columns: 1fr;
    }

    .admin-clubs-panel-head {
        flex-direction: column;
    }
}

@media (max-width: 640px) {
    .admin-clubs-page {
        padding: 1.5rem 0 2.5rem;
    }

    .admin-clubs-hero,
    .admin-clubs-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .admin-clubs-actions .btn,
    .admin-club-actions .btn,
    .admin-club-actions .btn-red,
    .club-modal-actions .btn {
        width: 100%;
    }
}
</style>

<main class="admin-clubs-page">
    <div class="container">

        <section class="admin-clubs-hero">
            <div>
                <div class="admin-clubs-kicker">
                    <i class="fas fa-people-roof"></i>
                    Klubu pārvaldība
                </div>

                <h1>Klubi</h1>

                <p>
                    Pārvaldi Ceļa meklētāju klubus, to adreses un piesaistītās programmas.
                </p>

                <div class="admin-clubs-actions">
                    <button class="btn btn-primary" type="button" onclick="openAddModal()">
                        <i class="fas fa-plus"></i>
                        Pievienot klubu
                    </button>
                </div>
            </div>

            <aside class="admin-clubs-hero-card">
                <strong><?= count($clubs); ?></strong>
                <span>Klubi sistēmā</span>
            </aside>
        </section>

        <section class="admin-clubs-panel">
            <div class="admin-clubs-panel-head">
                <div>
                    <h2>Klubu saraksts</h2>
                    <p>Rediģē esošos klubus vai pievieno jaunu klubu.</p>
                </div>

                <button class="btn btn-primary btn-sm" type="button" onclick="openAddModal()">
                    <i class="fas fa-plus"></i>
                    Jauns klubs
                </button>
            </div>

            <?php if (!empty($clubs)): ?>
                <div class="admin-clubs-grid">

                    <?php foreach ($clubs as $club): ?>
                        <article class="admin-club-card">
                            <div class="admin-club-top">
                                <div class="admin-club-icon">
                                    <i class="fas fa-compass"></i>
                                </div>

                                <div>
                                    <h3 class="admin-club-title">
                                        <?= htmlspecialchars($club['name']) ?>
                                    </h3>

                                    <div class="admin-club-programs">
                                        <i class="fas fa-layer-group"></i>
                                        <?= htmlspecialchars($club['programs'] ?: 'Nav programmas') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="admin-club-meta">
                                <div class="admin-club-meta-item">
                                    <i class="fas fa-location-dot"></i>
                                    <span><?= htmlspecialchars($club['address'] ?: 'Adrese nav norādīta') ?></span>
                                </div>
                            </div>

                            <div class="admin-club-actions">
                                <button
                                    type="button"
                                    class="btn btn-outline btn-sm"
                                    onclick="openEditModal(
                                        '<?= (int)$club['id'] ?>',
                                        '<?= htmlspecialchars($club['name'], ENT_QUOTES) ?>',
                                        '<?= htmlspecialchars($club['address'], ENT_QUOTES) ?>'
                                    )"
                                >
                                    <i class="fas fa-pen"></i>
                                    Rediģēt
                                </button>

                                <a
                                    href="delete_club.php?id=<?= (int)$club['id'] ?>"
                                    class="btn-red"
                                    onclick="return confirm('Tiešām dzēst šo klubu?')"
                                >
                                    <i class="fas fa-trash"></i>
                                    Dzēst
                                </a>
                            </div>
                        </article>
                    <?php endforeach; ?>

                </div>
            <?php else: ?>
                <div class="admin-empty">
                    <h3>Nav pievienotu klubu</h3>
                    <p>Pievieno pirmo klubu, izmantojot pogu “Pievienot klubu”.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<!-- MODAL -->
<div id="clubModal" class="club-modal" aria-hidden="true">
    <div class="club-modal-content" role="dialog" aria-modal="true" aria-labelledby="modalTitle">

        <div class="club-modal-head">
            <button class="club-modal-close" type="button" onclick="closeModal()" aria-label="Aizvērt">
                &times;
            </button>

            <h3 id="modalTitle">Pievienot klubu</h3>
            <p id="modalDescription">
                Aizpildi kluba nosaukumu un adresi.
            </p>
        </div>

        <div class="club-modal-body">
            <form method="POST" action="save_club.php" class="club-form">

                <input type="hidden" name="id" id="club_id">

                <div class="club-form-group">
                    <label for="name">Kluba nosaukums</label>
                    <input
                        class="club-input"
                        type="text"
                        name="name"
                        id="name"
                        placeholder="Piemēram: Grobiņas Ceļa meklētāji"
                        required
                    >
                </div>

                <div class="club-form-group">
                    <label for="address">Adrese</label>
                    <input
                        class="club-input"
                        type="text"
                        name="address"
                        id="address"
                        placeholder="Piemēram: Lielā iela 12, Grobiņa"
                        required
                    >
                </div>

                <div class="club-modal-note">
                    <i class="fas fa-circle-info"></i>
                    <span>
                        Programmas šajā skatā tikai tiek parādītas. To piesaisti var pievienot vēlāk atsevišķā sadaļā.
                    </span>
                </div>

                <div class="club-modal-actions">
                    <button type="button" class="btn btn-outline" onclick="closeModal()">
                        Atcelt
                    </button>

                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-floppy-disk"></i>
                        Saglabāt
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>

<script>
function openAddModal() {
    document.getElementById("modalTitle").innerText = "Pievienot klubu";
    document.getElementById("modalDescription").innerText = "Aizpildi jaunā kluba nosaukumu un adresi.";

    document.getElementById("club_id").value = "";
    document.getElementById("name").value = "";
    document.getElementById("address").value = "";

    openModal();
}

function openEditModal(id, name, address) {
    document.getElementById("modalTitle").innerText = "Rediģēt klubu";
    document.getElementById("modalDescription").innerText = "Atjauno kluba nosaukumu vai adresi.";

    document.getElementById("club_id").value = id;
    document.getElementById("name").value = name;
    document.getElementById("address").value = address;

    openModal();
}

function openModal() {
    const modal = document.getElementById("clubModal");
    modal.classList.add("show");
    modal.setAttribute("aria-hidden", "false");

    setTimeout(function () {
        document.getElementById("name").focus();
    }, 100);
}

function closeModal() {
    const modal = document.getElementById("clubModal");
    modal.classList.remove("show");
    modal.setAttribute("aria-hidden", "true");
}

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeModal();
    }
});

document.getElementById("clubModal").addEventListener("click", function (event) {
    if (event.target === this) {
        closeModal();
    }
});
</script>

<?php require __DIR__ . "/../../includes/templates/footer.php"; ?>
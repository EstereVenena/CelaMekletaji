<?php
session_start();

$lapa  = "Paziņojumi";
$title = "Mani paziņojumi - Ceļa meklētāji";

require_once __DIR__ . "/../includes/config/database.php";

if (!isset($_SESSION['lietotajs_id'])) {
    header("Location: ../auth/login.php");
    exit;
}

$userId = (int)$_SESSION['lietotajs_id'];
$userRole = $_SESSION["loma"] ?? "";

$sql = "
    SELECT *
    FROM cm_notifications
    WHERE user_id = ?
    ORDER BY created_at DESC
";

$stmt = $savienojums->prepare($sql);
$stmt->bind_param("i", $userId);
$stmt->execute();

$result = $stmt->get_result();

$notifications = [];

while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

$stmt->close();

$unreadCount = 0;

foreach ($notifications as $notification) {
    if (empty($notification['is_read'])) {
        $unreadCount++;
    }
}

function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00 00:00:00") {
        return "—";
    }

    return date("d.m.Y H:i", strtotime($date));
}

/* ===============================
   HEADER PĒC LOMAS
================================ */
if (in_array($userRole, ["Vecāks", "parent"], true)) {
    require __DIR__ . "/../includes/templates/header-parent.php";

} elseif (in_array($userRole, ["Direktors", "direktors", "director"], true)) {
    require __DIR__ . "/../includes/templates/header-director.php";

} elseif (in_array($userRole, ["Skolotājs", "skolotājs", "teacher"], true)) {
    require __DIR__ . "/../includes/templates/header-teacher.php";

} elseif (in_array($userRole, ["Skolēns", "Ceļameklētājs", "Bērns", "student", "child"], true)) {
    require __DIR__ . "/../includes/templates/header-student.php";

} elseif (in_array($userRole, ["admin", "Admin"], true)) {
    require __DIR__ . "/../includes/templates/header-admin.php";

} else {
    require __DIR__ . "/../includes/templates/header.php";
}
?>

<style>
.notifications-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.notifications-hero {
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

.notifications-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.notifications-hero > * {
    position: relative;
    z-index: 1;
}

.notifications-kicker {
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

.notifications-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.notifications-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.notifications-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.notifications-hero-card strong {
    display: block;
    font-size: 2.2rem;
    line-height: 1;
    color: #f4c430;
}

.notifications-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.notifications-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.notifications-panel-head {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 1rem;
    margin-bottom: 1.1rem;
}

.notifications-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.notifications-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
    line-height: 1.6;
}

.notifications-badge {
    display: inline-flex;
    align-items: center;
    gap: .45rem;
    padding: .65rem .95rem;
    border-radius: 999px;
    background: #fff8e6;
    color: #8a650b;
    border: 1px solid #f5df9f;
    font-weight: 900;
    white-space: nowrap;
}

.notifications-list {
    display: grid;
    gap: .85rem;
}

.notification-card {
    position: relative;
    overflow: hidden;
    display: grid;
    grid-template-columns: auto 1fr auto;
    gap: 1rem;
    align-items: flex-start;
    padding: 1rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px solid #edf2fb;
    transition: .2s ease;
}

.notification-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.notification-card.unread {
    background:
        radial-gradient(circle at top right, rgba(244,196,48,.16), transparent 36%),
        #ffffff;
    border-color: #f5df9f;
}

.notification-card.unread::before {
    content: "";
    position: absolute;
    inset: 0 0 auto 0;
    height: 5px;
    background: linear-gradient(90deg, #173f84, #f4c430);
}

.notification-icon {
    width: 48px;
    height: 48px;
    display: grid;
    place-items: center;
    flex-shrink: 0;
    border-radius: 16px;
    background: #eef3ff;
    color: #173f84;
    font-size: 1.15rem;
}

.notification-card.unread .notification-icon {
    background: linear-gradient(135deg, #173f84, #1e4fa1);
    color: #f4c430;
    box-shadow: 0 12px 24px rgba(23, 63, 132, 0.16);
}

.notification-content h3 {
    margin: 0 0 .35rem;
    color: #101828;
    font-size: 1.08rem;
    line-height: 1.3;
}

.notification-content p {
    margin: 0;
    color: #667085;
    line-height: 1.6;
}

.notification-meta {
    display: flex;
    flex-wrap: wrap;
    gap: .5rem;
    margin-top: .75rem;
}

.notification-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .34rem .65rem;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #edf2fb;
    color: #667085;
    font-size: .84rem;
    font-weight: 850;
}

.notification-pill.unread {
    background: #fff8e6;
    color: #8a650b;
    border-color: #f5df9f;
}

.notification-actions {
    display: flex;
    align-items: flex-start;
    justify-content: flex-end;
}

.notification-actions button {
    border: none;
    cursor: pointer;
}

.notification-read-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .45rem;
    padding: .7rem .95rem;
    border-radius: 999px;
    background: linear-gradient(135deg, #173f84, #1e4fa1);
    color: #fff;
    font-weight: 900;
    transition: .2s ease;
    white-space: nowrap;
}

.notification-read-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 12px 26px rgba(23, 63, 132, 0.18);
}

.notifications-empty {
    padding: 2rem;
    border-radius: 22px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    text-align: center;
}

.notifications-empty-icon {
    width: 66px;
    height: 66px;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    border-radius: 20px;
    background: #eef3ff;
    color: #173f84;
    font-size: 1.7rem;
}

.notifications-empty h3 {
    margin: 0 0 .45rem;
    color: #173f84;
}

.notifications-empty p {
    margin: 0;
    color: #667085;
    line-height: 1.6;
}

@media (max-width: 900px) {
    .notifications-hero {
        grid-template-columns: 1fr;
    }

    .notifications-panel-head {
        flex-direction: column;
    }

    .notification-card {
        grid-template-columns: auto 1fr;
    }

    .notification-actions {
        grid-column: 1 / -1;
        justify-content: flex-start;
    }
}

@media (max-width: 560px) {
    .notifications-page {
        padding: 1.5rem 0 2.5rem;
    }

    .notifications-hero,
    .notifications-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .notification-card {
        grid-template-columns: 1fr;
    }

    .notification-icon {
        width: 44px;
        height: 44px;
    }

    .notification-read-btn {
        width: 100%;
    }

    .notifications-badge {
        width: 100%;
        justify-content: center;
    }
}
</style>

<main class="notifications-page">
    <div class="container">

        <section class="notifications-hero">
            <div>
                <div class="notifications-kicker">
                    <i class="fas fa-bell"></i>
                    Paziņojumu centrs
                </div>

                <h1>Mani paziņojumi</h1>

                <p>
                    Šeit redzami sistēmas paziņojumi par svarīgām izmaiņām, jaunumiem,
                    aktivitātēm un citām lietām, kuras nevajadzētu palaist garām.
                </p>
            </div>

            <aside class="notifications-hero-card">
                <strong><?= (int)$unreadCount ?></strong>
                <span>Nelasīti paziņojumi</span>
            </aside>
        </section>

        <section class="notifications-panel">
            <div class="notifications-panel-head">
                <div>
                    <h2>Paziņojumu saraksts</h2>
                    <p>Jaunākie paziņojumi ir augšpusē.</p>
                </div>

                <div class="notifications-badge">
                    <i class="fas fa-inbox"></i>
                    <?= count($notifications) ?> kopā
                </div>
            </div>

            <?php if (empty($notifications)): ?>

                <div class="notifications-empty">
                    <div class="notifications-empty-icon">
                        <i class="fas fa-bell-slash"></i>
                    </div>

                    <h3>Tev pašlaik nav paziņojumu</h3>
                    <p>Kad būs kāda svarīga ziņa, tā parādīsies šeit.</p>
                </div>

            <?php else: ?>

                <div class="notifications-list">

                    <?php foreach ($notifications as $notification): ?>
                        <?php
                            $isRead = !empty($notification['is_read']);
                            $cardClass = $isRead ? 'read' : 'unread';
                        ?>

                        <article class="notification-card <?= $cardClass ?>">

                            <div class="notification-icon">
                                <i class="fas <?= $isRead ? 'fa-envelope-open' : 'fa-envelope' ?>"></i>
                            </div>

                            <div class="notification-content">
                                <h3><?= htmlspecialchars($notification['title'] ?? 'Paziņojums') ?></h3>

                                <p><?= nl2br(htmlspecialchars($notification['message'] ?? '')) ?></p>

                                <div class="notification-meta">
                                    <span class="notification-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatDateLv($notification['created_at'] ?? null)) ?>
                                    </span>

                                    <span class="notification-pill <?= !$isRead ? 'unread' : '' ?>">
                                        <i class="fas <?= $isRead ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
                                        <?= $isRead ? 'Izlasīts' : 'Nelasīts' ?>
                                    </span>
                                </div>
                            </div>

                            <div class="notification-actions">
                                <?php if (!$isRead): ?>
                                    <form method="post" action="mark_notification_read.php">
                                        <input type="hidden" name="id" value="<?= (int)$notification['id'] ?>">

                                        <button type="submit" class="notification-read-btn">
                                            <i class="fas fa-check"></i>
                                            Atzīmēt kā lasītu
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>

                        </article>

                    <?php endforeach; ?>

                </div>

            <?php endif; ?>

        </section>

    </div>
</main>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
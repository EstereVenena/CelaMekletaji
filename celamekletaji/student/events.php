<?php
session_start();

$lapa  = "Pasākumi";
$title = "Pasākumi - Ceļa meklētāji";

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

$userId = (int) $_SESSION["lietotajs_id"];

$events = [];
$success = trim($_GET["success"] ?? "");
$error   = trim($_GET["error"] ?? "");

/* ===============================
   PALĪGFUNKCIJAS
================================ */
function formatDateLv(?string $date): string
{
    if (empty($date) || $date === "0000-00-00") {
        return "—";
    }

    return date("d.m.Y", strtotime($date));
}

function formatDateRangeLv(?string $startDate, ?string $endDate): string
{
    if (empty($startDate) || $startDate === "0000-00-00") {
        return "—";
    }

    $start = date("d.m.Y", strtotime($startDate));

    if (!empty($endDate) && $endDate !== "0000-00-00" && $endDate !== $startDate) {
        $end = date("d.m.Y", strtotime($endDate));
        return $start . " - " . $end;
    }

    return $start;
}

function formatTimeRangeLv(?string $startTime, ?string $endTime): string
{
    if (empty($startTime)) {
        return "—";
    }

    $start = substr($startTime, 0, 5);

    if (!empty($endTime) && $endTime !== $startTime) {
        $end = substr($endTime, 0, 5);
        return $start . " - " . $end;
    }

    return $start;
}

function tableExists(mysqli $db, string $table): bool
{
    $stmt = $db->prepare("
        SELECT COUNT(*) AS total
        FROM information_schema.TABLES
        WHERE TABLE_SCHEMA = DATABASE()
          AND TABLE_NAME = ?
    ");

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param("s", $table);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $stmt->close();

    return (int) ($row["total"] ?? 0) > 0;
}

function redirectWithMessage(string $type, string $message): void
{
    $param = $type === "success" ? "success" : "error";
    header("Location: events.php?" . $param . "=" . urlencode($message));
    exit();
}

/* ===============================
   TABULU PĀRBAUDE
================================ */
$eventsTableExists = tableExists($savienojums, "cm_events");
$appTableExists    = tableExists($savienojums, "cm_event_applications");
$formsTableExists  = tableExists($savienojums, "cm_participant_forms");

if (!$eventsTableExists) {
    $error = "Pasākumu tabula cm_events vēl nav izveidota.";
}

if (!$appTableExists) {
    $error = "Pasākumu pieteikšanās tabula cm_event_applications vēl nav izveidota.";
}

/* cm_participant_forms vajag tikai nometnēm */

/* ===============================
   LIETOTĀJA DATI FORMAS PRIEKŠAIZPILDEI
================================ */
$studentData = [
    "vards" => "",
    "uzvards" => ""
];

$stmt = $savienojums->prepare("
    SELECT vards, uzvards
    FROM cm_lietotaji
    WHERE lietotajs_id = ?
    LIMIT 1
");

if ($stmt) {
    $stmt->bind_param("i", $userId);
    $stmt->execute();

    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    if ($row) {
        $studentData["vards"] = $row["vards"] ?? "";
        $studentData["uzvards"] = $row["uzvards"] ?? "";
    }

    $stmt->close();
}

/* ===============================
   VIENKĀRŠA PIETEIKŠANĀS PASĀKUMAM
   TIKAI PASĀKUMIEM, KAS NAV NOMETNE
================================ */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["simple_event_apply_submit"])
) {
    if (!$eventsTableExists || !$appTableExists) {
        redirectWithMessage("error", "Pieteikšanās nav iespējama, jo nav izveidotas nepieciešamās tabulas.");
    }

    $eventId = (int)($_POST["event_id"] ?? 0);

    if ($eventId <= 0) {
        redirectWithMessage("error", "Nederīgs pasākums.");
    }

    $checkSql = "
        SELECT id, event_type, max_participants
        FROM cm_events
        WHERE id = ?
          AND is_active = 1
          AND (
                start_date >= CURDATE()
                OR (
                    end_date IS NOT NULL
                    AND end_date >= CURDATE()
                )
          )
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($checkSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās pārbaudīt pasākumu.");
    }

    $stmt->bind_param("i", $eventId);
    $stmt->execute();

    $eventResult = $stmt->get_result();
    $event = $eventResult->fetch_assoc();

    $stmt->close();

    if (!$event) {
        redirectWithMessage("error", "Pasākums nav atrasts, nav aktīvs vai jau ir beidzies.");
    }

    if (($event["event_type"] ?? "") === "nometne") {
        redirectWithMessage("error", "Nometnei jāaizpilda dalībnieka anketa.");
    }

    $applicationId = 0;

    $alreadySql = "
        SELECT id, status
        FROM cm_event_applications
        WHERE event_id = ?
          AND child_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($alreadySql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās pārbaudīt esošu pieteikumu.");
    }

    $stmt->bind_param("ii", $eventId, $userId);
    $stmt->execute();

    $alreadyResult = $stmt->get_result();
    $existingApplication = $alreadyResult->fetch_assoc();

    $stmt->close();

    if ($existingApplication) {
        $applicationId = (int)$existingApplication["id"];
        $existingStatus = $existingApplication["status"] ?? "";

        if (in_array($existingStatus, ["pieteikts", "apstiprināts"], true)) {
            redirectWithMessage("error", "Tu jau esi pieteicies šim pasākumam.");
        }

        $restoreSql = "
            UPDATE cm_event_applications
            SET status = 'pieteikts',
                applied_at = NOW()
            WHERE id = ?
              AND child_id = ?
            LIMIT 1
        ";

        $stmt = $savienojums->prepare($restoreSql);

        if (!$stmt) {
            redirectWithMessage("error", "Neizdevās atjaunot pieteikumu.");
        }

        $stmt->bind_param("ii", $applicationId, $userId);

        if ($stmt->execute()) {
            $stmt->close();
            redirectWithMessage("success", "Pieteikšanās pasākumam veiksmīga.");
        }

        $stmt->close();
        redirectWithMessage("error", "Neizdevās atjaunot pieteikumu.");
    }

    if (!empty($event["max_participants"])) {
        $countSql = "
            SELECT COUNT(*) AS total
            FROM cm_event_applications
            WHERE event_id = ?
              AND status IN ('pieteikts', 'apstiprināts')
        ";

        $stmt = $savienojums->prepare($countSql);

        if (!$stmt) {
            redirectWithMessage("error", "Neizdevās pārbaudīt brīvās vietas.");
        }

        $stmt->bind_param("i", $eventId);
        $stmt->execute();

        $countResult = $stmt->get_result();
        $countRow = $countResult->fetch_assoc();

        $stmt->close();

        if ((int)($countRow["total"] ?? 0) >= (int)$event["max_participants"]) {
            redirectWithMessage("error", "Šim pasākumam vairs nav brīvu vietu.");
        }
    }

    $insertSql = "
        INSERT INTO cm_event_applications 
            (event_id, child_id, status, applied_at)
        VALUES 
            (?, ?, 'pieteikts', NOW())
    ";

    $stmt = $savienojums->prepare($insertSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās sagatavot pieteikumu.");
    }

    $stmt->bind_param("ii", $eventId, $userId);

    try {
        if ($stmt->execute()) {
            $stmt->close();
            redirectWithMessage("success", "Pieteikšanās pasākumam veiksmīga.");
        }

        $stmt->close();
        redirectWithMessage("error", "Neizdevās saglabāt pieteikumu.");

    } catch (Throwable $e) {
        $stmt->close();

        if ((int)$e->getCode() === 1062) {
            redirectWithMessage("error", "Tu jau esi pieteicies šim pasākumam.");
        }

        redirectWithMessage("error", "Neizdevās saglabāt pieteikumu.");
    }
}

/* ===============================
   PIETEIKŠANĀS NOMETNEI NO POPUP FORMAS
================================ */
if (
    $_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["event_apply_submit"])
) {
    if (!$eventsTableExists || !$appTableExists || !$formsTableExists) {
        redirectWithMessage("error", "Nometnes pieteikšanās nav iespējama, jo nav izveidotas nepieciešamās tabulas.");
    }

    $eventId = (int)($_POST["event_id"] ?? 0);

    $formData = [
        "child_first_name"       => trim($_POST["child_first_name"] ?? ""),
        "child_last_name"        => trim($_POST["child_last_name"] ?? ""),
        "personal_code"          => trim($_POST["personal_code"] ?? ""),
        "school"                 => trim($_POST["school"] ?? ""),
        "declared_address"       => trim($_POST["declared_address"] ?? ""),
        "actual_address"         => trim($_POST["actual_address"] ?? ""),
        "parent_phone"           => trim($_POST["parent_phone"] ?? ""),
        "can_swim"               => trim($_POST["can_swim"] ?? "nē"),
        "health_problems"        => trim($_POST["health_problems"] ?? ""),
        "psychological_notes"    => trim($_POST["psychological_notes"] ?? ""),
        "tick_vaccine"           => trim($_POST["tick_vaccine"] ?? "nē"),
        "tick_vaccine_date"      => trim($_POST["tick_vaccine_date"] ?? ""),
        "unwanted_activities"    => trim($_POST["unwanted_activities"] ?? ""),
        "interests"              => trim($_POST["interests"] ?? ""),
        "previous_participation" => trim($_POST["previous_participation"] ?? "")
    ];

    $allowedSwim = ["jā", "nē", "daļēji"];
    $allowedVaccine = ["jā", "nē"];

    if ($eventId <= 0) {
        redirectWithMessage("error", "Nederīgs pasākums.");
    }

    if (
        $formData["child_first_name"] === "" ||
        $formData["child_last_name"] === "" ||
        $formData["personal_code"] === "" ||
        $formData["school"] === "" ||
        $formData["declared_address"] === "" ||
        $formData["actual_address"] === "" ||
        $formData["parent_phone"] === "" ||
        $formData["interests"] === "" ||
        $formData["previous_participation"] === ""
    ) {
        redirectWithMessage("error", "Lūdzu aizpildi visus obligātos pieteikuma laukus.");
    }

    if (!in_array($formData["can_swim"], $allowedSwim, true)) {
        redirectWithMessage("error", "Nederīga peldēšanas prasmes vērtība.");
    }

    if (!in_array($formData["tick_vaccine"], $allowedVaccine, true)) {
        redirectWithMessage("error", "Nederīga ērču vakcīnas vērtība.");
    }

    if ($formData["tick_vaccine"] === "jā" && $formData["tick_vaccine_date"] === "") {
        redirectWithMessage("error", "Ja bērns ir vakcinēts pret ērcēm, norādi vakcīnas datumu.");
    }

    if ($formData["tick_vaccine"] === "nē") {
        $formData["tick_vaccine_date"] = "";
    }

    $checkSql = "
        SELECT id, event_type, max_participants
        FROM cm_events
        WHERE id = ?
          AND is_active = 1
          AND (
                start_date >= CURDATE()
                OR (
                    end_date IS NOT NULL
                    AND end_date >= CURDATE()
                )
          )
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($checkSql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās pārbaudīt pasākumu.");
    }

    $stmt->bind_param("i", $eventId);
    $stmt->execute();

    $eventResult = $stmt->get_result();
    $event = $eventResult->fetch_assoc();

    $stmt->close();

    if (!$event) {
        redirectWithMessage("error", "Pasākums nav atrasts, nav aktīvs vai jau ir beidzies.");
    }

    if (($event["event_type"] ?? "") !== "nometne") {
        redirectWithMessage("error", "Šāda anketa ir nepieciešama tikai nometnēm.");
    }

    $applicationId = 0;

    $alreadySql = "
        SELECT id, status
        FROM cm_event_applications
        WHERE event_id = ?
          AND child_id = ?
        ORDER BY id DESC
        LIMIT 1
    ";

    $stmt = $savienojums->prepare($alreadySql);

    if (!$stmt) {
        redirectWithMessage("error", "Neizdevās pārbaudīt esošu pieteikumu.");
    }

    $stmt->bind_param("ii", $eventId, $userId);
    $stmt->execute();

    $alreadyResult = $stmt->get_result();
    $existingApplication = $alreadyResult->fetch_assoc();

    $stmt->close();

    if ($existingApplication) {
        $applicationId = (int)$existingApplication["id"];
        $existingStatus = $existingApplication["status"] ?? "";

        if (in_array($existingStatus, ["pieteikts", "apstiprināts"], true)) {
            redirectWithMessage("error", "Tu jau esi pieteicies šim pasākumam.");
        }
    }

    if (!empty($event["max_participants"])) {
        $countSql = "
            SELECT COUNT(*) AS total
            FROM cm_event_applications
            WHERE event_id = ?
              AND status IN ('pieteikts', 'apstiprināts')
        ";

        $stmt = $savienojums->prepare($countSql);

        if (!$stmt) {
            redirectWithMessage("error", "Neizdevās pārbaudīt brīvās vietas.");
        }

        $stmt->bind_param("i", $eventId);
        $stmt->execute();

        $countResult = $stmt->get_result();
        $countRow = $countResult->fetch_assoc();

        $stmt->close();

        if ((int)($countRow["total"] ?? 0) >= (int)$event["max_participants"]) {
            redirectWithMessage("error", "Šim pasākumam vairs nav brīvu vietu.");
        }
    }

    try {
        $savienojums->begin_transaction();

        if ($applicationId > 0) {
            $restoreSql = "
                UPDATE cm_event_applications
                SET status = 'pieteikts',
                    applied_at = NOW()
                WHERE id = ?
                  AND child_id = ?
                LIMIT 1
            ";

            $stmt = $savienojums->prepare($restoreSql);

            if (!$stmt) {
                throw new Exception("Neizdevās atjaunot pieteikumu.");
            }

            $stmt->bind_param("ii", $applicationId, $userId);
            $stmt->execute();
            $stmt->close();
        } else {
            $insertSql = "
                INSERT INTO cm_event_applications 
                    (event_id, child_id, status, applied_at)
                VALUES 
                    (?, ?, 'pieteikts', NOW())
            ";

            $stmt = $savienojums->prepare($insertSql);

            if (!$stmt) {
                throw new Exception("Neizdevās sagatavot pieteikumu.");
            }

            $stmt->bind_param("ii", $eventId, $userId);
            $stmt->execute();

            $applicationId = (int)$stmt->insert_id;

            $stmt->close();
        }

        if ($applicationId <= 0) {
            throw new Exception("Neizdevās iegūt pieteikuma ID.");
        }

        $tickDate = $formData["tick_vaccine_date"] !== ""
            ? $formData["tick_vaccine_date"]
            : null;

        $formId = 0;

        $stmt = $savienojums->prepare("
            SELECT id
            FROM cm_participant_forms
            WHERE application_id = ?
            LIMIT 1
        ");

        if (!$stmt) {
            throw new Exception("Neizdevās pārbaudīt dalībnieka formu.");
        }

        $stmt->bind_param("i", $applicationId);
        $stmt->execute();

        $formResult = $stmt->get_result();
        $formRow = $formResult->fetch_assoc();

        $stmt->close();

        if ($formRow) {
            $formId = (int)$formRow["id"];

            $formUpdateSql = "
                UPDATE cm_participant_forms
                SET
                    child_first_name = ?,
                    child_last_name = ?,
                    personal_code = ?,
                    school = ?,
                    declared_address = ?,
                    actual_address = ?,
                    parent_phone = ?,
                    can_swim = ?,
                    health_problems = ?,
                    psychological_notes = ?,
                    tick_vaccine = ?,
                    tick_vaccine_date = ?,
                    unwanted_activities = ?,
                    interests = ?,
                    previous_participation = ?,
                    updated_at = NOW()
                WHERE id = ?
                  AND application_id = ?
            ";

            $stmt = $savienojums->prepare($formUpdateSql);

            if (!$stmt) {
                throw new Exception("Neizdevās sagatavot dalībnieka formas atjaunināšanu.");
            }

            $stmt->bind_param(
                "sssssssssssssssii",
                $formData["child_first_name"],
                $formData["child_last_name"],
                $formData["personal_code"],
                $formData["school"],
                $formData["declared_address"],
                $formData["actual_address"],
                $formData["parent_phone"],
                $formData["can_swim"],
                $formData["health_problems"],
                $formData["psychological_notes"],
                $formData["tick_vaccine"],
                $tickDate,
                $formData["unwanted_activities"],
                $formData["interests"],
                $formData["previous_participation"],
                $formId,
                $applicationId
            );

            $stmt->execute();
            $stmt->close();
        } else {
            $formInsertSql = "
                INSERT INTO cm_participant_forms
                    (
                        application_id,
                        child_first_name,
                        child_last_name,
                        personal_code,
                        school,
                        declared_address,
                        actual_address,
                        parent_phone,
                        can_swim,
                        health_problems,
                        psychological_notes,
                        tick_vaccine,
                        tick_vaccine_date,
                        unwanted_activities,
                        interests,
                        previous_participation
                    )
                VALUES
                    (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ";

            $stmt = $savienojums->prepare($formInsertSql);

            if (!$stmt) {
                throw new Exception("Neizdevās sagatavot dalībnieka formas saglabāšanu.");
            }

            $stmt->bind_param(
                "isssssssssssssss",
                $applicationId,
                $formData["child_first_name"],
                $formData["child_last_name"],
                $formData["personal_code"],
                $formData["school"],
                $formData["declared_address"],
                $formData["actual_address"],
                $formData["parent_phone"],
                $formData["can_swim"],
                $formData["health_problems"],
                $formData["psychological_notes"],
                $formData["tick_vaccine"],
                $tickDate,
                $formData["unwanted_activities"],
                $formData["interests"],
                $formData["previous_participation"]
            );

            $stmt->execute();
            $stmt->close();
        }

        $savienojums->commit();

        redirectWithMessage("success", "Pieteikšanās nometnei veiksmīga.");

    } catch (Throwable $e) {
        $savienojums->rollback();

        if ((int)$e->getCode() === 1062) {
            redirectWithMessage("error", "Tu jau esi pieteicies šim pasākumam.");
        }

        redirectWithMessage("error", $e->getMessage());
    }
}

/* ===============================
   PASĀKUMU SARAKSTS
================================ */
if ($eventsTableExists) {
    if ($appTableExists) {
        $eventsSql = "
            SELECT
                e.id,
                e.title,
                e.description,
                e.start_date,
                e.end_date,
                e.start_time,
                e.end_time,
                e.location,
                e.event_type,
                e.max_participants,
                (
                    SELECT COUNT(*)
                    FROM cm_event_applications ea
                    WHERE ea.event_id = e.id
                      AND ea.status IN ('pieteikts', 'apstiprināts')
                ) AS applied_count,
                (
                    SELECT ea2.status
                    FROM cm_event_applications ea2
                    WHERE ea2.event_id = e.id
                      AND ea2.child_id = ?
                    ORDER BY ea2.id DESC
                    LIMIT 1
                ) AS user_application_status
            FROM cm_events e
            WHERE e.is_active = 1
              AND (
                    e.start_date >= CURDATE()
                    OR (
                        e.end_date IS NOT NULL
                        AND e.end_date >= CURDATE()
                    )
              )
            ORDER BY e.start_date ASC, e.start_time ASC
        ";

        $stmt = $savienojums->prepare($eventsSql);

        if ($stmt) {
            $stmt->bind_param("i", $userId);
            $stmt->execute();

            $result = $stmt->get_result();

            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }

            $stmt->close();
        } else {
            $error = "Neizdevās ielādēt pasākumus.";
        }
    } else {
        $eventsSql = "
            SELECT
                id,
                title,
                description,
                start_date,
                end_date,
                start_time,
                end_time,
                location,
                event_type,
                max_participants,
                0 AS applied_count,
                NULL AS user_application_status
            FROM cm_events
            WHERE is_active = 1
              AND (
                    start_date >= CURDATE()
                    OR (
                        end_date IS NOT NULL
                        AND end_date >= CURDATE()
                    )
              )
            ORDER BY start_date ASC, start_time ASC
        ";

        if ($result = $savienojums->query($eventsSql)) {
            while ($row = $result->fetch_assoc()) {
                $events[] = $row;
            }
        } else {
            $error = "Neizdevās ielādēt pasākumus.";
        }
    }
}

$eventsCount = count($events);

require __DIR__ . "/../includes/templates/header-student.php";
?>

<style>
.student-events-page {
    min-height: calc(100vh - 160px);
    padding: 2.4rem 0 3.5rem;
    background:
        radial-gradient(circle at top right, rgba(30, 79, 161, 0.10), transparent 32%),
        radial-gradient(circle at bottom left, rgba(244, 196, 48, 0.18), transparent 26%),
        linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
}

.events-hero {
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

.events-hero::before {
    content: "";
    position: absolute;
    inset: 0;
    background-image:
        linear-gradient(rgba(255,255,255,.06) 1px, transparent 1px),
        linear-gradient(90deg, rgba(255,255,255,.06) 1px, transparent 1px);
    background-size: 42px 42px;
    opacity: .35;
}

.events-hero > * {
    position: relative;
    z-index: 1;
}

.events-kicker {
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

.events-hero h1 {
    margin: 0 0 .65rem;
    color: #fff;
    font-size: clamp(2rem, 4vw, 3rem);
    line-height: 1.05;
    letter-spacing: -0.045em;
}

.events-hero p {
    max-width: 720px;
    margin: 0;
    color: rgba(255,255,255,.9);
    line-height: 1.75;
}

.events-hero-actions {
    display: flex;
    gap: .75rem;
    flex-wrap: wrap;
    margin-top: 1.35rem;
}

.events-hero-card {
    padding: 1.4rem;
    border-radius: 22px;
    background: rgba(255,255,255,0.14);
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
}

.events-hero-card strong {
    display: block;
    font-size: 2.1rem;
    line-height: 1;
    color: #f4c430;
}

.events-hero-card span {
    display: block;
    margin-top: .5rem;
    color: rgba(255,255,255,.86);
    line-height: 1.55;
}

.event-alert {
    display: flex;
    gap: .65rem;
    align-items: flex-start;
    padding: 1rem;
    margin-bottom: 1rem;
    border-radius: 18px;
    font-weight: 800;
}

.event-alert.success {
    background: #ecfff4;
    border: 1px solid #bdebd0;
    color: #17633a;
}

.event-alert.error {
    background: #fff0f0;
    border: 1px solid #ffd0d0;
    color: #9b1c1c;
}

.events-panel {
    padding: 1.35rem;
    border-radius: 24px;
    background: #fff;
    border: 1px solid #e8eef8;
    box-shadow: 0 14px 32px rgba(16, 24, 40, 0.06);
}

.events-panel-head {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    align-items: flex-start;
    margin-bottom: 1rem;
}

.events-panel-head h2 {
    margin: 0;
    color: #173f84;
    font-size: 1.35rem;
}

.events-panel-head p {
    margin: .3rem 0 0;
    color: #667085;
}

.events-count {
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

.events-grid {
    display: grid;
    gap: .95rem;
}

.event-card {
    display: grid;
    grid-template-columns: 1fr auto;
    gap: 1rem;
    padding: 1rem;
    border: 1px solid #edf2fb;
    border-radius: 20px;
    background: #f8fbff;
    transition: .2s ease;
}

.event-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 14px 28px rgba(23, 63, 132, 0.08);
    border-color: #d7e5ff;
}

.event-card h3 {
    margin: 0 0 .45rem;
    color: #101828;
    font-size: 1.12rem;
    line-height: 1.25;
}

.event-meta {
    display: flex;
    gap: .55rem;
    flex-wrap: wrap;
    margin-bottom: .65rem;
}

.event-pill {
    display: inline-flex;
    align-items: center;
    gap: .35rem;
    padding: .34rem .65rem;
    border-radius: 999px;
    background: #fff;
    border: 1px solid #edf2fb;
    color: #667085;
    font-size: .84rem;
    font-weight: 800;
}

.event-pill i {
    color: #1e4fa1;
}

.event-description {
    margin: .65rem 0 0;
    color: #667085;
    line-height: 1.55;
}

.event-side {
    width: 250px;
    align-self: stretch;
    display: flex;
    flex-direction: column;
    gap: .7rem;
    justify-content: space-between;
    padding: .95rem;
    border-radius: 18px;
    background: #fff;
    border: 1px solid #edf2fb;
}

.event-space {
    display: grid;
    gap: .25rem;
}

.event-space span {
    color: #667085;
    font-size: .88rem;
    font-weight: 800;
}

.event-space strong {
    color: #173f84;
    font-size: 1.05rem;
}

.event-progress {
    width: 100%;
    height: 9px;
    overflow: hidden;
    border-radius: 999px;
    background: #eef3ff;
}

.event-progress-bar {
    height: 100%;
    border-radius: inherit;
    background: linear-gradient(135deg, #173f84, #1e4fa1);
}

.event-status {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: .4rem;
    padding: .7rem .85rem;
    border-radius: 999px;
    background: #eef3ff;
    color: #173f84;
    font-weight: 950;
    text-align: center;
}

.event-status.success {
    background: #ecfff4;
    color: #17633a;
}

.event-status.warning {
    background: #fff8e6;
    color: #7a5517;
}

.event-status.disabled {
    background: #f2f4f7;
    color: #667085;
}

.event-side form {
    margin: 0;
}

.event-side .btn {
    width: 100%;
}

.events-empty {
    padding: 1.4rem;
    border-radius: 20px;
    background: #f8fbff;
    border: 1px dashed #cfe0ff;
    color: #667085;
    text-align: center;
}

.events-empty h3 {
    margin: 0 0 .4rem;
    color: #173f84;
}

/* ===============================
   POPUP PIETEIKUMA FORMA
================================ */
.apply-modal {
    position: fixed;
    inset: 0;
    z-index: 9999;
    display: none;
}

.apply-modal.open {
    display: block;
}

.apply-modal-overlay {
    position: absolute;
    inset: 0;
    background: rgba(8, 18, 13, 0.72);
    backdrop-filter: blur(8px);
}

.apply-modal-panel {
    position: relative;
    z-index: 1;
    width: min(980px, calc(100% - 1.2rem));
    max-height: calc(100vh - 1.2rem);
    overflow-y: auto;
    margin: .6rem auto;
    padding: 1.4rem;
    border-radius: 24px;
    background: #fff;
    box-shadow: 0 34px 100px rgba(0,0,0,.35);
}

.apply-modal-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    width: 42px;
    height: 42px;
    border: none;
    border-radius: 14px;
    background: #eef3ff;
    color: #173f84;
    cursor: pointer;
    font-size: 1.05rem;
}

.apply-modal-panel h2 {
    margin: 0 3rem .35rem 0;
    color: #173f84;
    font-size: 1.55rem;
}

.apply-modal-subtitle {
    margin: 0 0 1rem;
    color: #667085;
    font-weight: 800;
}

.apply-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: .9rem;
}

.form-group.full {
    grid-column: 1 / -1;
}

.form-group label {
    display: block;
    margin-bottom: .35rem;
    font-weight: 800;
    color: #344054;
}

.form-control {
    width: 100%;
    padding: .85rem .95rem;
    border: 1px solid #d0d5dd;
    border-radius: 12px;
    font-size: .95rem;
    box-sizing: border-box;
    background: #fff;
}

.form-control:focus {
    outline: none;
    border-color: #1e4fa1;
    box-shadow: 0 0 0 4px rgba(30,79,161,.10);
}

textarea.form-control {
    min-height: 95px;
    resize: vertical;
}

.apply-modal-actions {
    display: flex;
    gap: .8rem;
    flex-wrap: wrap;
    margin-top: 1.2rem;
}

body.modal-lock {
    overflow: hidden;
}

@media (max-width: 900px) {
    .events-hero,
    .event-card {
        grid-template-columns: 1fr;
    }

    .events-panel-head {
        flex-direction: column;
    }

    .event-side {
        width: 100%;
    }
}

@media (max-width: 760px) {
    .apply-grid {
        grid-template-columns: 1fr;
    }

    .apply-modal-panel {
        border-radius: 18px;
    }
}

@media (max-width: 640px) {
    .student-events-page {
        padding: 1.5rem 0 2.5rem;
    }

    .events-hero,
    .events-panel {
        border-radius: 20px;
        padding: 1.2rem;
    }

    .events-hero-actions .btn,
    .apply-modal-actions .btn {
        width: 100%;
    }
}
</style>

<main class="student-events-page">
    <div class="container">

        <section class="events-hero">
            <div>
                <div class="events-kicker">
                    <i class="fas fa-calendar-days"></i>
                    Pieejamie pasākumi
                </div>

                <h1>Pasākumi</h1>

                <p>
                    Apskati tuvākos pasākumus, seko brīvajām vietām un piesakies dalībai.
                    Nometnēm jāaizpilda papildu dalībnieka anketa.
                </p>

                <div class="events-hero-actions">
                    <a class="btn btn-primary btn-sm" href="applications.php">
                        <i class="fas fa-clipboard-check"></i>
                        Mani pieteikumi
                    </a>

                    <a class="btn btn-outline btn-sm" href="../dashboards/student.php">
                        <i class="fas fa-arrow-left"></i>
                        Atpakaļ uz paneli
                    </a>
                </div>
            </div>

            <aside class="events-hero-card">
                <strong><?= (int)$eventsCount; ?></strong>
                <span>
                    Aktīvi pasākumi, kuri pašlaik ir pieejami apskatei.
                </span>
            </aside>
        </section>

        <?php if (!empty($success)): ?>
            <div class="event-alert success">
                <i class="fas fa-circle-check"></i>
                <span><?= htmlspecialchars($success); ?></span>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="event-alert error">
                <i class="fas fa-triangle-exclamation"></i>
                <span><?= htmlspecialchars($error); ?></span>
            </div>
        <?php endif; ?>

        <section class="events-panel">
            <div class="events-panel-head">
                <div>
                    <h2>Pasākumu saraksts</h2>
                    <p>Izvēlies pasākumu un piesakies, ja vēl ir brīvas vietas.</p>
                </div>

                <div class="events-count">
                    <i class="fas fa-list-check"></i>
                    Kopā: <?= (int)$eventsCount; ?>
                </div>
            </div>

            <?php if (!empty($events)): ?>
                <div class="events-grid">

                    <?php foreach ($events as $event): ?>
                        <?php
                            $eventId = (int) ($event["id"] ?? 0);
                            $eventType = $event["event_type"] ?? "";
                            $isCamp = $eventType === "nometne";

                            $userApplicationStatus = $event["user_application_status"] ?? "";
                            $alreadyApplied = in_array($userApplicationStatus, ["pieteikts", "apstiprināts"], true);
                            $wasCancelled = in_array($userApplicationStatus, ["atteikts", "atcelts", "noraidīts"], true);

                            $appliedCount = (int) ($event["applied_count"] ?? 0);

                            $maxParticipants = $event["max_participants"] ?? null;
                            $isFull = !empty($maxParticipants) && $appliedCount >= (int) $maxParticipants;

                            $description = trim($event["description"] ?? "");

                            if ($description === "") {
                                $description = "Apraksts nav pievienots.";
                            }

                            $spaceText = !empty($maxParticipants)
                                ? $appliedCount . " / " . (int)$maxParticipants
                                : $appliedCount . " pieteikušies";

                            $progress = 0;

                            if (!empty($maxParticipants)) {
                                $progress = min(100, round(($appliedCount / (int)$maxParticipants) * 100));
                            }

                            $eventTitleForJs = htmlspecialchars($event["title"] ?? "Pasākums", ENT_QUOTES);
                        ?>

                        <article class="event-card">
                            <div>
                                <h3>
                                    <?= htmlspecialchars($event["title"] ?? "Bez nosaukuma"); ?>
                                </h3>

                                <div class="event-meta">
                                    <span class="event-pill">
                                        <i class="fas fa-tag"></i>
                                        <?= htmlspecialchars($eventType ?: "Pasākums"); ?>
                                    </span>

                                    <span class="event-pill">
                                        <i class="fas fa-calendar-day"></i>
                                        <?= htmlspecialchars(formatDateRangeLv($event["start_date"] ?? null, $event["end_date"] ?? null)); ?>
                                    </span>

                                    <span class="event-pill">
                                        <i class="fas fa-clock"></i>
                                        <?= htmlspecialchars(formatTimeRangeLv($event["start_time"] ?? null, $event["end_time"] ?? null)); ?>
                                    </span>

                                    <span class="event-pill">
                                        <i class="fas fa-location-dot"></i>
                                        <?= htmlspecialchars($event["location"] ?? "—"); ?>
                                    </span>
                                </div>

                                <p class="event-description">
                                    <?= nl2br(htmlspecialchars(mb_strimwidth($description, 0, 260, "..."))); ?>
                                </p>
                            </div>

                            <aside class="event-side">
                                <div class="event-space">
                                    <span>Pieteikušies</span>
                                    <strong><?= htmlspecialchars($spaceText); ?></strong>

                                    <?php if (!empty($maxParticipants)): ?>
                                        <div class="event-progress">
                                            <div class="event-progress-bar" style="width: <?= (int)$progress; ?>%;"></div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <?php if (!$appTableExists): ?>

                                    <div class="event-status disabled">
                                        <i class="fas fa-circle-info"></i>
                                        Pieteikšanās nav aktivizēta
                                    </div>

                                <?php elseif ($isCamp && !$formsTableExists): ?>

                                    <div class="event-status disabled">
                                        <i class="fas fa-circle-info"></i>
                                        Nometnes anketa nav aktivizēta
                                    </div>

                                <?php elseif ($alreadyApplied): ?>

                                    <div class="event-status success">
                                        <i class="fas fa-circle-check"></i>
                                        Jau pieteicies
                                    </div>

                                <?php elseif ($wasCancelled): ?>

                                    <?php if ($isCamp): ?>

                                        <button
                                            type="button"
                                            class="btn btn-primary btn-sm"
                                            onclick="openApplyModal(
                                                <?= (int)$eventId; ?>,
                                                '<?= $eventTitleForJs; ?>'
                                            )"
                                        >
                                            <i class="fas fa-rotate-right"></i>
                                            Pieteikties atkārtoti
                                        </button>

                                    <?php else: ?>

                                        <form method="POST">
                                            <input type="hidden" name="simple_event_apply_submit" value="1">
                                            <input type="hidden" name="event_id" value="<?= (int)$eventId; ?>">

                                            <button type="submit" class="btn btn-primary btn-sm">
                                                <i class="fas fa-rotate-right"></i>
                                                Pieteikties atkārtoti
                                            </button>
                                        </form>

                                    <?php endif; ?>

                                <?php elseif ($isFull): ?>

                                    <div class="event-status warning">
                                        <i class="fas fa-triangle-exclamation"></i>
                                        Vietu nav
                                    </div>

                                <?php elseif ($isCamp): ?>

                                    <button
                                        type="button"
                                        class="btn btn-primary btn-sm"
                                        onclick="openApplyModal(
                                            <?= (int)$eventId; ?>,
                                            '<?= $eventTitleForJs; ?>'
                                        )"
                                    >
                                        <i class="fas fa-paper-plane"></i>
                                        Aizpildīt anketu
                                    </button>

                                <?php else: ?>

                                    <form method="POST">
                                        <input type="hidden" name="simple_event_apply_submit" value="1">
                                        <input type="hidden" name="event_id" value="<?= (int)$eventId; ?>">

                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-paper-plane"></i>
                                            Pieteikties
                                        </button>
                                    </form>

                                <?php endif; ?>
                            </aside>
                        </article>

                    <?php endforeach; ?>

                </div>
            <?php else: ?>
                <div class="events-empty">
                    <h3>Šobrīd nav pieejamu pasākumu</h3>
                    <p>Pasākumu saraksts pagaidām ir tukšs.</p>
                </div>
            <?php endif; ?>
        </section>

    </div>
</main>

<!-- ===============================
     POPUP PIETEIKUMA FORMA NOMETNĒM
================================ -->
<div class="apply-modal" id="applyModal" aria-hidden="true">
    <div class="apply-modal-overlay" onclick="closeApplyModal()"></div>

    <div class="apply-modal-panel">
        <button type="button" class="apply-modal-close" onclick="closeApplyModal()">
            <i class="fas fa-xmark"></i>
        </button>

        <h2>Nometnes pieteikuma anketa</h2>
        <p id="applyModalEventTitle" class="apply-modal-subtitle"></p>

        <form method="POST" class="apply-form">
            <input type="hidden" name="event_apply_submit" value="1">
            <input type="hidden" name="event_id" id="modalEventId">

            <div class="apply-grid">

                <div class="form-group">
                    <label for="child_first_name">Bērna vārds *</label>
                    <input
                        type="text"
                        id="child_first_name"
                        name="child_first_name"
                        class="form-control"
                        value="<?= htmlspecialchars($studentData["vards"]); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="child_last_name">Bērna uzvārds *</label>
                    <input
                        type="text"
                        id="child_last_name"
                        name="child_last_name"
                        class="form-control"
                        value="<?= htmlspecialchars($studentData["uzvards"]); ?>"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="personal_code">Personas kods *</label>
                    <input
                        type="text"
                        id="personal_code"
                        name="personal_code"
                        class="form-control"
                        placeholder="000000-00000"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="school">Skola *</label>
                    <input
                        type="text"
                        id="school"
                        name="school"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="declared_address">Deklarētā adrese *</label>
                    <input
                        type="text"
                        id="declared_address"
                        name="declared_address"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="actual_address">Faktiskā adrese *</label>
                    <input
                        type="text"
                        id="actual_address"
                        name="actual_address"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="parent_phone">Vecāka telefons *</label>
                    <input
                        type="text"
                        id="parent_phone"
                        name="parent_phone"
                        class="form-control"
                        required
                    >
                </div>

                <div class="form-group">
                    <label for="can_swim">Vai prot peldēt? *</label>
                    <select id="can_swim" name="can_swim" class="form-control" required>
                        <option value="nē">Nē</option>
                        <option value="jā">Jā</option>
                        <option value="daļēji">Daļēji</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tick_vaccine">Ērču vakcīna *</label>
                    <select id="tick_vaccine" name="tick_vaccine" class="form-control" required>
                        <option value="nē">Nē</option>
                        <option value="jā">Jā</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="tick_vaccine_date">Ērču vakcīnas datums</label>
                    <input
                        type="date"
                        id="tick_vaccine_date"
                        name="tick_vaccine_date"
                        class="form-control"
                    >
                </div>

                <div class="form-group full">
                    <label for="health_problems">Veselības problēmas</label>
                    <textarea
                        id="health_problems"
                        name="health_problems"
                        class="form-control"
                        placeholder="Ja nav, vari atstāt tukšu."
                    ></textarea>
                </div>

                <div class="form-group full">
                    <label for="psychological_notes">Psiholoģiskas īpatnības / piezīmes</label>
                    <textarea
                        id="psychological_notes"
                        name="psychological_notes"
                        class="form-control"
                        placeholder="Ja nav, vari atstāt tukšu."
                    ></textarea>
                </div>

                <div class="form-group full">
                    <label for="unwanted_activities">Aktivitātes, kurās nevēlas piedalīties</label>
                    <textarea
                        id="unwanted_activities"
                        name="unwanted_activities"
                        class="form-control"
                        placeholder="Ja tādu nav, vari atstāt tukšu."
                    ></textarea>
                </div>

                <div class="form-group full">
                    <label for="interests">Intereses *</label>
                    <textarea
                        id="interests"
                        name="interests"
                        class="form-control"
                        required
                    ></textarea>
                </div>

                <div class="form-group full">
                    <label for="previous_participation">Iepriekšējā dalība *</label>
                    <textarea
                        id="previous_participation"
                        name="previous_participation"
                        class="form-control"
                        required
                    ></textarea>
                </div>

            </div>

            <div class="apply-modal-actions">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i>
                    Iesniegt nometnes pieteikumu
                </button>

                <button type="button" class="btn btn-outline" onclick="closeApplyModal()">
                    Atcelt
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openApplyModal(eventId, eventTitle) {
    const modal = document.getElementById("applyModal");
    const eventIdInput = document.getElementById("modalEventId");
    const titleBox = document.getElementById("applyModalEventTitle");

    if (!modal || !eventIdInput || !titleBox) {
        return;
    }

    eventIdInput.value = eventId;
    titleBox.textContent = eventTitle;

    modal.classList.add("open");
    modal.setAttribute("aria-hidden", "false");
    document.body.classList.add("modal-lock");
}

function closeApplyModal() {
    const modal = document.getElementById("applyModal");

    if (!modal) {
        return;
    }

    modal.classList.remove("open");
    modal.setAttribute("aria-hidden", "true");
    document.body.classList.remove("modal-lock");
}

document.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
        closeApplyModal();
    }
});
</script>

<?php require __DIR__ . "/../includes/templates/footer.php"; ?>
<?php

function createNotification($savienojums, $userId, $title, $message, $type = 'info', $relatedTable = null, $relatedId = null)
{
    $sql = "
        INSERT INTO cm_notifications 
        (user_id, title, message, type, related_table, related_id)
        VALUES (?, ?, ?, ?, ?, ?)
    ";

    $stmt = $savienojums->prepare($sql);

    if (!$stmt) {
        return false;
    }

    $stmt->bind_param(
        "issssi",
        $userId,
        $title,
        $message,
        $type,
        $relatedTable,
        $relatedId
    );

    return $stmt->execute();
}


function notifyUsersByClub($savienojums, $clubId, $title, $message, $type = 'info', $relatedTable = null, $relatedId = null)
{
    $sql = "
        SELECT lietotajs_id 
        FROM cm_lietotaji 
        WHERE club_id = ? 
        AND statuss = 'aktīvs'
    ";

    $stmt = $savienojums->prepare($sql);
    $stmt->bind_param("i", $clubId);
    $stmt->execute();

    $result = $stmt->get_result();

    while ($user = $result->fetch_assoc()) {
        createNotification(
            $savienojums,
            $user['lietotajs_id'],
            $title,
            $message,
            $type,
            $relatedTable,
            $relatedId
        );
    }
}


function notifyAllUsers($savienojums, $title, $message, $type = 'info', $relatedTable = null, $relatedId = null)
{
    $sql = "
        SELECT lietotajs_id 
        FROM cm_lietotaji 
        WHERE statuss = 'aktīvs'
    ";

    $result = $savienojums->query($sql);

    while ($user = $result->fetch_assoc()) {
        createNotification(
            $savienojums,
            $user['lietotajs_id'],
            $title,
            $message,
            $type,
            $relatedTable,
            $relatedId
        );
    }
}
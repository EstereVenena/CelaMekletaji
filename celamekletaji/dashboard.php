<?php
require_once "auth.php";

if (!isset($_SESSION["lietotajs_id"])) {
    header("Location: login.php");
    exit;
}

switch ($_SESSION["loma"]) {
    case "admin":
        require __DIR__ . "/dashboards/admin.php";
        break;

    case "moderators":
        require __DIR__ . "/dashboards/moderator.php";
        break;

    case "parent":
        require __DIR__ . "/dashboards/parent.php";
        break;
    
    case "pathfinder":
        require __DIR__ . "/dashboards/pathfinder.php";
        break;
    
    case "adventurer":
        require __DIR__ . "/dashboards/adventurer.php";
        break;
    
    case "masterguide":
        require __DIR__ . "/dashboards/masterguide.php";
        break;

    case "director":
        require __DIR__ . "/dashboards/director.php";
        break;

    case "teacher":
        require __DIR__ . "/dashboards/teacher.php";
        break;

    default:
        require __DIR__ . "/dashboards/user.php";
}

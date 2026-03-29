<?php
define('BASE_URL', '/4pt/venena/celamekletaji/');

function redirect(string $path): void {
    header("Location: " . BASE_URL . ltrim($path, '/'));
    exit;
}
<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/helpers.php';
requireLogin();

$user_id = $_SESSION['user_id'];
$action  = $_GET['action'] ?? 'list';

header('Content-Type: application/json');

if ($action === 'list') {
    $notifs   = getNotificaciones($pdo, $user_id);
    $no_leidas = contarNoLeidas($pdo, $user_id);
    echo json_encode(['notificaciones' => $notifs, 'no_leidas' => $no_leidas]);
    exit;
}

if ($action === 'marcar_leidas') {
    marcarTodasLeidas($pdo, $user_id);
    echo json_encode(['ok' => true]);
    exit;
}

echo json_encode(['error' => 'Acción no válida']);

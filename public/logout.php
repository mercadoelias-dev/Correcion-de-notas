<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// Endpoint de verificación para el botón atrás
if (isset($_GET['check'])) {
    header('Content-Type: application/json');
    echo json_encode(['loggedIn' => isset($_SESSION['user_id'])]);
    exit;
}

// Registrar cierre de sesión antes de destruir la sesión
if (isset($_SESSION['user_id'])) {
    try {
        $logger = new SesionLogger($pdo);
        $logger->registrarLogout((int)$_SESSION['user_id']);
    } catch (\Throwable $e) {
        // No interrumpir el logout si falla el log
    }
}

session_unset();
session_destroy();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Location: login.php');
exit;
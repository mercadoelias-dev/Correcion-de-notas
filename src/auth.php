<?php
require_once __DIR__ . '/db.php';
if (session_status() === PHP_SESSION_NONE) session_start();

// ¿Está logueado?
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Obligar login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
        header('Location: login.php');
        exit;
    }
    // Evitar que el navegador cachee páginas protegidas
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: Sat, 01 Jan 2000 00:00:00 GMT');
}

// Obtener usuario actual
function currentUser($pdo) {
    if (!isLoggedIn()) return null;

    $stmt = $pdo->prepare('SELECT profesor_id, CONCAT(primer_nombre, " ", primer_apellido) as nombre, email, rol FROM profesores WHERE profesor_id = ?');
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Obligar rol
function requireRole($pdo, $role) {
    $u = currentUser($pdo);

    if (!$u) {
        header("Location: login.php");
        exit;
    }

    if ($u['rol'] !== $role) {
        // Si NO es administrador lo enviamos a su dashboard normal
        if ($role === 'ADMINISTRADOR') {
            header("Location: dashboard_profesor.php");
            exit;
        }

        // Si no coincide el rol, acceso denegado
        http_response_code(403);
        echo "Acceso denegado.";
        exit;
    }
}
?>

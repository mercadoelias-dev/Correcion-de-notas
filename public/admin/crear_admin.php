<?php
/**
 * Script de creación de administrador inicial.
 * IMPORTANTE: Eliminar este archivo después de usarlo.
 * Acceso restringido a CLI o IP local.
 */

// Bloquear acceso web en producción
if (php_sapi_name() !== 'cli') {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($ip, ['127.0.0.1', '::1'])) {
        http_response_code(403);
        echo 'Acceso denegado.';
        exit;
    }
}

require_once __DIR__ . '/../../src/db.php';

$email    = 'admin@litoral.edu.co';
$password = readline('Contraseña para el administrador: ') ?: 'CambiarEsto123!';

$stmt = $pdo->prepare("SELECT profesor_id FROM profesores WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    echo "Ya existe un administrador con el email: $email\n";
    echo "Si olvidaste la contraseña, actualízala desde phpMyAdmin.\n";
} else {
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT INTO profesores (primer_nombre, primer_apellido, email, password_hash, rol) VALUES (?, ?, ?, ?, 'ADMINISTRADOR')");
    $stmt->execute(['Admin', 'Sistema', $email, $hash]);
    echo "Administrador creado: $email\n";
    echo "IMPORTANTE: Elimina este archivo ahora.\n";
}

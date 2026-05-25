<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/helpers.php';
require_once __DIR__.'/../src/solicitudes.php';
require_once __DIR__.'/../vendor/autoload.php';  // Cargar PHPMailer
require_once __DIR__.'/../src/mailer_smtp.php';
requireLogin();
requireRole($pdo, 'ADMINISTRADOR');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo 'Token de seguridad inválido';
        exit;
    }

    $id         = intval($_POST['id'] ?? 0);
    $action     = $_POST['action'] ?? '';
    $comentario = trim($_POST['comentario'] ?? '');

    // Obtener datos de la solicitud para la notificación
    $sol = getSolicitud($pdo, $id);

    if ($action === 'aprobar') {
        aprobarSolicitud($pdo, $id, $_SESSION['user_id'], $comentario);
        if ($sol && $sol['profesor_solicitante_id']) {
            // Notificación interna
            crearNotificacion(
                $pdo,
                $sol['profesor_solicitante_id'],
                "Tu solicitud #{$id} ({$sol['asignatura_nombre']}) fue APROBADA.",
                "solicitud_detalle.php?id={$id}"
            );
            // Correo al solicitante
            try {
                $mailer = new Mailer($pdo);
                $mailer->notificarDecision($sol, 'APROBADA', $comentario);
            } catch (\Throwable $e) {
                error_log('[aprobar_solicitud] Mailer error: ' . $e->getMessage());
            }
        }
    } else {
        rechazarSolicitud($pdo, $id, $_SESSION['user_id'], $comentario);
        if ($sol && $sol['profesor_solicitante_id']) {
            // Notificación interna
            crearNotificacion(
                $pdo,
                $sol['profesor_solicitante_id'],
                "Tu solicitud #{$id} ({$sol['asignatura_nombre']}) fue RECHAZADA.",
                "solicitud_detalle.php?id={$id}"
            );
            // Correo al solicitante
            try {
                $mailer = new Mailer($pdo);
                $mailer->notificarDecision($sol, 'RECHAZADA', $comentario);
            } catch (\Throwable $e) {
                error_log('[aprobar_solicitud] Mailer error: ' . $e->getMessage());
            }
        }
    }

    header('Location: solicitud_detalle.php?id='.$id);
    exit;
}

echo 'Método no permitido';

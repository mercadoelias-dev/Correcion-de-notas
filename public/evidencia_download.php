<?php
require_once __DIR__.'/../src/auth.php';
requireLogin();
$id = intval($_GET['id'] ?? 0);
if ($id<=0) { http_response_code(400); echo 'Archivo no válido'; exit; }
$stmt = $pdo->prepare('SELECT ev.*, s.profesor_solicitante_id FROM evidencias ev JOIN solicitudes s ON s.solicitud_id=ev.solicitud_id WHERE ev.evidencia_id=?');
$stmt->execute([$id]); $f = $stmt->fetch();
if (!$f) { http_response_code(404); echo 'No encontrado'; exit; }
$user = currentUser($pdo);
if ($user['rol'] !== 'ADMINISTRADOR' && $user['profesor_id'] != $f['profesor_solicitante_id']) { http_response_code(403); echo 'Acceso denegado'; exit; }
$path = __DIR__.'/uploads/'.$f['filename_stored'];
if (!is_file($path)) { http_response_code(404); echo 'Archivo no disponible'; exit; }
header('Content-Description: File Transfer');
header('Content-Type: '.($f['mime_type']?:'application/octet-stream'));
header('Content-Disposition: attachment; filename="'.basename($f['filename_original']).'"');
header('Content-Length: '.filesize($path));
readfile($path);
exit;
?>

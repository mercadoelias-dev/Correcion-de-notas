<?php
require_once __DIR__.'/../src/auth.php';
requireLogin();
$user = currentUser($pdo);
require_once __DIR__.'/../src/solicitudes.php';
if ($user['rol']==='ADMINISTRADOR') {
    $stmt=$pdo->query("SELECT s.*, CONCAT(p.primer_nombre, ' ', p.primer_apellido) as solicitante_nombre, a.nombre as asignatura_nombre, CONCAT(e.primer_nombre, ' ', e.primer_apellido) as estudiante_nombre FROM solicitudes s LEFT JOIN profesores p ON p.profesor_id=s.profesor_solicitante_id JOIN asignaturas a ON a.asignatura_id=s.asignatura_id JOIN estudiantes e ON e.estudiante_id=s.estudiante_id ORDER BY s.fecha_envio DESC");
    $list = $stmt->fetchAll();
} else {
    $list = getSolicitudesPorProfesor($pdo,$user['profesor_id']);
}
include 'header.php';
?>
<div class="card"><h3>Historial de Solicitudes</h3>
<table class="table"><thead><tr><th>ID</th><th>Asignatura</th><th>Estudiante</th><th>Solicitante</th><th>Estado</th><th>Fecha de envío</th><th></th></tr></thead><tbody>
<?php foreach($list as $s): ?>
<tr>
<td><?= $s['solicitud_id'] ?></td>
<td><?= h($s['asignatura_nombre'] ?? '') ?></td>
<td><?= h($s['estudiante_nombre'] ?? '') ?></td>
<td><?= h($s['solicitante_nombre'] ?? '') ?></td>
<td><?= h($s['estado']) ?></td>
<td><?= h($s['fecha_envio']) ?></td>
<td><a class="btn btn-secondary" href="solicitud_detalle.php?id=<?= $s['solicitud_id'] ?>">Ver</a></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php include 'footer.php'; ?>

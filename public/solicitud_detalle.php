<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/helpers.php';
requireLogin();
$user = currentUser($pdo);

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) { echo 'ID inválido'; exit; }

require_once __DIR__.'/../src/solicitudes.php';
$s = getSolicitud($pdo, $id);

if (!$s) { echo 'Solicitud no encontrada'; exit; }

if ($user['rol'] !== 'ADMINISTRADOR' && $s['profesor_solicitante_id'] != $user['profesor_id']) {
    http_response_code(403); echo 'Acceso denegado'; exit;
}

$files = $pdo->prepare('SELECT * FROM evidencias WHERE solicitud_id = ?');
$files->execute([$id]);
$files = $files->fetchAll();

$tipo_labels = [
    'CORRECCION'  => 'Corrección',
    'REPORTE'     => 'Reporte',
    'VALIDACION'  => 'Validación',
    'SUFICIENCIA' => 'Suficiencia',
    'HABILITACION'=> 'Habilitación',
    'SUPLETORIOS' => 'Supletorios',
];
$corte_labels = [
    'PRIMERO' => 'Primer corte (30%)',
    'SEGUNDO' => 'Segundo corte (30%)',
    'TERCERO' => 'Tercer corte (40%)',
];
$estado_color = [
    'PENDIENTE'  => '#F59E0B',
    'APROBADA'   => '#10B981',
    'RECHAZADA'  => '#EF4444',
];

include 'header.php';
?>

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
  <div>
    <h3 style="margin:0">Solicitud #<?= $s['solicitud_id'] ?></h3>
    <span style="font-size:12px;color:#aaa">Enviada el <?= date('d/m/Y H:i', strtotime($s['fecha_envio'])) ?></span>
  </div>
  <span style="background:<?= $estado_color[$s['estado']] ?? '#aaa' ?>22;color:<?= $estado_color[$s['estado']] ?? '#aaa' ?>;padding:5px 16px;border-radius:20px;font-size:13px;font-weight:700">
    <?= h($s['estado']) ?>
  </span>
</div>

<!-- DATOS DEL ESTUDIANTE -->
<div class="card" style="margin-bottom:16px">
  <div class="det-section-title">Datos personales del estudiante</div>
  <div class="det-grid">
    <div class="det-field"><span class="det-label">Nombre del estudiante</span><span class="det-val"><?= h($s['estudiante_nombre']) ?></span></div>
    <div class="det-field"><span class="det-label">Documento de identidad</span><span class="det-val"><?= h($s['estudiante_documento'] ?: ($s['estudiante_doc_bd'] ?: '—')) ?></span></div>
    <div class="det-field"><span class="det-label">Programa</span><span class="det-val"><?= h($s['carrera_nombre'] ?? '—') ?></span></div>
    <div class="det-field"><span class="det-label">Semestre</span><span class="det-val"><?= h($s['estudiante_semestre'] ?: ($s['estudiante_semestre_bd'] ?: '—')) ?></span></div>
  </div>
</div>

<!-- DATOS ACADÉMICOS -->
<div class="card" style="margin-bottom:16px">
  <div class="det-section-title">Datos académicos</div>
  <div class="det-grid">
    <div class="det-field"><span class="det-label">Período académico</span><span class="det-val"><?= h($s['periodo_academico'] ?? '—') ?></span></div>
    <div class="det-field"><span class="det-label">Nombre de la asignatura</span><span class="det-val"><?= h($s['asignatura_nombre']) ?></span></div>
    <div class="det-field"><span class="det-label">Código de la asignatura</span><span class="det-val"><?= h($s['asignatura_codigo'] ?? '—') ?></span></div>
    <div class="det-field"><span class="det-label">Profesor solicitante</span><span class="det-val"><?= h($s['solicitante_nombre']) ?></span></div>
  </div>
</div>

<!-- CORTE ACADÉMICO -->
<div class="card" style="margin-bottom:16px">
  <div class="det-section-title">Corte académico a corregir o reportar</div>
  <div class="det-grid">
    <div class="det-field"><span class="det-label">Tipo</span><span class="det-val"><?= h($tipo_labels[$s['tipo_solicitud']] ?? $s['tipo_solicitud']) ?></span></div>
    <div class="det-field"><span class="det-label">Corte</span><span class="det-val"><?= h($corte_labels[$s['corte']] ?? $s['corte']) ?></span></div>
  </div>
  <?php if (!empty($s['motivo'])): ?>
  <div class="det-field" style="margin-top:10px"><span class="det-label">Motivo</span><div class="det-val" style="margin-top:4px"><?= nl2br(h($s['motivo'])) ?></div></div>
  <?php endif; ?>
</div>

<!-- CORRECCIÓN DE NOTA -->
<div class="card" style="margin-bottom:16px">
  <div class="det-section-title">Corrección de nota</div>
  <div class="nota-table-wrap">
    <table class="nota-table">
      <thead>
        <tr>
          <th rowspan="2"></th>
          <th colspan="3">Nota</th>
          <th rowspan="2">En letras</th>
        </tr>
        <tr>
          <th>Formativa</th>
          <th>Aplicativa</th>
          <th>Cognitiva</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td class="nota-label">Nota inicialmente reportada</td>
          <td><?= $s['nota_inicial_formativa'] ?? '—' ?></td>
          <td><?= $s['nota_inicial_aplicativa'] ?? '—' ?></td>
          <td><?= $s['nota_inicial_cognitiva'] ?? '—' ?></td>
          <td><?= h($s['nota_inicial_letras'] ?? '—') ?></td>
        </tr>
        <tr>
          <td class="nota-label">Nota corregida</td>
          <td><?= $s['nota_corregida_formativa'] ?? '—' ?></td>
          <td><?= $s['nota_corregida_aplicativa'] ?? '—' ?></td>
          <td><?= $s['nota_corregida_cognitiva'] ?? '—' ?></td>
          <td><?= h($s['nota_corregida_letras'] ?? '—') ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<!-- EVIDENCIAS -->
<div class="card" style="margin-bottom:16px">
  <div class="det-section-title">Evidencias</div>
  <?php if (empty($files)): ?>
    <p style="color:#aaa;font-size:13px">No hay archivos adjuntos.</p>
  <?php else: ?>
    <ul style="margin:0;padding-left:18px">
      <?php foreach ($files as $f): ?>
        <li style="margin-bottom:6px">
          <a href="evidencia_download.php?id=<?= $f['evidencia_id'] ?>" style="color:#5A3DBA;font-size:13px">
            <?= h($f['filename_original']) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>

<!-- DECISIÓN DEL ADMINISTRADOR -->
<?php if (!empty($s['justificacion_administrador'])): ?>
<div class="card" style="margin-bottom:16px">
  <div class="det-section-title">Decisión del administrador</div>
  <p style="margin:0;font-size:14px"><?= nl2br(h($s['justificacion_administrador'])) ?></p>
  <?php if ($s['fecha_decision']): ?>
    <p style="font-size:12px;color:#aaa;margin-top:8px">Fecha de decisión: <?= date('d/m/Y H:i', strtotime($s['fecha_decision'])) ?></p>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- FORMULARIO DE APROBACIÓN/RECHAZO -->
<?php if ($user['rol'] === 'ADMINISTRADOR' && $s['estado'] === 'PENDIENTE'): ?>
<div class="card">
  <div class="det-section-title">Tomar decisión</div>
  <form method="post" action="aprobar_solicitud.php">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $s['solicitud_id'] ?>">
    <div style="margin-bottom:12px">
      <label>Comentario de decisión</label>
      <textarea name="comentario" class="input" rows="3" required placeholder="Escriba el motivo de la decisión..."></textarea>
    </div>
    <div style="display:flex;gap:10px">
      <button class="btn btn-primary" name="action" value="aprobar">Aprobar</button>
      <button class="btn btn-danger" name="action" value="rechazar">Rechazar</button>
    </div>
  </form>
</div>
<?php endif; ?>

<style>
.det-section-title {
  font-size: 12px;
  font-weight: 700;
  color: #fff;
  background: #1F2347;
  padding: 6px 12px;
  border-radius: 6px;
  margin-bottom: 14px;
  text-transform: uppercase;
  letter-spacing: .4px;
}
.det-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.det-field { display: flex; flex-direction: column; gap: 3px; }
.det-label { font-size: 11px; color: #aaa; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
.det-val { font-size: 14px; color: #1F2347; font-weight: 500; }
.nota-table-wrap { overflow-x: auto; }
.nota-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.nota-table th, .nota-table td { border: 1px solid #E2E4EF; padding: 8px 12px; text-align: center; }
.nota-table th { background: #f0ebff; color: #1F2347; font-weight: 600; }
.nota-label { text-align: left; font-weight: 500; color: #1F2347; white-space: nowrap; }
.btn-danger { background:#EF4444;color:white;border:none;cursor:pointer;font-family:inherit;font-weight:600;padding:10px 20px;border-radius:10px;font-size:14px;transition:all .15s; }
.btn-danger:hover { background:#dc2626; }
</style>

<?php include 'footer.php'; ?>

<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';
requireLogin();
requireRole($pdo, 'ADMINISTRADOR');

// ── Arrays con estadísticas del sistema ──────────────────────────────────────
$stats = [];

$row = $pdo->query("SELECT COUNT(*) FROM solicitudes WHERE estado='PENDIENTE'")->fetchColumn();
$stats['pendientes'] = (int)$row;

$row = $pdo->query("SELECT COUNT(*) FROM solicitudes WHERE estado='APROBADA'")->fetchColumn();
$stats['aprobadas'] = (int)$row;

$row = $pdo->query("SELECT COUNT(*) FROM solicitudes WHERE estado='RECHAZADA'")->fetchColumn();
$stats['rechazadas'] = (int)$row;

$row = $pdo->query("SELECT COUNT(*) FROM profesores WHERE rol='SOLICITANTE'")->fetchColumn();
$stats['profesores'] = (int)$row;

$stats['total'] = $stats['pendientes'] + $stats['aprobadas'] + $stats['rechazadas'];

// Array de tarjetas de resumen
$tarjetas = [
    ['label' => 'Pendientes',  'valor' => $stats['pendientes'],  'color' => '#F59E0B'],
    ['label' => 'Aprobadas',   'valor' => $stats['aprobadas'],   'color' => '#10B981'],
    ['label' => 'Rechazadas',  'valor' => $stats['rechazadas'],  'color' => '#EF4444'],
    ['label' => 'Profesores',  'valor' => $stats['profesores'],  'color' => '#5A3DBA'],
];

// Solicitudes pendientes
$stmt = $pdo->query("
    SELECT s.*, 
           CONCAT(p.primer_nombre, ' ', p.primer_apellido) as solicitante_nombre,
           a.nombre as asignatura_nombre
    FROM solicitudes s
    LEFT JOIN profesores p ON p.profesor_id = s.profesor_solicitante_id
    JOIN asignaturas a ON a.asignatura_id = s.asignatura_id
    WHERE s.estado = 'PENDIENTE'
    ORDER BY s.fecha_envio DESC
");
$pendientes = $stmt->fetchAll();

// ── Registro de sesiones ──────────────────────────────────────────────────────
$logger             = new SesionLogger($pdo);
$historial_sesiones = $logger->obtenerHistorial(0, 200);
$resumen_sesiones   = $logger->resumenPorUsuario();

include 'header.php';
?>

<div class="header-row">
    <div class="header-title">Panel de Coordinación</div>
    <div><span class="small">Solicitudes pendientes: <span class="badge"><?= $stats['pendientes'] ?></span></span></div>
</div>

<!-- Tarjetas de estadísticas (array) -->
<div class="stats-grid">
    <?php foreach ($tarjetas as $t): ?>
    <div class="stat-card">
        <div class="stat-valor" style="color:<?= $t['color'] ?>"><?= $t['valor'] ?></div>
        <div class="stat-label"><?= $t['label'] ?></div>
        <div class="stat-bar" style="background:<?= $t['color'] ?>22">
            <div class="stat-bar-fill" style="background:<?= $t['color'] ?>; width:<?= $stats['total'] > 0 ? round(($t['valor']/$stats['total'])*100) : 0 ?>%"></div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Tabla de pendientes -->
<div class="card">
    <h4>Solicitudes pendientes de revisión</h4>
    <?php if (empty($pendientes)): ?>
        <p style="color:var(--muted); font-size:14px;">No hay solicitudes pendientes.</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr><th>ID</th><th>Asignatura</th><th>Solicitante</th><th>Nota inicial</th><th>Nota corregida</th><th>Fecha de envío</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach ($pendientes as $s): ?>
            <tr>
                <td><?= $s['solicitud_id'] ?></td>
                <td><?= h($s['asignatura_nombre']) ?></td>
                <td><?= h($s['solicitante_nombre']) ?></td>
                <td>
                    <?php
                        $ni = array_filter([
                            $s['nota_inicial_formativa'],
                            $s['nota_inicial_aplicativa'],
                            $s['nota_inicial_cognitiva']
                        ], fn($v) => $v !== null);
                        echo $ni ? implode(' / ', array_map(fn($v) => number_format($v,2), $ni)) : (isset($s['nota_actual']) && $s['nota_actual'] !== null ? number_format($s['nota_actual'],2) : '—');
                    ?>
                </td>
                <td>
                    <?php
                        $nc = array_filter([
                            $s['nota_corregida_formativa'],
                            $s['nota_corregida_aplicativa'],
                            $s['nota_corregida_cognitiva']
                        ], fn($v) => $v !== null);
                        echo $nc ? implode(' / ', array_map(fn($v) => number_format($v,2), $nc)) : (isset($s['nota_propuesta']) && $s['nota_propuesta'] !== null ? number_format($s['nota_propuesta'],2) : '—');
                    ?>
                </td>
                <td><?= h($s['fecha_envio']) ?></td>
                <td><a class="btn btn-secondary" href="solicitud_detalle.php?id=<?= $s['solicitud_id'] ?>">Revisar</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- ══════════════════════════════════════════════════════════════════
     REGISTRO DE SESIONES
     ══════════════════════════════════════════════════════════════════ -->
<div style="margin-top:32px">
    <div class="header-row" style="margin-bottom:0">
        <div class="header-title" style="font-size:18px">Registro de Actividad de Usuarios</div>
    </div>
</div>

<!-- Tabs -->
<div style="border-bottom:2px solid #e8e5ff;display:flex;gap:4px;margin-bottom:0">
    <button class="tab-btn-dash active" onclick="switchTabDash('tabResumenDash',this)">Resumen por usuario</button>
    <button class="tab-btn-dash" onclick="switchTabDash('tabHistorialDash',this)">Historial completo</button>
</div>

<!-- Tab: Resumen -->
<div id="tabResumenDash" class="tab-panel-dash">
    <div class="card" style="border-top:3px solid #5A3DBA;margin-top:0;border-radius:0 0 14px 14px">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
            <h4 style="margin:0">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#5A3DBA" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><path stroke-linecap="round" stroke-linejoin="round" d="M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z"/></svg>
                Actividad de Usuarios
            </h4>
            <span style="font-size:12px;color:#aaa"><?= count($resumen_sesiones) ?> usuario(s) con actividad registrada</span>
        </div>
        <?php if (empty($resumen_sesiones)): ?>
            <div style="text-align:center;padding:32px;color:#aaa;font-size:14px">
                <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#ddd" stroke-width="1.5" style="display:block;margin:0 auto 12px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Aún no hay sesiones registradas. Los registros aparecerán cuando los usuarios inicien sesión.
            </div>
        <?php else: ?>
        <table class="table">
            <thead>
                <tr>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th>Total sesiones</th>
                    <th>Último ingreso</th>
                    <th>Último cierre</th>
                    <th>Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($resumen_sesiones as $rs): ?>
                <tr>
                    <td style="font-weight:600"><?= h($rs['nombre']) ?></td>
                    <td><?= h($rs['email']) ?></td>
                    <td>
                        <?php if ($rs['rol'] === 'ADMINISTRADOR'): ?>
                            <span class="rol-badge-dash admin">Administrador</span>
                        <?php else: ?>
                            <span class="rol-badge-dash solicitante">Solicitante</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="background:#f0ebff;color:#5A3DBA;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700">
                            <?= (int)$rs['total_sesiones'] ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($rs['ultimo_login']): ?>
                            <span style="font-weight:600"><?= date('d/m/Y', strtotime($rs['ultimo_login'])) ?></span>
                            <span style="color:#5A3DBA;font-size:12px;margin-left:4px"><?= date('H:i:s', strtotime($rs['ultimo_login'])) ?></span>
                        <?php else: ?>
                            <span style="color:#aaa">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($rs['ultimo_logout']): ?>
                            <span style="font-weight:600"><?= date('d/m/Y', strtotime($rs['ultimo_logout'])) ?></span>
                            <span style="color:#E84E9F;font-size:12px;margin-left:4px"><?= date('H:i:s', strtotime($rs['ultimo_logout'])) ?></span>
                        <?php else: ?>
                            <span style="background:#FEF3C7;color:#92400E;padding:2px 8px;border-radius:12px;font-size:11px;font-weight:600">Sesión activa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <button class="btn btn-sm" style="background:#f0ebff;color:#5A3DBA;border:none;cursor:pointer;font-family:inherit;font-weight:600;padding:6px 12px;border-radius:8px;font-size:13px"
                            onclick="filtrarPorUsuarioDash(<?= (int)$rs['usuario_id'] ?>, <?= htmlspecialchars(json_encode($rs['nombre']), ENT_QUOTES) ?>)">
                            Ver historial
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Tab: Historial completo -->
<div id="tabHistorialDash" class="tab-panel-dash" style="display:none">
    <div class="card" style="border-top:3px solid #E84E9F;margin-top:0;border-radius:0 0 14px 14px">
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px">
            <h4 style="margin:0" id="tituloHistorialDash">
                <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#E84E9F" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Historial de Sesiones
            </h4>
            <div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap">
                <input type="text" id="buscadorSesionesDash" class="input" placeholder="Buscar usuario o IP..." oninput="filtrarSesionesDash()" style="max-width:240px">
                <button class="btn btn-sm" style="background:#f0ebff;color:#5A3DBA;border:none;cursor:pointer;font-family:inherit;font-weight:600;padding:6px 12px;border-radius:8px;font-size:13px" onclick="limpiarFiltroSesionesDash()">Ver todos</button>
            </div>
        </div>
        <?php if (empty($historial_sesiones)): ?>
            <div style="text-align:center;padding:32px;color:#aaa;font-size:14px">
                <svg width="40" height="40" fill="none" viewBox="0 0 24 24" stroke="#ddd" stroke-width="1.5" style="display:block;margin:0 auto 12px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Aún no hay sesiones registradas.
            </div>
        <?php else: ?>
        <table class="table" id="tablaHistorialDash">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Usuario</th>
                    <th>Rol</th>
                    <th>Fecha ingreso</th>
                    <th>Hora ingreso</th>
                    <th>Fecha cierre</th>
                    <th>Hora cierre</th>
                    <th>Duración</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial_sesiones as $i => $s): ?>
                <tr data-uid="<?= (int)$s['usuario_id'] ?>">
                    <td><?= $i + 1 ?></td>
                    <td>
                        <div style="font-weight:600;color:#1F2347"><?= h($s['nombre']) ?></div>
                        <div style="font-size:11px;color:#aaa"><?= h($s['email']) ?></div>
                    </td>
                    <td>
                        <?php if ($s['rol'] === 'ADMINISTRADOR'): ?>
                            <span class="rol-badge-dash admin">Admin</span>
                        <?php else: ?>
                            <span class="rol-badge-dash solicitante">Solicitante</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:600"><?= date('d/m/Y', strtotime($s['fecha_login'])) ?></td>
                    <td>
                        <span style="background:#EDE9FE;color:#5A3DBA;padding:3px 8px;border-radius:8px;font-size:12px;font-weight:700">
                            <?= date('H:i:s', strtotime($s['fecha_login'])) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($s['fecha_logout']): ?>
                            <span style="font-weight:600"><?= date('d/m/Y', strtotime($s['fecha_logout'])) ?></span>
                        <?php else: ?>
                            <span style="color:#aaa">—</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['fecha_logout']): ?>
                            <span style="background:#FCE7F3;color:#E84E9F;padding:3px 8px;border-radius:8px;font-size:12px;font-weight:700">
                                <?= date('H:i:s', strtotime($s['fecha_logout'])) ?>
                            </span>
                        <?php else: ?>
                            <span style="background:#FEF3C7;color:#92400E;padding:3px 8px;border-radius:8px;font-size:11px;font-weight:600">Activa</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['duracion_segundos'] !== null): ?>
                            <?php
                                $seg = (int)$s['duracion_segundos'];
                                $hh  = floor($seg / 3600);
                                $mm  = floor(($seg % 3600) / 60);
                                $ss  = $seg % 60;
                                echo $hh > 0 ? "{$hh}h {$mm}m" : ($mm > 0 ? "{$mm}m {$ss}s" : "{$ss}s");
                            ?>
                        <?php else: ?>
                            <span style="color:#aaa">—</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:12px;color:#6F6F6F;font-family:monospace"><?= h($s['ip_address'] ?? '—') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <div id="paginacionSesionesDash" style="display:flex;gap:4px;margin-top:14px;flex-wrap:wrap"></div>
        <?php endif; ?>
    </div>
</div>

<style>
.tab-btn-dash {
    padding: 10px 20px;
    border: none;
    background: none;
    font-family: inherit;
    font-size: 14px;
    font-weight: 600;
    color: #aaa;
    cursor: pointer;
    border-bottom: 3px solid transparent;
    margin-bottom: -2px;
    transition: all .15s;
}
.tab-btn-dash.active { color: #5A3DBA; border-bottom-color: #5A3DBA; }
.tab-panel-dash { margin-top: 0; }
.rol-badge-dash { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.rol-badge-dash.admin      { background:#f0ebff; color:#5A3DBA; }
.rol-badge-dash.solicitante { background:#fce7f3; color:#E84E9F; }
</style>

<script>
function switchTabDash(tabId, btn) {
    document.querySelectorAll('.tab-panel-dash').forEach(function(p){ p.style.display='none'; });
    document.querySelectorAll('.tab-btn-dash').forEach(function(b){ b.classList.remove('active'); });
    document.getElementById(tabId).style.display = 'block';
    btn.classList.add('active');
}

var porPaginaSesD = 15, paginaActualSesD = 1;

function mostrarPaginaSesionesDash(p) {
    var todasFilas = Array.from(document.querySelectorAll('#tablaHistorialDash tbody tr'));
    var visibles = todasFilas.filter(function(tr){ return tr.getAttribute('data-hidden') !== '1'; });
    var totalPaginas = Math.ceil(visibles.length / porPaginaSesD);
    paginaActualSesD = p;
    todasFilas.forEach(function(tr){ tr.style.display='none'; });
    visibles.forEach(function(tr, i) {
        if (i >= (p-1)*porPaginaSesD && i < p*porPaginaSesD) tr.style.display = '';
    });
    var pag = document.getElementById('paginacionSesionesDash');
    if (!pag) return;
    pag.innerHTML = '';
    for (var i = 1; i <= totalPaginas; i++) {
        var btn = document.createElement('button');
        btn.textContent = i;
        btn.className = 'btn btn-sm' + (i === p ? ' btn-primary' : '');
        btn.style.cssText = 'margin:0 2px;min-width:32px;font-size:13px;padding:6px 12px;border-radius:8px;' + (i !== p ? 'background:#f0ebff;color:#5A3DBA;border:none;cursor:pointer;font-family:inherit;font-weight:600' : '');
        btn.setAttribute('data-p', i);
        btn.onclick = function() { mostrarPaginaSesionesDash(parseInt(this.getAttribute('data-p'))); };
        pag.appendChild(btn);
    }
}

function filtrarSesionesDash() {
    var q = document.getElementById('buscadorSesionesDash').value.toLowerCase();
    document.querySelectorAll('#tablaHistorialDash tbody tr').forEach(function(tr) {
        tr.setAttribute('data-hidden', tr.textContent.toLowerCase().includes(q) ? '0' : '1');
    });
    mostrarPaginaSesionesDash(1);
}

function filtrarPorUsuarioDash(uid, nombre) {
    switchTabDash('tabHistorialDash', document.querySelectorAll('.tab-btn-dash')[1]);
    document.getElementById('tituloHistorialDash').innerHTML =
        '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#E84E9F" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Sesiones de ' + nombre;
    document.querySelectorAll('#tablaHistorialDash tbody tr').forEach(function(tr) {
        tr.setAttribute('data-hidden', tr.getAttribute('data-uid') == uid ? '0' : '1');
    });
    mostrarPaginaSesionesDash(1);
    document.getElementById('tabHistorialDash').scrollIntoView({behavior:'smooth'});
}

function limpiarFiltroSesionesDash() {
    document.getElementById('buscadorSesionesDash').value = '';
    document.getElementById('tituloHistorialDash').innerHTML =
        '<svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#E84E9F" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>Historial de Sesiones';
    document.querySelectorAll('#tablaHistorialDash tbody tr').forEach(function(tr){ tr.setAttribute('data-hidden','0'); });
    mostrarPaginaSesionesDash(1);
}

document.addEventListener('DOMContentLoaded', function(){ mostrarPaginaSesionesDash(1); });
</script>

<?php include 'footer.php'; ?>

<?php
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/helpers.php';
requireLogin();
requireRole($pdo, 'ADMINISTRADOR');

$admin_id = $_SESSION['user_id'];
$flash = get_flash();

// ── Acciones POST ─────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        http_response_code(403);
        echo 'Token de seguridad inválido';
        exit;
    }

    $accion     = $_POST['accion'] ?? '';
    $target_id  = intval($_POST['profesor_id'] ?? 0);

    if ($target_id === $admin_id) {
        flash('No puedes modificar tu propia cuenta.');
        header('Location: admin_usuarios.php');
        exit;
    }

    if ($accion === 'crear_usuario') {
        $primer_nombre    = trim($_POST['primer_nombre'] ?? '');
        $segundo_nombre   = trim($_POST['segundo_nombre'] ?? '');
        $primer_apellido  = trim($_POST['primer_apellido'] ?? '');
        $segundo_apellido = trim($_POST['segundo_apellido'] ?? '');
        $email  = trim($_POST['email'] ?? '');
        $pass   = $_POST['password'] ?? '';
        $pass2  = $_POST['password2'] ?? '';
        $rol    = in_array($_POST['rol'] ?? '', ['ADMINISTRADOR','SOLICITANTE']) ? $_POST['rol'] : 'SOLICITANTE';
        $err = [];
        if (empty($primer_nombre))   $err[] = 'El primer nombre es obligatorio.';
        if (empty($primer_apellido)) $err[] = 'El primer apellido es obligatorio.';
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $err[] = 'Correo no válido.';
        if (strlen($pass) < 8)              $err[] = 'Mínimo 8 caracteres.';
        if (!preg_match('/[A-Z]/', $pass))  $err[] = 'Debe tener una mayúscula.';
        if (!preg_match('/[a-z]/', $pass))  $err[] = 'Debe tener una minúscula.';
        if (!preg_match('/[0-9]/', $pass))  $err[] = 'Debe tener un número.';
        if (!preg_match('/[\W_]/', $pass))  $err[] = 'Debe tener un carácter especial.';
        if ($pass !== $pass2)               $err[] = 'Las contraseñas no coinciden.';
        if (empty($err)) {
            $chk = $pdo->prepare("SELECT profesor_id FROM profesores WHERE email=?");
            $chk->execute([$email]);
            if ($chk->fetch()) { $err[] = 'El correo ya está registrado.'; }
        }
        if (empty($err)) {
            $pdo->prepare("INSERT INTO profesores (primer_nombre,segundo_nombre,primer_apellido,segundo_apellido,email,password_hash,rol) VALUES (?,?,?,?,?,?,?)")
                ->execute([$primer_nombre, $segundo_nombre ?: null, $primer_apellido, $segundo_apellido ?: null, $email, password_hash($pass, PASSWORD_DEFAULT), $rol]);
            flash('Usuario creado correctamente.');
            header('Location: admin_usuarios.php'); exit;
        } else {
            flash('Error: ' . implode(' | ', $err));
            header('Location: admin_usuarios.php'); exit;
        }
    }

    if ($accion === 'reset_password' && $target_id > 0) {
        $nueva = $_POST['nueva_pass'] ?? '';
        $conf  = $_POST['conf_pass'] ?? '';
        $err = [];
        if (strlen($nueva) < 8)             $err[] = 'Mínimo 8 caracteres.';
        if (!preg_match('/[A-Z]/', $nueva))  $err[] = 'Debe tener una mayúscula.';
        if (!preg_match('/[a-z]/', $nueva))  $err[] = 'Debe tener una minúscula.';
        if (!preg_match('/[0-9]/', $nueva))  $err[] = 'Debe tener un número.';
        if (!preg_match('/[\W_]/', $nueva))  $err[] = 'Debe tener un carácter especial.';
        if ($nueva !== $conf)                $err[] = 'Las contraseñas no coinciden.';
        if (empty($err)) {
            $pdo->prepare("UPDATE profesores SET password_hash=? WHERE profesor_id=?")
                ->execute([password_hash($nueva, PASSWORD_DEFAULT), $target_id]);
            flash('Contraseña actualizada correctamente.');
        } else {
            flash('Error: ' . implode(' | ', $err));
        }
        header('Location: admin_usuarios.php'); exit;
    }

    if ($accion === 'cambiar_rol' && $target_id > 0) {
        $nuevo_rol = $_POST['nuevo_rol'] ?? '';
        if (in_array($nuevo_rol, ['ADMINISTRADOR', 'SOLICITANTE'])) {
            $pdo->prepare("UPDATE profesores SET rol = ? WHERE profesor_id = ?")->execute([$nuevo_rol, $target_id]);
            flash('Rol actualizado correctamente.');
        }
    }

    if ($accion === 'toggle_activo' && $target_id > 0) {
        $actual = $pdo->prepare("SELECT activo FROM profesores WHERE profesor_id=?");
        $actual->execute([$target_id]);
        $estado = $actual->fetchColumn();
        $pdo->prepare("UPDATE profesores SET activo=? WHERE profesor_id=?")->execute([$estado ? 0 : 1, $target_id]);
        flash($estado ? 'Usuario deshabilitado.' : 'Usuario habilitado.');
        header('Location: admin_usuarios.php'); exit;
    }

    if ($accion === 'eliminar' && $target_id > 0) {
        $total_admins = $pdo->query("SELECT COUNT(*) FROM profesores WHERE rol='ADMINISTRADOR'")->fetchColumn();
        $es_admin = $pdo->prepare("SELECT rol FROM profesores WHERE profesor_id=?");
        $es_admin->execute([$target_id]);
        $rol_target = $es_admin->fetchColumn();
        if ($rol_target === 'ADMINISTRADOR' && $total_admins <= 1) {
            flash('No puedes eliminar el único administrador del sistema.');
        } else {
            $pdo->prepare("DELETE FROM profesores WHERE profesor_id = ?")->execute([$target_id]);
            flash('Usuario eliminado correctamente.');
        }
    }

    // ── Solicitudes de cambio de correo ───────────────────────────────────────
    if ($accion === 'aprobar_email') {
        $sol_id = intval($_POST['sol_id'] ?? 0);
        if ($sol_id > 0) {
            $sol = $pdo->prepare("SELECT * FROM solicitudes_email WHERE sol_id=? AND estado='PENDIENTE'");
            $sol->execute([$sol_id]);
            $se = $sol->fetch();
            if ($se) {
                // Verificar que el nuevo email no esté en uso
                $chk = $pdo->prepare("SELECT profesor_id FROM profesores WHERE email=? AND profesor_id != ?");
                $chk->execute([$se['email_nuevo'], $se['profesor_id']]);
                if ($chk->fetch()) {
                    flash('Error: El correo ' . $se['email_nuevo'] . ' ya está en uso por otro usuario.');
                } else {
                    $pdo->prepare("UPDATE profesores SET email=? WHERE profesor_id=?")->execute([$se['email_nuevo'], $se['profesor_id']]);
                    $pdo->prepare("UPDATE solicitudes_email SET estado='APROBADA', admin_id=?, fecha_resolucion=NOW() WHERE sol_id=?")->execute([$admin_id, $sol_id]);
                    crearNotificacion($pdo, $se['profesor_id'], 'Tu solicitud de cambio de correo fue APROBADA. Nuevo correo: ' . $se['email_nuevo'], 'perfil.php');
                    flash('Correo actualizado correctamente.');
                }
            }
        }
        header('Location: admin_usuarios.php'); exit;
    }

    if ($accion === 'rechazar_email') {
        $sol_id  = intval($_POST['sol_id'] ?? 0);
        $motivo  = trim($_POST['motivo_rechazo'] ?? '');
        if ($sol_id > 0) {
            $sol = $pdo->prepare("SELECT * FROM solicitudes_email WHERE sol_id=? AND estado='PENDIENTE'");
            $sol->execute([$sol_id]);
            $se = $sol->fetch();
            if ($se) {
                $pdo->prepare("UPDATE solicitudes_email SET estado='RECHAZADA', admin_id=?, motivo_rechazo=?, fecha_resolucion=NOW() WHERE sol_id=?")->execute([$admin_id, $motivo, $sol_id]);
                crearNotificacion($pdo, $se['profesor_id'], 'Tu solicitud de cambio de correo fue RECHAZADA.' . ($motivo ? ' Motivo: ' . $motivo : ''), 'perfil.php');
                flash('Solicitud rechazada.');
            }
        }
        header('Location: admin_usuarios.php'); exit;
    }

    header('Location: admin_usuarios.php');
    exit;
}

// ── Obtener usuarios ──────────────────────────────────────────────────────────

// Solicitudes de cambio de correo pendientes
$sols_email = $pdo->query("
    SELECT se.*, CONCAT(p.primer_nombre,' ',p.primer_apellido) AS nombre, p.email AS email_actual_bd
    FROM solicitudes_email se
    JOIN profesores p ON p.profesor_id = se.profesor_id
    WHERE se.estado = 'PENDIENTE'
    ORDER BY se.fecha_solicitud ASC
")->fetchAll();

$stmt = $pdo->query("
    SELECT p.profesor_id,
           p.primer_nombre, p.segundo_nombre, p.primer_apellido, p.segundo_apellido,
           CONCAT(p.primer_nombre, ' ', IFNULL(p.segundo_nombre,''), ' ', p.primer_apellido, ' ', IFNULL(p.segundo_apellido,'')) as nombre,
           p.email, p.rol, p.fecha_creacion, p.foto, p.activo,
           COUNT(s.solicitud_id) as total_solicitudes
    FROM profesores p
    LEFT JOIN solicitudes s ON s.profesor_solicitante_id = p.profesor_id
    GROUP BY p.profesor_id
    ORDER BY p.fecha_creacion DESC
");
$todos = $stmt->fetchAll();
$usuarios     = array_filter($todos, fn($u) => $u['activo']);
$desactivados = array_filter($todos, fn($u) => !$u['activo']);
$roles = ['ADMINISTRADOR', 'SOLICITANTE'];

include 'header.php';
?>

<div class="header-row">
    <div class="header-title">Gestión de Usuarios</div>
    <div>
        <span class="small">Activos: <span class="badge"><?= count($usuarios) ?></span></span>
        <button class="btn btn-primary" style="margin-left:12px" onclick="document.getElementById('modalCrear').style.display='flex'">+ Crear usuario</button>
    </div>
</div>

<div style="margin-bottom:16px">
    <input type="text" id="buscadorUsuarios" class="input" placeholder="Buscar por nombre, email o rol..." oninput="filtrarUsuarios()" style="max-width:360px">
</div>

<?php if ($flash): ?>
    <div class="alert alert-info"><?= h($flash) ?></div>
<?php endif; ?>

<!-- Modal crear usuario -->
<div id="modalCrear" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:100%;max-width:520px;max-height:90vh;overflow-y:auto;position:relative">
    <button onclick="document.getElementById('modalCrear').style.display='none'" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#888">&times;</button>
    <h4 style="margin-bottom:18px">Crear nuevo usuario</h4>
    <form method="post" id="formCrear">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="crear_usuario">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px">
        <div><label>Primer nombre *</label><input class="input" name="primer_nombre" placeholder="Ej: Juan" required></div>
        <div><label>Segundo nombre</label><input class="input" name="segundo_nombre" placeholder="Opcional"></div>
        <div><label>Primer apellido *</label><input class="input" name="primer_apellido" placeholder="Ej: Pérez" required></div>
        <div><label>Segundo apellido</label><input class="input" name="segundo_apellido" placeholder="Opcional"></div>
      </div>
      <div style="margin-bottom:12px"><label>Correo institucional *</label><input class="input" type="email" name="email" placeholder="usuario@uni.edu" required></div>
      <div style="margin-bottom:12px"><label>Rol *</label>
        <select class="input" name="rol">
          <option value="SOLICITANTE">Solicitante (Docente)</option>
          <option value="ADMINISTRADOR">Administrador</option>
        </select>
      </div>
      <div style="margin-bottom:6px"><label>Contraseña *</label><input class="input" type="password" name="password" id="passNew" placeholder="Mín. 8 caracteres" required oninput="chkP(this.value)"></div>
      <div style="background:#f8f7ff;border-radius:10px;border:1px solid #e8e5ff;padding:10px 14px;margin-bottom:12px;font-size:12px">
        <div id="rLen"  class="prule">Mínimo 8 caracteres</div>
        <div id="rUp"   class="prule">Una letra mayúscula</div>
        <div id="rLow"  class="prule">Una letra minúscula</div>
        <div id="rNum"  class="prule">Un número</div>
        <div id="rSpec" class="prule">Un carácter especial (!@#$...)</div>
      </div>
      <div style="margin-bottom:16px"><label>Confirmar contraseña *</label><input class="input" type="password" name="password2" placeholder="Repite la contraseña" required></div>
      <button class="btn btn-primary" type="submit" style="width:100%">Crear usuario</button>
    </form>
  </div>
</div>

<div class="card">
    <h4>Usuarios Registrados</h4>
    <table class="table" id="tablaUsuarios">
        <thead>
            <tr>
                <th>ID</th><th>Nombre</th><th>Email</th><th>Rol actual</th>
                <th>Registro</th><th>Perfil</th><th>Estado</th><th>Cambiar rol</th><th>Eliminar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($usuarios as $i => $u): ?>
            <?php $es_yo = ($u['profesor_id'] == $admin_id); ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= h(trim($u['nombre'])) ?></td>
                <td><?= h($u['email']) ?></td>
                <td>
                    <?php if ($u['rol'] === 'ADMINISTRADOR'): ?>
                        <span class="rol-badge admin">Administrador</span>
                    <?php else: ?>
                        <span class="rol-badge solicitante">Solicitante</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($u['fecha_creacion'])) ?></td>
                <td>
                    <button class="btn btn-sm" style="background:#f0ebff;color:#5A3DBA;border:none;cursor:pointer;font-family:inherit;font-weight:600"
                        onclick="verPerfil(<?= htmlspecialchars(json_encode($u), ENT_QUOTES, 'UTF-8') ?>)">Ver</button>
                </td>
                <td>
                    <?php if ($es_yo): ?>
                        <span style="color:var(--muted);font-size:13px;">—</span>
                    <?php else: ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="toggle_activo">
                            <input type="hidden" name="profesor_id" value="<?= $u['profesor_id'] ?>">
                            <button class="btn btn-sm" type="submit"
                                style="background:<?= $u['activo'] ? '#FEF3C7' : '#D1FAE5' ?>;color:<?= $u['activo'] ? '#92400E' : '#065F46' ?>;border:none;cursor:pointer;font-family:inherit;font-weight:600">
                                <?= $u['activo'] ? 'Deshabilitar' : 'Habilitar' ?>
                            </button>
                        </form>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($es_yo): ?>
                        <span style="color:var(--muted);font-size:13px;">—</span>
                    <?php else: ?>
                        <form method="post" style="display:flex;gap:6px;align-items:center;">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="cambiar_rol">
                            <input type="hidden" name="profesor_id" value="<?= $u['profesor_id'] ?>">
                            <select name="nuevo_rol" class="input-sm">
                                <?php foreach ($roles as $r): ?>
                                    <option value="<?= $r ?>" <?= $u['rol'] === $r ? 'selected' : '' ?>>
                                        <?= $r === 'ADMINISTRADOR' ? 'Administrador' : 'Solicitante' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-primary" type="submit">Guardar</button>
                        </form>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($es_yo): ?>
                        <span style="color:var(--muted);font-size:13px;">—</span>
                    <?php else: ?>
                        <form method="post" onsubmit="return confirm('¿Eliminar a <?= h(addslashes(trim($u['nombre']))) ?>? Esta acción no se puede deshacer.')">
                            <?= csrf_field() ?>
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="profesor_id" value="<?= $u['profesor_id'] ?>">
                            <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                        </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div id="paginacion" style="display:flex;gap:4px;margin-top:14px;flex-wrap:wrap"></div>
</div>

<?php if (!empty($desactivados)): ?>
<div class="card" style="margin-top:24px;border-top:3px solid #EF4444">
    <h4 style="color:#EF4444">Usuarios Deshabilitados <span class="badge" style="background:#FEE2E2;color:#991B1B"><?= count($desactivados) ?></span></h4>
    <table class="table">
        <thead>
            <tr><th>ID</th><th>Nombre</th><th>Email</th><th>Rol</th><th>Registro</th><th>Habilitar</th><th>Eliminar</th></tr>
        </thead>
        <tbody>
            <?php foreach ($desactivados as $u): ?>
            <tr style="opacity:.7">
                <td><?= $u['profesor_id'] ?></td>
                <td><?= h(trim($u['nombre'])) ?></td>
                <td><?= h($u['email']) ?></td>
                <td>
                    <?php if ($u['rol'] === 'ADMINISTRADOR'): ?>
                        <span class="rol-badge admin">Administrador</span>
                    <?php else: ?>
                        <span class="rol-badge solicitante">Solicitante</span>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($u['fecha_creacion'])) ?></td>
                <td>
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="toggle_activo">
                        <input type="hidden" name="profesor_id" value="<?= $u['profesor_id'] ?>">
                        <button class="btn btn-sm" type="submit" style="background:#D1FAE5;color:#065F46;border:none;cursor:pointer;font-family:inherit;font-weight:600">Habilitar</button>
                    </form>
                </td>
                <td>
                    <form method="post" onsubmit="return confirm('¿Eliminar a <?= h(addslashes(trim($u['nombre']))) ?>?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="eliminar">
                        <input type="hidden" name="profesor_id" value="<?= $u['profesor_id'] ?>">
                        <button class="btn btn-sm btn-danger" type="submit">Eliminar</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Modal perfil usuario -->
<div id="modalPerfil" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:100%;max-width:480px;max-height:90vh;overflow-y:auto;position:relative">
    <button onclick="document.getElementById('modalPerfil').style.display='none'" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#888">&times;</button>
    <div style="display:flex;align-items:center;gap:16px;margin-bottom:20px">
      <div id="pAvatar" style="width:64px;height:64px;border-radius:50%;background:linear-gradient(135deg,#5A3DBA,#E84E9F);color:white;font-size:22px;font-weight:800;display:flex;align-items:center;justify-content:center;flex-shrink:0"></div>
      <div>
        <div id="pNombre" style="font-size:17px;font-weight:700;color:#1F2347"></div>
        <div id="pEmail"  style="font-size:13px;color:#6F6F6F;margin:3px 0"></div>
        <div id="pRol"></div>
      </div>
    </div>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:20px;font-size:13px">
      <div style="background:#f8f9fc;border-radius:10px;padding:12px">
        <div style="color:#aaa;font-size:11px;margin-bottom:4px">Miembro desde</div>
        <div id="pFecha" style="font-weight:600;color:#1F2347"></div>
      </div>
      <div style="background:#f8f9fc;border-radius:10px;padding:12px">
        <div style="color:#aaa;font-size:11px;margin-bottom:4px">Solicitudes</div>
        <div id="pSolicitudes" style="font-weight:600;color:#1F2347"></div>
      </div>
    </div>
    <hr style="border:none;border-top:1px solid #eee;margin-bottom:18px">
    <div style="font-size:13px;font-weight:600;color:#1F2347;margin-bottom:10px">Resetear contraseña</div>
    <form method="post" id="formReset">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="reset_password">
      <input type="hidden" name="profesor_id" id="pId">
      <div style="margin-bottom:8px"><input class="input" type="password" name="nueva_pass" id="passReset" placeholder="Nueva contraseña" required oninput="chkR(this.value)"></div>
      <div style="background:#f8f7ff;border-radius:10px;border:1px solid #e8e5ff;padding:10px 14px;margin-bottom:10px;font-size:12px">
        <div id="rrLen"  class="prule">Mínimo 8 caracteres</div>
        <div id="rrUp"   class="prule">Una mayúscula</div>
        <div id="rrLow"  class="prule">Una minúscula</div>
        <div id="rrNum"  class="prule">Un número</div>
        <div id="rrSpec" class="prule">Un carácter especial</div>
      </div>
      <div style="margin-bottom:14px"><input class="input" type="password" name="conf_pass" placeholder="Confirmar contraseña" required></div>
      <button class="btn btn-primary" type="submit" style="width:100%">Guardar nueva contraseña</button>
    </form>
  </div>
</div>

<style>
.rol-badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600; }
.rol-badge.admin       { background:#f0ebff; color:#5A3DBA; }
.rol-badge.solicitante { background:#fce7f3; color:#E84E9F; }
.input-sm { padding:6px 10px; font-size:13px; border-radius:8px; border:1.5px solid #E2E4EF; background:#F8F9FC; font-family:inherit; outline:none; width:auto; }
.btn-sm   { padding:6px 12px; font-size:13px; border-radius:8px; }
.btn-danger { background:#EF4444; color:white; border:none; cursor:pointer; font-family:inherit; font-weight:600; transition:all .15s ease; }
.btn-danger:hover { background:#dc2626; transform:translateY(-1px); }
.prule { color:#aaa; margin-bottom:3px; }
.prule::before { content:"· "; font-weight:700; }
.prule.ok { color:#10B981; }
.prule.ok::before { content:"✓ "; font-weight:700; }
</style>

<script>
function filtrarUsuarios() {
    var q = document.getElementById('buscadorUsuarios').value.toLowerCase();
    var filas = Array.from(document.querySelectorAll('#tablaUsuarios tbody tr'));
    filas.forEach(function(tr) {
        tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none';
    });
    if (q) {
        document.getElementById('paginacion').style.display = 'none';
    } else {
        document.getElementById('paginacion').style.display = 'flex';
        mostrarPagina(paginaActual);
    }
}

var porPagina = 10, paginaActual = 1;

function mostrarPagina(p) {
    var filas = Array.from(document.querySelectorAll('#tablaUsuarios tbody tr'));
    var totalPaginas = Math.ceil(filas.length / porPagina);
    paginaActual = p;
    filas.forEach(function(tr, i) {
        tr.style.display = (i >= (p-1)*porPagina && i < p*porPagina) ? '' : 'none';
    });
    var pag = document.getElementById('paginacion');
    pag.innerHTML = '';
    for (var i = 1; i <= totalPaginas; i++) {
        var btn = document.createElement('button');
        btn.textContent = i;
        btn.className = 'btn btn-sm' + (i === p ? ' btn-primary' : '');
        btn.style.cssText = 'margin:0 2px;min-width:32px;' + (i !== p ? 'background:#f0ebff;color:#5A3DBA;border:none;' : '');
        btn.setAttribute('data-p', i);
        btn.onclick = function() { mostrarPagina(parseInt(this.getAttribute('data-p'))); };
        pag.appendChild(btn);
    }
}

document.addEventListener('DOMContentLoaded', function() { mostrarPagina(1); });

function chkP(v) {
    document.getElementById('rLen').className  = 'prule' + (v.length >= 8    ? ' ok' : '');
    document.getElementById('rUp').className   = 'prule' + (/[A-Z]/.test(v)  ? ' ok' : '');
    document.getElementById('rLow').className  = 'prule' + (/[a-z]/.test(v)  ? ' ok' : '');
    document.getElementById('rNum').className  = 'prule' + (/[0-9]/.test(v)  ? ' ok' : '');
    document.getElementById('rSpec').className = 'prule' + (/[\W_]/.test(v)  ? ' ok' : '');
}
function chkR(v) {
    document.getElementById('rrLen').className  = 'prule' + (v.length >= 8   ? ' ok' : '');
    document.getElementById('rrUp').className   = 'prule' + (/[A-Z]/.test(v) ? ' ok' : '');
    document.getElementById('rrLow').className  = 'prule' + (/[a-z]/.test(v) ? ' ok' : '');
    document.getElementById('rrNum').className  = 'prule' + (/[0-9]/.test(v) ? ' ok' : '');
    document.getElementById('rrSpec').className = 'prule' + (/[\W_]/.test(v) ? ' ok' : '');
}
function verPerfil(u) {
    var nombre = (u.primer_nombre + ' ' + (u.segundo_nombre||'') + ' ' + u.primer_apellido + ' ' + (u.segundo_apellido||'')).replace(/\s+/,' ').trim();
    var iniciales = (u.primer_nombre.charAt(0) + u.primer_apellido.charAt(0)).toUpperCase();
    var avatar = document.getElementById('pAvatar');
    if (u.foto) {
        avatar.innerHTML = '<img src="uploads/fotos/' + u.foto + '" style="width:64px;height:64px;border-radius:50%;object-fit:cover;display:block" onerror="this.parentElement.textContent=\'' + iniciales + '\'">';
    } else {
        avatar.textContent = iniciales;
    }
    document.getElementById('pNombre').textContent = nombre;
    document.getElementById('pEmail').textContent  = u.email;
    document.getElementById('pRol').innerHTML = u.rol === 'ADMINISTRADOR'
        ? '<span class="rol-badge admin">Administrador</span>'
        : '<span class="rol-badge solicitante">Solicitante</span>';
    var d = new Date(u.fecha_creacion);
    document.getElementById('pFecha').textContent = d.toLocaleDateString('es-CO');
    document.getElementById('pSolicitudes').textContent = u.total_solicitudes;
    document.getElementById('pId').value = u.profesor_id;
    document.getElementById('passReset').value = '';
    document.getElementById('modalPerfil').style.display = 'flex';
}
</script>

<!-- ══ Solicitudes de cambio de correo ══ -->
<?php if (!empty($sols_email)): ?>
<div class="card" style="margin-top:24px;border-top:3px solid #F59E0B">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px">
        <h4 style="margin:0;color:#92400E">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="#F59E0B" stroke-width="2" style="vertical-align:-3px;margin-right:6px"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg>
            Solicitudes de cambio de correo
        </h4>
        <span style="background:#FEF3C7;color:#92400E;padding:3px 10px;border-radius:20px;font-size:12px;font-weight:700">
            <?= count($sols_email) ?> pendiente(s)
        </span>
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Usuario</th>
                <th>Correo actual</th>
                <th>Correo solicitado</th>
                <th>Fecha solicitud</th>
                <th>Aprobar</th>
                <th>Rechazar</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($sols_email as $se): ?>
            <tr>
                <td style="font-weight:600"><?= h($se['nombre']) ?></td>
                <td style="font-family:monospace;font-size:13px"><?= h($se['email_actual']) ?></td>
                <td>
                    <span style="background:#D1FAE5;color:#065F46;padding:3px 10px;border-radius:8px;font-size:13px;font-weight:600;font-family:monospace">
                        <?= h($se['email_nuevo']) ?>
                    </span>
                </td>
                <td style="font-size:13px"><?= date('d/m/Y H:i', strtotime($se['fecha_solicitud'])) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('¿Aprobar el cambio de correo a <?= h(addslashes($se['email_nuevo'])) ?>?')">
                        <?= csrf_field() ?>
                        <input type="hidden" name="accion" value="aprobar_email">
                        <input type="hidden" name="sol_id" value="<?= (int)$se['sol_id'] ?>">
                        <button class="btn btn-sm" type="submit" style="background:#D1FAE5;color:#065F46;border:none;cursor:pointer;font-family:inherit;font-weight:600">
                            ✓ Aprobar
                        </button>
                    </form>
                </td>
                <td>
                    <button class="btn btn-sm btn-danger" onclick="abrirRechazo(<?= (int)$se['sol_id'] ?>, '<?= h(addslashes($se['nombre'])) ?>')">
                        ✕ Rechazar
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Modal rechazo de correo -->
<div id="modalRechazoEmail" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.45);z-index:1000;align-items:center;justify-content:center">
  <div class="card" style="width:100%;max-width:420px;position:relative">
    <button onclick="document.getElementById('modalRechazoEmail').style.display='none'" style="position:absolute;top:14px;right:16px;background:none;border:none;font-size:20px;cursor:pointer;color:#888">&times;</button>
    <h4 style="margin-bottom:4px">Rechazar solicitud</h4>
    <p id="rechazoNombre" style="font-size:13px;color:#6F6F6F;margin-bottom:16px"></p>
    <form method="post" id="formRechazoEmail">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="rechazar_email">
      <input type="hidden" name="sol_id" id="rechazoSolId">
      <div style="margin-bottom:14px">
        <label style="font-size:13px;font-weight:600;color:#1F2347;display:block;margin-bottom:6px">Motivo del rechazo (opcional)</label>
        <textarea class="input" name="motivo_rechazo" rows="3" placeholder="Ej: El correo solicitado no pertenece al dominio institucional..."></textarea>
      </div>
      <button class="btn btn-danger" type="submit" style="width:100%">Confirmar rechazo</button>
    </form>
  </div>
</div>

<script>
function abrirRechazo(solId, nombre) {
    document.getElementById('rechazoSolId').value = solId;
    document.getElementById('rechazoNombre').textContent = 'Solicitud de: ' + nombre;
    document.getElementById('modalRechazoEmail').style.display = 'flex';
}
</script>

<?php include 'footer.php'; ?>

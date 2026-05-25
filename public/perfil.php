<?php
ini_set('display_errors',1); error_reporting(E_ALL);
require_once __DIR__.'/../src/auth.php';
require_once __DIR__.'/../src/helpers.php';
requireLogin();
$user = currentUser($pdo);

$stmt = $pdo->prepare('SELECT primer_nombre,segundo_nombre,primer_apellido,segundo_apellido,email,rol,fecha_creacion,foto FROM profesores WHERE profesor_id=?');
$stmt->execute([$user['profesor_id']]);
$perfil = $stmt->fetch();

$r = $pdo->prepare("SELECT estado,COUNT(*) as c FROM solicitudes WHERE profesor_solicitante_id=? GROUP BY estado");
$r->execute([$user['profesor_id']]);
$stats = ['pendiente'=>0,'aprobada'=>0,'rechazada'=>0];
foreach ($r->fetchAll() as $row) $stats[strtolower($row['estado'])] = (int)$row['c'];
$stats['total'] = array_sum($stats);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Token inválido';
    } else {
        $accion = $_POST['accion'] ?? '';

        // ── Foto ──────────────────────────────────────────────────────────────
        if ($accion === 'foto') {
            $file = $_FILES['foto'] ?? null;
            if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
                $error = 'Error al subir.';
            } else {
                $allowedMime = ['image/jpeg','image/png','image/webp'];
                $mime = mime_content_type($file['tmp_name']);
                $extMap = ['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
                if (!in_array($mime, $allowedMime)) {
                    $error = 'Solo JPG, PNG, WEBP.';
                } elseif ($file['size'] > 3*1024*1024) {
                    $error = 'Max 3MB.';
                } else {
                    $dir = __DIR__.'/uploads/fotos/';
                    if (!is_dir($dir)) mkdir($dir, 0755, true);
                    if (!empty($perfil['foto']) && is_file($dir.$perfil['foto'])) unlink($dir.$perfil['foto']);
                    $stored = 'foto_'.$user['profesor_id'].'_'.time().'.'.$extMap[$mime];
                    if (move_uploaded_file($file['tmp_name'], $dir.$stored)) {
                        $pdo->prepare('UPDATE profesores SET foto=? WHERE profesor_id=?')->execute([$stored, $user['profesor_id']]);
                        flash('Foto actualizada.');
                        header('Location: perfil.php'); exit;
                    } else {
                        $error = 'No se pudo guardar.';
                    }
                }
            }
        }

        // ── Datos personales ──────────────────────────────────────────────────
        if ($accion === 'perfil') {
            $pn = trim($_POST['primer_nombre'] ?? '');
            $sn = trim($_POST['segundo_nombre'] ?? '');
            $pa = trim($_POST['primer_apellido'] ?? '');
            $sa = trim($_POST['segundo_apellido'] ?? '');
            if (empty($pn) || empty($pa)) {
                $error = 'Nombre y apellido obligatorios.';
            } else {
                $pdo->prepare('UPDATE profesores SET primer_nombre=?,segundo_nombre=?,primer_apellido=?,segundo_apellido=? WHERE profesor_id=?')
                    ->execute([$pn, $sn ?: null, $pa, $sa ?: null, $user['profesor_id']]);
                flash('Perfil actualizado.');
                header('Location: perfil.php'); exit;
            }
        }

        // ── Contraseña ────────────────────────────────────────────────────────
        if ($accion === 'password') {
            $actual = $_POST['password_actual'] ?? '';
            $nueva  = $_POST['password_nueva'] ?? '';
            $conf   = $_POST['password_confirma'] ?? '';
            $hr = $pdo->prepare('SELECT password_hash FROM profesores WHERE profesor_id=?');
            $hr->execute([$user['profesor_id']]);
            $hash = $hr->fetchColumn();
            if (!password_verify($actual, $hash)) {
                $error = 'Contraseña actual incorrecta.';
            } elseif (strlen($nueva) < 6) {
                $error = 'Mínimo 6 caracteres.';
            } elseif ($nueva !== $conf) {
                $error = 'Las contraseñas no coinciden.';
            } else {
                $pdo->prepare('UPDATE profesores SET password_hash=? WHERE profesor_id=?')
                    ->execute([password_hash($nueva, PASSWORD_DEFAULT), $user['profesor_id']]);
                flash('Contraseña actualizada.');
                header('Location: perfil.php'); exit;
            }
        }

        // ── Solicitar cambio de correo ────────────────────────────────────────
        if ($accion === 'solicitar_cambio_email') {
            $nuevo_email = trim($_POST['nuevo_email'] ?? '');
            if (!filter_var($nuevo_email, FILTER_VALIDATE_EMAIL)) {
                flash('Error: Correo no válido.');
            } elseif ($nuevo_email === $perfil['email']) {
                flash('Error: El nuevo correo es igual al actual.');
            } else {
                $chk = $pdo->prepare("SELECT profesor_id FROM profesores WHERE email=? AND profesor_id != ?");
                $chk->execute([$nuevo_email, $user['profesor_id']]);
                if ($chk->fetch()) {
                    flash('Error: Ese correo ya está registrado por otro usuario.');
                } else {
                    $pdo->prepare("UPDATE solicitudes_email SET estado='CANCELADA' WHERE profesor_id=? AND estado='PENDIENTE'")
                        ->execute([$user['profesor_id']]);
                    $pdo->prepare("INSERT INTO solicitudes_email (profesor_id, email_actual, email_nuevo) VALUES (?,?,?)")
                        ->execute([$user['profesor_id'], $perfil['email'], $nuevo_email]);
                    $admins = $pdo->query("SELECT profesor_id FROM profesores WHERE rol='ADMINISTRADOR' AND activo=1")->fetchAll();
                    foreach ($admins as $adm) {
                        crearNotificacion($pdo, $adm['profesor_id'],
                            "El usuario {$user['nombre']} solicita cambio de correo.",
                            "admin_usuarios.php"
                        );
                    }
                    flash('Solicitud enviada. El administrador la revisará pronto.');
                }
            }
            header('Location: perfil.php'); exit;
        }

        // ── Cancelar solicitud de correo ──────────────────────────────────────
        if ($accion === 'cancelar_cambio_email') {
            $pdo->prepare("UPDATE solicitudes_email SET estado='CANCELADA' WHERE profesor_id=? AND estado='PENDIENTE'")
                ->execute([$user['profesor_id']]);
            flash('Solicitud cancelada.');
            header('Location: perfil.php'); exit;
        }
    }
}

// Recargar perfil actualizado
$stmt = $pdo->prepare('SELECT primer_nombre,segundo_nombre,primer_apellido,segundo_apellido,email,rol,fecha_creacion,foto FROM profesores WHERE profesor_id=?');
$stmt->execute([$user['profesor_id']]);
$perfil = $stmt->fetch();

$flash   = get_flash();
$fotoUrl = !empty($perfil['foto']) && is_file(__DIR__.'/uploads/fotos/'.$perfil['foto'])
    ? 'uploads/fotos/'.htmlspecialchars($perfil['foto'], ENT_QUOTES, 'UTF-8')
    : null;
$iniciales = strtoupper(substr($perfil['primer_nombre'],0,1).substr($perfil['primer_apellido'],0,1));

// Solicitud de cambio de correo pendiente
$sol_email_q = $pdo->prepare("SELECT * FROM solicitudes_email WHERE profesor_id=? AND estado='PENDIENTE' ORDER BY fecha_solicitud DESC LIMIT 1");
$sol_email_q->execute([$user['profesor_id']]);
$sol_email = $sol_email_q->fetch();

include 'header.php';
?>

<?php if ($flash): ?>
  <div class="alert alert-info"><?= htmlspecialchars($flash, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>
<?php if ($error): ?>
  <div class="alert alert-error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<div class="perfil-header card">
  <div style="display:flex;flex-direction:column;align-items:center;gap:8px;flex-shrink:0">
    <div class="perfil-avatar-wrap">
      <?php if ($fotoUrl): ?>
        <img src="<?= $fotoUrl ?>" alt="Foto" class="perfil-avatar-img">
      <?php else: ?>
        <div class="perfil-avatar"><?= $iniciales ?></div>
      <?php endif; ?>
    </div>
    <form method="post" enctype="multipart/form-data" id="fotoForm">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="accion" value="foto">
      <input type="file" name="foto" id="fotoInput" accept="image/jpeg,image/png,image/webp" style="display:none"
             onchange="document.getElementById('fotoForm').submit()">
    </form>
    <button type="button" onclick="document.getElementById('fotoInput').click()" class="btn-cambiar-foto">Cambiar foto</button>
  </div>
  <div class="perfil-info">
    <div class="perfil-nombre"><?= htmlspecialchars(trim($perfil['primer_nombre'].' '.($perfil['segundo_nombre']??'').' '.$perfil['primer_apellido'].' '.($perfil['segundo_apellido']??'')), ENT_QUOTES, 'UTF-8') ?></div>
    <div class="perfil-email"><?= htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8') ?></div>
    <span class="rol-badge <?= $perfil['rol']==='ADMINISTRADOR' ? 'admin' : 'solicitante' ?>">
      <?= $perfil['rol']==='ADMINISTRADOR' ? 'Administrador' : 'Docente' ?>
    </span>
  </div>
  <div class="perfil-stats">
    <div class="pstat"><span class="pstat-val"><?= $stats['total'] ?></span><span class="pstat-label">Total</span></div>
    <div class="pstat"><span class="pstat-val" style="color:#F59E0B"><?= $stats['pendiente'] ?></span><span class="pstat-label">Pendientes</span></div>
    <div class="pstat"><span class="pstat-val" style="color:#10B981"><?= $stats['aprobada'] ?></span><span class="pstat-label">Aprobadas</span></div>
    <div class="pstat"><span class="pstat-val" style="color:#EF4444"><?= $stats['rechazada'] ?></span><span class="pstat-label">Rechazadas</span></div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:20px">
  <div class="card">
    <h4>Datos personales</h4>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="accion" value="perfil">
      <div class="form-row" style="margin-bottom:14px">
        <div class="col"><label>Primer nombre</label><input class="input" name="primer_nombre" value="<?= htmlspecialchars($perfil['primer_nombre']??'', ENT_QUOTES, 'UTF-8') ?>" required></div>
        <div class="col"><label>Segundo nombre</label><input class="input" name="segundo_nombre" value="<?= htmlspecialchars($perfil['segundo_nombre']??'', ENT_QUOTES, 'UTF-8') ?>" placeholder="Opcional"></div>
      </div>
      <div class="form-row" style="margin-bottom:14px">
        <div class="col"><label>Primer apellido</label><input class="input" name="primer_apellido" value="<?= htmlspecialchars($perfil['primer_apellido']??'', ENT_QUOTES, 'UTF-8') ?>" required></div>
        <div class="col"><label>Segundo apellido</label><input class="input" name="segundo_apellido" value="<?= htmlspecialchars($perfil['segundo_apellido']??'', ENT_QUOTES, 'UTF-8') ?>" placeholder="Opcional"></div>
      </div>
      <div style="margin-bottom:14px">
        <label>Correo</label>
        <input class="input" value="<?= htmlspecialchars($perfil['email'], ENT_QUOTES, 'UTF-8') ?>" disabled style="opacity:.6;cursor:not-allowed">
      </div>
      <div class="form-row" style="margin-bottom:14px">
        <div class="col"><label>Rol</label><input class="input" value="<?= $perfil['rol']==='ADMINISTRADOR' ? 'Administrador' : 'Docente' ?>" disabled style="opacity:.6;cursor:not-allowed"></div>
        <div class="col"><label>Miembro desde</label><input class="input" value="<?= date('d/m/Y', strtotime($perfil['fecha_creacion'])) ?>" disabled style="opacity:.6;cursor:not-allowed"></div>
      </div>
      <button class="btn btn-primary">Guardar cambios</button>
    </form>
  </div>

  <div class="card">
    <h4>Cambiar contraseña</h4>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="accion" value="password">
      <div style="margin-bottom:14px"><label>Contraseña actual</label><input class="input" type="password" name="password_actual" placeholder="..." required></div>
      <div style="margin-bottom:14px"><label>Nueva contraseña</label><input class="input" type="password" name="password_nueva" placeholder="Mínimo 6 caracteres" required></div>
      <div style="margin-bottom:14px"><label>Confirmar</label><input class="input" type="password" name="password_confirma" placeholder="Repite" required></div>
      <button class="btn btn-primary">Actualizar contraseña</button>
    </form>
  </div>
</div>

<!-- Card: Solicitar cambio de correo -->
<div class="card" style="margin-top:20px">
  <h4>Solicitar cambio de correo</h4>
  <?php if ($sol_email): ?>
    <div style="background:#FEF3C7;border-left:4px solid #F59E0B;border-radius:8px;padding:12px 16px;font-size:13px;margin-bottom:16px">
      <strong>Solicitud pendiente</strong> — Estás esperando que el administrador apruebe el cambio a
      <strong><?= h($sol_email['email_nuevo']) ?></strong>.<br>
      <span style="color:#aaa;font-size:11px">Enviada el <?= date('d/m/Y H:i', strtotime($sol_email['fecha_solicitud'])) ?></span>
    </div>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="accion" value="cancelar_cambio_email">
      <button class="btn btn-sm" type="submit"
        style="background:#FEE2E2;color:#991B1B;border:none;cursor:pointer;font-family:inherit;font-weight:600;padding:8px 16px;border-radius:8px">
        Cancelar solicitud
      </button>
    </form>
  <?php else: ?>
    <p style="font-size:13px;color:#6F6F6F;margin-bottom:14px">
      Tu correo actual es <strong><?= h($perfil['email']) ?></strong>.
      Si necesitas cambiarlo, envía una solicitud al administrador.
    </p>
    <form method="post">
      <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
      <input type="hidden" name="accion" value="solicitar_cambio_email">
      <div style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap">
        <div style="flex:1;min-width:220px">
          <label style="font-size:13px;font-weight:600;color:#1F2347;display:block;margin-bottom:6px">Nuevo correo</label>
          <input class="input" type="email" name="nuevo_email" placeholder="nuevo@litoral.edu.co" required>
        </div>
        <button class="btn btn-primary" type="submit" style="white-space:nowrap">Enviar solicitud</button>
      </div>
    </form>
  <?php endif; ?>
</div>

<style>
.perfil-header { display:flex; align-items:center; gap:24px; padding:24px 28px !important }
.perfil-avatar-wrap { width:80px; height:80px; border-radius:50%; overflow:hidden; flex-shrink:0 }
.perfil-avatar { width:80px; height:80px; border-radius:50%; background:linear-gradient(135deg,#5A3DBA,#E84E9F); color:white; font-size:26px; font-weight:800; display:flex; align-items:center; justify-content:center }
.perfil-avatar-img { width:80px; height:80px; border-radius:50%; object-fit:cover; display:block }
.btn-cambiar-foto { background:none; border:none; color:#5A3DBA; font-size:12px; font-weight:600; cursor:pointer; text-decoration:underline; padding:0; font-family:inherit }
.perfil-info { flex:1 }
.perfil-nombre { font-size:18px; font-weight:700; color:#1F2347; margin-bottom:4px }
.perfil-email { font-size:13px; color:#6F6F6F; margin-bottom:8px }
.perfil-stats { display:flex; gap:24px }
.pstat { display:flex; flex-direction:column; align-items:center }
.pstat-val { font-size:22px; font-weight:800; color:#1F2347 }
.pstat-label { font-size:11px; color:#6F6F6F }
.rol-badge { padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600 }
.rol-badge.admin { background:#f0ebff; color:#5A3DBA }
.rol-badge.solicitante { background:#fce7f3; color:#E84E9F }
</style>

<?php include 'footer.php'; ?>

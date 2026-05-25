<?php
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../src/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

if (isset($_SESSION['user_id'])) {
    header('Location: ' . ($_SESSION['rol'] === 'ADMINISTRADOR' ? 'dashboard_admin.php' : 'dashboard_profesor.php'));
    exit;
}

// Proteccion contra fuerza bruta (max 5 intentos en 10 min)
if (!isset($_SESSION['login_intentos'])) $_SESSION['login_intentos'] = 0;
if (!isset($_SESSION['login_tiempo']))   $_SESSION['login_tiempo']   = time();

$bloqueado = false;
$segundos_restantes = 0;
if ($_SESSION['login_intentos'] >= 5) {
    $transcurrido = time() - $_SESSION['login_tiempo'];
    if ($transcurrido < 600) {
        $bloqueado = true;
        $segundos_restantes = 600 - $transcurrido;
    } else {
        $_SESSION['login_intentos'] = 0;
        $_SESSION['login_tiempo']   = time();
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$bloqueado) {
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (empty($email) || empty($pass)) {
        $error = 'Todos los campos son obligatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Correo electrónico no válido.';
    } else {
        $stmt = $pdo->prepare('SELECT profesor_id, password_hash, rol, activo, CONCAT(primer_nombre, " ", primer_apellido) as nombre FROM profesores WHERE email=?');
        $stmt->execute([$email]);
        $u = $stmt->fetch();

        if ($u && password_verify($pass, $u['password_hash'])) {
            if (!$u['activo']) {
                $error = 'Tu cuenta ha sido deshabilitada. Contacta al administrador.';
            } else {
                // Login exitoso - resetear intentos
                $_SESSION['login_intentos'] = 0;
                session_regenerate_id(true);
                $_SESSION['user_id'] = $u['profesor_id'];
                $_SESSION['rol']     = $u['rol'];
                $pdo->prepare("UPDATE profesores SET ultimo_acceso=NOW() WHERE profesor_id=?")->execute([$u['profesor_id']]);

                // Registrar inicio de sesión en el log
                require_once __DIR__ . '/../src/helpers.php';
                $logger = new SesionLogger($pdo);
                $ip = $_SERVER['REMOTE_ADDR'] ?? '';
                $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
                $_SESSION['sesion_log_id'] = $logger->registrarLogin($u['profesor_id'], $ip, $ua);

                header('Location: ' . ($u['rol'] === 'ADMINISTRADOR' ? 'dashboard_admin.php' : 'dashboard_profesor.php'));
                exit;
            }
        } else {
            $_SESSION['login_intentos']++;
            $_SESSION['login_tiempo'] = time();
            $restantes = 5 - $_SESSION['login_intentos'];
            $error = 'Credenciales incorrectas.' . ($restantes > 0 ? " Intentos restantes: {$restantes}." : ' Cuenta bloqueada por 10 minutos.');
        }
    }
}
?>
<!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Iniciar sesión - SEDD</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/global.css">
</head>
<body class="auth-body">
<div class="login-wrapper">
  <div class="login-left">
    <div class="brand">
      <img src="img/logo_login.png" alt="SEDD" class="brand-logo-img">
      <div class="brand-sub">Sistema de Corrección de Notas</div>
    </div>
    <div class="features">
      <div class="feature-item">
        <div class="feature-icon"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2h-2M9 4a2 2 0 012-2h2a2 2 0 012 2v0a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg></div>
        <span>Gestión de solicitudes de corrección</span>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg></div>
        <span>Acceso seguro con protección de cuenta</span>
      </div>
      <div class="feature-item">
        <div class="feature-icon"><svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>
        <span>Aprobación y seguimiento en tiempo real</span>
      </div>
    </div>
    <div class="left-footer">
      <p>Institucion Litoral &copy; <?php echo date('Y'); ?><br>Todos los derechos reservados</p>
    </div>
  </div>

  <div class="login-right">
    <div class="form-title">Bienvenido</div>
    <div class="form-subtitle">Ingresa tus credenciales para continuar</div>

    <?php if ($bloqueado): ?>
      <div class="alert-error" style="background:#fff0f5;border-left:4px solid #EF4444;color:#991b1b;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:20px">
        Cuenta bloqueada. Intenta de nuevo en <?php echo ceil($segundos_restantes/60); ?> minuto(s).
      </div>
    <?php elseif (!empty($error)): ?>
      <div class="alert-error" style="background:#fff0f5;border-left:4px solid #E84E9F;color:#a0174f;padding:12px 14px;border-radius:10px;font-size:13px;margin-bottom:20px;display:flex;align-items:center;gap:8px">
        <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z"/></svg>
        <?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
      <div class="field">
        <label>Correo institucional</label>
        <div class="field-wrap">
          <span class="field-icon"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75"/></svg></span>
          <input type="email" name="email" placeholder="usuario@uni.edu" value="<?php echo htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>" required <?php echo $bloqueado ? 'disabled' : ''; ?> autofocus>
        </div>
      </div>
      <div class="field">
        <label>Contraseña</label>
        <div class="field-wrap">
          <span class="field-icon"><svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg></span>
          <input type="password" name="password" id="passInput" placeholder="Contraseña" required <?php echo $bloqueado ? 'disabled' : ''; ?> style="padding-right:44px">
          <button type="button" tabindex="-1" onclick="var i=document.getElementById('passInput');i.type=i.type==='password'?'text':'password'" style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;padding:4px;line-height:0;color:#9ca3af">
            <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
          </button>
          
        </div>
      </div>
      <?php if (!$bloqueado): ?>
        <div style="font-size:11px;color:#aaa;margin-bottom:12px">
          <?php echo $_SESSION['login_intentos'] > 0 ? 'Intentos fallidos: '.$_SESSION['login_intentos'].'/5' : ''; ?>
        </div>
      <?php endif; ?>
      <button type="submit" class="btn-login" <?php echo $bloqueado ? 'disabled style="opacity:.5;cursor:not-allowed"' : ''; ?>>Iniciar sesión</button>
    </form>

    <div class="contact-note">Soporte: soporte@uni.edu</div>
  </div>
</div>
</body>
</html>

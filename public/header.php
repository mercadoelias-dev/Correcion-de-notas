<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';

if (session_status() === PHP_SESSION_NONE) session_start();

$user = currentUser($pdo);

// Cargar último acceso
$ultimo_acceso = null;
if ($user) {
    $ua = $pdo->prepare("SELECT ultimo_acceso FROM profesores WHERE profesor_id=?");
    $ua->execute([$user['profesor_id']]);
    $ultimo_acceso = $ua->fetchColumn();
}
if ($user) {
    $no_leidas = contarNoLeidas($pdo, $user['profesor_id']);
}
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/global.css">
    <title>SEDD — Corrección de Notas</title>
</head>
<body>
<div class="app">

    <aside class="sidebar">
        <div class="logo">
            <img src="img/logo_login.png" alt="Litoral" class="logo-img">
        </div>

        <nav class="nav">
            <?php if ($user): ?>
                <a href="dashboard_profesor.php">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-9 9 9M4.5 10.5V20a1 1 0 001 1h4v-5h5v5h4a1 1 0 001-1v-9.5"/></svg>
                    Inicio
                </a>
                <a href="crear_solicitud.php">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/></svg>
                    Nueva solicitud
                </a>
                <a href="ver_solicitudes.php">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6M7 4H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V6a2 2 0 00-2-2h-2M9 4a2 2 0 012-2h2a2 2 0 012 2v0a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                    Historial
                </a>

                <!-- Notificaciones -->
                <a href="#" class="notif-trigger" id="notifBtn">
                    <span style="position:relative; display:flex; align-items:center;">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"/></svg>
                        <?php if ($no_leidas > 0): ?>
                            <span class="notif-dot"><?= $no_leidas ?></span>
                        <?php endif; ?>
                    </span>
                    Notificaciones
                </a>

                <a href="perfil.php">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/></svg>
                    Perfil
                </a>

                <?php if ($user['rol'] === 'ADMINISTRADOR'): ?>
                    <div class="nav-divider"></div>
                    <a href="dashboard_admin.php">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25A2.25 2.25 0 0113.5 8.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z"/></svg>
                        Panel Coordinación
                    </a>
                    <a href="admin_usuarios.php">
                        <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z"/></svg>
                        Gestión de Usuarios
                    </a>
                <?php endif; ?>

                <div class="nav-divider"></div>
            <?php else: ?>
                <a href="login.php">
                    <svg width="18" height="18" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9"/></svg>
                    Iniciar sesión
                </a>
            <?php endif; ?>
        </nav>

        <div style="flex:1"></div>

        <?php if ($user): ?>
        <div class="sidebar-user-card">
            <div class="sidebar-user-status">
                <span class="status-dot"></span>
                <span>Sesi&oacute;n activa</span>
            </div>
            <div class="sidebar-user-name"><?= htmlspecialchars($user['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="sidebar-user-email"><?= htmlspecialchars($user['email'], ENT_QUOTES, 'UTF-8') ?></div>
            <span class="sidebar-user-rol"><?= $user['rol'] === 'ADMINISTRADOR' ? 'ADMINISTRADOR' : 'DOCENTE' ?></span>
            <?php if ($ultimo_acceso): ?>
            <div class="sidebar-user-acceso">
                <span>&#218;ltimo acceso:</span><br>
                <span><?= date('Y-m-d H:i', strtotime($ultimo_acceso)) ?></span>
            </div>
            <?php endif; ?>
            <a href="logout.php" class="sidebar-logout-btn">
                <svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75"/></svg>
                Cerrar sesi&oacute;n
            </a>
        </div>
        <?php endif; ?>

        <div class="footer-note">
            Instituci&oacute;n Litoral<br>Sistema de Correcci&oacute;n de Notas
        </div>
    </aside>

    <!-- Panel de notificaciones -->
    <?php if ($user): ?>
    <div class="notif-panel" id="notifPanel">
        <div class="notif-header">
            <span>Notificaciones</span>
            <button class="notif-close" id="notifClose">&times;</button>
        </div>
        <div class="notif-list" id="notifList">
                    <div style="padding:20px; text-align:center; color:#aaa; font-size:13px;">Cargando...</div>
        </div>
    </div>
    <div class="notif-overlay" id="notifOverlay"></div>
    <?php endif; ?>

    <main class="main">

<script>
// Seguridad: si el usuario vuelve con el botón atrás tras cerrar sesión, redirigir al login
window.addEventListener('pageshow', function(e) {
    if (e.persisted) {
        fetch('logout.php?check=1')
            .then(r => r.json())
            .then(data => { if (!data.loggedIn) window.location.replace('login.php'); });
    }
});
</script>
<script>
(function(){
    const btn     = document.getElementById('notifBtn');
    const panel   = document.getElementById('notifPanel');
    const overlay = document.getElementById('notifOverlay');
    const close   = document.getElementById('notifClose');
    const list    = document.getElementById('notifList');
    if (!btn) return;

    function openPanel() {
        panel.classList.add('open');
        overlay.classList.add('open');
        fetch('notificaciones.php?action=list')
            .then(r => r.json())
            .then(data => {
                if (!data.notificaciones.length) {
                    list.innerHTML = '<div class="notif-empty">Sin notificaciones nuevas</div>';
                    return;
                }
                list.innerHTML = data.notificaciones.map(n => `
                    <div class="notif-item ${n.leida == 0 ? 'unread' : ''}" onclick="goNotif('${n.url || '#'}')">
                        <div class="notif-msg">${n.mensaje}</div>
                        <div class="notif-time">${n.fecha}</div>
                    </div>
                `).join('');
                // Marcar como leídas
                fetch('notificaciones.php?action=marcar_leidas');
                // Quitar badge
                const dot = document.querySelector('.notif-dot');
                if (dot) dot.remove();
            });
    }

    function closePanel() {
        panel.classList.remove('open');
        overlay.classList.remove('open');
    }

    btn.addEventListener('click', e => { e.preventDefault(); openPanel(); });
    close.addEventListener('click', closePanel);
    overlay.addEventListener('click', closePanel);
})();

function goNotif(url) {
    if (url && url !== '#') window.location.href = url;
}
</script>

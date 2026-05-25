<?php
require_once __DIR__ . '/../src/auth.php';
require_once __DIR__ . '/../src/helpers.php';
requireLogin();

$user = currentUser($pdo);
require_once __DIR__ . '/../src/solicitudes.php';

// Array con todas las solicitudes del profesor
$mis = getSolicitudesPorProfesor($pdo, $user['profesor_id']);

// Estadísticas calculadas desde el array
$stats = [
    'total'     => count($mis),
    'pendiente' => 0,
    'aprobada'  => 0,
    'rechazada' => 0,
];

foreach ($mis as $s) {
    $estado = strtolower($s['estado']);
    if (isset($stats[$estado])) {
        $stats[$estado]++;
    }
}

// Array de tarjetas de resumen con iconos
$tarjetas = [
    [
        'label' => 'Total Solicitudes',
        'valor' => $stats['total'],
        'color' => '#5A3DBA',
        'bg' => 'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>'
    ],
    [
        'label' => 'Pendientes',
        'valor' => $stats['pendiente'],
        'color' => '#F59E0B',
        'bg' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>'
    ],
    [
        'label' => 'Aprobadas',
        'valor' => $stats['aprobada'],
        'color' => '#10B981',
        'bg' => 'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
    ],
    [
        'label' => 'Rechazadas',
        'valor' => $stats['rechazada'],
        'color' => '#EF4444',
        'bg' => 'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
    ],
];

include 'header.php';
?>

<style>
.welcome-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 32px 40px;
    border-radius: 20px;
    margin-bottom: 32px;
    color: white;
    box-shadow: 0 20px 60px rgba(102, 126, 234, 0.3);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 20px;
}

.welcome-content h1 {
    margin: 0 0 8px 0;
    font-size: 32px;
    font-weight: 700;
    display: flex;
    align-items: center;
    gap: 12px;
}

.welcome-emoji {
    font-size: 36px;
    animation: wave 2s ease-in-out infinite;
}

@keyframes wave {
    0%, 100% { transform: rotate(0deg); }
    25% { transform: rotate(20deg); }
    75% { transform: rotate(-20deg); }
}

.welcome-content p {
    margin: 0;
    opacity: 0.95;
    font-size: 16px;
    font-weight: 500;
}

.welcome-actions {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
}

.btn-hero {
    padding: 12px 28px;
    background: white;
    color: #667eea;
    border: 2px solid white;
    border-radius: 12px;
    font-weight: 700;
    font-size: 15px;
    cursor: pointer;
    transition: all 0.2s;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 8px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
}

.btn-hero:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0,0,0,0.15);
}

.btn-hero-outline {
    background: transparent;
    color: white;
    border: 2px solid rgba(255,255,255,0.5);
}

.btn-hero-outline:hover {
    background: rgba(255,255,255,0.1);
    border-color: white;
}

.stats-grid-modern {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    margin-bottom: 32px;
}

.stat-card-modern {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.stat-card-modern:hover {
    transform: translateY(-5px);
    box-shadow: 0 12px 40px rgba(0,0,0,0.15);
}

.stat-card-modern::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: var(--card-gradient);
}

.stat-card-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 16px;
}

.stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: var(--card-gradient);
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 8px 16px rgba(0,0,0,0.1);
}

.stat-valor-modern {
    font-size: 36px;
    font-weight: 700;
    color: #1F2347;
    line-height: 1;
}

.stat-label-modern {
    font-size: 14px;
    color: #6B7280;
    font-weight: 500;
    margin-top: 8px;
}

@media (max-width: 768px) {
    .welcome-hero {
        padding: 24px;
    }
    
    .welcome-content h1 {
        font-size: 24px;
    }
    
    .stats-grid-modern {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="welcome-hero">
    <div class="welcome-content">
        <h1>
            <span class="welcome-emoji">👋</span>
            ¡Hola, <?= h($user['nombre']) ?>!
        </h1>
        <p>Bienvenido a tu panel de solicitudes de corrección de notas</p>
    </div>
    <div class="welcome-actions">
        <a class="btn-hero" href="crear_solicitud.php">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Solicitud
        </a>
        <a class="btn-hero btn-hero-outline" href="ver_solicitudes.php">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Ver Todas
        </a>
    </div>
</div>

<!-- Tarjetas de estadísticas mejoradas -->
<div class="stats-grid-modern">
    <?php foreach ($tarjetas as $t): ?>
    <div class="stat-card-modern" style="--card-gradient: <?= $t['bg'] ?>">
        <div class="stat-card-header">
            <div>
                <div class="stat-valor-modern"><?= $t['valor'] ?></div>
                <div class="stat-label-modern"><?= $t['label'] ?></div>
            </div>
            <div class="stat-icon">
                <svg width="24" height="24" fill="none" viewBox="0 0 24 24" stroke="white" stroke-width="2">
                    <?= $t['icon'] ?>
                </svg>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Últimas solicitudes -->
<div class="card">
    <h4>Últimas solicitudes</h4>
    <?php if (empty($mis)): ?>
        <p style="color:var(--muted); font-size:14px;">Aún no tienes solicitudes. <a href="crear_solicitud.php">Crear una</a>.</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr><th>ID</th><th>Asignatura</th><th>Estudiante</th><th>Solicitante</th><th>Nota actual</th><th>Nota propuesta</th><th>Estado</th><th>Fecha de envío</th><th></th></tr>
        </thead>
        <tbody>
            <?php foreach (array_slice($mis, 0, 8) as $s): ?>
            <?php
                $badge_color = match($s['estado']) {
                    'APROBADA'  => '#10B981',
                    'RECHAZADA' => '#EF4444',
                    default     => '#F59E0B',
                };
            ?>
            <tr>
                <td><?= $s['solicitud_id'] ?></td>
                <td><?= h($s['asignatura_nombre']) ?></td>
                <td><?= h($s['estudiante_nombre']) ?></td>
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
                <td><span style="background:<?= $badge_color ?>22; color:<?= $badge_color ?>; padding:3px 10px; border-radius:20px; font-size:12px; font-weight:600;"><?= h($s['estado']) ?></span></td>
                <td><?= h($s['fecha_envio']) ?></td>
                <td><a class="btn btn-secondary" href="solicitud_detalle.php?id=<?= $s['solicitud_id'] ?>">Ver</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>

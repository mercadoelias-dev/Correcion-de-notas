<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * PRUEBA DE CONFIGURACIÓN DE EMAIL
 * ══════════════════════════════════════════════════════════════════════════════
 * 
 * Este archivo te permite probar si tu configuración SMTP está funcionando.
 * 
 * INSTRUCCIONES:
 * 1. Configura src/config_email.php con tus credenciales SMTP
 * 2. Ejecuta: composer install (para instalar PHPMailer)
 * 3. Accede a: http://localhost/correccion/public/test_email.php
 * 4. Ingresa tu email y haz clic en "Enviar correo de prueba"
 * 
 * ══════════════════════════════════════════════════════════════════════════════
 */

require_once __DIR__ . '/../src/db.php';

// Cargar autoload de Composer
$autoloadPath = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoloadPath)) {
    require_once $autoloadPath;
} else {
    die('❌ PHPMailer no está instalado. Ejecuta: composer install');
}

require_once __DIR__ . '/../src/mailer_smtp.php';

$resultado = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
    try {
        $mailer = new Mailer($pdo);
        $resultado = $mailer->probarConexion($_POST['email']);
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prueba de Email — SEDD</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 600px;
            width: 100%;
            padding: 40px;
        }
        
        h1 {
            color: #1F2347;
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .subtitle {
            color: #6B7280;
            font-size: 14px;
            margin-bottom: 32px;
        }
        
        .form-group {
            margin-bottom: 24px;
        }
        
        label {
            display: block;
            font-weight: 600;
            color: #1F2347;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        input[type="email"] {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #E5E7EB;
            border-radius: 10px;
            font-size: 15px;
            font-family: inherit;
            transition: all 0.2s;
        }
        
        input[type="email"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .btn {
            width: 100%;
            padding: 14px 24px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }
        
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 24px;
            font-size: 14px;
            line-height: 1.6;
        }
        
        .alert-success {
            background: #D1FAE5;
            color: #065F46;
            border-left: 4px solid #10B981;
        }
        
        .alert-error {
            background: #FEE2E2;
            color: #991B1B;
            border-left: 4px solid #EF4444;
        }
        
        .alert-info {
            background: #DBEAFE;
            color: #1E40AF;
            border-left: 4px solid #3B82F6;
        }
        
        .config-info {
            background: #F9FAFB;
            border: 2px solid #E5E7EB;
            border-radius: 12px;
            padding: 16px;
            margin-top: 24px;
            font-size: 13px;
        }
        
        .config-info h3 {
            font-size: 14px;
            color: #1F2347;
            margin-bottom: 12px;
        }
        
        .config-info p {
            color: #6B7280;
            margin: 4px 0;
        }
        
        .config-info strong {
            color: #1F2347;
        }
        
        .icon {
            font-size: 32px;
            margin-bottom: 16px;
        }
        
        .back-link {
            display: inline-block;
            margin-top: 20px;
            color: #667eea;
            text-decoration: none;
            font-size: 14px;
            font-weight: 600;
        }
        
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="icon">📧</div>
        <h1>Prueba de Email SMTP</h1>
        <p class="subtitle">Verifica que tu configuración de correo esté funcionando correctamente</p>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <strong>❌ Error:</strong><br>
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($resultado): ?>
            <?php if ($resultado['success']): ?>
                <div class="alert alert-success">
                    <strong>✅ ¡Éxito!</strong><br>
                    <?= htmlspecialchars($resultado['message']) ?><br>
                    <small>Revisa tu bandeja de entrada (y spam) para ver el correo de prueba.</small>
                </div>
            <?php else: ?>
                <div class="alert alert-error">
                    <strong>❌ Error al enviar:</strong><br>
                    <?= htmlspecialchars($resultado['message']) ?>
                </div>
            <?php endif; ?>
            
            <div class="config-info">
                <h3>📋 Configuración utilizada:</h3>
                <p><strong>Servidor:</strong> <?= htmlspecialchars($resultado['config']['host']) ?></p>
                <p><strong>Puerto:</strong> <?= htmlspecialchars($resultado['config']['port']) ?></p>
                <p><strong>Encriptación:</strong> <?= htmlspecialchars($resultado['config']['encryption']) ?></p>
            </div>
        <?php else: ?>
            <div class="alert alert-info">
                <strong>ℹ️ Instrucciones:</strong><br>
                1. Asegúrate de haber configurado <code>src/config_email.php</code><br>
                2. Ejecuta <code>composer install</code> en la raíz del proyecto<br>
                3. Ingresa tu email y haz clic en "Enviar correo de prueba"
            </div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="email">Tu correo electrónico</label>
                <input 
                    type="email" 
                    id="email" 
                    name="email" 
                    placeholder="ejemplo@gmail.com" 
                    required
                    value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                >
            </div>
            
            <button type="submit" class="btn">
                📨 Enviar correo de prueba
            </button>
        </form>
        
        <a href="login.php" class="back-link">← Volver al sistema</a>
    </div>
</body>
</html>

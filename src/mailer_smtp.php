<?php
/**
 * ══════════════════════════════════════════════════════════════════════════════
 * Mailer — Servicio de correo electrónico 100% FUNCIONAL con SMTP
 * ══════════════════════════════════════════════════════════════════════════════
 * 
 * Usa PHPMailer con SMTP para envío confiable de correos.
 * Configuración en: src/config_email.php
 * 
 * INSTALACIÓN:
 * 1. Ejecuta: composer install
 * 2. Configura src/config_email.php con tus credenciales SMTP
 * 
 * ══════════════════════════════════════════════════════════════════════════════
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class Mailer {

    private PDO $pdo;
    private array $config;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
        
        // Cargar configuración
        $configFile = __DIR__ . '/config_email.php';
        if (!file_exists($configFile)) {
            throw new Exception('Archivo de configuración de email no encontrado: ' . $configFile);
        }
        $this->config = require $configFile;
    }

    // ── Métodos públicos ──────────────────────────────────────────────────────

    /**
     * Notifica a todos los administradores que llegó una nueva solicitud.
     */
    public function notificarNuevaSolicitud(array $solicitud, string $solicitante_nombre, string $solicitante_email): void {
        try {
            $admins = $this->pdo->query(
                "SELECT email, CONCAT(primer_nombre,' ',primer_apellido) AS nombre
                 FROM profesores WHERE rol='ADMINISTRADOR' AND activo=1"
            )->fetchAll();

            foreach ($admins as $admin) {
                $asunto = "Nueva solicitud #{$solicitud['solicitud_id']} — {$solicitud['asignatura_nombre']}";
                $cuerpo = $this->plantillaNuevaSolicitud($solicitud, $solicitante_nombre, $admin['nombre']);
                $this->enviar($admin['email'], $admin['nombre'], $asunto, $cuerpo);
            }
        } catch (\Throwable $e) {
            $this->logError('notificarNuevaSolicitud', $e->getMessage());
        }
    }

    /**
     * Notifica al solicitante que su solicitud fue aprobada o rechazada.
     */
    public function notificarDecision(array $solicitud, string $decision, string $comentario): void {
        try {
            if (empty($solicitud['profesor_solicitante_id'])) return;

            $prof = $this->pdo->prepare(
                "SELECT email, CONCAT(primer_nombre,' ',primer_apellido) AS nombre
                 FROM profesores WHERE profesor_id=?"
            );
            $prof->execute([$solicitud['profesor_solicitante_id']]);
            $destinatario = $prof->fetch();
            if (!$destinatario) return;

            $estado  = strtoupper($decision) === 'APROBADA' ? 'APROBADA ✅' : 'RECHAZADA ❌';
            $asunto  = "Tu solicitud #{$solicitud['solicitud_id']} fue {$estado}";
            $cuerpo  = $this->plantillaDecision($solicitud, $decision, $comentario, $destinatario['nombre']);
            $this->enviar($destinatario['email'], $destinatario['nombre'], $asunto, $cuerpo);
        } catch (\Throwable $e) {
            $this->logError('notificarDecision', $e->getMessage());
        }
    }

    /**
     * Método genérico para enviar cualquier correo personalizado
     */
    public function enviarCorreo(string $destinatario_email, string $destinatario_nombre, string $asunto, string $mensaje_html): bool {
        try {
            return $this->enviar($destinatario_email, $destinatario_nombre, $asunto, $mensaje_html);
        } catch (\Throwable $e) {
            $this->logError('enviarCorreo', $e->getMessage());
            return false;
        }
    }

    /**
     * Prueba la configuración SMTP enviando un correo de prueba
     */
    public function probarConexion(string $email_destino): array {
        try {
            $asunto = "Prueba de configuración SMTP — SEDD";
            $mensaje = $this->envoltura("Prueba exitosa", "
                <p style='margin:0 0 16px'>¡Felicitaciones! 🎉</p>
                <p style='margin:0 0 16px'>La configuración SMTP está funcionando correctamente.</p>
                <div style='background:#10B98115;border-left:4px solid #10B981;border-radius:8px;padding:16px 20px;margin:20px 0'>
                    <div style='font-size:16px;font-weight:700;color:#10B981;margin-bottom:8px'>✅ Conexión exitosa</div>
                    <div style='font-size:13px;color:#6F6F6F'>
                        Servidor: {$this->config['smtp']['host']}<br>
                        Puerto: {$this->config['smtp']['port']}<br>
                        Encriptación: {$this->config['smtp']['encryption']}
                    </div>
                </div>
                <p style='margin:16px 0 0;font-size:13px;color:#6F6F6F'>
                    Este es un correo de prueba del sistema SEDD. Tu configuración de email está lista para usar.
                </p>
            ");
            
            $resultado = $this->enviar($email_destino, 'Usuario de prueba', $asunto, $mensaje);
            
            return [
                'success' => $resultado,
                'message' => $resultado ? 'Correo de prueba enviado exitosamente' : 'Error al enviar correo de prueba',
                'config' => [
                    'host' => $this->config['smtp']['host'],
                    'port' => $this->config['smtp']['port'],
                    'encryption' => $this->config['smtp']['encryption'],
                ]
            ];
        } catch (\Throwable $e) {
            return [
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
                'config' => [
                    'host' => $this->config['smtp']['host'],
                    'port' => $this->config['smtp']['port'],
                    'encryption' => $this->config['smtp']['encryption'],
                ]
            ];
        }
    }

    // ── Envío con PHPMailer ───────────────────────────────────────────────────

    /**
     * Envía el correo usando PHPMailer con SMTP
     */
    private function enviar(string $to_email, string $to_name, string $asunto, string $html): bool {
        // Verificar si PHPMailer está instalado
        if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            $this->logError('enviar', 'PHPMailer no está instalado. Ejecuta: composer install');
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host       = $this->config['smtp']['host'];
            $mail->SMTPAuth   = $this->config['smtp']['auth'];
            $mail->Username   = $this->config['smtp']['username'];
            $mail->Password   = $this->config['smtp']['password'];
            $mail->SMTPSecure = $this->config['smtp']['encryption'];
            $mail->Port       = $this->config['smtp']['port'];
            $mail->CharSet    = $this->config['options']['charset'];
            $mail->Timeout    = $this->config['options']['timeout'];

            // Debug (solo si está habilitado en config)
            if ($this->config['options']['debug'] > 0) {
                $mail->SMTPDebug = $this->config['options']['debug'];
                $mail->Debugoutput = function($str, $level) {
                    $this->logError('SMTP Debug', $str);
                };
            }

            // Remitente
            $mail->setFrom(
                $this->config['from']['email'],
                $this->config['from']['name']
            );

            // Destinatario
            $mail->addAddress($to_email, $to_name);

            // Contenido
            $mail->isHTML(true);
            $mail->Subject = $asunto;
            $mail->Body    = $html;
            $mail->AltBody = strip_tags($html); // Versión texto plano

            // Enviar
            $resultado = $mail->send();
            
            if ($resultado) {
                $this->logSuccess($to_email, $asunto);
            }
            
            return $resultado;

        } catch (Exception $e) {
            $this->logError('enviar', "Error al enviar a {$to_email}: {$mail->ErrorInfo}");
            return false;
        }
    }

    // ── Plantillas HTML ───────────────────────────────────────────────────────

    private function plantillaNuevaSolicitud(array $sol, string $solicitante, string $admin_nombre): string {
        $id       = (int)$sol['solicitud_id'];
        $asig     = htmlspecialchars($sol['asignatura_nombre'], ENT_QUOTES);
        $tipo     = htmlspecialchars($sol['tipo_solicitud'], ENT_QUOTES);
        $fecha    = date('d/m/Y H:i', strtotime($sol['fecha_envio']));
        $url      = $this->config['app']['url'] . "/solicitud_detalle.php?id={$id}";
        $sol_name = htmlspecialchars($solicitante, ENT_QUOTES);
        $adm_name = htmlspecialchars($admin_nombre, ENT_QUOTES);

        return $this->envoltura("Nueva solicitud recibida", "
            <p style='margin:0 0 16px'>Hola <strong>{$adm_name}</strong>,</p>
            <p style='margin:0 0 16px'>Se ha recibido una nueva solicitud que requiere tu revisión:</p>

            <table style='width:100%;border-collapse:collapse;font-size:14px;margin-bottom:20px'>
                <tr><td style='padding:8px 12px;background:#f8f7ff;font-weight:600;width:40%'>N° Solicitud</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee'>#{$id}</td></tr>
                <tr><td style='padding:8px 12px;background:#f8f7ff;font-weight:600'>Solicitante</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee'>{$sol_name}</td></tr>
                <tr><td style='padding:8px 12px;background:#f8f7ff;font-weight:600'>Asignatura</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee'>{$asig}</td></tr>
                <tr><td style='padding:8px 12px;background:#f8f7ff;font-weight:600'>Tipo</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee'>{$tipo}</td></tr>
                <tr><td style='padding:8px 12px;background:#f8f7ff;font-weight:600'>Fecha de envío</td>
                    <td style='padding:8px 12px'>{$fecha}</td></tr>
            </table>

            <div style='text-align:center;margin:24px 0'>
                <a href='{$url}' style='background:#5A3DBA;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;display:inline-block'>
                    Revisar solicitud
                </a>
            </div>
        ");
    }

    private function plantillaDecision(array $sol, string $decision, string $comentario, string $prof_nombre): string {
        $id       = (int)$sol['solicitud_id'];
        $asig     = htmlspecialchars($sol['asignatura_nombre'], ENT_QUOTES);
        $aprobada = strtoupper($decision) === 'APROBADA';
        $color    = $aprobada ? '#10B981' : '#EF4444';
        $icono    = $aprobada ? '✅' : '❌';
        $texto    = $aprobada ? 'APROBADA' : 'RECHAZADA';
        $url      = $this->config['app']['url'] . "/solicitud_detalle.php?id={$id}";
        $nombre   = htmlspecialchars($prof_nombre, ENT_QUOTES);
        $coment   = htmlspecialchars($comentario ?: 'Sin comentarios adicionales.', ENT_QUOTES);

        return $this->envoltura("Solicitud #{$id} — {$texto}", "
            <p style='margin:0 0 16px'>Hola <strong>{$nombre}</strong>,</p>
            <p style='margin:0 0 20px'>Tu solicitud ha sido revisada por el administrador:</p>

            <div style='background:{$color}15;border-left:4px solid {$color};border-radius:8px;padding:16px 20px;margin-bottom:20px;text-align:center'>
                <div style='font-size:28px;margin-bottom:6px'>{$icono}</div>
                <div style='font-size:18px;font-weight:700;color:{$color}'>Solicitud {$texto}</div>
                <div style='font-size:13px;color:#6F6F6F;margin-top:4px'>#{$id} — {$asig}</div>
            </div>

            <table style='width:100%;border-collapse:collapse;font-size:14px;margin-bottom:20px'>
                <tr><td style='padding:8px 12px;background:#f8f7ff;font-weight:600;width:40%'>N° Solicitud</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee'>#{$id}</td></tr>
                <tr><td style='padding:8px 12px;background:#f8f7ff;font-weight:600'>Asignatura</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee'>{$asig}</td></tr>
                <tr><td style='padding:8px 12px;background:#f8f7ff;font-weight:600'>Estado</td>
                    <td style='padding:8px 12px;border-bottom:1px solid #eee;color:{$color};font-weight:700'>{$texto}</td></tr>
                <tr><td style='padding:8px 12px;background:#f8f7ff;font-weight:600'>Comentario</td>
                    <td style='padding:8px 12px'>{$coment}</td></tr>
            </table>

            <div style='text-align:center;margin:24px 0'>
                <a href='{$url}' style='background:#5A3DBA;color:white;padding:12px 28px;border-radius:8px;text-decoration:none;font-weight:600;font-size:14px;display:inline-block'>
                    Ver detalle de la solicitud
                </a>
            </div>
        ");
    }

    /**
     * Envuelve el contenido en una plantilla HTML base con el estilo de la institución.
     */
    private function envoltura(string $titulo, string $contenido): string {
        $year = date('Y');
        $appName = $this->config['app']['name'];
        return <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>{$titulo}</title>
</head>
<body style="margin:0;padding:0;background:#F4F5FB;font-family:'Segoe UI',Arial,sans-serif">
  <table width="100%" cellpadding="0" cellspacing="0" style="background:#F4F5FB;padding:32px 0">
    <tr><td align="center">
      <table width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%">

        <!-- Header -->
        <tr>
          <td style="background:linear-gradient(135deg,#1F2347,#5A3DBA);border-radius:14px 14px 0 0;padding:28px 32px;text-align:center">
            <div style="color:white;font-size:22px;font-weight:800;letter-spacing:-0.5px">{$appName}</div>
            <div style="color:rgba(255,255,255,.7);font-size:12px;margin-top:4px">Sistema de Corrección de Notas — Institución Litoral</div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="background:white;padding:32px;border-radius:0 0 14px 14px;color:#1F2347;font-size:14px;line-height:1.6">
            {$contenido}
            <hr style="border:none;border-top:1px solid #eee;margin:24px 0">
            <p style="font-size:12px;color:#aaa;margin:0">
              Este es un mensaje automático del sistema {$appName}. Por favor no respondas a este correo.<br>
              Si tienes dudas, contacta a soporte: soporte@litoral.edu.co
            </p>
          </td>
        </tr>

        <!-- Footer -->
        <tr>
          <td style="text-align:center;padding:16px;font-size:11px;color:#aaa">
            &copy; {$year} Institución Litoral. Todos los derechos reservados.
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body>
</html>
HTML;
    }

    // ── Logging ───────────────────────────────────────────────────────────────

    private function logError(string $metodo, string $mensaje): void {
        if ($this->config['options']['log_errors']) {
            error_log("[Mailer::{$metodo}] ERROR: {$mensaje}");
        }
    }

    private function logSuccess(string $destinatario, string $asunto): void {
        if ($this->config['options']['log_errors']) {
            error_log("[Mailer] ✓ Enviado a {$destinatario} — {$asunto}");
        }
    }
}

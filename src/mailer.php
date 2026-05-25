<?php
/**
 * Mailer — Servicio de correo electrónico (POO)
 *
 * Usa la función mail() nativa de PHP para funcionar en XAMPP sin
 * dependencias externas. Para producción, cambia el método send()
 * por SMTP (ver comentarios al final del archivo).
 *
 * Configuración SMTP: edita las constantes de la sección CONFIG.
 */

class Mailer {

    // ── CONFIG ────────────────────────────────────────────────────────────────
    private const FROM_EMAIL = 'noreply@litoral.edu.co';
    private const FROM_NAME  = 'SEDD — Institución Litoral';
    private const APP_URL    = 'http://localhost/correcion/public';
    // ─────────────────────────────────────────────────────────────────────────

    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
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
            // No interrumpir el flujo si falla el correo
            error_log('[Mailer] notificarNuevaSolicitud: ' . $e->getMessage());
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
            error_log('[Mailer] notificarDecision: ' . $e->getMessage());
        }
    }

    // ── Envío ─────────────────────────────────────────────────────────────────

    /**
     * Envía el correo usando mail() nativo de PHP.
     * En XAMPP: habilita sendmail en php.ini o usa un servidor SMTP externo.
     */
    private function enviar(string $to_email, string $to_name, string $asunto, string $html): void {
        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: " . self::FROM_NAME . " <" . self::FROM_EMAIL . ">\r\n";
        $headers .= "Reply-To: " . self::FROM_EMAIL . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $to = "{$to_name} <{$to_email}>";

        $resultado = mail($to, '=?UTF-8?B?' . base64_encode($asunto) . '?=', $html, $headers);

        if (!$resultado) {
            error_log("[Mailer] Fallo al enviar a {$to_email} — asunto: {$asunto}");
        }
    }

    // ── Plantillas HTML ───────────────────────────────────────────────────────

    private function plantillaNuevaSolicitud(array $sol, string $solicitante, string $admin_nombre): string {
        $id       = (int)$sol['solicitud_id'];
        $asig     = htmlspecialchars($sol['asignatura_nombre'], ENT_QUOTES);
        $tipo     = htmlspecialchars($sol['tipo_solicitud'], ENT_QUOTES);
        $fecha    = date('d/m/Y H:i', strtotime($sol['fecha_envio']));
        $url      = self::APP_URL . "/solicitud_detalle.php?id={$id}";
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
        $url      = self::APP_URL . "/solicitud_detalle.php?id={$id}";
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
            <div style="color:white;font-size:22px;font-weight:800;letter-spacing:-0.5px">SEDD</div>
            <div style="color:rgba(255,255,255,.7);font-size:12px;margin-top:4px">Sistema de Corrección de Notas — Institución Litoral</div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style="background:white;padding:32px;border-radius:0 0 14px 14px;color:#1F2347;font-size:14px;line-height:1.6">
            {$contenido}
            <hr style="border:none;border-top:1px solid #eee;margin:24px 0">
            <p style="font-size:12px;color:#aaa;margin:0">
              Este es un mensaje automático del sistema SEDD. Por favor no respondas a este correo.<br>
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
}

/*
 * ══════════════════════════════════════════════════════════════════════════════
 * CONFIGURACIÓN SMTP (para producción o si mail() no funciona en XAMPP)
 * ══════════════════════════════════════════════════════════════════════════════
 *
 * 1. Instala PHPMailer:
 *    composer require phpmailer/phpmailer
 *
 * 2. Reemplaza el método enviar() con:
 *
 *    use PHPMailer\PHPMailer\PHPMailer;
 *    use PHPMailer\PHPMailer\SMTP;
 *
 *    private function enviar(string $to_email, string $to_name, string $asunto, string $html): void {
 *        $mail = new PHPMailer(true);
 *        try {
 *            $mail->isSMTP();
 *            $mail->Host       = 'smtp.gmail.com';   // o tu servidor SMTP
 *            $mail->SMTPAuth   = true;
 *            $mail->Username   = 'tu@gmail.com';
 *            $mail->Password   = 'tu_app_password';
 *            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
 *            $mail->Port       = 587;
 *            $mail->CharSet    = 'UTF-8';
 *            $mail->setFrom(self::FROM_EMAIL, self::FROM_NAME);
 *            $mail->addAddress($to_email, $to_name);
 *            $mail->isHTML(true);
 *            $mail->Subject = $asunto;
 *            $mail->Body    = $html;
 *            $mail->send();
 *        } catch (\Exception $e) {
 *            error_log('[Mailer SMTP] ' . $mail->ErrorInfo);
 *        }
 *    }
 * ══════════════════════════════════════════════════════════════════════════════
 */

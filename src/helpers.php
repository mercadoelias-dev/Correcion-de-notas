<?php
function h($s){ return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function flash($msg){ if(!isset($_SESSION)) session_start(); $_SESSION['flash']=$msg; }
function get_flash(){ if(!isset($_SESSION)) session_start(); $m = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $m; }

// ── Gestión de Sesiones (POO) ─────────────────────────────────────────────────

class SesionLogger {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    /**
     * Registra el inicio de sesión de un usuario.
     * Devuelve el ID del registro creado.
     */
    public function registrarLogin(int $usuario_id, string $ip = '', string $user_agent = ''): int {
        try {
            $stmt = $this->pdo->prepare(
                "INSERT INTO sesiones_log (usuario_id, ip_address, user_agent, fecha_login)
                 VALUES (?, ?, ?, NOW())"
            );
            $stmt->execute([$usuario_id, $ip, $user_agent]);
            return (int)$this->pdo->lastInsertId();
        } catch (\PDOException $e) {
            // No interrumpir el flujo si falla el log
            return 0;
        }
    }

    /**
     * Registra el cierre de sesión actualizando el registro abierto más reciente.
     */
    public function registrarLogout(int $usuario_id): void {
        try {
            $stmt = $this->pdo->prepare(
                "UPDATE sesiones_log
                 SET fecha_logout = NOW(),
                     duracion_segundos = TIMESTAMPDIFF(SECOND, fecha_login, NOW())
                 WHERE usuario_id = ? AND fecha_logout IS NULL
                 ORDER BY sesion_id DESC
                 LIMIT 1"
            );
            $stmt->execute([$usuario_id]);
        } catch (\PDOException $e) {
            // Silencioso
        }
    }

    /**
     * Obtiene el historial de sesiones con filtros opcionales.
     */
    public function obtenerHistorial(int $usuario_id = 0, int $limite = 100): array {
        try {
            if ($usuario_id > 0) {
                $stmt = $this->pdo->prepare(
                    "SELECT sl.*, CONCAT(p.primer_nombre,' ',p.primer_apellido) AS nombre, p.email, p.rol
                     FROM sesiones_log sl
                     JOIN profesores p ON p.profesor_id = sl.usuario_id
                     WHERE sl.usuario_id = ?
                     ORDER BY sl.fecha_login DESC
                     LIMIT ?"
                );
                $stmt->execute([$usuario_id, $limite]);
            } else {
                $stmt = $this->pdo->prepare(
                    "SELECT sl.*, CONCAT(p.primer_nombre,' ',p.primer_apellido) AS nombre, p.email, p.rol
                     FROM sesiones_log sl
                     JOIN profesores p ON p.profesor_id = sl.usuario_id
                     ORDER BY sl.fecha_login DESC
                     LIMIT ?"
                );
                $stmt->execute([$limite]);
            }
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }

    /**
     * Resumen de actividad por usuario (último acceso, total sesiones).
     */
    public function resumenPorUsuario(): array {
        try {
            $stmt = $this->pdo->query(
                "SELECT sl.usuario_id,
                        CONCAT(p.primer_nombre,' ',p.primer_apellido) AS nombre,
                        p.email, p.rol,
                        COUNT(sl.sesion_id) AS total_sesiones,
                        MAX(sl.fecha_login) AS ultimo_login,
                        MAX(sl.fecha_logout) AS ultimo_logout
                 FROM sesiones_log sl
                 JOIN profesores p ON p.profesor_id = sl.usuario_id
                 GROUP BY sl.usuario_id
                 ORDER BY ultimo_login DESC"
            );
            return $stmt->fetchAll();
        } catch (\PDOException $e) {
            return [];
        }
    }
}

// Generar token CSRF
function csrf_token() {
    if(!isset($_SESSION)) session_start();
    if(!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Validar token CSRF
function csrf_verify() {
    if(!isset($_SESSION)) session_start();
    $token = $_POST['csrf_token'] ?? '';
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Campo CSRF para formularios
function csrf_field() {
    return '<input type="hidden" name="csrf_token" value="'.csrf_token().'">';
}

// ── Notificaciones ────────────────────────────────────────────────────────────

function crearNotificacion($pdo, $usuario_id, $mensaje, $url = null) {
    $stmt = $pdo->prepare("INSERT INTO notificaciones (usuario_id, mensaje, url) VALUES (?,?,?)");
    $stmt->execute([$usuario_id, $mensaje, $url]);
}

function getNotificaciones($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? ORDER BY fecha DESC LIMIT 20");
    $stmt->execute([$usuario_id]);
    return $stmt->fetchAll();
}

function contarNoLeidas($pdo, $usuario_id) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notificaciones WHERE usuario_id = ? AND leida = 0");
    $stmt->execute([$usuario_id]);
    return (int)$stmt->fetchColumn();
}

function marcarTodasLeidas($pdo, $usuario_id) {
    $stmt = $pdo->prepare("UPDATE notificaciones SET leida = 1 WHERE usuario_id = ?");
    $stmt->execute([$usuario_id]);
}


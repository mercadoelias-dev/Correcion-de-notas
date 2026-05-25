<?php
require_once __DIR__.'/db.php';

function crearSolicitud($pdo, $profesor_id, $estudiante_id, $asignatura_id, $data) {
    $stmt = $pdo->prepare("
        INSERT INTO solicitudes (
            profesor_solicitante_id, estudiante_id, estudiante_documento, estudiante_semestre,
            asignatura_id, periodo_academico, tipo_solicitud, corte, motivo,
            nota_inicial_formativa, nota_inicial_aplicativa, nota_inicial_cognitiva, nota_inicial_letras,
            nota_corregida_formativa, nota_corregida_aplicativa, nota_corregida_cognitiva, nota_corregida_letras,
            justificacion_solicitante
        ) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
    ");
    $stmt->execute([
        $profesor_id, $estudiante_id,
        $data['estudiante_documento'] ?? null,
        $data['estudiante_semestre'] ?? null,
        $asignatura_id,
        $data['periodo_academico'],
        $data['tipo_solicitud'],
        $data['corte'],
        $data['motivo'] ?? null,
        $data['nota_inicial_formativa'] ?? null,
        $data['nota_inicial_aplicativa'] ?? null,
        $data['nota_inicial_cognitiva'] ?? null,
        $data['nota_inicial_letras'] ?? null,
        $data['nota_corregida_formativa'] ?? null,
        $data['nota_corregida_aplicativa'] ?? null,
        $data['nota_corregida_cognitiva'] ?? null,
        $data['nota_corregida_letras'] ?? null,
        $data['motivo'] ?? null,
    ]);
    return $pdo->lastInsertId();
}

function getSolicitudesPorProfesor($pdo, $profesor_id) {
    $stmt = $pdo->prepare("
        SELECT s.*,
               a.nombre as asignatura_nombre, a.codigo as asignatura_codigo,
               CONCAT(e.primer_nombre, ' ', e.primer_apellido) as estudiante_nombre,
               c.nombre as carrera_nombre,
               CONCAT(p.primer_nombre, ' ', p.primer_apellido) as solicitante_nombre
        FROM solicitudes s
        JOIN asignaturas a ON a.asignatura_id = s.asignatura_id
        JOIN estudiantes e ON e.estudiante_id = s.estudiante_id
        LEFT JOIN carreras c ON c.carrera_id = e.carrera_id
        LEFT JOIN profesores p ON p.profesor_id = s.profesor_solicitante_id
        WHERE s.profesor_solicitante_id = ?
        ORDER BY s.fecha_envio DESC
    ");
    $stmt->execute([$profesor_id]);
    return $stmt->fetchAll();
}

function getSolicitud($pdo, $id) {
    $stmt = $pdo->prepare("
        SELECT s.*,
               CONCAT(p.primer_nombre, ' ', p.primer_apellido) as solicitante_nombre,
               a.nombre as asignatura_nombre, a.codigo as asignatura_codigo,
               CONCAT(e.primer_nombre, ' ', e.primer_apellido) as estudiante_nombre,
               e.documento_identidad as estudiante_doc_bd, e.semestre as estudiante_semestre_bd,
               c.nombre as carrera_nombre
        FROM solicitudes s
        LEFT JOIN profesores p ON p.profesor_id = s.profesor_solicitante_id
        JOIN asignaturas a ON a.asignatura_id = s.asignatura_id
        JOIN estudiantes e ON e.estudiante_id = s.estudiante_id
        LEFT JOIN carreras c ON c.carrera_id = e.carrera_id
        WHERE s.solicitud_id = ?
    ");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function aprobarSolicitud($pdo, $id, $admin_id, $comentario) {
    $stmt = $pdo->prepare("UPDATE solicitudes SET estado='APROBADA', profesor_revisor_id=?, justificacion_administrador=?, fecha_decision=NOW() WHERE solicitud_id=?");
    $stmt->execute([$admin_id, $comentario, $id]);
    $log = $pdo->prepare("INSERT INTO auditoria_logs (accion, usuario_id, solicitud_id, descripcion) VALUES ('APROBAR', ?, ?, ?)");
    $log->execute([$admin_id, $id, $comentario]);
}

function rechazarSolicitud($pdo, $id, $admin_id, $comentario) {
    $stmt = $pdo->prepare("UPDATE solicitudes SET estado='RECHAZADA', profesor_revisor_id=?, justificacion_administrador=?, fecha_decision=NOW() WHERE solicitud_id=?");
    $stmt->execute([$admin_id, $comentario, $id]);
    $log = $pdo->prepare("INSERT INTO auditoria_logs (accion, usuario_id, solicitud_id, descripcion) VALUES ('RECHAZAR', ?, ?, ?)");
    $log->execute([$admin_id, $id, $comentario]);
}
?>

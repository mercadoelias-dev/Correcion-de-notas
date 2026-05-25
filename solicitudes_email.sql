-- Ejecutar en phpMyAdmin sobre la BD sedd_completo

CREATE TABLE IF NOT EXISTS `solicitudes_email` (
  `sol_id`            int(11)      NOT NULL AUTO_INCREMENT,
  `profesor_id`       int(11)      NOT NULL,
  `email_actual`      varchar(200) NOT NULL,
  `email_nuevo`       varchar(200) NOT NULL,
  `estado`            enum('PENDIENTE','APROBADA','RECHAZADA','CANCELADA') NOT NULL DEFAULT 'PENDIENTE',
  `admin_id`          int(11)      DEFAULT NULL,
  `motivo_rechazo`    text         DEFAULT NULL,
  `fecha_solicitud`   datetime     NOT NULL DEFAULT current_timestamp(),
  `fecha_resolucion`  datetime     DEFAULT NULL,
  PRIMARY KEY (`sol_id`),
  KEY `idx_sol_email_profesor` (`profesor_id`),
  KEY `idx_sol_email_estado`   (`estado`),
  CONSTRAINT `sol_email_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`profesor_id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `sol_email_ibfk_2` FOREIGN KEY (`admin_id`)    REFERENCES `profesores` (`profesor_id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

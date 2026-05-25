-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 14-04-2026 a las 05:01:51
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.0.30

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sedd_completo`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `asignaturas`
--

CREATE TABLE `asignaturas` (
  `asignatura_id` int(11) NOT NULL,
  `nombre` varchar(250) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `carrera_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `asignaturas`
--

INSERT INTO `asignaturas` (`asignatura_id`, `nombre`, `codigo`, `carrera_id`) VALUES
(1, 'Matemáticas I', 'MAT101', 1),
(2, 'Física I', 'FIS101', 1),
(3, 'Química General', 'QUI101', 1),
(4, 'Programación I', 'PRG101', 1),
(5, 'Cálculo Diferencial', 'MAT201', 1),
(6, 'Álgebra Lineal', 'MAT202', 1),
(7, 'Base de Datos', 'PRG201', 1),
(8, 'Estructuras de Datos', 'PRG202', 1),
(9, 'Inglés I', 'ING101', NULL),
(10, 'Estadística', 'EST101', NULL);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `auditoria_logs`
--

CREATE TABLE `auditoria_logs` (
  `log_id` int(11) NOT NULL,
  `accion` varchar(100) NOT NULL,
  `usuario_id` int(11) DEFAULT NULL,
  `solicitud_id` int(11) DEFAULT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `auditoria_logs`
--

INSERT INTO `auditoria_logs` (`log_id`, `accion`, `usuario_id`, `solicitud_id`, `descripcion`, `fecha`) VALUES
(1, 'APROBAR', 2, 1, 'aprobada', '2026-04-13 20:37:43'),
(2, 'APROBAR', 2, 2, 'ok', '2026-04-13 20:41:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `carreras`
--

CREATE TABLE `carreras` (
  `carrera_id` int(11) NOT NULL,
  `nombre` varchar(200) NOT NULL,
  `codigo` varchar(20) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `carreras`
--

INSERT INTO `carreras` (`carrera_id`, `nombre`, `codigo`) VALUES
(1, 'Ingeniería de Sistemas', 'INGSIST'),
(2, 'Ingeniería Industrial', 'INGIND'),
(3, 'Ingeniería Civil', 'INGCIV'),
(4, 'Ingeniería Electrónica', 'INGELEC'),
(5, 'Ingeniería Mecánica', 'INGMEC');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `estudiantes`
--

CREATE TABLE `estudiantes` (
  `estudiante_id` int(11) NOT NULL,
  `codigo` varchar(50) NOT NULL,
  `primer_nombre` varchar(100) NOT NULL,
  `segundo_nombre` varchar(100) DEFAULT NULL,
  `primer_apellido` varchar(100) NOT NULL,
  `segundo_apellido` varchar(100) DEFAULT NULL,
  `documento_identidad` varchar(50) DEFAULT NULL,
  `semestre` varchar(20) DEFAULT NULL,
  `carrera_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `estudiantes`
--

INSERT INTO `estudiantes` (`estudiante_id`, `codigo`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `documento_identidad`, `semestre`, `carrera_id`) VALUES
(1, '2021001', 'María', 'José', 'García', 'López', NULL, NULL, 1),
(2, '2021002', 'Carlos', 'Andrés', 'Rodríguez', 'Pérez', NULL, NULL, 2),
(3, '2021003', 'Ana', 'María', 'Martínez', 'Silva', NULL, NULL, 3),
(4, '2021004', 'Luis', 'Fernando', 'Hernández', 'Torres', NULL, NULL, 1),
(5, '2021005', 'Laura', 'Sofía', 'Gómez', 'Ruiz', NULL, NULL, 4),
(6, '2021006', 'Pedro', 'Antonio', 'Sánchez', 'Díaz', NULL, NULL, 5),
(7, '2021007', 'Sofía', 'Isabel', 'López', 'Castro', NULL, NULL, 1),
(8, '2021008', 'Diego', 'Alejandro', 'Ramírez', 'Ortiz', NULL, NULL, 2),
(9, '2021009', 'Valentina', 'Andrea', 'Cruz', 'Moreno', NULL, NULL, 3),
(10, '2021010', 'Andrés', 'Felipe', 'Flores', 'Vargas', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `evidencias`
--

CREATE TABLE `evidencias` (
  `evidencia_id` int(11) NOT NULL,
  `solicitud_id` int(11) NOT NULL,
  `filename_original` varchar(255) NOT NULL,
  `filename_stored` varchar(255) NOT NULL,
  `mime_type` varchar(100) DEFAULT NULL,
  `tamano_bytes` bigint(20) DEFAULT NULL,
  `fecha_subida` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `notificacion_id` int(11) NOT NULL,
  `usuario_id` int(11) NOT NULL,
  `mensaje` varchar(255) NOT NULL,
  `url` varchar(255) DEFAULT NULL,
  `leida` tinyint(1) NOT NULL DEFAULT 0,
  `fecha` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`notificacion_id`, `usuario_id`, `mensaje`, `url`, `leida`, `fecha`) VALUES
(2, 2, 'Nueva solicitud #1 de Elias Andres pendiente de revisión.', 'solicitud_detalle.php?id=1', 1, '2026-04-13 20:37:26'),
(3, 2, 'Tu solicitud #1 (Álgebra Lineal) fue APROBADA.', 'solicitud_detalle.php?id=1', 1, '2026-04-13 20:37:43'),
(5, 2, 'Nueva solicitud #2 de haim acostsa pendiente de revisión.', 'solicitud_detalle.php?id=2', 1, '2026-04-13 20:40:58'),
(6, 5, 'Tu solicitud #2 (Estructuras de Datos) fue APROBADA.', 'solicitud_detalle.php?id=2', 1, '2026-04-13 20:41:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `profesores`
--

CREATE TABLE `profesores` (
  `profesor_id` int(11) NOT NULL,
  `primer_nombre` varchar(100) NOT NULL,
  `segundo_nombre` varchar(100) DEFAULT NULL,
  `primer_apellido` varchar(100) NOT NULL,
  `segundo_apellido` varchar(100) DEFAULT NULL,
  `email` varchar(200) NOT NULL,
  `password_hash` varchar(255) NOT NULL,
  `rol` enum('ADMINISTRADOR','SOLICITANTE') NOT NULL DEFAULT 'SOLICITANTE',
  `fecha_creacion` datetime NOT NULL DEFAULT current_timestamp(),
  `ultimo_acceso` datetime DEFAULT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `profesores`
--

INSERT INTO `profesores` (`profesor_id`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `email`, `password_hash`, `rol`, `fecha_creacion`, `ultimo_acceso`, `foto`, `activo`) VALUES
(2, 'Elias', NULL, 'Andres', NULL, 'elias@litoral.edu.co', '$2y$10$FWg83V4WL6rLRg2ZVCoexuj/SsK2YsRtAhnvWHigub1YNHiacMS3u', 'ADMINISTRADOR', '2026-04-14 01:33:24', '2026-04-13 21:18:23', 'foto_2_1776130744.png', 1),
(5, 'haim', NULL, 'acostsa', NULL, 'haim@litoral.edu.co', '$2y$10$fnOPq9gzeljd6Z7PkRl6N.d7zUiaw8b/e7vFBSwUFwBvdHgQkUZmC', 'SOLICITANTE', '2026-04-13 20:39:58', '2026-04-13 21:14:04', 'foto_5_1776131660.png', 1),
(6, 'Ana', NULL, 'Garcia', NULL, 'ana.garcia@litoral.edu.co', '$2y$10$6NBzy0R1I5s6CrUXzRJyUeGEm4f8aLaO26W2kgFK4XG6TcO11siGq', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(7, 'Carlos', NULL, 'Lopez', NULL, 'carlos.lopez@litoral.edu.co', '$2y$10$cxZ4PVIJ.SNm.lbRI0PkFeHGZAfTOsjF1H6Qao/BNotmdWtHYrYXy', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(8, 'Maria', NULL, 'Rodriguez', NULL, 'maria.rodriguez@litoral.edu.co', '$2y$10$hEZONZlQgLahbXYKyNRHUecRsG3s60m3Hnpx0Q4vtYUh6z/fP3C3S', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(9, 'Jose', NULL, 'Martinez', NULL, 'jose.martinez@litoral.edu.co', '$2y$10$OIuB0snRZXQMmi00G35syOjSI/WPtqkTQJnExqLxgQAay7VVsGYTC', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(10, 'Laura', NULL, 'Hernandez', NULL, 'laura.hernandez@litoral.edu.co', '$2y$10$4/BSNgIHXPYpxOEy1B6ZleHFfc6AJb6aU46AYqwDttTlafuKfQ9NW', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(11, 'Pedro', NULL, 'Gonzalez', NULL, 'pedro.gonzalez@litoral.edu.co', '$2y$10$4vykO4iIT7gsPjU/QlZY6e0oUBFS9988LdE7cxwqMC7rHILdXPsKO', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(12, 'Sofia', NULL, 'Perez', NULL, 'sofia.perez@litoral.edu.co', '$2y$10$E812eGwuj7pJJaVy3EkuB.DT/bp9ypWZHwXatal4amlCBGlj4cUB.', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(13, 'Luis', NULL, 'Sanchez', NULL, 'luis.sanchez@litoral.edu.co', '$2y$10$6ypKnurQBbkOVRTJHbskHu4g1UeZoN0HLnxBsov8AeNAJtPJGR8xm', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(14, 'Elena', NULL, 'Ramirez', NULL, 'elena.ramirez@litoral.edu.co', '$2y$10$BVaM.aXWaHTuSWlD.DTp4.2VVPH3j78jugPgvdl8dNLuzE10Czp0G', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(15, 'Jorge', NULL, 'Torres', NULL, 'jorge.torres@litoral.edu.co', '$2y$10$gxmgMTe1X8o5pwYEf9bRs.AK8WivGDPRakfPC3Rh32ZJQBgjMfaV6', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(16, 'Patricia', NULL, 'Flores', NULL, 'patricia.flores@litoral.edu.co', '$2y$10$73gwLYWsTygoSysK41ltAu5IVWMrPA4GgxVSwxDixtWFfDs1c.dae', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(17, 'Andres', NULL, 'Vargas', NULL, 'andres.vargas@litoral.edu.co', '$2y$10$b5iqaQnrfk.bKw..W4FvO.Z2oUm1CQULkZ59oXzpdkBfvUgi8UZ3m', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(18, 'Diana', NULL, 'Castro', NULL, 'diana.castro@litoral.edu.co', '$2y$10$3mb3FWjPxWeNCKCWEKOJVeJ6FpDjDOsrvzKCPTUrGlT9lyh3.raaC', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(19, 'Miguel', NULL, 'Ortiz', NULL, 'miguel.ortiz@litoral.edu.co', '$2y$10$smv7hT6cfVktEyUbJ4U7tuWvI20PxwYjRVTkWD8moczb4aRSUxWpG', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(20, 'Claudia', NULL, 'Moreno', NULL, 'claudia.moreno@litoral.edu.co', '$2y$10$QNx7LenHIfyaZuYG4uFhBOqYx6hQNYGgrJgTBOmihaSNIM9et.jKC', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(21, 'Roberto', NULL, 'Jimenez', NULL, 'roberto.jimenez@litoral.edu.co', '$2y$10$XQlxB4r4/paLcDgqWR/oJOGTnHeY6puztw7bMQ9c0rJYGv0owyHuK', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(22, 'Isabel', NULL, 'Ruiz', NULL, 'isabel.ruiz@litoral.edu.co', '$2y$10$zPzycoLGi1cz9X81O5koeevv2yazP1FolpapOQgnaAqTI7KNwFJrS', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(23, 'Fernando', NULL, 'Diaz', NULL, 'fernando.diaz@litoral.edu.co', '$2y$10$awOrzn.Fa35.Rp.cydXiyuDVVDpfIoHEFKuSCwUMMh0eQRVHowwYq', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(24, 'Monica', NULL, 'Reyes', NULL, 'monica.reyes@litoral.edu.co', '$2y$10$8Bv/3aGxRHi.IPQeCvApPux/nExqB70T/DA64hrNL4a5NtfgaLVHe', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(25, 'Alejandro', NULL, 'Cruz', NULL, 'alejandro.cruz@litoral.edu.co', '$2y$10$34ssugyxuRInjrq9JT.GcebM7a3fGdCDYwS4vLoWwqa1xnH43sOTK', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(26, 'Natalia', NULL, 'Romero', NULL, 'natalia.romero@litoral.edu.co', '$2y$10$WB2GtzFhUu1EpTNNEeJ5ruH59csUr.f2EPQUFuKfZJDNh6tFjkWk.', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(27, 'David', NULL, 'Morales', NULL, 'david.morales@litoral.edu.co', '$2y$10$jk6J3oVuSOBU2OJbkBqUGeNRHzhHNi3o5CCvcphTH5sH3mCLztHsG', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(28, 'Carolina', NULL, 'Silva', NULL, 'carolina.silva@litoral.edu.co', '$2y$10$T..EnJX7lTCWBJspj04gRe7q8r4O7U/iMQlARMrVahF4Plhy1f.PC', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(29, 'Juan', NULL, 'Medina', NULL, 'juan.medina@litoral.edu.co', '$2y$10$VD8jXR18F5BbN3au/kbQ0.6l6551ULOqlU37Eg56we5iDhgFKKtlO', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(30, 'Andrea', NULL, 'Aguilar', NULL, 'andrea.aguilar@litoral.edu.co', '$2y$10$tkIvuSxwp50u4E8brBbOhOlrovm6C8CxZdB/Uz57s9AkQRcSLp9gO', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(31, 'Pablo', NULL, 'Rios', NULL, 'pablo.rios@litoral.edu.co', '$2y$10$FHVtA.EgcrLY6QOeRU4NYOZM/JH7MCqnP//Vg9ysekwGThGDkq4SC', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(32, 'Valentina', NULL, 'Mendez', NULL, 'valentina.mendez@litoral.edu.co', '$2y$10$zrIk7dogL9VI1CCShOutt.z0qzMOMoShzQfOG4S4KsiMJdvjSyqNa', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(33, 'Sergio', NULL, 'Guerrero', NULL, 'sergio.guerrero@litoral.edu.co', '$2y$10$8qGqpT/.sftDTCjrXxJgKeMR1SjTp6lTRlC6k5.uzhmQjlexmMSD2', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(34, 'Camila', NULL, 'Herrera', NULL, 'camila.herrera@litoral.edu.co', '$2y$10$2o3cUYh2VUqOBdmDgAbUZO.Xh4Q/2CaF8AkfoPRM3ADhVWxzNcrxy', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(35, 'Oscar', NULL, 'Nunez', NULL, 'oscar.nunez@litoral.edu.co', '$2y$10$t.XW3L1QI38UtkhvS9BlFu..U4.NbuU66ImSGB.9zzJZjWB7GIjAW', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(36, 'Daniela', NULL, 'Campos', NULL, 'daniela.campos@litoral.edu.co', '$2y$10$9lZhpKTm9IFfjNFjIPrf1uGo5U98R4P1vT6FNYxP7AanHYCf22WP6', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(37, 'Hector', NULL, 'Vega', NULL, 'hector.vega@litoral.edu.co', '$2y$10$Y3VnOE8hbQs/kOAt22SAKuHJ9Im15BEFQmDvA261sldBn2ysHMp36', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(38, 'Paola', NULL, 'Soto', NULL, 'paola.soto@litoral.edu.co', '$2y$10$PA/qs2Lonuawt0LG6n7fTuZ6qbdrqnxFZbVXFO.IJK042RY8gU1Oi', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(39, 'Ricardo', NULL, 'Espinoza', NULL, 'ricardo.espinoza@litoral.edu.co', '$2y$10$2YRDoy/.msMYeBsCLvMJnef.6ZFKz1wcJgvHzd0nxaiajjiq.dPne', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(40, 'Viviana', NULL, 'Fuentes', NULL, 'viviana.fuentes@litoral.edu.co', '$2y$10$tHrAFTnCPpyVKvtxIA45kei8vi8irrLT2itLyGSXHz97Dmiz6479.', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(41, 'Gabriel', NULL, 'Pena', NULL, 'gabriel.pena@litoral.edu.co', '$2y$10$0FLSehATsPgCOHWWEGWpc.YC/w5bB98t2CKhzwhHjtxSDrhoVTTAS', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(42, 'Marcela', NULL, 'Rojas', NULL, 'marcela.rojas@litoral.edu.co', '$2y$10$fIwDFfG1KiB2aYHKQYSWu.6Rhcv3G6eY4herdEn.jVTXaLdZVenxm', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(43, 'Ivan', NULL, 'Delgado', NULL, 'ivan.delgado@litoral.edu.co', '$2y$10$yZ/3MTD81swhdJ5Fq2bcce6jo9fQkKnDm/buAIEpTAApqwLHwotIm', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(44, 'Adriana', NULL, 'Ibarra', NULL, 'adriana.ibarra@litoral.edu.co', '$2y$10$umL1rEZyeZXpLFd3u6SNB.1nkfYszOqQii9RIlajB5gzoHDCIfAz.', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(45, 'Nicolas', NULL, 'Paredes', NULL, 'nicolas.paredes@litoral.edu.co', '$2y$10$27tIzbF9YoT137IaiO6hTuPKgzpPSomu8YReiPIT.o2rLD.L.piJm', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(46, 'Liliana', NULL, 'Cabrera', NULL, 'liliana.cabrera@litoral.edu.co', '$2y$10$EBnd1Ef31TUT1CscK4gCv.76LOuSbdJAWXFOf5BIUZKo.bd1l4N3W', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(47, 'Raul', NULL, 'Contreras', NULL, 'raul.contreras@litoral.edu.co', '$2y$10$MhfvUnvxQoe8tOezVKFv3.Dyj0/xMbEDxJeUQsb7mjIIJ3o1ecjn2', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(48, 'Gloria', NULL, 'Pacheco', NULL, 'gloria.pacheco@litoral.edu.co', '$2y$10$3Pjg6w/Pj5BKbV3.GsEazuTHoCSEUugC5s1XauVYICHB3Pp1qbZn2', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(49, 'Ernesto', NULL, 'Salinas', NULL, 'ernesto.salinas@litoral.edu.co', '$2y$10$C9n3zyEoQz9xSfq.ZRQHYeWnL.OLgM7NdQeSSyPibsWpq1bzEjeze', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(50, 'Beatriz', NULL, 'Miranda', NULL, 'beatriz.miranda@litoral.edu.co', '$2y$10$MFZlokhxOV4OrkCpISoOeeLfzlm.RkQzIPid6LmIlzLywpRyqVudO', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(51, 'Cesar', NULL, 'Lozano', NULL, 'cesar.lozano@litoral.edu.co', '$2y$10$AbgnW4NbloIv76CudwKCO.9Ndp5Lw6w2qyP7mkrFMxI3hrHqnfr06', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(52, 'Martha', NULL, 'Palacios', NULL, 'martha.palacios@litoral.edu.co', '$2y$10$kN9BuPozctIqD6CBZh2WmeEnVG01S8OXHxT4JDH07AXYP6XX2tzhe', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(53, 'Jaime', NULL, 'Cardenas', NULL, 'jaime.cardenas@litoral.edu.co', '$2y$10$BWfMD/SCGglM0QhBciWrsOJOQWrfIJ81n3SuxGIS7uNMPL8JG31Oe', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(54, 'Rosa', NULL, 'Bermudez', NULL, 'rosa.bermudez@litoral.edu.co', '$2y$10$qnHSP1k5qay.ouXUzZI/nO5YtTyihWQoCAX2NXLyUv3B1OuJch2W6', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(55, 'Alberto', NULL, 'Escobar', NULL, 'alberto.escobar@litoral.edu.co', '$2y$10$EiNeTRs8Xb/tFMU9PIzoqOTV3lu8WKUYWbBgH/RFoBiCNXDfknPmS', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(56, 'Luz', NULL, 'Pineda', NULL, 'luz.pineda@litoral.edu.co', '$2y$10$TEfU7QTUMO/TSt4wfv4DQulsMPmwv4xDA1ZTIl0ZMIpsWQD.f2Are', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(57, 'Hugo', NULL, 'Carrillo', NULL, 'hugo.carrillo@litoral.edu.co', '$2y$10$vG92H5HUBSL/OARX4OwikO7bB17iA54UQRiuyRci8ZtJ7Oe6/xYRC', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(58, 'Amparo', NULL, 'Serrano', NULL, 'amparo.serrano@litoral.edu.co', '$2y$10$ZIatACjQRAP8dZjdErpEnu6oo5RRjZ.X2FCanHpjl2nNq2RZL.cw2', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(59, 'Enrique', NULL, 'Molina', NULL, 'enrique.molina@litoral.edu.co', '$2y$10$GMJROWdWvZA5E2GBFjr.X.qcAiLD3Bk7TtyL.7chXJVgCqzkdMgL.', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(60, 'Pilar', NULL, 'Navarro', NULL, 'pilar.navarro@litoral.edu.co', '$2y$10$.O5DGR.QDJyGW5ojaI85o.k0WFFbshYyYOu/4BW9V2ZEn31WZUHEy', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(61, 'Arturo', NULL, 'Leon', NULL, 'arturo.leon@litoral.edu.co', '$2y$10$Y.3nVJMCTWFxsUY63JWeAu0AXMZt7ZsVHH4oPrSmbo7CN8FYqNFs.', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(62, 'Silvia', NULL, 'Marquez', NULL, 'silvia.marquez@litoral.edu.co', '$2y$10$kH0CKYlAeam4CfYoFin6XeAL7sIK102V3eYxBeDF6Atz18aVwmNi.', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(63, 'Marco', NULL, 'Acosta', NULL, 'marco.acosta@litoral.edu.co', '$2y$10$ioRBIomIKSX.qhfyC2RiB.y1oTbKgIy0/OTmDzfDmmny.ZadidokK', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(64, 'Teresa', NULL, 'Villanueva', NULL, 'teresa.villanueva@litoral.edu.co', '$2y$10$ycPOUUfgq4CPW235IZG8xOP8AGsUUDEBOdgUoBjjH0o9ysAFfA7pO', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1),
(65, 'Felipe', NULL, 'Rangel', NULL, 'felipe.rangel@litoral.edu.co', '$2y$10$vAJoca75tivkryIHInKTaupsxmz2a77iRvd/tXSi5K6le0f7dpK5.', 'SOLICITANTE', '2026-04-13 21:58:59', NULL, NULL, 1);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `solicitudes`
--

CREATE TABLE `solicitudes` (
  `solicitud_id` int(11) NOT NULL,
  `profesor_solicitante_id` int(11) DEFAULT NULL,
  `tercero_solicitante_id` int(11) DEFAULT NULL,
  `estudiante_id` int(11) NOT NULL,
  `estudiante_documento` varchar(50) DEFAULT NULL,
  `estudiante_semestre` varchar(20) DEFAULT NULL,
  `asignatura_id` int(11) NOT NULL,
  `periodo_academico` varchar(20) NOT NULL,
  `tipo_solicitud` enum('CORRECCION','REPORTE','VALIDACION','SUFICIENCIA','HABILITACION','SUPLETORIOS') NOT NULL DEFAULT 'CORRECCION',
  `corte` enum('PRIMERO','SEGUNDO','TERCERO') NOT NULL,
  `motivo` text DEFAULT NULL,
  `nota_inicial_formativa` decimal(3,2) DEFAULT NULL,
  `nota_inicial_aplicativa` decimal(3,2) DEFAULT NULL,
  `nota_inicial_cognitiva` decimal(3,2) DEFAULT NULL,
  `nota_inicial_letras` varchar(100) DEFAULT NULL,
  `nota_corregida_formativa` decimal(3,2) DEFAULT NULL,
  `nota_corregida_aplicativa` decimal(3,2) DEFAULT NULL,
  `nota_corregida_cognitiva` decimal(3,2) DEFAULT NULL,
  `nota_corregida_letras` varchar(100) DEFAULT NULL,
  `nota_actual` decimal(3,2) DEFAULT NULL,
  `nota_propuesta` decimal(3,2) DEFAULT NULL,
  `justificacion_solicitante` text DEFAULT NULL,
  `estado` enum('PENDIENTE','APROBADA','RECHAZADA') NOT NULL DEFAULT 'PENDIENTE',
  `profesor_revisor_id` int(11) DEFAULT NULL,
  `justificacion_administrador` text DEFAULT NULL,
  `fecha_envio` datetime NOT NULL DEFAULT current_timestamp(),
  `fecha_decision` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `solicitudes`
--

INSERT INTO `solicitudes` (`solicitud_id`, `profesor_solicitante_id`, `tercero_solicitante_id`, `estudiante_id`, `estudiante_documento`, `estudiante_semestre`, `asignatura_id`, `periodo_academico`, `tipo_solicitud`, `corte`, `motivo`, `nota_inicial_formativa`, `nota_inicial_aplicativa`, `nota_inicial_cognitiva`, `nota_inicial_letras`, `nota_corregida_formativa`, `nota_corregida_aplicativa`, `nota_corregida_cognitiva`, `nota_corregida_letras`, `nota_actual`, `nota_propuesta`, `justificacion_solicitante`, `estado`, `profesor_revisor_id`, `justificacion_administrador`, `fecha_envio`, `fecha_decision`) VALUES
(1, 2, NULL, 10, '104227788', '3', 6, '2020-1', 'REPORTE', 'PRIMERO', 'prueba', 2.00, 2.00, 2.00, 'dos punto cero', 3.50, 3.50, 3.50, 'tres punto cinco', NULL, NULL, 'prueba', 'APROBADA', 2, 'aprobada', '2026-04-13 20:37:26', '2026-04-13 20:37:43'),
(2, 5, NULL, 10, '102345678', '4', 8, '2020-1', 'REPORTE', 'SEGUNDO', 'prueba', 3.00, 3.00, 3.00, 'tres punto cero', 3.00, 3.00, 3.00, 'tres punto cero', NULL, NULL, 'prueba', 'APROBADA', 2, 'ok', '2026-04-13 20:40:58', '2026-04-13 20:41:49');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `terceros`
--

CREATE TABLE `terceros` (
  `tercero_id` int(11) NOT NULL,
  `primer_nombre` varchar(100) NOT NULL,
  `segundo_nombre` varchar(100) DEFAULT NULL,
  `primer_apellido` varchar(100) NOT NULL,
  `segundo_apellido` varchar(100) DEFAULT NULL,
  `email` varchar(200) DEFAULT NULL,
  `telefono` varchar(30) DEFAULT NULL,
  `documento_identidad` varchar(50) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `profesor_id` int(11) DEFAULT NULL,
  `estudiante_id` int(11) DEFAULT NULL,
  `fecha_registro` datetime NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `terceros`
--

INSERT INTO `terceros` (`tercero_id`, `primer_nombre`, `segundo_nombre`, `primer_apellido`, `segundo_apellido`, `email`, `telefono`, `documento_identidad`, `descripcion`, `profesor_id`, `estudiante_id`, `fecha_registro`) VALUES
(1, 'Roberto', 'José', 'García', 'Mendoza', 'roberto.garcia@email.com', '3001234567', '12345678', 'Padre de familia', NULL, 1, '2026-04-14 01:33:24'),
(2, 'Carmen', 'Lucía', 'López', 'Ramírez', 'carmen.lopez@email.com', '3009876543', '87654321', 'Madre de familia', NULL, 1, '2026-04-14 01:33:24'),
(3, 'Jorge', 'Luis', 'Rodríguez', 'Santos', 'jorge.rodriguez@email.com', '3112345678', '11223344', 'Acudiente', NULL, 2, '2026-04-14 01:33:24'),
(4, 'Patricia', 'Elena', 'Silva', 'Torres', 'patricia.silva@email.com', '3209876543', '44332211', 'Tutora legal', NULL, 3, '2026-04-14 01:33:24');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD PRIMARY KEY (`asignatura_id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `carrera_id` (`carrera_id`);

--
-- Indices de la tabla `auditoria_logs`
--
ALTER TABLE `auditoria_logs`
  ADD PRIMARY KEY (`log_id`),
  ADD KEY `usuario_id` (`usuario_id`);

--
-- Indices de la tabla `carreras`
--
ALTER TABLE `carreras`
  ADD PRIMARY KEY (`carrera_id`),
  ADD UNIQUE KEY `codigo` (`codigo`);

--
-- Indices de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD PRIMARY KEY (`estudiante_id`),
  ADD UNIQUE KEY `codigo` (`codigo`),
  ADD KEY `carrera_id` (`carrera_id`);

--
-- Indices de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD PRIMARY KEY (`evidencia_id`),
  ADD KEY `solicitud_id` (`solicitud_id`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`notificacion_id`),
  ADD KEY `idx_notif_usuario` (`usuario_id`),
  ADD KEY `idx_notif_leida` (`leida`);

--
-- Indices de la tabla `profesores`
--
ALTER TABLE `profesores`
  ADD PRIMARY KEY (`profesor_id`),
  ADD UNIQUE KEY `email` (`email`),
  ADD KEY `idx_profesores_email` (`email`);

--
-- Indices de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD PRIMARY KEY (`solicitud_id`),
  ADD KEY `idx_solicitudes_estado` (`estado`),
  ADD KEY `idx_solicitudes_prof_solicitante` (`profesor_solicitante_id`),
  ADD KEY `idx_solicitudes_tercero_solicitante` (`tercero_solicitante_id`),
  ADD KEY `profesor_revisor_id` (`profesor_revisor_id`),
  ADD KEY `estudiante_id` (`estudiante_id`),
  ADD KEY `asignatura_id` (`asignatura_id`);

--
-- Indices de la tabla `terceros`
--
ALTER TABLE `terceros`
  ADD PRIMARY KEY (`tercero_id`),
  ADD KEY `idx_terceros_email` (`email`),
  ADD KEY `profesor_id` (`profesor_id`),
  ADD KEY `estudiante_id` (`estudiante_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  MODIFY `asignatura_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `auditoria_logs`
--
ALTER TABLE `auditoria_logs`
  MODIFY `log_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `carreras`
--
ALTER TABLE `carreras`
  MODIFY `carrera_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  MODIFY `estudiante_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=11;

--
-- AUTO_INCREMENT de la tabla `evidencias`
--
ALTER TABLE `evidencias`
  MODIFY `evidencia_id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `notificacion_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `profesores`
--
ALTER TABLE `profesores`
  MODIFY `profesor_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT de la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  MODIFY `solicitud_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `terceros`
--
ALTER TABLE `terceros`
  MODIFY `tercero_id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `asignaturas`
--
ALTER TABLE `asignaturas`
  ADD CONSTRAINT `asignaturas_ibfk_1` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`carrera_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `auditoria_logs`
--
ALTER TABLE `auditoria_logs`
  ADD CONSTRAINT `auditoria_logs_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `profesores` (`profesor_id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `estudiantes`
--
ALTER TABLE `estudiantes`
  ADD CONSTRAINT `estudiantes_ibfk_1` FOREIGN KEY (`carrera_id`) REFERENCES `carreras` (`carrera_id`) ON DELETE SET NULL ON UPDATE CASCADE;

--
-- Filtros para la tabla `evidencias`
--
ALTER TABLE `evidencias`
  ADD CONSTRAINT `evidencias_ibfk_1` FOREIGN KEY (`solicitud_id`) REFERENCES `solicitudes` (`solicitud_id`) ON DELETE CASCADE ON UPDATE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `notif_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `profesores` (`profesor_id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `solicitudes`
--
ALTER TABLE `solicitudes`
  ADD CONSTRAINT `solicitudes_ibfk_1` FOREIGN KEY (`profesor_solicitante_id`) REFERENCES `profesores` (`profesor_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `solicitudes_ibfk_2` FOREIGN KEY (`tercero_solicitante_id`) REFERENCES `terceros` (`tercero_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `solicitudes_ibfk_3` FOREIGN KEY (`profesor_revisor_id`) REFERENCES `profesores` (`profesor_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `solicitudes_ibfk_4` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`estudiante_id`) ON UPDATE CASCADE,
  ADD CONSTRAINT `solicitudes_ibfk_5` FOREIGN KEY (`asignatura_id`) REFERENCES `asignaturas` (`asignatura_id`) ON UPDATE CASCADE;

--
-- Filtros para la tabla `terceros`
--
ALTER TABLE `terceros`
  ADD CONSTRAINT `terceros_ibfk_1` FOREIGN KEY (`profesor_id`) REFERENCES `profesores` (`profesor_id`) ON DELETE SET NULL ON UPDATE CASCADE,
  ADD CONSTRAINT `terceros_ibfk_2` FOREIGN KEY (`estudiante_id`) REFERENCES `estudiantes` (`estudiante_id`) ON DELETE SET NULL ON UPDATE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;

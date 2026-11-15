-- =====================================================================
-- EDUCARAMA — Esquema v2
-- database/educarama.sql
--
-- Reglas y datos oficiales: CLAUDE.md (raíz del proyecto).
-- Contiene: estructura completa + datos de las tablas estáticas chicas.
-- Las tablas `competencia` y `accion` se cargan aparte con
-- database/seeds/catalogo_competencias.sql (generado desde la planilla del
-- programa; el generador queda fuera del repo).
--
-- Orden de importación en phpMyAdmin:
--   1. educarama.sql            (este archivo)
--   2. database/seeds/catalogo_competencias.sql
--   3. database/datos_ejemplo.sql   (opcional, solo desarrollo)
--
-- Convenciones:
--   rama    = categoría del programa (LyL, Scouts, Caminantes, Rovers). Estática.
--   seccion = instancia de una rama dentro de un grupo. Dinámica.
-- =====================================================================

CREATE DATABASE IF NOT EXISTS `educarama_v2`
  DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_spanish_ci;
USE `educarama_v2`;

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS
  `insignia_otorgada`, `especialidad_protagonista`, `etapa_lograda`,
  `tarea_etapa_cumplida`, `accion_cumplida`, `competencia_protagonista`,
  `evento_seccion`, `evento`, `solicitud_alta`, `acceso_seccion`, `acceso_grupo`,
  `usuario`, `documento`, `es_familia`, `familiar`, `protagonista`, `persona`,
  `patrulla`, `seccion`, `grupo`,
  `rol`, `especialidad`, `insignia`, `tarea_etapa`, `etapa`, `accion`,
  `competencia`, `nomenclatura`, `area_rama`, `area`, `rama`;

SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================================
-- 1. TABLAS ESTÁTICAS (solo las modifican los devs)
-- =====================================================================

CREATE TABLE `rama` (
  `id`                 INT          NOT NULL AUTO_INCREMENT,
  `titulo`             VARCHAR(50)  NOT NULL,
  `titulo_corto`       VARCHAR(30)  NOT NULL,
  `usa_especialidades` TINYINT(1)   NOT NULL DEFAULT 0,
  `usa_acciones`       TINYINT(1)   NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `area` (
  `id`     INT         NOT NULL AUTO_INCREMENT,
  `titulo` VARCHAR(60) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Qué áreas usa cada rama y con qué nombre se muestran.
CREATE TABLE `area_rama` (
  `id_area` INT          NOT NULL,
  `id_rama` INT          NOT NULL,
  `titulo`  VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id_area`, `id_rama`),
  CONSTRAINT `fk_area_rama_area` FOREIGN KEY (`id_area`) REFERENCES `area` (`id`),
  CONSTRAINT `fk_area_rama_rama` FOREIGN KEY (`id_rama`) REFERENCES `rama` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Términos que cambian según la rama. Se leen con Nomenclatura::get($clave, $idRama).
CREATE TABLE `nomenclatura` (
  `clave`   VARCHAR(40)  NOT NULL,
  `id_rama` INT          NOT NULL,
  `texto`   VARCHAR(100) NOT NULL,
  PRIMARY KEY (`clave`, `id_rama`),
  CONSTRAINT `fk_nomenclatura_rama` FOREIGN KEY (`id_rama`) REFERENCES `rama` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Rovers numera sus competencias desde 1 dentro de cada área,
-- por eso la unicidad incluye el área.
CREATE TABLE `competencia` (
  `id`          INT  NOT NULL AUTO_INCREMENT,
  `id_rama`     INT  NOT NULL,
  `id_area`     INT  NOT NULL,
  `numero`      INT  NOT NULL,
  `descripcion` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_competencia` (`id_rama`, `id_area`, `numero`),
  CONSTRAINT `fk_competencia_rama` FOREIGN KEY (`id_rama`) REFERENCES `rama` (`id`),
  CONSTRAINT `fk_competencia_area` FOREIGN KEY (`id_area`) REFERENCES `area` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- es_opcional solo se usa en Lobatos y Lobeznas.
CREATE TABLE `accion` (
  `id`             INT        NOT NULL AUTO_INCREMENT,
  `id_competencia` INT        NOT NULL,
  `numero`         INT        NOT NULL,
  `descripcion`    TEXT       NOT NULL,
  `es_opcional`    TINYINT(1) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_accion` (`id_competencia`, `numero`),
  CONSTRAINT `fk_accion_competencia` FOREIGN KEY (`id_competencia`) REFERENCES `competencia` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- habilitaciones: texto informativo (no bloquea nada en el sistema).
-- competencias_etapa: cupo fijo de competencias de la etapa. Es el total de la
-- rama dividido por la cantidad de etapas, redondeado; la última se lleva el resto,
-- así los cupos de cada rama suman exactamente su total. El Service lo usa para
-- avisar y sugerir el pase; acá no se fuerza nada.
CREATE TABLE `etapa` (
  `id`                 INT          NOT NULL AUTO_INCREMENT,
  `id_rama`            INT          NOT NULL,
  `numero`             INT          NOT NULL,
  `titulo`             VARCHAR(100) NOT NULL,
  `habilitaciones`     TEXT         NULL,
  `competencias_etapa` INT          NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_etapa` (`id_rama`, `numero`),
  CONSTRAINT `fk_etapa_rama` FOREIGN KEY (`id_rama`) REFERENCES `rama` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `tarea_etapa` (
  `id`          INT  NOT NULL AUTO_INCREMENT,
  `id_etapa`    INT  NOT NULL,
  `numero`      INT  NOT NULL,
  `descripcion` TEXT NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_tarea_etapa` (`id_etapa`, `numero`),
  CONSTRAINT `fk_tarea_etapa_etapa` FOREIGN KEY (`id_etapa`) REFERENCES `etapa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- imagen: solo el nombre del archivo, en views/assets/images/Insignias/Progresion/ (lo arma Vista::imagenInsignia).
CREATE TABLE `insignia` (
  `id`       INT          NOT NULL AUTO_INCREMENT,
  `titulo`   VARCHAR(100) NOT NULL,
  `imagen`   VARCHAR(255) NULL,
  `id_etapa` INT          NULL,
  `id_rama`  INT          NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_insignia_etapa` FOREIGN KEY (`id_etapa`) REFERENCES `etapa` (`id`),
  CONSTRAINT `fk_insignia_rama`  FOREIGN KEY (`id_rama`)  REFERENCES `rama` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `especialidad` (
  `id`      INT          NOT NULL AUTO_INCREMENT,
  `id_area` INT          NOT NULL,
  `numero`  INT          NOT NULL,
  `titulo`  VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_especialidad` (`id_area`, `numero`),
  CONSTRAINT `fk_especialidad_area` FOREIGN KEY (`id_area`) REFERENCES `area` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- nivel permite comparar permisos: Supervisor 1 < Ayudante 2 < Responsable 3 < Jefe 4.
CREATE TABLE `rol` (
  `id`     INT         NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(30) NOT NULL,
  `nivel`  INT         NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_rol_nivel` (`nivel`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================
-- 2. TABLAS DINÁMICAS
-- =====================================================================

CREATE TABLE `grupo` (
  `id`     INT          NOT NULL AUTO_INCREMENT,
  `codigo` VARCHAR(20)  NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_grupo_codigo` (`codigo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `seccion` (
  `id`       INT          NOT NULL AUTO_INCREMENT,
  `nombre`   VARCHAR(100) NOT NULL,
  `id_grupo` INT          NOT NULL,
  `id_rama`  INT          NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_seccion_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupo` (`id`),
  CONSTRAINT `fk_seccion_rama`  FOREIGN KEY (`id_rama`)  REFERENCES `rama` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- El nombre visible (Seisena / Patrulla / Equipo) sale de nomenclatura.
CREATE TABLE `patrulla` (
  `id`         INT          NOT NULL AUTO_INCREMENT,
  `nombre`     VARCHAR(100) NOT NULL,
  `id_seccion` INT          NOT NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_patrulla_seccion` FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `persona` (
  `id`               INT          NOT NULL AUTO_INCREMENT,
  `dni`              INT          NOT NULL,
  `nombre`           VARCHAR(100) NOT NULL,
  `apellido`         VARCHAR(100) NOT NULL,
  `fecha_nacimiento` DATE         NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_persona_dni` (`dni`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Regla que valida el Service (no la DB): la patrulla debe ser de la misma sección.
CREATE TABLE `protagonista` (
  `id`           INT        NOT NULL AUTO_INCREMENT,
  `id_persona`   INT        NOT NULL,
  `id_seccion`   INT        NOT NULL,
  `id_patrulla`  INT        NULL,
  `promesa`      TINYINT(1) NOT NULL DEFAULT 0,
  `anio_ingreso` INT        NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_protagonista_persona` (`id_persona`),
  CONSTRAINT `fk_protagonista_persona`  FOREIGN KEY (`id_persona`)  REFERENCES `persona` (`id`),
  CONSTRAINT `fk_protagonista_seccion`  FOREIGN KEY (`id_seccion`)  REFERENCES `seccion` (`id`),
  CONSTRAINT `fk_protagonista_patrulla` FOREIGN KEY (`id_patrulla`) REFERENCES `patrulla` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `familiar` (
  `id`                INT          NOT NULL AUTO_INCREMENT,
  `id_persona`        INT          NOT NULL,
  `telefono_contacto` VARCHAR(30)  NULL,
  `email_contacto`    VARCHAR(150) NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_familiar_persona` (`id_persona`),
  CONSTRAINT `fk_familiar_persona` FOREIGN KEY (`id_persona`) REFERENCES `persona` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `es_familia` (
  `id_protagonista`           INT          NOT NULL,
  `id_familiar`               INT          NOT NULL,
  `vinculo`                   VARCHAR(50)  NULL,
  `es_contacto_de_emergencia` TINYINT(1)   NOT NULL DEFAULT 0,
  PRIMARY KEY (`id_protagonista`, `id_familiar`),
  CONSTRAINT `fk_es_familia_protagonista` FOREIGN KEY (`id_protagonista`) REFERENCES `protagonista` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_es_familia_familiar`     FOREIGN KEY (`id_familiar`)     REFERENCES `familiar` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- El archivo vive en el almacén (src/Almacen, carpeta ALMACEN_RUTA), no acá:
-- ruta_archivo es relativa al almacén, ej. 'documentos/protagonista/7/3f9a...e1.pdf'.
-- tipo_mime es el tipo real (detectado por contenido, no por la extensión).
CREATE TABLE `documento` (
  `id`              INT          NOT NULL AUTO_INCREMENT,
  `id_protagonista` INT          NOT NULL,
  `tipo_documento`  VARCHAR(50)  NULL,
  `descripcion`     TEXT         NULL,
  `ruta_archivo`    VARCHAR(255) NOT NULL,
  `nombre_original` VARCHAR(255) NOT NULL,
  `tipo_mime`       VARCHAR(100) NOT NULL,
  `tamanio_bytes`   INT          NOT NULL,
  `fecha_subida`    DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_documento_protagonista` FOREIGN KEY (`id_protagonista`) REFERENCES `protagonista` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `usuario` (
  `id`                 INT          NOT NULL AUTO_INCREMENT,
  `nombre`             VARCHAR(100) NOT NULL,
  `email`              VARCHAR(150) NOT NULL,
  `contrasenia`        VARCHAR(255) NOT NULL,
  `es_admin`           TINYINT(1)   NOT NULL DEFAULT 0,
  `email_validado`     TINYINT(1)   NOT NULL DEFAULT 0,
  `token_recuperacion` VARCHAR(20)  NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_usuario_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- El Jefe de grupo es Jefe de todas sus secciones por derivación en código:
-- no se crean filas en acceso_seccion para él.
CREATE TABLE `acceso_grupo` (
  `id_usuario` INT NOT NULL,
  `id_grupo`   INT NOT NULL,
  `id_rol`     INT NOT NULL,
  PRIMARY KEY (`id_usuario`, `id_grupo`),
  CONSTRAINT `fk_acceso_grupo_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_acceso_grupo_grupo`   FOREIGN KEY (`id_grupo`)   REFERENCES `grupo` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_acceso_grupo_rol`     FOREIGN KEY (`id_rol`)     REFERENCES `rol` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `acceso_seccion` (
  `id_usuario` INT NOT NULL,
  `id_seccion` INT NOT NULL,
  `id_rol`     INT NOT NULL,
  PRIMARY KEY (`id_usuario`, `id_seccion`),
  CONSTRAINT `fk_acceso_seccion_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuario` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_acceso_seccion_seccion` FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_acceso_seccion_rol`     FOREIGN KEY (`id_rol`)     REFERENCES `rol` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- datos_grupo / datos_secciones: JSON con lo que cargó el solicitante.
-- Se convierten en filas reales de grupo/seccion al aprobar.
CREATE TABLE `solicitud_alta` (
  `id`               INT      NOT NULL AUTO_INCREMENT,
  `id_usuario`       INT      NOT NULL,
  `datos_grupo`      LONGTEXT NOT NULL CHECK (JSON_VALID(`datos_grupo`)),
  `datos_secciones`  LONGTEXT NOT NULL CHECK (JSON_VALID(`datos_secciones`)),
  `estado`           ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  `fecha`            DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `id_admin_revisor` INT      NULL,
  `motivo_rechazo`   TEXT     NULL COMMENT 'Obligatorio al rechazar; lo ve el solicitante',
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_solicitud_usuario` FOREIGN KEY (`id_usuario`)       REFERENCES `usuario` (`id`),
  CONSTRAINT `fk_solicitud_admin`   FOREIGN KEY (`id_admin_revisor`) REFERENCES `usuario` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Reemplaza a la vieja tabla `aviso`.
-- Sin filas en evento_seccion = evento de todo el grupo.
-- id_tipo_evento: reservado para plantillas futuras (sin FK todavía).
CREATE TABLE `evento` (
  `id`             INT          NOT NULL AUTO_INCREMENT,
  `id_grupo`       INT          NOT NULL,
  `titulo`         VARCHAR(200) NOT NULL,
  `descripcion`    TEXT         NOT NULL,
  `fecha_inicio`   DATE         NOT NULL,
  `fecha_fin`      DATE         NULL,
  `prioridad`      ENUM('alta','media','baja') NOT NULL DEFAULT 'media',
  `activo`         TINYINT(1)   NOT NULL DEFAULT 1,
  `es_publico`     TINYINT(1)   NOT NULL DEFAULT 0,
  `id_tipo_evento` INT          NULL,
  PRIMARY KEY (`id`),
  CONSTRAINT `fk_evento_grupo` FOREIGN KEY (`id_grupo`) REFERENCES `grupo` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `evento_seccion` (
  `id_evento`  INT NOT NULL,
  `id_seccion` INT NOT NULL,
  PRIMARY KEY (`id_evento`, `id_seccion`),
  CONSTRAINT `fk_evento_seccion_evento`  FOREIGN KEY (`id_evento`)  REFERENCES `evento` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_evento_seccion_seccion` FOREIGN KEY (`id_seccion`) REFERENCES `seccion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================
-- 3. PROGRESIÓN (tablas dispersas)
-- Sin fila = pendiente / no cumplido. Cada fila la crea un educador.
-- Pasar de en_proceso a desarrollada es un UPDATE.
-- =====================================================================

CREATE TABLE `competencia_protagonista` (
  `id_protagonista` INT  NOT NULL,
  `id_competencia`  INT  NOT NULL,
  `id_etapa`        INT  NULL COMMENT 'Etapa en la que la eligió',
  `estado`          ENUM('en_proceso','desarrollada') NOT NULL DEFAULT 'en_proceso',
  `fecha`           DATE NOT NULL,
  PRIMARY KEY (`id_protagonista`, `id_competencia`),
  CONSTRAINT `fk_cp_protagonista` FOREIGN KEY (`id_protagonista`) REFERENCES `protagonista` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cp_competencia`  FOREIGN KEY (`id_competencia`)  REFERENCES `competencia` (`id`),
  CONSTRAINT `fk_cp_etapa`        FOREIGN KEY (`id_etapa`)        REFERENCES `etapa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `accion_cumplida` (
  `id_protagonista` INT  NOT NULL,
  `id_accion`       INT  NOT NULL,
  `fecha`           DATE NOT NULL,
  PRIMARY KEY (`id_protagonista`, `id_accion`),
  CONSTRAINT `fk_ac_protagonista` FOREIGN KEY (`id_protagonista`) REFERENCES `protagonista` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ac_accion`       FOREIGN KEY (`id_accion`)       REFERENCES `accion` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `tarea_etapa_cumplida` (
  `id_protagonista` INT  NOT NULL,
  `id_tarea_etapa`  INT  NOT NULL,
  `fecha`           DATE NOT NULL,
  PRIMARY KEY (`id_protagonista`, `id_tarea_etapa`),
  CONSTRAINT `fk_tec_protagonista` FOREIGN KEY (`id_protagonista`) REFERENCES `protagonista` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_tec_tarea`        FOREIGN KEY (`id_tarea_etapa`)  REFERENCES `tarea_etapa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- elemento: solo Caminantes (eligen el orden de los elementos).
CREATE TABLE `etapa_lograda` (
  `id_protagonista` INT  NOT NULL,
  `id_etapa`        INT  NOT NULL,
  `elemento`        ENUM('agua','tierra','fuego','aire') NULL,
  `fecha`           DATE NOT NULL,
  PRIMARY KEY (`id_protagonista`, `id_etapa`),
  CONSTRAINT `fk_el_protagonista` FOREIGN KEY (`id_protagonista`) REFERENCES `protagonista` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_el_etapa`        FOREIGN KEY (`id_etapa`)        REFERENCES `etapa` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

CREATE TABLE `especialidad_protagonista` (
  `id_protagonista` INT  NOT NULL,
  `id_especialidad` INT  NOT NULL,
  `estado`          ENUM('en_proceso','desarrollada') NOT NULL DEFAULT 'en_proceso',
  `fecha`           DATE NOT NULL,
  PRIMARY KEY (`id_protagonista`, `id_especialidad`),
  CONSTRAINT `fk_ep_protagonista` FOREIGN KEY (`id_protagonista`) REFERENCES `protagonista` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ep_especialidad` FOREIGN KEY (`id_especialidad`) REFERENCES `especialidad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- Toda insignia entregada, incluidas las de etapa.
CREATE TABLE `insignia_otorgada` (
  `id_protagonista` INT  NOT NULL,
  `id_insignia`     INT  NOT NULL,
  `fecha`           DATE NOT NULL,
  PRIMARY KEY (`id_protagonista`, `id_insignia`),
  CONSTRAINT `fk_io_protagonista` FOREIGN KEY (`id_protagonista`) REFERENCES `protagonista` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_io_insignia`     FOREIGN KEY (`id_insignia`)     REFERENCES `insignia` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_spanish_ci;

-- =====================================================================
-- 4. DATOS ESTÁTICOS
-- =====================================================================

INSERT INTO `rama` (`id`, `titulo`, `titulo_corto`, `usa_especialidades`, `usa_acciones`) VALUES
(1, 'Lobatos y Lobeznas', 'Manada',     1, 1),
(2, 'Scouts',             'Unidad',     1, 1),
(3, 'Caminantes',         'Caminantes', 0, 1),
(4, 'Rovers',             'Rovers',     0, 0);

INSERT INTO `area` (`id`, `titulo`) VALUES
(1,  'Salud y Bienestar'),
(2,  'Paz y Desarrollo'),
(3,  'Ambiente'),
(4,  'Habilidades para la Vida'),
(5,  'Corporalidad'),
(6,  'Creatividad'),
(7,  'Carácter'),
(8,  'Afectividad'),
(9,  'Sociabilidad'),
(10, 'Espiritualidad');

INSERT INTO `area_rama` (`id_area`, `id_rama`, `titulo`) VALUES
(1, 1, 'Sendero de la Salud y el Bienestar'),
(2, 1, 'Sendero de la Paz'),
(3, 1, 'Sendero de la Naturaleza'),
(4, 1, 'Sendero de las Habilidades'),
(1, 2, 'Área de Salud y Bienestar'),
(2, 2, 'Área de Paz y Desarrollo'),
(3, 2, 'Área de Ambiente'),
(4, 2, 'Área de Habilidades para la Vida'),
(1, 3, 'Área de Salud y Bienestar'),
(2, 3, 'Área de Paz y Desarrollo'),
(3, 3, 'Área de Ambiente'),
(4, 3, 'Área de Habilidades para la Vida'),
(5, 4, 'Corporalidad'),
(6, 4, 'Creatividad'),
(7, 4, 'Carácter'),
(8, 4, 'Afectividad'),
(9, 4, 'Sociabilidad'),
(10, 4, 'Espiritualidad');

-- Rovers no tiene "pequenio_equipo": el helper devuelve null y la vista lo oculta.
INSERT INTO `nomenclatura` (`clave`, `id_rama`, `texto`) VALUES
('areas', 1, 'Senderos'),
('areas', 2, 'Áreas de desarrollo'),
('areas', 3, 'Áreas de desarrollo'),
('areas', 4, 'Áreas de crecimiento'),
('competencias', 1, 'Competencias intermedias'),
('competencias', 2, 'Competencias intermedias'),
('competencias', 3, 'Competencias intermedias'),
('competencias', 4, 'Competencias finales'),
('pequenio_equipo', 1, 'Seisena'),
('pequenio_equipo', 2, 'Patrulla'),
('pequenio_equipo', 3, 'Equipo');

-- Cupos: LyL 47 = 12+12+12+11 · Scouts 53 = 13+13+13+14 · Caminantes 49 = 12+12+12+13 · Rovers 37 = 12+12+13
INSERT INTO `etapa` (`id`, `id_rama`, `numero`, `titulo`, `habilitaciones`, `competencias_etapa`) VALUES
(1,  1, 1, 'Pata Tierna',                        'Puede comenzar a realizar especialidades e insignias especiales.', 12),
(2,  1, 2, 'Lobo Saltador / Loba Saltadora',     NULL, 12),
(3,  1, 3, 'Lobo Rastreador / Loba Rastreadora', NULL, 12),
(4,  1, 4, 'Lobo Cazador / Loba Cazadora',       NULL, 11),
(5,  2, 1, 'Pistas',                             'Puede comenzar a realizar especialidades e insignias especiales.', 13),
(6,  2, 2, 'Senda',                              NULL, 13),
(7,  2, 3, 'Rumbo',                              NULL, 13),
(8,  2, 4, 'Travesía',                           NULL, 14),
(9,  3, 1, 'Primera etapa',                      NULL, 12),
(10, 3, 2, 'Segunda etapa',                      NULL, 12),
(11, 3, 3, 'Tercera etapa',                      NULL, 12),
(12, 3, 4, 'Cuarta etapa',                       NULL, 13),
(13, 4, 1, 'Encuentro',                          NULL, 12),
(14, 4, 2, 'Compromiso',                         NULL, 12),
(15, 4, 3, 'Proyección',                         NULL, 13);

INSERT INTO `tarea_etapa` (`id_etapa`, `numero`, `descripcion`) VALUES
-- Lobatos y Lobeznas
(2, 1, 'Participa al menos de una Descubierta.'),
(3, 1, 'Participa al menos de una Cacería.'),
(4, 1, 'Se ha desempeñado alguna vez como Seisenero/a.'),
(4, 2, 'Ayuda a otra lobezna u otro lobato en su progresión personal.'),
-- Scouts
(5, 1, 'Desempeña un rol dentro de la Patrulla.'),
(6, 1, 'Reorganiza sus Cartas de Exploración.'),
(6, 2, 'Ejerce un rol de Patrulla diferente al ya desempeñado.'),
(6, 3, 'Participa al menos de una Descubierta con su Patrulla o de Unidad.'),
(7, 1, 'Reorganiza sus Cartas de Exploración.'),
(7, 2, 'Participa al menos de un Proyecto de Unidad y de un Proyecto de Patrulla.'),
(8, 1, 'Reorganiza sus Cartas de Exploración.'),
(8, 2, 'Ha desempeñado 3 o más roles en su Patrulla.'),
(8, 3, 'Apoya a otro Scout en su progresión personal.'),
(8, 4, 'Al final de la etapa realiza la Exploración de Travesía.'),
-- Caminantes
(9, 1, 'Desarrolla y evalúa su Diario de Marcha.'),
(9, 2, 'Participa/desarrolla al menos un proyecto caminante en el campo de acción solidaridad.'),
(9, 3, 'Participa al menos de un rally de aventura.'),
(9, 4, 'Realiza el Raid de Descubrimiento (marcha de un solo día al final de la primera etapa).'),
(10, 1, 'Participa/desarrolla al menos un proyecto caminante en el campo de acción naturaleza.'),
(10, 2, 'Participa al menos de un rally de descubierta.'),
(10, 3, 'Trabaja en su Diario de Marcha y percibe avances.'),
(10, 4, 'Realiza el Raid de Desafío (marcha con pernocte y buena acción o actividad solidaria, al final de la segunda o comienzo de la tercera etapa).'),
(11, 1, 'Participa/desarrolla al menos un proyecto caminante en el campo de acción viaje y aventura o arte y cultura.'),
(11, 2, 'Trabaja en su Diario de Marcha y percibe avances.'),
(11, 3, 'Realiza al menos una tutoría y/o toma un rol de liderazgo en el equipo o comunidad.'),
(12, 1, 'Participa/desarrolla al menos un proyecto caminante en el campo de acción vocación.'),
(12, 2, 'Trabaja en su Diario de Marcha con avances significativos y se prepara para su pase de rama.'),
(12, 3, 'Lidera una actividad o proyecto de los campos prioritarios junto a su equipo.'),
(12, 4, 'Realiza el Raid de Compromiso (marcha con pernocte y actividad solidaria al inicio de la cuarta etapa).'),
-- Rovers
(13, 1, 'Participa/desarrolla al menos un proyecto rover en el campo de acción prioritario "solidario".'),
(13, 2, 'Participa al menos de una descubierta.'),
(13, 3, 'Trabaja en su Plan Personal, se perciben avances y lo renueva.'),
(13, 4, 'Se informa sobre sus Derechos Asociativos.'),
(14, 1, 'Participa/desarrolla al menos un proyecto en el campo de acción prioritario "trabajo".'),
(14, 2, 'Participa al menos de una descubierta.'),
(14, 3, 'Trabaja en su Plan Personal percibiéndose avances significativos.'),
(14, 4, 'Realiza al menos una acción de tutoría.'),
(15, 1, 'Lidera al menos un proyecto en un campo de acción prioritario.'),
(15, 2, 'Participa de un proyecto rover en el campo de acción prioritario "viaje".'),
(15, 3, 'Participa de una descubierta.'),
(15, 4, 'Actúa como tutor/a de otros jóvenes.'),
(15, 5, 'Continúa trabajando en su Plan Personal, se perciben avances y se prepara para su partida.');

-- Una insignia por etapa + Ciudadanía (partida Rover).
INSERT INTO `insignia` (`titulo`, `imagen`, `id_etapa`, `id_rama`)
SELECT `titulo`, NULL, `id`, `id_rama` FROM `etapa` ORDER BY `id`;

INSERT INTO `insignia` (`titulo`, `imagen`, `id_etapa`, `id_rama`) VALUES
('Ciudadanía', NULL, NULL, 4);

-- Imágenes de las insignias de etapa: solo el nombre del archivo (la carpeta la pone
-- Vista::imagenInsignia). Caminantes (etapas 9 a 12) queda en NULL: su imagen depende
-- del elemento elegido en etapa_lograda. Ciudadanía no tiene imagen.
UPDATE `insignia` SET `imagen` = 'lobatos-1-pata-tierna.png' WHERE `id_etapa` = 1;
UPDATE `insignia` SET `imagen` = 'lobatos-2-saltador.png'    WHERE `id_etapa` = 2;
UPDATE `insignia` SET `imagen` = 'lobatos-3-rastreador.png'  WHERE `id_etapa` = 3;
UPDATE `insignia` SET `imagen` = 'lobatos-4-cazador.png'     WHERE `id_etapa` = 4;
UPDATE `insignia` SET `imagen` = 'scouts-1-pistas.png'       WHERE `id_etapa` = 5;
UPDATE `insignia` SET `imagen` = 'scouts-2-senda.png'        WHERE `id_etapa` = 6;
UPDATE `insignia` SET `imagen` = 'scouts-3-rumbo.png'        WHERE `id_etapa` = 7;
UPDATE `insignia` SET `imagen` = 'scouts-4-travesia.png'     WHERE `id_etapa` = 8;
UPDATE `insignia` SET `imagen` = 'rovers-1-encuentro.png'    WHERE `id_etapa` = 13;
UPDATE `insignia` SET `imagen` = 'rovers-2-compromiso.png'   WHERE `id_etapa` = 14;
UPDATE `insignia` SET `imagen` = 'rovers-3-proyeccion.png'   WHERE `id_etapa` = 15;

INSERT INTO `especialidad` (`id_area`, `numero`, `titulo`) VALUES
-- 1 Salud y Bienestar
(1, 1, 'Deportes'), (1, 2, 'Atletismo'), (1, 3, 'Natación'), (1, 4, 'Coleccionismo'),
(1, 5, 'Primeros auxilios'), (1, 6, 'Seguridad'), (1, 7, 'Defensa Civil'), (1, 8, 'Modelismo'),
(1, 9, 'Higiene y salud pública'), (1, 10, 'Seguridad vial'), (1, 11, 'Navegación'),
(1, 12, 'Artes marciales'), (1, 13, 'Hobbies'),
-- 2 Paz y Desarrollo
(2, 1, 'Culturas originarias'), (2, 2, 'Interpretación'), (2, 3, 'Orientación urbana'), (2, 4, 'Cívica'),
(2, 5, 'Comunicación'), (2, 6, 'Mediación'), (2, 7, 'Alfabetización'), (2, 8, 'Animación espiritual'),
(2, 9, 'Folclore'), (2, 10, 'Lector/a'), (2, 11, 'Teatro'), (2, 12, 'Bellas artes'),
(2, 13, 'Cerámica'), (2, 14, 'Música'), (2, 15, 'Circo'), (2, 16, 'Escritura'), (2, 17, 'Periodismo'),
-- 3 Ambiente
(3, 1, 'Naturalista'), (3, 2, 'Montañismo'), (3, 3, 'Pesca'), (3, 4, 'Astronomía'),
(3, 5, 'Meteorología'), (3, 6, 'Ornitología'), (3, 7, 'Orientación'), (3, 8, 'Cocina rústica'),
(3, 9, 'Pionerismo'), (3, 10, 'Senderismo'), (3, 11, 'Geología'), (3, 12, 'Habilidades de supervivencia'),
(3, 13, 'Espeleología'), (3, 14, 'Conservación ambiental'), (3, 15, 'Horticultura y jardinería'), (3, 16, 'Forestal'),
-- 4 Habilidades para la Vida
(4, 1, 'Cocina'), (4, 2, 'Albañilería'), (4, 3, 'Electrónica'), (4, 4, 'Electricidad'),
(4, 5, 'Hojalatería'), (4, 6, 'Reparaciones'), (4, 7, 'Puericultura'), (4, 8, 'Costura'),
(4, 9, 'Carpintería'), (4, 10, 'Fotografía'), (4, 11, 'Informática'), (4, 12, 'Tallado'),
(4, 13, 'Mecánica'), (4, 14, 'Química'), (4, 15, 'Diseño gráfico'), (4, 16, 'Videos'),
(4, 17, 'Redes sociales'), (4, 18, 'Cuidado de mascotas'), (4, 19, 'Economía doméstica');

INSERT INTO `rol` (`id`, `nombre`, `nivel`) VALUES
(1, 'Supervisor',  1),
(2, 'Ayudante',    2),
(3, 'Responsable', 3),
(4, 'Jefe',        4);

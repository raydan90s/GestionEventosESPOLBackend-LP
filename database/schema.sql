-- ===========================================================================
--  Plataforma Web para la Organizacion y Gestion de Eventos ESPOL
--  Esquema de base de datos - PostgreSQL (Supabase)
--
--  Ejecutar en: Supabase Dashboard -> SQL Editor -> New query
-- ===========================================================================

-- Limpieza previa (el orden respeta las llaves foraneas).
DROP TABLE IF EXISTS comentarios;
DROP TABLE IF EXISTS inscripciones;
DROP TABLE IF EXISTS eventos;
DROP TABLE IF EXISTS categorias;

-- ---------------------------------------------------------------------------
-- Categorias preestablecidas
-- ---------------------------------------------------------------------------
CREATE TABLE categorias (
    id          BIGSERIAL PRIMARY KEY,
    nombre      VARCHAR(80)  NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    created_at  TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- ---------------------------------------------------------------------------
-- Eventos
-- ---------------------------------------------------------------------------
CREATE TABLE eventos (
    id                BIGSERIAL PRIMARY KEY,
    titulo            VARCHAR(150) NOT NULL,
    descripcion       TEXT,
    categoria_id      BIGINT       NOT NULL REFERENCES categorias (id) ON DELETE RESTRICT,
    ubicacion         VARCHAR(150) NOT NULL,
    fecha_evento      TIMESTAMPTZ  NOT NULL,
    cupos_maximos     INTEGER      NOT NULL CHECK (cupos_maximos > 0),
    cupos_disponibles INTEGER      NOT NULL CHECK (cupos_disponibles >= 0),
    organizador       VARCHAR(120),
    imagen_url        VARCHAR(500),
    estado            VARCHAR(20)  NOT NULL DEFAULT 'activo'
                      CHECK (estado IN ('activo', 'cancelado', 'finalizado')),
    created_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),
    updated_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW(),

    -- El aforo disponible nunca puede exceder el aforo maximo.
    CONSTRAINT chk_cupos CHECK (cupos_disponibles <= cupos_maximos)
);

CREATE INDEX idx_eventos_categoria ON eventos (categoria_id);
CREATE INDEX idx_eventos_fecha     ON eventos (fecha_evento);
CREATE INDEX idx_eventos_estado    ON eventos (estado);

-- ---------------------------------------------------------------------------
-- Inscripciones (control de aforo en tiempo real)
-- ---------------------------------------------------------------------------
CREATE TABLE inscripciones (
    id                BIGSERIAL PRIMARY KEY,
    evento_id         BIGINT       NOT NULL REFERENCES eventos (id) ON DELETE CASCADE,
    nombre_estudiante VARCHAR(120) NOT NULL,
    matricula         VARCHAR(20),
    correo            VARCHAR(150) NOT NULL,
    telefono          VARCHAR(20),
    created_at        TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

-- Un mismo correo no puede inscribirse dos veces al mismo evento.
CREATE UNIQUE INDEX idx_inscripciones_unicas ON inscripciones (evento_id, LOWER(correo));
CREATE INDEX idx_inscripciones_evento ON inscripciones (evento_id);

-- ---------------------------------------------------------------------------
-- Comentarios
-- ---------------------------------------------------------------------------
CREATE TABLE comentarios (
    id         BIGSERIAL PRIMARY KEY,
    evento_id  BIGINT       NOT NULL REFERENCES eventos (id) ON DELETE CASCADE,
    autor      VARCHAR(120) NOT NULL,
    contenido  TEXT         NOT NULL CHECK (char_length(contenido) BETWEEN 3 AND 1000),
    created_at TIMESTAMPTZ  NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_comentarios_evento ON comentarios (evento_id, created_at DESC);

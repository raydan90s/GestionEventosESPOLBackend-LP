-- ===========================================================================
--  Datos iniciales: categorias preestablecidas + eventos de ejemplo
--  Ejecutar despues de schema.sql
-- ===========================================================================

INSERT INTO categorias (nombre, descripcion) VALUES
    ('Talleres academicos',      'Sesiones practicas de refuerzo academico y tecnico.'),
    ('Seminarios',               'Charlas y conferencias con expositores invitados.'),
    ('Clubes extracurriculares', 'Actividades organizadas por los clubes estudiantiles.'),
    ('Deportes',                 'Torneos, entrenamientos y actividades recreativas.'),
    ('Cultura y arte',           'Eventos artisticos, musicales y culturales del campus.'),
    ('Emprendimiento',           'Ferias, hackathons y espacios de innovacion.');

INSERT INTO eventos
    (titulo, descripcion, categoria_id, ubicacion, fecha_evento, cupos_maximos, cupos_disponibles, organizador)
VALUES
    ('Taller de introduccion a React',
     'Construccion de interfaces con componentes, hooks y consumo de APIs REST.',
     (SELECT id FROM categorias WHERE nombre = 'Talleres academicos'),
     'Laboratorio de Computo FIEC 1', NOW() + INTERVAL '10 days', 30, 30, 'Capitulo ACM ESPOL'),

    ('Seminario de Ciberseguridad Ofensiva',
     'Fundamentos de pruebas de penetracion y buenas practicas de seguridad.',
     (SELECT id FROM categorias WHERE nombre = 'Seminarios'),
     'Auditorio Jose Vicente Trujillo', NOW() + INTERVAL '15 days', 120, 120, 'FIEC'),

    ('Torneo interfacultades de futbol sala',
     'Competencia deportiva abierta a todas las facultades del campus.',
     (SELECT id FROM categorias WHERE nombre = 'Deportes'),
     'Coliseo ESPOL', NOW() + INTERVAL '20 days', 80, 80, 'Bienestar Estudiantil'),

    ('Feria de Emprendimiento Politecnico',
     'Exposicion de proyectos estudiantiles y ruedas de negocio.',
     (SELECT id FROM categorias WHERE nombre = 'Emprendimiento'),
     'Plaza del Estudiante', NOW() + INTERVAL '25 days', 200, 200, 'ESPOL Emprende');

INSERT INTO comentarios (evento_id, autor, contenido) VALUES
    ((SELECT id FROM eventos WHERE titulo = 'Taller de introduccion a React'),
     'Maria Salazar', 'Excelente iniciativa. Es necesario traer laptop propia?'),
    ((SELECT id FROM eventos WHERE titulo = 'Taller de introduccion a React'),
     'Andres Vera',   'Se entregara certificado de participacion?');

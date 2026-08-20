# GestionEventosESPOLBackend-LP

API RESTful nativa en **PHP** para la *Plataforma Web para la Organizacion y Gestion de Eventos ESPOL* (Proyecto Parcial - Equipo 3, FIEC).

Implementa la capa **Modelo** y **Controlador** del patron MVC; la **Vista** corresponde al frontend en React. La persistencia se hace sobre **PostgreSQL alojado en Supabase** mediante PDO.

---

## Estructura del proyecto

```
GestionEventosESPOLBackend-LP/
├── config/
│   └── config.php               # Configuracion (app, base de datos, CORS)
├── database/
│   ├── schema.sql               # Definicion de tablas para Supabase
│   └── seed.sql                 # Categorias preestablecidas y datos de prueba
├── docs/
│   └── Propuesta_Proyecto_Grupo3_Corregido.md
├── public/
│   ├── .htaccess                # Reescritura de URLs para Apache/XAMPP
│   └── index.php                # Front controller (punto de entrada unico)
├── routes/
│   └── api.php                  # Tabla de rutas de la API
├── src/
│   ├── Controllers/             # Controladores (validan y responden)
│   │   ├── CategoriaController.php
│   │   ├── EventoController.php
│   │   ├── FeedbackController.php
│   │   ├── HealthController.php
│   │   └── InscripcionController.php
│   ├── Services/                # Lógica de negocio (refactoring)
│   │   └── FeedbackService.php
│   ├── Core/                    # Micro-framework propio
│   │   ├── Exceptions/
│   │   │   ├── HttpException.php
│   │   │   └── ValidationException.php
│   │   ├── Middleware/
│   │   │   ├── Cors.php
│   │   │   └── RequestLogger.php  # Log de peticiones para desarrollo
│   │   ├── App.php              # Arranque y manejo global de errores
│   │   ├── Autoloader.php       # Autoload PSR-4 sin Composer
│   │   ├── Config.php
│   │   ├── Controller.php       # Controlador base
│   │   ├── Database.php         # Conexion PDO a Supabase
│   │   ├── Env.php              # Lector de .env
│   │   ├── Request.php
│   │   ├── Response.php
│   │   ├── Router.php
│   │   └── Validator.php
│   └── Models/                  # Modelos (acceso a datos y reglas de negocio)
│       ├── Categoria.php
│       ├── Evento.php
│       ├── Feedback.php
│       ├── Inscripcion.php
│       └── Model.php            # Modelo base
├── server.php                   # Router del servidor embebido de PHP
├── .env.example
└── README.md
```

### Flujo de una peticion

```
Cliente (React)
   -> public/index.php        (front controller)
   -> Core\App                (boot: .env, config, rutas)
   -> Core\Middleware\RequestLogger  (solo en desarrollo)
   -> Core\Middleware\Cors
   -> Core\Router             (resuelve metodo + ruta)
   -> Controllers\*           (valida con Core\Validator)
   -> Models\*                (consulta PDO a Supabase)
   -> Core\Response           (JSON)
```

---

## Requisitos

- PHP 8.0 o superior
- Extensiones `pdo_pgsql` y `pgsql` habilitadas
- Una base de datos PostgreSQL en Supabase

En XAMPP, abre `php.ini` y descomenta (quita el `;` inicial):

```ini
extension=pdo_pgsql
extension=pgsql
```

Verifica con:

```bash
php -m | findstr pgsql
```

---

## Instalacion

**1. Configurar las variables de entorno**

```bash
copy .env.example .env
```

Completa los datos de conexion desde el panel de Supabase
(*Project Settings → Database → Connection string → PSQL/Session pooler*):

```ini
DB_HOST=aws-0-us-east-1.pooler.supabase.com
DB_PORT=5432
DB_NAME=postgres
DB_USER=postgres.tuprojectref
DB_PASSWORD=tu_password
DB_SSLMODE=require
```

**2. Crear las tablas**

En Supabase → *SQL Editor* → *New query*, ejecuta primero
[database/schema.sql](database/schema.sql) y luego [database/seed.sql](database/seed.sql).

**3. Levantar el servidor**

Opcion A — servidor embebido de PHP (recomendado para desarrollo):

```bash
php -S localhost:8000 server.php
```

Opcion B — XAMPP: copia el proyecto en `htdocs/` y apunta el navegador a
`http://localhost/GestionEventosESPOLBackend-LP/public/api/health`.
Requiere `mod_rewrite` activo.

**4. Probar**

```bash
curl http://localhost:8000/api/health
```

---

## Ver las peticiones que llegan

El servidor embebido de PHP **no escribe ninguna linea de acceso para las peticiones
que resuelve `server.php`**: solo muestra `Accepted` y `Closing`, sin decir que
endpoint se pidio. Por eso el proyecto trae su propio log.

Con `APP_DEBUG=true` (o `LOG_REQUESTS=true` en el `.env`), cada peticion imprime una
linea en la terminal donde corre el servidor:

```
[10:47:50] GET    200 1243.2ms  /api/health
[10:48:14] GET    200 1664.0ms  /api/eventos/1/asistentes?q=vera
[10:47:52] POST   409 1925.7ms  /api/eventos/1/inscripciones  body={"nombre_estudiante":"Ana Vera","correo":"ana@espol.edu.ec"}
[10:47:52] GET    404    0.9ms  /api/ruta-que-no-existe
[10:48:15] OPTIONS 204    0.4ms  /api/eventos/1/inscripciones
```

Metodo, codigo de estado, duracion, ruta con su query string y el cuerpo JSON.
Tambien registra las peticiones de verificacion previa (`OPTIONS`), utiles cuando
falla el CORS. Bajo Apache/XAMPP la linea va al error log del servidor.

Para apagarlo sin tocar `APP_DEBUG`:

```ini
LOG_REQUESTS=false
```

> Dejalo apagado fuera de desarrollo: el cuerpo de una inscripcion lleva el correo
> y el telefono del estudiante. Las claves tipo `password` o `token` se enmascaran,
> y los cuerpos de mas de 500 caracteres se recortan.

### Los errores de PHP no se estan guardando

Aparte de lo anterior: XAMPP fija en su `php.ini`

```ini
error_log = C:\xampp\php\logs\php_error_log
log_errors = On
```

pero **ese directorio no existe**, asi que todo warning o error de PHP se descarta en
silencio. Se arregla creando la carpeta `C:\xampp\php\logs`.

---

## Endpoints

Todas las respuestas son JSON con el formato `{ "ok": bool, "data": ..., "message": ... }`.

### Diagnostico

| Metodo | Ruta | Descripcion |
| :--- | :--- | :--- |
| GET | `/api/health` | Estado de la API y de la conexion a Supabase |

### Categorias

| Metodo | Ruta | Descripcion |
| :--- | :--- | :--- |
| GET | `/api/categorias` | Lista las categorias con su total de eventos activos |
| GET | `/api/categorias/{id}` | Detalle de una categoria |

### Eventos — *Juliana Burgos*

| Metodo | Ruta | Descripcion |
| :--- | :--- | :--- |
| GET | `/api/eventos` | **Ver catalogo de eventos** (filtrable) |
| GET | `/api/eventos/{id}` | Detalle de un evento |
| POST | `/api/eventos` | **Crear evento** |
| PUT | `/api/eventos/{id}` | Actualizar un evento |
| DELETE | `/api/eventos/{id}` | Eliminar un evento |

Filtros de `GET /api/eventos` (query string):

| Parametro | Ejemplo | Descripcion |
| :--- | :--- | :--- |
| `categoria_id` | `2` | Filtra por categoria |
| `fecha_desde` / `fecha_hasta` | `2026-09-01` | Rango de fechas |
| `q` | `react` | Busqueda en titulo, descripcion y ubicacion |
| `estado` | `activo` | `activo`, `cancelado` o `finalizado` |
| `solo_disponibles` | `true` | Solo eventos con cupos libres |
| `solo_proximos` | `true` | Solo eventos futuros |
| `solo_pasados` | `true` | Solo eventos ya realizados, del mas reciente al mas antiguo (lo ignora `solo_proximos` si van juntos) |
| `limite` / `offset` | `20` / `0` | Paginacion (limite maximo 100) |

```bash
curl "http://localhost:8000/api/eventos?categoria_id=1&solo_disponibles=true"
```

Crear un evento:

```bash
curl -X POST http://localhost:8000/api/eventos \
  -H "Content-Type: application/json" \
  -d '{
        "titulo": "Taller de Git y GitHub",
        "descripcion": "Control de versiones para proyectos academicos.",
        "categoria_id": 1,
        "ubicacion": "Laboratorio FIEC 2",
        "fecha_evento": "2026-09-20T15:00",
        "cupos_maximos": 25,
        "organizador": "Capitulo ACM ESPOL"
      }'
```

### Inscripciones y asistentes — *Diego Parrales*

| Metodo | Ruta | Descripcion |
| :--- | :--- | :--- |
| POST | `/api/eventos/{id}/inscripciones` | **Registrar inscripcion** (descuenta un cupo) |
| GET | `/api/eventos/{id}/asistentes` | **Ver asistentes** del evento |
| DELETE | `/api/inscripciones/{id}` | Cancela la inscripcion y devuelve el cupo |

```bash
curl -X POST http://localhost:8000/api/eventos/1/inscripciones \
  -H "Content-Type: application/json" \
  -d '{
        "nombre_estudiante": "Diego Parrales",
        "matricula": "202312345",
        "correo": "dparrales@espol.edu.ec"
      }'
```

El control de aforo se resuelve dentro de una transaccion con `SELECT ... FOR UPDATE`
sobre la fila del evento, de modo que dos peticiones simultaneas no puedan tomar el
mismo ultimo cupo. Ademas se rechazan inscripciones duplicadas por correo, eventos no
activos y eventos ya realizados (HTTP 409).

`GET /api/eventos/{id}/asistentes` acepta `?q=` para buscar por nombre, matricula o correo.

### Comentarios (Feedback) — *Eimmy Ochoa*

| Metodo | Ruta | Descripcion |
| :--- | :--- | :--- |
| GET | `/api/eventos/{id}/comentarios` | **Ver comentarios** del evento |
| POST | `/api/eventos/{id}/comentarios` | **Escribir comentario** |
| DELETE | `/api/comentarios/{id}` | Eliminar un comentario |

```bash
curl -X POST http://localhost:8000/api/eventos/1/comentarios \
  -H "Content-Type: application/json" \
  -d '{"autor": "Eimmy Ochoa", "contenido": "Se requiere conocimiento previo?"}'
```

---

## Codigos de estado

| Codigo | Significado |
| :--- | :--- |
| 200 | Consulta u operacion exitosa |
| 201 | Recurso creado |
| 400 | Parametro o filtro invalido |
| 404 | Recurso o ruta inexistente |
| 405 | Metodo HTTP no permitido en esa ruta |
| 409 | Conflicto de negocio (sin cupos, inscripcion duplicada, evento inactivo) |
| 422 | Errores de validacion del payload |
| 500 | Error interno |

Respuesta de validacion fallida:

```json
{
  "ok": false,
  "message": "Los datos enviados no son validos",
  "errors": {
    "titulo": ["El campo titulo debe tener al menos 5 caracteres."],
    "cupos_maximos": ["El campo cupos_maximos es obligatorio."]
  }
}
```

---

## Equipo

| Integrante | Requerimientos funcionales |
| :--- | :--- |
| Juliana Burgos | Crear evento · Ver catalogo de eventos |
| Eimmy Ochoa | Escribir comentario de evento · Ver comentarios de evento |
| Diego Parrales | Registrar inscripcion · Ver asistentes |

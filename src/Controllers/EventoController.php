<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Evento;

/**
 * RF "Crear evento" y "Ver catalogo de eventos" (Juliana Burgos).
 */
final class EventoController extends Controller
{
    private const ESTADOS = ['activo', 'cancelado', 'finalizado'];

    private Evento $eventos;

    public function __construct()
    {
        $this->eventos = new Evento();
    }

    /**
     * GET /api/eventos
     * Filtros: categoria_id, fecha_desde, fecha_hasta, q, estado,
     *          solo_disponibles, solo_proximos, solo_pasados, limite, offset
     */
    public function index(Request $request): void
    {
        $filtros = [
            'categoria_id'     => $request->query('categoria_id'),
            'q'                => $request->query('q'),
            'estado'           => $request->query('estado'),
            'solo_disponibles' => filter_var($request->query('solo_disponibles', false), FILTER_VALIDATE_BOOLEAN),
            'solo_proximos'    => filter_var($request->query('solo_proximos', false), FILTER_VALIDATE_BOOLEAN),
            'solo_pasados'     => filter_var($request->query('solo_pasados', false), FILTER_VALIDATE_BOOLEAN),
            'limite'           => $request->query('limite'),
            'offset'           => $request->query('offset'),
        ];

        foreach (['fecha_desde', 'fecha_hasta'] as $campo) {
            $valor = $request->query($campo);

            if ($valor === null) {
                continue;
            }

            $fecha = Validator::parseDate((string) $valor);

            if ($fecha === null) {
                throw HttpException::badRequest(sprintf('El filtro "%s" no tiene un formato de fecha valido.', $campo));
            }

            $filtros[$campo] = $fecha->format('Y-m-d H:i:sP');
        }

        if ($filtros['categoria_id'] !== null && filter_var($filtros['categoria_id'], FILTER_VALIDATE_INT) === false) {
            throw HttpException::badRequest('El filtro "categoria_id" debe ser numerico.');
        }

        if ($filtros['estado'] !== null && !in_array($filtros['estado'], self::ESTADOS, true)) {
            throw HttpException::badRequest('El filtro "estado" debe ser: ' . implode(', ', self::ESTADOS) . '.');
        }

        $eventos = $this->eventos->catalogo($filtros);

        // El total es el de la consulta completa, no el de esta pagina: con
        // 60 eventos y limite 50, count($eventos) diria 50 y el frontend no
        // podria saber que faltan 10 mas.
        Response::json([
            'ok'    => true,
            'total' => $this->eventos->contarCatalogo($filtros),
            'data'  => $eventos,
        ]);
    }

    /** GET /api/eventos/{id} */
    public function show(Request $request): void
    {
        $evento = $this->eventos->detalle($this->idParam($request));

        if ($evento === null) {
            throw HttpException::notFound('El evento solicitado no existe.');
        }

        $this->ok($evento);
    }

    /** POST /api/eventos */
    public function store(Request $request): void
    {
        // Las etiquetas son las que ve el organizador en el formulario: sin ellas
        // el mensaje de error mostraria el nombre de la columna ("cupos_maximos").
        $datos = Validator::make($request->body())
            ->required('titulo', 'titulo')->string('titulo', 5, 150, 'titulo')
            ->required('categoria_id', 'categoria')->integer('categoria_id', 1, null, 'categoria')
            ->required('ubicacion', 'lugar')->string('ubicacion', 3, 150, 'lugar')
            ->required('fecha_evento', 'fecha')->datetime('fecha_evento', true, 'fecha')
            ->required('cupos_maximos', 'aforo')->integer('cupos_maximos', 1, 10000, 'aforo')
            ->optionalString('descripcion', 2000, 'descripcion')
            ->optionalString('organizador', 120, 'organizador')
            ->optionalString('imagen_url', 500, 'imagen')
            ->in('estado', self::ESTADOS, 'estado')
            ->validated();

        // La categoria se valida dentro del modelo, igual que Inscripcion
        // valida sus reglas de negocio antes de escribir en la base de datos.
        $this->created($this->eventos->crear($datos), 'Evento creado correctamente.');
    }

    /** PUT /api/eventos/{id} */
    public function update(Request $request): void
    {
        $id = $this->idParam($request);

        $datos = Validator::make($request->body())
            ->string('titulo', 5, 150, 'titulo')
            ->integer('categoria_id', 1, null, 'categoria')
            ->string('ubicacion', 3, 150, 'lugar')
            ->datetime('fecha_evento', false, 'fecha')
            ->integer('cupos_maximos', 1, 10000, 'aforo')
            ->optionalString('descripcion', 2000, 'descripcion')
            ->optionalString('organizador', 120, 'organizador')
            ->optionalString('imagen_url', 500, 'imagen')
            ->in('estado', self::ESTADOS, 'estado')
            ->validated();

        // Existencia del evento, existencia de la categoria y el chequeo de
        // aforo vs. inscritos ocurren dentro del modelo, bajo la misma
        // transaccion que ajusta los cupos.
        $this->ok($this->eventos->actualizar($id, $datos), 'Evento actualizado correctamente.');
    }

    /** Extension por MIME real; sólo estos tres se aceptan. */
    private const EXTENSIONES_IMAGEN = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    private const LIMITE_IMAGEN_BYTES = 2 * 1024 * 1024; // 2 MB

    /**
     * POST /api/eventos/{id}/imagen (multipart/form-data, campo "imagen")
     *
     * Aparte de POST /api/eventos a propósito: así `createEvent` sigue
     * enviando JSON como siempre, sin convertir todo el formulario a
     * multipart por un campo opcional.
     */
    public function imagen(Request $request): void
    {
        // 1. El evento tiene que existir antes de aceptar nada.
        $id = $this->idParam($request);
        $evento = $this->eventos->detalle($id);

        if ($evento === null) {
            throw HttpException::notFound('El evento indicado no existe.');
        }

        $archivo = $request->file('imagen');

        if ($archivo === null) {
            throw new HttpException(422, 'No se recibió ningún archivo en el campo "imagen".');
        }

        // 2. UPLOAD_ERR_INI_SIZE es el limite de php.ini (2 MB por defecto en
        // XAMPP), distinto del limite propio de 2 MB que se aplica despues:
        // este puede saltar antes incluso de que $archivo['size'] sea fiable.
        if ((int) $archivo['error'] === UPLOAD_ERR_INI_SIZE) {
            throw new HttpException(422, sprintf(
                'La imagen supera el límite de subida del servidor (%s). Reduce el tamaño e intenta de nuevo.',
                ini_get('upload_max_filesize')
            ));
        }

        if ((int) $archivo['error'] !== UPLOAD_ERR_OK) {
            throw new HttpException(422, 'No se pudo recibir el archivo. Intenta de nuevo.');
        }

        // 3. El tipo se valida por el contenido real, no por el nombre ni por
        // el "type" que manda el navegador: cualquiera de los dos permite
        // subir cualquier cosa con la extension cambiada a .jpg.
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $archivo['tmp_name']);
        $extension = self::EXTENSIONES_IMAGEN[$mime] ?? null;

        if ($extension === null) {
            throw new HttpException(422, 'La imagen debe ser JPEG, PNG o WEBP.');
        }

        // 4. Limite propio, independiente del de php.ini.
        if ((int) $archivo['size'] > self::LIMITE_IMAGEN_BYTES) {
            throw new HttpException(422, 'La imagen no puede superar los 2 MB.');
        }

        // 5. Nombre generado por el servidor, no el que trae el usuario: un
        // nombre de usuario puede traer "../" o caracteres fuera de la carpeta.
        $nombreArchivo = sprintf('%d-%s.%s', $id, bin2hex(random_bytes(8)), $extension);
        $carpetaStorage = dirname(__DIR__, 2) . '/public/storage/eventos';
        $rutaDestino = $carpetaStorage . '/' . $nombreArchivo;

        // 6. Al mover el archivo nuevo, se borra el anterior para no dejar
        // huerfanos en el storage.
        if (!move_uploaded_file($archivo['tmp_name'], $rutaDestino)) {
            throw new HttpException(500, 'No se pudo guardar la imagen en el servidor.');
        }

        $imagenAnterior = $evento['imagen_url'] ?? null;
        if (is_string($imagenAnterior) && $imagenAnterior !== '') {
            $rutaAnterior = dirname(__DIR__, 2) . '/public/' . $imagenAnterior;
            if (is_file($rutaAnterior)) {
                unlink($rutaAnterior);
            }
        }

        // 7. Ruta relativa, no absoluta: una URL con "localhost:8000" dentro
        // dejaria de funcionar en cuanto otro integrante levante el backend
        // en otro puerto.
        $actualizado = $this->eventos->actualizar($id, [
            'imagen_url' => 'storage/eventos/' . $nombreArchivo,
        ]);

        $this->ok($actualizado, 'Imagen actualizada correctamente.');
    }

    /** DELETE /api/eventos/{id} */
    public function destroy(Request $request): void
    {
        $id = $this->idParam($request);

        if (!$this->eventos->delete($id)) {
            throw HttpException::notFound('El evento que intenta eliminar no existe.');
        }

        $this->ok(['id' => $id], 'Evento eliminado correctamente.');
    }
}

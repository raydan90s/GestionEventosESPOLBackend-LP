<?php

declare(strict_types=1);

use App\Controllers\CategoriaController;
use App\Controllers\FeedbackController;
use App\Controllers\EventoController;
use App\Controllers\HealthController;
use App\Controllers\InscripcionController;
use App\Core\Router;

/**
 * Definicion de rutas de la API REST.
 *
 * Convencion: /api/<recurso>[/{id}][/<subrecurso>]
 */
return static function (Router $router): void {
    // --- Diagnostico ---------------------------------------------------------
    $router->get('/api/health', [HealthController::class, 'index']);

    // --- Categorias preestablecidas -----------------------------------------
    $router->get('/api/categorias', [CategoriaController::class, 'index']);
    $router->get('/api/categorias/{id}', [CategoriaController::class, 'show']);

    // --- Eventos (Juliana Burgos) -------------------------------------------
    $router->get('/api/eventos', [EventoController::class, 'index']);      // Ver catalogo filtrable
    $router->get('/api/eventos/{id}', [EventoController::class, 'show']);
    $router->post('/api/eventos', [EventoController::class, 'store']);     // Crear evento
    $router->put('/api/eventos/{id}', [EventoController::class, 'update']);
    $router->post('/api/eventos/{id}/imagen', [EventoController::class, 'imagen']); // Subir imagen
    $router->delete('/api/eventos/{id}', [EventoController::class, 'destroy']);

    // --- Inscripciones y asistentes (Diego Parrales) ------------------------
    $router->post('/api/eventos/{id}/inscripciones', [InscripcionController::class, 'store']);   // Registrar inscripcion
    $router->get('/api/eventos/{id}/asistentes', [InscripcionController::class, 'asistentes']);  // Ver asistentes
    $router->delete('/api/inscripciones/{id}', [InscripcionController::class, 'destroy']);

    // --- Comentarios (Eimmy Ochoa) ------------------------------------------
    $router->get('/api/eventos/{id}/comentarios', [FeedbackController::class, 'index']);   // Ver comentarios
    $router->post('/api/eventos/{id}/comentarios', [FeedbackController::class, 'store']);  // Escribir comentario
    $router->delete('/api/comentarios/{id}', [FeedbackController::class, 'destroy']);
};

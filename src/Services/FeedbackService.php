<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Exceptions\HttpException;
use App\Models\Feedback;
use App\Models\Evento;

/**
 * Servicio para manejar la lógica de negocio de los comentarios (Eimmy Ochoa).
 */
final class FeedbackService
{
    private Feedback $feedbackModel;
    private Evento $eventoModel;

    public function __construct()
    {
        $this->feedbackModel = new Feedback();
        $this->eventoModel = new Evento();
    }

    public function obtenerParaEvento(int $idEvento, int $limite = 50, int $offset = 0): array
    {
        if (!$this->eventoModel->exists($idEvento)) {
            throw HttpException::notFound('El evento solicitado no existe.');
        }

        $limite = max(1, min(100, $limite));
        $offset = max(0, $offset);

        return [
            'total' => $this->feedbackModel->contarPorEvento($idEvento),
            'data'  => $this->feedbackModel->obtenerListaPorEvento($idEvento, $limite, $offset),
        ];
    }

    public function guardar(int $idEvento, array $datosValidados): array
    {
        if (!$this->eventoModel->exists($idEvento)) {
            throw HttpException::notFound('El evento sobre el que intenta comentar no existe.');
        }

        return $this->feedbackModel->insertar($idEvento, $datosValidados);
    }

    public function borrar(int $idFeedback): void
    {
        if (!$this->feedbackModel->delete($idFeedback)) {
            throw HttpException::notFound('El comentario que intenta eliminar no existe.');
        }
    }
}

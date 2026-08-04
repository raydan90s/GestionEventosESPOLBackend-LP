<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Exceptions\HttpException;
use App\Core\Request;
use App\Models\Categoria;

/**
 * Categorias preestablecidas usadas para estructurar el catalogo.
 */
final class CategoriaController extends Controller
{
    private Categoria $categorias;

    public function __construct()
    {
        $this->categorias = new Categoria();
    }

    /** GET /api/categorias */
    public function index(Request $request): void
    {
        $this->ok($this->categorias->all());
    }

    /** GET /api/categorias/{id} */
    public function show(Request $request): void
    {
        $categoria = $this->categorias->find($this->idParam($request));

        if ($categoria === null) {
            throw HttpException::notFound('La categoria solicitada no existe.');
        }

        $this->ok($categoria);
    }
}

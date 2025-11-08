<?php

namespace App\Http\Controllers;

use App\Application\UseCases\CreateProductUseCase;
use App\Http\Requests\CreateProductRequest; // Request para validar
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    // O Controller recebe o UseCase, não o Repositório
    public function __construct(
        private CreateProductUseCase $createProductUseCase
    ) {}

    public function store(CreateProductRequest $request): JsonResponse
    {
        // 1. O Request valida os dados
        $validated = $request->validated();

        // 2. O Controller só chama o UseCase
        $product = $this->createProductUseCase->execute(
            $validated['name'],
            $validated['price']
        );

        // 3. Retorna a resposta
        return response()->json($product, 201);
    }
}

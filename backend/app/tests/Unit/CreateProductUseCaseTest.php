<?php

use App\Application\UseCases\CreateProductUseCase;
use App\Domain\Entities\Product;
use App\Domain\Repositories\ProductRepositoryInterface;

test('it creates a product', function () {
    // 1. Arrange (Preparar)
    
    // Criamos um "Mock" (dublê) do repositório.
    // O teste NÃO VAI tocar no banco de dados.
    $mockRepo = Mockery::mock(ProductRepositoryInterface::class);
    
    // Esperamos que o método 'save' seja chamado 1 vez
    // com um objeto Produto que tenha 'Test Product' e 100
    $mockRepo->shouldReceive('save')
        ->once()
        ->withArgs(function (Product $product) {
            return $product->name === 'Test Product' && $product->price === 100;
        })
        ->andReturn(new Product(1, 'Test Product', 100)); // E retorne isso

    // 2. Act (Agir)
    // Instanciamos o UseCase com o Mock
    $useCase = new CreateProductUseCase($mockRepo);
    $result = $useCase->execute('Test Product', 100);

    // 3. Assert (Verificar)
    expect($result)->toBeInstanceOf(Product::class);
    expect($result->id)->toBe(1);
    expect($result->name)->toBe('Test Product');
});

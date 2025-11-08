<?php

namespace Tests\Unit;

use App\Application\UseCases\CreateProductUseCase;
use App\Domain\Entities\Product;
use App\Domain\Repositories\ProductRepositoryInterface;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreateProductUseCaseTest extends TestCase
{
    public function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }


    #[Test]
    public function it_creates_a_product(): void
    {
        // 1. Arrange (Preparar)
        $mockRepo = Mockery::mock(ProductRepositoryInterface::class);
        
        $mockRepo->shouldReceive('save')
            ->once()
->withArgs(function (Product $product) {
    return $product->id === null &&
           $product->name === 'Test Product' &&
           $product->price === 100.0;
})->andReturn(new Product(1, 'Test Product', 100.0));

        // 2. Act (Agir)
        $useCase = new CreateProductUseCase($mockRepo);
        $result = $useCase->execute('Test Product', 100);

        // 3. Assert (Verificar)
        // No PHPUnit clássico, usamos $this->...
        $this->assertInstanceOf(Product::class, $result);
        $this->assertEquals(1, $result->id);
        $this->assertEquals('Test Product', $result->name);
    }
}

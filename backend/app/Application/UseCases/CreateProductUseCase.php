<?php

namespace App\Application\UseCases;

use App\Domain\Entities\Product;
use App\Domain\Repositories\ProductRepositoryInterface;

class CreateProductUseCase
{
    // Recebe a INTERFACE, não a implementação Eloquent
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    public function execute(string $name, float $price): Product
    {
        // Aqui poderiam ter validações complexas, eventos, etc.
        $product = new Product(
            id: null,
            name: $name,
            price: $price
        );

        return $this->productRepository->save($product);
    }
}

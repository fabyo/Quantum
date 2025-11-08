<?php

namespace App\Domain\Repositories;

use App\Domain\Entities\Product;

interface ProductRepositoryInterface
{
    public function save(Product $product): Product;
    public function findById(int $id): ?Product;
}

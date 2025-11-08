<?php

namespace App\Domain\Entities;

// Nota: Sem "extends Model". É PHP puro.
class Product
{
    public function __construct(
        public ?int $id,
        public string $name,
        public float $price
    ) {}
}

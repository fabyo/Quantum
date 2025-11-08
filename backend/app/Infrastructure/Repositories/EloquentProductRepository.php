<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Entities\Product as ProductEntity;
use App\Domain\Repositories\ProductRepositoryInterface;
use App\Models\Product as ProductModel; // O Model Eloquent

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function save(ProductEntity $product): ProductEntity
    {
        // Converte a Entidade do Domain para o Model Eloquent
        $model = ProductModel::updateOrCreate(
            ['id' => $product->id], // Procura pelo ID
            ['name' => $product->name, 'price' => $product->price] // Atualiza/Cria
        );

        // Retorna a Entidade atualizada (importante)
        $product->id = $model->id;
        return $product;
    }

    public function findById(int $id): ?ProductEntity
    {
        $model = ProductModel::find($id);

        if (!$model) {
            return null;
        }

        // Converte o Model Eloquent para a Entidade do Domain
        return new ProductEntity(
            id: $model->id,
            name: $model->name,
            price: $model->price
        );
    }
}

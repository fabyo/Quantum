<?php

use App\Models\User; // Model Eloquent
use Laravel\Sanctum\Sanctum;

test('an authenticated user can create a product', function () {
    // 1. Arrange
    // Criamos um usuário real no banco de dados de teste
    $user = User::factory()->create();

    // "Logamos" esse usuário para a requisição (via Sanctum)
    Sanctum::actingAs($user);

    $productData = [
        'name' => 'New Product',
        'price' => 199.99,
    ];

    // 2. Act
    // Fazemos uma chamada HTTP real para a API
    $response = $this->postJson('/api/products', $productData);

    // 3. Assert
    // Verificamos a resposta HTTP
    $response->assertStatus(201)
        ->assertJson([
            'name' => 'New Product',
            'price' => 199.99,
        ]);

    // Verificamos se o produto foi salvo NO BANCO DE DADOS
    $this->assertDatabaseHas('products', [
        'name' => 'New Product',
        'price' => 199.99,
    ]);
});

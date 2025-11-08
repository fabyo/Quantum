<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\HasApiTokens;
use Laravel\Sanctum\Sanctum;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductCreationTest extends TestCase
{
    use RefreshDatabase; 

    #[Test]
    public function an_authenticated_user_can_create_a_product(): void
    {
        // 1. Arrange
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $productData = [
            'name' => 'New Product',
            'price' => 199.99,
        ];

        // 2. Act
        $response = $this->postJson('/api/products', $productData);

        // 3. Assert
        $response->assertStatus(201)
            ->assertJson([
                'name' => 'New Product',
                'price' => 199.99,
            ]);

        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'price' => 199.99,
        ]);
    }

    #[Test]
    public function an_unauthenticated_user_cannot_create_a_product(): void
    {
        // 1. Arrange
        $productData = ['name' => 'Stolen Product', 'price' => 10];
        
        // 2. Act
        $response = $this->postJson('/api/products', $productData);

        // 3. Assert
        $response->assertStatus(401); 
        $this->assertDatabaseMissing('products', [
            'name' => 'Stolen Product',
        ]);
    }
}

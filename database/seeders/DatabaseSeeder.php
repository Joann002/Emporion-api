<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\ProductCategory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Créer un utilisateur admin
        User::create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('password'),
        ]);

        // Créer des catégories de produits
        $electronics = ProductCategory::create([
            'name' => 'Électronique',
            'description' => 'Appareils électroniques et accessoires',
        ]);

        $clothing = ProductCategory::create([
            'name' => 'Vêtements',
            'description' => 'Vêtements et accessoires de mode',
        ]);

        $books = ProductCategory::create([
            'name' => 'Livres',
            'description' => 'Livres et magazines',
        ]);

        // Créer des produits
        Product::create([
            'name' => 'Smartphone XYZ',
            'description' => 'Smartphone dernière génération',
            'price' => 599.99,
            'stock_quantity' => 50,
            'category_id' => $electronics->id,
        ]);

        Product::create([
            'name' => 'Écouteurs Bluetooth',
            'description' => 'Écouteurs sans fil avec réduction de bruit',
            'price' => 89.99,
            'stock_quantity' => 100,
            'category_id' => $electronics->id,
        ]);

        Product::create([
            'name' => 'T-shirt Premium',
            'description' => 'T-shirt en coton bio',
            'price' => 29.99,
            'stock_quantity' => 200,
            'category_id' => $clothing->id,
        ]);

        Product::create([
            'name' => 'Jean Slim',
            'description' => 'Jean coupe slim confortable',
            'price' => 79.99,
            'stock_quantity' => 75,
            'category_id' => $clothing->id,
        ]);

        Product::create([
            'name' => 'Guide Laravel',
            'description' => 'Guide complet pour Laravel',
            'price' => 39.99,
            'stock_quantity' => 30,
            'category_id' => $books->id,
        ]);

        // Créer des clients
        Client::create([
            'name' => 'Jean Dupont',
            'email' => 'jean.dupont@example.com',
            'phone' => '0612345678',
            'address' => '123 Rue de la Paix, 75001 Paris',
        ]);

        Client::create([
            'name' => 'Marie Martin',
            'email' => 'marie.martin@example.com',
            'phone' => '0687654321',
            'address' => '456 Avenue des Champs, 69002 Lyon',
        ]);

        Client::create([
            'name' => 'Pierre Durand',
            'email' => 'pierre.durand@example.com',
            'phone' => '0698765432',
            'address' => '789 Boulevard Victor Hugo, 31000 Toulouse',
        ]);
    }
}

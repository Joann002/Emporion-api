<?php

namespace Database\Seeders;

use App\Models\Client;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database with a realistic test dataset:
     * users, categories, products (incl. low/zero stock), clients, and
     * ~30 orders spread over the last 6 months with mixed statuses,
     * order lines, and the matching stock movements.
     */
    public function run(): void
    {
        // Make the seeder safe to re-run (`php artisan db:seed`): wipe the demo
        // data first, in FK-safe order.
        Schema::disableForeignKeyConstraints();
        foreach (['stock_movements', 'order_lines', 'orders', 'products', 'product_categories', 'clients'] as $table) {
            DB::table($table)->truncate();
        }
        Schema::enableForeignKeyConstraints();

        // ---- Users (idempotent: never duplicates an existing email) ---------
        User::updateOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin', 'password' => Hash::make('password')],
        );

        User::updateOrCreate(
            ['email' => 'demo@emporion.test'],
            ['name' => 'Démo Emporion', 'password' => Hash::make('password')],
        );

        // ---- Categories -----------------------------------------------------
        $categories = collect([
            'Électronique' => 'Appareils électroniques et accessoires',
            'Vêtements' => 'Vêtements et accessoires de mode',
            'Livres' => 'Livres et magazines',
            'Maison & Cuisine' => 'Équipement et décoration pour la maison',
            'Sport & Loisirs' => 'Articles de sport et de plein air',
        ])->map(fn ($desc, $name) => ProductCategory::create([
            'name' => $name,
            'description' => $desc,
        ]));

        // ---- Products (name, category, price, stock) ------------------------
        // Some products are intentionally low (<10) or out of stock (0) to
        // exercise the dashboard "low stock" alerts.
        $productData = [
            ['Smartphone Galaxy S', 'Électronique', 699.99, 42],
            ['Casque Bluetooth ANC', 'Électronique', 149.99, 8],   // low
            ['Chargeur USB-C 65W', 'Électronique', 29.99, 120],
            ['Montre connectée Pro', 'Électronique', 219.00, 0],   // out
            ['T-shirt coton bio', 'Vêtements', 24.99, 200],
            ['Jean slim brut', 'Vêtements', 79.90, 60],
            ['Veste mi-saison', 'Vêtements', 119.00, 5],           // low
            ['Baskets running', 'Vêtements', 89.99, 35],
            ['Guide Laravel complet', 'Livres', 39.99, 25],
            ['Roman policier — L\'Ombre', 'Livres', 18.50, 70],
            ['BD aventure tome 1', 'Livres', 14.90, 3],            // low
            ['Cafetière italienne', 'Maison & Cuisine', 34.90, 48],
            ['Set de couteaux inox', 'Maison & Cuisine', 59.00, 18],
            ['Bougie parfumée vanille', 'Maison & Cuisine', 12.50, 0], // out
            ['Tapis de yoga premium', 'Sport & Loisirs', 44.99, 27],
            ['Gourde inox 1L', 'Sport & Loisirs', 19.90, 90],
        ];

        $products = collect($productData)->map(fn ($p) => Product::create([
            'name' => $p[0],
            'description' => $p[0].' — produit de démonstration.',
            'price' => $p[2],
            'stock_quantity' => $p[3],
            'category_id' => $categories[$p[1]]->id,
        ]));

        // Initial "in" stock movements so the stock ledger is not empty.
        foreach ($products as $product) {
            if ($product->stock_quantity > 0) {
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'in',
                    'quantity' => $product->stock_quantity,
                    'reason' => 'Stock initial',
                    'date' => Carbon::now()->subMonths(6),
                ]);
            }
        }

        // ---- Clients --------------------------------------------------------
        $clientData = [
            ['Jean Dupont', 'jean.dupont@example.com', '0612345678', '123 Rue de la Paix, 75001 Paris'],
            ['Marie Martin', 'marie.martin@example.com', '0687654321', '456 Avenue des Champs, 69002 Lyon'],
            ['Pierre Durand', 'pierre.durand@example.com', '0698765432', '789 Boulevard Victor Hugo, 31000 Toulouse'],
            ['Sophie Bernard', 'sophie.bernard@example.com', '0611223344', '12 Rue Nationale, 59000 Lille'],
            ['Lucas Petit', 'lucas.petit@example.com', '0655667788', '34 Cours Mirabeau, 13100 Aix-en-Provence'],
            ['Camille Robert', 'camille.robert@example.com', '0622334455', '8 Place Bellecour, 69002 Lyon'],
            ['Thomas Richard', 'thomas.richard@example.com', '0633445566', '56 Rue du Commerce, 44000 Nantes'],
            ['Julie Moreau', 'julie.moreau@example.com', '0644556677', '90 Avenue Jean Jaurès, 33000 Bordeaux'],
            ['Antoine Laurent', 'antoine.laurent@example.com', '0677889900', '3 Rue Sainte-Catherine, 67000 Strasbourg'],
            ['Emma Simon', 'emma.simon@example.com', '0699887766', '21 Rue de la République, 13001 Marseille'],
        ];

        $clients = collect($clientData)->map(fn ($c) => Client::create([
            'name' => $c[0],
            'email' => $c[1],
            'phone' => $c[2],
            'address' => $c[3],
        ]));

        // ---- Orders ---------------------------------------------------------
        // Track live stock in memory so "paid" orders decrement realistically.
        $stock = $products->mapWithKeys(fn ($p) => [$p->id => $p->stock_quantity]);

        mt_srand(2026); // deterministic dataset
        $statuses = array_merge(
            array_fill(0, 6, 'paid'),
            array_fill(0, 3, 'pending'),
            array_fill(0, 2, 'cancelled'),
        );

        for ($i = 0; $i < 32; $i++) {
            $client = $clients[mt_rand(0, $clients->count() - 1)];
            $createdAt = Carbon::now()->subDays(mt_rand(0, 178))->setTime(mt_rand(8, 19), mt_rand(0, 59));
            $status = $statuses[mt_rand(0, count($statuses) - 1)];

            $order = Order::create([
                'client_id' => $client->id,
                'status' => 'pending',
                'total' => 0,
            ]);

            // Deterministic distinct sample of products (seeded Fisher-Yates).
            $indices = range(0, $products->count() - 1);
            for ($k = count($indices) - 1; $k > 0; $k--) {
                $j = mt_rand(0, $k);
                [$indices[$k], $indices[$j]] = [$indices[$j], $indices[$k]];
            }
            $lineProducts = collect(array_slice($indices, 0, mt_rand(1, 4)))
                ->map(fn ($idx) => $products[$idx]);
            $total = 0;

            foreach ($lineProducts as $product) {
                $quantity = mt_rand(1, 5);

                // For paid orders, respect available stock.
                if ($status === 'paid') {
                    if ($stock[$product->id] <= 0) {
                        continue;
                    }
                    $quantity = min($quantity, $stock[$product->id]);
                }

                OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                ]);

                $total += $quantity * $product->price;

                if ($status === 'paid') {
                    $stock[$product->id] -= $quantity;
                    StockMovement::create([
                        'product_id' => $product->id,
                        'type' => 'out',
                        'quantity' => $quantity,
                        'reason' => "Commande #{$order->id}",
                        'date' => $createdAt,
                    ]);
                }
            }

            // Skip empty orders (can happen if a paid order hit only empty stock).
            if ($order->orderLines()->count() === 0) {
                $order->delete();
                continue;
            }

            $order->update(['status' => $status, 'total' => $total]);

            // Backdate timestamps (bypass Eloquent auto-timestamps).
            DB::table('orders')->where('id', $order->id)->update([
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);
        }

        // Persist the decremented stock back to products.
        foreach ($products as $product) {
            $product->update(['stock_quantity' => max(0, $stock[$product->id])]);
        }

        $this->command->info('Seed terminé : '.User::count().' utilisateurs, '
            .ProductCategory::count().' catégories, '
            .Product::count().' produits, '
            .Client::count().' clients, '
            .Order::count().' commandes, '
            .OrderLine::count().' lignes, '
            .StockMovement::count().' mouvements de stock.');
    }
}

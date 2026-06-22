<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    public function index()
    {
        $movements = StockMovement::with('product')
            ->orderBy('date', 'desc')
            ->paginate(20);

        return response()->json($movements);
    }

    public function byProduct(Product $product)
    {
        $movements = $product->stockMovements()
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'product' => $product,
            'movements' => $movements,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'type' => 'required|in:in,out',
            'quantity' => 'required|integer|min:1',
            'reason' => 'required|string',
        ]);

        return DB::transaction(function () use ($validated) {
            $product = Product::findOrFail($validated['product_id']);

            // Créer le mouvement de stock
            $movement = StockMovement::create([
                'product_id' => $validated['product_id'],
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason' => $validated['reason'],
                'date' => now(),
            ]);

            // Mettre à jour le stock du produit
            if ($validated['type'] === 'in') {
                $product->increment('stock_quantity', $validated['quantity']);
            } else {
                if ($product->stock_quantity < $validated['quantity']) {
                    throw new \Exception('Stock insuffisant');
                }
                $product->decrement('stock_quantity', $validated['quantity']);
            }

            return response()->json([
                'movement' => $movement,
                'product' => $product->fresh(),
            ], 201);
        });
    }
}

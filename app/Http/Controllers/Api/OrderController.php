<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        $orders = Order::with(['client', 'orderLines.product'])
            ->orderBy('created_at', 'desc')
            ->paginate(15);
        
        return OrderResource::collection($orders);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'client_id' => 'required|exists:clients,id',
            'lines' => 'required|array|min:1',
            'lines.*.product_id' => 'required|exists:products,id',
            'lines.*.quantity' => 'required|integer|min:1',
        ]);

        return DB::transaction(function () use ($validated) {
            // Créer la commande
            $order = Order::create([
                'client_id' => $validated['client_id'],
                'status' => 'pending',
                'total' => 0,
            ]);

            $total = 0;

            // Créer les lignes de commande
            foreach ($validated['lines'] as $line) {
                $product = Product::findOrFail($line['product_id']);

                $orderLine = OrderLine::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $line['quantity'],
                    'unit_price' => $product->price,
                ]);

                $total += $orderLine->quantity * $orderLine->unit_price;
            }

            // Mettre à jour le total de la commande
            $order->update(['total' => $total]);

            return new OrderResource($order->load(['client', 'orderLines.product']));
        });
    }

    public function show(Order $order)
    {
        return new OrderResource($order->load(['client', 'orderLines.product']));
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'status' => 'sometimes|required|in:pending,paid,cancelled',
        ]);

        $order->update($validated);

        return new OrderResource($order->load(['client', 'orderLines.product']));
    }

    public function validate(Order $order)
    {
        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'Seules les commandes en attente peuvent être validées',
            ], 400);
        }

        return DB::transaction(function () use ($order) {
            // Vérifier et décrémenter le stock pour chaque ligne
            foreach ($order->orderLines as $line) {
                $product = $line->product;

                if ($product->stock_quantity < $line->quantity) {
                    throw new \Exception("Stock insuffisant pour le produit: {$product->name}");
                }

                // Décrémenter le stock
                $product->decrement('stock_quantity', $line->quantity);

                // Créer un mouvement de stock
                StockMovement::create([
                    'product_id' => $product->id,
                    'type' => 'out',
                    'quantity' => $line->quantity,
                    'reason' => "Commande #{$order->id}",
                    'date' => now(),
                ]);
            }

            // Mettre à jour le statut de la commande
            $order->update(['status' => 'paid']);

            return new OrderResource($order->load(['client', 'orderLines.product']));
        });
    }

    public function destroy(Order $order)
    {
        if ($order->status === 'paid') {
            return response()->json([
                'message' => 'Les commandes payées ne peuvent pas être supprimées',
            ], 400);
        }

        $order->delete();

        return response()->json([
            'message' => 'Commande supprimée avec succès',
        ]);
    }
}

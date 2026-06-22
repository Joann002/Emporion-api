<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Order;
use App\Models\OrderLine;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class StatsController extends Controller
{
    public function index()
    {
        // Statistiques générales
        $totalClients = Client::count();
        $totalProducts = Product::count();
        $totalOrders = Order::count();
        $totalRevenue = Order::where('status', 'paid')->sum('total');

        // Top 5 produits les plus vendus
        $topProducts = OrderLine::select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->limit(5)
            ->with('product')
            ->get()
            ->map(function ($line) {
                return [
                    'product' => $line->product->name,
                    'quantity' => $line->total_sold,
                ];
            });

        // CA par mois (6 derniers mois)
        $revenueByMonth = Order::where('status', 'paid')
            ->where('created_at', '>=', now()->subMonths(6))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                    'revenue' => $item->revenue,
                ];
            });

        // Produits avec stock bas (< 10)
        $lowStockProducts = Product::where('stock_quantity', '<', 10)
            ->orderBy('stock_quantity', 'asc')
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'stock' => $product->stock_quantity,
                ];
            });

        // Commandes récentes
        $recentOrders = Order::with('client')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'client' => $order->client->name,
                    'total' => $order->total,
                    'status' => $order->status,
                    'date' => $order->created_at->format('d/m/Y H:i'),
                ];
            });

        return response()->json([
            'summary' => [
                'total_clients' => $totalClients,
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
            ],
            'top_products' => $topProducts,
            'revenue_by_month' => $revenueByMonth,
            'low_stock_products' => $lowStockProducts,
            'recent_orders' => $recentOrders,
        ]);
    }
}

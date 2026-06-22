<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Barryvdh\DomPDF\Facade\Pdf;

class InvoiceController extends Controller
{
    public function download(Order $order)
    {
        $order->load(['client', 'orderLines.product']);

        $pdf = Pdf::loadView('invoices.template', [
            'order' => $order,
        ]);

        return $pdf->download("facture-{$order->id}.pdf");
    }

    public function preview(Order $order)
    {
        $order->load(['client', 'orderLines.product']);

        $pdf = Pdf::loadView('invoices.template', [
            'order' => $order,
        ]);

        return $pdf->stream("facture-{$order->id}.pdf");
    }
}

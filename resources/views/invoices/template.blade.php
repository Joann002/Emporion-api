<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #{{ $order->id }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            padding: 40px;
            color: #333;
        }
        .header {
            border-bottom: 3px solid #4CAF50;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .header h1 {
            color: #4CAF50;
            font-size: 32px;
            margin-bottom: 5px;
        }
        .header .invoice-number {
            font-size: 18px;
            color: #666;
        }
        .company-info {
            margin-bottom: 30px;
        }
        .company-info h2 {
            color: #2c3e50;
            margin-bottom: 10px;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .info-column {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 5px;
        }
        .info-box h3 {
            color: #2c3e50;
            margin-bottom: 10px;
            font-size: 14px;
            text-transform: uppercase;
        }
        .info-box p {
            margin: 5px 0;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 30px;
        }
        table thead {
            background-color: #2c3e50;
            color: white;
        }
        table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
        }
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid #ddd;
        }
        table tbody tr:hover {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 20px;
            float: right;
            width: 300px;
        }
        .totals table {
            margin-top: 0;
        }
        .totals td {
            padding: 8px 12px;
        }
        .total-row {
            background-color: #4CAF50 !important;
            color: white;
            font-weight: bold;
            font-size: 16px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 15px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }
        .status-paid {
            background-color: #d4edda;
            color: #155724;
        }
        .status-cancelled {
            background-color: #f8d7da;
            color: #721c24;
        }
        .footer {
            margin-top: 50px;
            padding-top: 20px;
            border-top: 2px solid #ddd;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>FACTURE</h1>
        <div class="invoice-number">#{{ $order->id }}</div>
    </div>

    <div class="company-info">
        <h2>Mini Business</h2>
        <p>Votre entreprise de vente</p>
    </div>

    <div class="info-section">
        <div class="info-column">
            <div class="info-box">
                <h3>Client</h3>
                <p><strong>{{ $order->client->name }}</strong></p>
                <p>{{ $order->client->email }}</p>
                @if($order->client->phone)
                    <p>{{ $order->client->phone }}</p>
                @endif
                @if($order->client->address)
                    <p>{{ $order->client->address }}</p>
                @endif
            </div>
        </div>
        <div class="info-column" style="padding-left: 20px;">
            <div class="info-box">
                <h3>Informations</h3>
                <p><strong>Date:</strong> {{ \Carbon\Carbon::parse($order->created_at)->format('d/m/Y') }}</p>
                <p>
                    <strong>Statut:</strong> 
                    <span class="status-badge status-{{ $order->status }}">
                        @if($order->status === 'pending') En attente
                        @elseif($order->status === 'paid') Payée
                        @elseif($order->status === 'cancelled') Annulée
                        @endif
                    </span>
                </p>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Produit</th>
                <th class="text-right">Prix unitaire</th>
                <th class="text-right">Quantité</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->orderLines as $line)
                <tr>
                    <td>
                        <strong>{{ $line->product->name }}</strong>
                        @if($line->product->description)
                            <br><small style="color: #666;">{{ $line->product->description }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ number_format($line->unit_price, 2, ',', ' ') }} €</td>
                    <td class="text-right">{{ $line->quantity }}</td>
                    <td class="text-right">{{ number_format($line->unit_price * $line->quantity, 2, ',', ' ') }} €</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <table>
            <tr class="total-row">
                <td><strong>TOTAL</strong></td>
                <td class="text-right"><strong>{{ number_format($order->total, 2, ',', ' ') }} €</strong></td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        <p>Merci pour votre confiance !</p>
        <p>Mini Business - Tous droits réservés</p>
    </div>
</body>
</html>

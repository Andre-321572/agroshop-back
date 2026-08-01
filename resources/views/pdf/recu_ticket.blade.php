<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu {{ $commande->code_reference }}</title>
    <style>
        @page {
            margin: 4px;
            size: 80mm 200mm;
        }
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 11px;
            line-height: 1.3;
            color: #111827;
            margin: 0;
            padding: 8px 4px;
            background: #ffffff;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-left { text-align: left; }
        .font-bold { font-weight: bold; }
        .uppercase { text-transform: uppercase; }
        
        .header {
            text-align: center;
            padding-bottom: 6px;
        }
        .header h1 {
            font-size: 15px;
            font-weight: 900;
            margin: 0 0 2px 0;
            letter-spacing: 0.5px;
        }
        .header p {
            font-size: 9px;
            margin: 1px 0;
            color: #4b5563;
        }
        
        .dashed-line {
            border-bottom: 1px dashed #9ca3af;
            margin: 6px 0;
        }
        .solid-line {
            border-bottom: 1.5px solid #111827;
            margin: 6px 0;
        }

        .meta-table {
            width: 100%;
            font-size: 10px;
            margin: 4px 0;
        }
        .meta-table td {
            padding: 1px 0;
        }
        .meta-label {
            color: #4b5563;
            width: 35%;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            margin: 4px 0;
        }
        .items-table th {
            font-size: 9px;
            text-transform: uppercase;
            color: #6b7280;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 4px;
        }
        .items-table td {
            padding: 4px 0;
            vertical-align: top;
        }

        .total-block {
            padding-top: 4px;
            font-size: 12px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 900;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 9px;
            color: #4b5563;
        }
        .footer .thanks {
            font-weight: bold;
            font-size: 10px;
            color: #111827;
            margin-bottom: 2px;
        }
    </style>
</head>
<body>

    <!-- En-tête Boutique -->
    <div class="header">
        <h1>AGROSHOP TOGO</h1>
        <p>Produits Agricoles & Quincaillerie</p>
        <p class="font-bold" style="color: #111827; font-size: 10px;">{{ $commande->boutique->nom ?? 'Boutique Agroshop' }}</p>
        <p>{{ $commande->boutique->adresse ?? 'Lomé, Togo' }} | Tél: {{ $commande->boutique->telephone ?? '+228 90 00 00 00' }}</p>
    </div>

    <div class="dashed-line"></div>

    <!-- Metadonnées Vente -->
    <table class="meta-table">
        <tr>
            <td class="meta-label">Réf. Reçu:</td>
            <td class="text-right font-bold">{{ $commande->code_reference }}</td>
        </tr>
        <tr>
            <td class="meta-label">Date & Heure:</td>
            <td class="text-right">{{ $commande->created_at ? $commande->created_at->format('d/m/Y H:i:s') : date('d/m/Y H:i:s') }}</td>
        </tr>
        <tr>
            <td class="meta-label">Client:</td>
            <td class="text-right font-bold">{{ $commande->nom_client }} {{ $commande->prenom_client }}</td>
        </tr>
        @if($commande->telephone)
        <tr>
            <td class="meta-label">Tél Client:</td>
            <td class="text-right">{{ $commande->telephone }}</td>
        </tr>
        @endif
    </table>

    <div class="dashed-line"></div>

    <!-- Table des Articles -->
    <table class="items-table">
        <thead>
            <tr>
                <th class="text-left" style="width: 50%;">ART.</th>
                <th class="text-center" style="width: 12%;">QTÉ</th>
                <th class="text-right" style="width: 18%;">P.U</th>
                <th class="text-right" style="width: 20%;">TOTAL</th>
            </tr>
        </thead>
        <tbody>
            @foreach($commande->articles as $art)
            <tr>
                <td class="font-bold">{{ $art->nom_produit ?? ($art->produit->nom_commercial ?? 'Article') }}</td>
                <td class="text-center">{{ $art->quantite }}</td>
                <td class="text-right">{{ number_format($art->prix_unitaire, 0, ',', ' ') }}</td>
                <td class="text-right font-bold">{{ number_format($art->montant_ligne, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="solid-line"></div>

    <!-- Totaux -->
    <table style="width: 100%; font-size: 11px; margin-top: 4px;">
        <tr>
            <td class="font-bold" style="font-size: 13px;">TOTAL PAYÉ :</td>
            <td class="text-right font-bold" style="font-size: 14px;">{{ number_format($commande->montant_total, 0, ',', ' ') }} FCFA</td>
        </tr>
        <tr>
            <td style="font-size: 9px; color: #6b7280;">Mode de règlement:</td>
            <td class="text-right font-bold" style="font-size: 9.5px;">Espèces (Comptoir)</td>
        </tr>
    </table>

    <div class="dashed-line"></div>

    <!-- Footer Ticket -->
    <div class="footer">
        <p class="thanks">Merci pour votre confiance ! 🙏</p>
        <p>Agroshop - La qualité au service de l'agriculture</p>
    </div>

</body>
</html>

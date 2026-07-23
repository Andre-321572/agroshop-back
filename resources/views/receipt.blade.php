<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            padding: 20px;
            color: #000;
            font-size: 12px;
        }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h1 {
            font-size: 20px;
            color: #15803d;
            margin-bottom: 5px;
        }

        .info-row {
            margin: 3px 0;
        }

        .section {
            margin: 10px 0;
        }

        .section-title {
            font-weight: bold;
            margin-bottom: 5px;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0;
        }

        .table td, .table th {
            padding: 4px 2px;
            text-align: left;
        }

        .table .price {
            text-align: right;
        }

        .total {
            margin-top: 10px;
            font-size: 14px;
            font-weight: bold;
            text-align: right;
        }

        .footer {
            margin-top: 20px;
            text-align: center;
            color: #666;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 10px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>AGROSHOP</h1>
        <div class="info-row">Kpogan zogbédji</div>
        <div class="info-row">Date: {{ $commande->created_at->format('d/m/Y H:i') }}</div>
        <div class="info-row">Réf: {{ $commande->code_reference }}</div>
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="section-title">Client:</div>
        <div class="info-row">{{ $commande->prenom_client }} {{ $commande->nom_client }}</div>
        <div class="info-row">Tel: {{ $commande->telephone }}</div>
        @if($commande->adresse_ligne1)
            <div class="info-row">Adresse: {{ $commande->adresse_ligne1 }}</div>
        @endif
    </div>

    <div class="divider"></div>

    <div class="section">
        <div class="section-title">Articles:</div>
        <table class="table">
            @foreach($commande->articles as $article)
                <tr>
                    <td>{{ $article->produit->nom_commercial ?? 'Produit' }}</td>
                    <td class="price">x {{ $article->quantite }}</td>
                    <td class="price">{{ number_format($article->prix_unitaire, 0, ',', ' ') }} FCFA</td>
                </tr>
                @if($article->quantite > 1)
                    <tr>
                        <td colspan="2"></td>
                        <td class="price">{{ number_format($article->prix_unitaire * $article->quantite, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endif
            @endforeach
        </table>
    </div>

    <div class="divider"></div>

    <div class="total">
        TOTAL: {{ number_format($commande->montant_total, 0, ',', ' ') }} FCFA
    </div>

    @if($commande->commentaires)
        <div class="section">
            <div class="info-row" style="color: #666;">Note: {{ $commande->commentaires }}</div>
        </div>
    @endif

    <div class="footer">
        <div>Merci pour votre confiance !</div>
        <div>Conservez ce reçu pour toute réclamation</div>
    </div>
</body>
</html>

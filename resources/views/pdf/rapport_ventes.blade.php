<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre }}</title>
    <style>
        body { font-family: sans-serif; font-size: 14px; }
        .header { text-align: center; margin-bottom: 30px; }
        .info { margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f4f4f4; }
        .total { font-weight: bold; font-size: 16px; text-align: right; }
    </style>
</head>
<body>
    <div class="header">
        <h2>{{ $titre }}</h2>
        <h3>{{ $boutique }}</h3>
    </div>

    <div class="info">
        <p><strong>Généré par :</strong> {{ $gestionnaire }}</p>
        <p><strong>Date de génération :</strong> {{ $date_generation }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Réf. Commande</th>
                <th>Heure</th>
                <th>Client</th>
                <th>Statut</th>
                <th>Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commandes as $commande)
            <tr>
                <td>{{ $commande->code_reference }}</td>
                <td>{{ $commande->created_at->format('H:i') }}</td>
                <td>{{ $commande->nom_client }} {{ $commande->prenom_client }}</td>
                <td>{{ $commande->statut_commande }}</td>
                <td>{{ number_format($commande->montant_total, 0, ',', ' ') }} F CFA</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" style="text-align:center">Aucune vente pour cette période.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="total">
        <p>Chiffre d'affaires validé : {{ number_format($chiffre_affaires, 0, ',', ' ') }} F CFA</p>
    </div>
</body>
</html>

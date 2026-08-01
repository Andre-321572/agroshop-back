<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $titre ?? 'Rapport' }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 13px; color: #1e293b; line-height: 1.6; padding: 20px; }
        .header { text-align: center; border-bottom: 2px solid #059669; padding-bottom: 15px; margin-bottom: 20px; }
        .header h1 { color: #065f46; margin: 0 0 5px 0; font-size: 20px; text-transform: uppercase; }
        .header h2 { color: #10b981; margin: 0; font-size: 14px; font-weight: normal; }
        .meta-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 20px; }
        .meta-box table { width: 100%; border-collapse: collapse; }
        .meta-box td { padding: 4px; border: none; font-size: 12px; }
        .section-title { color: #065f46; font-size: 14px; font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-top: 20px; margin-bottom: 10px; text-transform: uppercase; }
        .section-content { margin-bottom: 15px; text-align: justify; }
        .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>AGROSHOP TOGO — RAPPORT D'ACTIVITÉ </h1>
        <h2>{{ $boutique ?? 'Boutique AgroShop' }}</h2>
    </div>

    <div class="meta-box">
        <table>
            <tr>
                <td><strong>Rapport :</strong> {{ $titre ?? 'Rapport d\'Activité' }}</td>
                <td><strong>Date :</strong> {{ $date_rapport ?? date('d/m/Y') }}</td>
            </tr>
            <tr>
                <td><strong>Gestionnaire :</strong> {{ $gestionnaire ?? 'Gestionnaire' }}</td>
                <td><strong>Généré le :</strong> {{ $date_generation ?? date('d/m/Y H:i') }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($introduction))
    <div class="section-title">📌 Introduction</div>
    <div class="section-content">{{ $introduction }}</div>
    @endif

    @if(!empty($section_activite))
    <div class="section-title">📊 Activité & Chiffres Clés</div>
    <div class="section-content">{!! nl2br(e($section_activite)) !!}</div>
    @endif

    @if(!empty($section_stocks))
    <div class="section-title">📦 État des Stocks</div>
    <div class="section-content">{!! nl2br(e($section_stocks)) !!}</div>
    @endif

    @if(!empty($section_anomalies))
    <div class="section-title">⚠️ Points d'Attention & Anomalies</div>
    <div class="section-content">{!! nl2br(e($section_anomalies)) !!}</div>
    @endif

    @if(!empty($section_recommandations))
    <div class="section-title">💡 Recommandations </div>
    <div class="section-content">{!! nl2br(e($section_recommandations)) !!}</div>
    @endif

    @if(!empty($conclusion))
    <div class="section-title">📝 Conclusion</div>
    <div class="section-content">{{ $conclusion }}</div>
    @endif

    <div class="footer">
        Document généré automatiquement par le Système d'Intelligence Artificielle AgroShop Togo.
    </div>
</body>
</html>

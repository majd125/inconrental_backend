<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #eee; border-radius: 5px; }
        .header { background-color: #0f172a; color: white; padding: 15px; text-align: center; border-radius: 5px 5px 0 0; }
        .content { padding: 20px 0; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        .details-box { background: #f8fafc; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 4px solid #0f172a; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Confirmation de Réservation</h2>
        </div>
        <div class="content">
            <p>Bonjour,</p>
            <p>Nous avons le plaisir de vous informer que votre réservation pour <strong>{{ $serviceType }}</strong> a été confirmée avec succès !</p>
            
            <div class="details-box">
                <p><strong>Détails :</strong></p>
                <ul>
                    @foreach($details as $key => $value)
                        <li><strong>{{ $key }} :</strong> {{ $value }}</li>
                    @endforeach
                </ul>
            </div>
            
            <p>Merci pour votre confiance. Si vous avez la moindre question, n'hésitez pas à nous contacter.</p>
            <p>Cordialement,</p>
            <p><strong>L'équipe IconRental</strong></p>
        </div>
        <div class="footer">
            &copy; {{ date('Y') }} IconRental. Tous droits réservés.
        </div>
    </div>
</body>
</html>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Création de compte automatique</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <div style="max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 5px;">
        <h2 style="color: #0056b3;">Bienvenue sur IconRental !</h2>
        <p>Bonjour {{ $user->name }},</p>
        
        <p>Suite à votre réservation, nous vous informons qu'un compte a été automatiquement créé pour vous afin de faciliter vos prochaines visites.</p>
        
        <div style="background-color: #f9f9f9; padding: 15px; border-left: 4px solid #0056b3; margin: 20px 0;">
            <p style="margin: 0;"><strong>Email :</strong> {{ $user->email }}</p>
            <p style="margin: 0;"><strong>Mot de passe :</strong> {{ $cin }}</p>
        </div>
        
        <p>Nous vous conseillons de vous connecter et de modifier ce mot de passe dès que possible.</p>
        
        <p>Merci pour votre confiance,</p>
        <p><strong>L'équipe IconRental</strong></p>
    </div>
</body>
</html>

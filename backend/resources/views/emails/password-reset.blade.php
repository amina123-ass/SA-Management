<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Réinitialisation de mot de passe</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background-color: #d32f2f;
            color: white;
            padding: 20px;
            text-align: center;
            border-radius: 5px 5px 0 0;
        }
        .content {
            background-color: #f9f9f9;
            padding: 30px;
            border-radius: 0 0 5px 5px;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background-color: #d32f2f;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #666;
        }
        .warning {
            background-color: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 10px;
            margin: 15px 0;
        }
        .security-note {
            background-color: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 10px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔒 SA Management</h1>
    </div>
    <div class="content">
        <h2>Bonjour {{ $user->prenom }} {{ $user->nom }},</h2>
        
        <p>Nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
        
        <p>Pour réinitialiser votre mot de passe, veuillez cliquer sur le bouton ci-dessous :</p>
        
        <center>
            <a href="{{ $resetUrl }}" class="button">Réinitialiser mon mot de passe</a>
        </center>
        
        <p>Ou copiez et collez ce lien dans votre navigateur :</p>
        <p style="background-color: #e9ecef; padding: 10px; word-wrap: break-word; font-size: 12px;">
            {{ $resetUrl }}
        </p>
        
        <div class="warning">
            <strong>⚠️ Important :</strong> Ce lien est valable pendant 1 heure seulement.
        </div>
        
        <div class="security-note">
            <strong>🔐 Note de sécurité :</strong>
            <p>Si vous n'avez pas demandé cette réinitialisation, veuillez ignorer cet email. Votre mot de passe actuel restera inchangé.</p>
        </div>
        
        <p><strong>Rappel des exigences pour votre nouveau mot de passe :</strong></p>
        <ul>
            <li>Au moins 8 caractères</li>
            <li>Au moins une majuscule</li>
            <li>Au moins une minuscule</li>
            <li>Au moins un chiffre</li>
            <li>Au moins un symbole (!@#$%^&*)</li>
        </ul>
        
        <p>Cordialement,<br>L'équipe SA Management</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} SA Management. Tous droits réservés.</p>
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
    </div>
</body>
</html>
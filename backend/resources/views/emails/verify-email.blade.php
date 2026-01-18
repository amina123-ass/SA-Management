<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification de votre email</title>
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
            background-color: #1976d2;
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
            background-color: #1976d2;
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
    </style>
</head>
<body>
    <div class="header">
        <h1>SA Management</h1>
    </div>
    <div class="content">
        <h2>Bonjour {{ $user->prenom }} {{ $user->nom }},</h2>
        
        <p>Merci de vous être inscrit sur SA Management !</p>
        
        <p>Pour finaliser votre inscription et activer votre compte, veuillez cliquer sur le bouton ci-dessous :</p>
        
        <center>
            <a href="{{ $verificationUrl }}" class="button">Vérifier mon email</a>
        </center>
        
        <p>Ou copiez et collez ce lien dans votre navigateur :</p>
        <p style="background-color: #e9ecef; padding: 10px; word-wrap: break-word; font-size: 12px;">
            {{ $verificationUrl }}
        </p>
        
        <div class="warning">
            <strong>⚠️ Important :</strong> Ce lien est valable pendant 24 heures seulement.
        </div>
        
        <p>Après avoir cliqué sur le lien, vous devrez :</p>
        <ul>
            <li>Créer votre mot de passe</li>
            <li>Répondre à 3 questions de sécurité</li>
        </ul>
        
        <p>Si vous n'avez pas créé de compte sur SA Management, veuillez ignorer cet email.</p>
        
        <p>Cordialement,<br>L'équipe SA Management</p>
    </div>
    <div class="footer">
        <p>&copy; {{ date('Y') }} SA Management. Tous droits réservés.</p>
        <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
    </div>
</body>
</html>
<!DOCTYPE html>
<html>
<head>
    <title>Réinitialisation de mot de passe</title>
</head>
<body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
    <h2>Bonjour,</h2>
    <p>Vous recevez cet email car nous avons reçu une demande de réinitialisation de mot de passe pour votre compte.</p>
    <p>
        <a href="{{ $resetUrl }}" style="display: inline-block; padding: 10px 20px; background-color: #f97316; color: white; text-decoration: none; border-radius: 5px;">
            Réinitialiser le mot de passe
        </a>
    </p>
    <p>Ce lien de réinitialisation de mot de passe expirera dans 60 minutes.</p>
    <p>Si vous n'avez pas demandé de réinitialisation de mot de passe, aucune action supplémentaire n'est requise.</p>
    <br>
    <p>Cordialement,<br>L'équipe ArtisanRecruit</p>
    <hr style="border: 0; border-top: 1px solid #ccc;">
    <p style="font-size: 0.8em; color: #777;">
        Si vous rencontrez des problèmes pour cliquer sur le bouton "Réinitialiser le mot de passe", copiez et collez l'URL ci-dessous dans votre navigateur Web : <br>
        <a href="{{ $resetUrl }}">{{ $resetUrl }}</a>
    </p>
</body>
</html>

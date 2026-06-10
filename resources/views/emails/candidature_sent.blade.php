<!doctype html>
<html lang="fr">
  <body style="font-family: Arial, sans-serif; line-height: 1.6;">
    <h2>Nouvelle candidature reçue</h2>
    <p>
      Bonjour,
      <br />
      Vous avez reçu une nouvelle candidature pour l'offre:
      <strong>{{ $offre->titre }}</strong>.
    </p>

    <h3>Candidat</h3>
    <ul>
      <li><strong>Nom:</strong> {{ $artisan->nom }}</li>
      <li><strong>Email:</strong> {{ $artisan->email }}</li>
    </ul>

    <p style="color:#64748b">
      Envoyée le {{ $candidature->created_at->format('d/m/Y H:i') }}
    </p>
  </body>
</html>


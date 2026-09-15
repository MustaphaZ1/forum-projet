<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum</title>
    <link rel="stylesheet" href="/forum/public/css/bootstrap.min.css">
    <link rel="stylesheet" href="/forum/public/assets/theme.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/forum/public/index.php">
            Forum
            </a>
            <div class="ms-auto align-items-center d-flex gap-2">
                <?php if (isset($_SESSION['pseudo'])): ?>
        <!-- Affiche le pseudo de l'utilisateur connecté (manière sécurisée anti-XSS)-->
            <p class="text-end">Connecté en tant que <a href="/forum/public/profil.php"><?= htmlspecialchars($_SESSION['pseudo']) ?></a>
            <a href="/forum/public/deconnexion.php" class="text-danger text-decoration-none ms-2">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="mb-1">
                    <path d="M5.146 5.146a.5.5 0 0 1 .708.708 3.5 3.5 0 1 0 4.288-.001.5.5 0 1 1 .61-.792 4.5 4.5 0 1 1-5.606.001z"/>
                    <path d="M7.5 1v7a.5.5 0 0 0 1 0V1a.5.5 0 0 0-1 0z"/>
                </svg>
                Se déconnecter
            </a></p>
        <!-- Si l'utilisateur n'est pas connecté-->
            <?php else : ?>
                <p>
                    <a href="/forum/public/connexion.php">Se connecter</a>|
                    <a href="/forum/public/inscription.php">S'inscrire</a>
                </p>
        <?php endif; ?>
                <button class="btn btn-secondary" id="theme-toggle">🌙
                </button>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
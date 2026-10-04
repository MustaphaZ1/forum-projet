<?php
require_once __DIR__ . '/csrf.php';
$pages_messagerie = [
    'messages_prives.php',
    'conversation.php',
    'acces_messagerie.php',
    'definir_codes_messagerie.php',
    'nouveau_message_prive.php',
    'supprimer_message_prive.php',
    'conversation_e2e.php',
];
$page_actuelle = basename($_SERVER['SCRIPT_NAME']);
if (!in_array($page_actuelle, $pages_messagerie, true)) {
    unset($_SESSION['messagerie_deverrouillee']);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forum</title>
    <link rel="icon" type="image/png" href="/favicon.png">
    <link rel="stylesheet" href="/forum/public/css/bootstrap.min.css">
    <link rel="stylesheet" href="/forum/public/assets/theme.css">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
</head>
<body>
    <header>
        <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
            <div class="container">
                <img src="/favicon.png" alt="logo">
    			<a class="navbar-brand" href="/index.php">
                Forum
                </a>
                <div class="ms-auto d-flex flex-wrap align-items-center justify-content-end gap-2">
                    <?php if (isset($_SESSION['pseudo'])): ?>
                <a href="/forum/public/acces_messagerie.php" class="text-decoration-none">🔒📩</a>
            <!-- Affiche le pseudo de l'utilisateur connecté (manière sécurisée anti-XSS)-->
                <p class="text-end mb-0 d-none d-sm-inline">Connecté en tant que <a href="/forum/public/profil.php"><?= htmlspecialchars($_SESSION['pseudo']) ?></a>
                <p class="text-end mb-0 d-inline d-sm-none"><a href="/forum/public/profil.php"><?= htmlspecialchars($_SESSION['pseudo']) ?></a></p>
                <a href="/forum/public/deconnexion.php" id="lien-deconnexion" class="text-danger text-decoration-none ms-2">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16" class="mb-1">
                        <path d="M5.146 5.146a.5.5 0 0 1 .708.708 3.5 3.5 0 1 0 4.288-.001.5.5 0 1 1 .61-.792 4.5 4.5 0 1 1-5.606.001z"/>
                        <path d="M7.5 1v7a.5.5 0 0 0 1 0V1a.5.5 0 0 0-1 0z"/>
                    </svg>
                    <span class="d-none d-sm-inline">Se déconnecter</span>
                </a></p>
            <!-- Si l'utilisateur n'est pas connecté-->
                <?php else : ?>
                    <p class="mb-0">
                        <a href="/forum/public/connexion.php">Se connecter</a> |
                        <a href="/forum/public/inscription.php">S'inscrire</a>
                    </p>
            <?php endif; ?>
                    <button class="btn btn-secondary" id="theme-toggle">🌙
                    </button>
                </div>
            </div>
        </nav>
    </header>
    <div class="container mt-4">
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
$erreur='';
$success='';
$token=$_GET['token'] ?? '';
if ($token===''){
    $erreur='Lien de confirmation invalide.';
} else {
    $pdo=getPDO();
    $stmt=$pdo->prepare('SELECT id_utilisateur, email_confirme FROM utilisateurs WHERE token_confirmation=?');
    $stmt->execute([$token]);
    $utilisateur=$stmt->fetch();
    if (!$utilisateur){
        $erreur='Ce lien de confirmation est invalide ou a deja ete utilisé (veuillez vous connecter avec vos identifiants';
    } else {
        $update=$pdo->prepare('UPDATE utilisateurs SET email_confirme=1, token_confirmation= NULL WHERE id_utilisateur=?');
        $update->execute([$utilisateur['id_utilisateur']]);
        $success='Ton compte a été confirmé ! Tu peux maintenant te connecter avec tes identifiants';
    }
}
?>
<main>
    <p><a href="/index.php">&larr; Retour aux catégories</a></p>
    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?><a href="/../forum/public/connexion.php"></a></div>
    <?php endif; ?>
</main>
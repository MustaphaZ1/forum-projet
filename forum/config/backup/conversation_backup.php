<?php
    error_reporting(E_ALL);
ini_set('display_errors','1');
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions_messages.php';
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['id_utilisateur'])){
    header('Location: connexion.php');
    exit;
}
$pdo=getPDO();
$id_utilisateur=$_SESSION['id_utilisateur'];
$id_interlocuteur=$_GET['id_utilisateur']?? null;
if (!$id_interlocuteur || !ctype_digit((string) $id_interlocuteur)){
    die('Utilisateur introuvable');
}
$id_interlocuteur=(int)$id_interlocuteur;
if ($id_interlocuteur===(int)$id_utilisateur){
    die("Vous ne pouvez pas discuter avec vous même ce n'est pas un bloc notes, vous avez 2 personnalités ?");
}
$stmt=$pdo->prepare('SELECT id_utilisateur, pseudo FROM utilisateurs WHERE id_utilisateur=?');
$stmt->execute([$id_interlocuteur]);
$interlocuteur=$stmt->fetch();
if (!$interlocuteur){
    die('Utilisateur introuvable');
}
$erreur='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $contenu=trim($_POST['contenu']??'');
    if ($contenu===''){
        $erreur='Le message ne peut pas être vide.';
    } else {
        try {
            $contenu_chiffre= chiffrerMessage($contenu);
            $stmt=$pdo->prepare('INSERT INTO messages_prives (id_expediteur, id_destinataire, contenu_chiffre, lu, date_envoi, suppression_expediteur, suppression_destinataire) VALUES (?, ?, ?, 0, NOW(), 0, 0)');
            $stmt->execute([$id_utilisateur, $id_interlocuteur, $contenu_chiffre]);
            header('Location: conversation.php?id_utilisateur='.$id_interlocuteur);
            exit;
        } catch (Throwable $e){
            $erreur='Une erreur est survenue lors de l\'envoi du message.';
        }
    }
}
$stmt=$pdo->prepare('UPDATE messages_prives SET lu=1 WHERE id_expediteur=? AND id_destinataire=? AND lu=0');
$stmt->execute([$id_interlocuteur, $id_utilisateur]);
$stmt=$pdo->prepare('SELECT id_message_prive, id_expediteur, id_destinataire, contenu_chiffre, lu, date_envoi FROM messages_prives WHERE (id_expediteur=? AND id_destinataire=? AND suppression_expediteur=0) OR (id_expediteur=? AND id_destinataire=? AND suppression_destinataire=0) ORDER BY date_envoi ASC, id_message_prive ASC');
$stmt->execute([$id_utilisateur, $id_interlocuteur, $id_interlocuteur, $id_utilisateur]);
$messages=$stmt->fetchAll();
?>
<body>
    <div class="container mt-5" style="max-width: 800px;">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <a href="messages_prives.php" class="text-decoration-none">&larr; Retour aux messages</a>
            <h1 class="mt-2"><?= htmlspecialchars($interlocuteur['pseudo']) ?></h1>
        </div>
    </div>
    <?php if ($erreur !==''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <div class="mb-4">
        <?php if (empty($messages)): ?>
            <div class="alert alert-secondary">Aucun message dans cette conversation</div>
        <?php else: ?>
            <?php foreach ($messages as $message): ?>
                <?php 
                    try {
                        $contenu=dechiffrerMessage($message['contenu_chiffre']);
                    } catch (Throwable $e){
                        die('ERREUR DECHIFFREMENT : ' . $e->getMessage());
                    }
                    $est_moi=(int)$message['id_expediteur']===(int)$id_utilisateur;
                ?>
                <div class="d-flex mb-3 <?= $est_moi ? 'justify-content-end':'justify-content-start'?>">
                    <div class="card <?= $est_moi ? 'bg-primary text-white' : 'bg-light' ?>" style="max-width: 70%;">
                        <div class="card-body">
                            <p class="card-text mb-2"><?= nl2br(htmlspecialchars($contenu)) ?></p>
                            <small class="<?= $est_moi ? 'text-white-50' : 'text-muted' ?>"><?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <form action="" method="post">
        <div class="input-group">
            <textarea name="contenu" id="" rows="2" class="form-control" placeholder="Ecrire un message ..." required></textarea>
            <button type="submit" class="btn btn-primary">Envoyer</button>
        </div>
    </form>
    <?php require_once __DIR__ . '/../includes/footer.php' ?>
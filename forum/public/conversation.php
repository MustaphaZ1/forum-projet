<?php
session_start();
date_default_timezone_set('Europe/Paris');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions_messages.php';
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
$estAjax=!empty($_POST['ajax']);
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $contenu=trim($_POST['contenu']??'');
    if ($contenu===''){
        $erreur='Le message ne peut pas être vide.';
        if ($estAjax){
            header('Content-Type: application/json');
            echo json_encode(['succes'=>false, 'erreur'=>$erreur]);
            exit;
        }
    } else {
        try {
            $contenu_chiffre= chiffrerMessage($contenu);
            $dateActuelle=date('Y-m-d H:i:s');
            $stmt=$pdo->prepare('INSERT INTO messages_prives (id_expediteur, id_destinataire, contenu_chiffre, lu, date_envoi, suppression_expediteur, suppression_destinataire) VALUES (?, ?, ?, 0, ?, 0, 0)');
            $stmt->execute([$id_utilisateur, $id_interlocuteur, $contenu_chiffre, $dateActuelle]);
            if ($estAjax){
                header('Content-Type: application/json');
                echo json_encode(['succes'=>true]);
                exit;
            }
            header('Location: conversation.php?id_utilisateur='.$id_interlocuteur);
            exit;
        } catch (Throwable $e){
            $erreur='Une erreur est survenue lors de l\'envoi du message. ' . $e->getMessage();
            if ($estAjax){
                header('Content-Type: application/json');
                echo json_encode(['succes'=>false, 'erreur'=>$erreur]);
                exit;
            }
        }
    }
}
$stmt=$pdo->prepare('UPDATE messages_prives SET lu=1 WHERE id_expediteur=? AND id_destinataire=? AND lu=0');
$stmt->execute([$id_interlocuteur, $id_utilisateur]);
$stmt=$pdo->prepare('SELECT id_message_prive, id_expediteur, id_destinataire, contenu_chiffre, lu, date_envoi FROM messages_prives WHERE (id_expediteur=? AND id_destinataire=? AND suppression_expediteur=0) OR (id_expediteur=? AND id_destinataire=? AND suppression_destinataire=0) ORDER BY date_envoi ASC, id_message_prive ASC');
$stmt->execute([$id_utilisateur, $id_interlocuteur, $id_interlocuteur, $id_utilisateur]);
$messages=$stmt->fetchAll();
require_once __DIR__ . '/../includes/header.php';
?>
<body>
    <div class="mb-2">
        <a href="messages_prives.php" class="text-decoration-none">&larr; Retour</a>
    </div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1 class="mb-0"><?= htmlspecialchars($interlocuteur['pseudo']) ?></h1>
            <form action="supprimer_conversation.php" method="post" onsubmit="return confirm('Supprimer toute la conversation ?');" class="d-inline">
                <input type="hidden" name="id_interlocuteur" value="<?= (int)$interlocuteur['id_utilisateur'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">Tout supprimer &#x1F5D1;</button>
            </form>
    </div>
    <?php if ($erreur !==''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <div class="mb-4" id="zone-messages">
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
                    <div class="card<?= $est_moi?'bg-primary text-white':'' ?>" style="max-width: 70%;">
                        <div class="card-body">
                            <p class="card-text mb-2" style="<?= $est_moi ? 'color:white;' : 'color: white;' ?>"><?= nl2br(htmlspecialchars($contenu)) ?></p>
                            <small class="<?= $est_moi ? 'text-white-50' : 'text-muted' ?>"><?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></small>
                            <form action="supprimer_message_prive.php" method="post" onsubmit="return confirm('Supprimer ce message (uniquement pour vous) ?');" class="mt-1">
                                <input type="hidden" name="id_message_prive" value="<?= (int)$message['id_message_prive'] ?>">
                                <input type="hidden" name="id_interlocuteur" value="<?= (int)$interlocuteur['id_utilisateur'] ?>">
                                <button type="submit" class="btn btn-link btn-sm text-decoration-none <?= $est_moi ? 'text-white-50' : 'text-muted' ?> p-0">Supprimer &#x1F5D1;</button>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <form action="" method="post" id="form-envoi-message">
        <div class="input-group">
            <textarea name="contenu" id="contenu-message" rows="2" class="form-control" placeholder="Ecrire un message ..." required></textarea>
            <button type="submit" class="btn btn-primary">Envoyer</button>
        </div>
    </form>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
<script src="js/messagerie.js"></script>
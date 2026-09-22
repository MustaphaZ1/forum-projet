<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/fonctions_messages.php';
if(!isset($_SESSION['id_utilisateur'])){
    header('Location: connexion.php');
    exit;
}
$pdo=getPDO();
$id_utilisateur=$_SESSION['id_utilisateur'];
$stmt=$pdo->prepare('SELECT messages_prives.*, expediteur.pseudo AS pseudo_expediteur, destinataire.pseudo AS pseudo_destinataire FROM messages_prives JOIN utilisateurs AS expediteur ON messages_prives.id_expediteur=expediteur.id_utilisateur JOIN utilisateurs AS destinataire ON messages_prives.id_destinataire=destinataire.id_utilisateur WHERE (messages_prives.id_expediteur=? AND messages_prives.suppression_expediteur=0) OR (messages_prives.id_destinataire=? AND messages_prives.suppression_destinataire=0) ORDER BY messages_prives.date_envoi DESC, messages_prives.id_message_prive DESC');
$stmt->execute([$id_utilisateur, $id_utilisateur]);
$messages=$stmt->fetchAll();
$conversation=[];
foreach ($messages as $message){
    if($message['id_expediteur']==$id_utilisateur){
        $id_interlocuteur=$message['id_destinataire'];
        $pseudo_interlocuteur=$message['pseudo_destinataire'];
    } else {
        $id_interlocuteur=$message['id_expediteur'];
        $pseudo_interlocuteur=$message['pseudo_expediteur'];
    }
    if(!isset($conversations[$id_interlocuteur])){
        try{
            $apercu=dechiffrerMessage($message['contenu_chiffre']);
        } catch (Throwable $e){
            $apercu='[Message impossible à déchiffrer]';
        }
        $conversations[$id_interlocuteur]=[
            'id_interlocuteur'=>$id_interlocuteur,
            'pseudo'=>$pseudo_interlocuteur,
            'dernier message'=>$apercu,
            'date_envoi'=>$message['date_envoi'],
            'non_lu'=>0
            ];
    }
    if($message['id_destinataire']==$id_utilisateur && $message['lu']==0){
        $conversations[$id_interlocuteur]['non_lu']++;
    }
}
?>
<body>
    <div class="container mt-5 messages-container">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-2 mb-4">
            <h1 class="h3 h1-sm mb-0">Messages privés</h1>
            <a href="nouveau_message_prive.php" class="btn btn-primary btn-sm btn-sm-sm">Nouveau message</a>
        </div>
        <?php if(empty($conversations)): ?>
            <div class="alert alert-secondary">Vous n'avez aucune conversation vous pouvez en commencer une</div>
            <?php else: ?>
                <?php foreach($conversations as $conversation): ?>
                    <a href="conversation.php?id_utilisateur=<?= (int) $conversation['id_interlocuteur'] ?>" class="text-decoration-none text-reset">
                        <div class="card mb-3">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="card-title mb-1"><?= htmlspecialchars($conversation['pseudo']) ?>
                                <?php if($conversation['non_lu']>0): ?>
                                <span class="badge bg-primary ms-2"><?= $conversation['non_lu'] ?></span>
                                <?php endif; ?>
                                </h5>
                                <p class="card-text text-muted mb-0">
                                    <?= htmlspecialchars(mb_strimwidth($conversation['dernier_message'], 0, 100, '...')) ?>
                                </p>
                                </div>
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i', strtotime($conversation['date_envoi'])) ?>
                                </small>
                            </div>
                        </div>
                    </a>
                <?php endforeach; ?>
        <?php endif; ?>
    </div>
</body>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
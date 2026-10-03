<?php
session_start(); // DEMARRE OU REPREND LA SESSION
date_default_timezone_set('Europe/Paris'); // DEFINIT LE FUSEAU HORAIRE SUR PARIS
require_once __DIR__ . '/../config/database.php'; // CONNEXION A LA BASE DE DONNEES
require_once __DIR__ . '/../includes/fonctions_messages.php'; // CHARGE LES FONCTIONS DE CHIFFREMENT/DECHIFFREMENT
// VERIFIE SI L'UTILISATEUR EST CONNECTE SINON REDIRECTION
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['messagerie_deverrouillee'])){
    header('Location: acces_messagerie.php');
    exit;
}
$pdo=getPDO();
$id_utilisateur=$_SESSION['id_utilisateur']; // ID DE L'UTILISATEUR
$id_interlocuteur=$_GET['id_utilisateur']?? null; // ID DE L'INTERLOCUTEUR (A QUI L'UTILISATER DISCUTE A PARTIR DE L'URL)
// VERIFIE SI L'ID DE L'INTERLOCUTEUR EST ABSENT OU INVALIDE (DOIT ETRE INT)
if (!$id_interlocuteur || !ctype_digit((string) $id_interlocuteur)){
    die('Utilisateur introuvable');
}
$id_interlocuteur=(int)$id_interlocuteur;
// EMPECHE UN UTILISATEUR DE S'ENVOYER DES MSG A LUI MEME
if ($id_interlocuteur===(int)$id_utilisateur){
    die("Vous ne pouvez pas discuter avec vous même ce n'est pas un bloc notes, vous avez 2 personnalités ?");
}
// VERIFIE DANS LA BASE DE DONNEES SI L'ID DE L'INTERLOCUTEUR EST VALIDE
$stmt=$pdo->prepare('SELECT id_utilisateur, pseudo FROM utilisateurs WHERE id_utilisateur=?');
$stmt->execute([$id_interlocuteur]);
$interlocuteur=$stmt->fetch();
if (!$interlocuteur){
    die('Utilisateur introuvable');
}
$erreur='';
$estAjax=!empty($_POST['ajax']); // DETECTE SI LA REQUETE PROVIENT D'UN SCRIPT JS (AJAX)
// VERIFIE SI LE FORMULAIRE A ETE SOUMIS VIA LA METHODE POST
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $contenu=trim($_POST['contenu']??''); // RECUPERE LE TEXTE DU MSG ET SUPPRIME LES ESPACES SUPERFLUS
    // VERIFIE SI LE MSG EST VIDE
    if ($contenu===''){
        $erreur='Le message ne peut pas être vide.';
        if ($estAjax){
            header('Content-Type: application/json');
            echo json_encode(['succes'=>false, 'erreur'=>$erreur]); // RENVOIE L'ERREUR EN JSON SI C'EST DE L'AJAX
            exit;
        }
    } else {
        try {
            $contenu_chiffre= chiffrerMessage($contenu); // CHIFFRE LE CONTENU DU MSG POUR LA SECURITE
            $dateActuelle=date('Y-m-d H:i:s'); // RECUPERE LA DATE ET L'HEURE
            // PREPARE LA REQUETE D'INSERTION DU MSG CHIFFRE DANS LA BASE DE DONNEES
            $stmt=$pdo->prepare('INSERT INTO messages_prives (id_expediteur, id_destinataire, contenu_chiffre, lu, date_envoi, suppression_expediteur, suppression_destinataire) VALUES (?, ?, ?, 0, ?, 0, 0)');
            $stmt->execute([$id_utilisateur, $id_interlocuteur, $contenu_chiffre, $dateActuelle]);
            if ($estAjax){
                header('Content-Type: application/json');
                echo json_encode(['succes'=>true]); // REPOND EN JSON SI LA REQUETE VIENT D'AJAX
                exit;
            }
            header('Location: conversation.php?id_utilisateur='.$id_interlocuteur); // REDIRIGE VERS LA CONVERSATION CLASSIQUE SI CE N'EST PAS DE L'AJAX
            exit;
        } catch (Throwable $e){
            $erreur='Une erreur est survenue lors de l\'envoi du message. ' . $e->getMessage(); // CAPTURE LES ERREURS TECHNIQUES
            if ($estAjax){
                header('Content-Type: application/json');
                echo json_encode(['succes'=>false, 'erreur'=>$erreur]); // RENVOIE L'ERREUR EN JSON EN CAS DE SOUCI
                exit;
            }
        }
    }
}
// MISE A JOUR DES STATUTS DE LECTURE
// MARQUE COMME LU=1 LES MSG DE L'INTERLOCUTEUR PAS ENCORE LUS
$stmt=$pdo->prepare('UPDATE messages_prives SET lu=1 WHERE id_expediteur=? AND id_destinataire=? AND lu=0');
$stmt->execute([$id_interlocuteur, $id_utilisateur]);
// RECUPERATION DE TOUTE LA CONVERSATION
// RECUPERE L'HISTORIQUE DES MSG ENTRE LES DES UTILISATEURS
$stmt=$pdo->prepare('SELECT id_message_prive, id_expediteur, id_destinataire, contenu_chiffre, lu, date_envoi FROM messages_prives WHERE (id_expediteur=? AND id_destinataire=? AND suppression_expediteur=0) OR (id_expediteur=? AND id_destinataire=? AND suppression_destinataire=0) ORDER BY date_envoi ASC, id_message_prive ASC');
$stmt->execute([$id_utilisateur, $id_interlocuteur, $id_interlocuteur, $id_utilisateur]);
$messages=$stmt->fetchAll();
// CHARGE L'ENTETE HTML DE LA PAGE
require_once __DIR__ . '/../includes/header.php';
?>
<body>
    <div class="mb-2">
        <a href="messages_prives.php" class="text-decoration-none">&larr; Retour</a>
    </div>
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h1 class="mb-0"><?= htmlspecialchars($interlocuteur['pseudo']) ?></h1> <!-- AFFICHE LE PSEUDO DE L'INTERLOCUTEUR DE MANIERE SECURISEE -->
<!-- FORMULAIRE POUR SUPPRIMER TOUTE LA CONVERSATION D'UN COUP AVEC UNE BOITE DE CONFIRMATION JS -->
            <form action="supprimer_conversation.php" method="post" onsubmit="return confirm('Supprimer toute la conversation ?');" class="d-inline">
                <input type="hidden" name="id_interlocuteur" value="<?= (int)$interlocuteur['id_utilisateur'] ?>">
                <button type="submit" class="btn btn-outline-danger btn-sm">Tout supprimer &#x1F5D1;</button>
            </form>
    </div>
<!-- AFFICHAGE D'UNE ERREUR GLOBALE EVENTUELLE -->
    <?php if ($erreur !==''): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <!-- CONTENEUR VIDE OU LE JS VIENT INJECTER DYNAMIQUEMENT LES MSG -->
    <div class="mb-4" id="zone-messages">
    <!-- VERIFIE SI LE TABLEAU DES MSG EST VIDE -->
        <?php if (empty($messages)): ?>
            <div class="alert alert-secondary">Aucun message dans cette conversation</div>
        <?php else: ?>
            <!-- PARCOURT CHAQUE MSG DE LA CONVERSATION -->
            <?php foreach ($messages as $message): ?>
                <?php 
                    try {
                        // DECHIFFRE LE CONTENU DU MSG POUR L'AFFICHER EN CLAIR
                        $contenu=dechiffrerMessage($message['contenu_chiffre']);
                    } catch (Throwable $e){
                        die('ERREUR DECHIFFREMENT : ' . $e->getMessage());
                    }
                    // VERIFIE SI LE MSG A ETE ENVOYE PAR L'UTILISATEUR CONNECTE
                    $est_moi=(int)$message['id_expediteur']===(int)$id_utilisateur;
                ?>
                <!-- ALIGNE LA BULLE A DROITE SI LE MSG APPARTIENT A L'UTILISATEUR ET A GAUCHE SI IL APPARTIENT A L'INTERLOCUTEUR -->
                <div class="d-flex mb-3 <?= $est_moi ? 'justify-content-end':'justify-content-start'?>">
                    <div class="card<?= $est_moi?'bg-primary text-white':'' ?>" style="max-width: 70%;">
                        <div class="card-body">
                            <!-- AFFICHE LE CONTENU DECHIFFRE, SECURISE CONTRE LES FAILLES XSS ET AVEC LES RETOURS A LA LIGNE -->
                            <p class="card-text mb-2" style="<?= $est_moi ? 'color:white;' : 'color: white;' ?>"><?= nl2br(htmlspecialchars($contenu)) ?></p>
                            <!-- AFFICHE LA DATE D'ENVOI FORMATEE ET LA COULEUR DU TEXTE ADAPTEE -->
                            <small class="<?= $est_moi ? 'text-white-50' : 'text-muted' ?>"><?= date('d/m/Y H:i', strtotime($message['date_envoi'])) ?></small>
                            <!-- FORMULAIRE D'ACTION POUR SUPPRIMER UN MSG AVEC UNE BOITE DE CONFIRMATION -->
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
    <!-- FORMULAIRE EN BAS DE PAGE POUR ECRIRE UN NOUVEAU MSG -->
    <form action="" method="post" id="form-envoi-message">
        <div class="input-group">
            <textarea name="contenu" id="contenu-message" rows="2" class="form-control" placeholder="Ecrire un message ..." required></textarea>
            <button type="submit" class="btn btn-primary">Envoyer</button>
        </div>
    </form>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
<script>const CSRF_TOKEN = "<?= csrf_token() ?>";</script>
<script src="js/messagerie.js"></script>
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/fonctions_messages.php';
header('Content-Type: application/json');
if (!isset($_SESSION['id_utilisateur'])){
    http_response_code(401);
    echo json_encode(['erreur' => 'Non connecté']);
    exit;
}
$id_utilisateur=$_SESSION['id_utilisateur'];
$id_interlocuteur=$_GET['id_interlocuteur'] ?? null;
if (!$id_interlocuteur || !ctype_digit((string)$id_interlocuteur)){
    http_response_code(400);
    echo json_encode(['erreur' => 'Interlocuteur invalide']);
    exit;
}
$pdo=getPDO();
$stmt=$pdo->prepare('SELECT id_message_prive, id_expediteur, id_destinataire, contenu_chiffre, lu, date_envoi FROM messages_prives WHERE (id_expediteur=? AND id_destinataire=? AND suppression_expediteur=0) OR (id_expediteur=? AND id_destinataire=? AND suppression_destinataire=0) ORDER BY date_envoi ASC, id_message_prive ASC');
$stmt->execute([$id_utilisateur, $id_interlocuteur, $id_interlocuteur, $id_utilisateur]);
$messages=$stmt->fetchAll();
$resultat=[];
foreach ($messages as $message){
    try {
        $contenu=dechiffrerMessage($message['contenu_chiffre']);
    } catch (Throwable $e){
        $contenu='[Erreur de déchiffrement]';
    }
    $resultat[]=['id_message_prive' => (int)$message['id_message_prive'], 'est_moi' => (int)$message['id_expediteur']===(int)$id_utilisateur, 'contenu' => $contenu, 'date_envoi' => $message['date_envoi']];
}
echo json_encode($resultat);
exit;
?>
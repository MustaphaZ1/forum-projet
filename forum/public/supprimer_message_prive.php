<?php
session_start();
$estAjax=!empty($_POST['ajax']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['id_utilisateur'])){
    csrf_verifier();
    header('Location: connexion.php');
    exit;
}
$id_message_prive=$_POST['id_message_prive'] ?? null;
$id_interlocuteur=$_POST['id_interlocuteur'] ?? null;
if (!$id_message_prive || !ctype_digit((string)$id_message_prive)){
    die('Message invalide');
}
if (!$id_interlocuteur || !ctype_digit((string)$id_interlocuteur)){
    die('Interlocuteur invalide');
}
$pdo=getPDO();
$stmt=$pdo->prepare('SELECT id_expediteur, id_destinataire FROM messages_prives WHERE id_message_prive=?');
$stmt->execute([$id_message_prive]);
$message=$stmt->fetch();
if (!$message){
    die('Message introuvable');
}
if ((int)$message['id_expediteur']===(int)$_SESSION['id_utilisateur']){
    $stmt=$pdo->prepare('UPDATE messages_prives SET suppression_expediteur=1 WHERE id_message_prive=?');
    $stmt->execute([$id_message_prive]);
    var_dump($stmt->rowCount()); exit;
} elseif ((int)$message['id_destinataire']===(int)$_SESSION['id_utilisateur']){
    $stmt=$pdo->prepare('UPDATE messages_prives SET suppression_destinataire=1 WHERE id_message_prive=?');
    $stmt->execute([$id_message_prive]);
} else {
    die('Tu ne peux pas supprimer ce message');
}
$stmt=$pdo->prepare('DELETE FROM messages_prives WHERE id_message_prive=? AND suppression_expediteur=1 AND suppression_destinataire=1');
$stmt->execute([$id_message_prive]);
if ($estAjax){
    header('Content-Type: application/json');
    echo json_encode(['succes'=>true]);
    exit;
}
header('Location: conversation.php?id_utilisateur=' . $id_interlocuteur);
exit;
?>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
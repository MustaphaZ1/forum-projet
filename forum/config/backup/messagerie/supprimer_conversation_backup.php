<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['id_utilisateur'])){
    header('Location: connexion.php');
    exit;
}
$id_interlocuteur=$_POST['id_interlocuteur'] ?? null;
if (!$id_interlocuteur || !ctype_digit((string)$id_interlocuteur)){
    die('Interlocuteur invalide');
}
$pdo=getPDO();
$stmt=$pdo->prepare('UPDATE messages_prives SET suppression_expediteur=1 WHERE id_expediteur=? AND id_destinataire=?');
$stmt->execute([$_SESSION['id_utilisateur'], $id_interlocuteur]);
$stmt=$pdo->prepare('UPDATE messages_prives SET suppression_destinataire=1 WHERE id_expediteur=? AND id_destinataire=?');
$stmt->execute([$id_interlocuteur, $_SESSION['id_utilisateur']]);
header('Location: messages_prives.php');
exit;
?>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['id_utilisateur'])){
    header('Location: connexioin.php');
    exit;
}
$id_message=$_POST['id_message']??null;
if (!$id_message || !ctype_digit((string)$id_message)){
    die('Message invalide.');
}
$pdo=getPDO();
$stmt=$pdo->prepare('SELECT id_sujet, id_utilisateur FROM messages WHERE id_message=?');
$stmt->execute([$id_message]);
$message=$stmt->fetch();
if(!$message){
    die('Message introuvable.');
}
if($message['id_utilisateur']!= $_SESSION['id_utilisateur']){
    die('Tu ne peux pas supprimer ce message !');
}
$stmt=$pdo->prepare('DELETE FROM messages WHERE id_message=?');
$stmt->execute([$id_message]);
header('Location: sujet.php?id_sujet=' . $message['id_sujet']);
exit;
?>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
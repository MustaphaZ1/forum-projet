<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['id_utilisateur'])){
    header('Location: connexion.php');
    exit;
}
$id_sujet=$_POST['id_sujet']??null;
if(!$id_sujet || !ctype_digit((string)$id_sujet)){
    die('Sujet invalide.');
}
$pdo=getPDO();
$stmt=$pdo->prepare('SELECT id_categorie, id_utilisateur FROM sujets WHERE id_sujet=?');
$stmt->execute([$id_sujet]);
$sujet=$stmt->fetch();
if(!$sujet){
    die('Sujet introuvable');
}
if($sujet['id_utilisateur']!= $_SESSION['id_utilisateur']){
    die('Tu ne peux pas supprimer ce sujet');
}
$stmt=$pdo->prepare('DELETE FROM sujets WHERE id_sujet=?');
$stmt->execute([$id_sujet]);
header('Location: categorie.php?id_categorie='.$sujet['id_categorie']);
exit;
?>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
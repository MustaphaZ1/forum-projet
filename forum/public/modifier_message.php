<?php
error_reporting(E_ALL);
ini_set('display_errors',1);
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
$pdo=getPDO();
// Il faut etre connecté pour modifier un message
if(!isset($_SESSION['id_utilisateur'])){
    header('Location: /../public/connexion.php');
    exit;
}
$id_message = $_GET['id_message']??null;
if(!$id_message){
    die('Message invalide.');
}
// On récupère le message
$stmt=$pdo->prepare('SELECT * FROM messages WHERE id_message=?');
$stmt->execute([$id_message]);
$message=$stmt->fetch();
// Vérifie que le message existe et que c'est bien son auteur
if(!$message || $message['id_utilisateur']!== $_SESSION['id_utilisateur']){
    die('Accès refusé.');
}
// Si le formulaire a été soumis
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $nouveau_contenu=trim($_POST['contenu']);
    if($nouveau_contenu!==''){
        $update=$pdo->prepare('UPDATE messages SET contenu=? WHERE id_message=?');
        $update->execute([$nouveau_contenu, $id_message]);
    }
    header('Location: sujet.php?id_sujet=' . $message['id_sujet']);
    exit;
}
?>
<main>
    <h1>Modifier le message</h1>
    <form action="" method="post">
        <textarea name="contenu" id="contenu" class="form-control mb-3" rows="5"><?= htmlspecialchars($message['contenu']) ?></textarea>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
        <a href="sujet.php?id_sujet=<?= $message['id_sujet'] ?>" class="btn btn-secondary">Annuler</a>
    </form>
</main>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['id_utilisateur'])){
    header('Location: connexion.php');
    exit;
}
$id_categorie=$_GET['id_categorie'] ??
$_POST['id_categorie'] ?? null ;
if (!$id_categorie || !ctype_digit((string)$id_categorie)){die('Catégorie invalide.');}
$pdo=getPDO();
$stmt=$pdo->prepare('SELECT * FROM categories WHERE id_categorie =?');
$stmt->execute([$id_categorie]);
$categorie=$stmt->fetch();
if (!$categorie) {
    die('Catégorie introuvable.');
}
$erreur='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verifier();
    $titre=trim($_POST['titre']??'');
    $contenu=trim($_POST['contenu']??'');
    if ($titre===''|| $contenu===''){
        $erreur='Le titre et le message sont obligatoires.';
    } else {
        // Créer un sujet
        $stmt=$pdo->prepare('INSERT INTO sujets (titre, id_categorie, id_utilisateur) VALUES (?, ?, ?)');
        $stmt->execute([$titre, $id_categorie, $_SESSION['id_utilisateur']]);
        $id_sujet=$pdo->lastInsertId();
        // Créer le premier message du sujet
        $stmt=$pdo->prepare('INSERT INTO messages (contenu,id_sujet,id_utilisateur) VALUES (?, ?, ?)');
        $stmt->execute([$contenu, $id_sujet, $_SESSION['id_utilisateur']]);
        header('Location: sujet.php?id_sujet=' . $id_sujet);
        exit;
    }
}
?>
        <main>
        <h1>Nouveau sujet</h1>
        <?php if($erreur): ?>
            <div class="alert alert-danger"><?=htmlspecialchars($erreur)?></div>
        <?php endif; ?>
        <form action="" method="post">
            <?= csrf_champ() ?>
            <input type="hidden" name="id_categorie" value="<?= $categorie['id_categorie'] ?>">
            <div class="mb-3">
                <label for="titre" class="form-label">Titre</label>
                <input type="text" name="titre" id="titre" class="form-control" required placeholder="Entre un titre">
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Message</label>
                <textarea name="contenu" id="contenu" class="form-control" rows="6" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Publier</button>
        </form>
        </main>
 <?php require_once __DIR__ . '/../includes/footer.php' ?>
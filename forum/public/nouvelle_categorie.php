<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['id_utilisateur'])){
    header('Location: connexion.php');
    exit;
}
$erreur='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $nom=trim($_POST['nom']??'');
    $description=trim($_POST['description']??'');
    if($nom===''){
        $erreur='Le nom de la catégorie est obligatoire.';
    } else {
        $pdo=getPDO();
        $stmt=$pdo->prepare('INSERT INTO categories (nom, description) VALUES (?,?)');
        $stmt->execute([$nom, $description]);
        header('Location: index.php');
        exit;
    }
}
?>
        <main>
        <h1>Nouvelle catégorie</h1>
        <?php if ($erreur): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>
        <form action="" method="post">
            <div class="mb-3">
                <label for="nom" class="form-label">Nom</label>
                <input type="text" name="nom" id="nom" class="form-control" required placeholder="Entrez le nom de la nouvelle catégorie ...">
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description</label>
                <textarea name="description" class="form-control" id="description" rows="3" placeholder="Entrez une description de la nouvelle catégorie ..."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Créer la catégorie</button>
        </form>
        </main>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
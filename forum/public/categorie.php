<?php 
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
$id_categorie=$_GET['id_categorie'] ?? null;
if (!$id_categorie || !ctype_digit((string)$id_categorie)){
    die('Catégorie invalide.');
}
$pdo=getPDO();
// Pour récuperer la catégorie
$stmt=$pdo->prepare('SELECT * FROM categories WHERE id_categorie = ?');
$stmt->execute([$id_categorie]);
$categorie=$stmt->fetch();
if (!$categorie){
    die('Catégorie introuvable.');
}
// Pour récupérer les sujets des catégories avec l'auteur
$stmt=$pdo->prepare('SELECT sujets.*, utilisateurs.pseudo FROM sujets JOIN utilisateurs ON sujets.id_utilisateur=utilisateurs.id_utilisateur WHERE sujets.id_categorie=? ORDER BY sujets.date_creation DESC');
$stmt->execute([$id_categorie]);
$sujets=$stmt->fetchAll();
?>
<p><a href="/forum/public/index.php">&larr; Retour aux catégories</a></p>
        <main>
        <h1><?= htmlspecialchars($categorie['nom']) ?></h1>
        <p class="text-muted"><?= htmlspecialchars($categorie['description']) ?></p>
        <?php if (isset($_SESSION['pseudo'])): ?>
            <a href="nouveau_sujet.php?id_categorie=<?= $categorie['id_categorie'] ?>" class="btn btn-primary mb-3">Nouveau sujet</a>
            <?php endif; ?>
        <div class="list-group">
            <?php foreach ($sujets as $sujet): ?>
                <a href="sujet.php?id_sujet=<?= $sujet['id_sujet'] ?>" class="list-group-item list-group-item-action">
                    <h5 class="mb-1"><?= htmlspecialchars($sujet['titre']) ?></h5>
                    <small class="text-muted">
                        par <?= htmlspecialchars($sujet['pseudo']) ?> - 
                        <?= date('d/m/Y H:i', strtotime($sujet['date_creation'])) ?>
                    </small>
                </a>
            <?php endforeach; ?>
            <?php if (empty($sujets)): ?>
                <p class="text-muted">Aucun sujet pour l'instant.</p>
            <?php endif; ?>
        </div>
        </main>

<?php require_once __DIR__ . '/../includes/footer.php' ?>
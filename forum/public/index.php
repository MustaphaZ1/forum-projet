<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
$pdo=getPDO();
?>
        <main>
        <?php 
        $pdo=getPDO();
        $stmt=$pdo->query('SELECT * FROM categories ORDER BY ordre DESC');
        $categories=$stmt->fetchAll(); ?>
        <?php if (isset($_SESSION['pseudo'])): ?>
            <a href="nouvelle_categorie.php" class="btn btn-primary mb-3">Ajouter une nouvelle catégorie</a>
        <?php endif; ?>
        <div class="list-group mt-4">
            <?php foreach($categories as $categorie): ?>
                <a href="categorie.php?id_categorie=<?= $categorie['id_categorie'] ?>"class="list-group-item list-group-item-action">
                    <h5 class="mb-1"><?= htmlspecialchars($categorie['nom']) ?></h5>
                    <p class="mb-1 text-muted"><?= htmlspecialchars($categorie['description']) ?></p>
                </a>
                <?php endforeach; ?>
        </div>
        </main>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['id_utilisateur'])){
    header('Location: connexion.php');
    exit;;
}
$pdo=getPDO();
$resultats=[];
$recherche='';
if ($_SERVER['REQUEST_METHOD']==='GET'){
    $recherche=trim($_GET['pseudo']??'');
    if ($recherche !==''){
        $stmt=$pdo->prepare('SELECT id_utilisateur, pseudo FROM utilisateurs WHERE pseudo LIKE ? AND id_utilisateur !=? ORDER BY pseudo ASC LIMIT 10');
        $stmt->execute(['%'.$recherche.'%', $_SESSION['id_utilisateur']]);
        $resultats=$stmt->fetchAll();
    }
}
?>
<body>
    <p><a href="/forum/public/index.php">&larr; Retour à l'accueil</a></p>
<div class="container mt-5" style="max-width: 800px;">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Nouveau message</h1>
        <a href="messages_prives.php" class="btn btn-secondary">Retour aux messages</a>
    </div>
    <form action="" method="get" class="mb-4">
        <label for="pseudo" class="form-label">Rechercher un utilisateur</label>
        <div class="input-group">
            <input type="text" name="pseudo" id="pseudo" class="form-control" placeholder="Entrez un pseudo" value="<?= htmlspecialchars($recherche) ?>" required>
            <button type="submit" class="btn btn-primary">Rechercher</button>
        </div>
    </form>
    <?php if ($recherche !== ''): ?>
        <?php if (empty($resultats)): ?>
            <div class="alert alert-secondary">Aucun utilisateur trouvé</div>
        <?php else: ?>
            <h5 class="mb-3">Utilisateurs trouvés</h5>
            <?php foreach ($resultats as $utilisateur): ?>
                <div class="card mb-2">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <strong><?= htmlspecialchars($utilisateur['pseudo']) ?></strong>
                        <a href="conversation.php?id_utilisateur=<?= (int) $utilisateur['id_utilisateur'] ?>" class="btn btn-primary">Envoyer un message</a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
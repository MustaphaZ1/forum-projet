<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
if (!isset($_SESSION['id_utilisateur'])){
    header('Location: connexion.php');
    exit;
}
$pdo=getPDO();
// Récuperer les infos de l'utilisateur
$stmt=$pdo->prepare('SELECT * FROM utilisateurs WHERE id_utilisateur=?');
$stmt->execute([$_SESSION['id_utilisateur']]);
$utilisateur=$stmt->fetch();
// Nombre de sujets créés
$stmt=$pdo->prepare('SELECT id_sujet FROM sujets WHERE id_utilisateur=?');
$stmt->execute([$_SESSION['id_utilisateur']]);
$nb_sujets=$stmt->rowCount();
// Nombre total de messages postés (sujets+réponses confondus)
$stmt=$pdo->prepare('SELECT id_message FROM messages WHERE id_utilisateur=?');
$stmt->execute([$_SESSION['id_utilisateur']]);
$nb_messages=$stmt->rowCount();
// Nombre de reponses uniquement
$stmt=$pdo->prepare('SELECT id_message FROM messages WHERE id_utilisateur=? AND id_message NOT IN (SELECT MIN(id_message) FROM messages GROUP BY id_sujet)');
$stmt->execute([$_SESSION['id_utilisateur']]);
$nb_reponses=$stmt->rowCount();
?>
        <main>
        <p><a href="/forum/public/index.php">&larr; Retour aux catégories</a></p>
        <h1>Mon profil</h1>
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title"><?= htmlspecialchars($utilisateur['pseudo']) ?></h5>
                <p class="card-text text-muted">
                    <?= htmlspecialchars($utilisateur['email']) ?><br>
                    Membre depuis le <?= date('d/m/Y', strtotime($utilisateur['date_inscription'])) ?>
                </p>
            </div>
        </div>
        <div class="row text-center">
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h3><?= $nb_sujets ?></h3>
                        <p class="text-muted mb-0">Sujets créés</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h3><?= $nb_reponses ?></h3>
                        <p class="text-muted mb-0">Réponses</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body">
                        <h3><?= $nb_messages ?></h3>
                        <p class="text-muted mb-0">Messages au total</p>
                    </div>
                </div>
            </div>
        </div>
        </main>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
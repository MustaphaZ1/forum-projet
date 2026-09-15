<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
$erreur ='';
$success='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $pseudo =trim($_POST['pseudo']??'');
    $email=trim($_POST['email'] ?? '');
    $mot_de_passe=$_POST['mot_de_passe']??'';
    if ($pseudo===''||$email===''||$mot_de_passe===''){
        $erreur='Tous les champs sont obligatoires.';}
    elseif(strlen($mot_de_passe)<6){
        $erreur='Le mot de passe doit contenir au moins 6 caractères.';}
    else {
        $pdo=getPDO();
        $stmt=$pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE pseudo = ? OR email=?');
        $stmt->execute([$pseudo, $email]);
        if ($stmt->fetch()){
            $erreur='Ce pseudo ou cet email est déja utilisé. Veuillez vous connecter avec vos identifiants.';}
        else {
            $hash=password_hash($mot_de_passe, PASSWORD_DEFAULT);
            $stmt=$pdo->prepare('INSERT INTO utilisateurs (pseudo,email,mot_de_passe) VALUES (?,?,?)');
            $stmt->execute([$pseudo,$email,$hash]);
            $success='Inscritpion réussie ! Tu es maintenant connecté tu peux accéder au <a href="sujet.php">sujets</a>.';
            $_SESSION['id_utilisateur']=$pdo->lastInsertId();
            $_SESSION['pseudo']=$pseudo;
            header('Location: index.php');
            exit;
        }
    }
}
?>
    <main>
    <h1 class="mb-4">Inscription</h1>
    <?php if($erreur): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><?= ($success) ?></div>
    <?php endif; ?>
    <form action="" method="post">
        <div class="mb-3">
            <label for="pseudo" class="form-label">Pseudo</label>
            <input type="text" name="pseudo" id="pseudo" class="form-control" required placeholder="Entrez votre pseudo">
        </div>
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" required placeholder="Entrez votre email">
        </div>
        <div class="mb-3">
            <label for="mot_de_passe" class="form-label">Mot de passe</label>
            <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required placeholder="Entrez un mot de passe valable">
        </div>
        <button type="submit" class="btn btn-primary">S'inscrire</button>
    </form>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
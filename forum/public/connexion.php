<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
$erreur='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $email=trim($_POST['email']??'');
    $mot_de_passe=$_POST['mot_de_passe']??'';
    if ($email === ''|| $mot_de_passe ===''){
        $erreur='Tous les champs sont obligatoires.';
    } else {
        $pdo=getPDO();
        $stmt=$pdo->prepare('SELECT id_utilisateur, pseudo, mot_de_passe, email_confirme FROM utilisateurs WHERE email=?');
        $stmt->execute([$email]);
        $utilisateur=$stmt->fetch();
        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])){
            if(!$utilisateur['email_confirme']){
                $erreur='Merci de confirmer ton email avant de te connecter';
            } else {
                $_SESSION['id_utilisateur']=$utilisateur['id_utilisateur'];
                $_SESSION['pseudo']=$utilisateur['pseudo'];
                header('Location: index.php');
                exit;
            }
            } else {
            $erreur='Email ou mot de passe incorrect.';
        }
    }
}
?>
    <main>
    <h1 class="mb-4">Connexion</h1>
    <?php if($erreur):?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif;?>
    <form action="" method="post">
        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" name="email" id="email" class="form-control" required placeholder="Entrez votre email">
        </div>
        <div class="mb-3">
            <label for="mot_de_passe" class="form-label">Mot de passe</label>
            <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" placeholder="Entrez votre mot de passe" required>
        </div>
        <button type="submit" class="btn btn-primary">Se connecter</button>
    </form>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
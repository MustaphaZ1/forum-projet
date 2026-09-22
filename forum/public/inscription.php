<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
$erreur ='';
$success='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    $pseudo =trim($_POST['pseudo'] ?? '');
    $email=trim($_POST['email'] ?? '');
    $mot_de_passe=$_POST['mot_de_passe'] ?? '';
    if ($pseudo===''||$email===''||$mot_de_passe===''){
        $erreur='Tous les champs sont obligatoires.';}
    elseif(strlen($mot_de_passe)<6){
        $erreur='Le mot de passe doit contenir au moins 6 caractères.';}
    else {
        $captchaReponse = $_POST['g-recaptcha-response'] ?? '';
        if ($captchaReponse === ''){
            $erreur='Merci de valider le captcha';
        } else {
            $verif = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $_ENV['RECAPTCHA_SECRET_KEY'] . '&response=' . $captchaReponse);
            $verifData = json_decode($verif);
            if (!$verifData->success){
                $erreur='Le captcha est invalide, réessaies';
            } else {         
                $pdo=getPDO();         
                $stmt=$pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE pseudo = ? OR email=?');         
                $stmt->execute([$pseudo, $email]);         
                if ($stmt->fetch()){             
                    $erreur='Ce pseudo ou cet email est déja utilisé. Veuillez vous connecter avec vos identifiants.';}         
                else {             
                    $hash=password_hash($mot_de_passe, PASSWORD_DEFAULT);             
                    $token=bin2hex(random_bytes(32));             
                    $stmt=$pdo->prepare('INSERT INTO utilisateurs (pseudo,email,mot_de_passe,token_confirmation) VALUES (?,?,?,?)');             
                    $stmt->execute([$pseudo,$email,$hash,$token]);             
                    $lienConfirmation="https://forum-resu.infinityfree.me/forum/public/confirmation.php?token=" . $token;             
                    $mail=new PHPMailer(true);             
                    try {                 
                        $mail->isSMTP();                 
                        $mail->Host=$_ENV['SMTP_HOST'];                 
                        $mail->SMTPAuth=true;                 
                        $mail->Username=$_ENV['SMTP_USER'];                 
                        $mail->Password=$_ENV['SMTP_PASS'];                 
                        $mail->SMTPSecure='tls';                 
                        $mail->Port=$_ENV['SMTP_PORT'];                 
                        $mail->CharSet='UTF-8';                 
                        $mail->setFrom($_ENV['SMTP_USER'], 'Forum');                 
                        $mail->addAddress($email);                 
                        $mail->Subject='Confirmes ton inscription';                 
                        $mail->isHTML(true);                 
                        $mail->Body="Bonjour $pseudo,<br><br>Cliques sur ce lien pour confirmer ton inscription :<br><a href=\"$lienConfirmation\">Confirmer mon compte</a>";                 
                        $mail->send();             } 
                    catch (Exception $e){                 
                        $erreur="L'inscription a réussi mais l'email n'a pas pu etre envoyé";             }             
                    $success='Inscritpion réussie ! Confirmes ton inscription sur ton email';         }     }
        }
    }
}
?>
    <main>
    <p><a href="/index.php">&larr; Retour aux catégories</a></p>
    <h1 class="mb-4">Inscription</h1>
    <?php if($erreur): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><?= ($success) ?></div>
    <?php endif; ?>
    <form action="" method="post">
        <div class="mb-3" id="couleur_dark_placeholder">
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
        <div class="g-recaptcha" data-sitekey="<?= $_ENV['RECAPTCHA_SITE_KEY']?>"></div>
        <button type="submit" class="btn btn-primary">S'inscrire</button>
    </form>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
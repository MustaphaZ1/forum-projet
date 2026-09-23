<?php
// CONFIGURATION ET CHARGEMENT
ini_set('display_errors', 1);
error_reporting(E_ALL);
session_start(); //DEMARRE OU REPREND LA SESSION
// CHARGEMENT DES DEPENDANCES
require_once __DIR__ . '/../vendor/autoload.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
// INITIALISATION DES VARIABLES POUR MESSAGES D'ERREUR
$erreur ='';
$success='';
if ($_SERVER['REQUEST_METHOD']==='POST'){
    // RECUPERE LES DONNEES DU FORMULAIRE ET ENLEVE LES ESPACES SUPERFLUS (trim)
    $pseudo =trim($_POST['pseudo'] ?? '');
    $email=trim($_POST['email'] ?? '');
    $mot_de_passe=$_POST['mot_de_passe'] ?? '';
    // SI CHAMPS VIDES
    if ($pseudo===''||$email===''||$mot_de_passe===''){
        $erreur='Tous les champs sont obligatoires.';}
    // VALIDATION DE LA LONGUEUR DU MDP
    elseif(strlen($mot_de_passe)<6){
        $erreur='Le mot de passe doit contenir au moins 6 caractères.';}
    else {
        // VERIFICATION GOOGLE RECAPTCHA V2
        $captchaReponse = $_POST['g-recaptcha-response'] ?? '';
        if ($captchaReponse === ''){
            $erreur='Merci de valider le captcha';
        } else {
            // ENVOIE UNE REQUETE A L'API DE GOOGLE POUR VERIFIER LE CAPTCHA
            $verif = file_get_contents('https://www.google.com/recaptcha/api/siteverify?secret=' . $_ENV['RECAPTCHA_SECRET_KEY'] . '&response=' . $captchaReponse);
            $verifData = json_decode($verif);
            if (!$verifData->success){
                $erreur='Le captcha est invalide, réessaies';
            } else {
                // VERIFICATION SI LE PSEUDO OU L'EMAIL EXISTE DEJA DANS LA BASE
                $pdo=getPDO();         
                $stmt=$pdo->prepare('SELECT id_utilisateur FROM utilisateurs WHERE pseudo = ? OR email=?');         
                $stmt->execute([$pseudo, $email]);         
                if ($stmt->fetch()){             
                    $erreur='Ce pseudo ou cet email est déja utilisé. Veuillez vous connecter avec vos identifiants.';}         
                else {
                    // INSCRIPTION DE L'UTILISATEUR
                    $hash=password_hash($mot_de_passe, PASSWORD_DEFAULT); // SECURISE LE MDP AVEC UN HACHAGE
                    $token=bin2hex(random_bytes(32)); // GENERE UN TOKEN POUR LA CONFIRMATION PAR EMAIL
                    // INSERTION DE L'UTILISATEUR DANS LA BASE AVEC LE STATUT NON CONFIRME
                    $stmt=$pdo->prepare('INSERT INTO utilisateurs (pseudo,email,mot_de_passe,token_confirmation) VALUES (?,?,?,?)');             
                    $stmt->execute([$pseudo,$email,$hash,$token]);
                    // GENERATION DU LIEN DE CONFIRMATION ENVOYER PAR EMAIL
                    $lienConfirmation="https://forum-resu.infinityfree.me/forum/public/confirmation.php?token=" . $token;
                    // ENVOI DE L'EMAIL AVEC PHPMAILER
                    $mail=new PHPMailer(true);             
                    try {
                        // CONFIGURATION DU SERVEUR SMTP
                        $mail->isSMTP(); // UTILISE LE PROTOCOLE SMTP POUR L'ENVOI
                        $mail->Host=$_ENV['SMTP_HOST']; // DEFINIT L'HOTE DEPUIS LES VARIABLES ENV
                        $mail->SMTPAuth=true; // ACTIVE L'AUTHENTIFICATION
                        $mail->Username=$_ENV['SMTP_USER']; // NOM D'UTILISATEUR
                        $mail->Password=$_ENV['SMTP_PASS']; // MDP SMTP
                        $mail->SMTPSecure='tls'; // ACTIVE LE CHIFFREMENT TLS
                        $mail->Port=$_ENV['SMTP_PORT']; // PORT DU SERVEUR SMTP
                        $mail->CharSet='UTF-8';
                        // PARAMETRES DU MESSAGE
                        $mail->setFrom($_ENV['SMTP_USER'], 'Forum'); // EXPEDITEUR DE L'EMAIL
                        $mail->addAddress($email); // DESTINATAIRE (EMAIL D'INSCRIPTION)
                        $mail->Subject='Confirmes ton inscription'; // OBJET DE L'EMAIL
                        $mail->isHTML(true); // PERMET D'UTILISER DU CODE DANS LE CORPS DU MESSAGE
                        // CORPS DU MESSAGE AVEC LE LIEN CONTENANT LE TOKEN
                        $mail->Body="Bonjour $pseudo,<br><br>Cliques sur ce lien pour confirmer ton inscription :<br><a href=\"$lienConfirmation\">Confirmer mon compte</a>";                 
                        $mail->send(); // ENVOI DE L'EMAIL
                        }
                    catch (Exception $e){
                        // SI L'ENVOI ECHOUE MAIS L'INSCRIPTION EST QUAND MEME EFFECTIVE SUR LA BASE
                        $erreur="L'inscription a réussi mais l'email n'a pas pu etre envoyé";
                        }            
                    $success='Inscritpion réussie ! Confirmes ton inscription sur ton email';
                    }
                }
        }
    }
}
?>
    <main>
        <!-- Lien de retour vers la page d'accueil -->
    <p><a href="/index.php">&larr; Retour aux catégories</a></p>
    <h1 class="mb-4">Inscription</h1>
    <?php if($erreur): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
    <div class="alert alert-success"><?= ($success) ?></div>
    <?php endif; ?>
    <!-- FORMULAIRE D'INSCRIPTION (POST) -->
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
    <!-- WIDGET GOOGLE RECAPTCHA CLE DU SITE DEPUIS ENV -->
        <div class="g-recaptcha" data-sitekey="<?= $_ENV['RECAPTCHA_SITE_KEY']?>"></div>
        <button type="submit" class="btn btn-primary">S'inscrire</button>
    </form>
    </main>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
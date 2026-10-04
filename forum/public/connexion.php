<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
$erreur='';
$estAjax = !empty($_POST['ajax']);
if ($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verifier();
    $email=trim($_POST['email']??'');
    $mot_de_passe=$_POST['mot_de_passe']??'';
    if ($email === ''|| $mot_de_passe ===''){
        $erreur='Tous les champs sont obligatoires.';
    } else {
        $pdo=getPDO();
        $stmt=$pdo->prepare('SELECT id_utilisateur, pseudo, mot_de_passe, email_confirme, cle_privee_chiffree_e2e, iv_cle_privee_e2e, sel_derivation_e2e FROM utilisateurs WHERE email=?');
        $stmt->execute([$email]);
        $utilisateur=$stmt->fetch();
        if ($utilisateur && password_verify($mot_de_passe, $utilisateur['mot_de_passe'])){
            if(!$utilisateur['email_confirme']){
                $erreur='Merci de confirmer ton email avant de te connecter';
            } else {
                $_SESSION['id_utilisateur']=$utilisateur['id_utilisateur'];
                $_SESSION['pseudo']=$utilisateur['pseudo'];
                if ($estAjax){
                    header('Content-Type: application/json');
                    echo json_encode([
                        'succes' => true,
                        'cle_privee_chiffree' => $utilisateur['cle_privee_chiffree_e2e'],
                        'iv_cle_privee' => $utilisateur['iv_cle_privee_e2e'],
                        'sel_derivation' => $utilisateur['sel_derivation_e2e'],
                    ]);
                    exit;
                }
                header('Location: index.php');
                exit;
            }
        } else {
            $erreur='Email ou mot de passe incorrect.';
        }
        if ($estAjax){
            header('Content-Type: application/json');
            echo json_encode(['succes'=>false, 'erreur'=>$erreur]);
            exit;
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
    <main>
    <h1 class="mb-4">Connexion</h1>
    <?php if($erreur):?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif;?>
    <form action="" method="post" id="form-connexion">
        <?= csrf_champ() ?>
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
<script>
document.getElementById('form-connexion').addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = document.getElementById('email').value;
    const motDePasse = document.getElementById('mot_de_passe').value;
    const csrfToken = document.querySelector('#form-connexion [name=csrf_token]').value;

    const donnees = new FormData();
    donnees.append('email', email);
    donnees.append('mot_de_passe', motDePasse);
    donnees.append('csrf_token', csrfToken);
    donnees.append('ajax', '1');

    try {
        const reponse = await fetch('connexion.php', { method: 'POST', body: donnees });
        const resultat = await reponse.json();

        if (resultat.succes) {
            if (resultat.cle_privee_chiffree) {
                await deverrouillerClePrivee(
                    motDePasse,
                    resultat.cle_privee_chiffree,
                    resultat.iv_cle_privee,
                    resultat.sel_derivation
                );
            }
            window.location.href = 'index.php';
        } else {
            alert(resultat.erreur || 'Erreur de connexion');
        }
    } catch (err) {
        console.error('Erreur:', err);
        alert('Erreur de connexion au serveur.');
    }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit;
}
$pdo = getPDO();
$id_utilisateur = $_SESSION['id_utilisateur'];

// Redirige vers la configuration si aucun code n'est encore défini
$stmt = $pdo->prepare('SELECT code_acces_hash FROM utilisateurs WHERE id_utilisateur=?');
$stmt->execute([$id_utilisateur]);
$verif = $stmt->fetch();
if (!$verif['code_acces_hash']) {
    header('Location: definir_codes_messagerie.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();

    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id_utilisateur=?');
    $stmt->execute([$id_utilisateur]);
    $utilisateur = $stmt->fetch();

    if ($utilisateur['code_bloque_jusqua'] && strtotime($utilisateur['code_bloque_jusqua']) > time()) {
        $erreur = 'Trop de tentatives. Réessaie dans quelques minutes.';
    } else {
        $code_saisi = trim($_POST['code_pin'] ?? '');

        $est_piege = $utilisateur['code_piege_hash'] && password_verify($code_saisi, $utilisateur['code_piege_hash']);
        $est_acces = $utilisateur['code_acces_hash'] && password_verify($code_saisi, $utilisateur['code_acces_hash']);

        if ($est_piege) {
            $log = $pdo->prepare('INSERT INTO journal_suppressions (id_utilisateur_supprime, pseudo_supprime, type_suppression) VALUES (?, ?, "piege")');
            $log->execute([$id_utilisateur, $utilisateur['pseudo']]);

            $pdo->prepare('DELETE FROM utilisateurs WHERE id_utilisateur=?')->execute([$id_utilisateur]);

            session_destroy();
            $erreur = 'Code incorrect.';
        } elseif ($est_acces) {
            $pdo->prepare('UPDATE utilisateurs SET tentatives_code=0, code_bloque_jusqua=NULL WHERE id_utilisateur=?')->execute([$id_utilisateur]);
            $_SESSION['messagerie_deverrouillee'] = true;
            header('Location: messages_prives.php');
            exit;
        } else {
            $tentatives = $utilisateur['tentatives_code'] + 1;
            $blocage = null;
            if ($tentatives >= 3) {
                $blocage = date('Y-m-d H:i:s', time() + 200);
                $tentatives = 0;
            }
            $pdo->prepare('UPDATE utilisateurs SET tentatives_code=?, code_bloque_jusqua=? WHERE id_utilisateur=?')
                ->execute([$tentatives, $blocage, $id_utilisateur]);
            $erreur = 'Code incorrect.';
        }
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<main>
    <h1>Accès à la messagerie</h1>
    <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
    <form action="" method="post">
        <?= csrf_champ() ?>
        <div class="mb-3">
            <label class="form-label">Code à 4 chiffres</label>
            <input type="password" name="code_pin" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" required autofocus>
        </div>
        <button type="submit" class="btn btn-primary">Valider</button>
    </form>
</main>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
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
$erreur = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $cle_publique = $_POST['cle_publique'] ?? '';
    $cle_privee_chiffree = $_POST['cle_privee_chiffree'] ?? '';
    $iv_cle_privee = $_POST['iv_cle_privee'] ?? '';
    $sel_derivation = $_POST['sel_derivation'] ?? '';

    if ($cle_publique === '' || $cle_privee_chiffree === '' || $iv_cle_privee === '' || $sel_derivation === '') {
        $erreur = 'Données de chiffrement incomplètes.';
    } else {
        $stmt = $pdo->prepare('UPDATE utilisateurs SET cle_publique_e2e=?, cle_privee_chiffree_e2e=?, iv_cle_privee_e2e=?, sel_derivation_e2e=? WHERE id_utilisateur=?');
        $stmt->execute([$cle_publique, $cle_privee_chiffree, $iv_cle_privee, $sel_derivation, $id_utilisateur]);
        $success = 'Chiffrement de bout en bout activé.';
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<main>
    <h1>Activer le chiffrement de bout en bout (test)</h1>
    <p class="text-muted">Cette page génère une paire de clés dans ton navigateur. La clé privée est chiffrée avec ton mot de passe avant d'être envoyée — le serveur ne voit jamais ta clé privée en clair.</p>
    <?php if ($erreur): ?><div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="alert alert-success"><?= htmlspecialchars($success) ?></div><?php endif; ?>

    <form id="form-activation" method="post" onsubmit="return false;">
        <?= csrf_champ() ?>
        <div class="mb-3">
            <label class="form-label">Confirme ton mot de passe</label>
            <input type="password" name="mot_de_passe" id="mot_de_passe" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary">Générer mes clés</button>
    </form>
    <div id="resultat" class="mt-3"></div>
</main>
<script src="js/crypto_e2e.js"></script>
<script>
document.getElementById('form-activation').addEventListener('submit', async (e) => {
    e.preventDefault();
    const motDePasse = document.getElementById('mot_de_passe').value;
    const resultatDiv = document.getElementById('resultat');
    resultatDiv.innerHTML = '<div class="alert alert-info">Génération en cours...</div>';

    try {
        const donnees = await activerChiffrementE2E(motDePasse);

        const formData = new FormData();
        formData.append('csrf_token', document.querySelector('[name=csrf_token]').value);
        formData.append('cle_publique', donnees.cle_publique);
        formData.append('cle_privee_chiffree', donnees.cle_privee_chiffree);
        formData.append('iv_cle_privee', donnees.iv_cle_privee);
        formData.append('sel_derivation', donnees.sel_derivation);

        const reponse = await fetch('activer_chiffrement_e2e.php', { method: 'POST', body: formData });
        if (reponse.redirected || reponse.ok) {
            resultatDiv.innerHTML = '<div class="alert alert-success">Clés générées et envoyées. Recharge la page pour confirmer.</div>';
        }
    } catch (err) {
        resultatDiv.innerHTML = '<div class="alert alert-danger">Erreur : ' + err.message + '</div>';
        console.error(err);
    }
});
</script>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
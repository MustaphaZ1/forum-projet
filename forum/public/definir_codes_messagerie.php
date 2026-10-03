<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['id_utilisateur'])) {
    header('Location: connexion.php');
    exit;
}
$pdo = getPDO();
$erreur = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verifier();
    $code_acces = trim($_POST['code_acces'] ?? '');
    $code_acces_confirm = trim($_POST['code_acces_confirm'] ?? '');
    $code_piege = trim($_POST['code_piege'] ?? '');
    $code_piege_confirm = trim($_POST['code_piege_confirm'] ?? '');

    if (!preg_match('/^\d{4}$/', $code_acces) || !preg_match('/^\d{4}$/', $code_piege)) {
        $erreur = 'Les deux codes doivent être composés de 4 chiffres.';
    } elseif ($code_acces !== $code_acces_confirm || $code_piege !== $code_piege_confirm) {
        $erreur = 'La confirmation ne correspond pas au code saisi.';
    } elseif ($code_acces === $code_piege) {
        $erreur = 'Le code d\'accès et le code piège doivent être différents.';
    } else {
        $stmt = $pdo->prepare('UPDATE utilisateurs SET code_acces_hash=?, code_piege_hash=?, tentatives_code=0, code_bloque_jusqua=NULL WHERE id_utilisateur=?');
        $stmt->execute([
            password_hash($code_acces, PASSWORD_DEFAULT),
            password_hash($code_piege, PASSWORD_DEFAULT),
            $_SESSION['id_utilisateur']
        ]);
        $success = 'Tes codes ont été enregistrés.';
    }
}
require_once __DIR__ . '/../includes/header.php';
?>
<main>
    <h1>Codes d'accès à la messagerie</h1>
    <p class="text-muted">Définis un code à 4 chiffres pour accéder à ta messagerie, et un autre piège.</p>
    <?php if ($erreur): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?> Redirection dans <span id="compte">3</span> secondes...</div>
        <script>
            let secondes = 3;
            const compteur = document.getElementById('compte');
            const interval = setInterval(() => {
                secondes--;
                compteur.textContent = secondes;
                if (secondes <= 0) {
                    clearInterval(interval);
                    window.location.href = 'acces_messagerie.php';
                }
            }, 1000);
        </script>
    <?php endif; ?>
    <form action="" method="post">
        <?= csrf_champ() ?>
        <div class="mb-3">
            <label class="form-label">Code d'accès (4 chiffres)</label>
            <input type="password" name="code_acces" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirme le code d'accès</label>
            <input type="password" name="code_acces_confirm" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Code piège (4 chiffres, différent du précédent)</label>
            <input type="password" name="code_piege" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" required>
        </div>
        <div class="mb-3">
            <label class="form-label">Confirme le code piège</label>
            <input type="password" name="code_piege_confirm" class="form-control" inputmode="numeric" pattern="\d{4}" maxlength="4" required>
        </div>
        <button type="submit" class="btn btn-primary">Enregistrer</button>
    </form>
</main>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
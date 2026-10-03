<?php
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_champ(): string {
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token()) . '">';
}

function csrf_verifier(): void {
    $jeton = $_POST['csrf_token'] ?? '';
    if ($jeton === '' || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $jeton)) {
        http_response_code(403);
        die('Requête invalide (jeton de sécurité manquant ou expiré). Rechargez la page et réessayez.');
    }
}
?>
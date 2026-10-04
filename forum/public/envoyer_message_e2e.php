<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['messagerie_deverrouillee'])) {
    http_response_code(401);
    echo json_encode(['succes' => false, 'erreur' => 'Non autorisé']);
    exit;
}
csrf_verifier();

$id_utilisateur = $_SESSION['id_utilisateur'];
$id_destinataire = $_POST['id_destinataire'] ?? null;
$contenu_chiffre = $_POST['contenu_chiffre'] ?? '';
$iv = $_POST['iv'] ?? '';

if (!$id_destinataire || !ctype_digit((string)$id_destinataire)) {
    echo json_encode(['succes' => false, 'erreur' => 'Destinataire invalide']);
    exit;
}
if ($contenu_chiffre === '' || $iv === '') {
    echo json_encode(['succes' => false, 'erreur' => 'Message vide']);
    exit;
}

$pdo = getPDO();
$stmt = $pdo->prepare('INSERT INTO messages_prives_e2e (id_expediteur, id_destinataire, contenu_chiffre, iv) VALUES (?, ?, ?, ?)');
$stmt->execute([$id_utilisateur, $id_destinataire, $contenu_chiffre, $iv]);

echo json_encode(['succes' => true]);
exit;
<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
if (!isset($_SESSION['id_utilisateur'])) {
    http_response_code(401);
    echo json_encode(['erreur' => 'Non connecté']);
    exit;
}
$id_utilisateur = $_GET['id_utilisateur'] ?? null;
if (!$id_utilisateur || !ctype_digit((string)$id_utilisateur)) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Utilisateur invalide']);
    exit;
}
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT cle_publique_e2e FROM utilisateurs WHERE id_utilisateur=?');
$stmt->execute([$id_utilisateur]);
$resultat = $stmt->fetch();
if (!$resultat || !$resultat['cle_publique_e2e']) {
    http_response_code(404);
    echo json_encode(['erreur' => 'Cet utilisateur n\'a pas activé le chiffrement E2E']);
    exit;
}
echo json_encode(['cle_publique' => $resultat['cle_publique_e2e']]);
exit;
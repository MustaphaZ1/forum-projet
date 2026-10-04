<?php
session_start();
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['messagerie_deverrouillee'])) {
    http_response_code(401);
    echo json_encode(['erreur' => 'Non autorisé']);
    exit;
}
$id_utilisateur = $_SESSION['id_utilisateur'];
$id_interlocuteur = $_GET['id_interlocuteur'] ?? null;
if (!$id_interlocuteur || !ctype_digit((string)$id_interlocuteur)) {
    http_response_code(400);
    echo json_encode(['erreur' => 'Interlocuteur invalide']);
    exit;
}
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT id_message_e2e, id_expediteur, id_destinataire, contenu_chiffre, iv, date_envoi FROM messages_prives_e2e WHERE (id_expediteur=? AND id_destinataire=? AND suppression_expediteur=0) OR (id_expediteur=? AND id_destinataire=? AND suppression_destinataire=0) ORDER BY date_envoi ASC, id_message_e2e ASC');
$stmt->execute([$id_utilisateur, $id_interlocuteur, $id_interlocuteur, $id_utilisateur]);
$messages = $stmt->fetchAll();

$resultat = [];
foreach ($messages as $m) {
    $resultat[] = [
        'id_message_e2e' => (int)$m['id_message_e2e'],
        'est_moi' => (int)$m['id_expediteur'] === (int)$id_utilisateur,
        'contenu_chiffre' => $m['contenu_chiffre'],
        'iv' => $m['iv'],
        'date_envoi' => $m['date_envoi'],
    ];
}
echo json_encode($resultat);
exit;
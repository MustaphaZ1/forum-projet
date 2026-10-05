<?php
session_start();
date_default_timezone_set('Europe/Paris');
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
$depuis_id = $_GET['depuis_id'] ?? 0;
if (!ctype_digit((string)$depuis_id)) {
    $depuis_id = 0;
}

$pdo = getPDO();
if ($depuis_id > 0) {
    // Rafraîchissement incrémental : uniquement les messages plus récents que ceux déjà affichés
    $stmt = $pdo->prepare('SELECT id_message_e2e, id_expediteur, id_destinataire, contenu_chiffre, iv, date_envoi FROM messages_prives_e2e WHERE ((id_expediteur=? AND id_destinataire=? AND suppression_expediteur=0) OR (id_expediteur=? AND id_destinataire=? AND suppression_destinataire=0)) AND id_message_e2e > ? AND (expire_a IS NULL OR expire_a > ?) ORDER BY date_envoi ASC, id_message_e2e ASC');
    $stmt->execute([$id_utilisateur, $id_interlocuteur, $id_interlocuteur, $id_utilisateur, $depuis_id, date('Y-m-d H:i:s')]);
    $messages = $stmt->fetchAll();
} else {
    // Premier chargement : les 30 derniers messages seulement
    $stmt = $pdo->prepare('SELECT id_message_e2e, id_expediteur, id_destinataire, contenu_chiffre, iv, date_envoi FROM messages_prives_e2e WHERE ((id_expediteur=? AND id_destinataire=? AND suppression_expediteur=0) OR (id_expediteur=? AND id_destinataire=? AND suppression_destinataire=0)) AND (expire_a IS NULL OR expire_a > ?) ORDER BY date_envoi DESC, id_message_e2e DESC LIMIT 30');
    $stmt->execute([$id_utilisateur, $id_interlocuteur, $id_interlocuteur, $id_utilisateur, date('Y-m-d H:i:s')]);
    $messages = array_reverse($stmt->fetchAll()); // remis dans l'ordre chronologique
}
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
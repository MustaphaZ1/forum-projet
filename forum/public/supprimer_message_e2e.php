<?php
session_start();
$estAjax = !empty($_POST['ajax']);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['messagerie_deverrouillee'])) {
    header('Location: acces_messagerie.php');
    exit;
}
csrf_verifier();

$id_message_e2e = $_POST['id_message_e2e'] ?? null;
$id_interlocuteur = $_POST['id_interlocuteur'] ?? null;
if (!$id_message_e2e || !ctype_digit((string)$id_message_e2e)) {
    die('Message invalide');
}
if (!$id_interlocuteur || !ctype_digit((string)$id_interlocuteur)) {
    die('Interlocuteur invalide');
}
$pdo = getPDO();
$stmt = $pdo->prepare('SELECT id_expediteur, id_destinataire FROM messages_prives_e2e WHERE id_message_e2e=?');
$stmt->execute([$id_message_e2e]);
$message = $stmt->fetch();
if (!$message) {
    die('Message introuvable');
}

if ((int)$message['id_expediteur'] === (int)$_SESSION['id_utilisateur']) {
    $pdo->prepare('UPDATE messages_prives_e2e SET suppression_expediteur=1 WHERE id_message_e2e=?')->execute([$id_message_e2e]);
} elseif ((int)$message['id_destinataire'] === (int)$_SESSION['id_utilisateur']) {
    $pdo->prepare('UPDATE messages_prives_e2e SET suppression_destinataire=1 WHERE id_message_e2e=?')->execute([$id_message_e2e]);
} else {
    die('Tu ne peux pas supprimer ce message');
}

$pdo->prepare('DELETE FROM messages_prives_e2e WHERE id_message_e2e=? AND suppression_expediteur=1 AND suppression_destinataire=1')->execute([$id_message_e2e]);

if ($estAjax) {
    header('Content-Type: application/json');
    echo json_encode(['succes' => true]);
    exit;
}
header('Location: conversation_e2e.php?id_utilisateur=' . $id_interlocuteur);
exit;
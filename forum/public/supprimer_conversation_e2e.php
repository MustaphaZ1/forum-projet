<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['messagerie_deverrouillee'])) {
    csrf_verifier();
    header('Location: acces_messagerie.php');
    exit;
}
csrf_verifier();

$id_interlocuteur = $_POST['id_interlocuteur'] ?? null;
if (!$id_interlocuteur || !ctype_digit((string)$id_interlocuteur)) {
    die('Interlocuteur invalide');
}
$pdo = getPDO();
$pdo->prepare('UPDATE messages_prives_e2e SET suppression_expediteur=1 WHERE id_expediteur=? AND id_destinataire=?')
    ->execute([$_SESSION['id_utilisateur'], $id_interlocuteur]);
$pdo->prepare('UPDATE messages_prives_e2e SET suppression_destinataire=1 WHERE id_expediteur=? AND id_destinataire=?')
    ->execute([$id_interlocuteur, $_SESSION['id_utilisateur']]);
$pdo->prepare('DELETE FROM messages_prives_e2e WHERE suppression_expediteur=1 AND suppression_destinataire=1')->execute();

header('Location: messages_prives.php');
exit;
<?php
session_start();
date_default_timezone_set('Europe/Paris');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
header('Content-Type: application/json');
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['messagerie_deverrouillee'])){
    http_response_code(401);
    echo json_encode(['succes'=>false, 'erreur'=>'Non authentifié']);
    exit;
}
if ($_SERVER['REQUEST_METHOD']!=='POST'){
    http_response_code(405);
    echo json_encode(['succes'=>false, 'erreur'=>'Méthode non autorisée']);
    exit;
}
csrf_verifier();
$id_utilisateur=(int)$_SESSION['id_utilisateur'];
$id_interlocuteur=$_POST['id_interlocuteur']??'';
$jusqu_id=$_POST['jusqu_id']??'';
if (!ctype_digit((string)$id_interlocuteur) || !ctype_digit((string)$jusqu_id)){
    echo json_encode(['succes'=>false, 'erreur'=>'Paramètres invalides']);
    exit;
}
$pdo=getPDO();
$stmt=$pdo->prepare('UPDATE messages_prives_e2e SET lu=1, lu_a=?, expire_a=? WHERE id_expediteur=? AND id_destinataire=? AND id_message_e2e<=? AND lu=0');
$stmt->execute([date('Y-m-d H:i:s'), date('Y-m-d H:i:s', strtotime('+24 hours')), (int)$id_interlocuteur, $id_utilisateur, (int)$jusqu_id]);
echo json_encode(['succes'=>true, 'marques'=>$stmt->rowCount()]);
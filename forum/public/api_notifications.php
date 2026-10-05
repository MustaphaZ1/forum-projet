<?php
session_start();
date_default_timezone_set('Europe/Paris');
require_once __DIR__ . '/../config/database.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');
if (!isset($_SESSION['id_utilisateur'])){
    http_response_code(401);
    echo json_encode(['succes'=>false, 'erreur'=>'Non authentifié']);
    exit;
}
$id_utilisateur=(int)$_SESSION['id_utilisateur'];
session_write_close();
$pdo=getPDO();
$maintenant=date('Y-m-d H:i:s');
$limite_non_lus=date('Y-m-d H:i:s', strtotime('-30 days'));
// 1. Purge des messages expirés (24h après lecture) ou jamais lus depuis 30 jours
if (mt_rand(1, 10)===1){
    foreach (['messages_prives', 'messages_prives_e2e'] as $table){
        $stmt=$pdo->prepare("DELETE FROM $table WHERE (expire_a IS NOT NULL AND expire_a <= ?) OR (lu=0 AND date_envoi <= ?)");
        $stmt->execute([$maintenant, $limite_non_lus]);
    }
}
// 2. Comptage des messages non lus (standard + E2E)
$stmt=$pdo->prepare('SELECT (SELECT COUNT(*) FROM messages_prives WHERE id_destinataire=? AND lu=0 AND suppression_destinataire=0 AND (expire_a IS NULL OR expire_a > ?)) AS standard, (SELECT COUNT(*) FROM messages_prives_e2e WHERE id_destinataire=? AND lu=0 AND suppression_destinataire=0 AND (expire_a IS NULL OR expire_a > ?)) AS e2e');
$stmt->execute([$id_utilisateur, $maintenant, $id_utilisateur, $maintenant]);
$resultat=$stmt->fetch();
$non_lus_standard=(int)$resultat['standard'];
$non_lus_e2e=(int)$resultat['e2e'];
echo json_encode([
    'succes'=>true,
    'messages_non_lus'=>$non_lus_standard + $non_lus_e2e,
    'standard'=>$non_lus_standard,
    'e2e'=>$non_lus_e2e,
]);
<?php
require_once __DIR__ . '/../config/cle_secrete.php';
function chiffrerMessage(string $message): string {
    $cleChiffrement=hash('sha256', CLE_SECRETE_MESSAGES . 'chiffrement', true);
    $cleAuthentification=hash('sha256', CLE_SECRETE_MESSAGES . 'authentification', true);
    $iv=random_bytes(16);
    $messageChiffre=openssl_encrypt($message, 'AES-256-CBC', $cleChiffrement, OPENSSL_RAW_DATA, $iv);
    if ($messageChiffre===false){
        throw new RuntimeException('Erreur lors du chiffrement du message');
    }
    $signature=hash_hmac('sha256', $iv . $messageChiffre, $cleAuthentification, true);
    $contenu=$iv . $signature . $messageChiffre;
    return base64_encode($contenu);
}
function dechiffrerMessage(string $messageChiffre): string {
    $contenu=base64_decode($messageChiffre, true);
    if ($contenu===false){
        throw new RuntimeException('Message chiffré invalide');
    }
    if (strlen($contenu)<49){
        throw new RuntimeException('Message chiffré invalide');
    }
    $iv=substr($contenu, 0, 16);
    $signature=substr($contenu, 16, 32);
    $contenuChiffre=substr($contenu, 48);
    $cleChiffrement=hash('sha256', CLE_SECRETE_MESSAGES . 'chiffrement', true);
    $cleAuthentification=hash('sha256', CLE_SECRETE_MESSAGES . 'authentification', true);
    $signatureAttendue=hash_hmac('sha256', $iv . $contenuChiffre, $cleAuthentification, true);
    if(!hash_equals($signatureAttendue, $signature)){
        throw new RuntimeException('Le message chiffré a été modifié ou est invalide');
    }
    $message=openssl_decrypt($contenuChiffre, 'AES-256-CBC', $cleChiffrement, OPENSSL_RAW_DATA, $iv);
    if ($message===false){
        throw new RuntimeException('Impossible de déchiffrer le message');
    }
    return $message;
}
?>
<?php
require_once __DIR__ . '/../config/cle_secrete.php';
function chiffrerMessage(string $message): string {
    $iv=random_bytes(12);
    $tag='';
    $messageChiffre=openssl_encrypt($message,'AES-256-CGM', CLE_SECRETE_MESSAGES, OPENSSL_RAW_DATA, $iv, $tag);
    if($messageChiffre===false){
        throw new RuntimeException('Erreur lors du chiffrement du message.');
    }
    return base64_encode($iv . $tag . $messageChiffre);
}
function dechiffrerMessage(string $messageChiffre): string {
    $contenu=base64_decode($messageChiffre, true);
    if($contenu===false){
        throw new RuntimeException('Message chiffré invalide.');
    }
    $iv=substr($contenu, 0, 12);
    $tag=substr($contenu, 12, 16);
    $contenuChiffre=substr($contenu, 28);
    if(strlen($iv)!==12 || strlen($tag)!==16 || $contenuChiffre===''){
        throw new RuntimeException('Message chiffré invalide.');
    }
    $message=openssl_decrypt($contenuChiffre, 'AES-256-CGM', CLE_SECRETE_MESSAGES, OPENSSL_RAW_DATA, $iv, $tag);
    if($message===false){
        throw new RuntimeException('Impossible de déchiffrer le message.');
    }
    return $message;
}
<?php
// CHARGEMENT DE LA CLE SECRETE
require_once __DIR__ . '/../config/cle_secrete.php'; // CHARGE LA CLE DEPUIS LE FICHIER DE CONFIGURATION
// FONCTION DE CHIFFREMENT DES MESSAGES
function chiffrerMessage(string $message): string {
    // GENERE UNE CLE DE CHIFFREMENT DERIVEE A PARTIR DE LA CLE SECRETE GLOBALE
    $cleChiffrement=hash('sha256', CLE_SECRETE_MESSAGES . 'chiffrement', true);
    // GENERE UNE CLE D'AUTHENTIFICATION (HMAC) DISTINCTES DE CELLE DE CHIFFREMENT
    $cleAuthentification=hash('sha256', CLE_SECRETE_MESSAGES . 'authentification', true);
    // GENERE UN VECTEUR D'INITIALISATION (IV) ALEATOIRE DE 16 OCTETS POUR RENFORCER LA SECURITE
    $iv=random_bytes(16);
    // CHIFFRE LE MESSAGE AVEC L'ALGORITHME AES-256-CBC
    $messageChiffre=openssl_encrypt($message, 'AES-256-CBC', $cleChiffrement, OPENSSL_RAW_DATA, $iv);
    if ($messageChiffre===false){
        throw new RuntimeException('Erreur lors du chiffrement du message'); // ARRETE LE SCRIPT SI LE CHIFFREMENT ECHOUE
    }
    // CREE UNE SIGNATURE HMAC POUR GARANTIR L'INTEGRITE DU MESSAGE (NO ALERT)
    $signature=hash_hmac('sha256', $iv . $messageChiffre, $cleAuthentification, true);
    // ASSEMBLE LA SIGNATURE, L'IV ET LE MSG CHIFFRE EN UN SEUL BLOC
    $contenu=$iv . $signature . $messageChiffre;
    // ENCODE LE TOUT EN BASE64 POUR POUVOIR LE STOCKER FACILEMENT EN BASE DE DONNEES
    return base64_encode($contenu);
}
// FONCTION DE DECHIFFREMENT DES MESSAGES
function dechiffrerMessage(string $messageChiffre): string {
    // DECODE LA CHAINE BASE64 POUR RETROUVER LES DONNEES BINAIRES BRUTES
    $contenu=base64_decode($messageChiffre, true);
    if ($contenu===false){
        throw new RuntimeException('Message chiffré invalide');
    }
    // VERIFIE QUE LE CONTENU A UNE TAILLE MINIMALE COHERENTE (SIGNATURE+IV+DONNEES)
    if (strlen($contenu)<49){
        throw new RuntimeException('Message chiffré invalide');
    }
    // EXTRAIT LA SIGNATURE (LES 32 PREMIERS OCTETS)
    $iv=substr($contenu, 0, 16); // EXTRAIT L'IV (16 OCTETS SUIVANTS)
    $signature=substr($contenu, 16, 32);
    // EXTRAIT LE TEXTE CHIFFRE
    $contenuChiffre=substr($contenu, 48);
    // RECREE LES CLES DE CHIFFREMENT ET D'AUTHENTIFICATION A PARTIR DE LA CONFIG
    $cleChiffrement=hash('sha256', CLE_SECRETE_MESSAGES . 'chiffrement', true);
    $cleAuthentification=hash('sha256', CLE_SECRETE_MESSAGES . 'authentification', true);
    // CALCULE LA SIGNATURE ATTENDUE POUR VERIFIER QUE LE MSG N'A PAS ETE MODIFIER EN BASE
    $signatureAttendue=hash_hmac('sha256', $iv . $contenuChiffre, $cleAuthentification, true);
    if(!hash_equals($signatureAttendue, $signature)){
        throw new RuntimeException('Le message chiffré a été modifié ou est invalide');
    }
    // DECHIFFRE LE MSG AVEC LA CLE AES ET L'IV EXTRAIT
    $message=openssl_decrypt($contenuChiffre, 'AES-256-CBC', $cleChiffrement, OPENSSL_RAW_DATA, $iv);
    if ($message===false){
        throw new RuntimeException('Impossible de déchiffrer le message');
    }
    // RETOURNE LE MSG INITIAL EN CLAIR
    return $message;
}
?>
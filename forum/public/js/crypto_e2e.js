// GENERATION ET STOCKAGE DES CLES DE CHIFFREMENT DE BOUT EN BOUT

// Dérive une clé AES à partir du mot de passe de l'utilisateur, via PBKDF2.
async function deriverCleDepuisMotDePasse(motDePasse, selBase64) {
    const sel = selBase64 ? base64ToArrayBuffer(selBase64) : crypto.getRandomValues(new Uint8Array(16));
    const materiauCle = await crypto.subtle.importKey(
        'raw',
        new TextEncoder().encode(motDePasse),
        'PBKDF2',
        false,
        ['deriveKey']
    );
    const cleDerivee = await crypto.subtle.deriveKey(
        { name: 'PBKDF2', salt: sel, iterations: 250000, hash: 'SHA-256' },
        materiauCle,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt']
    );
    return { cleDerivee, sel };
}

// Génère la paire de clés ECDH de l'utilisateur (publique + privée)
async function genererPaireCles() {
    return await crypto.subtle.generateKey(
        { name: 'ECDH', namedCurve: 'P-256' },
        true,
        ['deriveKey']
    );
}

// Chiffre la clé privée (au format JWK) avec la clé dérivée du mot de passe
async function chiffrerClePrivee(clePrivee, cleDerivee) {
    const clePriveeJwk = await crypto.subtle.exportKey('jwk', clePrivee);
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const donnees = new TextEncoder().encode(JSON.stringify(clePriveeJwk));
    const chiffre = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, cleDerivee, donnees);
    return { chiffre: arrayBufferToBase64(chiffre), iv: arrayBufferToBase64(iv) };
}

// Exporte la clé publique au format JWK, prête à être envoyée au serveur
async function exporterClePublique(clePublique) {
    const jwk = await crypto.subtle.exportKey('jwk', clePublique);
    return JSON.stringify(jwk);
}

// Fonctions utilitaires de conversion
function arrayBufferToBase64(buffer) {
    return btoa(String.fromCharCode(...new Uint8Array(buffer)));
}
function base64ToArrayBuffer(base64) {
    return Uint8Array.from(atob(base64), c => c.charCodeAt(0));
}

// FONCTION PRINCIPALE : génère tout et prépare les données à envoyer au serveur
async function activerChiffrementE2E(motDePasse) {
    const { cleDerivee, sel } = await deriverCleDepuisMotDePasse(motDePasse, null);
    const paireCles = await genererPaireCles();
    const clePubliqueExportee = await exporterClePublique(paireCles.publicKey);
    const { chiffre, iv } = await chiffrerClePrivee(paireCles.privateKey, cleDerivee);

    return {
        cle_publique: clePubliqueExportee,
        cle_privee_chiffree: chiffre,
        iv_cle_privee: iv,
        sel_derivation: arrayBufferToBase64(sel)
    };
}
// STOCKAGE DE LA CLE PRIVEE DECHIFFREE (IndexedDB, clé non-extractable)

function ouvrirBaseCles() {
    return new Promise((resolve, reject) => {
        const requete = indexedDB.open('forum_e2e', 1);
        requete.onupgradeneeded = () => {
            requete.result.createObjectStore('cles');
        };
        requete.onsuccess = () => resolve(requete.result);
        requete.onerror = () => reject(requete.error);
    });
}

async function stockerClePriveeDechiffree(clePriveeCryptoKey) {
    const db = await ouvrirBaseCles();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('cles', 'readwrite');
        tx.objectStore('cles').put(clePriveeCryptoKey, 'clePrivee');
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}

async function recupererClePriveeDechiffree() {
    const db = await ouvrirBaseCles();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('cles', 'readonly');
        const requete = tx.objectStore('cles').get('clePrivee');
        requete.onsuccess = () => resolve(requete.result || null);
        requete.onerror = () => reject(requete.error);
    });
}

async function effacerClePriveeDechiffree() {
    const db = await ouvrirBaseCles();
    return new Promise((resolve, reject) => {
        const tx = db.transaction('cles', 'readwrite');
        tx.objectStore('cles').delete('clePrivee');
        tx.oncomplete = () => resolve();
        tx.onerror = () => reject(tx.error);
    });
}
// DEVERROUILLAGE : déchiffre la clé privée avec le mot de passe, au moment de la connexion
async function deverrouillerClePrivee(motDePasse, cleChiffreeBase64, ivBase64, selBase64) {
    motDePasse = motDePasse.normalize('NFKC'); // uniformise les caractères accentués
    const { cleDerivee } = await deriverCleDepuisMotDePasse(motDePasse, selBase64);

    const iv = base64ToArrayBuffer(ivBase64);
    const chiffre = base64ToArrayBuffer(cleChiffreeBase64);

    const dechiffre = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, cleDerivee, chiffre);
    const clePriveeJwk = JSON.parse(new TextDecoder().decode(dechiffre));

    // Réimporte la clé privée en mode non-extractable : une fois importée ainsi,
    // sa valeur brute ne peut plus jamais être relue, même via du JS malveillant.
    const clePriveeCryptoKey = await crypto.subtle.importKey(
        'jwk',
        clePriveeJwk,
        { name: 'ECDH', namedCurve: 'P-256' },
        false,
        ['deriveKey']
    );

    await stockerClePriveeDechiffree(clePriveeCryptoKey);
}
// DERIVATION DE LA CLE PARTAGEE (ECDH) entre ma clé privée et la clé publique du destinataire
async function deriverClePartagee(clePubliqueDestinataireJwkString) {
    const clePriveeCryptoKey = await recupererClePriveeDechiffree();
    if (!clePriveeCryptoKey) {
        throw new Error('Clé privée non disponible — reconnecte-toi.');
    }

    const clePubliqueJwk = JSON.parse(clePubliqueDestinataireJwkString);
    const clePubliqueDestinataire = await crypto.subtle.importKey(
        'jwk',
        clePubliqueJwk,
        { name: 'ECDH', namedCurve: 'P-256' },
        false,
        []
    );

    return await crypto.subtle.deriveKey(
        { name: 'ECDH', public: clePubliqueDestinataire },
        clePriveeCryptoKey,
        { name: 'AES-GCM', length: 256 },
        false,
        ['encrypt', 'decrypt']
    );
}

// CHIFFRE un message avec la clé partagée
async function chiffrerMessageE2E(texte, clePubliqueDestinataireJwkString) {
    const clePartagee = await deriverClePartagee(clePubliqueDestinataireJwkString);
    const iv = crypto.getRandomValues(new Uint8Array(12));
    const donnees = new TextEncoder().encode(texte);
    const chiffre = await crypto.subtle.encrypt({ name: 'AES-GCM', iv }, clePartagee, donnees);
    return {
        contenu_chiffre: arrayBufferToBase64(chiffre),
        iv: arrayBufferToBase64(iv)
    };
}

// DECHIFFRE un message avec la clé partagée
async function dechiffrerMessageE2E(contenuChiffreBase64, ivBase64, clePubliqueAutrePartieJwkString) {
    const clePartagee = await deriverClePartagee(clePubliqueAutrePartieJwkString);
    const iv = base64ToArrayBuffer(ivBase64);
    const chiffre = base64ToArrayBuffer(contenuChiffreBase64);
    const dechiffre = await crypto.subtle.decrypt({ name: 'AES-GCM', iv }, clePartagee, chiffre);
    return new TextDecoder().decode(dechiffre);
}
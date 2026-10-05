<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/csrf.php';
if (!isset($_SESSION['id_utilisateur']) || !isset($_SESSION['messagerie_deverrouillee'])) {
    header('Location: acces_messagerie.php');
    exit;
}
$pdo = getPDO();
$id_utilisateur = $_SESSION['id_utilisateur'];
$id_interlocuteur = $_GET['id_utilisateur'] ?? null;
if (!$id_interlocuteur || !ctype_digit((string)$id_interlocuteur)) {
    die('Utilisateur introuvable');
}
$id_interlocuteur = (int)$id_interlocuteur;
$stmt = $pdo->prepare('SELECT id_utilisateur, pseudo, cle_publique_e2e FROM utilisateurs WHERE id_utilisateur=?');
$stmt->execute([$id_interlocuteur]);
$interlocuteur = $stmt->fetch();
if (!$interlocuteur) {
    die('Utilisateur introuvable');
}
if (!$interlocuteur['cle_publique_e2e']) {
    die('Cet utilisateur n\'a pas activé le chiffrement de bout en bout.');
}
require_once __DIR__ . '/../includes/header.php';
?>
<body>
    <div class="mb-2">
        <a href="messages_prives.php" class="text-decoration-none">&larr; Retour</a>
    </div>
    <h1 class="mb-4"><?= htmlspecialchars($interlocuteur['pseudo']) ?> <span class="badge bg-success">Chiffré E2E</span></h1>
    <form action="supprimer_conversation_e2e.php" method="post" onsubmit="return confirm('Supprimer toute la conversation ?');" class="mb-3">
        <?= csrf_champ() ?>
        <input type="hidden" name="id_interlocuteur" value="<?= (int)$id_interlocuteur ?>">
        <button type="submit" class="btn btn-outline-danger btn-sm">Tout supprimer &#x1F5D1;</button>
    </form>
    <div class="mb-4" id="zone-messages-e2e">
        <div class="alert alert-secondary">Chargement des messages...</div>
    </div>

    <form id="form-envoi-e2e" method="post" onsubmit="return false;">
        <?= csrf_champ() ?>
        <div class="input-group">
            <textarea id="contenu-message-e2e" rows="2" class="form-control" placeholder="Écrire un message chiffré..." required></textarea>
            <button type="submit" class="btn btn-primary">Envoyer</button>
        </div>
    </form>
</body>
<script src="js/crypto_e2e.js"></script>
<script>
const idInterlocuteur = <?= (int)$id_interlocuteur ?>;
const csrfToken = document.querySelector('#form-envoi-e2e [name=csrf_token]').value;
const zoneMessages = document.getElementById('zone-messages-e2e');
let clePubliqueInterlocuteur = null;
let dernierIdAffiche = 0;
let dernierRecuAMarquer = 0;
async function marquerCommeLuSiVisible() {
    if (document.hidden || dernierRecuAMarquer === 0) return;
    const jusquId = dernierRecuAMarquer;
    dernierRecuAMarquer = 0;
    try {
        const donnees = new FormData();
        donnees.append('id_interlocuteur', idInterlocuteur);
        donnees.append('jusqu_id', jusquId);
        donnees.append('csrf_token', csrfToken);
        await fetch('marquer_lu_e2e.php', { method: 'POST', body: donnees });
        if (typeof majBadgeMessages === 'function') majBadgeMessages();
    } catch (err) {
        dernierRecuAMarquer = Math.max(dernierRecuAMarquer, jusquId);
        console.error('Erreur marquage lu :', err);
    }
}
document.addEventListener('visibilitychange', marquerCommeLuSiVisible);
function escapeHtml(texte) {
    const div = document.createElement('div');
    div.textContent = texte;
    return div.innerHTML;
}
function nl2br(texte) {
    return escapeHtml(texte).replace(/\n/g, '<br>');
}
function formaterDate(dateSql) {
    const date = new Date(dateSql.replace(' ', 'T'));
    const j = String(date.getDate()).padStart(2, '0');
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const a = date.getFullYear();
    const h = String(date.getHours()).padStart(2, '0');
    const min = String(date.getMinutes()).padStart(2, '0');
    return `${j}/${m}/${a} ${h}:${min}`;
}
async function recupererClePubliqueInterlocuteur() {
    const reponse = await fetch('cle_publique.php?id_utilisateur=' + idInterlocuteur);
    const data = await reponse.json();
    if (!data.cle_publique) {
        throw new Error('Clé publique introuvable');
    }
    return data.cle_publique;
}
async function verifierClePriveeDisponible() {
    const cle = await recupererClePriveeDechiffree();
    if (!cle) {
        zoneMessages.innerHTML = '<div class="alert alert-warning"><strong>Clé de déchiffrement indisponible sur cet appareil.</strong><br>Si c\'est la première fois que tu utilises la messagerie chiffrée sur ce navigateur ou cet appareil, déconnecte-toi puis reconnecte-toi — la clé se déverrouille automatiquement à la connexion.</div>';
        return false;
    }
    return true;
}
async function chargerMessages() {
    try {
        if (!(await verifierClePriveeDisponible())) {
            return;
        }
        if (!clePubliqueInterlocuteur) {
            clePubliqueInterlocuteur = await recupererClePubliqueInterlocuteur();
        }
        const reponse = await fetch('recuperer_messages_e2e.php?id_interlocuteur=' + idInterlocuteur + '&depuis_id=' + dernierIdAffiche);
        const messages = await reponse.json();
        // Conversation vide : on l'affiche une seule fois, au tout premier chargement
        if (messages.length === 0) {
            if (dernierIdAffiche === 0) {
                zoneMessages.innerHTML = '<div class="alert alert-secondary">Aucun message dans cette conversation</div>';
            }
            return;
        }
        const nouveaux = messages.filter(m => m.id_message_e2e > dernierIdAffiche);
        if (nouveaux.length === 0) {
            return; // rien de neuf, on ne touche à rien
        }
        // Si c'est le tout premier chargement, on vide le message "Chargement..." initial
        if (dernierIdAffiche === 0) {
            zoneMessages.innerHTML = '';
        }
        for (const m of nouveaux) {
            let contenu;
            let dechiffre = true;
            try {
                contenu = await dechiffrerMessageE2E(m.contenu_chiffre, m.iv, clePubliqueInterlocuteur);
            } catch (err) {
                contenu = '[Impossible de déchiffrer ce message]';
                dechiffre = false;
                console.error('Erreur déchiffrement message', m.id_message_e2e, err);
            }
            // ... (le reste de ta boucle ne change pas)
            dernierIdAffiche = m.id_message_e2e;
            if (!m.est_moi && dechiffre) {
                dernierRecuAMarquer = Number(m.id_message_e2e);
            }
        }
    zoneMessages.scrollTop = zoneMessages.scrollHeight;
    await marquerCommeLuSiVisible();
    } catch (err) {
        if (dernierIdAffiche === 0) {
            zoneMessages.innerHTML = '<div class="alert alert-danger">Erreur de chargement des messages.</div>';
        }
        console.error(err);
    }
}
document.getElementById('form-envoi-e2e').addEventListener('submit', async (e) => {
    e.preventDefault();
    const champ = document.getElementById('contenu-message-e2e');
    const texte = champ.value.trim();
    if (texte === '') return;
    if (!(await verifierClePriveeDisponible())) {
        return;
    }
    try {
        if (!clePubliqueInterlocuteur) {
            clePubliqueInterlocuteur = await recupererClePubliqueInterlocuteur();
        }
        const { contenu_chiffre, iv } = await chiffrerMessageE2E(texte, clePubliqueInterlocuteur);
        const donnees = new FormData();
        donnees.append('id_destinataire', idInterlocuteur);
        donnees.append('contenu_chiffre', contenu_chiffre);
        donnees.append('iv', iv);
        donnees.append('csrf_token', csrfToken);
        const reponse = await fetch('envoyer_message_e2e.php', { method: 'POST', body: donnees });
        const resultat = await reponse.json();
        if (resultat.succes) {
            champ.value = '';
            await chargerMessages();
        } else {
            alert(resultat.erreur || 'Erreur lors de l\'envoi');
        }
    } catch (err) {
        alert('Erreur de chiffrement : ' + err.message);
        console.error(err);
    }
});
zoneMessages.addEventListener('submit', (e) => {
    const form = e.target;
    if (form.matches('form[action="supprimer_message_e2e.php"]')) {
        e.preventDefault();
        if (!confirm('Supprimer ce message (uniquement pour vous) ?')) return;
        const donnees = new FormData(form);
        donnees.append('ajax', '1');
        fetch('supprimer_message_e2e.php', { method: 'POST', body: donnees })
            .then(r => r.json())
            .then(resultat => {
                if (resultat.succes) {
                    form.closest('.d-flex').remove();
                } else {
                    alert(resultat.erreur || 'Erreur lors de la suppression');
                }
            })
            .catch(err => console.error('Erreur suppression :', err));
    }
});
chargerMessages();
setInterval(chargerMessages, 3000);
</script>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
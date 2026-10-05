// INITIALISATION DES VARIABLES GLOBALES
// RECUPERE L'ID DE L'INTERLOCUTEUR DANS L'URL
const idInterlocuteur= new URLSearchParams(window.location.search).get('id_utilisateur');
// SELECTIONNE LE CONTENEUR HTML OU LES MESSAGES VONT S'AFFICHER
const zoneMessages= document.getElementById('zone-messages');
// INITIALISE LA VARIABLE POUR STOCKER L'ID UTILISATEUR CONNECTE
let idUtilisateurConnecte=null;
// FONCTION D'AFFICHAGE DES MESSAGES
function afficherMessages(messages){
    // VIDE LA ZONE DES MESSAGES AVANT DE LES REAFFICHER
    zoneMessages.innerHTML='';
    // SI AUCUN MESSAGE AFFICHER UN MESSAGE D'INFORMATION
    if (messages.length===0){
        zoneMessages.innerHTML='<div class="alert alert-secondary">Aucun message dans cette conversation</div>';
        return; // ARRETE L'EXECUTION DE LA FONCTION
    }
    // PARCOURT LES MESSAGES POUR LES STRUCTURER ET LES INSERER
    messages.forEach(message => {
        // ALIGNE LE MESSAGE A DROITE SI L'UTILISATEUR EST L'EXPEDITEUR
        const alignement= message.est_moi ? 'justify-content-end' : 'justify-content-start';
        // APPLIQUE UNE COULEUR DE CARTE DIFFERENTE SELON L'EXPEDITEUR
        const couleurCard= message.est_moi ? 'message-envoye' : 'message-recu';
        const classeMessage= message.est_moi ? 'message-envoye' : 'message-recu';
        // CREE UN ELEMENT DIV POUR ENVELOPPER 1 MESSAGE
        const div= document.createElement('div');
        div.className= `d-flex mb-3 ${alignement}`;
        // INJECTE LA STRUCTURE HTML DU MESSAGE
        div.innerHTML= `<div class="card ${classeMessage}" style="max-width: 70%;"><div class="card-body"><p class="card-text mb-2">${nl2br(message.contenu)}</p><small>${formaterDate(message.date_envoi)}</small><form method="post" action="supprimer_message_prive.php" class="mt-1"><input type="hidden" name="id_message_prive" value="${message.id_message_prive}"><input type="hidden" name="id_interlocuteur" value="${idInterlocuteur}"><button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none p-0">Supprimer&nbsp;<span class="emoji-fix">\u{1F5D1}</span></button></form></div></div>`;
        // AJOUTE LA DIV DU MESSAGE DANS LE CONTENEUR PRINCIPAL DES MESSAGES
        zoneMessages.appendChild(div);
    });
}
// FONCTION DE SECURITE
function escapeHtml(texte){
    const div= document.createElement('div');
    div.textContent=texte; // TRANSFORME LES CARACTERES SPECIAUX EN ENTITES HTML SURES (CONTRE FAILLE XSS)
    return div.innerHTML;
}
// FONCTION POUR LES SAUTS DE LIGNE
function nl2br(texte){
    return escapeHtml(texte).replace(/\n/g, '<br>');
}
// FONCTION DE FORMATAGE DES DATES
function formaterDate(dateSql){
    // CONVERTIT LA DATE SQL (FORMAT STANDARD) EN OBJET DATE JS
    const date= new Date(dateSql.replace(' ', 'T'));
    const jour= String(date.getDate()).padStart(2, '0'); // FORMATE LE JOUR SUR 2 CHIFFRES
    const mois= String(date.getMonth() + 1).padStart(2, '0'); // FORMATE LE MOIS SUR 2 CHIFFRES
    const annee= date.getFullYear(); // RECUPERE L'ANNEE SUR 4 CHIFFRES
    const heures= String(date.getHours()).padStart(2, '0'); // FORMATE LES HEURES SUR 2 CHIFFRES
    const minutes= String(date.getMinutes()).padStart(2, '0'); // FORMATE LES MINUTES SUR 2 CHIFFRES
    // RETOURNE LA DATE AU FORMAT JJ/MM/AAAA HH:MM UTILISATION DE ``
    return `${jour} / ${mois} / ${annee} ${heures}:${minutes}`;
}
// ENVOI D'UN NOUVEAU MSG EN AJAX
const formEnvoi= document.getElementById('form-envoi-message'); // SELECTIONNE LE FORMULAIRE D'ENVOI
if (formEnvoi){
    formEnvoi.addEventListener('submit', (e)=>{
        e.preventDefault(); // EMPECHE LE RECHARGEMENT CLASSIQUE DE LA PAGE LORS DE L'ENVOI
        const champTexte= document.getElementById('contenu-message');
        const contenu= champTexte.value.trim(); // RECUPERE LE TEXTE ET SUPPRIME LES ESPACES SUPERFLUS
        if (contenu==='') return; // SI LE MSG EST VIDE ON ARRETE L'EXECUTION
        const donnees= new FormData(); // CREE UN OBJET FORMDATA POUR ENVOYER LES DONNEES EN POST
        donnees.append('contenu', contenu);
        donnees.append('ajax', '1'); // INDIQUE AU SERVEUR QU'IL S'AGIT D'UNE REQUETE AJAX
        donnees.append('csrf_token', CSRF_TOKEN);
        // ENVOI LES DONNEES AU SCRIPT PHP DE LA CONVERSATION
        fetch('conversation.php?id_utilisateur=' + idInterlocuteur, {
            method: 'POST', body: donnees
        })
        .then(response=>response.json()) // CONVERTIT LA REPONSE DU SERVEUR EN JSON
        .then(resultat=>{
            console.log('Resultat recu :', resultat);
            if (resultat.succes){
                champTexte.value= ''; // VIDE LE CHAMP DE SAISIE SI L'ENVOI A REUSSI
                chargerMessages(); // RECHARGE LA LISTE DES MESSAGES POUR AFFICHER LE NOUVEAU
            } else {
                alert(resultat.erreur || 'Erreur lors de l\'envoi du message'); // AFFICHE UNE ERREUR EN CAS DE PROBLEME
            }
        })
        .catch(erreur=>console.error('Erreur envoi message :',erreur)); // GERE LES ERREURS RESEAU
    });
}
// SUPPRESSION D'UN MSG EN AJAX
zoneMessages.addEventListener('submit', (e)=>{
    const form= e.target;
    // VERIFIE SI LE FORMULAIRE SOUMIS CORRESPOND A CELUI DE LA SUPPRESSION D'UN MSG PRIVE
    if (form.matches('form[action="supprimer_message_prive.php"]')){
        e.preventDefault(); // EMPECHE LE RECHARGEMENT DE LA PAGE
        // DEMANDE UNE CONFIRMATION A L'UTILISATEUR AVANT DE SUPPRIMER
        if (!confirm('Supprimer ce message (uniquement pour vous) ?')) return;
        const donnees= new FormData(form); // RECUPERE LES DONNEES DU FORMULAIRE DE SUPPRESSION
        donnees.append('ajax', '1'); // INDIQUE QUE C'EST UNE REQUETE AJAX
        // ENVOI LA REQUETE DE SUPPRESSION AU SCRIPT PHP DEDIE
        fetch('supprimer_message_prive.php', {
            method: 'POST', body: donnees
        })
        .then(response=>response.json())
        .then(resultat=>{
            if (resultat.succes){
                chargerMessages(); // RECHARGE LA LISTE DES MSG SI LA SUPPRESSION A REUSSI
            } else {
                alert(resultat.erreur || 'Erreur lors de la suppression');
            }
        })
        .catch(erreur=>console.error('Erreur suppression :', erreur));
    }
});
// FONCTION DE CHARGEMENT DES MSG VIA AJAX
function chargerMessages(){
    const visible= document.hidden ? 0 : 1;
    fetch(`recuperer_message.php?id_interlocuteur=${idInterlocuteur}&visible=${visible}`)
    .then(response => {
        const marques= parseInt(response.headers.get('X-Messages-Marques') || '0', 10);
        if (marques > 0 && typeof majBadgeMessages === 'function') majBadgeMessages();
        return response.json();
    })
    .then(messages => afficherMessages(messages))
    .catch(erreur => console.error('Erreur de chargement des message :', erreur));
}
// APPELS INITIAUX ET AUTOMATIQUES
chargerMessages(); // CHARGE LES MSG IMMEDIATEMENT AU CHARGEMENT DE LA PAGE
setInterval(chargerMessages, 2800); // ACTUALISE AUTOMATIQUEMENT LES MESSAGES TOUTES LES 2,8S POUR SIMULER LE TEMPS REEL
document.addEventListener('visibilitychange', ()=>{ if (!document.hidden) chargerMessages(); });
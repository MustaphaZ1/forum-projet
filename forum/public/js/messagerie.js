const idInterlocuteur= new URLSearchParams(window.location.search).get('id_utilisateur');
const zoneMessages= document.getElementById('zone-messages');
let idUtilisateurConnecte=null;
function afficherMessages(messages){
    zoneMessages.innerHTML='';
    if (messages.length===0){
        zoneMessages.innerHTML='<div class="alert alert-secondary">Aucun message dans cette conversation</div>';
        return;
    }
    messages.forEach(message => {
        const alignement= message.est_moi ? 'justify-content-end' : 'justify-content-start';
        const couleurCard= message.est_moi ? 'message-envoye' : 'message-recu';
        const classeMessage= message.est_moi ? 'message-envoye' : 'message-recu';
        const div= document.createElement('div');
        div.className= `d-flex mb-3 ${alignement}`;
        div.innerHTML= `<div class="card ${classeMessage}" style="max-width: 70%;"><div class="card-body"><p class="card-text mb-2">${nl2br(message.contenu)}</p><small>${formaterDate(message.date_envoi)}</small><form method="post" action="supprimer_message_prive.php" class="mt-1"><input type="hidden" name="id_message_prive" value="${message.id_message_prive}"><input type="hidden" name="id_interlocuteur" value="${idInterlocuteur}"><button type="submit" class="btn btn-link btn-sm text-danger text-decoration-none p-0">Supprimer&nbsp;<span class="emoji-fix">\u{1F5D1}</span></button></form></div></div>`;
        zoneMessages.appendChild(div);
    });
}
function escapeHtml(texte){
    const div= document.createElement('div');
    div.textContent=texte;
    return div.innerHTML;
}
function nl2br(texte){
    return escapeHtml(texte).replace(/\n/g, '<br>');
}
function formaterDate(dateSql){
    const date= new Date(dateSql.replace(' ', 'T'));
    const jour= String(date.getDate()).padStart(2, '0');
    const mois= String(date.getMonth() + 1).padStart(2, '0');
    const annee= date.getFullYear();
    const heures= String(date.getMinutes()).padStart(2, '0');
    const minutes= String(date.getMinutes()).padStart(2, '0');
    return `${jour} / ${mois} / ${annee} ${heures}:${minutes}`;
}
function chargerMessages(){
    fetch(`recuperer_message.php?id_interlocuteur=${idInterlocuteur}`)
    .then(response => response.json())
    .then(messages => afficherMessages(messages))
    .catch(erreur => console.error('Erreur de chargement des message :', erreur));
}
chargerMessages();
setInterval(chargerMessages, 5000);
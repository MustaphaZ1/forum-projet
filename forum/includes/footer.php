</div>
<script>
    // SELECTIONNE LE BOUTON DE BASCULE DU THEME DANS LE DOM
    const button =
    document.getElementById('theme-toggle');
    console.log('Button trouvé:', button);
    console.log('localStorage theme:', localStorage.getItem('theme'));
    // VERIFIE SI LE THEME ENREGISTRE DANS LE STOCKAGE LOCALE EST 'DARK' AU DEMARRAGE
    if(localStorage.getItem('theme')==='dark'){
        document.body.classList.add('dark-mode'); // APPLIQUE LA CLE CSS DU MODE SOMBRE AU CORPS DE LA PAGE
        button.textContent='☀️'; // CHANGE L'ICONE DU BOUTON EN SOLEIL
        console.log('Mode dark appliqué au démarrage');
    } else {
        document.body.classList.remove('dark-mode'); // REVIENT AU MODE CLAIR
        button.textContent='🌙'; // CHANGE L'ICONE DU BOUTON EN LUNE
        console.log('Mode light appliqué au démarrage');
    }
    // AJOUTE UN ECOUTEUR D'EVENEMENT AU CLIC SUR LE BOUTON DE CHANGEMENT DE THEME
    button.addEventListener('click', () => {
        console.log('Bouton cliqué')
        document.body.classList.toggle('dark-mode'); // BASCULE L'ETAT DU MODE SOMBRE SUR LE CORPS
        // VERIFIE SI LE MODE SOMBRE EST ACTIF APRE LE CLIC ET LE MET A JOUR LOCALSTORAGE
        if(document.body.classList.contains('dark-mode')){
            localStorage.setItem('theme','dark'); // SAUVEGARDE LE CHOIX 'DARK'
            button.textContent='☀️';
            console.log('Switched to dark, stored:', localStorage.getItem('theme'));
        } else {
            localStorage.setItem('theme','light');
            button.textContent='🌙';
            console.log('Switched to light, stored:', localStorage.getItem('theme'));
        }
    });
    </script>
    <div class="mb-4"></div>
    <script src="js/crypto_e2e.js"></script>
    <script>
    document.getElementById('lien-deconnexion')?.addEventListener('click', async (e) => {
        e.preventDefault();
        if (typeof effacerClePriveeDechiffree === 'function') {
            await effacerClePriveeDechiffree();
        }
        window.location.href = e.currentTarget.href;
    });
    </script>
</body>
</html>
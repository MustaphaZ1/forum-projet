</div>
<script>
    const button =
    document.getElementById('theme-toggle');
    console.log('Button trouvé:', button);
    console.log('localStorage theme:', localStorage.getItem('theme'));
    if(localStorage.getItem('theme')==='dark'){
        document.body.classList.add('dark-mode');
        button.textContent='☀️';
        console.log('Mode dark appliqué au démarrage');
    } else {
        document.body.classList.remove('dark-mode');
        button.textContent='🌙';
        console.log('Mode light appliqué au démarrage');
    }
    button.addEventListener('click', () => {
        console.log('Bouton cliqué')
        document.body.classList.toggle('dark-mode');
        if(document.body.classList.contains('dark-mode')){
            localStorage.setItem('theme','dark');
            button.textContent='☀️';
            console.log('Switched to dark, stored:', localStorage.getItem('theme'));
        } else {
            localStorage.setItem('theme','light');
            button.textContent='🌙';
            console.log('Switched to light, stored:', localStorage.getItem('theme'));
        }
    });
    </script>
</body>
</html>
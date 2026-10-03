<?php
// 1. INITIALISATIOIN ET CONFIGURATION DE LA PAGE
// Démarre ou récupere la session active de l'utilisateur
session_start();
// Inclut le fichier de config pour se conneter à la base de donnée via PDO
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/header.php';
// 2. FONCTIONS DE TRAITEMENT DE DONNEES
// Fonction récursive pour organiser les messages
function trierEnThread(array $messages, ?int $parentId=null, int $profondeur = 0): array {
    $resultat=[];
    foreach ($messages as $message){
    // Pour récuperer l'ID du message auquel celui-ci répond
        $messageParentId=$message['id_message_1']!== null ? (int)$message['id_message_1']: null;
    // Si le message est une réponse directe au parent que l'on traite actuellement
        if ($messageParentId===$parentId){
        // On attribue sa profondeur d'affichage
            $message['profondeur']=$profondeur;
            $resultat[]=$message;
        // Appel récursif : on cherche toutes les réponses à CE message specifique
            $reponses= trierEnThread($messages, (int)$message['id_message'], $profondeur + 1);
        // On fusionne les réponses trouvées à la suite dans le tableau final
            $resultat= array_merge($resultat, $reponses);
        }
    }
    return $resultat;
}
// 3. SECURISATION ET RECUPERATION DES PARAMETRES D'URL
// Récupere l'ID du sujet depuis l'URL
$id_sujet=$_GET['id_sujet']??null;
// Sécurité : Vérifie quue l'ID est présent et contient uniquement des chifffres
if(!$id_sujet || !ctype_digit((string)$id_sujet)){
    die('Sujet invalide.');
}
// Initialise la connexion PDO en appelant la fonction présente dans database.php
$pdo=getPDO();
// Récupérer le sujet avec le nom de la catégorie 
$stmt=$pdo->prepare('SELECT sujets.*, categories.nom AS nom_categorie FROM sujets JOIN categories ON sujets.id_categorie=categories.id_categorie WHERE sujets.id_sujet=?');
$stmt->execute([$id_sujet]);
$sujet=$stmt->fetch();
// Sécurité : si le sujet n'existe pas en base de données, on arrete le script
if (!$sujet){die('Sujet introuvable.');}
// 4. GESTION DU FORMULAIRE : ENVOI NOUVEAU MESSAGE/REPONSE

$erreur=''; // Initialisation de la variable de message d'erreur
// Detecte si l'utilisateur a soumis le formulaire (requete HTTP POST)
if($_SERVER['REQUEST_METHOD']==='POST'){
    csrf_verifier();
// Sécurité : Si l'utilisateur n'est pas connecté, on le redirige vers la page de connxion
    if (!isset($_SESSION['id_utilisateur'])){
        header ('Location: connexion.php');
        exit;
    }
// Récupere le texte saisi et supprime les espaces vides inutiles au debut et a la fin
    $contenu=trim($_POST['contenu']??'');
// Récupere l'ID du message parent si l'utilisateur est en train de répondre à quelqu'un
    $id_message_1=$_POST['id_message_1']?? null;
// Vérifie que cet ID parent est bien numérique
    $id_message_1=($id_message_1 && ctype_digit((string)$id_message_1)) ? $id_message_1 : null;
// Détermine si le message est une réponse (1) ou un message principal (0)
    $reponse_message = $id_message_1 ? 1 : 0;
// Validation : Empeche l'envoi de messages null
    if ($contenu===''){
        $erreur='Le message ne peut pas être vide.';
    } else {
    // Enregistre le nouveau message dans la table "messages
        $stmt=$pdo->prepare('INSERT INTO messages (contenu, id_sujet, id_utilisateur, id_message_1, reponse_message) VALUES (?, ?, ?, ?, ?)');
        $stmt->execute([$contenu, $id_sujet, $_SESSION['id_utilisateur'], $id_message_1, $reponse_message]);
    // Redirige l'utilisateur sur la meme page pour vider le formulaire et éviter les doubles envois au rafraichissement
        header('Location: sujet.php?id_sujet=' . $id_sujet);
        exit; 
    }
}
// 5. RECUPERATIOIN ET ORGANISATION DES MESSAGES DU SUJET
// Récupérer les messages et les réponses avec l'auteur
$stmt=$pdo->prepare('SELECT messages.*, utilisateurs.pseudo, parent.contenu AS parent_contenu,parent_user.pseudo AS parent_pseudo FROM messages JOIN utilisateurs ON messages.id_utilisateur=utilisateurs.id_utilisateur LEFT JOIN messages AS parent ON messages.id_message_1=parent.id_message LEFT JOIN utilisateurs AS parent_user ON parent.id_utilisateur=parent_user.id_utilisateur WHERE messages.id_sujet=? ORDER BY messages.date_creation ASC');
$stmt->execute([$id_sujet]);
$messages=$stmt->fetchAll();
// On passe les messages récupérés dans notre fonction pour créer l'arbre de discussion
$messages= trierEnThread($messages);
?>
<p><a href="categorie.php?id_categorie=<?= $sujet['id_categorie'] ?>">&larr; Retour à <?= htmlspecialchars($sujet['nom_categorie']) ?></a></p>
            <main>
    <!-- Affiche le titre sécurisé du sujet-->
        <h1><?= htmlspecialchars($sujet['titre']) ?></h1>
    <!-- Affiche le bouton supprimer si l'utilisateur connecté est l'auteur -->
        <?php if(isset($_SESSION['id_utilisateur'])&& $_SESSION['id_utilisateur']===$sujet['id_utilisateur']): ?>
            <form action="supprimer_sujet.php" class="mb-3" onsubmit="return confirm('Supprimer ce sujet et tous ses messages ?')"; method="post">
                <input type="hidden" name="id_sujet" value="<?= $sujet['id_sujet'] ?>">
                <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer le sujet</button>
            </form>
            <?php endif; ?>
    <!-- Boucle pour afficher chaque message du fil-->
        <?php foreach ($messages as $message): ?>
        <!-- Injecte dynamiquement la marge a gauche-->
            <div class="card mb-3" style="margin-left: <?= $message['profondeur'] * 40 ?>px;">
                <div class="card_body">
                <!-- Verifie si le message est une réponse à un autre message -->
                    <?php if($message['reponse_message']===1): ?>
                    <!-- Affiche le pseudo de l'auteur d'origine-->
                        <div class="border-start-ps-2 mb-2 text-muted small">En réponse à <strong><?= htmlspecialchars($message['parent_pseudo']) ?></strong> : « <?= htmlspecialchars(mb_strimwidth($message['parent_contenu'],0, 80, '...')) ?> »
                    </div>
                    <?php endif; ?>
                <!-- htmlspecialchar = contre le code malveillant | n2blr = convertit les touches "Entrée" par un saut de ligne-->
                    <p class="card-text"><?= nl2br(htmlspecialchars($message['contenu'])) ?></p>
                <!-- Affiche l'auteur et reformule la date brute en d/m/Y H:i -->
                    <small class="text-muted">par <?= htmlspecialchars($message['pseudo']) ?> - <?= date('d/m/Y H:i', strtotime($message['date_creation'])) ?></small>
                    <div class="d-flex gap-2">
                    <div class="mt-2">
                    <!-- Affiche le bouton répondre seulement si connecté-->
                        <?php if (isset($_SESSION['pseudo'])): ?>
                            <button type="button" class="btn btn-sm btn-outline-secondary btn-repondre" data-id="<?= $message['id_message'] ?>" data-pseudo="<?= htmlspecialchars($message['pseudo']) ?>">Répondre à ce message
                            </button>
                        <?php endif; ?>
                    </div>
                <!-- Affiche le bouton supprimer seulement si l'utilisateur est l'auteur-->
                    <?php if(isset($_SESSION['id_utilisateur']) && $_SESSION['id_utilisateur']===$message['id_utilisateur']): ?>
                    <a href="modifier_message.php?id_message=<?= $message['id_message'] ?>" class="btn btn-sm btn-outline-secondary mt-2">Modifier</a>
                        <form action="supprimer_message.php" class="mt-2" onsubmit="return confirm('Supprimer ce message ?');" method="post">
                            <input type="hidden" name="id_message" value="<?= $message['id_message'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Supprimer</button>
                        </form>
                        <?php endif; ?>
                        </div>
                </div>
            </div>
            <?php endforeach; ?>
        <!-- Affiche le bouton "Répondre" si l'utilsateur est connecté-->
            <?php if (isset($_SESSION['pseudo'])): ?>
                <h5 class="mt-4">Répondre</h5>
                <?php if ($erreur): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($erreur) ?></div>
                <?php endif; ?>
                <div class="alert alert-info d-none" id="reponse-a">En réponse à <strong id="reponse-a-pseudo"></strong>
                <button type="button" class="btn btn-sm btn-link" id="annuler-reponse">Annuler</button>
                </div>
                <form action="" method="post" id="form-reponse">
                    <input type="hidden" name="id_message_1" id="id_message_1" value="">
                    <div class="mb-3">
                        <textarea name="contenu" id="contenu" class="form-control" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Envoyer</button>
                </form>
                <?php else: ?>
                    <p><a href="connexion.php">Connecte-toi</a> pour répondre.</p>
                <?php endif; ?>
            </main>
    </div>
    <script>
        // Logique spécifique au bouton réponse
        document.querySelectorAll('.btn-repondre').forEach(function (btn) {
            btn.addEventListener('click',function(){
                document.getElementById('id_message_1').value = this.dataset.id;
                document.getElementById('reponse-a-pseudo').textContent = this.dataset.pseudo;
                document.getElementById('reponse-a').classList.remove('d-none');
                document.getElementById('contenu').focus();
            });
        });
        // Annule la réponse pour refaire un message classique
        document.getElementById('annuler-reponse').addEventListener('click', function(){
            document.getElementById('id_message_1').value = '';
            document.getElementById('reponse-a').classList.add('d-none');
        });
    </script>
<?php require_once __DIR__ . '/../includes/footer.php' ?>
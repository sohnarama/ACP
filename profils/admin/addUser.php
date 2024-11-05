<?php
// Démarre une session ou reprend une session existante
session_start();
if (empty($_SESSION['username']) && empty($_SESSION['mdp'])) {
    header('Location: /COUD/codif/');
    exit();
}

// Connexion à la base de données
include('../../traitement/fonction.php');

// Récupération de l'ID de l'utilisateur à modifier
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : null;

// Variables pour stocker les informations de l'utilisateur
$username = "";
$nom_complet = "";
$profil = "";
$password = "";

// Si un utilisateur est sélectionné, on récupère ses informations
if ($user_id) {
    $sql = "SELECT id, username, password, nom_complet, profil FROM User WHERE id = ?";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // On assigne les valeurs récupérées aux variables
    $username = $user['username'];
    $nom_complet = $user['nom_complet'];
    $profil = $user['profil'];
    $password = $user['password'];
    
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer les données du formulaire
    $username = $_POST['username'];
    $nom_complet = $_POST['nom_complet'];
    $profil = $_POST['profil'];
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Si c'est un ajout d'utilisateur (user_id est nul)
    if ($user_id == null) {
        // Vérifier si les mots de passe correspondent
        if ($password !== $confirm_password) {
            echo "Les mots de passe ne correspondent pas.";
            exit();
        }

        try {
            // Ajouter un nouvel utilisateur
            addUser($username, $nom_complet, $password, $profil);
            // Rediriger ou afficher un message de succès
            echo "Utilisateur ajouté avec succès.";
        } catch (Exception $e) {
            // Gérer l'erreur
            echo "Erreur : " . $e->getMessage();
        }
    } else {
        // Mettre à jour l'utilisateur existant (logique existante)
        try {
            updateUtilisateur($user_id, $username, $nom_complet, $password, $profil);
            echo "Utilisateur mis à jour avec succès.";
        } catch (Exception $e) {
            echo "Erreur : " . $e->getMessage();
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupération des données du formulaire
    $username = $_POST['username'];
    $nom_complet = $_POST['nom_complet'];
    $profil = $_POST['profil'];
    $password = $_POST['password']?? '';

    // Si un mot de passe est fourni, utiliser celui-ci, sinon passer null
    $motDePasse = !empty($password) ? $password : null;

    try {
        // Appel de la fonction pour mettre à jour l'utilisateur
        updateUtilisateur( $user_id, $username, $nom_complet, $motDePasse, $profil);
        // Rediriger ou afficher un message de succès
        echo "Utilisateur mis à jour avec succès.";
    } catch (Exception $e) {
        // Gérer l'erreur
        echo "Erreur : " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <!-- Metas, liens CSS et scripts -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../../assets/css/vendor.css" />
    <link rel="stylesheet" href="../../assets/css/main.css" />
    <link rel="stylesheet" href="../../assets/css/login.css" />
    <link rel="stylesheet" href="/assets/bootstrap/css/bootstrap.min.css">
    <!-- script================================================== -->
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/bootstrap/js/bootstrap.min.js">
    <link rel="stylesheet" href="../../assets/bootstrap/js/bootstrap.bundle.min.js">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script src="../assets/js/modernizr.js"></script>
    <script src="../../assets/js/pace.min.js"></script>
 <!-- Lien vers les icônes Fontawesome -->
 <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Inclure Font Awesome pour les icônes -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

    <title>Formulaire Utilisateur</title>
    <style>.input-group {
    position: relative;
}

.input-group-text {
    cursor: pointer; /* Change le curseur pour indiquer que l'icône est cliquable */
}
</style>
</head>

<body>
    <?php include('../../head.php'); ?>
    <div class="container" style="width:50%;">
        <div class="contact__form1">
            <form method="POST" action="">
                <!-- Champ caché pour l'ID de l'utilisateur lors de la modification -->
                <?php if (isset($user['id'])): ?>
                <input type="hidden" name="id" value="<?php echo htmlspecialchars($user['id']); ?>">
                <?php endif; ?>

                <fieldset>
                    <legend><strong>VEUILLEZ RENSEIGNER LES CHAMPS</strong></legend>

                    <!-- Username -->
                    <div class="mb-3">
                        <label for="username" class="form-label">Nom d'utilisateur</label>
                        <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($username, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Entrez le nom d'utilisateur" required class="form-control">
                    </div>

                    <!-- Nom complet -->
                    <div class="mb-3">
                        <label for="nom_complet" class="form-label">Nom complet</label>
                        <input type="text" name="nom_complet" id="nom_complet" value="<?php echo htmlspecialchars($nom_complet, ENT_QUOTES, 'UTF-8'); ?>" placeholder="Entrez le nom complet" required class="form-control">
                    </div>

                    <!-- Mot de passe (uniquement si création) -->
                    <?php if ($user_id == null): ?>
                    <div class="mb-3">
                        <label for="password" class="form-label">Mot de passe</label>
                        <input type="password" name="password" id="password" required class="form-control" placeholder="Entrez le mot de passe">
                    </div>
                     <!-- Confirmer Mot de Passe -->
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirmer Mot de Passe</label>
                        <input type="password" name="confirm_password" id="confirm_password" required class="form-control"
                            placeholder="Confirmez le mot de passe" oninput="checkPasswords()">
                        <span id="error-message" style="color:red; display:none;">Les mots de passe ne correspondent pas.</span>
                    </div>
                    <?php endif; ?>
                    <?php if (isset($user['id'])): ?>
                    <label for="password" class="form-label">Mot de passe</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" required value="<?php echo htmlspecialchars($user['password']); ?>" class="form-control" placeholder="Entrez le mot de passe">
                        <span class="input-group-text" id="togglePassword">
                            <i class="fas fa-eye" id="passwordIcon"></i>
                        </span>
                    </div>
                    <?php endif; ?>
                
                    <!-- Profil (Enumération des rôles) -->
                    <div class="mb-3">
                        <label for="profil" class="form-label">Profil</label>
                        <select value="<?php echo ($profil)?>" name="profil" id="profil" required class="form-select">
                            <option value="<?php echo ($profil)?>" disabled <?php echo (empty($profil)) ? 'selected' : ''; ?>>Choisir...</option>
                            <option value="regisseurEntree" <?php echo ($profil === 'regisseurEntree') ? 'selected' : ''; ?>>Régisseur Entrée</option>
                            <option value="regisseurSortie" <?php echo ($profil === 'regisseurSortie') ? 'selected' : ''; ?>>Régisseur Sortie</option>
                            <option value="admin" <?php echo ($profil === 'admin') ? 'selected' : ''; ?>>Admin</option>
                            <option value="controleur" <?php echo ($profil === 'controleur') ? 'selected' : ''; ?>>Contrôleur</option>
                        </select>
                    </div>

                    <!-- Bouton d'enregistrement -->
                    <div class="form-field">
    <button type="submit" class="btn btn-primary">
        <strong>
            <?php echo $user_id ? 'METTRE À JOUR' : 'ENREGISTRER'; ?>
        </strong>
    </button>
</div>

                </fieldset>
            </form>

            <br>
            <center> <a href="javascript:history.back()">Retour</a> </center>
        </div>
    </div>
    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
    <script>
    function checkPasswords() {
        // Récupérer les valeurs des champs de mot de passe
        var password = document.getElementById("password").value;
        var confirmPassword = document.getElementById("confirm_password").value;
        var errorMessage = document.getElementById("error-message");

        // Vérifier si les mots de passe correspondent
        if (password !== confirmPassword) {
            errorMessage.style.display = "block"; // Afficher le message d'erreur
        } else {
            errorMessage.style.display = "none"; // Cacher le message d'erreur si les mots de passe correspondent
        }
    }
    </script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const passwordInput = document.getElementById('password');
    const togglePasswordButton = document.getElementById('togglePassword');
    const passwordIcon = document.getElementById('passwordIcon');

    togglePasswordButton.addEventListener('click', function() {
        // Vérifier le type du champ de mot de passe
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type); // Changer le type

        // Changer l'icône en fonction de l'état
        if (type === 'text') {
            passwordIcon.classList.remove('fa-eye');
            passwordIcon.classList.add('fa-eye-slash');
        } else {
            passwordIcon.classList.remove('fa-eye-slash');
            passwordIcon.classList.add('fa-eye');
        }
    });
});
</script>


    <script src="../../assets/js/jquery-3.2.1.min.js"></script>
    <script src="../../assets/js/plugins.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>


</html>

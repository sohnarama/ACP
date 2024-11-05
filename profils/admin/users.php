<?php
session_start();
if (empty($_SESSION['username']) && empty($_SESSION['mdp'])) {
    header('Location: /../../CONTROL_FATURATION0_SM/');
    exit();
}

include('../../traitement/fonction.php');


$userId = $_SESSION['id_user'];

// Nombre de lignes par page
$limit = 10;
// Numéro de la page actuelle (par exemple, à partir d'une requête GET)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Si une recherche est effectuée
if ($search) {
    $allUsers = GetUsersBySearch($search); // Fonction à adapter pour la recherche
} else {
    $allUsers = GetAllUser($limit, $page); // Fonction à adapter pour récupérer les utilisateurs avec pagination
}
if (isset($_POST['action']) && $_POST['action'] == 'changeUserStatus') {
    $userId = $_POST['userStatusChange'];
    $newStatus = $_POST['newStatus'];

    ChangeUserStatus($userId, $newStatus); // Fonction qui modifie le statut dans la base de données
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
<script src="../../assets/js/script.js"></script>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <link rel="stylesheet" href="../../assets/css/vendor.css" />
    <link rel="stylesheet" href="../../assets/css/main.css" />
    <!-- <link rel="stylesheet" href="../../assets/css/login.css" /> -->
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <!-- script================================================== -->
    <link rel="stylesheet" href="../../assets/css/styles.css">
    <link rel="stylesheet" href="../../assets/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../../assets/bootstrap/js/bootstrap.min.js">
    <link rel="stylesheet" href="../../assets/bootstrap/js/bootstrap.bundle.min.js">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <script src="../../assets/js/modernizr.js"></script>
    <script src="../../assets/js/pace.min.js"></script>
    <!-- Lien vers les icônes Fontawesome -->
    <link rel="stylesheet" type="text/css" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link  rel="stylesheet" href="../../assets/js/script.js"></link>
<style>.modal-content {
    border-radius: 10px;
    box-shadow: 0px 5px 15px rgba(0, 0, 0, 0.5);
}

.modal-header, .modal-footer {
    border: none;
}

.modal-header {
    background-color: #f8f9fa; /* Couleur plus claire */
}

.modal-footer {
    background-color: #f8f9fa;
}

.modal-title {
    font-weight: bold;
}

.btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

.btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}
</style>
    <title>Gestion des Utilisateurs</title>
</head>

<body>
    <?php include('../../head.php'); ?>
    <br>
    <nav class="navbar navbar-light bg">
    <div class="container">
    <div class="d-flex align-items-center">
        <form class="d-flex me-2" method="GET" action="">
            <input class="form-control me-2" type="search" name="search" placeholder="Recherche" aria-label="Search">
            <button type="submit" class="btn btn-primary" style="height:30px">Rechercher</button>
        </form>
        <a class="nav-link" href="addUser.php">
            <button type="button" class="btn btn-success " style="height: 30px; width: 60px;" >Ajouter</button>
        </a>
    </div>
</div>

    </nav>

    <br>
    <div id="refreshedContent" class="container-fluid">
        <div class="table-responsive">
            <table class="table table-striped" style="font-size: 20px; font-family: 'Times New Roman', Times, serif;">
                <thead>
                    <tr>
                        <th scope="col"><b>N°</b></th>
                        <th scope="col"><b>Username</b></th>
                        <th scope="col"><b>Nom Complet</b></th>
                        <th scope="col"><b>Profil</b></th>
                        <th scope="col"><b>Statut</b></th>
                        <th scope="col"><b>Modif</b></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($allUsers): ?>
                    <?php foreach ($allUsers as $user): ?>
                        <!-- Exclure les utilisateurs avec le profil admin -->
                        <?php if ($user['profil'] !== 'admin'): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['nom_complet']); ?></td>
                            <td><?php echo htmlspecialchars($user['profil']); ?></td>
                            <td>
                                <button class="btn btn-outline-primary change-status-btn" data-bs-toggle="modal" data-bs-target="#statusModal"
                                        data-user-id="<?php echo htmlspecialchars($user['id']); ?>"
                                        data-current-status="<?php echo htmlspecialchars($user['statut']); ?>">
                                    <?php echo $user['statut'] == 1 ? 'Désactiver' : 'Activer'; ?>
                                </button>
                            </td>

                            <td>
                                <a href="addUser.php?user_id=<?php echo htmlspecialchars($user['id']); ?>" class="btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="auto" fill="currentColor"
                                        class="bi bi-pencil" viewBox="0 0 16 16">
                                        <path d="M12.146.854a.5.5 0 0 1 .708 0l2.292 2.292a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.651-.651l2-5a.5.5 0 0 1 .11-.168l10-10zm-10.61 10.763L1.5 14.5l2.883-.036-2.12-2.121zm11.357-8.036L10.207 1.5 3 8.707V11h2.293l7.293-7.293z" />
                                    </svg>
                                </a>
                            </td>
                        </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr>
                        <td colspan="6">Aucun utilisateur trouvé.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>

            </table>
        </div>
    </div>
 
    <!-- Modal de confirmation de modification du statut -->
    <div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered"> <!-- Ajout de modal-lg pour une plus grande taille -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">Confirmation de Changement de Statut</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="lead">Voulez-vous vraiment <span id="statusActionText"></span> cet utilisateur ?</p>
            </div>
            <div class="modal-footer d-flex justify-content-between">
                <button type="submit" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                <form id="statusForm" method="post" action="">
                    <input type="hidden" name="userStatusChange" id="statusUserIdInput" value="">
                    <input type="hidden" name="newStatus" id="newStatusInput" value="">
                    <input type="hidden" name="action" value="changeUserStatus">
                    <button type="submit" class="btn btn-danger">Confirmer</button>
                </form>
            </div>
        </div>
    </div>
</div>



</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gérer le clic sur les boutons pour changer le statut
    document.querySelectorAll('.change-status-btn').forEach(function(button) {
        button.addEventListener('click', function() {
            console.log("Button clicked"); // Tester si le clic est capturé
            var annulerButton = document.querySelector('.btn-secondary');
            const userId = this.getAttribute('data-user-id');
            const currentStatus = parseInt(this.getAttribute('data-current-status'));

            console.log("User ID:", userId, "Current Status:", currentStatus); // Vérifier les valeurs
            // Ajouter un évènement sur le bouton Annuler
            // Mettre à jour le texte de la modale
            const actionText = currentStatus === 1 ? 'désactiver' : 'activer';
            document.getElementById('statusActionText').textContent = actionText;

            // Mettre à jour les champs cachés
            document.getElementById('statusUserIdInput').value = userId;
            document.getElementById('newStatusInput').value = currentStatus === 1 ? 0 : 1;

            // Afficher la modale
            $('#statusModal').modal('show');
        });
    });

   
});
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    // Écouteur pour le bouton d'annulation
    const cancelButton = document.querySelector('.btn-secondary');
    if (cancelButton) {
        cancelButton.addEventListener('click', function() {
            console.log("L'utilisateur a cliqué sur Annuler");
            $('#statusModal').modal('hide');

        });
    }
});

</script>

<script src="../../assets/js/script.js"></script>
 
    <!-- Inclure jQuery -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.0.0/dist/js/bootstrap.min.js" crossorigin="anonymous"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>


</html>

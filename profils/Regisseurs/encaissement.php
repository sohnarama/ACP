<?php
session_start();
if (empty($_SESSION['username']) && empty($_SESSION['mdp'])) {
    header('Location: /../../CONTROL_FATURATION0_SM/');
    exit();
}

// Inclusion du fichier des fonctions et connexion à la base de données
include('../../traitement/fonction.php');
// Vérifier si l'utilisateur est actif
$userId = $_SESSION['id_user'];
$user = GetUserById($userId); // Fonction pour récupérer les détails de l'utilisateur par ID
if ($user['statut'] == 0) {
    // Si l'utilisateur est inactif, le rediriger ou afficher un message
    echo "Votre compte est désactivé. Veuillez contacter l'administrateur.";
    exit();
}
// Initialisation des variables pour les totaux
$totalEntree = 0;
$totalSortie = 0;

$limit = 10;

// Page actuelle (par défaut 1 si non définie)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // S'assurer que la page est au moins 1
$service='';
// Calcul de l'offset pour la requête
$start = ($page - 1) * $limit;
 $regisseur = $_SESSION['profil'];
 $user = $_SESSION['id_user'];
// Total des opérations pour la pagination
// Total des opérations pour la pagination
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $service = isset($_POST['service']) ? $_POST['service'] : '';
    
    // Recherche par utilisateur
   

    if (!empty($date_debut) && !empty($date_fin)) {
        // Récupérer les opérations par date et utilisateur avec pagination
        $totalEntreeOperations = CountsOperationByUserAndDateRange($regisseur, $user, $date_debut, $date_fin, 'ENCAISSEMENT', $service);
        $operationsEntree = GetOperationsByUserAndDateRange($regisseur, $user, $date_debut, $date_fin, 'ENCAISSEMENT', $start, $limit, $service);

        $totalSortieOperations = CountsOperationByUserAndDateRange($regisseur, $user, $date_debut, $date_fin, 'DECAISSEMENT', $service);
        $operationsSortie = GetOperationsByUserAndDateRange($regisseur, $user, $date_debut, $date_fin, 'DECAISSEMENT', $start, $limit, $service);
    } else {
        $totalEntreeOperations = CountOperationsByUserAndNature($regisseur, $user, 'ENCAISSEMENT', $service);
        $operationsEntree = GetOperationsByUserAndNature($regisseur, $user, 'ENCAISSEMENT', $start, $limit, $service);

        $totalSortieOperations = CountOperationsByUserAndNature($regisseur, $user, 'DECAISSEMENT', $service);
        $operationsSortie = GetOperationsByUserAndNature($regisseur, $user, 'DECAISSEMENT', $start, $limit, $service);
    }
} else {
    $totalEntreeOperations = CountOperationsByUserAndNature($regisseur, $user, 'ENCAISSEMENT',$service);
    $operationsEntree = GetOperationsByUserAndNature($regisseur, $user, 'ENCAISSEMENT', $start, $limit,$service);

    $totalSortieOperations = CountOperationsByUserAndNature($regisseur, $user, 'DECAISSEMENT',$service);
    $operationsSortie = GetOperationsByUserAndNature($regisseur, $user, 'DECAISSEMENT', $start, $limit,$service);
}


// Calcul du nombre total de pages pour chaque type
$totalPagesEntree = ceil($totalEntreeOperations / $limit);
$totalPagesSortie = ceil($totalSortieOperations / $limit);
?>

<!DOCTYPE html>
<html lang="en">

<head>
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
</head>

<body>

<?php 
include('../../head.php'); 
  $services = GetAllServices(); // Assurez-vous que cette fonction renvoie tous les services disponibles

?>

<div class="container">
<h2 class="text-center mb-4">Recherche par période</h2>
    <form method="POST" action="">
<div class="row align-items-end mb-4">
        <div class="col-md-3 mb-3">
            <label for="date_debut">Date de début :</label>
            <input type="date" id="date_debut" name="date_debut" class="form-control" value="<?php echo isset($_POST['date_debut']) ? $_POST['date_debut'] : ''; ?>" >
        </div>
        <div class="col-md-3 mb-3">
            <label for="date_fin">Date de fin :</label>
            <input type="date" id="date_fin" name="date_fin" class="form-control" value="<?php echo isset($_POST['date_fin']) ? $_POST['date_fin'] : ''; ?>" >
        </div>
        <div class="col-md-3 mb-3">
            <label for="service">Service :</label>
            <select id="service" name="service" class="form-control">
                <option value="">Tous les services</option>
                <?php foreach ($services as $service): ?>
                    <option value="<?php echo htmlspecialchars($service['id']); ?>" <?php echo (isset($_POST['service']) && $_POST['service'] == $service['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($service['libelle']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
       
        <div class="col-md-3 mb-3">
            <br>
            <button type="submit" class="btn btn-primary">Rechercher</button>
        </div>
    </div>
</form>

 <?php if ($regisseur == 'regisseurEntree'): ?>
            <!-- Table des encaissements -->
            <h1 class="text-center">Liste des Encaissements</h1>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Montant</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Libellé</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($operationsEntree as $operation): ?>
                        <tr>
                            <td><?php echo $operation['id_op']; ?></td>
                            <td><?php echo number_format($operation['montant'], 2); ?></td>
                            <td><?php echo $operation['service_libelle']; ?></td>
                            <td><?php echo $operation['date']; ?></td>
                            <td><?php echo $operation['libelle']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th><?php echo number_format(array_sum(array_column($operationsEntree, 'montant')), 2); ?> FCFA</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
            <!-- Pagination Encaissements -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Précédent</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPagesEntree; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $totalPagesEntree) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Suivant</a>
                    </li>
                </ul>
            </nav>
        <?php elseif ($regisseur == 'regisseurSortie'): ?>
            <!-- Table des décaissements -->
            <h1 class="text-center">Liste des Décaissements</h1>
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Montant</th>
                        <th>Service</th>
                        <th>Date</th>
                        <th>Libellé</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($operationsSortie as $operation): ?>
                        <tr>
                            <td><?php echo $operation['id_op']; ?></td>
                            <td><?php echo number_format($operation['montant'], 2); ?></td>
                            <td><?php echo $operation['service_libelle']; ?></td>
                            <td><?php echo $operation['date']; ?></td>
                            <td><?php echo $operation['libelle']; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Total</th>
                        <th><?php echo number_format(array_sum(array_column($operationsSortie, 'montant')), 2); ?> FCFA</th>
                        <th colspan="3"></th>
                    </tr>
                </tfoot>
            </table>
            <!-- Pagination pour Decaissements -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?>" tabindex="-1">Précédent</a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPagesSortie; $i++): ?>
                        <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo ($page >= $totalPagesSortie) ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">Suivant</a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>

</div>

<script src="../../assets/js/jquery-3.2.1.min.js"></script>
<script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>
</html>

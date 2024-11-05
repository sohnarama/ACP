<?php
session_start();
if (empty($_SESSION['username']) && empty($_SESSION['mdp'])) {
    header('Location: /../../CONTROL_FATURATION0_SM/');
    exit();
}

// Inclusion du fichier des fonctions et connexion à la base de données
include('../../traitement/fonction.php');

// Initialisation des variables pour les totaux
$totalEntree = 0;
$totalSortie = 0;

$limit = 10;

// Page actuelle (par défaut 1 si non définie)
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1); // S'assurer que la page est au moins 1

// Calcul de l'offset pour la requête
$start = ($page - 1) * $limit;

// Total des opérations pour la pagination
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date_debut = $_POST['date_debut'];
    $date_fin = $_POST['date_fin'];
    $service = $_POST['service'] ?? '';
    $user = $_POST['user'] ?? '';

    if (!empty($date_debut) && !empty($date_fin)) {
        // Récupérer les opérations par date avec pagination
        $totalEntreeOperations = CountsOperationsByDateOnly($date_debut, $date_fin, 'ENCAISSEMENT');
        $operationsEntree = GetOperationByDateOnly($date_debut, $date_fin, 'ENCAISSEMENT', $start, $limit);

        $totalSortieOperations = CountsOperationsByDateOnly($date_debut, $date_fin, 'DECAISSEMENT');
        $operationsSortie =  GetOperationByDateOnly($date_debut, $date_fin, 'DECAISSEMENT', $start, $limit);
        if (!empty($service)) {
            $totalEntreeOperations = CountsOperationsByServices($date_debut, $date_fin, 'ENCAISSEMENT', $service);
            $operationsEntree = GetOperationByServices($date_debut, $date_fin, 'ENCAISSEMENT', $service, $start, $limit);

            $totalSortieOperations = CountsOperationsByServices($date_debut, $date_fin, 'DECAISSEMENT', $service);
            $operationsSortie = GetOperationByServices($date_debut, $date_fin, 'DECAISSEMENT', $service, $start, $limit);
        } else {
            $totalEntreeOperations = CountsOperationsByDateOnly($date_debut, $date_fin, 'ENCAISSEMENT');
            $operationsEntree = GetOperationByDateOnly($date_debut, $date_fin, 'ENCAISSEMENT', $start, $limit);

            $totalSortieOperations = CountsOperationsByDateOnly($date_debut, $date_fin, 'DECAISSEMENT');
            $operationsSortie =  GetOperationByDateOnly($date_debut, $date_fin, 'DECAISSEMENT', $start, $limit);
        }
        if (!empty($user)) {
            $totalEntreeOperations = CountsOperationsByLogins($date_debut, $date_fin, 'ENCAISSEMENT', $user);
            $operationsEntree = GetOperationByLogins($date_debut, $date_fin, 'ENCAISSEMENT', $user, $start, $limit);

            $totalSortieOperations = CountsOperationsByLogins($date_debut, $date_fin, 'DECAISSEMENT', $user);
            $operationsSortie = GetOperationByLogins($date_debut, $date_fin, 'DECAISSEMENT', $user, $start, $limit);
        }
    } elseif (!empty($service)) {
        // Filtrer par service et login si spécifié
        if (!empty($user)) {
            $totalEntreeOperations = CountsOperationsByServicesAndLogin('ENCAISSEMENT', $user, $service);
            $operationsEntree = GetOperationByServicesAndLogin('ENCAISSEMENT', $user, $service, $start, $limit);

            $totalSortieOperations = CountsOperationsByServicesAndLogin('DECAISSEMENT', $user, $service);
            $operationsSortie = GetOperationByServicesAndLogin('DECAISSEMENT', $user, $service, $start, $limit);
        } else {
            // Filtrer uniquement par service
            $totalEntreeOperations = CountsOperationsByServicesOnly('ENCAISSEMENT', $service);
            $operationsEntree = GetOperationByServicesOnly('ENCAISSEMENT', $service, $start, $limit);

            $totalSortieOperations = CountsOperationsByServicesOnly('DECAISSEMENT', $service);
            $operationsSortie = GetOperationByServicesOnly('DECAISSEMENT', $service, $start, $limit);
        }
    } elseif (!empty($user)) {
        // Filtrer uniquement par login
        $totalEntreeOperations = CountsOperationsByLoginOnly('ENCAISSEMENT', $user);
        $operationsEntree = GetOperationByLoginOnly('ENCAISSEMENT', $user, $start, $limit);

        $totalSortieOperations = CountsOperationsByLoginOnly('DECAISSEMENT', $user);
        $operationsSortie = GetOperationByLoginOnly('DECAISSEMENT', $user, $start, $limit);
    } else {
        // Si aucun filtre, obtenir toutes les opérations
        $totalEntreeOperations = CountOperationsByNature('ENCAISSEMENT');
        $operationsEntree = GetOperationsByNature('ENCAISSEMENT', $start, $limit);

        $totalSortieOperations = CountOperationsByNature('DECAISSEMENT');
        $operationsSortie = GetOperationsByNature('DECAISSEMENT', $start, $limit);
    }
} else {
    $totalEntreeOperations = CountOperationsByNature('ENCAISSEMENT');
    $operationsEntree = GetOperationsByNature('ENCAISSEMENT', $start, $limit);

    $totalSortieOperations = CountOperationsByNature('DECAISSEMENT');
    $operationsSortie = GetOperationsByNature('DECAISSEMENT', $start, $limit);
}

/* $totalEntreeOperations = CountsOperationsByDateRange($date_debut, $date_fin, 'ENCAISSEMENT',$user,$service);
        $operationsEntree = GetOperationByDateRange($date_debut, $date_fin, 'ENCAISSEMENT', $user,$service,$start, $limit);

        $totalSortieOperations = CountsOperationsByDateRange($date_debut, $date_fin, 'DECAISSEMENT',$user,$service);
        $operationsSortie = GetOperationByDateRange($date_debut, $date_fin, 'DECAISSEMENT', $user,$service,$start, $limit);
    } else {
        $totalEntreeOperations = CountOperationsByNature('ENCAISSEMENT');
        $operationsEntree = GetOperationsByNature('ENCAISSEMENT', $start, $limit);

        $totalSortieOperations = CountOperationsByNature('DECAISSEMENT');
        $operationsSortie = GetOperationsByNature('DECAISSEMENT', $start, $limit);
    }*/

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
    $users = GetAllUsers();

    ?>

    <div class="container">
        <!-- <h2 class="text-center mb-4">Recherche par période</h2> -->
        <form method="POST" action="" style="justify-content: center;
            align-items: center;">
            <div class="row mb-4 align-items-end">
                <div class="col-md-2">
                    <label for="date_debut">Date de début :</label>
                    <input type="date" id="date_debut" name="date_debut" class="form-control" value="<?php echo isset($_POST['date_debut']) ? $_POST['date_debut'] : ''; ?>">
                </div>
                <div class="col-md-2">
                    <label for="date_fin">Date de fin :</label>
                    <input type="date" id="date_fin" name="date_fin" class="form-control" value="<?php echo isset($_POST['date_fin']) ? $_POST['date_fin'] : ''; ?>">
                </div>
                <div class="col-md-2">
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
                <div class="col-md-2">
                    <label for="user">Utilisateur :</label>
                    <select id="user" name="user" class="form-control">
                        <option value="">Tous les utilisateurs</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?php echo htmlspecialchars($user['id']); ?>" <?php echo (isset($_POST['user']) && $_POST['user'] == $user['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($user['nom_complet']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex flex-column">
                    <label>&nbsp;</label> <!-- Pour garder l'alignement -->
                    <button type="submit" class="btn btn-primary btn-sm " style="height: 30px; width: 70px;">Rechercher</button>
                </div>
            </div>
        </form>

        <!-- Table des opérations d'encaissement -->
        <h1 class="text-center">Liste des Encaissements</h1>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Montant Entrée (Encaissement)</th>
                    <th>Service Concerné</th>
                    <th>Date</th>
                    <th>Libellé</th>
                    <th>Utilisateur</th>

                </tr>
            </thead>
            <tbody>
                <?php foreach ($operationsEntree as $operation): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($operation['id_op']); ?></td>
                        <td><?php echo number_format($operation['montant'], 2); ?></td>
                        <td><?php echo htmlspecialchars($operation['service_libelle']); ?></td>
                        <td><?php echo htmlspecialchars($operation['date']); ?></td>
                        <td><?php echo htmlspecialchars($operation['libelle']); ?></td>
                        <td><?php echo htmlspecialchars($operation['user_name']); ?></td>

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

        <!-- Pagination pour Encaissements -->
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
    </div>

    <script src="../../assets/js/jquery-3.2.1.min.js"></script>
    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>
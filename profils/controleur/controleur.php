<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL); 

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

$limit = 10; // Nombre d'opérations par page
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1; // Page actuelle, au moins 1
$start = ($page - 1) * $limit; // Calcul de l'offset

// Récupération des filtres si soumis
$date_debut = $_POST['date_debut'] ?? '';
$date_fin = $_POST['date_fin'] ?? '';
$service = $_POST['service'] ?? '';
$login = $_POST['user'] ?? '';

// Conditions dynamiques pour filtrer les résultats
// Vérifier si les champs de date sont remplis
if (!empty($date_debut) && !empty($date_fin)) {
    // Filtrer par date, service et login si spécifiés
    if (!empty($service) && !empty($login)) {
        $totalOperations = CountOperationsByDateRangeServiceAndLogin($date_debut, $date_fin, $service, $login);
        $operations = GetOperationsByDateRangeServiceAndLogin($date_debut, $date_fin, $service, $login, $start, $limit);
    } elseif (!empty($service)) {
        // Filtrer par date et service
        $totalOperations = CountOperationsByDateRangeAndService($date_debut, $date_fin, $service);
        $operations = GetOperationsByDateRangeAndService($date_debut, $date_fin, $service, $start, $limit);
    } elseif (!empty($login)) {
        // Filtrer par date et login
        $totalOperations = CountOperationsByDateRangeAndLogin($date_debut, $date_fin, $login);
        $operations = GetOperationsByDateRangeAndLogin($date_debut, $date_fin, $login, $start, $limit);
    } else {
        // Filtrer uniquement par date
        $totalOperations = CountOperationsByDateRange($date_debut, $date_fin);
        $operations = GetOperationsByDateRange($date_debut, $date_fin, $start, $limit);
    }
} elseif (!empty($service)) {
    // Filtrer par service et login si spécifié
    if (!empty($login)) {
        $totalOperations = CountOperationsByServiceAndLogin($service, $login);
        $operations = GetOperationsByServiceAndLogin($service, $login, $start, $limit);
    } else {
        // Filtrer uniquement par service
        $totalOperations = CountOperationsByService($service);
        $operations = GetOperationsByService($service, $start, $limit);
    }
} elseif (!empty($login)) {
    // Filtrer uniquement par login
    $totalOperations = CountOperationsByLogs($login);
    $operations =GetOperationsByLogs($login,$start,$limit);
} else {
    // Si aucun filtre, obtenir toutes les opérations
    $totalOperations = CountAllOperations();
    $operations = GetAllOperations($start, $limit);
}

// Afficher les résultats ou autres traitements...


// Calcul du nombre total de pages
$totalPages = ceil($totalOperations / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <!-- Metas, liens CSS et scripts -->
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <!-- <link rel="stylesheet" href="../../assets/css/vendor.css" /> -->
    <link rel="stylesheet" href="../../assets/css/main.css" />
    <!-- <link rel="stylesheet" href="../../assets/css/login.css" /> -->
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
<?php include('../../head.php'); ?>

<div class="container">

    <!-- Formulaire de recherche -->
    <form method="POST" action="" style="justify-content: right;
            align-items: center; padding-left: 10%; padding-right: 10%; padding-bottom:2%;">
    <div class="row mb-4 align-items-end">
        <div class="col-md-2">
            <label for="date_debut">Date de début :</label>
            <input type="date" id="date_debut" name="date_debut" class="form-control" value="<?= $date_debut ?>">
        </div>
        <div class="col-md-2">
            <label for="date_fin">Date de fin :</label>
            <input type="date" id="date_fin" name="date_fin" class="form-control" value="<?= $date_fin ?>">
        </div>
        <div class="col-md-2">
            <label for="user">Utilisateur :</label>
            <select id="user" name="user" class="form-control">
                <option value="">Tous les utilisateurs</option>
                <?php foreach (GetAllUsers() as $u): ?>
                    <option value="<?= $u['id'] ?>" <?= $login == $u['id'] ? 'selected' : '' ?>><?= htmlspecialchars($u['nom_complet']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="service">Service :</label>
            <select id="service" name="service" class="form-control">
                <option value="">Tous les services</option>
                <?php foreach (GetAllServices() as $s): ?>
                    <option value="<?= $s['id'] ?>" <?= $service == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['libelle']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2 d-flex flex-column">
            <label>&nbsp;</label> <!-- Pour garder l'alignement -->
            <button type="submit" class="btn btn-primary btn-sm " style="height: 30px; width: 120px;">Rechercher</button>
        </div>
    </div>
</form>
        
    <h1 class="text-center">Liste des opérations</h1>
    <div class="table-responsive">

<table class="table table-bordered">
    <thead>
    <tr>
        <th>ID</th>
        <th>Montant Entrée (Encaissement)</th>
        <th>Montant Sortie (Décaissement)</th>
        <th>Service</th>
        <th>Date</th>
        <th>Libellé</th>
        <th>Utilisateur</th>
        <th>Action</th> <!-- Nouvelle colonne pour les actions -->
    </tr>
</thead>
<tbody>
    <?php foreach ($operations as $operation): ?>
        <tr>
            <td><?= htmlspecialchars($operation['id_op']) ?></td>
            <td>
            <?php 
            if ($operation['nature'] == 'ENCAISSEMENT') {
                echo number_format($operation['montant'], 2);
                $totalEntree += $operation['montant'];
            } else {
                echo '-';
            }
            ?>
            </td>
            <td>
            <?php 
            if ($operation['nature'] == 'DECAISSEMENT') {
                echo number_format($operation['montant'], 2);
                $totalSortie += $operation['montant'];
            } else {
                echo '-';
            }
            ?>
            </td>
            <td><?= htmlspecialchars($operation['service_libelle']) ?></td>
            <td><?= htmlspecialchars($operation['date']) ?></td>
            <td><?= htmlspecialchars($operation['libelle']) ?></td>
            <td><?php echo htmlspecialchars($operation['user_name']); ?></td>
            <td>
                <form method="POST" action="delete_operation.php" style="display:inline;">
                    <input type="hidden" name="id_op" value="<?= htmlspecialchars($operation['id_op']) ?>">
                    <button type="submit" class="btn btn-danger btn-sm full-width" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette opération ?');">
                        <i class="fa fa-trash"></i> Supprimer
                    </button>
                </form>
            </td>
        </tr>
    <?php endforeach; ?>
</tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th><?= number_format($totalEntree, 2) ?> FCFA</th>
                <th><?= number_format($totalSortie, 2) ?> FCFA</th>
                <th colspan="4"></th>
            </tr>
            <tr>
                <th>Solde</th>
                <th colspan="2"><?= number_format($totalEntree - $totalSortie, 2) ?> FCFA</th>
                <th colspan="4"></th>
            </tr>
        </tfoot>
    </table>
</div>
    <!-- Pagination -->
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>" tabindex="-1">Précédent</a>
            </li>
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= $i == $page ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>"><?= $i ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $page >= $totalPages ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>">Suivant</a>
            </li>
        </ul>
    </nav>
</div>
</body>
<script src="../../assets/js/script.js"></script>
  <script src="../../assets/js/jquery-3.2.1.min.js"></script>
  <script src="../../assets/js/plugins.js"></script>
  <script src="../../assets/js/main.js"></script>
</html>


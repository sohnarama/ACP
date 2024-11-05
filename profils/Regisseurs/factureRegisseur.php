<?php
session_start();


// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['id_user'])) {
    header('Location: /login.php'); // Redirection vers la page de connexion si non connecté
    exit();
}

$profil = $_SESSION['profil'];
$idUser = $_SESSION['id_user'];


// Ini
// Dossier où sont stockées les factures
$factureDir = 'c:/xampp/htdocs/CONTROL_FACTURATION_SM/factures/';

// Initialiser une variable pour stocker les factures
$factures = [];

// Vérifier le profil et lister les factures correspondantes
if ($profil == 'regisseurEntree' || $profil == 'regisseurSortie') {
    $factureFiles = scandir($factureDir);

    foreach ($factureFiles as $file) {
        // Vérifier que le fichier est bien une facture (vous pouvez ajouter plus de validations si nécessaire)
        if (is_file($factureDir . $file) && preg_match("/facture_" . $idUser . "_profil_/", $file)) {
            $factures[] = $file;
        }
    }

    // Trier les factures par date (le plus récent en premier)
    usort($factures, function($a, $b) {
        // Extraire les timestamps des fichiers
        preg_match('/_(\d+)\.pdf$/', $a, $aMatches);
        preg_match('/_(\d+)\.pdf$/', $b, $bMatches);

        // Assurez-vous que nous avons bien extrait des timestamps
        $aTime = isset($aMatches[1]) ? (int) $aMatches[1] : 0;
        $bTime = isset($bMatches[1]) ? (int) $bMatches[1] : 0;
        
        return $bTime - $aTime; // Trier du plus récent au plus ancien
    });
} else {
    echo "Accès refusé.";
    exit();
}

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
  

<body>

<?php include('../../head.php'); ?>
<style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
       
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }
        ul {
            list-style-type: none;
            padding: 0;
        }
        .liste {
            padding: 10px;
            border-bottom: 1px solid #ddd;
        }
        .liste a {
            text-decoration: none;
            color: #007bff;
            font-weight: bold;
            display: block;
            transition: color 0.3s;
        }
        .liste a:hover {
            color: #0056b3;
        }
        .form-field {
            text-align: center;
            margin-top: 20px;
        }
        .form-field button {
            padding: 10px 20px;
            background-color: #007bff;
            color: #fff;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .form-field button:hover {
            background-color: #0056b3;
        }
    </style>
<div class="container">
        <h1><strong>Liste des Factures Générées</strong></h1>
    
        <?php if (!empty($factures)): ?>
            <ul>
                <?php foreach ($factures as $facture): ?>
                    <li class="liste"><a href="<?php echo "/CONTROL_FACTURATION_SM/factures/" . $facture; ?>" target="_blank"><?php echo $facture; ?></a></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>Aucune facture générée pour l'instant.</p>
        <?php endif; ?>
    </div>
    <div class="form-field">
        

    <script src="../../assets/js/jquery-3.2.1.min.js"></script>
    <script src="../../assets/bootstrap/js/bootstrap.bundle.min.js"></script>
</body>

</html>

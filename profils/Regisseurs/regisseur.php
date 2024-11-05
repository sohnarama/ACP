<?php
// // Démarre une nouvelle session ou reprend une session existante
session_start();
if (empty($_SESSION['username']) && empty($_SESSION['mdp'])) {
    header('Location: /../../CONTROL_FATURATION0_SM/');
    exit();
}
// // Supprimer une variable de session spécifique
unset($_SESSION['classe']);
// Sélectionnez les options à partir de la base de données avec une pagination
include('../../pdf/fpdf.php');
include('../../traitement/fonction.php');
include('../../traitement/fonctionPdf.php');
//recuperer la liste des service et stocker dans le tableau $service 
$services = array();
$services=GetAllServices();

$userId = $_SESSION['id_user'];
$user = GetUserById($userId); // Fonction pour récupérer les détails de l'utilisateur par ID
if ($user['statut'] == 0) {
    // Si l'utilisateur est inactif, le rediriger ou afficher un message
    echo "Votre compte est désactivé. Veuillez contacter l'administrateur.";
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

    <script>
        function validateForm() {
            var natureOperation = document.getElementById("nature_operation").value;
            var montant = parseFloat(document.getElementById("montant").value);

            if (natureOperation === 'DECAISSEMENT' && montant > 500000) {
                alert('Le montant ne peut pas dépasser 500000 pour un Décaissement.');
                return false; // Empêche l'envoi du formulaire
            }

            return true; // Autorise l'envoi du formulaire
        }

        window.onload = function() {
            document.getElementById("transactionForm").onsubmit = validateForm;
        };
    </script>

</head>

<body>
    <?php
    include('../../head.php');
    // Définir la valeur par défaut du champ nature de l'operation en fonction du profil
    $defaultOption = '';
    if ($_SESSION['profil'] == 'regisseurEntree') {
        $defaultOption = 'Encaissement';
    } elseif ($_SESSION['profil'] == 'regisseurSortie') {
        $defaultOption = 'Decaissement';
    }
    // Convertir en majuscules pour la comparaison
    $defaultOption = strtoupper(trim($defaultOption));

    // Définir les valeurs des options
    $options = [
        'ENCAISSEMENT' => 'Encaissement',
        'DECAISSEMENT' => 'Decaissement',
    ];
    ?>
    <div class="container" style="width:60%;" >
        <div class="contact__form1"  style="width:100%; height:auto" >
            <form class="justify-content-center" method="post" id="transactionForm">
                <tr>
                    <td colspan="4">
                        <center>
                            <strong>VEUILLEZ RENSEIGNER LES CHAMPS</strong>
                        </center>
                    </td>
                </tr>
                <fieldset>
                    <table>
                        <tr>
                            <td>
                                <strong>Service Concerné</strong>
                                <select name="service_concerne" required class="form-select"
                                    style="background-color: rgba(50, 115, 220, 0.1);">
                                    <option value="" disabled selected>Choisir...</option>
                                    <?php foreach ($services as $service): ?>
                                        <option value="<?php echo htmlspecialchars($service['id']); ?>">
                                            <?php echo htmlspecialchars($service['libelle']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Nom complet</strong>
                                <input type="text" name="nom_complet" id="nom_complet" required class="form-control"
                                    placeholder="Entrez le nom complet" style="background-color: rgba(50, 115, 220, 0.1);">
                            </td>
                        </tr>

                        <tr>
                            <td>

                                <!--strong>Nature de l'operation</strong-->
                                <select name="nature_operation" id="nature_operation" required class="form-select"
                                style="display: none;"
                                    style="background-color: rgba(50, 115, 220, 0.1);" >
                                    <?php foreach ($options as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php if ($defaultOption == $value)
                                               echo 'selected'; ?>     <?php if ($defaultOption != $value)
                                                          echo 'hidden'; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select> 

                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>Montant</strong>
                                <input type="number" name="montant" id="montant" required class="form-control">
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <strong>libellé</strong>
                                <textarea name="libelle" required class="form-control" rows="3"
                                    style="height: 90px;font-size:15px;background-color: rgba(50, 115, 220, 0.1);"></textarea>
                            </td>
                        </tr>
                    </table>

                    <div class="form-field">
                    <button type="submit" name="action" value="enregistrer" class="btn--primary"><strong>ENREGISTRER</strong></button>
       
                        <br><br>
                        <center> <a href="javascript:history.back()">Retour</a> </center>
                    </div>

                </fieldset>
            </form>

        </div>
    </div>
    <?php
// process.php

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Récupérer les valeurs du formulaire
    $natureOperation = isset($_POST['nature_operation']) ? strtoupper(trim($_POST['nature_operation'])) : '';
    $montant = isset($_POST['montant']) ? floatval($_POST['montant']) : 0;
    $serviceConcerne = isset($_POST['service_concerne']) ? intval($_POST['service_concerne']) : 0;
    $libelle = isset($_POST['libelle']) ? trim($_POST['libelle']) : '';
    $nomComplet = isset($_POST['nom_complet']) ? trim($_POST['nom_complet']) : ''; // Nouveau champ
    $idUser = $_SESSION['id_user'];
    $profil = $_SESSION['profil'];
    $serviceLibelle = getServiceLibelle($serviceConcerne, $connexion);

    // Validation côté serveur
    if ($natureOperation === 'DECAISSEMENT' && $montant > 500000) {
        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                <strong>Erreur !</strong> Le montant ne peut pas dépasser 500000 pour un Décaissement.
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
              </div>';
    } else {
        // Vérifier si l'opération existe déjà
        if (operationExists($montant, $natureOperation, $serviceConcerne, $connexion)) {
            echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <strong>Erreur !</strong> Cette opération existe déjà.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                  </div>';
        } else {
            // Ajoutez l'opération avec le nom complet
            $id_op = ajouteroperation($montant, $natureOperation, $serviceConcerne, $idUser, $libelle, $nomComplet); // Ajouter le nom complet

            // Générer le PDF de la facture
            $fileUrl = generateInvoice($montant, $natureOperation, $serviceLibelle, $libelle, $profil, $idUser, $id_op,$nomComplet);

            if ($fileUrl) {
                echo '<script type="text/javascript">
                    var pdfWindow = window.open("' . $fileUrl . '", "_blank");
                    pdfWindow.onload = function() {
                        pdfWindow.print();
                    };
                </script>';
            }
        }
    }
}


?>
<script>
    // Fonction pour réinitialiser le formulaire
    function resetForm() {
        document.getElementById("transactionForm").reset();
    }
</script>


    <!-- Optional JavaScript -->
    <!-- jQuery first, then Popper.js, then Bootstrap JS -->
   

    <script src="../../assets/js/jquery-3.2.1.min.js"></script>
    <script src="../../assets/js/plugins.js"></script>
    <script src="../../assets/js/main.js"></script>

</body>
<script src="../../assets/js/script.js"></script>

<?php //include('../../footer.php'); ?>

</html>
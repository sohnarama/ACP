<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Connectez-vous à votre base de données MySQL
function connexionBD()
{
    $connexion = mysqli_connect("localhost", "root", "", "db_recette");
    // Vérifiez la connexion
    if ($connexion === false) {
        die("Erreur : Impossible de se connecter. " . mysqli_connect_error());
    }
    return $connexion;
}
$connexion = connexionBD();

// Fonction de connexion dans l'espace utilisateur
function login($username, $password)
{
    global $connexion;
    $users = "SELECT * FROM `user` where `username`='$username' and `password`='$password'";
    $info = mysqli_query($connexion, $users);
    return $info->fetch_assoc();
}

//recuperer l'operation par l'id
function getOperationById($id) {
    // Connexion à la base de données
   global $connexion ;

    // Préparer la requête pour récupérer l'opération
    $stmt = $connexion->prepare("SELECT * FROM operations WHERE id_op = ?");
    
    // Vérifier si la préparation a réussi
    if ($stmt === false) {
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }

    // Lier les paramètres
    $stmt->bind_param('i', $id);

    // Exécuter la requête
    $stmt->execute();

    // Récupérer le résultat
    $result = $stmt->get_result();

    // Vérifier si une opération a été trouvée
    if ($result->num_rows > 0) {
        $operation = $result->fetch_assoc(); // Récupérer la première ligne
        $stmt->close();
        $connexion->close();
        return $operation; // Retourner l'opération
    } else {
        $stmt->close();
        $connexion->close();
        return null; // Aucune opération trouvée
    }
}

  // fonction de suppression d'une operation 
  function DeleteOperation($id_op) {
    global $connexion;
    // Assurez-vous d'avoir accès à votre connexion PDO
    $stmt = $connexion->prepare("DELETE FROM operations WHERE id_op = ?");
    return $stmt->execute([$id_op]);
}
//AJOUTER UNE OPERATION DANS ARCHIVE
function archive_operations($id_op,  $id_user) {
    global $connexion;
    // Étape 1 : Récupérer l'opération
    $stmt = $connexion->prepare('SELECT * FROM operations WHERE id_op = ?');
    $stmt->bind_param('i', $id_op);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $operation = $result->fetch_assoc();
        
        // Étape 2 : Préparer l'insertion dans archive_operations
        $operation_data = json_encode($operation); // Encodage des données au format JSON
        $date_suppression = date('Y-m-d H:i:s'); // Date actuelle
       
        $stmt = $connexion->prepare('INSERT INTO archive_operations (operation_data, date_suppression, id_user_action) VALUES (?, ?, ?)');
        $stmt->bind_param('ssi', $operation_data, $date_suppression, $id_user);
        
        if ($stmt->execute()) {
            // Étape 3 : Supprimer l'opération
            $stmt = $connexion->prepare('DELETE FROM operations WHERE id_op = ?');
            $stmt->bind_param('i', $id_op);
            $stmt->execute();
            
            return true; // Opération réussie
        } else {
            return false; // Échec de l'insertion
        }
    }
    
    return false; // Aucune opération trouvée
}

//lister les utilisateurs 
function GetAllUsers() {
    global $connexion;

    // Requête pour récupérer tous les utilisateurs
    $sql = "SELECT id, nom_complet , username FROM user"; // Utilisation de `nom_complet` pour afficher le nom complet
    $stmt = $connexion->prepare($sql);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}


//Les attributs de la pagination: Pagination par page de 54 elements
function getAttributByPagination()
{
    global $page, $limit, $offset, $counter;
    $page = isset($_GET['page']) ? $_GET['page'] : 1;
    $limit = 90;
    $offset = ($page - 1) * $limit;
    $counter = 0;
}
function GetAllUser($limit, $page) {
    global $connexion;

    // Calcul de l'offset pour la pagination
    $offset = ($page - 1) * $limit;

    // Requête SQL pour récupérer les utilisateurs avec limite et offset

$sql = "SELECT id, username, nom_complet, profil, statut 
            FROM user
            ORDER BY id DESC 
            LIMIT ? OFFSET ?";
    
    // Préparation de la requête
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('ii', $limit, $offset);
    $stmt->execute();
    
    // Récupération des résultats
    $result = $stmt->get_result();
    
    // Vérification si des utilisateurs sont trouvés
    if ($result->num_rows > 0) {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    } else {
        return false;
    }
}
function GetUserById($userId) {
    global $connexion;

    // Préparer la requête SQL pour récupérer l'utilisateur par son ID
    $sql = "SELECT id, username, nom_complet, profil,statut, password FROM user WHERE id = ?";
    $stmt = $connexion->prepare($sql);

    // Lier l'ID de l'utilisateur à la requête
    $stmt->bind_param('i', $userId);

    // Exécuter la requête
    $stmt->execute();

    // Récupérer le résultat
    $result = $stmt->get_result();

    // Si un utilisateur est trouvé, retourner ses informations
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    } else {
        throw new Exception("Utilisateur non trouvé.");
    }
}

function updateUtilisateur($id, $username, $nom_complet, $motDePasse, $profil) {
    global $connexion;
    // Vérifier si le nom d'utilisateur existe déjà pour un autre utilisateur
    $sqlCheck = "SELECT COUNT(*) AS count FROM user WHERE username = ? AND id != ?";
    $stmtCheck = $connexion->prepare($sqlCheck);

    if ($stmtCheck === false) {
        throw new Exception('Échec de la préparation de la requête : ' . $connexion->error);
    }

    // Lier les paramètres username et id
    $stmtCheck->bind_param('si', $username, $id);
    $stmtCheck->execute();
    $resultCheck = $stmtCheck->get_result();
    $rowCheck = $resultCheck->fetch_assoc();

    // Si le nom d'utilisateur existe déjà pour un autre utilisateur, retourner une erreur
    if ($rowCheck['count'] > 0) {
        throw new Exception("Un autre utilisateur avec ce nom d'utilisateur existe déjà.");
    }

    // Fermer la requête de vérification
    $stmtCheck->close();

    // Préparer la requête de mise à jour
    if ($motDePasse !== null && $motDePasse !== '') {
        // Si un nouveau mot de passe est fourni, le hacher avec SHA-1
       // $motDePasseHashe = sha1($motDePasse);
        $sql = "UPDATE user SET username = ?, nom_complet = ?, password = ?, profil = ? WHERE id = ?";
    } else {
        // Si aucun mot de passe n'est fourni, ne pas mettre à jour le champ password
        $sql = "UPDATE user SET username = ?, nom_complet = ?, profil = ? WHERE id = ?";
    }

    $stmt = $connexion->prepare($sql);

    if ($stmt === false) {
        throw new Exception('Échec de la préparation de la requête : ' . $connexion->error);
    }

    // Lier les paramètres en fonction de la présence du mot de passe
    if ($motDePasse !== null && $motDePasse !== '') {
        $stmt->bind_param('ssssi', $username, $nom_complet, $motDePasse, $profil, $id);
    } else {
        $stmt->bind_param('sssi', $username, $nom_complet, $profil, $id);
    }

    // Exécuter la requête
    if ($stmt->execute() === false) {
        throw new Exception('Échec de l\'exécution de la requête : ' . $stmt->error);
    }

    // Fermer la requête
    $stmt->close();
    header('Location: ../admin/users.php');
    return true;
   
}
// Fonction pour ajouter un utilisateur
function addUser($username, $nom_complet, $password, $profil) {
    global $connexion;

    // Hacher le mot de passe avant de l'insérer dans la base de données
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Préparer la requête SQL pour insérer un nouvel utilisateur
    $sql = "INSERT INTO User (username, nom_complet, password, profil) VALUES (?, ?, ?, ?)";
    $stmt = $connexion->prepare($sql);
    
    // Lier les paramètres
    $stmt->bind_param('ssss', $username, $nom_complet, $password, $profil);

    // Exécuter la requête
    if ($stmt->execute()) {
        return true; // Succès
    } else {
        throw new Exception("Erreur lors de l'ajout de l'utilisateur : " . $stmt->error);
    }
}

function ChangeUserStatus($userId, $newStatus) {
    global $connexion;
    $stmt = $connexion->prepare("UPDATE user SET statut = ? WHERE id = ?");
    $stmt->bind_param('ii', $newStatus, $userId);
    if ($stmt->execute()) {
        // Statut mis à jour avec succès
        header('Location: users.php');
    } else {
        // Gérer l'erreur ici
        echo "Erreur lors de la mise à jour du statut.";
    }
    $stmt->close();
}
function GetUsersBySearch($search) {
    global $connexion;

    // Ajouter les pourcentages pour effectuer une recherche avec LIKE
    $searchTerm = "%" . $search . "%";

    // Requête SQL pour rechercher les utilisateurs par mot-clé
    $sql = "SELECT id, username, nom_complet, profil, statut 
            FROM user
            WHERE username LIKE ? OR nom_complet LIKE ? OR profil LIKE ?
            ORDER BY id DESC";
    
    // Préparation de la requête
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param('sss', $searchTerm, $searchTerm, $searchTerm);
    $stmt->execute();
    
    // Récupération des résultats
    $result = $stmt->get_result();
    
    // Vérification si des utilisateurs sont trouvés
    if ($result->num_rows > 0) {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        return $users;
    } else {
        return false;
    }
}

function GetAllServices()
{
    global $connexion;

// Récupérer la liste des services
$query = "SELECT id, libelle FROM services ORDER BY libelle";
$result = $connexion->query($query);

if (!$result) {
    die('Erreur de requête : ' . $connexion->error);
}

// Récupérer les services sous forme de tableau associatif
$services = [];
while ($row = $result->fetch_assoc()) {
    $services[] = $row;
}
 return $services;
}

//fonction pour ajouter une nouvelle operation dans la base de donnees
function ajouteroperation($montant, $natureOperation, $idService, $id_user, $libelle, $nomComplet)
{
    global $connexion;
    date_default_timezone_set('Africa/Dakar');
    $date = date("Y-n-j H:i:s");

    // Ajout du champ nom_complet dans la requête SQL
    $requeteAjoutOperation = "INSERT INTO `operations` (`montant`, `nature`, `id_service`, `id_user`, `date`, `libelle`, `nom_complet`)
                              VALUES ('$montant', '$natureOperation', '$idService', '$id_user', '$date', '$libelle', '$nomComplet')";

    // Préparation et exécution de la requête
    $requete = $connexion->prepare($requeteAjoutOperation);
    $requete->execute();

    // Récupérer l'ID de la dernière opération insérée
    $id_op = mysqli_insert_id($connexion);

    return $id_op;
}


function operationExists($montant, $natureOperation, $serviceConcerne, $connexion) {
    $query = "SELECT COUNT(*) FROM operations WHERE montant = ? AND nature = ? AND id_service = ?";
    $stmt = $connexion->prepare($query);
    $stmt->bind_param("dsi", $montant, $natureOperation, $serviceConcerne);
    $stmt->execute();
    $stmt->bind_result($query);
    $stmt->fetch();
    $stmt->close();

    return $query > 0; // Retourne vrai si l'opération existe déjà
}
function GetAllOperations($start, $limit) {
    global $connexion;

    // Requête avec jointure pour récupérer le libellé du service et les informations de l'utilisateur
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle, u.username AS user_name
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id
        ORDER BY o.date ASC
        LIMIT ?, ?";

    // Préparer la requête
    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres
        $stmt->bind_param('ii', $start, $limit);

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $operations = array();

        while ($row = $result->fetch_assoc()) {
            $operations[] = $row;
        }

        // Fermer la requête
        $stmt->close();

        return $operations;
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}
/*
function GetsOperationsByDateRange($date_debut, $date_fin, $nature, $start, $limit) {
    global $connexion;
    $query = " SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, s.libelle AS service_libelle,
                       u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
         WHERE date_operation BETWEEN ? AND ? AND nature = ? LIMIT ?, ?";
    $stmt = $connexion->prepare($query);
    $stmt->bind_param("sssii", $date_debut, $date_fin, $nature, $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $operations = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $operations;
}*/
/*
function tOperationsByDateRange($date_debut, $date_fin,$nature,$start, $limit) {
    global $connexion;

    // Sécuriser les entrées pour éviter les injections SQL
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);

    // Requête pour récupérer les opérations entre deux dates
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, s.libelle AS service_libelle,
                       u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users

        WHERE o.date BETWEEN '$date_debut' AND '$date_fin'
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();
    
    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }
    
    return $operations;
}*/
function GetOperationsByDateRange($date_debut, $date_fin, $start, $limit) {
    global $connexion;

    // Sécuriser les entrées pour éviter les injections SQL
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);

    // Requête pour récupérer les opérations entre deux dates
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, s.libelle AS service_libelle,
                       u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users

        WHERE o.date BETWEEN '$date_debut' AND '$date_fin'
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();
    
    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }
    
    return $operations;
}

function CountAllOperations() {
    global $connexion;
    $query = "SELECT COUNT(*) as total FROM operations";
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}

function CountOperationsByDateRange($date_debut, $date_fin) {
    global $connexion;

    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);

    $query = "
        SELECT COUNT(*) as total
        FROM operations
        WHERE date BETWEEN '$date_debut' AND '$date_fin'";
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    return $row['total'];
}


/*
function CounOperationsByDateRange($date_debut, $date_fin, $nature) {
    global $connexion;

    // Sécurisation des variables
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);
   

    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE date BETWEEN '$date_debut' AND '$date_fin' 
        AND nature = '$nature'
       "; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}*/


function CountsOperationsByLogins($date_debut, $date_fin, $nature, $user_id) {
    global $connexion;

    // Sécurisation des variables
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);

    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE date BETWEEN '$date_debut' AND '$date_fin' 
        AND nature = '$nature'
        AND (id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
      "; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}
function CountsOperationsByLoginOnly( $nature, $user_id) {
    global $connexion;

    // Sécurisation des variables
   
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);

    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE  nature = '$nature'
        AND (id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
       "; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}
function CountsOperationByUserAndDateRange($regisseur,$user_id, $date_debut, $date_fin, $nature ,$service) {
    global $connexion;

    // Sécurisation des variables
    $regisseur = mysqli_real_escape_string($connexion, $regisseur);
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);
    $service = mysqli_real_escape_string($connexion, $service);


    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE date BETWEEN '$date_debut' AND '$date_fin' 
        AND nature = '$nature'
        AND (id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
        AND (id_service = '$service' OR '$service' IS NULL)"; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}
function CountsOperationByOnlyDateRange($regisseur,$user_id, $date_debut, $date_fin, $nature) {
    global $connexion;

    // Sécurisation des variables
    $regisseur = mysqli_real_escape_string($connexion, $regisseur);
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);


    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE date BETWEEN '$date_debut' AND '$date_fin' 
        AND nature = '$nature'
        AND (id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
      "; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}
//verifie
function CountsOperationsByDateRange($date_debut, $date_fin, $nature, $user_id, $service_id) {
    global $connexion;

    // Sécurisation des variables
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);
    $service_id = mysqli_real_escape_string($connexion, $service_id);

    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE date BETWEEN '$date_debut' AND '$date_fin' 
        AND nature = '$nature'
        AND (id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
        AND (id_service = '$service_id' OR '$service_id' IS NULL)"; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}
function CountsOperationsByServicesAndLogin( $nature, $user_id, $service_id) {
    global $connexion;

    // Sécurisation des variables
   
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);
    $service_id = mysqli_real_escape_string($connexion, $service_id);

    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE  nature = '$nature'
        AND (id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
        AND (id_service = '$service_id' OR '$service_id' IS NULL)"; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}
function CountsOperationsByServices($date_debut, $date_fin, $nature,  $service_id) {
    global $connexion;

    // Sécurisation des variables
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);
    $service_id = mysqli_real_escape_string($connexion, $service_id);

    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE date BETWEEN '$date_debut' AND '$date_fin' 
        AND nature = '$nature'
        AND (id_service = '$service_id' OR '$service_id' IS NULL)"; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}
function CountsOperationsByDateOnly($date_debut, $date_fin, $nature) {
    global $connexion;

    // Sécurisation des variables
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);

    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE date BETWEEN '$date_debut' AND '$date_fin' 
        AND nature = '$nature'
       "; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}
function GetOperationByDateOnly($date_debut, $date_fin, $nature,  $start, $limit) {
    global $connexion;

    // Sécurisation des variables
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);

    // Requête pour récupérer les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
        WHERE o.date BETWEEN '$date_debut' AND '$date_fin'
        AND o.nature = '$nature'
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }

    return $operations;
}
function CountsOperationsByServicesOnly( $nature,  $service_id) {
    global $connexion;

    // Sécurisation des variables
   
    $nature = mysqli_real_escape_string($connexion, $nature);
    $service_id = mysqli_real_escape_string($connexion, $service_id);

    // Requête pour compter les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT COUNT(*) as total 
        FROM operations WHERE
         nature = '$nature'
        AND (id_service = '$service_id' OR '$service_id' IS NULL)"; 
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}

function GetOperationByServicesOnly( $nature, $service_id, $start, $limit) {
    global $connexion;

    // Sécurisation des variables
    $nature = mysqli_real_escape_string($connexion, $nature);
    $service_id = mysqli_real_escape_string($connexion, $service_id);

    // Requête pour récupérer les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
        WHERE  o.nature = '$nature'
        AND (o.id_service = '$service_id' OR '$service_id' IS NULL)  -- Condition pour le service
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }

    return $operations;
}
function GetOperationByServices($date_debut, $date_fin, $nature, $service_id, $start, $limit) {
    global $connexion;

    // Sécurisation des variables
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);
    $service_id = mysqli_real_escape_string($connexion, $service_id);

    // Requête pour récupérer les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
        WHERE o.date BETWEEN '$date_debut' AND '$date_fin'
        AND o.nature = '$nature'
        AND (o.id_service = '$service_id' OR '$service_id' IS NULL)  -- Condition pour le service
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }

    return $operations;
}
function GetOperationByLogins($date_debut, $date_fin, $nature, $user_id, $start, $limit) {
    global $connexion;

    // Sécurisation des variables
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);

    // Requête pour récupérer les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
        WHERE o.date BETWEEN '$date_debut' AND '$date_fin'
        AND o.nature = '$nature'
        AND (o.id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }

    return $operations;
}
function GetOperationByServicesAndLogin( $nature, $user_id, $service_id, $start, $limit) {
    global $connexion;

    // Sécurisation des variables
   
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);
    $service_id = mysqli_real_escape_string($connexion, $service_id);

    // Requête pour récupérer les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
        AND o.nature = '$nature'
        AND (o.id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
        AND (o.id_service = '$service_id' OR '$service_id' IS NULL)  -- Condition pour le service
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }

    return $operations;
}
function GetOperationByLoginOnly( $nature, $user_id, $start, $limit) {
    global $connexion;

    // Sécurisation des variables
   
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);

    // Requête pour récupérer les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
        WHERE  o.nature = '$nature'
        AND (o.id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }

    return $operations;
}
//verifie
function GetOperationByDateRange($date_debut, $date_fin, $nature, $user_id, $service_id, $start, $limit) {
    global $connexion;

    // Sécurisation des variables
    $date_debut = mysqli_real_escape_string($connexion, $date_debut);
    $date_fin = mysqli_real_escape_string($connexion, $date_fin);
    $nature = mysqli_real_escape_string($connexion, $nature);
    $user_id = mysqli_real_escape_string($connexion, $user_id);
    $service_id = mysqli_real_escape_string($connexion, $service_id);

    // Requête pour récupérer les opérations selon la nature, la plage de dates, l'utilisateur et le service
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
        WHERE o.date BETWEEN '$date_debut' AND '$date_fin'
        AND o.nature = '$nature'
        AND (o.id_user = '$user_id' OR '$user_id' IS NULL)  -- Condition pour l'utilisateur
        AND (o.id_service = '$service_id' OR '$service_id' IS NULL)  -- Condition pour le service
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }

    return $operations;
}
//verifie
function GetOperationsByDateRangeServiceAndLogin($date_debut, $date_fin, $service, $login, $start, $limit) {
    $conn = connexionBD();
    $sql = "SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o 
        JOIN services s ON o.id_service = s.id
                JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
        WHERE date >= ? AND date <= ? AND id_service = ? AND id_user = ? LIMIT ?, ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssii", $date_debut, $date_fin, $service, $login, $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $operations = [];
    while ($row = $result->fetch_assoc()) {
        $operations[] = $row;
    }
    $stmt->close();
    $conn->close();
    return $operations;
}
//verifie
//Cette fonction récupère les opérations dans une plage de dates donnée et pour un service spécifique.
function CountOperationsByDateRangeServiceAndLogin($date_debut, $date_fin, $service, $login) {
    $conn = connexionBD();
    $sql = "SELECT COUNT(*) as total FROM operations WHERE date >= ? AND date <= ? AND id_service = ? AND id_user = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssss", $date_debut, $date_fin, $service, $login);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $conn->close();
    return $result['total'];
}
//verifie
function CountOperationsByDateRangeAndService($date_debut, $date_fin, $service) {
    global $connexion;
   
    // Assurer que $service est un entier ou une chaîne vide
    $service = ($service === '') ? null : (int)$service;

    // Préparer la requête SQL
    $query = "
        SELECT COUNT(*) as total
        FROM operations
        WHERE date BETWEEN ? AND ?
        AND (? IS NULL OR id_service = ?)";

    // Préparer la requête
    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres
        $stmt->bind_param('ssii', $date_debut, $date_fin, $service, $service);

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Fermer la requête
        $stmt->close();

        return $row['total'];
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}

/*function GetsOperationsByDateRangeAndService($date_debut, $date_fin,$nature , $service, $start, $limit) {
    global $connexion;
    $nature = mysqli_real_escape_string($connexion,$nature );

    // Assurer que $start et $limit sont des entiers
    $start = (int)$start;
    $limit = (int)$limit;

    // Assurer que $service est un entier ou une chaîne vide
    $service = ($service === '') ? null : (int)$service;

    // Préparer la requête SQL
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, s.libelle AS service_libelle
        FROM operations o
        JOIN services s ON o.id_service = s.id
        WHERE o.date BETWEEN ? AND ?
        AND (? IS NULL OR o.id_service = ?)
        ORDER BY o.date ASC
        LIMIT ?, ?";

    // Préparer la requête
    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres
        $stmt->bind_param('ssiiii', $date_debut, $date_fin, $service, $service, $start, $limit);

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $operations = array();

        while ($row = $result->fetch_assoc()) {
            $operations[] = $row;
        }

        // Fermer la requête
        $stmt->close();

        return $operations;
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}*/
//verifie
function GetOperationsByDateRangeAndService($date_debut, $date_fin, $service, $start, $limit) {
    global $connexion;

    // Assurer que $start et $limit sont des entiers
    $start = (int)$start;
    $limit = (int)$limit;

    // Assurer que $service est un entier ou une chaîne vide
    $service = ($service === '') ? null : (int)$service;

    // Préparer la requête SQL
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, s.libelle AS service_libelle
        FROM operations o
        JOIN services s ON o.id_service = s.id
        WHERE o.date BETWEEN ? AND ?
        AND (? IS NULL OR o.id_service = ?)
        ORDER BY o.date ASC
        LIMIT ?, ?";

    // Préparer la requête
    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres
        $stmt->bind_param('ssiiii', $date_debut, $date_fin, $service, $service, $start, $limit);

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $operations = array();

        while ($row = $result->fetch_assoc()) {
            $operations[] = $row;
        }

        // Fermer la requête
        $stmt->close();

        return $operations;
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}
/*function CountsOperationsByDateRangeAndLogin($date_debut, $date_fin, $nature ,$login) {
    // Connexion à la base de données
    $conn = connexionBD();
    $nature = mysqli_real_escape_string($conn,$nature );

    // Requête SQL pour compter les opérations par plage de dates et login
    $sql = "SELECT COUNT(*) as total FROM operations WHERE date >= ? AND date <= ? AND id_user = ?";

    // Préparation de la requête pour éviter les injections SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $date_debut, $date_fin, $login);  // "sss" signifie trois chaînes (string)
    
    // Exécuter la requête
    $stmt->execute();
    
    // Obtenir le résultat
    $result = $stmt->get_result()->fetch_assoc();
    
    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner le nombre total d'opérations
    return $result['total'];
}*/
//verifie
function CountOperationsByDateRangeAndLogin($date_debut, $date_fin, $login) {
    // Connexion à la base de données
    $conn = connexionBD();

    // Requête SQL pour compter les opérations par plage de dates et login
    $sql = "SELECT COUNT(*) as total FROM operations WHERE date >= ? AND date <= ? AND id_user = ?";

    // Préparation de la requête pour éviter les injections SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $date_debut, $date_fin, $login);  // "sss" signifie trois chaînes (string)
    
    // Exécuter la requête
    $stmt->execute();
    
    // Obtenir le résultat
    $result = $stmt->get_result()->fetch_assoc();
    
    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner le nombre total d'opérations
    return $result['total'];
}

/*function GetssOperationsByServiceAndLogin($nature,$service, $login, $start, $limit) {
    // Connexion à la base de données
    $conn = connexionBD();
    $nature = mysqli_real_escape_string($conn,$nature );
    // Requête SQL pour récupérer les opérations par service et login avec pagination
    $sql = " SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
         WHERE id_service = ? AND id_user = ? LIMIT ?, ?";

    // Préparation de la requête pour éviter les injections SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $service, $login, $start, $limit);  // "ssii" signifie deux strings et deux integers
    
    // Exécuter la requête
    $stmt->execute();
    
    // Obtenir le résultat
    $result = $stmt->get_result();

    // Stocker les opérations dans un tableau
    $operations = [];
    while ($row = $result->fetch_assoc()) {
        $operations[] = $row;
    }

    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner le tableau des opérations
    return $operations;
}*/
//verifie
function GetOperationsByServiceAndLogin($service, $login, $start, $limit) {
    // Connexion à la base de données
    $conn = connexionBD();

    // Requête SQL pour récupérer les opérations par service et login avec pagination
    $sql = " SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle,
               u.username AS user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
         WHERE id_service = ? AND id_user = ? LIMIT ?, ?";

    // Préparation de la requête pour éviter les injections SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssii", $service, $login, $start, $limit);  // "ssii" signifie deux strings et deux integers
    
    // Exécuter la requête
    $stmt->execute();
    
    // Obtenir le résultat
    $result = $stmt->get_result();

    // Stocker les opérations dans un tableau
    $operations = [];
    while ($row = $result->fetch_assoc()) {
        $operations[] = $row;
    }

    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner le tableau des opérations
    return $operations;
}
/*function CountsOperationsByServiceAndLogin($nature ,$service, $login) {
    // Connexion à la base de données
    $conn = connexionBD();
    $nature = mysqli_real_escape_string($conn,$nature );

    // Requête SQL pour compter les opérations par service et login
    $sql = "SELECT COUNT(*) as total FROM operations WHERE id_service = ? AND id_user = ?";

    // Préparation de la requête pour éviter les injections SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $service, $login);  // "ss" signifie que les deux paramètres sont des chaînes (strings)
    
    // Exécuter la requête
    $stmt->execute();
    
    // Obtenir le résultat
    $result = $stmt->get_result()->fetch_assoc();
    
    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner le nombre total d'opérations
    return $result['total'];
}*/
//verifie
function CountOperationsByServiceAndLogin($service, $login) {
    // Connexion à la base de données
    $conn = connexionBD();

    // Requête SQL pour compter les opérations par service et login
    $sql = "SELECT COUNT(*) as total FROM operations WHERE id_service = ? AND id_user = ?";

    // Préparation de la requête pour éviter les injections SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $service, $login);  // "ss" signifie que les deux paramètres sont des chaînes (strings)
    
    // Exécuter la requête
    $stmt->execute();
    
    // Obtenir le résultat
    $result = $stmt->get_result()->fetch_assoc();
    
    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner le nombre total d'opérations
    return $result['total'];
}
/*function CountsOperationsByLogin($nature,$login) {
    // Connexion à la base de données
    $conn = connexionBD();
    $nature = mysqli_real_escape_string($conn,$nature );

    // Requête SQL pour compter les opérations d'un utilisateur via une jointure avec la table 'user'
    $sql = "
        SELECT COUNT(*) as total
        FROM operations
        INNER JOIN user ON operations.id_user = user.id
        WHERE user.username = ?
    ";
    
    // Préparation de la requête pour éviter les injections SQL
    $stmt = $conn->prepare($sql);
    
    // Lier le paramètre : "s" signifie string pour le paramètre $login
    $stmt->bind_param("s", $login);

    // Exécuter la requête
    $stmt->execute();
    
    // Obtenir le résultat
    $result = $stmt->get_result()->fetch_assoc();
    
    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner le nombre total d'opérations
    return $result['total'];
}*/
//verifie

/*function getssOperationByLogin($nature,$login, $start, $limit) {
    // Connexion à la base de données
    $connexion = connexionBD();
    $nature = mysqli_real_escape_string($connexion,$nature );

    // Préparation de la requête SQL avec une jointure entre les tables 'user' et 'operations'
    $sql = "
    SELECT operations.*, s.libelle AS service_libelle
    FROM operations
    JOIN services s ON operations.id_service = s.id
    INNER JOIN user ON operations.id_user = user.id
    WHERE user.username = ?
    ORDER BY operations.date
    LIMIT ?, ?
";

    // Préparer la requête pour éviter les injections SQL
    $stmt = $connexion->prepare($sql);
    
    // Lier les paramètres : "sii" signifie (string pour login, int pour start, int pour limit)
    $stmt->bind_param("sii", $login, $start, $limit);

    // Exécuter la requête
    $stmt->execute();

    // Obtenir le résultat
    $result = $stmt->get_result();

    // Stocker les opérations dans un tableau
    $operations = array();
    while ($row = $result->fetch_assoc()) {
        $operations[] = $row;
    }

    // Fermer la connexion
    $stmt->close();
    $connexion->close();

    // Retourner le tableau des opérations
    return $operations;
}*/
//verifie


 // la fonction  pour recuperer les operation par date et login 
 /*function getsOperationsByDateRangeAndLogin($date_debut, $date_fin,$nature, $login, $start, $limit) {
    // Connexion à la base de données
    $conn = connexionBD();
    $nature = mysqli_real_escape_string($conn, string: $nature);

    // Requête SQL pour récupérer les opérations par plage de dates et login avec pagination
    $sql = " SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle, u.nom_complet AS user_name
         FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id

                WHERE date >= ? AND date <= ? AND id_user = ? LIMIT ?, ?";

    // Préparation de la requête pour éviter les injections SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $date_debut, $date_fin, $login, $start, $limit);  // "sssii" signifie trois strings et deux integers
    
    // Exécuter la requête
    $stmt->execute();
    
    // Obtenir le résultat
    $result = $stmt->get_result();

    // Stocker les opérations dans un tableau
    $operations = [];
    while ($row = $result->fetch_assoc()) {
        $operations[] = $row;
    }

    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner le tableau des opérations
    return $operations;
}*/
//verifie
function GetOperationsByDateRangeAndLogin($date_debut, $date_fin, $login, $start, $limit) {
    // Connexion à la base de données
    $conn = connexionBD();

    // Requête SQL pour récupérer les opérations par plage de dates et login avec pagination
    $sql = " SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle, u.nom_complet AS user_name
         FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id

                WHERE date >= ? AND date <= ? AND id_user = ? LIMIT ?, ?";

    // Préparation de la requête pour éviter les injections SQL
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssii", $date_debut, $date_fin, $login, $start, $limit);  // "sssii" signifie trois strings et deux integers
    
    // Exécuter la requête
    $stmt->execute();
    
    // Obtenir le résultat
    $result = $stmt->get_result();

    // Stocker les opérations dans un tableau
    $operations = [];
    while ($row = $result->fetch_assoc()) {
        $operations[] = $row;
    }

    // Fermer la connexion
    $stmt->close();
    $conn->close();
    
    // Retourner le tableau des opérations
    return $operations;
}
/*function GetOperationsByDateRangeUserAndService($date_debut, $date_fin, $user_id, $service_id, $start, $limit) {
    global $connexion;

    // Assurer que $start et $limit sont des entiers
    $start = (int)$start;
    $limit = (int)$limit;

    // Assurer que $user_id et $service_id sont des entiers ou null si non fournis
    $user_id = ($user_id === '') ? null : (int)$user_id;
    $service_id = ($service_id === '') ? null : (int)$service_id;

    // Préparer la requête SQL avec jointures pour récupérer les informations de l'utilisateur et du service
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle, u.nom_complet AS user_name
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id
        WHERE o.date BETWEEN ? AND ?
        AND (? IS NULL OR o.id_user = ?)
        AND (? IS NULL OR o.id_service = ?)
        ORDER BY o.date ASC
        LIMIT ?, ?";

    // Préparer la requête
    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres (on utilise les valeurs dynamiques pour user et service)
        $stmt->bind_param('ssiiiiii', $date_debut, $date_fin, $user_id, $user_id, $service_id, $service_id, $start, $limit);

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $operations = array();

        while ($row = $result->fetch_assoc()) {
            $operations[] = $row;
        }

        // Fermer la requête
        $stmt->close();

        return $operations;
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}*/




//Cette fonction compte toutes les opérations avec ou sans filtre par service.
/*function CountAllOperationsByService($service = '') {
    global $connexion;

    // Assurer que $service est un entier ou une chaîne vide
    $service = ($service === '') ? null : (int)$service;

    // Préparer la requête SQL
    $query = "
        SELECT COUNT(*) as total
        FROM operations
        WHERE (? IS NULL OR id_service = ?)";

    // Préparer la requête
    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres
        $stmt->bind_param('ii', $service, $service);

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Fermer la requête
        $stmt->close();

        return $row['total'];
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}*/

//Cette fonction récupère toutes les opérations avec ou sans filtre par service.


/*function GetAllOperationsByService($start, $limit, $service = '') {
    global $connexion;

    // Assurer que $start et $limit sont des entiers
    $start = (int)$start;
    $limit = (int)$limit;

    // Assurer que $service est un entier ou une chaîne vide
    $service = ($service === '') ? null : (int)$service;

    // Préparer la requête SQL
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, s.libelle AS service_libelle
        FROM operations o
        JOIN services s ON o.id_service = s.id
        WHERE (? IS NULL OR o.id_service = ?)
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    // Préparer la requête
    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres
        $stmt->bind_param('ii',  $start, $limit );

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $operations = array();

        while ($row = $result->fetch_assoc()) {
            $operations[] = $row;
        }

        // Fermer la requête
        $stmt->close();

        return $operations;
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}*/

//cette fonction compte le nombre d'opérations dans une plage de dates donnée et pour un service spécifique
/*
function CountOperationsByDateRangeUserAndService($date_debut, $date_fin, $user_id, $service) {
    global $connexion;

    // Assurer que $user_id et $service sont des entiers ou null si non fournis
    $user_id = ($user_id === '') ? null : (int)$user_id;
    $service = ($service === '') ? null : (int)$service;

    // Préparer la requête SQL
    $query = "
        SELECT COUNT(*) as total
        FROM operations
        WHERE date BETWEEN ? AND ?
        AND (? IS NULL OR id_user = ?)
        AND (? IS NULL OR id_service = ?)";

    // Préparer la requête
    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres
        $stmt->bind_param('ssiiii', $date_debut, $date_fin, $user_id, $user_id, $service, $service);

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Fermer la requête
        $stmt->close();

        return $row['total'];
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}*/
//verifie
function CountOperationsByNature($nature) {
    global $connexion;

    // Sécurisation de la variable
    $nature = mysqli_real_escape_string($connexion, string: $nature);

    // Requête pour compter les opérations selon la nature
    $query = "
        SELECT COUNT(*) as total 
        FROM operations 
        WHERE nature = '$nature'";
    
    $result = mysqli_query($connexion, $query);
    $row = mysqli_fetch_assoc($result);
    
    return $row['total'];
}
//verifie
function GetOperationsByNature($nature, $start, $limit) {
    global $connexion;

    // Sécurisation de la variable
    $nature = mysqli_real_escape_string($connexion, $nature);

    // Requête pour récupérer les opérations selon la nature
    $query = "
        SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle, 
               u.username as user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
        WHERE o.nature = '$nature'
        ORDER BY o.date ASC
        LIMIT $start, $limit";

    $result = mysqli_query($connexion, $query);
    $operations = array();

    while ($row = mysqli_fetch_assoc($result)) {
        $operations[] = $row;
    }

    return $operations;
}


//veifie
function CountOperationsByService($service) {
    global $connexion;
    $query = "SELECT COUNT(*)as total FROM operations WHERE id_service = ?";


    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres
        $stmt->bind_param("i", $service);

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Fermer la requête
        $stmt->close();

        return $row['total'];
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}
function CountOperationsByLogs($login) {
    global $connexion;
    $query = "SELECT COUNT(*)as total FROM operations WHERE id_user = ?";


    if ($stmt = $connexion->prepare($query)) {
        // Lier les paramètres
        $stmt->bind_param("s", $login);

        // Exécuter la requête
        $stmt->execute();

        // Obtenir les résultats
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // Fermer la requête
        $stmt->close();

        return $row['total'];
    } else {
        // Gestion des erreurs pour la préparation de la requête
        die("Erreur lors de la préparation de la requête : " . $connexion->error);
    }
}
/*
function GetsOperationsByServices($nature,$service, $start, $limit) {
    global $connexion;
    $nature = mysqli_real_escape_string($connexion, $nature);

    $sql = "SELECT * FROM operations WHERE id_service = ? LIMIT ?, ?";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param("iii", $service, $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $operations = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $operations;
}
*/
//verifie
function GetOperationsByService($service, $start, $limit) {
    global $connexion;
    $sql = "SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle, 
               u.username as user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
         WHERE id_service = ? LIMIT ?, ?";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param("iii", $service, $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $operations = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $operations;
}
function GetOperationsByLogs($login, $start, $limit) {
    global $connexion;
    $sql = "SELECT o.id_op, o.montant, o.nature, o.id_user, o.date, o.libelle, 
               s.libelle AS service_libelle, 
               u.username as user_name, u.nom_complet, u.profil  -- Sélection des champs utilisateur
        FROM operations o
        JOIN services s ON o.id_service = s.id
        JOIN user u ON o.id_user = u.id  -- Jointure avec la table users
         WHERE id_user = ? LIMIT ?, ?";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param("iii", $login, $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $operations = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $operations;
}





function GetOperationsByOnlyDateRange($regisseur, $id_user, $date_debut, $date_fin, $nature, $start, $limit) {
    global $connexion;

    $query = "SELECT operations.id_op, operations.montant, operations.date, operations.libelle, services.libelle as service_libelle
              FROM operations
              JOIN services ON operations.id_service = services.id
              WHERE operations.id_user = ? 
              AND operations.nature = ? 
              AND operations.date BETWEEN ? AND ?";
    $query .= " LIMIT ?, ?";
    $stmt = $connexion->prepare($query);
        $stmt->bind_param("isssii", $id_user, $nature, $date_debut, $date_fin, $start, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    $operations = [];
    while ($row = $result->fetch_assoc()) {
        $operations[] = $row;
    }
    return $operations;
}









function GetOperationsByUserAndDateRange($regisseur, $id_user, $date_debut, $date_fin, $nature, $start, $limit, $service = null) {
    global $connexion;

    $query = "SELECT operations.id_op, operations.montant, operations.date, operations.libelle, services.libelle as service_libelle
              FROM operations
              JOIN services ON operations.id_service = services.id
              WHERE operations.id_user = ? 
              AND operations.nature = ? 
              AND operations.date BETWEEN ? AND ?";
    
    if ($service) {
        $query .= " AND operations.id_service = ?";
    }

    $query .= " LIMIT ?, ?";

    $stmt = $connexion->prepare($query);

    if ($service) {
        $stmt->bind_param("isssiii", $id_user, $nature, $date_debut, $date_fin, $service, $start, $limit);
    } else {
        $stmt->bind_param("isssii", $id_user, $nature, $date_debut, $date_fin, $start, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $operations = [];
    while ($row = $result->fetch_assoc()) {
        $operations[] = $row;
    }

    return $operations;
}






function CountOperationsByUserAndNature($regisseur, $id_user, $nature, $service = null) {
    global $connexion;

    $query = "SELECT COUNT(*) as total 
              FROM operations 
              WHERE id_user = ? 
              AND nature = ?";
    
    if ($service) {
        $query .= " AND id_service = ?";
    }

    $stmt = $connexion->prepare($query);

    if ($service) {
        $stmt->bind_param("isi", $id_user, $nature, $service);
    } else {
        $stmt->bind_param("is", $id_user, $nature);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return $row['total'];
}

function GetOperationsByUserAndNature($regisseur, $id_user, $nature, $start, $limit, $service = null) {
    global $connexion;

    $query = "SELECT operations.id_op, operations.montant, operations.date, operations.libelle, services.libelle as service_libelle
              FROM operations
              JOIN services ON operations.id_service = services.id
              WHERE operations.id_user = ? 
              AND operations.nature = ?";
    
    if ($service) {
        $query .= " AND operations.id_service = ?";
    }

    $query .= " LIMIT ?, ?";

    $stmt = $connexion->prepare($query);

    if ($service) {
        $stmt->bind_param("isiii", $id_user, $nature, $service, $start, $limit);
    } else {
        $stmt->bind_param("isii", $id_user, $nature, $start, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $operations = [];
    while ($row = $result->fetch_assoc()) {
        $operations[] = $row;
    }

    return $operations;
}










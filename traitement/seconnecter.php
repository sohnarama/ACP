

<?php
include('fonction.php');
$error = "";
if (!empty($_GET['username']) && !empty($_GET['password'])) {
    $username = $_GET['username'];
    $password = $_GET['password'];
    $row = login($username, $password);
    if ($row) {
        session_start();
        $_SESSION['id_user'] = $row['id'];
        $_SESSION['username'] = $row['username'];
        $_SESSION['password'] = $row['password'];
        $_SESSION['profil'] = $row['profil'];
        $_SESSION['nom_complet'] = $row['nom_complet'];

        
        if ($row['profil'] == 'regisseurEntree') {
            header('Location: ../profils/Regisseurs/Regisseur.php');
            exit();
        }
        else if ($row['profil'] == 'regisseurSortie') {
            header('Location: ../profils/Regisseurs/Regisseur.php');
            exit();
        }
        else if ($row['profil'] == 'controleur') {
            header('Location: ../profils/controleur/controleur.php');
            exit();
        } else if ($row['profil'] == 'admin') {
            header('Location: ../profils/admin/users.php');
            exit();
        }
       
    } else {
        $error_message = 'Incorrect username or password!';
        $error = "Nom d'utilisateur ou mot de passe Incorrect"; 
         header('location: /CONTROL_FACTURATION_SM  ? error='.$error);
        exit();
    }
}

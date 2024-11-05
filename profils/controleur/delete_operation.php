<?php
session_start();
if (empty($_SESSION['username']) && empty($_SESSION['mdp'])) {
    header('Location: /../../CONTROL_FATURATION0_SM/');
    exit();
}

// Inclusion du fichier des fonctions et connexion à la base de données
include('../../traitement/fonction.php');
if (!function_exists('getOperationById')) {
    echo "La fonction getOperationById n'est pas définie.";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id_op'])) {
    $id_op = (int)$_POST['id_op'];
    $id_user_action= $_SESSION['id_user'];

   if(archive_operations($id_op,$id_user_action)){
    echo $id_op;
   }

}


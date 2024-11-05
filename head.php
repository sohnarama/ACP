<?php
if (empty($_SESSION['username']) && empty($_SESSION['password'])) {
  header('Location: /CONTROL_FACTURATION_SM/');
  exit();
}
$profil = isset($_SESSION['profil']) ? $_SESSION['profil'] : '';

?>

<head>  
  <!--- basic page needs================================================== -->
  <meta charset="utf-8" />
  <title>ACP</title>
  <meta name="description" content="" />
  <meta name="author" content="" />

  <!-- mobile specific metas================================================== -->
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- CSS================================================== -->
  <!-- <link rel="stylesheet" href="assets/css/base.css" /> -->
  <!-- <link rel="stylesheet" href="assets/css/vendor.css" /> -->
  <!-- <link rel="stylesheet" href="assets/css/main.css" /> -->

  <!-- script================================================== -->
  <script src="../assets/js/modernizr.js"></script>
  <script src="assets/js/pace.min.js"></script>

  <!-- favicons================================================== -->
  <link rel="shortcut icon" href="log.gif" type="image/x-icon" />
  <link rel="icon" href="log.gif" type="image/x-icon" />
</head>

<body id="top">
  <!-- header================================================== -->
  <header class="s-header">
    <div class="header-logo">
      <a class="site-logo" href="#"><img src="/CONTROL_FACTURATION_SM/assets/images/logo.png" alt="Homepage" /></a>
      CENTRE DES OEUVRES UNIVERSITAIRE DE DAKAR

    </div>
    <nav class="header-nav-wrap">
      <ul class="header-nav">
      <?php if ($profil === 'regisseurEntree' || $profil === 'regisseurSortie'): ?>
        <li class="nav-item">
            <a href="../../profils/Regisseurs/factureRegisseur.php" class="btn--primary">
                <i class="fa fa-list" aria-hidden="true"></i>Lister les factures</a>
        </li>
        <li class="nav-item">
            <a href="../../profils/Regisseurs/regisseur.php" class="btn--primary">
                <i class="fa fa-plus" aria-hidden="true"></i>Ajouter Opération</a>
        </li>
    <?php endif; ?>
    <?php if ($profil === 'regisseurEntree'): ?>
        <li class="nav-item">
            <a href="../../profils/Regisseurs/encaissement.php" class="btn--primary">
                <i class="fa fa-list" aria-hidden="true"></i>Lister les operations</a>
        </li>
        <?php endif; ?>
        <?php if ($profil === 'regisseurSortie'): ?>
        <li class="nav-item">
            <a href="../../profils/Regisseurs/encaissement.php" class="btn--primary">
                <i class="fa fa-list" aria-hidden="true"></i>Lister les operations</a>
        </li>
        <?php endif; ?>
    <?php if ($profil === 'controleur'): ?>
      <li class="nav-item">
          <a href="controleur.php" class="btn--primary">
              <i class="fa fa-list" aria-hidden="true"></i>Accueil</a>
      </li>
      <li class="nav-item">
            <a href="../../profils/admin/addUser.php" class="btn--primary">
                <i class="fa fa-plus" aria-hidden="true"></i>Ajout Utilisateur</a>
        </li>
      <li class="nav-item">
            <a href="../../profils/admin/users.php" class="btn--primary">
                <i class="fa fa-list" aria-hidden="true"></i>Liste Utilisateurs</a>
        </li>
       
      
        <li class="nav-item">
            <a href="encaissement.php" class="btn--primary">
                <i class="fa fa-list" aria-hidden="true"></i>Encaissement</a>
        </li>
        <li class="nav-item">
            <a href="decaissement.php" class="btn--primary">
                <i class="fa fa-list" aria-hidden="true"></i>Decaissement</a>
        </li>
    <?php endif; ?>
        <li class="nav-item">
          <a class="nav-link" href="/CONTROL_FACTURATION_SM/" title="Déconnexion">
            <i class="fa fa-sign-out" aria-hidden="true"></i>Déconnexion</a>
        </li>
      </ul> 
    </nav>

    <a class="header-menu-toggle" href="#0"><span>Menu</span></a>
    </header>
  <!-- end s-header -->
</body>
<section id="homedesigne" class="s-homedesigne">
    <p class="lead">Espace Administration: Bienvenue! <br> <br> <span>
    (<?= $_SESSION['nom_complet'] . "  " . $_SESSION['profil'] ?>)
    </span><br><br></p>
</section>
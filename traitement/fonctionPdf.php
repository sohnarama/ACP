<?php
header('Content-Type: text/html; charset=utf-8');
require_once('fonction.php');

// Connectez-vous à votre base de données MySQL
$connexion = connexionBD();
$connexion->set_charset("utf8");

function getServiceLibelle($serviceId, $connexion) {
    $sql = "SELECT libelle FROM services WHERE id = ?";
    $stmt = $connexion->prepare($sql);
    $stmt->bind_param("i", $serviceId); // "i" signifie que le paramètre est un entier
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        return $row['libelle'];
    }
    
    return null;
}

// Fonction pour convertir UTF-8 en ISO-8859-1 pour FPDF
function utf8_to_iso8859($text) {
    return iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $text);
}

// Fonction pour convertir un montant en lettre
function convertirMontantEnLettres($nombre) {
    $unités = ["", "un", "deux", "trois", "quatre", "cinq", "six", "sept", "huit", "neuf"];
    $dizaines = ["", "dix", "vingt", "trente", "quarante", "cinquante", "soixante", "soixante-dix", "quatre-vingt", "quatre-vingt-dix"];
    $centaines = ["", "cent", "deux cent", "trois cent", "quatre cent", "cinq cent", "six cent", "sept cent", "huit cent", "neuf cent"];

    if ($nombre == 0) {
        return "zéro";
    }

    if ($nombre < 10) {
        return $unités[$nombre];
    }

    if ($nombre < 100) {
        $dizaine = (int)($nombre / 10);
        $unité = $nombre % 10;
        return $dizaines[$dizaine] . ($unité ? "-" . $unités[$unité] : "");
    }

    if ($nombre < 1000) {
        $centaine = (int)($nombre / 100);
        $reste = $nombre % 100;
        return $centaines[$centaine] . ($reste ? " " . convertirMontantEnLettres($reste) : "");
    }

    if ($nombre < 1000000) {
        $milliers = (int)($nombre / 1000);
        $reste = $nombre % 1000;
        
        if ($milliers == 1) {
            $milliersTexte = "mille";
        } else {
            $milliersTexte = convertirMontantEnLettres($milliers) . " mille";
        }
        
        return $milliersTexte . ($reste ? " " . convertirMontantEnLettres($reste) : "");
    }

    return $nombre;  // Si le nombre est supérieur à 500 000, on retourne simplement le nombre
}




require 'C:\xampp\htdocs\CONTROL_FACTURATION_SM\vendor\autoload.php'; // Chemin correct vers l'autoload de Composer

use Dompdf\Dompdf;

ob_start(); // Démarre le tamponnage de la sortie
function generateInvoice($montant, $natureOperation, $serviceLibelle, $libelle, $profil, $idUser, $idOp, $nomComplet) {
    // Initialiser Dompdf
    $dompdf = new Dompdf();

    // Récupérer la somme en lettres
    $montantEnLettres = convertirMontantEnLettres($montant);

    // Contenu HTML pour le PDF
    $html = '
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; font-size: 7pt; margin: 3mm; }
            h1 { font-size: 8pt; text-align:left; }
            h2 { font-size: 6pt; text-align: left; }
            .total { text-align: center; font-weight: bold; font-size: 7pt; }
            .signature { margin-top: 10px; }
            .border {
                border: 1px solid black;
                padding: 10px;
                margin: 10px 0;
            }
            hr {
                border: 0;
                height: 2px;
                background: #000;
                margin: 5px 0;
                
            }
        </style>
    </head>
    <body>
        <div class="border">
            <h1>QUITTANCE N° ' . sprintf('%06d', $idOp) . '</h1>
            <h2>CENTRE DES ŒUVRES UNIVERSITAIRES DE DAKAR</h2>
            <hr>
            <p>Je soussigné l\'agent comptable particulier du Centre des Œuvres Universitaires reconnais avoir reçu de M. ' . htmlspecialchars($nomComplet) . '
            la somme de ' . htmlspecialchars($montantEnLettres) . ' francs CFA</p>
            <p>Pour : ' . htmlspecialchars($natureOperation) . '</p>
            <p>Service concerné : ' . htmlspecialchars($serviceLibelle) . '</p>
            <p>Dakar le ' . date('d/m/Y') . '</p>
            <p class="total">TOTAL : ' . number_format($montant, 2) . ' FCFA</p>
            <div class="signature">Signature:</div>
        </div>
    </body>
    </html>';

    // Charger le contenu HTML dans Dompdf
    $dompdf->loadHtml($html);

    // (Optional) Configurer le format de papier et l'orientation
    $dompdf->setPaper('A6', 'portrait');

    // Rendre le PDF
    $dompdf->render();

    // Sauvegarde du fichier PDF
    $directoryPath = "c:\\xampp\\htdocs\\CONTROL_FACTURATION_SM\\factures";
    $filePath = $directoryPath . "\\facture_" . $idUser . "_profil_" . $profil . "_" . time() . ".pdf";

    if (!is_dir($directoryPath)) {
        mkdir($directoryPath, 0777, true);
    }

    if (!is_writable(dirname($filePath))) {
        echo 'Erreur : Le répertoire de destination n\'est pas accessible en écriture.';
        return;
    }

    // Générer le fichier PDF
    file_put_contents($filePath, $dompdf->output());

    // Générer le lien vers le fichier PDF
    $fileUrl = str_replace('c:\\xampp\\htdocs', '', $filePath); // Retirer la partie du chemin serveur
    $fileUrl = str_replace('\\', '/', $fileUrl); // Remplacer les backslashes par des slashes

    return $fileUrl; // Retourner le lien du fichier PDF généré
}


/*function generateInvoice($montant, $natureOperation, $serviceLibelle, $libelle, $profil, $idUser, $idOp, $nomComplet) {
    // Initialisation de FPDF avec format A6 (105x148mm)
    $pdf = new FPDF('P', 'mm', array(105, 148)); // Format A6, Portrait
    $pdf->SetMargins(3, 3, 15); // Marge de gauche à 3mm, haut/bas à 3mm, et droite à 15mm
    $pdf->AddPage();
    $traitWidth = 30; // Largeur du petit trait (ajuster si nécessaire)
    $pageWidth = 78; // Largeur de la page A6
    
    // Récupérer la somme en lettres
    $montantEnLettres = convertirMontantEnLettres($montant);
    
    // Calcul de la position X pour centrer le trait
    $xPosition = ($pageWidth - $traitWidth) / 2;
    
    // Utiliser Arial compatible avec UTF-8
    $pdf->SetFont('Arial', '', 7); // Réduire la taille de police générale à 7
    
    // Initialiser la hauteur pour le cadre
    $initialY = $pdf->GetY(); // Position initiale du contenu
    
    // Numéro de quittance
    $pdf->SetFont('Arial', 'B', 8); // Taille de police légèrement plus grande pour le numéro de quittance
    $pdf->Cell(0, 5, utf8_to_iso8859('QUITTANCE N° ' . sprintf('%06d', $idOp)), 0, 1, 'L');
    $pdf->Ln(1); // Espace après la quittance
    
    // Titre du Centre des Œuvres Universitaires
    $pdf->SetFont('Arial', 'B', 6); // Taille de police plus petite pour le titre
    $pdf->Cell(0, 5, utf8_to_iso8859('CENTRE DES ŒUVRES UNIVERSITAIRES DE DAKAR'), 0, 1, 'L');
    $pdf->Ln(1); // Petit espace

    // Positionnement du trait centré sous le texte
    $pdf->SetXY($xPosition, $pdf->GetY());
    $pdf->Cell($traitWidth, 0.5, '', 'T', 0, 'C'); // Petit trait centré
    $pdf->Ln(2); // Espace après le trait

    // Texte principal avec montant en lettres et nom complet
    $pdf->SetFont('Arial', '', 6); // Police plus petite pour le texte principal
    $pdf->MultiCell(0, 4, utf8_to_iso8859("   Je soussigné l'agent comptable particulier du centre des œuvres
    Universitaires soussigné reconnais avoir reçu de M. {$nomComplet} 
    la somme de " . $montantEnLettres . ' francs CFA'), 0, 'L');
    $pdf->Ln(2); // Espace après le texte

    // Ajout des détails
    $pdf->Cell(0, 5, utf8_to_iso8859('Pour : ' . $natureOperation), 0, 1, 'L');
    $pdf->Cell(0, 5, utf8_to_iso8859('Service concerné : ' . $serviceLibelle), 0, 1, 'L');

    // Ajouter la date et le lieu
    $pdf->Ln(1); // Espace avant la date
    $pdf->Cell(0, 5, utf8_to_iso8859('Dakar le ' . date('d/m/Y')), 0, 1, 'L');

    // TOTAL ajusté à droite dans le cadre
    $pdf->SetFont('Arial', 'B', 7); // Augmenter légèrement la taille du texte pour le total
    $pdf->Cell(0, 5, utf8_to_iso8859('TOTAL : ' . number_format($montant, 2) . ' FCFA'), 0, 1, 'C');
    $pdf->Ln(2);

    // Signature
    $pdf->SetFont('Arial', '', 6); // Taille normale pour la signature
    $pdf->Cell(0, 5, utf8_to_iso8859('Signature:'), 3, 1, 'L');

    // Position finale après tout le contenu
    $finalY = $pdf->GetY();

    // Calculer la hauteur du contenu
    $contentHeight = $finalY - $initialY;

    // Cadre autour du contenu, ajusté en fonction de la hauteur du contenu
    $pdf->Rect(3, $initialY - 3, 73, $contentHeight + 6); // Réduire la largeur du cadre

    // Sauvegarde du fichier PDF
    $directoryPath = "c:\\xampp\\htdocs\\CONTROL_FACTURATION_SM\\factures";
    $filePath = $directoryPath . "\\facture_" . $idUser . "_profil_" . $profil . "_" . time() . ".pdf";

    if (!is_dir($directoryPath)) {
        mkdir($directoryPath, 0777, true);
    }

    if (!is_writable(dirname($filePath))) {
        echo 'Erreur : Le répertoire de destination n\'est pas accessible en écriture.';
        return;
    }

    // Générer le fichier PDF
    $pdf->Output('F', $filePath);

    // Générer le lien vers le fichier PDF
    $fileUrl = str_replace('c:\\xampp\\htdocs', '', $filePath); // Retirer la partie du chemin serveur
    $fileUrl = str_replace('\\', '/', $fileUrl); // Remplacer les backslashes par des slashes

    return $fileUrl; // Retourner le lien du fichier PDF généré
}


*/






/*function generateInvoice( $montant, $natureOperation, $serviceLibelle, $libelle, $profil, $idUser,$idOp) {
   // Taille pour un format 1/4 A4
$pdf = new FPDF('P', 'mm', array(105, 148)); // Portrait, mm, A6
$pdf->SetMargins(0, 0, 0);
$pdf->AddPage();

$pageWidth = 105; 
$columnWidthDescription = $pageWidth / 2; // Largeur pour la colonne Description
$columnWidthValeur = $pageWidth / 2; // Largeur pour la colonne Valeur

$pdf->SetFont('Arial', 'B', 12);
$pdf->Cell($pageWidth, 10, 'FACTURE CLIENT', 0, 1, 'C');
$pdf->Ln(2); // Espace après le titre

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell($columnWidthDescription, 6, 'Description', 1);
$pdf->Cell($columnWidthValeur, 6, 'Valeur', 1);
$pdf->Ln();

$pdf->SetFont('Arial', '', 9);
$pdf->Cell($columnWidthDescription, 6, 'Numero Operation', 1);
$pdf->Cell($columnWidthValeur, 6, $idOp, 1);
$pdf->Ln();

$pdf->Cell($columnWidthDescription, 6, 'Montant', 1);
$pdf->Cell($columnWidthValeur, 6, number_format($montant, 2) . ' FCFA', 1);
$pdf->Ln();

$pdf->Cell($columnWidthDescription, 6, 'Nature', 1);
$pdf->Cell($columnWidthValeur, 6, $natureOperation, 1);
$pdf->Ln();

$pdf->Cell($columnWidthDescription, 6, 'Service Concerne', 1);
$pdf->Cell($columnWidthValeur, 6, $serviceLibelle, 1);
$pdf->Ln();

$pdf->Cell($columnWidthDescription, 6, 'Libelle', 1);
$pdf->Cell($columnWidthValeur, 6, $libelle, 1);
$pdf->Ln();
    
    // Enregistrer le fichier PDF avec un schéma de nommage qui inclut l'ID et le profil
    $directoryPath = "c:\\xampp\\htdocs\\CONTROL_FACTURATION_SM\\factures";
    $filePath = $directoryPath . "\\facture_" . $idUser . "_profil_" . $profil . "_" . time() . ".pdf";
    
    if (!is_dir($directoryPath)) {
        mkdir($directoryPath, 0777, true);
    }
    
    if (!is_writable(dirname($filePath))) {
        echo 'Erreur : Le répertoire de destination n\'est pas accessible en écriture.';
        return;
    }
    
    $pdf->Output('F', $filePath);
    
    // Générer le lien vers le fichier PDF
    $fileUrl = str_replace('c:\\xampp\\htdocs', '', $filePath); // Retirer la partie du chemin serveur
    $fileUrl = str_replace('\\', '/', $fileUrl); // Remplacer les backslashes par des slashes
    // Créer un lien HTML vers le fichier PDF
   // echo '<a href="' . $fileUrl . '" target="_blank">Télécharger la facture</a>';

   // Toujours utiliser exit après une redirection avec header
 return $fileUrl;// Retourner le chemin du fichier PDF généré
}*/







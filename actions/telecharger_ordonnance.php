<?php
// actions/telecharger_ordonnance.php
session_start();
require_once '../config/db.php';
require_once '../libs/fpdf.php'; 

// Feinte pour calmer l'analyseur de VS Code (Intelephense)
if (!class_exists('FPDF')) {
    class FPDF {
        function AddPage($orientation='', $size='', $rotation=0){}
        function SetFont($family, $style='', $size=0){}
        function SetTextColor($r, $g=null, $b=null){}
        function Cell($w, $h=0, $txt='', $border=0, $ln=0, $align='', $fill=false, $link=''){}
        function Line($x1, $y1, $x2, $y2){}
        function Ln($h=null){}
        function MultiCell($w, $h, $txt, $border=0, $align='J', $fill=false){}
        function Output($dest='', $name='', $isUTF8=false){}
    }
}
if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/connexion.php");
    exit();
}

$id_rdv = isset($_GET['id_rdv']) ? (int)$_GET['id_rdv'] : 0;
$user_id = $_SESSION['user_id'];

$stmt = $pdo->prepare("SELECT c.*, r.date_rdv, 
                              u_pat.nom AS pat_nom, u_pat.prenom AS pat_prenom,
                              u_med.nom AS med_nom, u_med.prenom AS med_prenom, m.specialite, m.numero_ordre
                       FROM consultations c
                       JOIN rendez_vous r ON c.id_rdv = r.id
                       JOIN users u_pat ON r.id_patient = u_pat.id
                       JOIN users u_med ON r.id_medecin = u_med.id
                       JOIN medecins m ON u_med.id = m.id
                       WHERE c.id_rdv = ? AND (r.id_patient = ? OR r.id_medecin = ?)");
$stmt->execute([$id_rdv, $user_id, $user_id]);
$data = $stmt->fetch();

if (!$data) {
    die("Document médical confidentiel inaccessible.");
}

function toISO($text) {
    return mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');
}

// Au lieu du bloc précédent, mettez ceci à la ligne 36-37 :
/** @var \FPDF $pdf */
$pdf = new \FPDF('P', 'mm', 'A5');
$pdf->AddPage();
$pdf->SetFont('Arial', 'B', 14);

// En-tête Médical
$pdf->SetTextColor(0, 102, 204); 
$pdf->Cell(0, 8, "Dr. " . toISO($data['med_prenom'] . " " . $data['med_nom']), 0, 1, 'L');
$pdf->SetFont('Arial', 'I', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, toISO("Spécialité : " . $data['specialite']), 0, 1, 'L');
$pdf->Cell(0, 5, toISO("N° Ordre CNOM : " . $data['numero_ordre']), 0, 1, 'L');

$pdf->Line(10, 32, 138, 32); 
$pdf->Ln(10);

// Infos Patient
$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(0, 5, "Kinshasa, le : " . date('d/m/Y', strtotime($data['date_rdv'])), 0, 1, 'R');
$pdf->Ln(5);

$pdf->SetFont('Arial', 'B', 11);
$pdf->Cell(0, 6, "Patient : " . toISO($data['pat_prenom'] . " " . $data['pat_nom']), 0, 1, 'L');
$pdf->Ln(8);

// Corps
$pdf->SetFont('Arial', 'B', 14);
$pdf->SetTextColor(204, 0, 0); 
$pdf->Cell(0, 8, "Rp /", 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Arial', '', 11);
$pdf->SetTextColor(0, 0, 0);
$pdf->MultiCell(0, 6, toISO($data['ordonnance']), 0, 'L');

$pdf->Ln(20);

// Pied de page
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(128, 128, 128);
$pdf->Cell(0, 4, toISO("Document généré électroniquement sur la plateforme sécurisée TelMed RDC."), 0, 1, 'C');
$pdf->Cell(0, 4, "Code de validation : TM-" . $data['id_rdv'] . "-" . strtotime($data['date_rdv']), 0, 1, 'C');

$pdf->Output('I', 'Ordonnance_TelMed_' . $data['id_rdv'] . '.pdf');
?>
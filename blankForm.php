<?php
// Prevent buffered whitespace issues
if (ob_get_length()) ob_end_clean();

// Check FPDF path
if (file_exists('../fpdf/fpdf.php')) {
    require_once '../fpdf/fpdf.php';
} elseif (file_exists('fpdf/fpdf.php')) {
    require_once 'fpdf/fpdf.php';
} else {
    die("Error: FPDF library not found in ../fpdf/fpdf.php");
}

function getWeekendDayName($date) {
    if (empty($date)) return '';
    $dayOfWeek = date('w', strtotime($date));
    if ($dayOfWeek == 6) return 'Saturday';
    if ($dayOfWeek == 0) return 'Sunday';
    return '';
}

function isWeekend($date) {
    if (empty($date)) return false;
    $dayOfWeek = date('w', strtotime($date));
    return ($dayOfWeek == 6 || $dayOfWeek == 0);
}

function getBlankFormSignatory($empName, $empDept) {
    $cleanEmpName = strtoupper(trim((string)$empName));
    $empDeptUpper = strtoupper(trim((string)$empDept));

    $sectionHeads = [
        'MA. DOLORES C. BALAGAT', 'JEANELYN D. BASTIAN', 'JEANLYN D. BASTIAN',
        'BENJAMIN I. NGALAWEN', 'NARDA A. GARCIA', 'NARDA A.GARCIA',
        'ELIZABETH B. ANTOLIN', 'EVELYN S. WALES', 'KATLYN S. FELIPE', 'EMERITA B. ALBAS'
    ];

    if (strpos($cleanEmpName, 'LEANDRO') !== false && strpos($cleanEmpName, 'JESUS') !== false) {
        return ['name' => 'ENGR. PAQUITO T. MORENO JR.', 'title' => 'REGIONAL EXECUTIVE DIRECTOR'];
    }

    foreach ($sectionHeads as $head) {
        if (strpos($cleanEmpName, $head) !== false) {
            return ['name' => 'LEANDRO L. DE JESUS', 'title' => 'CENR Officer'];
        }
    }

    if (strpos($empDeptUpper, 'RPS') !== false) return ['name' => 'MA. DOLORES C. BALAGAT', 'title' => 'RPS Chief'];
    if (strpos($empDeptUpper, 'CDS') !== false) return ['name' => 'JEANELYN D. BASTIAN', 'title' => 'CDS Chief'];
    if (strpos($empDeptUpper, 'EMS') !== false) return ['name' => 'BENJAMIN I. NGALAWEN', 'title' => 'EMS Chief'];
    if (strpos($empDeptUpper, 'PSU') !== false) return ['name' => 'NARDA A. GARCIA', 'title' => 'PSU Chief / DMO IV'];
    if (strpos($empDeptUpper, 'MHWFR') !== false) return ['name' => 'ELIZABETH B. ANTOLIN', 'title' => 'PASu-MHWFR'];
    if (strpos($empDeptUpper, 'LAWFR') !== false) return ['name' => 'EVELYN S. WALES', 'title' => 'SrEMS/PASu-LAWFR'];
    if (strpos($empDeptUpper, 'UARBRR') !== false) return ['name' => 'KATLYN S. FELIPE', 'title' => 'PASu-UARBRR'];
    if (strpos($empDeptUpper, 'MPPL') !== false) return ['name' => 'EMERITA B. ALBAS', 'title' => 'PASu-MPPL'];

    return ['name' => 'LEANDRO L. DE JESUS', 'title' => 'CENR Officer'];
}

// Extract POST or GET variables
$emp_Id     = !empty($_REQUEST['empId']) ? trim($_REQUEST['empId']) : '--';
$emp_Name   = !empty($_REQUEST['empName']) ? strtoupper(trim($_REQUEST['empName'])) : '';
$emp_Dept   = !empty($_REQUEST['empDept']) ? strtoupper(trim($_REQUEST['empDept'])) : '';

$start_raw  = !empty($_REQUEST['startDate']) ? $_REQUEST['startDate'] : date('Y-m-01');
$end_raw    = !empty($_REQUEST['endDate']) ? $_REQUEST['endDate'] : date('Y-m-t');

$start_Date = date('m/d/Y', strtotime($start_raw));
$end_Date   = date('m/d/Y', strtotime($end_raw));

$pdf = new FPDF('P', 'mm', 'Legal');
$pdf->SetMargins(10, 8, 10);
$pdf->SetAutoPageBreak(false);
$pdf->AddPage();

// Document Header Title
$pdf->SetFont('Times', 'B', 16);
$pdf->Cell(0, 6, 'CENRO-BAGUIO ATTENDANCE MONITORING SYSTEM', 0, 1, 'C');
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(0, 5, 'BIOMETRIC LOCATION: CENRO BAGUIO', 0, 1, 'C');

$pdf->Cell(0, 5, 'FOR THE PERIOD: ' . strtoupper(date('m/d/Y', strtotime($start_raw))) . ' TO ' . strtoupper(date('m/d/Y', strtotime($end_raw))), 0, 1, 'C');
$pdf->Ln(4);

// EMPLOYEE DETAILS
$pdf->SetFont('Times', 'B', 11);
$pdf->Cell(20, 6, 'ID NO. :', 0, 0, 'L');
$pdf->SetFont('Times', 'U', 11);
$pdf->Cell(80, 6, $emp_Id, 0, 0, 'L');

$pdf->SetFont('Times', 'B', 11);
$pdf->Cell(35, 6, 'DEPARTMENT :', 0, 0, 'R');
$pdf->SetFont('Times', 'U', 11);
$pdf->Cell(0, 6, $emp_Dept, 0, 1, 'L');

$pdf->SetFont('Times', 'B', 11);
$pdf->Cell(20, 6, 'NAME :', 0, 0, 'L');
$pdf->SetFont('Times', 'U', 11);
$pdf->Cell(0, 6, $emp_Name, 0, 1, 'L');
$pdf->Ln(4);

// TABLE HEADER LAYOUT
$pdf->SetFont('Times', 'B', 8);

$pdf->Cell(10, 4, '', 'TLR', 0, 'C');
$pdf->Cell(12, 4, '', 'TLR', 0, 'C');
$pdf->Cell(20, 4, '', 'TLR', 0, 'C');
$pdf->Cell(40, 4, 'AM', 1, 0, 'C');
$pdf->Cell(40, 4, 'PM', 1, 0, 'C');
$pdf->Cell(53, 4, '', 'TLR', 0, 'C');
$pdf->Cell(20, 4, '', 'TLR', 1, 'C');

$pdf->Cell(10, 5, 'NO.', 'BLR', 0, 'C');
$pdf->Cell(12, 5, 'DAY', 'BLR', 0, 'C');
$pdf->Cell(20, 5, 'DATE', 'BLR', 0, 'C');
$pdf->Cell(20, 5, 'TIME IN', 1, 0, 'C');
$pdf->Cell(20, 5, 'TIME OUT', 1, 0, 'C');
$pdf->Cell(20, 5, 'TIME IN', 1, 0, 'C');
$pdf->Cell(20, 5, 'TIME OUT', 1, 0, 'C');
$pdf->Cell(53, 5, 'REMARKS', 'BLR', 0, 'C');
$pdf->Cell(20, 5, 'TOTAL', 'BLR', 1, 'C');

// BLANK DTR TABLE ROWS
$count = 1;
$interval = new DateInterval('P1D');
$realEnd  = new DateTime($end_Date);
$realEnd->add($interval);

$period = new DatePeriod(new DateTime($start_Date), $interval, $realEnd);
$rowHeight = 5.5;

foreach ($period as $datess) {
    $toDate   = $datess->format('m/d/Y');
    $dayShort = strtoupper($datess->format('D'));

    $pdf->SetFont('Times', '', 8);
    $pdf->Cell(10, $rowHeight, $count, 1, 0, 'C');
    $pdf->Cell(12, $rowHeight, $dayShort, 1, 0, 'C');
    $pdf->Cell(20, $rowHeight, $toDate, 1, 0, 'C');
    
    if (isWeekend($toDate)) {
        $weekendName = strtoupper(getWeekendDayName($toDate));
        $pdf->Cell(20, $rowHeight, $weekendName, 1, 0, 'C');
        $pdf->Cell(20, $rowHeight, '', 1, 0, 'C');
        $pdf->Cell(20, $rowHeight, '', 1, 0, 'C');
        $pdf->Cell(20, $rowHeight, '', 1, 0, 'C');
    } else {
        $pdf->Cell(20, $rowHeight, '', 1, 0, 'C');
        $pdf->Cell(20, $rowHeight, '', 1, 0, 'C');
        $pdf->Cell(20, $rowHeight, '', 1, 0, 'C');
        $pdf->Cell(20, $rowHeight, '', 1, 0, 'C');
    }
    
    $pdf->Cell(53, $rowHeight, '', 1, 0, 'L');
    $pdf->Cell(20, $rowHeight, '', 1, 1, 'C');

    $count++;
}

date_default_timezone_set('Asia/Manila');
$dates = date('l m-d-y h:i:s');

// TOTAL DURATION
$pdf->Ln(3);
$pdf->SetFont('Times', 'BI', 11);
$pdf->Cell(0, 6, 'TOTAL DURATION :  _______ hours _______ minutes', 0, 1, 'R');

$pdf->Ln(6);
$pdf->SetFont('Times', '', 9);

$sigData           = getBlankFormSignatory($emp_Name, $emp_Dept);
$deptHeadName      = $sigData['name'];
$deptHeadPosition1 = $sigData['title'];

$yCert = $pdf->GetY();

$pdf->SetXY(10, $yCert);
$pdf->MultiCell(90, 4, "I HEREBY CERTIFY THAT ABOVE RECORDS ARE TRUE AND CORRECT.", 0, 'C');

$pdf->SetXY(105, $yCert);
$pdf->MultiCell(90, 4, "VERIFIED AND FOUND CORRECT AS PRESCRIBED OFFICE HOURS.", 0, 'C');

$pdf->Ln(12);

$ySig = $pdf->GetY();

$pdf->SetXY(15, $ySig);
$pdf->Cell(80, 0, '', 'T');
$pdf->SetXY(15, $ySig + 2);
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(80, 4, $emp_Name, 0, 1, 'C');
$pdf->SetFont('Times', '', 9);
$pdf->SetX(15);
$pdf->Cell(80, 4, 'Employee Signature', 0, 0, 'C');

$pdf->SetXY(110, $ySig);
$pdf->Cell(80, 0, '', 'T');
$pdf->SetXY(110, $ySig + 2);
$pdf->SetFont('Times', 'B', 10);
$pdf->Cell(80, 4, $deptHeadName, 0, 1, 'C');
$pdf->SetFont('Times', '', 9);
$pdf->SetX(110);
$pdf->Cell(80, 4, $deptHeadPosition1, 0, 0, 'C');

$pdf->Ln(8);
$pdf->SetFont('Times', 'I', 7);
$pdf->Cell(0, 4, 'Printing Date : ' . $dates, 0, 0, 'L');

$pdf->Output('I', 'Blank_Attendance_Form.pdf');
exit;
?>
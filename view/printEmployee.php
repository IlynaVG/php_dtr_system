<?php
require_once '../controller/database.php';

// Safe AuthGuard Include
if (file_exists('../controller/authGuard.php')) {
    require_once '../controller/authGuard.php';
} elseif (file_exists('controller/authGuard.php')) {
    require_once 'controller/authGuard.php';
}

// Prevent output buffering issues
if (ob_get_length()) ob_end_clean();

$isAll     = isset($_GET['all']) && $_GET['all'] == '1';
$emp_param = isset($_GET['id']) ? trim($_GET['id']) : (isset($_GET['print_emp']) ? trim($_GET['print_emp']) : '');
$dept      = isset($_GET['dept']) ? trim($_GET['dept']) : (isset($_GET['print_dept']) ? trim($_GET['print_dept']) : '');

// Dynamic Auto-Detection of Active Month and Year from Database Logs
$defaultMonth = (int)date('m');
$defaultYear  = (int)date('Y');

$latestLogRes = $conn->query("SELECT MAX(date_info) AS latest_date FROM info_tbl WHERE date_info IS NOT NULL AND date_info != ''");
if (!$latestLogRes || $latestLogRes->num_rows == 0) {
    $latestLogRes = $conn->query("SELECT MAX(logs_checktime) AS latest_date FROM attendancelogs WHERE logs_checktime IS NOT NULL AND logs_checktime != ''");
}

if ($latestLogRes && $latestRow = $latestLogRes->fetch_assoc()) {
    if (!empty($latestRow['latest_date'])) {
        $dtLatest     = date_create($latestRow['latest_date']);
        if ($dtLatest) {
            $defaultMonth = (int)$dtLatest->format('m');
            $defaultYear  = (int)$dtLatest->format('Y');
        }
    }
}

$month = isset($_GET['month']) ? (int)$_GET['month'] : $defaultMonth;
$year  = isset($_GET['year'])  ? (int)$_GET['year']  : $defaultYear;

$monthName   = strtoupper(date('F', mktime(0, 0, 0, $month, 10)));
$daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

// Build employee list for single or bulk print
$employees = [];

if ($isAll) {
    if (!empty($dept)) {
        $empStmt = $conn->prepare("SELECT * FROM emp_tbl WHERE emp_dept LIKE CONCAT('%', ?, '%') ORDER BY emp_name ASC");
        $empStmt->bind_param("s", $dept);
    } else {
        $empStmt = $conn->prepare("SELECT * FROM emp_tbl ORDER BY emp_name ASC");
    }
    $empStmt->execute();
    $empRes = $empStmt->get_result();
    while ($row = $empRes->fetch_assoc()) {
        $employees[] = $row;
    }
} else {
    if (!empty($emp_param)) {
        $stmt = $conn->prepare("SELECT * FROM emp_tbl WHERE emp_id = ? OR emp_name = ? LIMIT 1");
        $stmt->bind_param("ss", $emp_param, $emp_param);
        $stmt->execute();
        $res = $stmt->get_result();
        $empRow = $res->fetch_assoc();
        if ($empRow) {
            $employees[] = $empRow;
        }
    }
}

// Fallback if list is empty
if (empty($employees)) {
    $isNumericParam = is_numeric($emp_param);

    $employees[] = [
        'emp_id'       => $isNumericParam ? $emp_param : '11001',
        'emp_name'     => !$isNumericParam && !empty($emp_param) ? strtoupper($emp_param) : 'ABRATIQUE, JUDE M.',
        'emp_position' => 'FOREST RANGER',
        'emp_dept'     => !empty($dept) ? $dept : 'RPS'
    ];
}

// Pre-fetch biometric raw logs
$rawPunches = [];
$rawLogsRes = $conn->query("SELECT * FROM info_tbl ORDER BY date_info ASC");
if (!$rawLogsRes) {
    $rawLogsRes = $conn->query("SELECT * FROM attendancelogs ORDER BY logs_checktime ASC");
}

if ($rawLogsRes && $rawLogsRes->num_rows > 0) {
    while ($r = $rawLogsRes->fetch_assoc()) {
        $checktime = isset($r['logs_checktime']) ? $r['logs_checktime'] : (isset($r['date_info']) ? $r['date_info'] : '');
        if (empty($checktime) || $checktime === '0000-00-00 00:00:00') continue;

        $dt = date_create($checktime);
        if (!$dt) continue;

        $m = (int)$dt->format('m');
        $d = (int)$dt->format('j');
        $y = (int)$dt->format('Y');

        if ($m === $month && $y === $year) {
            $empKey = !empty($r['emp_id']) ? $r['emp_id'] : (!empty($r['logs_id']) ? $r['logs_id'] : (isset($r['logs_name']) ? $r['logs_name'] : ''));
            $rawPunches[$empKey][$d][] = [
                'firstIn'  => isset($r['firstTimeIn']) ? $r['firstTimeIn'] : '',
                'firstOut' => isset($r['firstTimeOut']) ? $r['firstTimeOut'] : '',
                'secondIn' => isset($r['secondTimeIn']) ? $r['secondTimeIn'] : '',
                'secondOut'=> isset($r['secondTimeOut']) ? $r['secondTimeOut'] : '',
                'raw'      => $dt->format('H:i:s')
            ];
        }
    }
}

// Helper function to clamp timestamps strictly between 07:00:00 AM and 07:00:00 PM (19:00:00)
function getCappedTimestamp($timeStr, $dateStr) {
    if (!$timeStr || $timeStr === '00:00:00') return 0;
    
    $ts = strtotime($dateStr . ' ' . $timeStr);
    $sevenAM = strtotime($dateStr . ' 07:00:00');
    $sevenPM = strtotime($dateStr . ' 19:00:00');

    if ($ts < $sevenAM) $ts = $sevenAM;
    if ($ts > $sevenPM) $ts = $sevenPM;

    return $ts;
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>DTR - <?php echo $isAll ? "PRINT ALL ($monthName $year)" : htmlspecialchars($employees[0]['emp_name']); ?></title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
    <style>
        body { background: #ffffff !important; color: #000000 !important; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .dtr-page { max-width: 800px; margin: 0 auto 50px auto; padding: 10px; page-break-after: always; }
        .dtr-page:last-child { page-break-after: avoid; }
        .title-header { text-align: center; font-weight: bold; margin-bottom: 20px; }
        .title-header h4 { font-size: 18px; font-weight: 900; margin: 0; letter-spacing: 0.5px; }
        .meta-table { width: 100%; font-size: 13px; font-weight: bold; margin-bottom: 12px; }
        .meta-table td { padding: 2px 0; }
        .dtr-table { width: 100%; border-collapse: collapse; font-size: 10px; font-weight: bold; margin-bottom: 10px; }
        .dtr-table th, .dtr-table td { border: 1px solid #000000 !important; text-align: center; padding: 3px 2px; }
        .dtr-table th { font-size: 9px; text-transform: uppercase; background-color: #ffffff; }
        .duration-row { text-align: right; font-style: italic; font-weight: bold; font-size: 13px; margin-top: 10px; margin-bottom: 35px; }
        .cert-section { display: flex; justify-content: space-between; margin-top: 40px; font-family: Arial, sans-serif; }
        .cert-box { width: 45%; text-align: center; }
        .cert-text { font-size: 9px; font-weight: bold; text-transform: uppercase; line-height: 1.2; min-height: 30px; }
        .signature-line { border-bottom: 1.5px solid #000; margin-top: 35px; margin-bottom: 3px; }
        .signatory-name { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        .signatory-title { font-size: 13px; font-weight: bold; text-transform: uppercase; }
        @media print { .no-print { display: none !important; } body { padding: 0; } .dtr-page { max-width: 100%; padding: 0; margin-bottom: 0; } }
    </style>
</head>
<body>

    <div class="no-print text-center mb-4">
        <button onclick="window.print()" class="btn btn-primary font-weight-bold px-4">🖨️ <?php echo $isAll ? 'PRINT ALL DTRs' : 'PRINT DTR'; ?></button>
        <a href="viewEmployee.php" class="btn btn-secondary font-weight-bold ml-2">BACK TO MASTERLIST</a>
    </div>

    <?php foreach ($employees as $employee): 
        $emp_id       = $employee['emp_id'];
        $emp_name     = $employee['emp_name'];
        $emp_position = !empty($employee['emp_position']) ? $employee['emp_position'] : 'FOREST RANGER';
        $emp_dept     = !empty($employee['emp_dept']) ? $employee['emp_dept'] : 'RPS';

        // Signatories mapping
        $signatories = [
            'EMS'          => ['name' => 'BENJAMIN I. NGALAWEN', 'title' => 'EMS CHIEF'],
            'PSU'          => ['name' => 'NARDA A. GARCIA',      'title' => 'PLANNING SUPPORT UNIT'],
            'RPS'          => ['name' => 'MA. DOLORES C. BALAGAT','title' => 'RPS CHIEF'],
            'CDS'          => ['name' => 'JEANELYN D. BASTIAN',  'title' => 'CDS CHIEF'],
            'PASU(MARCOS)' => ['name' => 'ELIZABETH B. ANTOLIN', 'title' => 'PA MHWFR'],
            'PASU(UPPER)'  => ['name' => 'KATLYN S. FELIPE',     'title' => 'PA UARBRR'],
            'PASU(LOWER)'  => ['name' => 'EVELYN S. WALES',      'title' => 'PA LAWFR'],
            'MPPL'         => ['name' => 'EMERITA B. ALBAS',     'title' => 'PASu-MPPL'],
            'MHWFR'        => ['name' => 'ELIZABETH B. ANTOLIN', 'title' => 'PASu-MHWFR'],
            'LAWFR'        => ['name' => 'EVELYN S. WALES',      'title' => 'SvEMS/ PASu-LAWFR'],
            'UARBRR'       => ['name' => 'KATLYN S. FELIPE',     'title' => 'PASu-UARBRR']
        ];

        $sectionHeads = [
            'MA. DOLORES C. BALAGAT',
            'JEANELYN D. BASTIAN',
            'JEANLYN D. BASTIAN',
            'BENJAMIN I. NGALAWEN',
            'NARDA A. GARCIA',
            'NARDA A.GARCIA',
            'ELIZABETH B. ANTOLIN',
            'EVELYN S. WALES',
            'KATLYN S. FELIPE',
            'EMERITA B. ALBAS'
        ];

        $cleanEmpName = strtoupper(trim($emp_name));
        $deptKey      = strtoupper(trim($emp_dept));

        // Signatory Hierarchy Logic
        if (strpos($cleanEmpName, 'LEANDRO') !== false && strpos($cleanEmpName, 'JESUS') !== false) {
            $supervisor_name  = 'ENGR. PAQUITO T. MORENO JR.';
            $supervisor_title = 'REGIONAL EXECUTIVE DIRECTOR';
        } else {
            $isDeptHead = false;
            foreach ($sectionHeads as $head) {
                $headParts = explode(' ', $head);
                $empParts  = explode(' ', $cleanEmpName);
                if (end($empParts) === end($headParts) || strpos($cleanEmpName, $head) !== false) {
                    $isDeptHead = true;
                    break;
                }
            }

            if ($isDeptHead || strpos(strtoupper($emp_position), 'CHIEF') !== false || $deptKey === 'HEADS') {
                $supervisor_name  = 'LEANDRO L. DE JESUS';
                $supervisor_title = 'CENR OFFICER';
            } else {
                $supervisor_name  = isset($signatories[$deptKey]) ? $signatories[$deptKey]['name'] : 'LEANDRO L. DE JESUS';
                $supervisor_title = isset($signatories[$deptKey]) ? $signatories[$deptKey]['title'] : 'CENR OFFICER';
            }
        }

        $dailyLogs = [];
        $empLogs = isset($rawPunches[$emp_id]) ? $rawPunches[$emp_id] : (isset($rawPunches[$emp_name]) ? $rawPunches[$emp_name] : []);

        foreach ($empLogs as $d => $entries) {
            if (isset($entries[0]['firstIn'])) {
                $e = $entries[0];
                $dailyLogs[$d] = [
                    'am_in'   => $e['firstIn'],
                    'am_out'  => $e['firstOut'],
                    'pm_in'   => $e['secondIn'],
                    'pm_out'  => $e['secondOut'],
                    'remarks' => ''
                ];
            } else {
                $times = array_column($entries, 'raw');
                sort($times);

                $am_in  = isset($times[0]) ? $times[0] : '00:00:00';
                $am_out = isset($times[1]) ? $times[1] : '00:00:00';
                $pm_in  = isset($times[2]) ? $times[2] : '00:00:00';
                $pm_out = count($times) > 3 ? $times[count($times) - 1] : (isset($times[3]) ? $times[3] : '00:00:00');

                if (count($times) == 2) {
                    $am_in  = $times[0];
                    $am_out = '00:00:00';
                    $pm_in  = '00:00:00';
                    $pm_out = $times[1];
                }

                $dailyLogs[$d] = [
                    'am_in'   => $am_in,
                    'am_out'  => $am_out,
                    'pm_in'   => $pm_in,
                    'pm_out'  => $pm_out,
                    'remarks' => ''
                ];
            }
        }

        $totalMinutesAll = 0;
    ?>

    <div class="dtr-page">
        <div class="title-header">
            <h4>CENRO-BAGUIO ATTENDANCE MONITORING SYSTEM</h4>
            <div style="font-size: 12px; margin-top: 3px;">BIOMETRIC LOCATION: CENRO BAGUIO</div>
            <div style="font-size: 12px; margin-top: 2px;">FOR THE MONTH OF: <u><strong><?php echo $monthName . ' ' . $year; ?></strong></u></div>
        </div>

        <table class="meta-table">
            <tr>
                <td width="60%">ID NO. : <u><?php echo htmlspecialchars($emp_id); ?></u></td>
                <td width="40%" align="right">DEPARTMENT : <u><?php echo htmlspecialchars($emp_dept); ?></u></td>
            </tr>
            <tr>
                <td colspan="2">NAME : <u><?php echo htmlspecialchars($emp_name); ?></u></td>
            </tr>
        </table>

        <table class="dtr-table">
            <thead>
                <tr>
                    <th width="4%">NO.</th>
                    <th width="6%">DAY</th>
                    <th width="10%">DATE</th>
                    <th width="10%">AM TIME IN</th>
                    <th width="10%">AM TIME OUT</th>
                    <th width="10%">PM TIME IN</th>
                    <th width="10%">PM TIME OUT</th>
                    <th width="28%">REMARKS</th>
                    <th width="12%">TOTAL</th>
                </tr>
            </thead>
            <tbody>
                <?php
                for ($d = 1; $d <= $daysInMonth; $d++) {
                    $dateStr  = sprintf('%04d-%02d-%02d', $year, $month, $d);
                    $dayShort = strtoupper(date('D', strtotime($dateStr)));
                    $dateFmt  = sprintf('%02d/%02d/%04d', $month, $d, $year);

                    $am_in  = isset($dailyLogs[$d]['am_in']) ? $dailyLogs[$d]['am_in'] : '';
                    $am_out = isset($dailyLogs[$d]['am_out']) ? $dailyLogs[$d]['am_out'] : '';
                    $pm_in  = isset($dailyLogs[$d]['pm_in']) ? $dailyLogs[$d]['pm_in'] : '';
                    $pm_out = isset($dailyLogs[$d]['pm_out']) ? $dailyLogs[$d]['pm_out'] : '';
                    $remark = isset($dailyLogs[$d]['remarks']) ? $dailyLogs[$d]['remarks'] : '';

                    $dayMinutes = 0;

                    // Calculate AM Shift (clamped between 7:00 AM and 7:00 PM)
                    if ($am_in && $am_out && $am_in != '00:00:00' && $am_out != '00:00:00') {
                        $amInTs  = getCappedTimestamp($am_in, $dateStr);
                        $amOutTs = getCappedTimestamp($am_out, $dateStr);
                        if ($amOutTs > $amInTs) {
                            $dayMinutes += ($amOutTs - $amInTs) / 60;
                        }
                    }

                    // Calculate PM Shift (clamped between 7:00 AM and 7:00 PM)
                    if ($pm_in && $pm_out && $pm_in != '00:00:00' && $pm_out != '00:00:00') {
                        $pmInTs  = getCappedTimestamp($pm_in, $dateStr);
                        $pmOutTs = getCappedTimestamp($pm_out, $dateStr);
                        if ($pmOutTs > $pmInTs) {
                            $dayMinutes += ($pmOutTs - $pmInTs) / 60;
                        }
                    }

                    $totalMinutesAll += $dayMinutes;

                    $dayTotalStr = '';
                    if ($dayMinutes > 0) {
                        $h = floor($dayMinutes / 60);
                        $m = $dayMinutes % 60;
                        $dayTotalStr = "{$h}h {$m}m";
                    }

                    $fmtAmIn  = ($am_in && $am_in != '00:00:00') ? date('h:i a', strtotime($am_in)) : '';
                    $fmtAmOut = ($am_out && $am_out != '00:00:00') ? date('h:i a', strtotime($am_out)) : '';
                    $fmtPmIn  = ($pm_in && $pm_in != '00:00:00') ? date('h:i a', strtotime($pm_in)) : '';
                    $fmtPmOut = ($pm_out && $pm_out != '00:00:00') ? date('h:i a', strtotime($pm_out)) : '';

                    echo "<tr>";
                    echo "<td>{$d}</td>";
                    echo "<td>{$dayShort}</td>";
                    echo "<td>{$dateFmt}</td>";
                    echo "<td>{$fmtAmIn}</td>";
                    echo "<td>{$fmtAmOut}</td>";
                    echo "<td>{$fmtPmIn}</td>";
                    echo "<td>{$fmtPmOut}</td>";
                    echo "<td>{$remark}</td>";
                    echo "<td>{$dayTotalStr}</td>";
                    echo "</tr>";
                }

                $totalHours = floor($totalMinutesAll / 60);
                $totalMins  = $totalMinutesAll % 60;
                ?>
            </tbody>
        </table>

        <div class="duration-row">
            TOTAL DURATION : &nbsp;&nbsp;<?php echo $totalHours; ?> HOURS &nbsp;&nbsp;<?php echo $totalMins; ?> MINUTES
        </div>

        <div class="cert-section">
            <div class="cert-box">
                <div class="cert-text">
                    I HEREBY CERTIFY THAT ABOVE RECORDS ARE TRUE AND CORRECT.
                </div>
                <div class="signature-line"></div>
                <div class="signatory-name"><?php echo htmlspecialchars($emp_name); ?></div>
                <div class="signatory-title"><?php echo htmlspecialchars($emp_position); ?></div>
            </div>

            <div class="cert-box">
                <div class="cert-text">
                    VERIFIED AND FOUND CORRECT AS PRESCRIBE OFFICE HOURS.
                </div>
                <div class="signature-line"></div>
                <div class="signatory-name"><?php echo htmlspecialchars($supervisor_name); ?></div>
                <div class="signatory-title"><?php echo htmlspecialchars($supervisor_title); ?></div>
            </div>
        </div>
    </div>

    <?php endforeach; ?>

</body>
</html>
<?php
// 1. IF FORM IS SUBMITTED, RENDER HTML DTR PAGE
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (file_exists('../controller/database.php')) {
        require_once '../controller/database.php';
    }

    $emp_id   = !empty($_POST['empId']) ? trim($_POST['empId']) : '--';
    $emp_name = !empty($_POST['empName']) ? strtoupper(trim($_POST['empName'])) : 'EMPLOYEE NAME';
    $emp_dept = !empty($_POST['empDept']) ? strtoupper(trim($_POST['empDept'])) : 'DEPARTMENT';

    $start_raw = !empty($_POST['startDate']) ? $_POST['startDate'] : date('Y-m-01');
    $end_raw   = !empty($_POST['endDate']) ? $_POST['endDate'] : date('Y-m-t');

    $dtStart = new DateTime($start_raw);
    $dtEnd   = new DateTime($end_raw);

    $monthName   = strtoupper($dtStart->format('F Y'));
    $daysInPeriod = $dtStart->diff($dtEnd)->days + 1;

    // Look up position if available from database
    $emp_position = 'STAFF';
    if (isset($conn) && $conn) {
        $pStmt = $conn->prepare("SELECT emp_position FROM emp_tbl WHERE emp_id = ? OR emp_name = ? LIMIT 1");
        $pStmt->bind_param("ss", $emp_id, $emp_name);
        $pStmt->execute();
        $pRes = $pStmt->get_result();
        if ($pRow = $pRes->fetch_assoc()) {
            if (!empty($pRow['emp_position'])) {
                $emp_position = strtoupper($pRow['emp_position']);
            }
        }
    }

    // Signatories mapping matching printEmployee.php
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
        'MA. DOLORES C. BALAGAT', 'JEANELYN D. BASTIAN', 'JEANLYN D. BASTIAN',
        'BENJAMIN I. NGALAWEN', 'NARDA A. GARCIA', 'NARDA A.GARCIA',
        'ELIZABETH B. ANTOLIN', 'EVELYN S. WALES', 'KATLYN S. FELIPE', 'EMERITA B. ALBAS'
    ];

    $cleanEmpName = strtoupper(trim($emp_name));
    $deptKey      = strtoupper(trim($emp_dept));

    if (strpos($cleanEmpName, 'LEANDRO') !== false && strpos($cleanEmpName, 'JESUS') !== false) {
        $supervisor_name  = 'ENGR. PAQUITO T. MORENO JR.';
        $supervisor_title = 'REGIONAL EXECUTIVE DIRECTOR';
    } else {
        $isDeptHead = false;
        foreach ($sectionHeads as $head) {
            if (strpos($cleanEmpName, $head) !== false) {
                $isDeptHead = true;
                break;
            }
        }

        if ($isDeptHead || $deptKey === 'HEADS') {
            $supervisor_name  = 'LEANDRO L. DE JESUS';
            $supervisor_title = 'CENR OFFICER';
        } else {
            $supervisor_name  = isset($signatories[$deptKey]) ? $signatories[$deptKey]['name'] : 'LEANDRO L. DE JESUS';
            $supervisor_title = isset($signatories[$deptKey]) ? $signatories[$deptKey]['title'] : 'CENR OFFICER';
        }
    }
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>DTR Blank Form - <?php echo htmlspecialchars($emp_name); ?></title>
        <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
        <style>
            body { background: #ffffff !important; color: #000000 !important; font-family: Arial, sans-serif; margin: 0; padding: 20px; }
            .dtr-page { max-width: 800px; margin: 0 auto; padding: 10px; }
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
            @media print { .no-print { display: none !important; } body { padding: 0; } .dtr-page { max-width: 100%; padding: 0; } }
        </style>
    </head>
    <body>

        <div class="no-print text-center mb-4">
            <button onclick="window.print()" class="btn btn-primary font-weight-bold px-4">🖨️ PRINT DTR</button>
            <a href="generateBlank.php" class="btn btn-secondary font-weight-bold ml-2">BACK TO FORM</a>
        </div>

        <div class="dtr-page">
            <div class="title-header">
                <h4>CENRO-BAGUIO ATTENDANCE MONITORING SYSTEM</h4>
                <div style="font-size: 12px; margin-top: 3px;">BIOMETRIC LOCATION: CENRO BAGUIO</div>
                <div style="font-size: 12px; margin-top: 2px;">FOR THE MONTH OF: <u><strong><?php echo $monthName; ?></strong></u></div>
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
                    $currDate = clone $dtStart;
                    $count = 1;

                    while ($currDate <= $dtEnd) {
                        $dayShort = strtoupper($currDate->format('D'));
                        $dateFmt  = $currDate->format('m/d/Y');

                        echo "<tr>";
                        echo "<td>{$count}</td>";
                        echo "<td>{$dayShort}</td>";
                        echo "<td>{$dateFmt}</td>";
                        echo "<td></td>";
                        echo "<td></td>";
                        echo "<td></td>";
                        echo "<td></td>";
                        echo "<td></td>";
                        echo "<td></td>";
                        echo "</tr>";

                        $currDate->modify('+1 day');
                        $count++;
                    }
                    ?>
                </tbody>
            </table>

            <div class="duration-row">
                TOTAL DURATION : &nbsp;&nbsp;____ HOURS &nbsp;&nbsp;____ MINUTES
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

    </body>
    </html>
    <?php
    exit();
}

// 2. IF NOT SUBMITTED, SHOW FORM SCREEN
if (file_exists('../controller/database.php')) {
    require_once '../controller/database.php';
}

$deptResult = false;
if (isset($conn) && $conn) {
    $getDepts = "SELECT DISTINCT emp_dept FROM emp_tbl WHERE emp_dept IS NOT NULL AND emp_dept != '' ORDER BY emp_dept ASC";
    $deptResult = $conn->query($getDepts);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS | Generate Blank Form</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../asset/css/style.css">
    <style>
        body { background-color: #121212; color: #ffffff; font-family: Arial, sans-serif; }
        .form-container { max-width: 600px; margin: 50px auto; padding: 30px; background-color: #1e1e1e; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.5); }
        .form-control { background-color: #2b2b2b; border: 1px solid #444; color: #ffffff; border-radius: 6px; padding: 10px; }
        .form-control:focus { background-color: #333; color: #ffffff; border-color: #7b1fa2; box-shadow: 0 0 5px rgba(123, 31, 162, 0.5); }
        label { font-weight: bold; margin-bottom: 5px; color: #e0e0e0; }
        .btn-generate { background-color: #6a1b9a; color: #ffffff; font-weight: bold; border: 1px solid #8e24aa; padding: 12px; border-radius: 6px; width: 100%; transition: all 0.3s ease; text-transform: uppercase; letter-spacing: 1px; }
        .btn-generate:hover { background-color: #8e24aa; color: #ffffff; box-shadow: 0 4px 12px rgba(142, 36, 170, 0.4); }
        .btn-back { background-color: transparent; color: #ffffff; border: 1px solid #666; padding: 8px 25px; border-radius: 6px; font-weight: bold; text-decoration: none; display: inline-block; transition: all 0.3s ease; }
        .btn-back:hover { background-color: #ffffff; color: #000000; text-decoration: none; }
    </style>
</head>
<body>

    <div class="container">
        <div class="form-container">
            <h4 class="text-center font-weight-bold mb-4" style="color: #ba68c8;">GENERATE BLANK FORM</h4>
            
            <form action="generateBlank.php" method="POST" target="_blank">
                
                <div class="form-group mb-3">
                    <label for="empName">Employee Name:</label>
                    <input type="text" class="form-control" id="empName" name="empName" placeholder="e.g. ELIZABETH B. ANTOLIN" required>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="empId">Employee ID:</label>
                            <input type="text" class="form-control" id="empId" name="empId" placeholder="e.g. 11001">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="empDept">Department:</label>
                            <select class="form-control" id="empDept" name="empDept" required>
                                <option value="">Select Department</option>
                                <?php
                                    if ($deptResult && $deptResult->num_rows > 0) {
                                        while ($dept = $deptResult->fetch_assoc()) {
                                            echo "<option value='" . htmlspecialchars($dept['emp_dept']) . "'>" . htmlspecialchars($dept['emp_dept']) . "</option>";
                                        }
                                    } else {
                                        $depts = ['RPS', 'EMS', 'CDS', 'PSU', 'MHWFR', 'LAWFR', 'UARBRR', 'MPPL'];
                                        foreach ($depts as $d) {
                                            echo "<option value='{$d}'>{$d}</option>";
                                        }
                                    }
                                ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="startDate">Start Date:</label>
                            <input type="date" class="form-control" id="startDate" name="startDate" value="<?php echo date('Y-m-01'); ?>" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="endDate">End Date:</label>
                            <input type="date" class="form-control" id="endDate" name="endDate" value="<?php echo date('Y-m-t'); ?>" required>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-3">
                    <button type="submit" name="submit" value="1" class="btn btn-generate">
                        GENERATE FORM
                    </button>
                </div>

                <div class="form-group">
                    <a href="../index.php" class="btn btn-back">BACK</a>
                </div>

            </form>
        </div>
    </div>

</body>
</html>
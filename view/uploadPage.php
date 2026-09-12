<?php
    require_once '../controller/database.php';

    $msg = '';
    $msgType = '';

    // Handle Upload Feedback Status query parameters from controller redirects
    if (isset($_GET['status'])) {
        if ($_GET['status'] === 'success') {
            $count = isset($_GET['count']) ? (int)$_GET['count'] : 0;
            $msg = "Uploaded and processed {$count} raw biometric log records!";
            $msgType = "success";
        } elseif ($_GET['status'] === 'failed') {
            $reason = isset($_GET['reason']) ? urldecode($_GET['reason']) : 'Unknown file error.';
            $msg = "UPLOAD FAILED: {$reason}";
            $msgType = "error";
        }
    }

    // Handle Employee Masterlist CSV Upload
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'upload_masterlist') {
        if (isset($_FILES['masterlist_file']) && $_FILES['masterlist_file']['error'] === UPLOAD_ERR_OK) {
            $fileTmpPath   = $_FILES['masterlist_file']['tmp_name'];
            $fileName      = $_FILES['masterlist_file']['name'];
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if ($fileExtension === 'csv') {
                $file = fopen($fileTmpPath, 'r');
                $firstRow = fgetcsv($file); 
                
                $isHeader = false;
                if ($firstRow && (stripos($firstRow[0], 'name') !== false || stripos($firstRow[0], 'emp') !== false || stripos($firstRow[0], 'id') !== false)) {
                    $isHeader = true;
                }

                $stmt = $conn->prepare("INSERT INTO emp_tbl (emp_id, emp_name, emp_position, emp_dept) 
                    VALUES (?, ?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                        emp_name = VALUES(emp_name), 
                        emp_position = VALUES(emp_position), 
                        emp_dept = VALUES(emp_dept)");

                $count = 0;
                $generatedIdCount = 100;
                $rowsToProcess = [];

                if (!$isHeader && $firstRow) {
                    $rowsToProcess[] = $firstRow;
                }

                while (($row = fgetcsv($file)) !== FALSE) {
                    $rowsToProcess[] = $row;
                }

                foreach ($rowsToProcess as $row) {
                    if (empty($row) || count($row) < 2) continue;

                    // Clean input columns
                    $col0 = isset($row[0]) ? trim((string)$row[0]) : '';
                    $col1 = isset($row[1]) ? trim((string)$row[1]) : '';
                    $col2 = isset($row[2]) ? trim((string)$row[2]) : '';
                    $col3 = isset($row[3]) ? trim((string)$row[3]) : '';

                    if (count($row) == 3) {
                        // 3-Column format: Name, Position, Department
                        $generatedIdCount++;
                        $emp_id       = '11' . str_pad($generatedIdCount, 3, '0', STR_PAD_LEFT);
                        $emp_name     = strtoupper($col0);
                        $emp_position = !empty($col1) ? strtoupper($col1) : 'STAFF';
                        $emp_dept     = !empty($col2) ? strtoupper($col2) : 'CENRO';
                    } else {
                        // 4-Column format: ID NO., EMPLOYEE NAME, POSITION, OFFICE
                        $rawId = $col0;

                        if (is_numeric($rawId)) {
                            $rawId = (string)(int)$rawId;
                        }

                        if (empty($rawId) || $rawId === '0') {
                            $generatedIdCount++;
                            $emp_id = '11' . str_pad($generatedIdCount, 3, '0', STR_PAD_LEFT);
                        } else {
                            $emp_id = $rawId;
                        }

                        $emp_name     = strtoupper($col1);
                        $emp_position = !empty($col2) ? strtoupper($col2) : 'STAFF';
                        $emp_dept     = !empty($col3) ? strtoupper($col3) : 'CENRO';
                    }

                    if (!empty($emp_name)) {
                        $stmt->bind_param("ssss", $emp_id, $emp_name, $emp_position, $emp_dept);
                        if ($stmt->execute()) {
                            $count++;
                        }
                    }
                }
                fclose($file);

                $msg = "Uploaded and updated {$count} employee records in the masterlist!";
                $msgType = "success";
            } else {
                $msg = "UPLOAD FAILED: Please upload a valid CSV file (.csv).";
                $msgType = "error";
            }
        } else {
            $msg = "UPLOAD FAILED: File upload failed or no file selected.";
            $msgType = "error";
        }
    }

    // Handle Complete System Reset
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'clear_masterlist') {
        $conn->query("SET FOREIGN_KEY_CHECKS = 0");
        $tablesToTruncate = ['emp_tbl', 'info_tbl', 'attendancelogs', 'time_logs', 'emp_remarks', 'emp_biometric_map'];

        $allCleared = true;
        foreach ($tablesToTruncate as $tbl) {
            if (!$conn->query("TRUNCATE TABLE {$tbl}")) {
                $allCleared = false;
            }
        }
        $conn->query("SET FOREIGN_KEY_CHECKS = 1");

        if ($allCleared) {
            $msg = "TOTAL SYSTEM WIPE COMPLETE! All records have been reset.";
            $msgType = "success";
        } else {
            $msg = "FAILED: Error clearing system data: " . addslashes($conn->error);
            $msgType = "error";
        }
    }

    // Default start and end dates (first and last day of current month)
    $defaultStartDate = date('Y-m-01');
    $defaultEndDate   = date('Y-m-t');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS | Upload & Reset Portal</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../asset/css/style.css">
    <script src="../asset/js/bootstrap.min.js"></script>
    
    <!-- SweetAlert2 Library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        .upload-card {
            background: #ffffff;
            border-radius: 8px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
            margin-bottom: 25px;
        }
    </style>
</head>
<body>

    <div class="text-center headWall" style="padding: 12px 0 !important; margin-bottom: 25px !important;">
        <h2 style="margin: 0; font-weight: bold; font-size: 24px; color: #ffffff;">ATTENDANCE MONITORING SYSTEM</h2>
        <p style="margin: 0; font-size: 14px; color: rgba(255, 255, 255, 0.8);">Biometric & Masterlist File Import Portal</p>
    </div>

    <div class="container" style="max-width: 900px;">

        <div class="row">
            <!-- 1. Raw Biometric Upload Card with Date Pickers -->
            <div class="col-md-6">
                <div class="upload-card border-top border-primary" style="border-top-width: 5px !important;">
                    <h5 class="font-weight-bold mb-3">1. RAW BIOMETRIC LOGS</h5>
                    <p class="text-muted small">Upload `.dat`, `.txt`, `.csv`, or `.log` raw biometric log files.</p>
                    
                    <form action="../controller/upload.php" method="POST" enctype="multipart/form-data">
                        
                        <!-- Start Date & End Date Inputs -->
                        <div class="form-row mb-3">
                            <div class="col-md-6">
                                <label><strong>Start Date:</strong></label>
                                <input type="date" name="startDate" class="form-control" value="<?php echo $defaultStartDate; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label><strong>End Date:</strong></label>
                                <input type="date" name="endDate" class="form-control" value="<?php echo $defaultEndDate; ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><strong>Select Biometric Log File:</strong></label>
                            <input type="file" name="file" class="form-control-file border rounded p-2" required>
                        </div>
                        
                        <button type="submit" name="submit" class="btn btn-primary btn-block font-weight-bold">
                            UPLOAD BIOMETRIC LOGS
                        </button>
                    </form>
                </div>
            </div>

            <!-- 2. Bulk Employee Masterlist Upload Card -->
            <div class="col-md-6">
                <div class="upload-card border-top border-success" style="border-top-width: 5px !important;">
                    <h5 class="font-weight-bold mb-3">2. EMPLOYEE MASTERLIST</h5>
                    <p class="text-muted small">Upload CSV with official names, positions, and departments.</p>

                    <form method="POST" action="" enctype="multipart/form-data">
                        <input type="hidden" name="action" value="upload_masterlist">
                        
                        <div class="form-group">
                            <label><strong>Select Masterlist CSV File:</strong></label>
                            <input type="file" name="masterlist_file" accept=".csv" class="form-control-file border rounded p-2" required>
                        </div>
                        <button type="submit" class="btn btn-success btn-block font-weight-bold">
                            UPDATE MASTERLIST (CSV)
                        </button>
                    </form>
                </div>
            </div>

            <!-- 3. Total System Reset Card -->
            <div class="col-md-12">
                <div class="upload-card border-top border-danger" style="border-top-width: 5px !important;">
                    <h5 class="font-weight-bold text-danger mb-2">3. TOTAL SYSTEM RESET (CLEAN SLATE)</h5>
                    <p class="text-muted small m-0 mb-3">Permanently delete <strong>all employees, attendance time logs, department filters, custom remarks, and biometric mappings</strong>.</p>
                    
                    <form id="wipeForm" method="POST" action="">
                        <input type="hidden" name="action" value="clear_masterlist">
                        <button type="button" onclick="confirmSystemWipe()" class="btn btn-outline-danger font-weight-bold btn-block">
                            💥 PERFORM TOTAL SYSTEM WIPE
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="text-center mt-2 mb-5">
            <a href="../index.php" class="btn btn-outline-dark font-weight-bold px-5">BACK TO DASHBOARD</a>
        </div>

    </div>

    <!-- SweetAlert2 Trigger for Success/Error Notifications -->
    <?php if (!empty($msg)): ?>
        <script>
            Swal.fire({
                icon: '<?php echo $msgType; ?>',
                title: '<?php echo ($msgType === "success") ? "Success!" : "Notice"; ?>',
                text: '<?php echo addslashes($msg); ?>',
                confirmColor: '#007bff'
            });
        </script>
    <?php endif; ?>

    <!-- SweetAlert2 Trigger for Reset Confirmation -->
    <script>
        function confirmSystemWipe() {
            Swal.fire({
                title: 'WARNING!',
                text: 'Wiping system records is irreversible. Proceed with total system reset?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc3545',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, Wipe Everything!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('wipeForm').submit();
                }
            });
        }
    </script>

</body>
</html>
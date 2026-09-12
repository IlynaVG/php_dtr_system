<?php
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);

    // Database connection import with fallback checks
    if (file_exists('../controller/database.php')) {
        require_once '../controller/database.php';
    } elseif (file_exists('controller/database.php')) {
        require_once 'controller/database.php';
    } else {
        die("Error: Controller database file not found.");
    }

    // Default Fallback Date: Current Month and Year
    $defaultMonth = (int)date('m');
    $defaultYear  = (int)date('Y');

    // Auto-detect month and year strictly from the uploaded biometric log date range
    $activeDateRes = $conn->query("SELECT MIN(date_info) AS min_date, MAX(date_info) AS max_date FROM info_tbl WHERE date_info IS NOT NULL AND date_info != ''");
    
    if (!$activeDateRes || $activeDateRes->num_rows == 0) {
        $activeDateRes = $conn->query("SELECT MIN(logs_checktime) AS min_date, MAX(logs_checktime) AS max_date FROM attendancelogs WHERE logs_checktime IS NOT NULL AND logs_checktime != ''");
    }

    if ($activeDateRes && $activeRow = $activeDateRes->fetch_assoc()) {
        $targetDate = !empty($activeRow['min_date']) ? $activeRow['min_date'] : $activeRow['max_date'];
        if (!empty($targetDate)) {
            $dtActive = date_create($targetDate);
            if ($dtActive) {
                $defaultMonth = (int)$dtActive->format('m');
                $defaultYear  = (int)$dtActive->format('Y');
            }
        }
    }

    $activeMonthName = strtoupper(date('F Y', mktime(0, 0, 0, $defaultMonth, 10, $defaultYear)));

    // Get ALL unique employees
    $getEmp = "SELECT emp_id, emp_name, MAX(emp_position) as emp_position, MAX(emp_dept) as emp_dept FROM emp_tbl GROUP BY emp_id, emp_name ORDER BY emp_name ASC";
    $resultEmp = $conn ? $conn->query($getEmp) : false;
    $total_rows = $resultEmp ? $resultEmp->num_rows : 0;

    // Get unique departments for dropdown
    $getDepts = "SELECT DISTINCT emp_dept FROM emp_tbl WHERE emp_dept IS NOT NULL AND emp_dept != '' ORDER BY emp_dept";
    $resultDepts = $conn ? $conn->query($getDepts) : false;

    $count = 1;
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS | Employee List</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../asset/css/style.css">
    <link rel="stylesheet" type="text/css" href="../asset/css/employee-style.css">
    <script src="../asset/js/bootstrap.min.js"></script>
</head>

<body>

    <div class="text-center headWall" style="padding: 12px 0 !important; margin-bottom: 15px !important;">
        <h2 style="margin: 0; font-weight: bold; font-size: 24px; color: #ffffff;">ATTENDANCE MONITORING SYSTEM</h2>
        <p style="margin: 0; font-size: 14px; color: rgba(255, 255, 255, 0.8);">
            ACTIVE PERIOD: <strong style="color: #00ffcc;"><?php echo $activeMonthName; ?></strong>
        </p>
    </div>
      
    <div class="container employee-container">
        <!-- Controls Row -->
        <div class="row employee-controls align-items-center mb-3">
            <div class="col-md-5">
                <input type="text" id="searchInput" class="form-control" placeholder="Search Name, Employee ID, Department...">
            </div>
            <div class="col-md-3">
                <select id="deptFilter" class="form-control">
                    <option value="">All Departments</option>
                    <?php
                        if ($resultDepts && $resultDepts->num_rows > 0) {
                            while($deptRow = $resultDepts->fetch_assoc()) {
                                echo "<option value='" . htmlspecialchars($deptRow['emp_dept']) . "'>" . htmlspecialchars($deptRow['emp_dept']) . "</option>";
                            }
                        }
                    ?>
                </select>
            </div>
            <div class="col-md-4 text-right">
                <a href="printEmployee.php?all=1&month=<?php echo $defaultMonth; ?>&year=<?php echo $defaultYear; ?>" target="_blank" class="btn btn-success font-weight-bold btn-block" id="printAllBtn">
                    🖨️ PRINT ALL DTRs
                </a>
            </div>
        </div>

        <div class="text-muted small mb-2" id="paginationInfo">
            Showing 1 to <?php echo min(15, $total_rows); ?> of <?php echo $total_rows; ?> employees
        </div>
        
        <!-- Table Container -->
        <div class="employee-table-responsive">
            <table id="dtBasicExample" class="table employee-table table-striped table-bordered table-sm" cellspacing="0" width="100%">
                <thead class="thead-dark sticky-top">
                    <tr>
                        <th scope="col">#</th>
                        <th scope="col">EMP. NO</th>
                        <th scope="col">NAME</th>
                        <th scope="col">DEPARTMENT</th>
                        <th scope="col">ACTION</th>
                    </tr>
                </thead>
                <tbody>
            <?php
                if ($resultEmp && $resultEmp->num_rows > 0) {
                    while($row = $resultEmp->fetch_assoc()){
                        echo "<tr>";
                            echo "<th scope=\"row\">" . $count . "</th>";
                            echo "<td>" . htmlspecialchars($row['emp_id']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['emp_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['emp_dept']) . "</td>";
                            echo "<td>
                                <a href=\"printEmployee.php?id=".urlencode($row['emp_id'])."&month={$defaultMonth}&year={$defaultYear}\" target=\"_blank\" class=\"btn btn-primary btn-sm\">PRINT</a>
                            </td>";
                        echo "</tr>";
                        $count++;
                    }
                } else {
                    echo "<tr><td colspan='5' class='text-center py-4'>
                        <h5 class='text-muted'>No employee records found in database.</h5>
                        <p class='small text-muted'>Please upload your masterlist CSV file using the portal.</p>
                        <a href='uploadPage.php' class='btn btn-sm btn-outline-success font-weight-bold'>📤 OPEN UPLOAD PORTAL</a>
                    </td></tr>";
                }
            ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination Controls -->
        <div class="pagination-controls mt-3">
            <nav aria-label="Employee pagination">
                <ul class="pagination justify-content-center" id="paginationControls">
                </ul>
            </nav>
        </div>

        <div class="text-center mt-3 mb-5">
            <style>
                .btn-back {
                    background-color: #ffffff;
                    color: #000000;
                    border: 2px solid #000000;
                    padding: 10px 50px;
                    border-radius: 30px;
                    font-weight: bold;
                    transition: all 0.3s ease;
                    text-decoration: none;
                    display: inline-block;
                    text-transform: uppercase;
                    letter-spacing: 1px;
                    font-size: 14px;
                }

                .btn-back:hover {
                    background-color: #000000;
                    color: #ffffff !important;
                    border-color: #000000;
                    text-decoration: none;
                    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
                    transform: translateY(-2px);
                }
            </style>
            <a href="../index.php" class="btn btn-back">BACK</a>
        </div>

    </div>

    <script>
        let allEmployees = [];
        let currentPage = 1;
        let resultsPerPage = 15;
        let filteredEmployees = [];

        const activeMonth = <?php echo $defaultMonth; ?>;
        const activeYear = <?php echo $defaultYear; ?>;
        
        document.addEventListener('DOMContentLoaded', function() {
            const tableRows = document.querySelectorAll('#dtBasicExample tbody tr');
            allEmployees = Array.from(tableRows).map((row, index) => {
                if (row.cells.length < 4) return null;
                return {
                    element: row,
                    empNo: row.cells[1].textContent.toLowerCase().trim(),
                    name: row.cells[2].textContent.toLowerCase().trim(),
                    dept: row.cells[3].textContent.toLowerCase().trim(),
                    originalDept: row.cells[3].textContent.trim(),
                    originalNumber: index + 1
                };
            }).filter(item => item !== null);
            
            filteredEmployees = [...allEmployees];
            filterAndPaginate();
        });
        
        function filterAndPaginate() {
            const searchValue = document.getElementById('searchInput').value.trim();
            const deptValue  = document.getElementById('deptFilter').value.trim();
            
            // Build PRINT ALL link dynamically
            const printBtn = document.getElementById('printAllBtn');
            let printUrl = `printEmployee.php?all=1&month=${activeMonth}&year=${activeYear}`;
            
            if (deptValue !== '') {
                printUrl += '&dept=' + encodeURIComponent(deptValue);
                printBtn.innerHTML = `🖨️ PRINT ALL (${deptValue.toUpperCase()})`;
            } else {
                printBtn.innerHTML = `🖨️ PRINT ALL DTRs`;
            }

            printBtn.setAttribute('href', printUrl);

            // Dynamically update individual row PRINT links with uploaded Month & Year
            document.querySelectorAll('#dtBasicExample tbody tr').forEach(row => {
                const printActionLink = row.querySelector('a.btn-primary');
                if (printActionLink) {
                    const empId = row.cells[1].textContent.trim();
                    printActionLink.setAttribute('href', `printEmployee.php?id=${encodeURIComponent(empId)}&month=${activeMonth}&year=${activeYear}`);
                }
            });
            
            const searchLower = searchValue.toLowerCase();
            const deptLower   = deptValue.toLowerCase();

            filteredEmployees = allEmployees.filter(employee => {
                const searchMatch = searchValue === '' || 
                                    employee.empNo.includes(searchLower) || 
                                    employee.name.includes(searchLower) || 
                                    employee.dept.includes(searchLower);
                
                const deptMatch = deptValue === '' || employee.dept === deptLower;
                
                return searchMatch && deptMatch;
            });
            
            if (searchValue !== '' || deptValue !== '') {
                currentPage = 1;
            }
            
            updateTable();
        }
        
        function updateTable() {
            const totalPages = Math.ceil(filteredEmployees.length / resultsPerPage);
            currentPage = Math.min(currentPage, totalPages || 1);
            
            allEmployees.forEach(emp => {
                emp.element.style.display = 'none';
            });
            
            const startIndex = (currentPage - 1) * resultsPerPage;
            const endIndex = startIndex + resultsPerPage;
            const currentEmployees = filteredEmployees.slice(startIndex, endIndex);
            
            currentEmployees.forEach((employee, index) => {
                employee.element.style.display = '';
                employee.element.cells[0].textContent = startIndex + index + 1;
            });
            
            const infoElement = document.getElementById('paginationInfo');
            if (infoElement) {
                const startNum = filteredEmployees.length === 0 ? 0 : startIndex + 1;
                const endNum = Math.min(endIndex, filteredEmployees.length);
                infoElement.textContent = `Showing ${startNum} to ${endNum} of ${filteredEmployees.length} employees`;
            }
            
            updatePaginationButtons(totalPages);
        }
        
        function updatePaginationButtons(totalPages) {
            const paginationContainer = document.getElementById('paginationControls');
            if (!paginationContainer) return;
            paginationContainer.innerHTML = '';
            
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${currentPage <= 1 ? 'disabled' : ''}`;
            const prevLink = document.createElement('a');
            prevLink.className = 'page-link';
            prevLink.href = '#';
            prevLink.textContent = 'Previous';
            prevLink.onclick = (e) => {
                e.preventDefault();
                if (currentPage > 1) {
                    currentPage--;
                    updateTable();
                }
            };
            prevLi.appendChild(prevLink);
            paginationContainer.appendChild(prevLi);
            
            const startPage = Math.max(1, currentPage - 2);
            const endPage = Math.min(totalPages, startPage + 4);
            
            for (let i = startPage; i <= endPage; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === currentPage ? 'active' : ''}`;
                const link = document.createElement('a');
                link.className = 'page-link';
                link.href = '#';
                link.textContent = i;
                link.onclick = (e) => {
                    e.preventDefault();
                    currentPage = i;
                    updateTable();
                };
                li.appendChild(link);
                paginationContainer.appendChild(li);
            }
            
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${currentPage >= totalPages ? 'disabled' : ''}`;
            const nextLink = document.createElement('a');
            nextLink.className = 'page-link';
            nextLink.href = '#';
            nextLink.textContent = 'Next';
            nextLink.onclick = (e) => {
                e.preventDefault();
                if (currentPage < totalPages) {
                    currentPage++;
                    updateTable();
                }
            };
            nextLi.appendChild(nextLink);
            paginationContainer.appendChild(nextLi);
        }

        document.getElementById('searchInput').addEventListener('keyup', filterAndPaginate);
        document.getElementById('deptFilter').addEventListener('change', filterAndPaginate);
    </script>
</body>
</html>
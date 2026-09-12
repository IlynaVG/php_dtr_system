<?php

require_once '../controller/database.php';

// Get ALL employees for dropdown
$getAllEmp = "SELECT emp_id, emp_name, emp_dept FROM emp_tbl ORDER BY emp_name";
$resultAllEmp = $conn->query($getAllEmp);

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="Author" content="Jaymar Gabriel S. Banking">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AMS | Out Field</title>
    <link rel="stylesheet" href="../asset/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="../asset/css/style.css">
    <style>
        :root {
            --brand-purple: rgba(83, 14, 143, 1);
        }

        body {
            background-color: #f4f7f6 !important;
        }

        .headWall {
            padding: 1rem 1rem !important;
            margin-bottom: 10px !important;
            background-color: var(--brand-purple) !important;
            border-bottom: 3px solid #4a0a80 !important;
            color: #ffffff !important;
        }

        .headWall h1 {
            font-size: 30px !important;
            margin-bottom: 5px !important;
            color: #ffffff !important;
            font-weight: bold !important;
        }

        .headWall p {
            font-size: 15px !important;
            color: rgba(255, 255, 255, 0.8) !important;
            margin-bottom: 0;
        }

        /* .form-container {
            background: #ffffff !important;
            padding: 20px 20px !important;
            border-radius: 12px !important;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.08) !important;
            margin: 15px auto 30px auto !important;
            max-width: 450px !important;
            border-top: 6px solid var(--brand-purple) !important;
        } */

             .form-container {
            background: #ffffff;
            padding-bottom: 30px;
            padding-top: 30px;
            padding-left: 40px;
            padding-right: 40px;
            border-radius: 10px;
            box-shadow: 0px 8px 20px rgba(0, 0, 0, 0.08);
            margin: 0px auto !important;
            max-width: 500px;
            border-top: 5px solid var(--brand-purple);
        }

        .form-group label {
            font-weight: bold !important;
            font-size: 14px !important;
            color: #555 !important;
            /* margin-bottom: 8px !important; */
        }

        .form-control {
            border-radius: 6px !important;
            border: 1px solid #ced4da !important;
            height: auto !important;
            padding: 10px !important;
        }

        /* Specific Purple Date inputs style */
        input[type="date"].form-control {
            border: 2px solid #6f42c1 !important;
            font-size: 15px !important;
            height: 45px !important;
        }

        .btn-generate {
            background-color: var(--brand-purple) !important;
            border: 2px solid var(--brand-purple) !important;
            color: #ffffff !important;
            font-weight: bold !important;
            padding: 8px 16px !important;
            border-radius: 6px !important;
            transition: 0.3s !important;
            font-size: 14px !important;
        }

        .btn-generate:hover {
            background-color: #ffffff !important;
            border-color: var(--brand-purple) !important;
            color: var(--brand-purple) !important;
        }

        .btn-back {
            background-color: #ffffff !important;
            border: 2px solid #131313 !important;
            color: #171717 !important;
            border-radius: 6px !important;
            padding: 8px 16px !important;
            transition: 0.3s !important;
            display: inline-block !important;
            text-align: center !important;
            font-weight: bold !important;
            font-size: 14px !important;
            text-decoration: none !important;
        }

        .btn-back:hover {
            background-color: #131313 !important;
            border-color: #131313 !important;
            color: #ffffff !important;
            text-decoration: none !important;
        }
    </style>
</head>
<body>
    <div class="jumbotron text-center headWall">
        <h1>GENERATE OUT FIELD FORM</h1>
        <p>Fill out the details below to create a printable Attendance Monitoring sheet</p>
    </div>

    <div class="container">
        <div class="form-container">
           <form method="post" action="printBlankLogs.php" target="_blank">
                <div class="form-group">
                    <label for="employeeSelect">Select Employee:</label>
                    <select class="form-control" id="employeeSelect" required>
                        <option value="">-- Select Employee Name --</option>
                        <?php
                            while($emp = $resultAllEmp->fetch_assoc()){
                                echo '<option value="'.htmlspecialchars($emp['emp_id']).'" 
                                    data-name="'.htmlspecialchars($emp['emp_name']).'" 
                                    data-dept="'.htmlspecialchars($emp['emp_dept']).'">
                                    '.htmlspecialchars($emp['emp_name']).'
                                </option>';
                            }
                        ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="empNameDisplay">Employee Name:</label>
                    <input type="text" class="form-control" id="empNameDisplay" readonly style="background-color: #e9ecef;">
                </div>
                
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="empId">Employee ID:</label>
                            <input type="text" class="form-control" id="empId" name="empId" required readonly style="background-color: #e9ecef;">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="empDept">Department:</label>
                            <input type="text" class="form-control" id="empDept" name="empDept" required readonly style="background-color: #e9ecef;">
                        </div>
                    </div>
                </div>

                <input type="hidden" id="empName" name="empName">

                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="startDate">Start Date:</label>
                            <input type="date" class="form-control" id="startDate" name="startDate" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="endDate">End Date:</label>
                            <input type="date" class="form-control" id="endDate" name="endDate" required>
                        </div>
                    </div>
                </div>

   <div style="margin-top: 4px;">
                    <button type="submit" name="submit" class="btn btn-generate btn-block">GENERATE PDF</button>
                    
                    <div class="text-left" style="margin-top: 5px;">
                     <a href="../index.php" class="btn btn-back">BACK</a>
                    </div>
                </div>



            </form>
        </div>
    </div>
    <script>
        document.getElementById('employeeSelect').addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value !== '') {
                document.getElementById('empId').value = selectedOption.value;
                document.getElementById('empName').value = selectedOption.getAttribute('data-name');
                document.getElementById('empNameDisplay').value = selectedOption.getAttribute('data-name');
                document.getElementById('empDept').value = selectedOption.getAttribute('data-dept');
            } else {
                document.getElementById('empId').value = '';
                document.getElementById('empName').value = '';
                document.getElementById('empNameDisplay').value = '';
                document.getElementById('empDept').value = '';
            }
        });
    </script>
</body>
</html>

<?php
	require_once('../controller/database.php');

	class Employee{

		private $dbname;

		function __construct(){
			$this->dbname = new mysqli(DB_SERVER, DB_UNAME, DB_PASS, DB_NAME);

			if(mysqli_connect_errno()){
				echo "Error: Could not connect to database.";
				exit;
			}
		}		

		function generateDate($startDate, $endDate, $format = 'm/d/Y'){
			$count = 0;

			$interval = new DateInterval('P1D');

	    	$realEnd = new DateTime($endDate);
	    	$realEnd->add($interval);
	  
	    	$period = new DatePeriod(new DateTime($startDate), $interval, $realEnd);

	    	foreach($period as $date){

	    		$toDate = $date->format($format);

	    		$insertDate = "INSERT INTO `date_tbl`(`date_name`) VALUES ('$toDate')";
	        	$resultDate = $this->dbname->query($insertDate);
	    	}

		}

		function fileUpload($fileName){
			$count = 0;

			$file = fopen($fileName, "r");

			while(!feof($file)){

				$content = fgets($file);
				$carray = explode(",", $content);

				list($id_db,$dept_db,$name_db,$dateAndTime_db,$checktype_db) = $carray;
						
				$insAttendanceLogs = "INSERT INTO `attendancelogs`(`logs_Id`, `logs_department`, `logs_name`, `logs_checktime`, `logs_checktype`) VALUES ('$id_db','$dept_db','$name_db','$dateAndTime_db','$checktype_db')";
				$this->dbname->query($insAttendanceLogs);
			}

			fclose($file);
		}

		function uploadEmployee(){
			$empId1 = '';

			$selectData = "SELECT logs_Id, logs_department, logs_name FROM attendancelogs";
			$exec_selectData = $this->dbname->query($selectData);

			if($exec_selectData->num_rows > 0){
				while($row = $exec_selectData->fetch_assoc()){
					$empId = intval($row['logs_Id']);
					$empName = $row['logs_name'];
					$empDept = $row['logs_department'];

					if ($empId1 == $empId || $empId == 0) {
						continue;
					}else{
						$insEmployeeData = "INSERT INTO `emp_tbl`(`emp_id`, `emp_name`, `emp_dept`) VALUES ('$empId','$empName','$empDept')";
						$this->dbname->query($insEmployeeData);
					}
					$empId1 = $empId;
				}
			}
		}

		function generateTimeLogs(){
			$getAttLogs = "SELECT logs_id, logs_checktime, logs_checktype FROM attendancelogs";
			$execGetAttLogs = $this->dbname->query($getAttLogs);

			if($execGetAttLogs->num_rows > 0){
				while($row = $execGetAttLogs->fetch_assoc()){
					$empId_db = $row["logs_id"];
					$dateTime = $row["logs_checktime"];
					$status = $row["logs_checktype"];
					$delimeter = ' ';

					$words = explode($delimeter,$dateTime);
					$get_date = strtotime($words[0]);
					$convertToDate = date('m/d/Y', $get_date); 
					$get_time = $words[1];
					$get_time .= $words[2];
					$exactTime = date("G:i", strtotime($get_time));
					

					$insertData = "INSERT INTO `time_logs`(`emp_id`, `check_date`, `check_time`, `check_type`) VALUES ('$empId_db','$convertToDate','$exactTime', '$status')";
					$this->dbname->query($insertData);
				}
			}
		}

		function splitTimeLogs(){

			$date = array();
			$empid = array();
			$timeLogs = array();

			// Define lunch break period (12:00 PM to 1:00 PM)
			$lunchStart = strtotime('12:00');
			$lunchEnd = strtotime('13:00');

			$getDate = "SELECT `date_name` FROM `date_tbl`";
			$exec_date = $this->dbname->query($getDate);
	
			if($exec_date->num_rows > 0){
				while ($dateRow = $exec_date->fetch_assoc()) {
					$dateOnTbl_db = $dateRow['date_name'];

					array_push($date, $dateOnTbl_db);
				}
			}

			$getEmp = "SELECT `emp_id` FROM `emp_tbl`";
			$exec_Emp = $this->dbname->query($getEmp);
	
			if($exec_Emp->num_rows > 0){
				while ($empRow = $exec_Emp->fetch_assoc()) {
					$empOnTbl_db = $empRow['emp_id'];

					array_push($empid, $empOnTbl_db);
				}
			}

			for ($i=0; $i < count($empid); $i++) { 
			for ($x=0; $x < count($date); $x++) {
				$arr = [];
				$convertTotalTime = '';

				$getTime = "SELECT `emp_id`, `check_date`,`check_time` FROM `time_logs` WHERE `emp_id` = '$empid[$i]'";
				$exec_Time = $this->dbname->query($getTime);

					if ($exec_Time->num_rows > 0) {
						while($timeRow = $exec_Time->fetch_assoc()){
							if($date[$x] != $timeRow['check_date']){
							continue;
						    }else{
						    	array_push($arr, $timeRow['check_time']);
						    }
						}
					}

					// Calculate total time with proper lunch break exclusion
					if(!empty($arr[0]) && !empty($arr[1]) && !empty($arr[2]) && !empty($arr[3])) {
						// Scenario 1: Complete 2-shift pattern (existing logic preserved)
						// Example: 7:21 AM → 12:00 PM → 1:30 PM → 5:00 PM
						$firstIn = strtotime($arr[0]);
						$firstOut = strtotime($arr[1]);
						$secondIn = strtotime($arr[2]);
						$secondOut = strtotime($arr[3]);

						// Adjust times to exclude lunch break
						// If first out is after 12:00, cap it at 12:00
						if ($firstOut > $lunchStart) {
							$firstOut = $lunchStart;
						}

						// If second in is before 1:00, set it to 1:00
						if ($secondIn < $lunchEnd) {
							$secondIn = $lunchEnd;
						}

						// Calculate total time: (Second OUT - Second IN) + (First OUT - First IN)
						$firstSession = $firstOut - $firstIn;
						$secondSession = $secondOut - $secondIn;
						$totalTimeDay = $firstSession + $secondSession;
						
						// Ensure we don't get negative time
						if ($totalTimeDay < 0) {
							$totalTimeDay = 0;
						}

						// Format total time as duration (not as time of day)
						$hours = floor($totalTimeDay / 3600);
						$minutes = floor(($totalTimeDay % 3600) / 60);
						$seconds = $totalTimeDay % 60;
						$convertTotalTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
					}
					else if(!empty($arr[0]) && !empty($arr[1]) && empty($arr[2]) && empty($arr[3])) {
						// Scenario 2: Continuous shift (NEW LOGIC)
						// Example: 7:21 AM → 7:00 PM (only 2 entries)
						$startTime = strtotime($arr[0]);
						$endTime = strtotime($arr[1]);
						$totalTimeDay = $endTime - $startTime;
						
						// Deduct 1 hour lunch break for continuous shifts
						$totalTimeDay -= 3600; // 1 hour = 3600 seconds
						
						// Prevent negative time
						if ($totalTimeDay < 0) {
							$totalTimeDay = 0;
						}

						// Format total time as duration
						$hours = floor($totalTimeDay / 3600);
						$minutes = floor(($totalTimeDay % 3600) / 60);
						$seconds = $totalTimeDay % 60;
						$convertTotalTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
					}
					else if(!empty($arr[0]) && !empty($arr[1]) && !empty($arr[2]) && empty($arr[3])) {
						// Scenario 3: Incomplete afternoon session (NEW LOGIC)
						// Example: 7:21 AM → 12:00 PM → 1:30 PM (no final OUT)
						// Only count morning session to avoid negative time
						$startTime = strtotime($arr[0]);
						$endTime = strtotime($arr[1]);
						$totalTimeDay = $endTime - $startTime;
						
						// Prevent negative time
						if ($totalTimeDay < 0) {
							$totalTimeDay = 0;
						}

						// Format total time as duration
						$hours = floor($totalTimeDay / 3600);
						$minutes = floor(($totalTimeDay % 3600) / 60);
						$seconds = $totalTimeDay % 60;
						$convertTotalTime = sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds);
					}
					else {
						// Default case: insufficient data
						$convertTotalTime = "00:00:00";
					}

					$getInfo = "INSERT INTO `info_tbl`(`emp_id`, `date_info`, `firstTimeIn`, `firstTimeOut`, `secondTimeIn`, `secondTimeOut`, `extraTime`, `totalTimeDay`) VALUES ('$empid[$i]','$date[$x]','$arr[0]','$arr[1]','$arr[2]','$arr[3]', '$arr[4]','$convertTotalTime')";
					$insertInfo = $this->dbname->query($getInfo);
				}
			}
		}
		function delDate(){
			$this->dbname->query("TRUNCATE TABLE date_tbl");
		}

		function delEmployee(){
			$this->dbname->query("TRUNCATE TABLE emp_tbl");
		}

		function delTimeLogs(){
			$this->dbname->query("TRUNCATE TABLE time_logs");
		}

		function delAttendanceLogs(){
			$this->dbname->query("TRUNCATE TABLE attendancelogs");
		}

		function delInfoTable(){
			$this->dbname->query("TRUNCATE TABLE info_tbl");
		}

		function getAllEmployees(){
			$employees = array();
			$query = "SELECT emp_id, emp_name, emp_dept FROM emp_tbl ORDER BY emp_name";
			$result = $this->dbname->query($query);
			
			if($result === false){
				error_log("Database query failed: " . $this->dbname->error);
				return $employees;
			}
			
			if($result->num_rows > 0){
				while($row = $result->fetch_assoc()){
					$employees[] = $row;
				}
			}
			
			return $employees;
		}
	}



?>

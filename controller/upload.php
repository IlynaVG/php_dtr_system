<?php
	require_once('../model/employee.php');

	error_reporting(0);
	
	if(isset($_POST['submit'])){

		$employee = new Employee();

	/*---------------------------DELETE DATA on TABLES-------------------------*/
		$employee->delDate();
		$employee->delAttendanceLogs();

	/*--------------------------------CREATE DATE-----------------------------*/
		$generateDate = $employee->generateDate($_POST['startDate'], $_POST['endDate']);

	/*--------------------------------FILE UPLOAD-----------------------------*/
		$employee->fileUpload($_FILES['file']['tmp_name']);


		echo '<script>alert("Attendance Logs was successfully uploaded!!!"); location="../index.php";</script>';


	}
?>
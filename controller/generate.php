<?php 

	include '../model/Employee.php';

	error_reporting(0);
	$gen = new employee;

	/*---------------------------DELETE DATA on TABLES-------------------------*/
	$gen->delInfoTable();
	$gen->delEmployee();
	$gen->delTimeLogs();

	/*---------------------------GENERATE TIME LOGS of EMPLOYEES-------------------------*/
	$gen->uploadEmployee();
	$gen->generateTimeLogs();
	$gen->splitTimeLogs();

	echo '<script>alert("Attendance Logs was successfully generated!!!"); location="../view/viewEmployee.php";</script>';
?>


<?php
	
	require_once('../model/employee.php');

	$employee = new Employee();

		$employee->delDate();
		$employee->delAttendanceLogs();
		$employee->delInfoTable();
		$employee->delEmployee();
		$employee->delTimeLogs();
?>
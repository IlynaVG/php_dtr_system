<?php
	session_start();
	
	define("DB_SERVER", "localhost");
	define("DB_UNAME", "root");
	define("DB_PASS", "");
	define("DB_NAME", "das_db");

	$conn = new mysqli(DB_SERVER,DB_UNAME,DB_PASS,DB_NAME);
?>
<?php
# database credentials and connection
	global $db;
	$servername = "localhost";
	$username = "***USERNAME***";
	$password = "***PASSWORD***";
	$db = "***DATABASE***";
	$conn = new mysqli($servername, $username, $password, $db);
	if ($conn->connect_error) {
		die("Connection failed: " . $conn->connect_error);
	}
?>

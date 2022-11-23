<?php
// receive values
if (strlen($_POST['user']) != 0) { $user = $_POST['user']; }
else { die("Sorry, the username was not transmitted properly. Please go back and try again."); }
if (strlen($_POST['pw']) != 0) { $pw = $_POST['pw']; }
else { die("Sorry, the password was not transmitted properly. Please go back and try again."); }


// CREATE PASSWORD HASHES
$hash = password_hash($pw, PASSWORD_DEFAULT);


if (password_verify($pw, $hash)) {
	// CHECK IF USER EXISTS
	include("../access.inc");
	$conn = new mysqli($servername, $username, $password, $db);
	if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
	$sql = "SELECT `pid` FROM `users` WHERE `name` = '".$user."'";
	$results = $conn->query($sql);
	if (mysqli_num_rows($results) == 0) {
		// CREATE USER
		$sql = "INSERT INTO `users` (`pid`, `name`, `pw`) VALUES (NULL, '".$user."', '".$hash."')";
		// EXECUTE QUERY
		if( $conn ->query($sql) ) { print("User ".$user." successfully created!"); }
		else { print("Database Error: Unable to create user record."); }
	}
	else { print("User already exists. Please choose another user name."); }
	$conn->close();

} else { die("Sorry, the password was not transmitted properly. Please go back and try again."); }

?>
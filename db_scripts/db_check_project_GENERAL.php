<?php

$user = $_POST['user'];
$project = $_POST['project'];
include("../access.inc");


$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
//echo "Connected successfully";


// get user ID
$sql="SELECT `pid` from `users` WHERE `name` = '".$user."'";
$results = $conn->query($sql);
if (mysqli_num_rows($results) == 1) {
	$row = mysqli_fetch_assoc($results);
	$user_id = $row['pid'];
} else {
	$user_id = false;
}


// get project details
$sql = "SELECT * from `project_".$project."` WHERE `u_id` = ".$user_id." ORDER BY `round_id` DESC";
if($conn ->query($sql)) {
	$results = $conn->query($sql);
	$options = '<option value="startnewround">Start new round</option>';
	if (mysqli_num_rows($results) > 0) {
		// handle result rows
		$list_of_roundids = array();
    	while($row = mysqli_fetch_assoc($results)) {
			// check if round id has not yet been encountered
			if ( ! in_array($row['round_id'], $list_of_roundids) ) {
				// push round id to list of round ids
				array_push($list_of_roundids, $row['round_id']);
				// create dropdown menu options
				$options = $options.'<option value="'.$row['id'].'">Round '.$row['round_id'].' (created on: '.$row['timestamp'].')'.$project.'</option>';
			}
		}
	}
	print $options;
} else {
	//if unable to create new record
	print("Database Error!");
	//print($sql);
}
$conn->close();

?>

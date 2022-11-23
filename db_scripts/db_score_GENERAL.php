<?php

$project = $_POST['project'];
$user_id = $_POST['user_id'];
$round_id  = $_POST['round_id'];
$img_id  = $_POST['img_id'];
$score   = $_POST['score'];
$scorename    = $_POST['scorename'];

include("../access.inc");
$conn = new mysqli($servername, $username, $password, $db);
if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$sql="UPDATE `project_".$project."` SET `".$img_id."` = '".$score."' WHERE `project_".$project."`.`u_id` = ".$user_id." AND `project_".$project."`.`round_id` = ".$round_id." AND `project_".$project."`.`type` = '".$scorename."'";
if( $conn ->query($sql) ) {
	print($score);
} else {
	//if unable to create new record
	print("Database Error!");
}

$conn->close();

?>

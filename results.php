<?php
include("../access.inc");
	
function get_user_id($user) {
	global $conn;
	$sql="SELECT `pid` from `users` WHERE `name` = '".$user."'";
	$result = $conn->query($sql);
	if (mysqli_num_rows($result) == 1) {
    	$row = mysqli_fetch_assoc($result);
    	$user_id = $row['pid'];
	} else {
    	$user_id = false;
	}
	return $user_id;
}

if (strlen($_POST['user']) != 0 && strlen($_POST['project']) != 0 && strlen($_POST['round']) != 0) {
	$user = $_POST['user'];
	$project = $_POST['project'];
	$round_id = $_POST['round'];
	$user_id = get_user_id($user);
	if($user_id == false) { die("User not found..."); }
} else {
	alert("Data not received!");
	die();
}

// used instead of the deprecated mysql_field_name()
function mysqli_field_name($result, $field_offset)
{
    $properties = mysqli_fetch_field_direct($result, $field_offset);
    return is_object($properties) ? $properties->name : false;
}
$sql="SELECT * from `project_".$project."` WHERE `u_id` = '".$user_id."'";
$result = $conn->query($sql);

if (!$result) die('Couldn\'t fetch records');

$headers = $result->fetch_fields();
foreach($headers as $header) {
    $head[] = $header->name;
}
$fp = fopen('php://output', 'w');

if ($fp && $result) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="'.$project.'_'.$user.'_scorenadoresults.csv"');
    header('Pragma: no-cache');
    header('Expires: 0');
    fputcsv($fp, array_values($head)); 
    while ($row = $result->fetch_array(MYSQLI_NUM)) {
        fputcsv($fp, array_values($row));
    }
    die;
}
//	$conn->close();*/
?>
<?php
# SCORENADO
# a customizable, user-friendly and open-source visual assessment tool for histological slides
##############################################################################################
# stefan reinhard, micha eichmann
# university of bern, institute of pathology
# version: 0.1 (preliminary release)

$scorenado_project_gallery = "demo_project";
$scorenado_project_name = "demo project preliminary";

$var1 = true;
$var2 = true;

$var1_button = true;

$var1_name = "intensity";
$var2_name = "percentage";

// DONT CHANGE BELOW

	include("../access.inc");

if ($_POST['project']) {
	//****** START SCORING
?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
	<meta name="description" content="Fast scoring tool">
	<link rel="icon" href="dist/imgs/scorenado_icon.png">
	<title>Scorenado</title>
	<!-- Bootstrap core CSS -->
	<link href="dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- Custom styles for this template -->
	<link href="dist/css/custom_rubin.css" rel="stylesheet">
	<script src='dist/js/jquery-3.2.1.min.js'></script>
</head>
<body>

<?php

// DATABASE INTERACTIONS
function get_user_id($user) {
	global $conn;
	$user_id = false;
	$sql="SELECT `pid` from `users` WHERE `name` = '".$user."'";
	$result = $conn->query($sql);
	if (mysqli_num_rows($result) == 1) {
		$row = mysqli_fetch_assoc($result);
		$user_id = $row['pid'];
	}
	return $user_id;
}

function get_max_round_id($project, $user_id) {
	global $conn;
	$round_id = 1;
	$sql="SELECT `round_id` from `project_".$project."` WHERE `u_id` = ".$user_id." ORDER BY `round_id` DESC";
	$result = $conn->query($sql);
	if (mysqli_num_rows($result) > 0) {
		$row = mysqli_fetch_assoc($result);
		$round_id = $row['round_id'];
		$round_id++;
	}
	return $round_id;
}

function setup_new_round($project, $user_id, $round_id, $vartype) {
	global $conn;
	// setup new round row
	$sql = "INSERT INTO `project_".$project."` (`id`, `timestamp`, `u_id`, `round_id`, `type`) VALUES (NULL, NOW(), '".$user_id."', '".$round_id."', '".$vartype."')";
	// EXECUTE QUERY
	if( $conn ->query($sql) ) {} else {
		//if unable to create new record
		print("Database Error: Unable to create record.");
	}
}
function load_round($project, $id, $vartype) {
	global $conn;
	$row = false;
	$sql="SELECT * from `project_".$project."` WHERE `id` = '".$id."' AND `type` = '".$vartype."'";
	$result = $conn->query($sql);
	if (mysqli_num_rows($result) == 1) {
		$row = mysqli_fetch_assoc($result);
		unset($row['id']);
		unset($row['timestamp']);
		unset($row['u_id']);
		unset($row['type']);
	}
	return $row;
}
function load_further_round($project, $user_id, $round_id, $vartype) {
	global $conn;
	$row = false;
	$sql="SELECT * from `project_".$project."` WHERE `u_id` = '".$user_id."' AND `round_id` = '".$round_id."' AND `type` = '".$vartype."'";
	$result = $conn->query($sql);
	if (mysqli_num_rows($result) == 1) {
		$row = mysqli_fetch_assoc($result);
		unset($row['id']); // remove items 0-4
		unset($row['timestamp']);
		unset($row['u_id']);
		unset($row['round_id']);
		unset($row['type']);
	}
	return $row;
}
function create_project_table_if_needed($project, $imgcols) {
	global $conn;
	$sql = "CREATE TABLE IF NOT EXISTS `project_".$project."` ( `id` INT NOT NULL AUTO_INCREMENT , `timestamp` DATETIME NOT NULL , `u_id` INT NOT NULL , `round_id` INT NOT NULL , `type` TEXT NOT NULL , ";

	  foreach ($imgcols as &$value) { $value = "`".$value."` TEXT "; }
			unset($value);
			ksort($imgcols);
			$imgcols = array_values($imgcols);
			$imgcols = implode(" , ", $imgcols);
			$sql = $sql.$imgcols." , PRIMARY KEY (`id`)) ENGINE = MyISAM;";

			//print $sql;
			// execute query
			if( $conn ->query($sql) ) { $report = "created"; }
			else{ die("Database Error! Unable to create new project table."); }

	return $report;
}

// CHECK RECEIVED FORM VALUES
if (strlen($_POST['user']) != 0) {
	$user = $_POST['user'];
	$user_id = get_user_id($user);
	if($user_id == false) { die("User not found..."); }
} else {
	echo '<script language="javascript" type="text/javascript">';
	echo 'alert("Sorry, the username was not transmitted properly. Please go back and try again.")';
	echo '</script>';
	exit;
}
if (strlen($_POST['project']) != 0) {
	$project = $_POST['project'];
	if ( $project == 'users' ) { die("Project name 'users' is not allowed!"); }

	// LOAD IMAGE FILE NAMES
  $dir    = $_SERVER["DOCUMENT_ROOT"]."/".$scorenado_project_gallery."/".$project;
	$files1 = scandir($dir);
	function keepimagefiles($item) {
		return(substr($item,0,1) !== ".")&&($item !== "Thumbs.db");
	}
	$files1 = array_filter($files1, "keepimagefiles");
	$n_files = count($files1);

	// STRAIGHTEN OUT KEYS
	ksort($files1);
	$files1 = array_values($files1); // sorted by original key order

	if (strlen($_POST['round']) != 0) {
		$id = $_POST['round']; // the value of round is actually the unique row id OR zero which means "start new round"
		if ($id == "startnewround") {
			$_POST['round'] = 1;
			// start new round
			$created = create_project_table_if_needed($project, $files1);

			$round_id = get_max_round_id($project, $user_id);
				setup_new_round($project, $user_id, $round_id, "scoreVar1");
			if ($var2)
				setup_new_round($project, $user_id, $round_id, "scoreVar2");
			$scores_scoreVar1 = false;
		} else {
			// load indicated round and continue from there
			$scores_scoreVar1 = load_round($project, $id, "scoreVar1");
			$round_id = $scores_scoreVar1['round_id'];
			unset($scores_scoreVar1['round_id']);
			if ($var2)
				$scores_scoreVar2 = load_further_round($project, $user_id, $round_id, "scoreVar2");
		}
	} else {
		echo '<script language="javascript" type="text/javascript">';
		echo 'alert("Sorry, round was not transmitted properly. Please go back and try again.")';
		echo '</script>';
		exit;
	}
}
else {
	echo '<script language="javascript" type="text/javascript">';
	echo 'alert("Sorry, the project title was not transmitted properly. Please go back and try again.")';
	echo '</script>';
	exit;
}


// CREATE SHUFFLED FILES ARRAY: i => SHUFFLED_FILE
function array_shuffle($array) {
	if (shuffle($array)) {
		return $array;
	} else {
		return FALSE;
	}
}
$shuffled_files = array_shuffle($files1);

?>

<!-- FIXED NAVBAR -->
<nav class="navbar navbar-default navbar-fixed-top">
  <div class="container">
	<div class="navbar-header">
	  <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
		<span class="sr-only">Toggle navigation</span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
		<span class="icon-bar"></span>
	  </button>
	  <img src="dist/imgs/scorenado.png" height="30" width="30" vspace="10"><a class="navbar-brand">Scorenado</a>
	</div>
	<div id="navbar" class="navbar-collapse collapse">
	  <ul class="nav navbar-nav">
		  <li><a href="index.php">Home</a></li>
		<li><a href="<?php print $_SERVER['PHP_SELF'];?>">Load Project</a></li>
		<li class="active"><a>Scoring</a></li>
	  </ul>
	  <ul class="nav navbar-nav navbar-right">
		  <li><a>Scored: <span id="current_sc">0</span> / <?php echo $n_files; ?></span></a></li>
		  <li><a>Position: <span id="current_i">0</span> / <?php echo $n_files; ?></span></a></li>
		  <li><a><?php echo $project; ?></a></li>
		<li><a><?php echo $user; ?></a></li>
	  </ul>
	</div>
  </div>
</nav>

<!-- START -->
<div class="container" id="start">
	<div class="jumbotron">
		<h1 id="keyh1">Press &rarr; to start scoring</h1>
		<p>Hot keys are indicated by an <u>u</u>nderscored letter.<br />
		Use the arrow keys to move forward (&rarr;) and backward (&larr;).</p>
	</div>
</div>

<!-- IMAGES -->
<div class="container images" style="width: 100% !important;">
	<?php

	// PRELOAD ALL IMAGES AND CLASS TAGS INTO HTML
	//print '<span style="display:none;" id="statistics">Image <span id="current_i">0</span> / '.$n_files.'</span><br>';
	foreach ($files1 as $filename) {
		//print $filename.'<br>';
		print '<div style="display:none;" id="'.explode('.',$filename)[0].'">';
	print '<div class="left">';
		print '<img src = "/'.$scorenado_project_gallery.'/'.$project.'/'.$filename.'" style = "margin-right: 50px; position: fixed;  top: 50px; bottom: 0; left: -300px;right: 0; max-height: 94%;  margin: auto;overflow: auto;">';

			print '</div><div id="tag_'.explode('.',$filename)[0].'" class="legend">';
			print '<div class="legenddiv">';
			//start1
			
			if ($var1) {
				print '<label class="labelline1">'.$var1_name.'</label>';
				$var1_type = 'type="number"';
				if ($var1_button) {
					$var1_type = 'type="hidden"';
					print '<br><button class="btn btn-lg btn-default legendbtn polypclassbtn" name="btn10" onclick="scoreButton(this,\''.explode('.',$filename)[0]."_scoreVar1".'\',0)" id="b1'.explode('.',$filename)[0].'_0" >0 (<u>y</u>)</button>
					<button class="btn btn-lg btn-default legendbtn polypclassbtn" name="btn11" onclick="scoreButton(this,\''.explode('.',$filename)[0]."_scoreVar1".'\',1)" id="b1'.explode('.',$filename)[0].'_1" >1+ (<u>x</u>)</button>
					<button class="btn btn-lg btn-default legendbtn polypclassbtn" name="btn12" onclick="scoreButton(this,\''.explode('.',$filename)[0]."_scoreVar1".'\',2)" id="b1'.explode('.',$filename)[0].'_2" >2+ (<u>c</u>)</button>
					<button class="btn btn-lg btn-default legendbtn polypclassbtn" name="btn13" onclick="scoreButton(this,\''.explode('.',$filename)[0]."_scoreVar1".'\',2)" id="b1'.explode('.',$filename)[0].'_3" >3+ (<u>v</u>)</button>';
				}
				print '<input class="numberinput input_btn" '.$var1_type.'  name="scoreVar1" min="0" id="'.explode('.',$filename)[0].'_scoreVar1"></input>';
			}

			if ($var2) {
				print '<br><label class="labelline1">'.$var2_name.'</label>';
				print '<input class="numberinput input_btn" type="number" name="scoreVar2" min="0" id="'.explode('.',$filename)[0].'_scoreVar2"></input>';
			}

?>
	  </div><div class="legenddiv">
				<div class="btn btn-lg btn-default legendbtn dontknowinvalidbtn" id="<?php echo explode('.',$filename)[0]; ?>_d"><u>D</u>on't know</div>
				<div class="btn btn-lg btn-default legendbtn dontknowinvalidbtn" id="<?php echo explode('.',$filename)[0]; ?>_i"><u>I</u>nvalid</div>
			</div>

				<div class="legenddiv">
					<div class="btn btn-lg btn-info legendbtn arrowbtn bck">&larr;</div>
					<div class="btn-lg btn-danger legendbtn arrowbtn scored" id="<?php echo explode('.',$filename)[0]; ?>_scored">&#10008;</div>
					<div class="btn btn-lg btn-info legendbtn arrowbtn fwd">&rarr;</div>
				</div>

			<div>
				<div class="controldiv">
					<button id="rearrange" class="btn btn-lg btn-warning controlbtn rearrange" title="This function is useful if you have unscored images distributed all over your project. This sorts all unscored images in the back. No data is lost or altered.">Sort images (scored & not) &raquo;</button>
					<form name="results" action="results.php" method="POST">
						<input type="hidden" name="user" value="<?php echo $user; ?>">
						<input type="hidden" name="project" value="<?php echo $project; ?>">
						<input type="hidden" name="round" value="<?php echo $round_id; ?>">
						<input type="submit" class="btn btn-lg btn-success controlbtn" value="Download Current Results &raquo;">
					</form>
				</div>
				core_id: <?php print explode('.',$filename)[0];  ?>
			</div>
			<?php
			print '</div>';
		print '</div>';
	}
	?>
</div>

<!-- THE END -->
<div class="container" id="theend" style="display:none;">
	<div class="jumbotron">
		<div id="alldone">
			<h1>Congratulations!</h1>
			<p>You've scored the whole project like a tornado.</p>
			<form name="results" action="results.php" method="POST">
				<input type="hidden" name="user" value="<?php echo $user; ?>">
				<input type="hidden" name="project" value="<?php echo $project; ?>">
				<input type="hidden" name="round" value="<?php echo $round_id; ?>">
				<input type="submit" class="btn btn-lg btn-success" value="Download Results &raquo;">
			</form>
		</div>
		<div id="notdoneyet">
			<h1>Nice run!</h1>
			<p>You went through the whole project, but there are still <b>some images left to score</b> (scored "Don't know" or with incomplete scoring)!<br>You can now collect all your unscored images and score those too.<br>Just click the sort button:</p>
			<button id="rearrange" class="btn btn-lg btn-warning rearrange" title="This function is useful if you have unscored images distributed all over your project. This sorts all unscored images in the back. No data is lost or altered.">Sort images (scored & not) &raquo;</button><br><br>
			<form name="results" action="results.php" method="POST">
				<input type="hidden" name="user" value="<?php echo $user; ?>">
				<input type="hidden" name="project" value="<?php echo $project; ?>">
				<input type="hidden" name="round" value="<?php echo $round_id; ?>">
				<input type="submit" class="btn btn-lg btn-success" value="Download Current Results &raquo;">
			</form>
		</div>
	</div>
</div>

<script>
$( document ).ready(function() {
	$("#someTextBox").focus();
	// IMPORT PHP VARIABLES
	<?php
	if ($var1)
		print "var scores_scoreVar1 =".json_encode($scores_scoreVar1).";
	";
	if ($var2)
		print "var scores_scoreVar2 =".json_encode($scores_scoreVar2).";
	";
	?>

	var n = <?php echo $n_files; ?>;
	var files = <?php echo json_encode($files1); ?>;
	var keypressblock = false;


	// DEFINE FUNCTIONS
	var hideimg = function() { 
		$('#'+shuffled_files[i].split('.')[0]).css('display','none');
	}
	var showimg = function() {
		$('#'+shuffled_files[i].split('.')[0]).css('display','inline');
		setTimeout(function(){
			$('#'+shuffled_files[i].split('.')[0]+'_scoreVar2').focus();
		}, 1);

		//$('#'+shuffled_files[i].split('.')[0]+'_scoreVar1').focus();
		// cursor can't be set to end in number input field
	}
	var validate_img_is_scored = function(file) {
		tag = file.split('.')[0];
		result = false;
		<?php
		if (($var1)&& ($var2) )
			print "if( ($('#'+tag+'_scoreVar1')[0].value != \"\") && ($('#'+tag+'_scoreVar2')[0].value != \"\")   ) { result = true; }";
		elseif (($var1) )
			print "if( ($('#'+tag+'_scoreVar1')[0].value != \"\")    ) { result = true; }";
		?>

		else if( $("#"+tag+'_i').hasClass("btn-danger") ) { result = true; }
		return result;
	}
	var set_is_scored = function(img, tag) {
		if( !is_scored[img] ) {
			is_scored[img] = true;
			++sc;
			update_sc_displayed();
			$("#"+tag+"_scored").html("&check;");
			$("#"+tag+"_scored").removeClass("btn-danger");
			$("#"+tag+"_scored").addClass("btn-success");
		}
	}
	var set_is_not_scored = function(img, tag) {
		if( is_scored[img] ) {
			is_scored[img] = false;
			--sc;
			update_sc_displayed();
			$("#"+tag+"_scored").html("&#10008;");
			$("#"+tag+"_scored").removeClass("btn-success");
			$("#"+tag+"_scored").addClass("btn-danger");
		}
	}
	var update_i_displayed = function(j) { $('#current_i').text(j+1); }
	var update_sc_displayed = function() { $('#current_sc').text(sc); }
	var update_permanenttag = function(k, i) { $('#tag_'+shuffled_files[i].split('.')[0]).text(k); }
	var update_all_polypdontknowinvalidtags = function(k, file, varname) {
		switch (k) {
			case "i":
				$("#"+file.split('.')[0]+"_"+k).removeClass( "btn-default" );
				$("#"+file.split('.')[0]+"_"+k).addClass( "btn-danger" );
				break;
			case "d":
				$("#"+file.split('.')[0]+"_"+k).removeClass( "btn-default" );
				$("#"+file.split('.')[0]+"_"+k).addClass( "btn-danger" );
				break;
				case "0":
					if (varname == "scoreVar1") {
						$("#b1"+file.split('.')[0]+"_"+k).addClass( "btn-primary" );
						$("#b1"+file.split('.')[0]+"_"+k).removeClass( "btn-default");
						$("#"+file.split('.')[0]+"_"+varname)[0].value = k;
						$("#"+file.split('.')[0]+"_"+varname).addClass( "numberscored" );
						$("#"+file.split('.')[0]+"_"+varname)[0].value = k;
					}
				break;
				case "1":
					if (varname == "scoreVar1") {
						$("#b1"+file.split('.')[0]+"_"+k).addClass( "btn-primary" );
						$("#b1"+file.split('.')[0]+"_"+k).removeClass( "btn-default");
						$("#"+file.split('.')[0]+"_"+varname).addClass( "numberscored" );
						$("#"+file.split('.')[0]+"_"+varname)[0].value = k;
					}
				break;
				case "2":
					if (varname == "scoreVar1") {
						$("#b1"+file.split('.')[0]+"_"+k).addClass( "btn-primary" );
						$("#b1"+file.split('.')[0]+"_"+k).removeClass( "btn-default");
						$("#"+file.split('.')[0]+"_"+varname).addClass( "numberscored" );
						$("#"+file.split('.')[0]+"_"+varname)[0].value = k;
					}
				break;
				case "3":
					if (varname == "scoreVar1") {
						$("#b1"+file.split('.')[0]+"_"+k).addClass( "btn-primary" );
						$("#b1"+file.split('.')[0]+"_"+k).removeClass( "btn-default");
						$("#"+file.split('.')[0]+"_"+varname).addClass( "numberscored" );
						$("#"+file.split('.')[0]+"_"+varname)[0].value = k;
					}
				break;

			case "default":
				$("#"+file.split('.')[0]+"_"+varname)[0].value = k;
				$("#"+file.split('.')[0]+"_"+varname).addClass( "numberscored" );
				if (varname == "scoreVar1") {
					$("#"+file.split('.')[0]+"_"+k).removeClass( "btn-default" );
					$("#"+file.split('.')[0]+"_"+k).addClass( "btn-primary" );
				}
		}
	}
	var ajax_polypbtn = function(img, k_send, c_send, tag, k) {
		$.ajax({
			url: "db_scripts/db_score_GENERAL.php",
			type: "POST",
			data: {project: '<?php echo $project; ?>', user_id: '<?php echo $user_id; ?>', round_id: '<?php echo $round_id; ?>', img_id: img, score: k_send, scorename: c_send},
			success: function(k_back) {
				// change button colors
				if (k_back == "Database Error!") { alert(k_back); }
				else {

					($("#tag_"+tag+" .legenddiv .dontknowinvalidbtn").not($("#"+k))).removeClass("btn-danger");
					($("#tag_"+tag+" .legenddiv .dontknowinvalidbtn").not($("#"+k))).addClass("btn-default");

					// store score in scores array
					if ( c_send == "scoreVar2" ) {
						scores_scoreVar2[shuffled_files[i]] = k_send;
						if ( scores_scoreVar1[shuffled_files[i]] == 'i' ) {
							$.ajax({
								url: "db_scripts/db_score_GENERAL.php",
								type: "POST",
								data: {project: '<?php echo $project; ?>', user_id: '<?php echo $user_id; ?>', round_id: '<?php echo $round_id; ?>', img_id: img, score: '', scorename: 'scoreVar1'},
								success: function(k_back) {
									// change button colors
									if (k_back == "Database Error!") { alert(k_back); }
									else { scores_scoreVar1[shuffled_files[i]] = ''; }
								},
							});
						}
					}
				}
			},
		});
			// check if image is scored and set its status accordingly
				if ( k_send == "" ) { set_is_not_scored(img, tag); }
				else {

					<?php
					if (($var1) && ($var2))
						print "if ( $(\"#\"+img.split('.')[0]+\"_scoreVar1\").hasClass(\"numberscored\") && $(\"#\"+img.split('.')[0]+\"_scoreVar2\").hasClass(\"numberscored\") ) { set_is_scored(img, tag); }";
					elseif (($var1))
						print "if ( $(\"#\"+img.split('.')[0]+\"_scoreVar1\").hasClass(\"numberscored\") ) { set_is_scored(img, tag); }";
					?>
					else { set_is_not_scored(img, tag); }
				}
	}
	var ajax_dontknowinvalidbtn = function(img, k_send, tag, k) {
		$.ajax({
			url: "db_scripts/db_score_GENERAL.php",
			type: "POST",
			data: {project: '<?php echo $project; ?>', user_id: '<?php echo $user_id; ?>', round_id: '<?php echo $round_id; ?>', img_id: img, score: k_send, scorename: 'scoreVar1'},
			success: function(k_back) {
				// change button colors
				if (k_back == "Database Error!") { alert(k_back); }
				else {
						($("#tag_"+tag+" .legenddiv .polypclassbtn").not($("#"+k))).removeClass("btn-primary");
					($("#tag_"+tag+" .legenddiv .polypclassbtn").not($("#"+k))).addClass("btn-default");
					$("#"+img.split('.')[0]+'_scoreVar1')[0].value = "";
					$("#"+img.split('.')[0]+'_scoreVar1').removeClass("numberscored");
					scores_scoreVar1[shuffled_files[i]] = k_send;


					$.ajax({
						url: "db_scripts/db_score_GENERAL.php",
						type: "POST",
						data: {project: '<?php echo $project; ?>', user_id: '<?php echo $user_id; ?>', round_id: '<?php echo $round_id; ?>', img_id: img, score: '', scorename: 'scoreVar2'},
						success: function(k_back) {
							// change button colors
							if (k_back == "Database Error!") { alert(k_back); }
							else {
								<?php
								if ($var2)
								print "$(\"#\"+img.split('.')[0]+'_scoreVar2')[0].value = null;$(\"#\"+img.split('.')[0]+'_scoreVar2').removeClass(\"numberscored\");scores_scoreVar2[shuffled_files[i]] = k_send;";							
								?>

				  ($("#tag_"+tag+" .legenddiv .dontknowinvalidbtn").not($("#"+k))).removeClass("btn-danger");
				  ($("#tag_"+tag+" .legenddiv .dontknowinvalidbtn").not($("#"+k))).addClass("btn-default");
				  ($("#tag_"+tag+" .legenddiv .polypclassbtn").not($("#"+k))).removeClass("btn-primary");
				  ($("#tag_"+tag+" .legenddiv .polypclassbtn").not($("#"+k))).addClass("btn-default");
				  $("#"+k).toggleClass( "btn-default" );
				  $("#"+k).toggleClass( "btn-danger" );
				  switch(k_send) {
					case "":
					  set_is_not_scored(img, tag);
					  break;
					case "d":
					  set_is_not_scored(img, tag);
					  break;
					case "i":
					  set_is_scored(img, tag);
					  break;
				  }


							}
						},
					});
				}
			},
		});
	}
	var fwd = function() {
		switch (i) {
			case -1:
				$('#start').css('display','none');
				$('#keyh1').css('display','none');
				//$('#legend').css('display','inline');
				i++;
				update_i_displayed(i);
				showimg();
				break;
			case n:
				break;
			case n-1:
				hideimg();
				i++;
				//$('#legend').css('display','none');
				if (sc == n) {
					$('#notdoneyet').css('display','none');
					$('#alldone').css('display','block');
				} else {
					$('#notdoneyet').css('display','block');
					$('#alldone').css('display','none');
				}
				$('#theend').css('display','block');
				break;
			default:
				hideimg();
				i++;
				showimg();
				update_i_displayed(i);
		}
	}
	var bck = function() {
		switch (i) {
			case 0:
				hideimg();
				$('#keyh1').css('display','inline');
				//$('#legend').css('display','none');
				$('#start').css('display','block');
				i--;
				update_i_displayed(i);
				break;
			case -1:
				break;
			case n:
				//$('#legend').css('display','inline');
				$('#theend').css('display','none');
				i--;
				update_i_displayed(i);
				showimg();
				break;
			default:
				hideimg();
				i--;
				showimg();
				update_i_displayed(i);
		}
	}
	var score = function(i, k, c) {
		ksplit = k.split('_');
		pc = ksplit[1];
		tag = ksplit[0];
		// figure out right ajax function
		if ( c == "scoreVar1" ) {
			ajax_polypbtn(shuffled_files[i], pc, c, tag, k);
		}
		if ( c == "scoreVar2" ) {
			ajax_polypbtn(shuffled_files[i], pc, c, tag, k);
		}
		if ( c == "dontknowinvalid" ) {
			// check if button was already clicked. if yes, delete DB entry
			if ( $("#"+k).hasClass("btn-danger") ) { k_send = ""; }
			else { k_send = pc; }
			ajax_dontknowinvalidbtn(shuffled_files[i], k_send, tag, k);
		}
	}
	var checknumberinput = function(inputid) {

		vartype = inputid.split('_')[1]

		if ($(inputid)[0].value == "") {
			$(inputid).removeClass("numberscored");
		}
		else {
			$(inputid).addClass("numberscored");
		}
		score(i, shuffled_files[i].split('.')[0]+'_'+$(inputid)[0].value, vartype);
	}
	var toggle_keys = function() {
		// show settings
		if($('#start').css('display') == 'block') {
			$('#start').slideUp();
			keypressblock = false;
		}
		else {
			$('#start').slideDown();
			keypressblock = true;
		}
	}

	// LOAD SCORES
	// check if we start a new round or whether we need to continue an already existing one
	var i = -1;
	var sc = 0;
	var shuffled_files = <?php echo json_encode($shuffled_files); ?>;
	if (scores_scoreVar1 == false) {
		// start new round
		<?php
		if ($var1)
			print "var scores_scoreVar1 = {};";
		if ($var2)
			print "var scores_scoreVar2 = {};";
	
		print "$.each ( files, function ( key, filename ) {";
			if ($var1)
				print "scores_scoreVar1[filename] = '';";
			if ($var2)
				print "scores_scoreVar2[filename] = '';";
		print "});";
		?>

		// setup array to store whether an image is completely scored or not
		var is_scored = {};
		$.each( scores_scoreVar1, function( value ) { is_scored[value] = false; });
		$('#start').css('display','block');

	} else {
		// update all number input values
		$.each( scores_scoreVar1, function( key, value ) {
			if( value != "" ) { update_all_polypdontknowinvalidtags(value, key, "scoreVar1"); }
		});
		$.each( scores_scoreVar2, function( key, value ) {
			if( value != "" ) { update_all_polypdontknowinvalidtags(value, key, "scoreVar2"); }
		});
		$.each( scores_scoreVar3, function( key, value ) {
	  	if( value != "" ) { update_all_polypdontknowinvalidtags(value, key, "scoreVar3"); }
		});
		
		// continue loaded round
		// setup array to store whether an image is completely scored or not
		var is_scored = {};
		$.each( scores_scoreVar1, function( value ) { is_scored[value] = false; });
		$.each( scores_scoreVar1, function( value ) {
			if( validate_img_is_scored(value) ) { set_is_scored(value, value.split('.')[0]); }
			else { set_is_not_scored(value, value.split('.')[0]); }
		});
		//console.log(is_scored);
		rearrange();
		if (i == n) {
			if (sc == n) {
				$('#notdoneyet').css('display','none');
				$('#alldone').css('display','block');
			} else {
				$('#notdoneyet').css('display','block');
				$('#alldone').css('display','none');
			}
			$('#theend').css('display','block');
			update_i_displayed(i-1);
		} else {
			showimg();
			$('#legend').css('display','inline');
			update_i_displayed(i);
		}
		update_sc_displayed();
	}

	// ARROW BUTTONS
	$(".fwd").click( function() { fwd(); });
	$(".bck").click( function() { bck(); });

	// NUMBER INPUT FIELD, CHECK
	$('.numberinput').on('input propertychange paste', function() {

		checknumberinput("#"+$(this).attr('id'));
	});
	$('.textinput').on('input propertychange paste', function() {

		checknumberinput("#"+$(this).attr('id'));
	});

	// POLYPCLASS & DYSPLASIA BUTTON ACTIONS
	$(".dontknowinvalidbtn").click( function() { score( i, $(this).attr('id'), "dontknowinvalid" ); });
	$(".polypclassbtn").click( function() { score( i, $(this).attr('id'), "score" ); });

	// WAIT FOR KEYSTROKE
	$(document).keypress(function(e) {
		switch (e.which) {
			case 121: if(!keypressblock && i != -1 && i != n){ $('#b1'+shuffled_files[i].split('.')[0]+'_0').click(); } break; // _Y_ (0)
			case 120: if(!keypressblock && i != -1 && i != n){ $('#b1'+shuffled_files[i].split('.')[0]+'_1').click(); } break; // _X_ (1+)
			case 99:  if(!keypressblock && i != -1 && i != n){ $('#b1'+shuffled_files[i].split('.')[0]+'_2').click(); } break; // _C_ (2+)
			case 118: if(!keypressblock && i != -1 && i != n){ $('#b1'+shuffled_files[i].split('.')[0]+'_0').click(); } break; // _v_ (3+)

			case 100: if(!keypressblock && i != -1 && i != n){ score(i, shuffled_files[i].split('.')[0]+'_d', 'dontknowinvalid'); } break; // _D ont know
			case 105: if(!keypressblock && i != -1 && i != n){ score(i, shuffled_files[i].split('.')[0]+'_i', 'dontknowinvalid'); } break; // _I_nvalid
			//case 115: if(i != -1 && i != n){ toggle_keys(); } break; // s
		}
	});
	$(document).keydown(function(event){
	var key = event.which;
		//alert(key);
		switch(key) {
			case 39:
				if(!keypressblock){ fwd(); } // Key right
				break;
			case 13:
				if(!keypressblock){ fwd(); } // Enter key
				break;
			case 37:
				if(!keypressblock){ bck(); } // Key left
				  break;
		}
	  });

	// SHUFFLE & REARRANGE IMAGES
	function shuffle(array) {
		var currentIndex = array.length, temporaryValue, randomIndex;

		// While there remain elements to shuffle...
		  while (0 !== currentIndex) {

			// Pick a remaining element...
			randomIndex = Math.floor(Math.random() * currentIndex);
			currentIndex -= 1;

			// And swap it with the current element.
			temporaryValue = array[currentIndex];
			array[currentIndex] = array[randomIndex];
			array[randomIndex] = temporaryValue;
		  }

		  return array;
	}


	function rearrange() {
		files_not_scored = [];
		files_scored = [];
		//console.log(scores);
		$.each( scores_scoreVar1, function( key, value ) {
			if ( is_scored[key] ) { files_scored.push(key); }
			else { files_not_scored.push(key); }
		});
		files_not_scored = shuffle(files_not_scored);
		files_scored = shuffle(files_scored);
		shuffled_files = files_scored.concat(files_not_scored);
		i = files_scored.length;
		sc = i;
	}

	$(".rearrange").click(function() {
		if (confirm('This function is useful if you have unscored images distributed all over your project. This sorts all unscored images in the back. No data is lost or altered. Are you sure you want to do this?')) {
			//console.log(JSON.stringify(scores));
			//console.log(JSON.stringify(shuffled_files));
			if (i < n) { hideimg(); }
			else { $('#theend').css('display','none'); }
			rearrange();
			if (i == n) {
				$('#legend').css('display','none');
				$('#theend').css('display','block');
				update_i_displayed(i-1);
			} else {
				showimg();
				$('#legend').css('display','inline');
				update_i_displayed(i);
			}
			update_sc_displayed();
		}
	});
});

function scoreButton(buttonElement,inputElement,score){
	$('input#'+inputElement).val(score);
	$('input#'+inputElement).trigger("propertychange");
	el_id = buttonElement.id.split("_")
	$("#"+el_id[0]+"_0").removeClass( "btn-primary" );
	$("#"+el_id[0]+"_1").removeClass( "btn-primary" );
	$("#"+el_id[0]+"_2").removeClass( "btn-primary" );
	$("#"+el_id[0]+"_3").removeClass( "btn-primary" );
	$(buttonElement).toggleClass( "btn-primary" );
}
</script>


	<!-- Bootstrap core JavaScript
	================================================== -->
	<script src="dist/js/bootstrap.min.js"></script>
</body>
</html>
<?php
$conn->close();
}
else {
	//****** START INDEX

	?>
	<!DOCTYPE html>
	<html lang="en">
	  <head>
		<meta charset="utf-8">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
		<meta name="description" content="Fast scoring tool">
		<link rel="icon" href="dist/imgs/scorenado_icon.png">

		<title>Scorenado</title>

		<!-- Bootstrap core CSS -->
		<link href="dist/css/bootstrap.min.css" rel="stylesheet">
		<!-- Custom styles for this template -->
		<link href="dist/css/custom.css" rel="stylesheet">
		<script src='dist/js/jquery-3.2.1.min.js'></script>

		<!-- GET PROJECT FOLDER NAMES -->
		<?php

		$galleryfolder = $_SERVER["DOCUMENT_ROOT"].'/'.$scorenado_project_gallery.'/';
		$projectfolders = glob($galleryfolder . '*' , GLOB_ONLYDIR);
		$gf_l = strlen($projectfolders) - strlen($galleryfolder);
		?>

		<script language="javascript">
			// FORM EVALUATION
			function validateForm() {
				var u = document.forms["login"]["user"].value;
				var p = document.forms["login"]["project"].value;
				var pw = document.forms["login"]["pw"].value;
				if (u == "")  {
					alert("Please provide your username.");
					return false;
				}
				if (p == "")  {
					alert("Please select a project to work on.");
					return false;
				}
				function check_pw(u, pw){
					$.ajax({
						url: "db_scripts/db_check_pw.php",
						type: "POST",
						data: {user: u, pw: pw},
						async: false,
						success: function(m) {
							if (m != true) {
								alert(m);
								pw_ok = false;
							} else {
								pw_ok = true;
							}
						}
					});
					return pw_ok;
				}
				if (pw == "")  {
					alert("Please indicate your password.");
					return false;
				} else {
					return check_pw(u, pw);
				}
			}

			// UPDATE ROUND DROPDOWN MENU
			$( document ).ready(function() {
				document.getElementById('project').onchange = function(){
					$('#round').empty();
					$('#round').append('<option value="startnewround">Start new round</option>');
					var u = document.forms["login"]["user"].value;
					var p = document.forms["login"]["project"].value;
					var currentVal = this.value;
					//alert (p);
					$.ajax({
						url: "db_scripts/db_check_project_GENERAL.php",
						type: "POST",
						data: {user: u, project: p },
						success: function(m) {
							if (m != "Database Error!") {
								// create dropdown print $options;
								$('#round').empty();
								$('#round').append(m);
							}
						}
					});
				}
			});

		</script>
	  </head>

	  <body>
		<div class="container">
		  <div class="jumbotron">
			  <div class="indexheader">
				  <img id="titlelogo" src="dist/imgs/scorenado.png" height="80" width="80">
				<div>
					<h1>Scorenado *</h1>
					<h2>Fast scoring tool</h2>
				<h3>Project: <?php print $scorenado_project_name; ?></h3><br>
				</div>
			</div>
			<div class="indexcontent">
				<p>Enter your details and select a slide to score.</p>
				<form name="login" action="<?php print $_SERVER['PHP_SELF'];?>" onsubmit="return validateForm()" method="POST">
					<p><label>Username </label>
					  <input name="user" type="text" size="25" style="margin-left: 10px;width:666px;" /></p>
					  <p><label>Password </label>
					  <input name="pw" type="password" size="25" style="margin-left: 12px;width:666px;"/></p>
					  <p><label>Slide </label>
					  <select name="project" id="project" style="margin-left: 62px;width:666px;padding: 5px 0px;" >
						  <option value="">---</option>
						  <?php
						foreach($projectfolders as $project) {
							  echo '<option value="'. substr($project, -$gf_l) .'">'. substr($project, -$gf_l) .'</option>';
						}
						  ?>
					  </select></p>
					  <p><label>Round</label>
					  <select name="round" id="round" style="margin-left: 46px;width:666px;padding: 5px 0px;">
						  <option value="startnewround">Start new round</option>
					  </select></p>
			  <br>
					  <p><input class="btn btn-lg btn-primary" name="mySubmit" type="submit" value="Load project &raquo;"/><br><br><br><a class="btn btn-lg btn-success" href="/index.php">home</a></p>
				</form>
			</div>
		  </div>
		</div>

		<!-- Bootstrap core JavaScript
		================================================== -->
		<script src="dist/js/bootstrap.min.js"></script>
	  </body>
	</html>
<?php
}
?>

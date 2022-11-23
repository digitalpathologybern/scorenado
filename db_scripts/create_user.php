<!DOCTYPE html>
<html lang="en">
  <head>
	<meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<!-- The above 3 meta tags *must* come first in the head; any other head content must come *after* these tags -->
	<meta name="description" content="Fast scoring tool">
	<link rel="icon" href="../dist/imgs/scorenado_icon.png">

	<title>Scorenado (beta, no support)</title>

	<!-- Bootstrap core CSS -->
	<link href="../dist/css/bootstrap.min.css" rel="stylesheet">
	<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
	<link href="../assets/css/ie10-viewport-bug-workaround.css" rel="stylesheet">
	<!-- Custom styles for this template -->
	<link href="../dist/css/custom.css" rel="stylesheet">
	<!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
	<!--[if lt IE 9]>
	  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
	  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
	<![endif]-->
	<script src='../dist/js/jquery-3.2.1.min.js'></script>
	
	<script language="javascript">
		// CREATE USER
		function create_user(u, pw){
			$.ajax({
				url: "db_create_new_user.php",
				type: "POST",
				data: {user: u, pw: pw},
				async: false,
				success: function(m) {
					alert(m);
				}
			});
		}
		
		// FORM EVALUATION
		function validateForm() {
			var u = document.forms["login"]["user"].value;
			var pw = document.forms["login"]["pw"].value;
			var pw2 = document.forms["login"]["pw2"].value;
			if (u == "") {
				alert("Please choose a username.");
				return false;
			}
			if (pw == "") {
				alert("Please choose a password.");
				return false;
			}
			if (pw != pw2) {
				alert("Passwords don't match.");
				return false;
			}
			$.ajax({
				url: "db_create_new_user.php",
				type: "POST",
				data: {user: u, pw: pw},
				async: false,
				success: function(m) {
					alert(m);
					return false;
				}
			});
		}
		
	</script>
  </head>
  
  
  <body>
	<div class="container">
	  <div class="jumbotron">
	  	<div class="indexheader">
	  		<img id="titlelogo" src="../dist/imgs/scorenado.png" height="80" width="80">
			<div>
				<h1>Scorenado *</h1>
				<h2>Create a new user</h2>
			</div>
		</div>
		<div class="indexcontent">
			<p>Enter your details to create a new user:</p>
			<form name="login" onsubmit="return validateForm()" method="POST">
				<p><label>Username: </label>
			  	<input name="user" type="text" size="25" /></p>
			  	<p><label>Password: </label>
			  	<input name="pw" type="password" size="25" /></p>
			  	<p><label>Repeat password: </label>
			  	<input name="pw2" type="password" size="25" /></p>
			  	<p><input class="btn btn-lg btn-primary" name="mySubmit" type="submit" value="Create new user &raquo;" />&nbsp;&nbsp;&nbsp;&nbsp;<a class="btn btn-lg btn-success" href="/index.php">home</a></p>
				
			</form>
		</div>
	  </div>
	* Beta version: no support
	</div>


	<!-- Bootstrap core JavaScript
	================================================== -->
	<script src="../dist/js/bootstrap.min.js"></script>
	<!-- IE10 viewport hack for Surface/desktop Windows 8 bug -->
	<script src="../assets/js/ie10-viewport-bug-workaround.js"></script>
  </body>
</html>

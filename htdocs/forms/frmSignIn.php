<?php
/**
 * The form and processing for signing in.
 * Assumption: jquery is already loaded on the page.
 **/
 

require_once("globals.php");
require_once("class.User.php");
ob_start();

// The first thing we want to do is check to see if they're already signed in.
if (isset($_SESSION['Email']) && strlen($_SESSION['Email'])>4 
	&& isset($_COOKIE['CkUserID']) && isset($_SESSION['UserID']) 
	&& $_COOKIE['CkUserID']==$_SESSION['UserID']) {
	//$u=unserialize($_SESSION['User']);
	echo "<script type='text/javascript'>\nwindow.location='/account/'\n</script>\n";
        echo "<p>Please wait while we take you to your <a href='/account/'>Account Page</a></p>.";
	exit();
}


// Set the errors to an empty array - no errors yet...
$Error=array();
$def['Email']='';

// It's a submission
if(count($_POST)>1) {
	// Filter the variables.
	$def['Email']=filter_input(INPUT_POST,'em',FILTER_VALIDATE_EMAIL);
	if(!$def['Email']) {
		$Error[]="You must include a valid email.";
		$def['Email']=$_POST['Email']; // Could strip quotes and tags
	}
	if(!isset($_POST['em']) || strlen($_POST['em'])<6 || strlen($_POST['em'])>30) {
		$Error[]="You must include a valid password.";
	}
	if(count($Error)<1) {
		// No problems, so attempts a signin
		$newu=new User();
		$tmpuid=$newu->verifyPassword($def['Email'],$_POST['em']);
		if($tmpuid<1) {
			$Error[]="Unknown email address or invalid password.";
		} else {
			$newu->signIn($tmpuid);
			exit();
		}
	}
}

printJScript();
printErrors();
printForm();


/**
 * Print the javascript section of the form.
 **/
function printJScript() {
?>

<script id="demo" type="text/javascript">
	$(document).ready(function() {
	$("#signinform").submit(function(e) {
		e.preventDefault();
		if(!isValidEmail($("#em").val()) { // the email field isn't valid.
			$("#em").addClass("inputError");
			$("#emErr").removeClass("hidden").html("A valid email is required.");
			return(false);
		}
		if($("#passwd").val().length<6) { // the email field isn't valid.
			$("#passwd").addClass("inputError");
			$("#pwErr").removeClass("hidden").html("A valid password is required.");
			return(false);
		}
		var fdata=$("#signinform").serialize();
		$.post("/forms/frmSignIn.php?rnd="+Math.floor(Math.random()*9999999),fdata, // cache breaker if needed...
			function(rdata) {
			  $("#signinwrap").html(rdata); // This is the id of the div containing the form
			}
		);
		return(false);
	});
});

// This function goes in js/functions.js
function isValidEmail(strEmail){
	var validRegExp   = /^([A-Za-z0-9_\-\.])+\@([A-Za-z0-9_\-\.])+\.([A-Za-z]{2,4})$/;
	if (strEmail.search(validRegExp) == -1) {
	  return false;
	}
	return true;
}
</script>



<?php
}



function printErrors($err) {
	if(count($err)<1) return();
	print("<ul class='error-list'>\n");
	foreach($err as $e) {
		print("<li>$e</li>\n");
	}
	print("</ul>\n");
}


function PrintForm() {
?>


<form id="signinform" class="sForm" method="post" action="#">
	<label id="lem" for="em">Email Address:</label>
	<input id="em" name="em" type="text" value="<? echo "$def[Email]"; ?>"/>
		<span class='hidden' id='emErr'></span>
	<label id="lpassword" for="passwd">Password: </label>
	<input id="passwd" name="passwd" type="password" maxlength="50" value="" />
		<span class='hidden' id='pwErr'></span>
	<label id="lsignupsubmit" for="signupsubmit"> &nbsp; </label>
	<input id="signinc" name="signinc" type="hidden" value="signin" />
	<div class="button button-orange" id="signinsubmit">
		<span class="form_button clearfix">
			<input type="submit" style="float:left; clear:both;" class="submit" name="submit" value="Sign In"/>
		</span>
	</div>
</form>


<?php
}
?>




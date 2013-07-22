<?php

/**
* User class - Extends the DB_User class and adds methods for all the basic user actions and requirements,
* including signing in/out, updating, setting cookies/sessions, etc.
* 
* @author Steve Cook <booyahmedia@gmail.com>
* @copyright 1998 - 2013 Stephen Cook
* @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
* @link http://booyahmedia.com/
* @version Release: @package_version@
* @todo Optimize some of the additional functionality.
*/

require_once("globals.php");
require_once("class.db.php");
require_once("class.db_classes.php");



/**
* User class - Extended from the fields in the User database table.
*/
class User extends DB_User {
	
	// An array of recently visted pages. Not saved - just here as an example.
	public $RecentPages;
	
	
	/**
	* Perform the signin function - load all the information, set session, set cookies, and do redirect.
	* @param mixed $uid  The UserID for the user.
	* @param mixed $remember  If greater than 0, set the cookie life longer. Reload session if it expires.
	* @param mixed $redirect    Perform a JAVASCRIPT redirect after the signin.
	*/
	public function SignIn($uid,$remember=0,$redirect=true) {
		//The UserID must be set! 
		if($uid<1) {
			return(false);
		}
		
		// connect to the database
		$link=db::factory();
				
		// Load the user object based on the integer, uid=UserID
		$this->load($uid);
		$ctm=time(); // The time for setting the cookie and for last signin timestamp
		
		// Set the session and cookie variables.
		$this->setSessionVars();
		$this->setCookieVars($remember);

		// Update the "lastStamp" timestamp, so we can track inactive and active users
		$q="UPDATE ".DB_NAME.".User SET LastStamp=$ctm WHERE UserID='$uid' LIMIT 1";
		$result=$link->query($q);

		// Perform a redirect if their redirect cookie is set and non-zero in length. Otherwise send to /account/
		$loc = (isset($_COOKIE['CkRedir']) && strlen($_COOKIE['CkRedir'])>0)?$_COOKIE['CkRedir']:"/account/";
		$expire_date=mktime(12,0,0,1, 1, 1990);
		setcookie("CkRedir",'',$expire_date,"/",COOKIE_DOMAIN);
		if(strpos($loc,"/ajax/")!==false) {
			$loc='/account/';
		}
                // check to see if they redirect
		if($redirect) { // Does it need to be a redirect with javascript? - used for ajax based signin forms.
			echo "<script type='text/javascript'>\nwindow.location='".$loc."';\n</script>\n";
			echo "<p>Please wait while we <a href='".$loc."'>redirect you</a>.</p>";
			exit(0);
		} else {    // Used when it's a full page form submit.
			header("Location: ".$loc);
			exit();
		}
		return(true); // Should never get here ;)
	}

	/**
	* Set the session variables that we'd like to keep handy on live pages. 
	* Also keep a fully serialized version of the whole user's information in the 'User' session key
	* 
	*/
	function setSessionVars() {
		@session_start();
		$_SESSION['Email']   = $this->Email;
		$_SESSION['UserID']  = $this->UserID;
		$_SESSION['Pass']    = $this->Pass;
		$_SESSION['Name']    = $this->Name;
		// Keep a serialized version of this entire object.
		$us=serialize($this);
		$_SESSION['User']=$us;
		return(true);
	}

	/**
	* Set all the required cookie variables. You can add more here, but most things are in the session variable.
	* 
	* @param mixed $remember Keep the user information handy incase they want to stay "signed in" for extended periods
	*/
	function setCookieVars($remember=true){
		$ctm=time();
		if($remember) {
			$ctmrem=$ctm+3600*24*90;    // Set the expiration date for remembering to 90 days.
			setcookie("CkRem",1,0,"/",COOKIE_DOMAIN);
		} else {
			$ctmrem=0;  // Set the cookies to expire whenever they close their browser.
			setcookie("CkRem",0,0,"/",COOKIE_DOMAIN);
		}
		//$sid=crypt($this->Pass,COOKIE_PASSWORD_SALT);
		//setcookie("CkSessID",$sid,0,"/",COOKIE_DOMAIN);
		setcookie("CkInTime",$ctm,0,"/",COOKIE_DOMAIN);
		setcookie("CkUserID",$this->UserID,0,"/",COOKIE_DOMAIN);
		setcookie("CkName",$this->Name,$ctmrem,"/",COOKIE_DOMAIN);
		setcookie("CkEmail",$this->Email,$ctmrem,"/",COOKIE_DOMAIN);
	}

	/**
	* Sign out a user by removing their cookies and destroying their session information.
	*/
	public function SignOut() {
		// Clear all the user's cookies and session information.
		$expire_date=mktime(12,0,0,1, 1, 1990);
		// Delete some/most cookies.
		setcookie('CkRem','', $expire_date,"/",COOKIE_DOMAIN);
		setcookie('CkInTime','', $expire_date,"/",COOKIE_DOMAIN);
		//setcookie("CkSessID",'',$expire_date,"/",COOKIE_DOMAIN);
		setcookie("CkUserID",'',$expire_date,"/",COOKIE_DOMAIN);
		setcookie("CkName",'',$expire_date,"/",COOKIE_DOMAIN);
		setcookie("CkRedir",'',$expire_date,"/",COOKIE_DOMAIN);
		unset($_SESSION['User']); // do it explicitly just to be sure.
		session_destroy();
	}

	
	/**
	* Hash the password so that it's really, really, REALLY, hard to determine the plaintext version
	* 
	* @param mixed $email The user's email
	* @param mixed $pass  The user's password
	* @return string $hash The hashed password with the salt prepended as the first 64 characters
	*/
	public function hashPassword($email,$pass) {
		// You should use a 256 bit (64 characters) long random salt
		$salt = hash('sha256', uniqid(mt_rand(), true) . PASSWORD_SALT . strtolower($email));
		// (yes, could be $this->Email, but I wanted to force it in the arguments.)
		$hash = $salt . $pass;  // Attach the 64char salt to the password
		// Hash the salted password a bunch of times
		for ( $i = 0; $i < PASSWORD_HASHES; $i ++ )  {
			$hash = hash('sha256', $hash);
		}
		// Prefix the hash with the salt so we can find it back later
		$hash = $salt . $hash;
		return($hash);
	}
	
	/**
	* verify that the given password is the correct password in the database.
	* 
	* @param mixed $email   The user's email.
	* @param mixed $pass    The attempted password. Should be parsed by PDO::parse, but always make sure.
	*/
	public function verifyPassword($email,$pass) {
		// Get the password from the database.
		$q='SELECT UserID,Pass FROM '.DB_NAME.".User WHERE Email LIKE :email LIMIT 1";
		$params=array(":email"=>$email);
		$link=db::factory();
		$r=$link->getRow($q,$params);
		
		// If that user Email doesn't exist, then return false - no way to sign in without an account!
		if(count($r)<1) return(false);
									  
		// The first 64 characters of the hash is the salt
		$salt = substr($r['Pass'], 0, 64);
		$hash = $salt . $pass;
		// Hash the password as we did before
		for ( $i = 0; $i < PASSWORD_HASHES; $i ++ ) {
			$hash = hash('sha256', $hash);
		}
		
		// Check to see if their attempt matches the hashed value in the database. If yes, sign them in!
		$hash = $salt . $hash;
		if ( $hash == $r['Pass'] ) {
			// Send back the verified User ID. (useful for signing them in!)
			return($r['UserID']);
		}
		// User not found. Return a 0 as the UserID.
		return(0);
	}
	
	
	/**
	* Check to see if the email is already registered.
	* 
	* @param mixed $name    The email to check
	* @param mixed $id      If it's their ID, then it's okay.
	*/
	public function uniqueEmail($email,$id) {
		//Name must be ESCAPED BEFORE SUBMITTING!
		$link=db::factory();
		$q="SELECT UserID,Email FROM ".DB_NAME.".User WHERE Email LIKE :email LIMIT 1";
		$result=$link->getArray($q,array(":email"=>$email));
		if(count($result)<1) return(true);
		if(isset($result['UserID']) && $result['UserID']<>$id) return(false);
		return(true);
	}
	
	
	/**
	* Check to see if the UserName is taken. Depends on if you want a unique "UserName" (nickname)
	* 
	* @param mixed $name    The UserName/nickname to check
	* @param mixed $id      If it's their ID, then it's okay.
	*/
	public function uniqueUserName($name,$id) {
		//Name must be ESCAPED BEFORE SUBMITTING!
		$link=db::factory();
		$q="SELECT UserID,UserName FROM ".DB_NAME.".User WHERE UserName LIKE :uname LIMIT 1";
		$result=$link->getArray($q,array(":uname"=>$name));
		if(count($result)<1) return(true);
		if(isset($result['UserID']) && $result['UserID']<>$id) return(false);
		return(true);
	}
	
	/**
	* Get the UserID of a User based on their email address
	* 
	* @param mixed $em The email address of the UserID to fetch
	*/
	public function getUserID($em) {
		$link=db::factory();
		$q="SELECT UserID FROM ".DB_NAME.".User WHERE Email LIKE :email AND Active>0 LIMIT 1";
		$result=$link->getArray($q,array(":email"=>$em));
		if(count($result)<1) {
			return(0);
		}
		return($result[0]['UserID']);
	}
									 
	
	public function redirect($url,$jscript=false) {
		if(strpos($url,"/ajax/")!==false) {
			$url='/account/';
		}
		if($jscript==true) {
			echo "<script type='text/javascript'>\nwindow.location='/'\n</script>\n";
			echo "<p>Please wait while we <a href='/'>redirect you</a>.</p>";
			exit(0);
		} else {         
			header("Location: $url");
			exit();
		}
		
	}

}

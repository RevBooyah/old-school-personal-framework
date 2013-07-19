<?php

namespace SCCFramework;

// Usually easiest to set this in web server conf files.
@session_start();

/* Showing all errors, warnings, and notices during development is wise. */
//error_reporting(E_ALL & ~E_NOTICE);
error_reporting(E_ALL);
ini_set("display_errors", 1);


/* Set all the site/domain settings for the site. */
define('DOMAIN',"mydomain.com");
define('URL_HOME',"http://www.".DOMAIN);
define('ROOT_DIR','/path/to/domain/directory/');
define('CDN_URL','http://cdn.'.DOMAIN);  // Useful if you keep images or css files on a CDN. Usually use a different TLD to avoid cookie issues

define("TOP_HOME",URL_HOME."/");
define("SUPPORT_DOMAIN","http://support".DOMAIN."/");
define("ADMIN_DOMAIN","http://support".DOMAIN."/");
// Domain for the cookies. Adding the . makes cookies universal across all subdomains - not generally good for CDNs with same TLD
define("COOKIE_DOMAIN",'.'.DOMAIN); 

// Password salt for hashing - this should be something unique.
define("PASSWORD_SALT","CHANGE THIS NOW! - Because if you leave it like this, anybody can decrypt your passwords!");
define("PASSWORD_HASHES",1000); // The number of times to hash the password. 1000 is good.

define("HTDOCS_DIR",ROOT_DIR.'htdocs/');
define("IMAGE_DIR",HTDOCS_DIR.'images/');
define("JSCRIPT_DIR",HTDOCS_DIR.'images/');
define("UPLOAD_DIR",ROOT_DIR."/upload/");
define("EMAIL_DIR",ROOT_DIR.'email/');
define("INCLUDE_DIR",ROOT_DIR.'lib/');
define("CACHE_DIR",ROOT_DIR."cache/");
define("TEMPLATE_DIR",ROOT_DIR."templates");
define("CLASS_DIR",INCLUDE_DIR);

// setting the version to "1" gets the latest version - or you can specify version
define('JQUERY_CDN','http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js');
define('JQUERYUI_CDN','http://ajax.googleapis.com/ajax/libs/jqueryui/1/jquery-ui.min.js');
// Additional CDN hosted libraries at: https://developers.google.com/speed/libraries/devguide

// Bootstrap - full responsive with icons
define("BOOTSTRAP_CDN",'http://netdna.bootstrapcdn.com/twitter-bootstrap/2.3.2/css/bootstrap-combined.min.css');
// Should be done with a link and not a script - to get file links, icons, etc.

// DB Definitions
define('DB_HOST','localhost');
define('DB_NAME','test');
define('DB_USER','SCook');
define('DB_PASS','');
define('OS_OWNER','The file owner - for permissions');
define('OS_GROUP','The file group - for multiple accounts');

// Email Definitions
define('SUPPORT_EMAIL','scook@booyahmedia.com');
define('FROM_EMAIL','SiteName <no-reply@'.DOMAIN.'>');

// To keep consistent date displays throughout the application
define('LONG_DATE',"F j, Y");
define('LONG_TIME',"F j, Y h:iA");
define('MEDIUM_DATE',"M j, Y");
define('SHORT_DATE',"m/d/y");
define('SHORT_TIME',"H:i");

// AWS Keys and buckets
define('AWS_ACCESSKEY','1234567890');
define('AWS_SECRETKEY','098343098kjlsdfa4joi4334/6Zm/yi81');
define('AWS_BUCKET','cdn.'.DOMAIN); // Set through DNS

define('FACEBOOK_APP_ID', '123456789098765');
define('FACEBOOK_SECRET', 'thisisthesecretthatfbgivesyou');
define('FACEBOOK_COOKIE', 'fbs_'.FACEBOOK_APP_ID);

// Some usual regex fomulas I use. Mostly I use filter_var now
define ('REGEX_USERNAME',"/^[0-9a-z_]{3,40}$/i");
define ('REGEX_NOT_USERNAME',"/[^0-9a-z_]/i");
define ('REGEX_SAFESTRING','/^[0-9a-z_\w \+\'\,\.\!\$\[\]\*\#\@]$/i');
define ('REGEX_EMAIL', '/^[\._0-9A-Za-z-]+@[0-9A-Za-z][-0-9A-Za-z\.]*\.[a-zA-Z]{2,4}$/' );
define ('REGEX_URL','/^https?:\/\/.*/');
define ('REGEX_SIMPLE',"/^[\._0-9A-Za-z \'\-]*$/");


// Extensions that I typically NEVER allow for upload.
$aBadExtensions=array("exe","bat","bin","386","asp","chm","com","dll","lnk","obj","reg","sys","vbs",
			"html","htm","php","js","scr",".css",".phtml",".php");

// I sometimes just put this in the template footer, but by having it here, I get some other benefits. 
$analytics_code=<<<__ANALYTICS__

<script type="text/javascript">

  var _gaq = _gaq || [];
  _gaq.push(['_setAccount', 'UA-NOTREAL-8']);
  _gaq.push(['_trackPageview']);

  (function() {
	var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
	ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
	var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
  })();

</script>

__ANALYTICS__;

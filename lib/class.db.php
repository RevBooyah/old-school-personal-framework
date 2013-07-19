<?php
/**
 * Class for a PDO database interface singleton.
 * Not a complete class - depends on the application, but covers most things right out of the gate.
 *
 * @author Steve Cook <booyahmedia@gmail.com>
 * @copyright 1998 - 2013 Stephen Cook
 * @license http://www.gnu.org/licenses/lgpl-3.0.txt GNU LESSER GENERAL PUBLIC LICENSE (LGPL) version 3
 * @link http://booyahmedia.com/
 * @version Release: @package_version@
 * @todo fomalize query building methods to move processing into the class.
 **/


// required to get the database user information and settings
require_once("globals.php");

// Define the abstract version of the class - in case I add support for other DB's (postgres,mongodb, cassandra,oracle etc.)
/**
 * Abstract class for all database interactions. It's a basic singleton pattern.
 * It's abstract in case I add support for other DB's (postgres,mongodb, cassandra,oracle etc.)
 *
 **/
abstract class db {
	/**
	 * This is the method to call when crateing the instance.  $db=db::factory();
	 * @param string|null 	The type of database object.
	 **/
	public static function factory($type="mysql") {
		return call_user_func(array($type, 'getInstance'));
	}
	abstract public function query($query);
	abstract public function getArray($query);
	abstract public function getRow($query);
	//abstract public function insertGetID($query);
	abstract public function clean($string);
}


/**
 * Implements the singleton interface for a mysql PDO connection 
 **/
class mysql extends db {
	protected static $instance = null; // the single instance
	protected $link;

	public static function getInstance() {
		if (is_null(self::$instance)) {
			self::$instance = new self;
		}
		return self::$instance;
	}
	
	// The __construct method is protected, so it can only be called from within the class (getInstance).
	protected function __construct() {
		
		// The old Non-PDO style
		//$this->link = mysql_connect($host, $user, $pass);
		//mysql_select_db($db, $this->link);
		
		// The PDO style
		$dsn="mysql:host=".DB_HOST.";dbname=".DB_NAME;	
		$this->link=new PDO($dsn,DB_USER,DB_PASS);

		// I skip an extra call to make a new connection - (usually a "getConnection()" method) because why else 
		// instantiate a DB class?
	
	}
	
	public function clean($string) {
		// Old mysql way
		//return mysql_real_escape_string($string, $this->link);

		// New PDO method is to use prepared statements. In case not using prepared, use this:
		return(PDO::quote($string));
		// But seriously - use the PDO::prepare() method!
	}
	
	
	public function getArray($query,$params=array()) {
		$q = $this->link->prepare($query);
		$q->execute($params);
		return($q->fetchAll());
	}

	/**
	* Get a single row of results (or return just the first row)
	* 	
	* @param mixed $query   The query to send to the database
	* @param mixed $params  an array to be parsed by the PDO::execute
	*/
	public function getRow($query,$params=array()) {
		$q = $this->link->prepare($query);
		$q->execute($params);
		return($q->fetch());
	}



	public function query($query,$params=array()) {
		$q = $this->link->prepare($query);
		$q->execute($params);
		return($q->fetch());
		/*
		$result=mysql_query($query, $this->link);
		return($result);
		 */
	}
	
	/*
	public function insertGetID($query) {
		$result=$this->query($query);
		if(!$result) {
			return(0);
		}
		return mysql_insert_id($this->link);
	}
	 */

}

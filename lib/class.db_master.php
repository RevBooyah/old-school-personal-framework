<?php
/**
 * Class for a PDO database interface singleton.
 * Not a complete class - depends on the application, but covers most things right out of the gate.
 *
 * @author Steve Cook <booyahmedia@gmail.com>
 * @copyright 1998 - 2016 Stephen Cook
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
     * @param string|null       The type of database object.
     **/
    public static function factory($type="mysql") {
        return call_user_func(array($type, 'getInstance'));
    }
    abstract public function query($query);
    abstract public function getArray($query,$params,$assocOnly);
    abstract public function getRow($query);
    abstract public function insertGetID($query);
    abstract public function clean($string);
}



/**
 * Implements the singleton interface for a mysql PDO connection 
 **/
class mysql extends db {
    protected static $instance = null; // the single instance
    //protected $link;
    public $link;

    public static function getInstance() {
        if (is_null(static::$instance)) {
            static::$instance = new static();
        }
        return static::$instance;
    }

    // The __construct method is protected, so it can only be called from within the class (getInstance).
    protected function __construct() {
        $dsn="mysql:host=".DB_HOST.";dbname=".DB_NAME;  
        $this->link=new PDO($dsn,DB_USER,DB_PASS);
    }

    private function __clone() {} // Do not allow cloning or deserialization.
    private function __wakeup() {}
	
    // But seriously - use the PDO::prepare() method!
    public function clean($string) {
        return(PDO::quote($string));
    }


    public function getArray($query,$params=array(),$assocOnly=true) {
        $q = $this->link->prepare($query);
        $res=$q->execute($params);
        if($res===false) return(false);
        if($assocOnly==true) {
            return($q->fetchAll(PDO::FETCH_ASSOC));
        }
        return($q->fetchAll());
    }

    /**
     * Get a single row of results (or return just the first row)
     *   
     * @param mixed $query   The query to send to the database
     * @param mixed $params  an array to be parsed by the PDO::execute
     */
    public function getRow($query,$params=array(),$assocOnly=true) {
        $q = $this->link->prepare($query);
        $res=$q->execute($params);
        if(!$res) return(false);
        if($assocOnly==true) return($q->fetch(PDO::FETCH_ASSOC)); 
        return($q->fetch());
    }



    public function query($query,$params=array()) {
        try {
            $q = $this->link->prepare($query);
            $res=$q->execute($params);
            if(!$res) return(false);
            if($q->rowCount()>0) {
                $out=$q->fetch();
                if($out==false) $out=true;
                return($out);
            } else {        
                return(1);
            }
        } catch (PDOException $e) {
            //$msg = "QUERY: There was a DB Exception:\n\nQ: $query\n\n".$e->getMessage()."\n"."Code: ".$e->getCode()."\n";
            //log(" QUERY DB Exception:",$msg); 
            return(1); // return 1 anyway...
        }
    }


    public function update($query,$params=array()) {
        try {
            $q = $this->link->prepare($query);
            $res=$q->execute($params);
            return($q->rowCount());
        } catch (PDOException $e) {
            //$msg = "UPDATE: There was a DB Exception:\n\nQ: $query\n\n".$e->getMessage()."\n"."Code: ".$e->getCode()."\n";
            //log(" UPDATE DB Exception:",$msg); 
            return(1); // return 1 anyway...
        }
    }
    public function insert($query,$params=array()){
        return($this->update($query,$params));
    }


    public function insertGetID($query,$params=array(),$showError=false) {
        try {
            $q = $this->link->prepare($query);
            $this->link->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 
            $result=$q->execute($params);
            if(!$result) {
                return(0);
            }
        } catch (PDOException $e) {
            //$msg = "INSERTGETID: There was a DB Exception:\n\nQ: $query\n\n".$e->getMessage()."\n"."Code: ".$e->getCode()."\n";
            //log("InsertGetID DB Exception:",$msg); 
            if($showError==true) echo "<div class='error'>".$e->getMessage()."</div>\n";
            return(0);
        }
        return $this->link->lastInsertId();
    }

    public function quoteAll($str,$len=255) {
        return(trim($this->link->quote(substr(trim($str),0,$len)),"'"));        
    }
}



class DBTable {
    // Child object/classes need to define the following fields.
    // dbFields are default values. objectID is the primary key, and tableName is, you guessed it, the table name.
    //public $dbFields = array('TablenameID'=>0,'UserID'=>0,'RequestID'=>0,'Location'=>'','Filename'=>'','FileExt'=>'','Filesize'=>0,'Active'=>1);
    //public $objectId = 'TablenameID';
    //public $tableName = 'Tablename';

    public function __construct($id=0,$getKids=false) {
        $link=db::factory();
        if($id>0){
            $this->load($id);
        } else {
            foreach($this->dbFields as $k=>$v) {
                $this->{$k} = $v;
            }
        }
        if($getKids==true) {
            $this->getChildren();
        } 
    }

    /**
     * Load(id)  if id=0, do nothing. if id>0, fetch row from table and load into the object.
     **/
    public function load($id) {
        if($id<1) return(0);
        $link=db::factory();
        $row=$link->getRow("SELECT * FROM ".DB_NAME.".".$this->tableName." WHERE ".$this->objectId." = $id AND Active>0 LIMIT 1");
        if(count($row)<1) return(0);
        $this->loadFromArray($row,true);
        return((method_exists($this,'customLoad'))?$this->customLoad():1);
    }


    /**
     * getChildren.  Based on the db tables that have this primary key as a field, retrieve all of 
     * the child rows as an array of those objects.
     * If obj is a string, juse fetch those child rows.
     * If there is a fieldname, use that as the object variable to store the specific children.
     **/
    public function getChildren($obj='',$fieldName='') {
        $link=db::factory();
        if(is_string($obj) && strlen($obj)>1 && in_array($obj,$this->subObjects)) {
            $field = (strlen($fieldName)>0)?$field=trim($fieldName):"a".$v; 

            $row=$link->getArray("SELECT * FROM ".DB_NAME.".$v WHERE ".$this->objectId." = ".$this->{$this->objectId}." AND Active>0 LIMIT 1000");
            if(!is_array($row) || count($row)<1) return(false);

            if(stream_resolve_include_path("class".$v.".php")==false) {
                $className='stdClass';
            } else {
                $className=$v;
                require_once("class.".$v.".php");
            }
            foreach($row as $o) {
                $this->{$field}[$o[$v."ID"]]= new $v();
                $this->{$field}[$o[$v."ID"]]->loadFromArray($o);
            }
        } else if(isset($this->subObjects) && count($this->subObjects)>0) {
            foreach($this->subObjects as $k=>$v)  {
                $field="a".$v; 
		$this->{$field}=array();
                require_once("class.".$v.".php");
                $row=$link->getArray("SELECT * FROM ".DB_NAME.".$v WHERE ".$this->objectId." = ".$this->{$this->objectId}." AND Active>0 LIMIT 1000");
                if(is_array($row) && count($row)>0) { // $row could be "false"
                    foreach($row as $o) {
                        $this->{$field}[$o[$v."ID"]]= new $v();
                        $this->{$field}[$o[$v."ID"]]->loadFromArray($o);
                    }
                }
            }
        }
        return(true);
    }

	/**
	 * Save the object - and set the objId if it's set.
	 **/
    public function save($id=0) {
		//prePrint("SAVING!");
		//prePrint(debug_backtrace());

        $aupd=array(); $str=array();
        if(method_exists($this,'customSave')) {
            return($this->customSave($id));
        }
        $objID=$this->objectId;
        $link=db::factory();
        if($this->{$objID}>0) $id=$this->{$objID};
        if($id==0) {
            $this->{$objID}='NULL';
            foreach($this->dbFields as $k=>$v) {
                $str[]=':'.strtolower($k);
                $aupd[":".strtolower($k)]=$this->{$k}; 
            }
            $q="INSERT INTO ".DB_NAME.".".$this->tableName." (".implode(",",array_keys($this->dbFields)).") VALUES (".implode(",",$str).")"; 
            $id=$link->insertGetID($q,$aupd);
            $this->{$objID}=$id;
        } else {
            foreach($this->dbFields as $k=>$v) {
                $str[]=" `$k` = :".strtolower($k);
                $aupd[":".strtolower($k)]=$this->{$k}; 
            }
            $query="UPDATE ".DB_NAME.".".$this->tableName." SET ".implode(",",$str)." WHERE ".$objID." = $id LIMIT 1";
            $q = $link->link->prepare($query);
            $res=$q->execute($aupd);
            $id=($id==true)?$this->{$objID}:0;
        }
        if($id<1) return(0);
        return($id);
    }

    public function delete($id=0) {
        $id=intval($id);
        if($id<1) return(0);
        $link=db::factory();
        $row=$link->query("DELETE FROM ".DB_NAME.".".$this->tableName." WHERE ".$this->objectId." = $id AND Active>0 LIMIT 1");
        return($row);
    }


    public function update($aUpdates,$updateCurrent=true) {
		//prePrint("UPDATING");
		//prePrint(debug_backtrace());
        $link=db::factory();
        $str=array(); $aupd=array();
        foreach($aUpdates as $k=>$v) {
            $str[]=" `$k` = :".strtolower($k);
            $aupd[":".strtolower($k)]=$v;
            if($updateCurrent==true) $this->{$k} = $v;  // Set it for the current item.
        }
        $id=$this->{$this->objectId};
        $q="UPDATE ".DB_NAME.".".$this->tableName." SET ".implode(',',$str)." WHERE $this->objectId=$id LIMIT 1";
        $sth=$link->link->prepare($q);
        return($sth->execute($aupd));
    }

    public function updateFields($aUpdates,$updateCurrent=true) {
		//prePrint("UPDATEFIELDS");
		//prePrint(debug_backtrace());
        $link=db::factory();
        $str=array(); $aupd=array();
        foreach($aUpdates as $k=>$v) {
            $str[]=" `$k` = :".strtolower($k);
            $aupd[":".strtolower($k)]=$v;
            if($updateCurrent==true) $this->{$k} = $v;  // Set it for the current item.
        }
        $id=$this->{$this->objectId};
        $q="UPDATE ".DB_NAME.".".$this->tableName." SET ".implode(',',$str)." WHERE $this->objectId=$id LIMIT 1";
        $sth=$link->link->prepare($q);
        return($sth->execute($aupd));
    }





    public function search($aWhere,$limit=1) {
        $link=db::factory();
        $w = '';
        $str=array();
        $AND="";
        foreach($aWhere as $k=>$v) {
            $w.=$AND." $k = :".strtolower($k);
            $aupd[":".strtolower($k)]=$v;
            $AND=" AND ";
        }

        if($limit<2) {
            $q="SELECT * FROM ".DB_NAME.".".$this->tableName." WHERE ".$w." AND Active>0 LIMIT 1";
            $row=$link->getRow("SELECT * FROM ".DB_NAME.".".$this->tableName." WHERE ".$w." AND Active>0 LIMIT 1",$aupd);
            if(!is_array($row)) return(array());
            return($row);
        } else {
            $q="SELECT * FROM ".DB_NAME.".".$this->tableName." WHERE ".$w." AND Active>0 LIMIT 1";
            $row=$link->getArray("SELECT * FROM ".DB_NAME.".".$this->tableName." WHERE ".$w." AND Active>0 LIMIT $limit",$aupd);
            if(!is_array($row)) return(array());
            return($row);
        }
    }

    public function _toJson() {
        //return(json_encode($this));
        $out = get_object_vars($this);
        foreach($out as &$v){
            if(is_object($v) && method_exists($v,'_toJson')){
                $v = $v->_toJson();
            }
        }
        return json_encode($out);
    }
    
    /**
     * Take an associative array and load it into the object. 
     * Doesn't check to see if the fields are defined for the object. 
     * If the array has numerical indexes, setting nonNumeric to true will filter them.
     * If you fetch with ASSOC only, then you won't need the nonnumeric filter.
     **/
    public function loadFromArray($arr,$nonNumeric=true) {
        if(!is_array($arr) || count($arr)<1) return(0);
        foreach($arr as $k=>$v) {
            if($nonNumeric==false || (!intval($k)>0 && $k!="0")) {
                $this->{$k}=$v;
            }
        }
        return(1);
    }

    public function __toString() {
	return("<pre style='font-size: 0.8em;'>\n".print_r($this,true)."</pre>\n");
    }

	public function sf($str,$maxLen=200) {
		$str=trim($str);

		$str=strip_tags($str);

		$str = filter_var($str,FILTER_SANITIZE_STRING);
		$str = str_replace("'","",$str);
		$str=trim(substr($str,0,$maxLen));
		return($str);
	}
}

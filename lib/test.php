<?php

require_once("globals.php");
require_once("class.db.php");

$db=db::factory();

$out=$db->getRow("SELECT * FROM User WHERE Email LIKE :email",array(":email"=>"booyahmedia%"));

print_r($out);

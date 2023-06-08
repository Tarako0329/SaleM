<?php
date_default_timezone_set('Asia/Tokyo');
require "./vendor/autoload.php";
require_once "functions.php";

$enc = sort_hash(2,"enc");
$dec = sort_hash($enc,"dec");

echo "enc => ".$enc;
echo "<br>";
echo "dec => ".$dec;

?>
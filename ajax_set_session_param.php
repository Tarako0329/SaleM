<?php
date_default_timezone_set('Asia/Tokyo');
session_start();

$_SESSION["UriageData_Correct_mode"]=(empty($_POST["mode"])?"%":$_POST["mode"]);

?>
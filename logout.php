<?php
    setCookie("webrez_token", '', -1, "/", null, TRUE, TRUE); 
    session_destroy();
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
?>
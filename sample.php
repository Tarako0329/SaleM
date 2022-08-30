<?php
require "php_header.php";
?>
<!DOCTYPE html>
<html lang="ja">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>GPS の値を得る</title>
</head>
 
<body>
<?php
    echo return_num_disp("1000000000")."<br>";
    echo return_num_disp("10000000000")."<br>";
    echo return_num_disp("100000000000")."<br>";
    echo return_num_disp("1")."<br>";
    echo return_num_disp("10")."<br>";
    echo return_num_disp("100")."<br>";
    echo return_num_disp("1000")."<br>";
    echo return_num_disp("10,00")."<br>";
    echo return_num_disp("1a000")."<br>";
?> 
<script>
</script>
</body>
</html>
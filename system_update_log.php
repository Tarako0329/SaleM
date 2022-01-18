<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";

$result = $mysqli->query( "select * from version order by version desc;" );
$row_cnt = $result->num_rows;

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_UriageData.css" >
    <TITLE><?php echo $title." システム更新履歴";?></TITLE>
</head>
 
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></div>
    <div style="font-size:1rem;"> システム更新履歴</div>

</header>

<body>    
    <div class="container-fluid">
    <table class="table-striped">
        <tr><td>version</td><td>内容</td></tr>
<?php    
$Goukei=0;
while($row = $result->fetch_assoc()){
    echo "<tr><td>".$row["version"]."</td><td>".$row["discription"]."</td></tr>\n";
    $Goukei = $Goukei + $row["UriageKin"];
}

?>
    </table>
    </div>
</body>



</html>
<?php
    $mysqli->close();
?>

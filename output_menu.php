<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);
$token = csrf_create();

//
if(!empty($_POST)){
    $ymfrom = $_POST["ymfrom"];
    $ymto = $_POST["ymto"];
    $list = $_POST["list"];
    
    $sqlstr="select Uridate,sum(UriageKin+zei) as zeikomi,CONCAT('売上No:',UriageNo) as 売上No,case when zeiKBN='1001' then '軽減税率8%' else '' end as 税率";
    $sqlstr=$sqlstr." from UriageData where uid=? and Uridate between ? and ?";
    $sqlstr=$sqlstr." group by Uridate,CONCAT('売上No:',UriageNo),case when zeiKBN='1001' then '軽減税率8%' else '' end";
    $sqlstr=$sqlstr." order by Uridate,売上No";

    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->bindValue(2, $ymfrom, PDO::PARAM_INT);
    $stmt->bindValue(3, $ymto, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    output_csv($row,$ymfrom."-".$ymto);
}else{
    $ymfrom = (string)date('Y')."-01-01";
    $ymto = (string)date('Y')."-12-31";
    $list = "%";
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.php" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_menu.css?<?php echo $time; ?>" >
    <TITLE><?php echo $title;?></TITLE>
</head>

<script>
  
</script>

<header class="header-color common_header">
    <div class="yagou title"><a href="menu.php"><?php echo $title;?></a></div></a></div>
    <?php
    //if($logoff==false){
    ?>
        <!--<div class="yagou title"><a href="menu.php"><?php //echo $title;?></a></div></a></div>
        <span style="font-size:1.5rem;"><a href="menu.php?action=logout"><i class="fa-solid fa-right-from-bracket"></i></a></span>-->
    <?php
    //}else{
    ?>
        <!--<div class="yagou title"><a href="index.php"><?php //echo $title;?></a></div></a></div>-->
    <?php
    //}
    ?>
</header>

<body class='common_body'>
    <div class="container" style="padding-top:15px;">
    <form method='post' action='#' style="font-size:1.5rem">
        
        <label for='ymfrom'> 出力対象期間</label>
        <input class='form-control' style="font-size:1.5rem;max-width:200px;" type='date' id='ymfrom' name='ymfrom' value='<?php echo $ymfrom; ?>'>
        <label for='ymto'>から</label>
        <input class='form-control' style="font-size:1.5rem;max-width:200px;" type='date' id='ymto' name='ymto' value='<?php echo $ymto; ?>'>
        <br>
        <label for='type'>連携会計システムの選択</label>
        <select class='form-control' style="font-size:1.5rem;padding:0;max-width:400px;" name='type' id='type'>
            <option value='yayoi'>やよいの青色申告</option>
            <option value='free'>フリー</option>
        </select>
        <br>
        <input class='btn btn-primary' type='submit' value='CSV出力' style='width:200px;hight:100px;'>
    </form>
    </div>
    
</body>

</html>
<?php
    $pdo_h=null;
?>
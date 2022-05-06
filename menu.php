<!DOCTYPE html>
<html lang='ja'>
<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

遷移先のチェック
csrf_chk()                              ：COOKIE・SESSION・POSTのトークンチェック。
csrf_chk_nonsession()                   ：COOKIE・POSTのトークンチェック。
csrf_chk_nonsession_get($_GET[token])   ：COOKIE・GETのトークンチェック。
csrf_chk_redirect($_GET[token])         ：SESSSION・GETのトークンチェック
*/

require "php_header.php";
$rtn=check_session_userid($pdo_h);
$token = csrf_create();
$logoff=false;
$action="";
//$color_No=0;
$Max_color_No=2;
if(!empty($_GET["action"])){
    $action = $_GET["action"];
}
if($action=="logout"){
    setCookie("webrez_token", 'a', -1, "/", null, TRUE, TRUE); 
    session_destroy();
    session_start();
    $logoff=true;
}
if($action=="color_change"){
    //配色の変更・保存
    $color_No = $_GET["color"]+1;
    if($color_No>$Max_color_No){
        $color_No=0;
    }
    $stmt = $pdo_h->prepare ( 'call PageDefVal_update(?,?,?,?,?)' );
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, MACHIN_ID, PDO::PARAM_STR);
    $stmt->bindValue(3, "menu.php", PDO::PARAM_STR);
    $stmt->bindValue(4, "COLOR", PDO::PARAM_STR);
    $stmt->bindValue(5, $color_No, PDO::PARAM_STR);
    $stmt->execute();
}
$_SESSION["PK"]=PKEY;
$_SESSION["SK"]=SKEY;
$_SESSION["URL"]="../SaleM/".MODE_DIR."/subscription.php";
$_SESSION["PLAN_M"]=PLAN_M;
$_SESSION["PLAN_Y"]=PLAN_Y;
$_SESSION["SUBID"]="";

//有効期限の取得
$sql="select * from Users where uid=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
$msg="<br>";
if($row[0]["yuukoukigen"]<>""){
    if(strtotime($row[0]["yuukoukigen"]) < strtotime(date("Y-m-d"))){
        //有効期限切れ。申込日から即課金
        $_SESSION["KIGEN"] = strtotime("+3 day");
        $msg= "有効期限切れ";
    }else{
        //試用期間、もしくは支払済み期間の翌日から課金
        $_SESSION["KIGEN"] = strtotime($row[0]["yuukoukigen"] ."+1 day");
        $msg= "有効期限付き(".$row[0]["yuukoukigen"]." まで)";
        //echo "有効期限付き(".date("Y-m-d",$_SESSION["KIGEN"])." まで)";
    }
    $plan=0;
}else{
    //契約済
    $plan=1;
    //echo "本契約済み";
    $_SESSION["SUBID"]=$row[0]["stripe_id"];
}
if($row[0]["yagou"]<>""){
    $user=$row[0]["yagou"];
}elseif($row[0]["name"]<>""){
    $user=$row[0]["name"];
}else{
    $user=$row[0]["mail"];
}
?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel='stylesheet' href='css/style_menu.css?<?php echo $time; ?>' >
    <TITLE><?php echo $title;?></TITLE>
</head>

<script>
  
</script>

<header class='header-color' style='display:block'>
    <?php
    if($logoff==false){
    ?>
        <div class='yagou title'><a href='menu.php'><?php echo $title;?></a></div>
        <div class='user_disp'>LogIn：<?php echo $user; ?></div>
        <div style='position:fixed;top:0;right:0;'><a href='menu.php?action=logout'><i class='fa-solid fa-right-from-bracket fa-lg logoff-color'></i></a></div>
    <?php
    }else{
    ?>
        <div class='yagou title'><a href='index.php'><?php echo $title;?></a></div>
    <?php
    }
    ?>
    
</header>

<body>
<?php
    if($logoff){
        echo "ログオフしました。<br>";
        echo "<a href='index.php'>再ログインする</a>";
        //echo $_COOKIE["webrez_token"];
        exit;
    }
    echo $msg;
?>
    <div style='position:fixed;top:70px;right:0;' class='rainbow-color'><b><a href='menu.php?action=color_change&color=<?php echo $color_No; ?>'>COLOR<i class='fa-solid fa-rotate-right fa-lg rainbow-color'></i></a></b></div>
    <div class='container-fluid'>

<?php
    $array = [
        'レジ'=>['EVregi.php?mode=evrez&csrf_token='.$token]
        ,'個別売上'=>['EVregi.php?mode=kobetu&csrf_token='.$token]
        ,'商品登録'=>['shouhinMSedit.php?csrf_token='.$token]
        ,'商品一覧'=>['shouhinMSList.php?csrf_token='.$token]
        //,'出品在庫登録'=>['EVregi.php?mode=shuppin_zaiko&csrf_token='.$token]
        ,'売上実績'=>['UriageData.php?mode=select&csrf_token='.$token]
        ,'売上分析'=>['analysis_menu.php?csrf_token='.$token]
        ,'ユーザ情報'=>['account_create.php?mode=1&csrf_token='.$token]
        ,'会計連携'=>['output_menu.php?csrf_token='.$token]
        ,'紹介者ID'=>['shoukai.php?csrf_token='.$token]
        //,'契約・解除'=>['../../PAY/index.php?system='.$title.'&mode='.MODE_DIR]
        //,'お知らせ'=>['system_update_log.php']
    ];
    
    if($plan==0){
        $array2 = ['本契約'=>['../../PAY/index.php?system='.$title.'&mode='.MODE_DIR]];
    }else{
        //$array2 = ['契約解除について'=>['../../PAY/cancel.php?system='.$title.'&mode='.MODE_DIR]];
        $array2 = ['契約解除へ'=>['sub_cancel.php?system='.$title.'&mode='.MODE_DIR]];
    }
    
    $i=0;
    echo "<div class='row'>";
	foreach(array_merge($array,$array2) as $key=>$vals){
        echo "  <div class ='col-md-3 col-sm-6 col-6' style='padding:5px;' >\n";
        echo "      <a href='".$vals[0]."' class='btn--topmenu btn-view'>".$key."\n";
        echo "      </a>\n";
        echo "  </div>\n";
        $i++;
	}
    echo "</div>";
	
?> 
              
    </div>
</body>

</html>
<?php
    $pdo_h=null;
?>
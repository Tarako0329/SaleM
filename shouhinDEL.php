<!DOCTYPE html>
<html lang="ja">
<?php
require "php_header.php";

if(isset($_GET["csrf_token"]) || empty($_POST)){
    if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
}

if(!($_SESSION['user_id']<>"")){
    //セッションのIDがクリアされた場合の再取得処理。
    if(check_auto_login($_COOKIE['webrez_token'],$pdo_h)==false){
        $_SESSION["EMSG"]="ログイン有効期限が切れてます";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
    }
}

//実績有無の確認
$sqlstr="select count(*) as cnt from UriageData where ShouhinCD=? and uid=?";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, $_GET["cd"], PDO::PARAM_INT);
$stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
$uri = $row[0]["cnt"];
if($uri!=0){
    $msg= "売上実績があるため、削除できません。<br>";
}


if($_POST["btn"] == "削除"){
    if(csrf_chk()==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
    $sqlstr="delete from ShouhinMS where shouhinCD=? and uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST["cd"], PDO::PARAM_INT);
    $stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();


    $status=$stmt->execute();
    if($status==true){
        $msg= secho($_POST["shouhinNM"])."　が削除されました。<br>";
    }else{
        echo "登録が失敗しました。<br>";
    }

    //echo $_POST["hyoujiKBN1"];
}else{
    //削除対象の取得
    $sqlstr="select * from ShouhinMS where shouhinCD=? and uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_GET["cd"], PDO::PARAM_INT);
    $stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $chk="";
    if($row[0]["hyoujiKBN1"]=="on"){$chk="checked";}
}

//税区分MSリスト取得
$sqlstr="select * from ZeiMS order by zeiKBN;";
$stmt = $pdo_h->query($sqlstr);
$csrf_token = csrf_create();

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_ShouhinMSedit.css?<?php echo $time; ?>" >
    <TITLE><?php echo secho($title)." 取扱商品削除画面";?></TITLE>
</head>
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo secho($title);?></a></div>
    <p style="font-size:1rem;">  取扱商品削除画面</p>
</header>

<body>
    <div class="container-fluid" style="padding-top:15px;">
    <?php 
    if($msg<>""){
        echo $msg;
        echo "<a href='shouhinMSList.php?csrf_token=".$csrf_token."'>商品一覧に戻る</a>";
        exit;
    } ?>
    <p>以下の商品を削除しますか？</p>
    <form method="post" class="form" action="shouhinDEL.php">
        <div class="form-group form-inline">
            <label for="shouhinNM" class="col-2 col-md-1 control-label">商品名</label>
            <div class="col-10">
                <input type="text" class="form-control" style="width:80%" id="shouhinNM" name="shouhinNM" required="required" placeholder="必須" value="<?php echo rot13decrypt($row[0]["shouhinNM"]);?>">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="tanka" class="col-2 col-md-1 control-label">単価(税抜)</label>
            <div class=" col-10">
                <input type="number" class="form-control" style="width:80%" id="tanka" name="tanka" required="required" placeholder="必須" value="<?php echo $row[0]["tanka"]; ?>">
            </div>
        </div>

        <div class="form-group form-inline">
            <label for="zeikbn" class="col-2 col-md-1 control-label">税区分</label>
            <div class=" col-10">
                <!--<input type="text" class="form-control" id="zeikbn" name="zeikbn">-->
                <select class="form-control" style="width:80%" id="zeikbn" name="zeikbn" required="required" placeholder="必須">
                    <option value=""></option>
                    <?php
                    foreach($stmt as $row2){
                        //echo "<option value=".secho($row["zeiKBN"]).">".secho($row["hyoujimei"])."</option>\n";
                        if($row[0]["zeiKBN"]==$row2["zeiKBN"]){
                            echo "<option value='".$row2["zeiKBN"]."' selected>".$row2["hyoujimei"]."</option>\n";
                        }else{
                            echo "<option value='".$row2["zeiKBN"]."'>".$row2["hyoujimei"]."</option>\n";
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="utisu" class="col-2 col-md-1 control-label">内容量</label>
            <div class=" col-10">
                <input type="number" class="form-control" style="width:80%" id="utisu" name="utisu" placeholder="1箱12個入りの場合「12」等" value="<?php echo $row[0]["utisu"]; ?>">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="tani" class="col-2 col-md-1 control-label">単位</label>
            <div class=" col-10">
                <input type="text" class="form-control" style="width:80%" id="tani" name="tani" placeholder="内容量の単位（g,個）等" value="<?php echo $row[0]["tani"]; ?>">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="bunrui1" class="col-2 col-md-1 control-label">大カテゴリー</label>
            <div class=" col-10">
                <input type="text" class="form-control" style="width:80%" id="bunrui1" name="bunrui1" placeholder="例：物販" value="<?php echo $row[0]["bunrui1"]; ?>">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="bunrui2" class="col-2 col-md-1 control-label">中カテゴリー</label>
            <div class=" col-10">
                <input type="text" class="form-control" style="width:80%" id="bunrui2" name="bunrui2" placeholder="例：食品" value="<?php echo $row[0]["bunrui2"]; ?>">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="bunrui3" class="col-2 col-md-1 control-label">小カテゴリー</label>
            <div class=" col-10">
                <input type="text" class="form-control" style="width:80%" id="bunrui3" name="bunrui3" placeholder="例：惣菜" value="<?php echo $row[0]["bunrui3"]; ?>">
            </div>
        </div>
        <div class="form-group form-inline form-switch">
            <label class="col-2 col-md-1 control-label">レジ対象</label>
            <div class="col-10" style="text-align:left">
                <label for="hyoujiKBN1" style="float:left;width:8rem;">
                     <input type="checkbox" style="vertical-align:middle;" id="hyoujiKBN1" name="hyoujiKBN1" <?php echo $chk;?>>表示する
                </label>
            </div>
        </div>
        
        <input type="hidden" class="form-control" id="hyoujiKBN2" name="hyoujiKBN2" value="">
        <input type="hidden" class="form-control" id="hyoujiKBN3" name="hyoujiKBN3" value="">

        <!--用途が未定なので非表示
        <div class="form-group form-inline">
            <label for="hyoujiKBN2" class="col-2 col-md-1 control-label">表示区分2</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="hyoujiKBN2" name="hyoujiKBN2">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="hyoujiKBN3" class="col-2 col-md-1 control-label">表示区分3</label>
            <div class=" col-10">
                <input type="text" class="form-control" id="hyoujiKBN3" name="hyoujiKBN3">
            </div>
        </div>
        -->
        <div class="form-group form-inline">
            <label for="hyoujiNO" class="col-2 col-md-1 control-label">表示順</label>
            <div class=" col-10">
                <input type="text" class="form-control" style="width:50%" id="hyoujiNO" name="hyoujiNO" placeholder="レジ表示順。未指定の場合は「カテゴリー大>中>小>商品名」の五十音順" value=0>
            </div>
        </div>
        
        <input type="hidden" name="cd" value="<?php echo $_GET["cd"]; ?>">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <br>
        <div class="col-2 col-md-1" style=" padding:0; margin-top:10px;">
            <button type="submit" class="btn btn-primary" style="width:100%;" name="btn" value="削除">削  除</button>
        </div>
    </form>
    </div>

</body>
</html>

<?php
$stmt  = null;
$pdo_h = null;
?>



















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

if($_POST["btn"] == "登録"){
    if(csrf_chk()==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
    }

    //税区分MSから税率の取得
    $sqlstr="select * from ZeiMS where zeiKBN=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST["zeikbn"], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $zeikbn = $row[0]["zeiKBN"];
    $zeiritu= $row[0]["zeiritu"];


    //商品CDの取得
    $sqlstr="select max(shouhinCD) as MCD from ShouhinMS where uid=? group by uid";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $new_shouhinCD = $row[0]["MCD"]+1;

    $sqlstr="insert into ShouhinMS values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $new_shouhinCD, PDO::PARAM_INT);
    $stmt->bindValue(3, rot13encrypt($_POST["shouhinNM"]), PDO::PARAM_STR);
    $stmt->bindValue(4, $_POST["tanka"], PDO::PARAM_INT);
    $stmt->bindValue(5, $zeiritu, PDO::PARAM_INT);
    $stmt->bindValue(6, $zeikbn, PDO::PARAM_INT);
    $stmt->bindValue(7, $_POST["utisu"], PDO::PARAM_INT);
    $stmt->bindValue(8, $_POST["tani"], PDO::PARAM_STR);
    $stmt->bindValue(9, $_POST["bunrui1"], PDO::PARAM_STR);
    $stmt->bindValue(10, $_POST["bunrui2"], PDO::PARAM_STR);
    $stmt->bindValue(11, $_POST["bunrui3"], PDO::PARAM_STR);
    $stmt->bindValue(12, $_POST["hyoujiKBN1"], PDO::PARAM_STR);
    $stmt->bindValue(13, $_POST["hyoujiKBN2"], PDO::PARAM_STR);
    $stmt->bindValue(14, $_POST["hyoujiKBN3"], PDO::PARAM_STR);
    $stmt->bindValue(15, $_POST["hyoujiNO"], PDO::PARAM_INT);
    
    $status=$stmt->execute();
    if($status==true){
        echo secho($_POST["shouhinNM"])."　が登録されました。<br>";
    }else{
        echo "登録が失敗しました。<br>";
    }

    //echo $_POST["hyoujiKBN1"];
}
//税区分MSリスト取得
$sqlstr="select * from ZeiMS order by zeiKBN;";
$stmt = $pdo_h->query($sqlstr);
$csrf_token=csrf_create();
?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS--><link rel="stylesheet" href="css/style_ShouhinMSedit.css" >
    <TITLE><?php echo secho($title)." 取扱商品登録画面";?></TITLE>
</head>
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo secho($title);?></a></div>
    <p style="font-size:1rem;">  取扱商品登録画面</p>
</header>

<body>
    <div class="container-fluid" style="padding-top:15px;">
    <form method="post" class="form" action="shouhinMSedit.php">
        <div class="form-group form-inline">
            <label for="shouhinNM" class="col-2 col-md-1 control-label">商品名</label>
            <div class="col-10">
                <input type="text" class="form-control" style="width:80%" id="shouhinNM" name="shouhinNM" required="required" placeholder="必須">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="tanka" class="col-2 col-md-1 control-label">単価(税抜)</label>
            <div class=" col-10">
                <input type="number" class="form-control" style="width:80%" id="tanka" name="tanka" required="required" placeholder="必須">
            </div>
        </div>

        <div class="form-group form-inline">
            <label for="zeikbn" class="col-2 col-md-1 control-label">税区分</label>
            <div class=" col-10">
                <!--<input type="text" class="form-control" id="zeikbn" name="zeikbn">-->
                <select class="form-control" style="width:80%;padding-top:0;" id="zeikbn" name="zeikbn" required="required" placeholder="必須" >
                    <option value=""></option>
                    <?php
                    foreach($stmt as $row){
                        echo "<option value=".secho($row["zeiKBN"]).">".secho($row["hyoujimei"])."</option>\n";
                    }
                    ?>
                </select>
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="tanka" class="col-2 col-md-1 control-label">税込み価格</label></label>
            <div class=" col-10">
                <input type="number" readonly='readonly' class="form-control" style="width:80%;border:none;" id="zkomitanka" >
            </div>
        </div>

        <div class="form-group form-inline">
            <label for="utisu" class="col-2 col-md-1 control-label">内容量</label>
            <div class=" col-10">
                <input type="number" class="form-control" style="width:80%" id="utisu" name="utisu" placeholder="1箱12個入りの場合「12」等">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="tani" class="col-2 col-md-1 control-label">単位</label>
            <div class=" col-10">
                <input type="text" class="form-control" style="width:80%" id="tani" name="tani" placeholder="内容量の単位（g,個）等">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="bunrui1" class="col-2 col-md-1 control-label">大カテゴリー</label>
            <div class=" col-10">
                <input type="text" class="form-control" style="width:80%" id="bunrui1" name="bunrui1" placeholder="例：物販">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="bunrui2" class="col-2 col-md-1 control-label">中カテゴリー</label>
            <div class=" col-10">
                <input type="text" class="form-control" style="width:80%" id="bunrui2" name="bunrui2" placeholder="例：食品">
            </div>
        </div>
        <div class="form-group form-inline">
            <label for="bunrui3" class="col-2 col-md-1 control-label">小カテゴリー</label>
            <div class=" col-10">
                <input type="text" class="form-control" style="width:80%" id="bunrui3" name="bunrui3" placeholder="例：惣菜">
            </div>
        </div>
        <div class="form-group form-inline form-switch">
            <label class="col-2 col-md-1 control-label">レジ対象</label>
            <div class="col-10" style="text-align:left">
                <label for="hyoujiKBN1" style="float:left;width:8rem;">
                     <input type="checkbox" style="vertical-align:middle;" id="hyoujiKBN1" name="hyoujiKBN1">表示する
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
        
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
        <br>
        <div class="col-2 col-md-1" style=" padding:0; margin-top:10px;">
            <button type="submit" class="btn btn-primary" style="width:100%;" name="btn" value="登録">登  録</button>
        </div>
    </form>
    </div>

    <script type="text/javascript" language="javascript">
        var select = document.getElementById('zeikbn');
        var tanka = document.getElementById('tanka');
        var zkomitanka = document.getElementById('zkomitanka');
        
        select.onchange = function(){
            switch(this.value){
                case '0':
                    zkomitanka.value=tanka.value;
                    break;
                case '1001':
                    zkomitanka.value=Math.round(tanka.value * (1 + 8 / 100));
                    break;
                case '1101':
                    zkomitanka.value=Math.round(tanka.value * (1 + 10 / 100));
                    break;
            }
        }
    </script>

</body>
</html>

<?php
$stmt  = null;
$pdo_h = null;
?>



















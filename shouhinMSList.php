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

if($_POST["commit_btn"] <> ""){
    if(csrf_chk()==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
    }
    $array = $_POST["ORDERS"];
    $sqlstr = "";

    foreach($array as $row){
        $sqlstr="select * from ZeiMS where zeiKBN=?;";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $row["zeikbn"], PDO::PARAM_INT);
        $stmt->execute();
        $row3 = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $sqlstr = "update ShouhinMS set tanka=?,zeiritu=?,zeikbn=?,tani=?,bunrui1=?,bunrui2=?,bunrui3=?,hyoujiKBN1=?,hyoujiNO=? where shouhinCD=? and uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $row["tanka"], PDO::PARAM_INT);
        $stmt->bindValue(2, $row3[0]["zeiritu"], PDO::PARAM_INT);
        $stmt->bindValue(3, $row["zeikbn"], PDO::PARAM_STR);
        $stmt->bindValue(4, $row["tani"], PDO::PARAM_STR);
        $stmt->bindValue(5, $row["bunrui1"], PDO::PARAM_STR);
        $stmt->bindValue(6, $row["bunrui2"], PDO::PARAM_STR);
        $stmt->bindValue(7, $row["bunrui3"], PDO::PARAM_STR);
        $stmt->bindValue(8, $row["hyoujiKBN1"], PDO::PARAM_STR);
        $stmt->bindValue(9, $row["hyoujiNO"], PDO::PARAM_INT);
        $stmt->bindValue(10,$row["shouhinCD"], PDO::PARAM_INT);
        $stmt->bindValue(11,$_SESSION['user_id'], PDO::PARAM_INT);
        $status=$stmt->execute();
        if($status==true){
            //echo secho($_POST["shouhinNM"])."　が登録されました。<br>";
        }else{
            echo secho($_POST["shouhinNM"])." の更新が失敗しました。<br>";
        }
    }
}
$csrf_create = csrf_create();

//商品マスタの取得
$sql = "select * from ShouhinMS left join ZeiMS on ShouhinMS.zeiKBN=ZeiMS.zeiKBN where uid = ? order by shouhinCD";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();



//税区分MSリスト取得
$sqlstr="select * from ZeiMS order by zeiKBN;";
$stmt2 = $pdo_h->query($sqlstr);
$ZKMS = $stmt2->fetchAll();

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS--><link rel="stylesheet" href="css/style_ShouhinMSL.css?<?php echo $time; ?>" >
    <TITLE><?php echo $title." 取扱商品 確認・編集";?></TITLE>
</head>
<header style="flex-wrap:wrap">
    <div class="title" style="width: 100%;"><a href="menu.php"><?php echo $title;?></a></div>
    <p style="font-size:1rem;">  取扱商品 確認・編集 画面</p>
</header>

<body>    
    <div class="container-fluid">
    <form method="post" action="shouhinMSList.php">

    <input type="hidden" name="csrf_token" value="<?php echo $csrf_create; ?>">
    
    <table class="table-striped">
        <!--表示区分２，３はまだ使わない
        <thead><tr><th scope="col" style='width:3rem;'>ID</th><th scope="col">商品名</th><th scope="col">単価(税抜)</th><th scope="col" class='d-none d-sm-table-cell'>税区分</th><th scope="col">内容量</th><th scope="col" class="d-none d-sm-table-cell">単位</th><th scope="col" class="d-none d-sm-table-cell">分類1</th><th scope="col" class="d-none d-sm-table-cell">分類2</th><th scope="col" class="d-none d-sm-table-cell">分類3</th><th scope="col">レジ</th><th scope="col" class="d-none d-sm-table-cell">区分2</th><th scope="col" class="d-none d-sm-table-cell">区分3</th><th scope="col">並順</th></tr></thead>
        -->
        <thead><tr><th scope="col" style='width:3rem;'>ID</th><th scope="col">商品名</th><th scope="col">単価</th><th scope="col" class='d-none d-sm-table-cell'>税区分</th><th scope="col">内容量</th><th scope="col" class="d-none d-sm-table-cell">単位</th><th scope="col" class="d-none d-sm-table-cell">分類1</th><th scope="col" class="d-none d-sm-table-cell">分類2</th><th scope="col" class="d-none d-sm-table-cell">分類3</th><th scope="col">レジ</th><th scope="col">並順</th></tr></thead>
        <tbody>
<?php    
$i=0;
foreach($stmt as $row){
    $chk="";
    if($row["hyoujiKBN1"]=="on"){$chk="checked";}
    echo "<tr>\n";
    echo "<td style='width:3rem;'>".$row["shouhinCD"]."</td>";
    echo "<td style='width:auto;padding:0px 5px 0px 0px;'>".rot13decrypt($row["shouhinNM"])."</td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][tanka]' style='width:6rem;' value='".$row["tanka"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><select name ='ORDERS[".$i."][zeikbn]' style='width:11rem;height:30px;'>";
        foreach($ZKMS as $row2){
            if($row["zeiKBN"]==$row2["zeiKBN"]){
                echo "<option value='".$row2["zeiKBN"]."' selected>".$row2["hyoujimei"]."</option>\n";
            }else{
                echo "<option value='".$row2["zeiKBN"]."'>".$row2["hyoujimei"]."</option>\n";
            }
        }
    echo "</select>";
    
    echo "<td><input type='number'   name ='ORDERS[".$i."][utisu]' style='width:6rem;' value='".$row["utisu"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][tani]' style='width:3rem;' value='".$row["tani"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][bunrui1]' style='width:6rem;' value='".$row["bunrui1"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][bunrui2]' style='width:6rem;' value='".$row["bunrui2"]."'></td>";
    echo "<td class='d-none d-sm-table-cell'><input type='text'   name ='ORDERS[".$i."][bunrui3]' style='width:6rem;' value='".$row["bunrui3"]."'></td>";
    echo "<td><input type='checkbox' name ='ORDERS[".$i."][hyoujiKBN1]' style='width:4rem;' ".$chk."></td>";
//    echo "<td class='d-none d-sm-table-cell'><input type='number'   name ='ORDERS[".$i."][hyoujiKBN2]' style='width:4rem;' value='".$row["hyoujiKBN2"]."'></td>";
//    echo "<td class='d-none d-sm-table-cell'><input type='number'   name ='ORDERS[".$i."][hyoujiKBN3]' style='width:4rem;' value='".$row["hyoujiKBN3"]."'></td>";
    echo "<td><input type='number'   name ='ORDERS[".$i."][hyoujiNO]' style='width:4rem;' value='".$row["hyoujiNO"]."'></td>";
    echo "<td class='d-none d-sm-table-cell' style='width:4rem;text-align:center;'><a href='shouhinDEL.php?cd=".$row["shouhinCD"]."&csrf_token=".$csrf_create."'><i class='fa-regular fa-trash-can'></i></a></td>";
    echo "</tr>\n";
    echo "<input type='hidden'   name ='ORDERS[".$i."][shouhinCD]' value='".$row["shouhinCD"]."'>";
    $i = $i+1;
}

?>
        </tbody>
    </table>
    
    
    </div>
</body>

<footer>
    <dev class="col-1"></dev>
    <dev class="col-2" style="padding:0;">
        <input type="submit" value="登録" class="btn btn--chk" name="commit_btn">
    </dev>
    </form>
</footer>
</html>
<?php
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>
<?php
require "php_header.php";

$rtn=check_session_userid();
$token = csrf_create();

//売上登録
if($_POST["commit_btn"] <> ""){
    if(csrf_chk_nonsession()==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
    $array = $_POST["ORDERS"];
    $sqlstr = "";

    //売上番号の取得
    $sqlstr = "select max(UriageNO) as UriageNO from UriageData where uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    

    if(is_null($row[0]["UriageNO"])){
        //初回売上時は売上NO[1]をセット
        $UriageNO = 1;
    }else{
        $UriageNO = $row[0]["UriageNO"]+1;
    }
    //echo (string)$UriageNO;
    
    $E_Flg=0;
    try{
        $pdo_h->beginTransaction();
        foreach($array as $row){
            if($row["SU"]==0){
                continue;
            }
            $sqlstr = "insert into UriageData values(?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
            $stmt = $pdo_h->prepare($sqlstr);
    
            $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
            $stmt->bindValue(3,  date("Y/m/d"), PDO::PARAM_STR);
            $stmt->bindValue(4,  date("Y/m/d H:i:s"), PDO::PARAM_STR);
            $stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
            $stmt->bindValue(6,  '', PDO::PARAM_STR);
            $stmt->bindValue(7,  $row["CD"], PDO::PARAM_INT);
            $stmt->bindValue(8,  $row["NM"], PDO::PARAM_STR);
            $stmt->bindValue(9,  $row["SU"], PDO::PARAM_INT);
            $stmt->bindValue(10, $row["UTISU"], PDO::PARAM_INT);
            $stmt->bindValue(11, $row["TANKA"], PDO::PARAM_INT);
            $stmt->bindValue(12, ($row["SU"] * $row["TANKA"]), PDO::PARAM_INT);
            $stmt->bindValue(13, ($row["SU"] * $row["ZEI"]), PDO::PARAM_INT);
            $stmt->bindValue(14, $row["ZEIKBN"], PDO::PARAM_INT);
            
            
            $flg=$stmt->execute();
            
            if($flg){
            }else{
                $E_Flg=1;
                break;
            }
        }
        if(E_Flg==0){
            $pdo_h->commit();    
        }else{
            //1件でも失敗したらロールバック
            $pdo_h->rollBack();
            echo "登録が失敗しました。<br>";
            echo "<a href='UriageData.php?csrf_token=".$token."'>レジ画面</a>より再度登録をお願いします。<br>再三失敗するようでしたら制作者へご連絡ください。";
            exit();
        }
        
    }catch (Exception $e) {
        $pdo_h->rollBack();
        echo "登録が失敗しました。<br>" . $e->getMessage()."<br>";
        echo "<a href='UriageData.php?csrf_token=".$token."'>レジ画面</a>より再度登録をお願いします。<br>再三失敗するようでしたら制作者へご連絡ください。";
        exit();
    }
        

}

$stmt = null;
$pdo_h = null;

header("HTTP/1.1 301 Moved Permanently");
header("Location: EVregi.php?csrf_token=".$token);
exit();

?>



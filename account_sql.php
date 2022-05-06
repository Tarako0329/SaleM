<?php
//ユーザ登録、登録情報の修正画面
//$mode 0:新規　1:更新　3:確認(新規) 4:確認(更新) 5:登録完了（更新・新規共通）6:登録失敗

require "php_header.php";

if($mode==4){
    //更新モードの場合、session[usr_id]のチェック
    $rtn=check_session_userid();
}

if(csrf_chk()==false && $mode>=3){
    $_SESSION["EMSG"]="セッションが正しくありませんでした";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}

if($_POST["BTN"] == "登　録"){
    //試用期間は登録日の翌月末->２ヶ月後に変更。（いつ登録すればお得・・・でタイミングを逃さないため）
    //$kigen=date('Y-m-d', strtotime('last day of next month' . date("Y-m-d")));
    $kigen=date('Y-m-d', strtotime(date("Y-m-d") . "+2 month"));
    
    //$sqlstr="insert into Users values(0,?,?,?,?,?,?,?,?,?,?,?,null,null,null,null,null,null,null,null,null,null,?,?,null)";
    $sqlstr="insert into Users(uid,mail,password,question,answer,loginrez,insdate,yuukoukigen,introducer_id) values(0,?,?,?,?,?,?,?,?)";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["MAIL"], PDO::PARAM_STR);
    $stmt->bindValue(2, $_SESSION["PASS"], PDO::PARAM_STR);
    $stmt->bindValue(3, $_SESSION["QUESTION"], PDO::PARAM_STR);
    $stmt->bindValue(4, $_SESSION["ANSWER"], PDO::PARAM_STR);
    $stmt->bindValue(5, $_SESSION["LOGINREZ"], PDO::PARAM_STR);
    $stmt->bindValue(6, date("Y-m-d"), PDO::PARAM_STR);
    $stmt->bindValue(7, $kigen, PDO::PARAM_STR);
    $stmt->bindValue(8, $_SESSION["SHOUKAI"], PDO::PARAM_STR);
    /*
    $stmt->bindValue(6, $_SESSION["NAME"], PDO::PARAM_STR);
    $stmt->bindValue(7, $_SESSION["YAGOU"], PDO::PARAM_STR);
    $stmt->bindValue(8, $_SESSION["zip11"], PDO::PARAM_STR);
    $stmt->bindValue(9, $_SESSION["addr11"], PDO::PARAM_STR);
    $stmt->bindValue(10, $_SESSION["ADD2"], PDO::PARAM_STR);
    $stmt->bindValue(11, $_SESSION["ADD3"], PDO::PARAM_STR);
    $stmt->bindValue(12, date("Y-m-d"), PDO::PARAM_STR);
    $stmt->bindValue(13, $kigen, PDO::PARAM_STR);
    */
    $flg=$stmt->execute();
    
    if($flg){
        $stmt2 = $pdo_h->prepare("select uid from Users where mail=?");
        $stmt2->bindValue(1, $_SESSION["MAIL"], PDO::PARAM_STR);
        $stmt2->execute();
        $tmp = $stmt2->fetchAll(PDO::FETCH_ASSOC);
        $_SESSION["user_id"]=$tmp[0]["uid"];
        $mode=5;
    }else{
        echo "登録が失敗しました。";
        $mode=6;
    }
}elseif($_POST["BTN"] == "更　新"){
    $sqlstr="update Users set mail=?,password=?,question=?,answer=?,loginrez=?,name=?,yagou=?,yubin=?,address1=?,address2=?,address3=? where uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["MAIL"], PDO::PARAM_STR);
    $stmt->bindValue(2, $_SESSION["PASS"], PDO::PARAM_STR);
    $stmt->bindValue(3, $_SESSION["QUESTION"], PDO::PARAM_STR);
    $stmt->bindValue(4, $_SESSION["ANSWER"], PDO::PARAM_STR);
    $stmt->bindValue(5, $_SESSION["LOGINREZ"], PDO::PARAM_STR);
    $stmt->bindValue(6, $_SESSION["NAME"], PDO::PARAM_STR);
    $stmt->bindValue(7, $_SESSION["YAGOU"], PDO::PARAM_STR);
    $stmt->bindValue(8, $_SESSION["zip11"], PDO::PARAM_STR);
    $stmt->bindValue(9, $_SESSION["addr11"], PDO::PARAM_STR);
    $stmt->bindValue(10, $_SESSION["ADD2"], PDO::PARAM_STR);
    $stmt->bindValue(11, $_SESSION["ADD3"], PDO::PARAM_STR);
    $stmt->bindValue(12, $_SESSION["user_id"], PDO::PARAM_INT);
    $flg=$stmt->execute();
    
    if($flg){
        $mode=5;
    }else{
        echo "登録が失敗しました。";
        $mode=6;
    }
}

$stmt=null;
$pdo_h=null;

header("HTTP/1.1 301 Moved Permanently");
header("Location: account_create.php?mode=".$mode);

?>





















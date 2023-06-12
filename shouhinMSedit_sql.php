<!DOCTYPE html>
<html lang="ja">
<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

*/

require "php_header.php";

//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);

$rtn = csrf_checker(["shouhinMSedit.php"],["P","C","S"]);
if($rtn !== true){
    redirect_to_login($rtn);
}

//税区分MSから税率の取得
try{
    $pdo_h->beginTransaction();
    $sqllog .= rtn_sqllog("START TRANSACTION",[]);

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
    
    $params[0]=$_SESSION['user_id'];
    $params[1]=$new_shouhinCD;
    $params[2]=$_POST["shouhinNM"];
    $params[3]=$_POST["tanka"];
    $params[4]=$_POST["shouhizei"];
    $params[5]=$zeiritu;
    $params[6]=$zeikbn;
    $params[7]=$_POST["utisu"];
    $params[8]=$_POST["tani"];
    $params[9]=$_POST["genka"];
    $params[10]=$_POST["bunrui1"];
    $params[11]=$_POST["bunrui2"];
    $params[12]=$_POST["bunrui3"];
    $params[13]=$_POST["hyoujiKBN1"];
    $params[14]=$_POST["hyoujiKBN2"];
    $params[15]=$_POST["hyoujiKBN3"];
    $params[16]=$_POST["hyoujiNO"];
    
    $sqlstr="insert into ShouhinMS values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1,  $params[0], PDO::PARAM_INT);
    $stmt->bindValue(2,  $params[1], PDO::PARAM_INT);
    $stmt->bindValue(3,  $params[2], PDO::PARAM_STR);
    $stmt->bindValue(4,  $params[3], PDO::PARAM_INT);
    $stmt->bindValue(5,  $params[4], PDO::PARAM_INT);
    $stmt->bindValue(6,  $params[5], PDO::PARAM_INT);
    $stmt->bindValue(7,  $params[6], PDO::PARAM_INT);
    $stmt->bindValue(8,  $params[7], PDO::PARAM_INT);
    $stmt->bindValue(9,  $params[8], PDO::PARAM_STR);
    $stmt->bindValue(10, $params[9], PDO::PARAM_INT);
    $stmt->bindValue(11, $params[10], PDO::PARAM_STR);
    $stmt->bindValue(12, $params[11], PDO::PARAM_STR);
    $stmt->bindValue(13, $params[12], PDO::PARAM_STR);
    $stmt->bindValue(14, $params[13], PDO::PARAM_STR);
    $stmt->bindValue(15, $params[14], PDO::PARAM_STR);
    $stmt->bindValue(16, $params[15], PDO::PARAM_STR);
    $stmt->bindValue(17, $params[16], PDO::PARAM_INT);
    $sqllog .= rtn_sqllog($sqlstr,$params);
    $status=$stmt->execute();
    $sqllog .= rtn_sqllog("commit",[]);
    sqllogger($sqllog,0);

    $_SESSION["MSG"] = secho($_POST["shouhinNM"])."　が登録されました。";
    
}catch(Exception $e){
    $pdo_h->rollBack();
    $sqllog .= rtn_sqllog("rollBack",$e);
    $_SESSION["MSG"] = "登録が失敗しました。";
}

$stmt  = null;
$pdo_h = null;

$csrf_token=csrf_create();
header("HTTP/1.1 301 Moved Permanently");
header("Location:shouhinMSedit.php?csrf_token=".$csrf_token);
exit();

?>



















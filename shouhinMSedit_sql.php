<!DOCTYPE html>
<html lang="ja">
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

//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);

if($_POST["btn"] == "登録"){
    if(csrf_chk()==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
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

    $sqlstr="insert into ShouhinMS values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, $new_shouhinCD, PDO::PARAM_INT);
    $stmt->bindValue(3, rot13encrypt($_POST["shouhinNM"]), PDO::PARAM_STR);
    $stmt->bindValue(4, $_POST["tanka"], PDO::PARAM_INT);
    $stmt->bindValue(5, $_POST["shouhizei"], PDO::PARAM_INT);
    $stmt->bindValue(6, $zeiritu, PDO::PARAM_INT);
    $stmt->bindValue(7, $zeikbn, PDO::PARAM_INT);
    $stmt->bindValue(8, $_POST["utisu"], PDO::PARAM_INT);
    $stmt->bindValue(9, $_POST["tani"], PDO::PARAM_STR);
    $stmt->bindValue(10, $_POST["genka"], PDO::PARAM_INT);
    $stmt->bindValue(11,$_POST["bunrui1"], PDO::PARAM_STR);
    $stmt->bindValue(12, $_POST["bunrui2"], PDO::PARAM_STR);
    $stmt->bindValue(13, $_POST["bunrui3"], PDO::PARAM_STR);
    $stmt->bindValue(14, $_POST["hyoujiKBN1"], PDO::PARAM_STR);
    $stmt->bindValue(15, $_POST["hyoujiKBN2"], PDO::PARAM_STR);
    $stmt->bindValue(16, $_POST["hyoujiKBN3"], PDO::PARAM_STR);
    $stmt->bindValue(17, $_POST["hyoujiNO"], PDO::PARAM_INT);
    
    $status=$stmt->execute();
    if($status==true){
        $_SESSION["MSG"] = secho($_POST["shouhinNM"])."　が登録されました。";
    }else{
        $_SESSION["MSG"] = "登録が失敗しました。";
    }

    //echo $_POST["hyoujiKBN1"];
}

$stmt  = null;
$pdo_h = null;

$csrf_token=csrf_create();
header("HTTP/1.1 301 Moved Permanently");
header("Location:shouhinMSedit.php?csrf_token=".$token);
exit();

?>



















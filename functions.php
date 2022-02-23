<?php
// =========================================================
// トークンを作成
// =========================================================
function get_token() {
    $TOKEN_LENGTH = 16;//16*2=32桁
    $bytes = openssl_random_pseudo_bytes($TOKEN_LENGTH);
    return bin2hex($bytes);
}
// =========================================================
// 自動ログイン処理
// =========================================================
function check_auto_login($cookie_token, $pdo) {
    //プレースホルダで SQL 作成
    $sql = "SELECT * FROM AUTO_LOGIN WHERE TOKEN = ? AND REGISTRATED_TIME >= ?;";
    //2週間前の日付を取得
    $date = new DateTime("- 7 days");
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $cookie_token, PDO::PARAM_STR);
    $stmt->bindValue(2, $date->format('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stmt->execute();
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) == 1) {
    	//自動ログイン成功
    	$_SESSION['user_id'] = $rows[0]['USER_ID'];
    
    	return true;
    } else {
    	//自動ログイン失敗
    
    	//Cookie のトークンを削除
    	setCookie("webrez_token", '', -1, "/", null, TRUE, TRUE); // secure, httponly
    
    	 //古くなったトークンを削除
    	delete_old_token($cookie_token, $pdo);
    
    	return false;
    }
}


function check_session_userid(){
    if(empty($_SESSION["user_id"])){
        //セッションのIDがクリアされた場合の再取得処理。
        if(empty($_COOKIE['webrez_token'])){
            //自動ログインが無効の場合、ログイン画面へ
            $_SESSION["EMSG"]="セッションが切れてます。";
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: index.php");
        }elseif(check_auto_login($_COOKIE['webrez_token'],$pdo_h)==false){
            $_SESSION["EMSG"]="自動ログインの有効期限が切れてます";
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: index.php");
        }
        
    }
    return true;
}

// =========================================================
// データ更新時のセキュリティ対応（セッション・クッキー・ポストのチェック）
// =========================================================
function csrf_chk(){
    $csrf_token = $_POST['csrf_token'];
    $cookie_token = $_COOKIE['csrf_token'];
    $session_token = $_SESSION['csrf_token'];
    
    unset($_SESSION['csrf_token']) ; // セッション側のトークンを削除し再利用を防止

    if ($cookie_token != $csrf_token || $csrf_token != $session_token) {
        //不正アクセス
        return false;
        //return true;
    }else{
        //echo "通った [".$cookie_token."::".$csrf_token."::".$session_token."] csrf_chk<br>";
        return true;
    }

}
function csrf_chk_nonsession(){
    //長期滞在できるページはセッション切れを許す
    $csrf_token = $_POST['csrf_token'];
    $cookie_token = $_COOKIE['csrf_token'];

    unset($_SESSION['csrf_token']) ; // セッション側のトークンを削除し再利用を防止
    setCookie("csrf_token", '', -1, "/", null, TRUE, TRUE); // secure, httponly// クッキー側のトークンを削除し再利用を防止

    if ($csrf_token != $cookie_token) {
        //不正アクセス
        return false;
        //return true;
    }else{
        //echo "通った [".$cookie_token."::".$csrf_token."] csrf_chk_nonsession<br>";
        return true;
    }
}
function csrf_chk_nonsession_get($csrf_token){
    //長期滞在できるページはセッション切れを許すGET版 引数にGETを渡す
    
    $cookie_token = $_COOKIE['csrf_token'];

    unset($_SESSION['csrf_token']) ; // セッション側のトークンを削除し再利用を防止
    setCookie("csrf_token", '', -1, "/", null, TRUE, TRUE); // secure, httponly// クッキー側のトークンを削除し再利用を防止

    if ($csrf_token != $cookie_token) {
        //不正アクセス
        return false;
        //return true;
    }else{
        //echo "通った [".$cookie_token."::".$csrf_token."] csrf_chk_nonsession_get<br>";
        return true;
    }
}

function csrf_create(){
    //INPUT HIDDEN で呼ぶ
    $token = get_token();
    $_SESSION['csrf_token'] = $token;

	//自動ログインのトークンを１週間の有効期限でCookieにセット
    //setCookie("webrez_token", $token, time()+60*60*24*7, "/", null, TRUE, TRUE); // secure, httponly
    setCookie("csrf_token", $token, time()+60*60*24*2, "/", null, TRUE, TRUE);
    
    return $token;
}

// =========================================================
// 不可逆暗号化
// =========================================================
function passEx($str,$uid,$key){
//	if(strlen($str)<=8 and !empty($uid)){
	if(strlen($str)>0 and !empty($uid)){
		$rtn = crypt($str,$key);
		for($i = 0; $i < 1000; $i++){
			$rtn = substr(crypt($rtn.$uid,$key),2);
		}
	}else{
		$rtn = $str;
	}
	return $rtn;
}
// =========================================================
// 可逆暗号(日本語文字化け対策)
// =========================================================
function rot13encrypt ($str) {
	//暗号化
    return str_rot13(base64_encode($str));
}

function rot13decrypt ($str) {
	//暗号化解除
    return base64_decode(str_rot13($str));
}
// =========================================================
// XSS対策 post get を echo するときに使用
// =========================================================
function secho($s) {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}
// =========================================================
// PDO の接続オプション取得
// =========================================================
function get_pdo_options() {
  return array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
               PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,   //sqlの複文禁止 "select * from hoge;delete from hoge"みたいなの
               PDO::ATTR_EMULATE_PREPARES => false);        //同上
}

// =========================================================
//登録メール
// =========================================================
function touroku_mail($mail){
$mail2=rot13encrypt($mail);
// 送信元
$from = "From: テスト送信者<information@green-island.mixh.jp>";
$from = "From: テスト送信者<green.green.midori@gmail.com>";
 
// メールタイトル
$subject = "WEBREZ＋ 登録案内";
 
// メール本文
$body = <<< "EOM"
WEBREZ+（ウェブレジプラス）にご興味をもっていただきありがとうございます。
こちらのURLから登録をお願いいたします。

https://green-island.mixh.jp/SaleM/TEST/account_create.php?mode=0&acc=$mail2
EOM;
 
// メール送信
mail($mail, $subject, $body, $from);
return 0;
}

// =========================================================
// バージョン差分修正SQL実行
// =========================================================
function updatedb($SV, $USER, $PASS, $DBNAME,$version,$comment){
    //引数；サーバ、ID、パス、DB名、PGバージョン
    //版管理ルール
    //1.000
    //1：大規模改善
    //0.01～0.99：小規模改善
    //0.001～0.009：バグ改修
    $mysqli_fc = new mysqli($SV, $USER, $PASS, $DBNAME);
    $mysqli_fc->set_charset('utf8');
    
    $sqlstr="select max(version) as version from version;";
    $result = $mysqli_fc->query( $sqlstr );
    $row_cnt = $result->num_rows;
    $row = $result->fetch_assoc(); 


    if((double)$row["version"]<1.00 && (double)$row["version"]<(double)$version){//DBのバージョン＜PGのバージョン
        //差分SQL実行

        echo $row["version"]." now version no<br>";
        echo (string)$version." version up complete!! <br>";
        $sqlstr = "insert into version values(1.00,'".$comment."');";
	    $stmt = $mysqli_fc->query("LOCK TABLES version WRITE");
	    $stmt = $mysqli_fc->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli_fc->query("UNLOCK TABLES");
    }
    if((double)$row["version"]<1.05 && (double)$row["version"]<(double)$version){//DBのバージョン＜PGのバージョン
        //差分SQL実行
        //ユーザテーブルの作成
        //各種テーブルにユーザIDの項目を追加
    }
    $ver="version ".(string)$row["version"];
    //echo $ver."<br>";
    
    $mysqli_fc->close();
}


?>



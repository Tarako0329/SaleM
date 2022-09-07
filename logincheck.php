<?php
date_default_timezone_set('Asia/Tokyo');
require "./vendor/autoload.php";

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
define("MAIN_DOMAIN",$_ENV["MAIN_DOMAIN"]);

$rtn=session_set_cookie_params(24*60*60*24*3,'/','.'.MAIN_DOMAIN,true);
if($rtn==false){
    echo "ERROR:session_set_cookie_params";
    exit();
}
session_start();
session_regenerate_id(true);

$pass=dirname(__FILE__);
require "functions.php";

define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);

//暗号化キー
$key = $_ENV["KEY"];

//パラメーター取得
$mail_id = "";
$password = "";
$auto = "";

$csrf_token = "";
$cookie_token="";
if(!empty($_COOKIE['webrez_token'])){
    $cookie_token = $_COOKIE['webrez_token'];
}

if(!empty($_POST)){
    $mail_id = $_POST['LOGIN_EMAIL'];
    $password = $_POST['LOGIN_PASS'];
    $auto = $_POST['AUTOLOGIN'];
    
    $csrf_token = $_POST['csrf_token'];
}
if(!empty($_GET)){
    $mail_id = $_SESSION["MAIL"];
    $password = $_SESSION["MOTO_PASS"];
    //$auto = $_GET['AUTOLOGIN'];
    
    $csrf_token = $_GET['csrf_token'];
}

//CSRF チェック
if (empty($cookie_token) && $csrf_token != $_SESSION['csrf_token']) {
    //クッキートークンがNULLかつPOSTトークン≠セッショントークンの場合、ログイン画面へ
	$_SESSION = array();
	session_destroy();
	session_start();
	// リダイレクト
	redirect_to_login();
	exit();
}

//ログイン判定フラグ
$normal_result = false;
$auto_result = false;

try {
	// DBとの接続
	$pdo = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

	//簡易ログイン①②
	if (empty($cookie_token)) {
	    //ログイン画面からログインしたらセッション再作成
        $_SESSION['user_id']="";

        $id = check_user($mail_id, $password, $pdo, $key);
        if ($id<>false) {
		    $normal_result = true;
	        $_SESSION['user_id']=$id;
    	    //echo "通った:".$id.":".$auto;
		    //exit;
		}else{
		    $_SESSION["EMSG"]="メールアドレス、又はパスワードが無効です。";
		    //echo "だめだった";
		    //exit;
		}
	}else{
		if (check_auto_login($cookie_token, $pdo)) {
		    $auto_result = true;
		    $id = $_SESSION['user_id']; // 後続の処理のため格納
		}
	}

    
    if (($normal_result && $auto == "on") || $auto_result) {
    //トークン生成処理(通常ログイン画面で自動ONにしてログインした、もしくは自動ログイン機能でログインした場合)
        
    	//トークンの作成
    	$token = get_token();
        
    	//トークンの登録
    	register_token($id, $token, $pdo);
    
    	//自動ログインのトークンを１週間の有効期限でCookieにセット
    	setCookie("webrez_token", $token, time()+60*60*24*7, "/", "", TRUE, TRUE); // secure, httponly
    
    	if ($auto_result) {
    	 //古いトークンの削除
    	 delete_old_token($cookie_token, $pdo);
    	}
    
    	// リダイレクト
    	redirect_to_welcome(get_top($id, $pdo));
    	exit();
    } else if ($normal_result) {
    	// リダイレクト
    	redirect_to_welcome(get_top($id, $pdo));
    } else {
    	// リダイレクト
    	redirect_to_login();
    	exit();
    }
} catch (PDOException $e) {
    die($e->getMessage());
}


//以降関数
/*
* 通常のログイン処理。成功時はuidを返す。
*/
function check_user($mail_id, $password, $pdo,$key) {
    
    $sql = "select uid,loginrez from Users where mail=? and password=?;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $mail_id, PDO::PARAM_STR);
    $stmt->bindValue(2, passEX($password,$mail_id,$key), PDO::PARAM_STR);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row['uid'] <> "") {
    	//ログイン成功
    	return $row['uid'];
    } else {
    	//ログイン失敗
    	$_SESSION["EMSG"]="メールアドレス、もしくはパスワードが不正です。";
    	return false;
    }
}

function get_top($id, $pdo){
    //ログイン後の表示画面を返す
    $sql = "select uid,loginrez from Users where uid=?;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $id, PDO::PARAM_STR);
    $stmt->execute();
    
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row['loginrez'] == "on") {
        return "EVregi.php?mode=evrez";
    }else{
        return "menu.php";
    }
    
}


/*
* トークンの登録
*/
function register_token($id, $token, $pdo) {
    //プレースホルダで SQL 作成
    $sql = "INSERT INTO AUTO_LOGIN ( USER_ID, TOKEN, REGISTRATED_TIME) VALUES (?,?,?);";
    // 現在日時を取得
    $date = date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $id, PDO::PARAM_STR);
    $stmt->bindValue(2, $token, PDO::PARAM_STR);
    $stmt->bindValue(3, $date, PDO::PARAM_STR);
    $stmt->execute();
}


/*
* ログイン画面へのリダイレクト
*/
function redirect_to_login() {
  header("HTTP/1.1 301 Moved Permanently");
  header("Location: index.php");
  exit();
}

/*
* Welcome画面へのリダイレクト
*/
function redirect_to_welcome($a) {
    $_SESSION["status"]="login_redirect";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: ".$a);
    exit();
}


?>
<?php
//コールされるタイミング
//１．Cookieのlogin_type = auto => index.php からリダイレクト
//２．index.phpからI：PASSで通常ログイン
date_default_timezone_set('Asia/Tokyo');
require "./vendor/autoload.php";

//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
define("MAIN_DOMAIN",$_ENV["MAIN_DOMAIN"]);
define("EXEC_MODE",$_ENV["EXEC_MODE"]);

ini_set('session.cookie_domain', '.'.MAIN_DOMAIN);
$rtn=session_set_cookie_params(24*60*60*24*3,'/','.'.MAIN_DOMAIN,true,true);
if($rtn==false){
    echo "ERROR:session_set_cookie_params";
    exit();
}
session_start();

$pass=dirname(__FILE__);
require_once "functions.php";

//log_writer2("logincheck.php MAIN_DOMAIN ",MAIN_DOMAIN,"lv3");
//log_writer2("logincheck.php > \$_SESSION",$_SESSION,"lv3");
//log_writer2("logincheck.php > \$POST",$_POST,"lv3");

define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);

$key = $_ENV["KEY"];//暗号化キー

//パラメーター取得
$mail_id = "";
$password = "";
$auto = "off";

$csrf_token = "";
$cookie_token="";

//ログイン判定フラグ
$normal_result = false;
$auto_result = false;

//自動ログイン情報の取得
$login_type = (!empty($_COOKIE["login_type"])?$_COOKIE["login_type"]:"normal");

if(!empty($_COOKIE['webrez_token']) && $login_type==="auto"){
    $cookie_token = $_COOKIE['webrez_token'];
}else if(!empty($_POST)){
    $mail_id = $_POST['LOGIN_EMAIL'];
    $password = $_POST['LOGIN_PASS'];
    $auto = $_POST['AUTOLOGIN'];
    $csrf_token = $_POST['csrf_token'];
}else{
    redirect_to_login("不正アクセス。処理を中止します。");
}

//CSRF チェック　通常ログインかつPOSTトークン≠セッショントークンの場合、ログイン画面へ
if ($login_type==="normal" && $csrf_token != $_SESSION['csrf_token']) {
	redirect_to_login("セッションが不正です");
}

try{
	$pdo = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());// DBとの接続
    $token = get_token();   //トークンの作成

    if ($login_type==="normal") {
        //ログイン画面からログインしたらセッション再作成
        $_SESSION['user_id']="";
        setCookie("webrez_token", "", -1, "/", "", TRUE, TRUE); // secure, httponly
        setCookie("login_type", "normal", time()+60*60*24*7, "/", "", TRUE, TRUE); // secure, httponly
        $id = check_user($mail_id, $password, $pdo, $key);
        if ($id<>false) {
		    $normal_result = true;
	        $_SESSION['user_id']=$id;
		}else{
            redirect_to_login("メールアドレス、又はパスワードが無効です。");
		}
	}else{//$login_type==="auto"
        $rtn = check_auto_login($cookie_token, $pdo);
		if($rtn===true){
		    $auto_result = true;
		    $id = $_SESSION['user_id']; // 後続の処理のため格納
		}else{
            redirect_to_login($rtn);    //自動ログイン失敗
        }
	}

    if (($normal_result===true && $auto === "on") || $auto_result===true) {
        //通常ログイン画面で自動ONにしてログイン or 自動ログイン機能でログイン
    	register_token($id, $token, $pdo);  //自動ログインの登録
    }

    if(EXEC_MODE!=="Local"){
        //session_regenerate_id(true);
    }
    redirect_to_welcome($id, $pdo);
    
}catch (PDOException $e){
    die($e->getMessage());
    log_writer2("logincheck.php",$e->getMessage(),"lv0");
    redirect_to_login("ログインできませんでした。");
}

exit();

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

/*
* トークンの登録
*/
function register_token($id, $token, $pdo) {
    $sql = "INSERT INTO AUTO_LOGIN ( USER_ID, TOKEN, REGISTRATED_TIME) VALUES (?,?,?);";
    // 現在日時を取得
    $date = date('Y-m-d H:i:s');
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $id, PDO::PARAM_STR);
    $stmt->bindValue(2, $token, PDO::PARAM_STR);
    $stmt->bindValue(3, $date, PDO::PARAM_STR);
    $stmt->execute();
    setCookie("webrez_token", $token, time()+60*60*24*7, "/", "", TRUE, TRUE); // secure, httponly
    setCookie("login_type", "auto", time()+60*60*24*7, "/", "", TRUE, TRUE); // secure, httponly
}


/*
* Welcome画面へのリダイレクト
*/
function redirect_to_welcome($id, $pdo) {
    //topメニュー or レジ画面へ
    $sql = "select uid,loginrez from Users where uid=?;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $id, PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row['loginrez'] == "on") {
        $a="EVregi.php?mode=evrez";
    }else{
        $a="menu.php";
    }

    $_SESSION["status"]="login_redirect";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: ".$a);
    exit();
}


?>
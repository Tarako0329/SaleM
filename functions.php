<?php
session_start();

// =========================================================
// オンラインリンク　設定ファイル
// =========================================================
//データベース設定

define("sv", "localhost");

define("key","bonBer");


//=========================================================
//  本番・テストのデータベースの切り替えはここで行います。
//  有効にしたいコードをコメントから外してください。
//=========================================================
//データベース切り替え

if(__FILE__=="/home/ifduktdo/public_html/SaleM/TEST/functions.php"){
	//echo "test";
	define("dbname", "ifduktdo_SaleM_test");
    define("user", "ifduktdo_SaleM_test");
    define("pass", "Ky0u2uke");
}else if(__FILE__=="/home/ifduktdo/public_html/SaleM/CafePresents/functions.php"){
	//echo "本番";
	define("dbname", "ifduktdo_CafePresent");
    define("user", "ifduktdo_CafePresent");
    define("pass", "H1mur0Ky0u2uke");
}else{
	echo "ERROR<BR>";
	exit();
}

//define("dbname", "ifduktdo_CafePresent");
$mysqli = new mysqli(sv, user, pass, dbname);

// =========================================================
// MySQLエラーレポート用共通宣言
// =========================================================
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);


// =========================================================
// 不可逆暗号化
// =========================================================
function passEx($str,$uid){
	if(strlen($str)<=8 and !empty($uid)){
		$rtn = crypt($str,key);
		for($i = 0; $i < 1000; $i++){
			$rtn = substr(crypt($rtn.$uid,key),2);
		}
	}else{
		$rtn = $str;
	}
	return $rtn;
}
// =========================================================
// 可逆暗号
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
// アクセスログ記録
// =========================================================

//未実装

// =========================================================
// 自動ログインチェック
// =========================================================




?>



<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

*/

require "php_header.php";
log_writer("\$_POST",$_POST,"lv3");

//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);

$rtn = csrf_checker(["shouhinMSList.php"],["P","C","S"]);
$status = "success";
if($rtn !== true){
	$msg = "セッションが不正です";
	$status = "failure";
}else{
	$sqllog="";
	
	$csrf_token=csrf_create();
	try{
		//トランザクション開始

		$pdo_h->beginTransaction();
		$sqllog .= rtn_sqllog("START TRANSACTION",[]);

		$params["uid"]=$_SESSION['user_id'];
		$params["shouhinCD"]=$_POST["shouhinCD"];
		$params["hyoujiKBN1"]=$_POST["disp_rezi"];

		$sqlstr="UPDATE shouhinMS set hyoujiKBN1 = :hyoujiKBN1 where uid = :uid and shouhinCD = :shouhinCD";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue("uid", $params["uid"], PDO::PARAM_INT);
		$stmt->bindValue("shouhinCD", $params["shouhinCD"], PDO::PARAM_INT);
		$stmt->bindValue("hyoujiKBN1", $params["hyoujiKBN1"], PDO::PARAM_STR);

		$sqllog .= rtn_sqllog($sqlstr,$params);
		$stmt->execute();
		$pdo_h->commit();
		$sqllog .= rtn_sqllog("commit",[]);
		sqllogger($sqllog,0);

		//$msg = $_POST["shouhinNM"]."　登録完了。";

	}catch(Exception $e){
		$pdo_h->rollBack();
		$sqllog .= rtn_sqllog("rollBack",[]);
		sqllogger($sqllog,$e);
		$status = "failure";
		$msg = "登録に失敗しました。";
		log_writer2(basename(__FILE__)."[\$_POST]",$_POST,"lv0");
	}
}

$return_sts = array(
	"MSG" => $msg
	,"status" => $status
	,"csrf_create" => $csrf_token
);
header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);

exit();

?>

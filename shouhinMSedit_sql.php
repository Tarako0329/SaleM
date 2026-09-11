<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

*/

require "php_header.php";
// ★追加: cURL（API）からの呼び出し判定
$is_api = $_POST['is_api'] ?? false;
$result = ['success' => false, 'message' => ''];
//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);

$rtn = csrf_checker(["shouhinMSedit.php"],["P","C","S"]);
if($rtn !== true && !$is_api){
	redirect_to_login($rtn);
}
$sqllog="";

//税区分MSから税率の取得 
try{
	//$pdo_h->beginTransaction();
	//$sqllog .= rtn_sqllog("START TRANSACTION",[]);
	$db->begin_tran();

	//$sqlstr="SELECT * from ZeiMS where zeiKBN=?";
	$sqlstr="SELECT * from ZeiMS where zeiKBN=:zeiKBN";
	/*
	$stmt = $pdo_h->prepare($sqlstr);
	$stmt->bindValue(1, $_POST["zeikbn"], PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
	*/
	$row = $db->SELECT(
		$sqlstr,
		[":zeiKBN"=>$_POST["zeikbn"]],
	);
	$zeikbn = $row[0]["zeiKBN"];
	$zeiritu= $row[0]["zeiritu"];
	
	//商品CDの取得
	/*
		$sqlstr="select max(shouhinCD) as MCD from ShouhinMS where uid=? group by uid";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$new_shouhinCD = $row[0]["MCD"]+1;
	*/
	$new_shouhinCD = get_new_ShouhinCD($_SESSION['user_id']);

	$params["uid"]=$_SESSION['user_id'];
	$params["shouhinCD"]=$new_shouhinCD;
	$params["shouhinNM"]=$_POST["shouhinNM"];
	$params["tanka"]=$_POST["tanka"] ?? 0; 
	$params["zeitanka"]=$_POST["shouhizei"];
	$params["zeiritu"]=$zeiritu;
	$params["zeiKBN"]=$zeikbn;
	$params["utisu"]=$_POST["utisu"];
	$params["tani"]=$_POST["tani"];
	$params["genka_tanka"]=U::exist($_POST["genka"])?$_POST["genka"]:0;
	$params["hyoujiKBN1"]=$_POST["hyoujiKBN1"];
	
	$sqlstr="INSERT into ShouhinMS(uid,shouhinCD,shouhinNM,tanka,tanka_zei,zeiritu,zeiKBN,utisu,tani,genka_tanka,hyoujiKBN1) values(:uid,:shouhinCD,:shouhinNM,:tanka,:zeitanka,:zeiritu,:zeiKBN,:utisu,:tani,:genka_tanka,:hyoujiKBN1)";
	/*
	$stmt = $pdo_h->prepare($sqlstr);
	$stmt->bindValue("uid", $params["uid"], PDO::PARAM_INT);
	$stmt->bindValue("shouhinCD", $params["shouhinCD"], PDO::PARAM_INT);
	$stmt->bindValue("shouhinNM", $params["shouhinNM"], PDO::PARAM_STR);
	$stmt->bindValue("tanka", $params["tanka"], PDO::PARAM_INT);
	$stmt->bindValue("zeitanka", $params["zeitanka"], PDO::PARAM_INT);
	$stmt->bindValue("zeiritu", $params["zeiritu"], PDO::PARAM_INT);
	$stmt->bindValue("zeiKBN", $params["zeiKBN"], PDO::PARAM_INT);
	$stmt->bindValue("utisu", $params["utisu"], PDO::PARAM_INT);
	$stmt->bindValue("tani", $params["tani"], PDO::PARAM_STR);
	$stmt->bindValue("genka_tanka", $params["genka_tanka"], PDO::PARAM_INT);
	$stmt->bindValue("hyoujiKBN1", $params["hyoujiKBN1"], PDO::PARAM_STR);

	$sqllog .= rtn_sqllog($sqlstr,$params);
	$stmt->execute();
	$pdo_h->commit();
	$sqllog .= rtn_sqllog("commit",[]);
	sqllogger($sqllog,0);
	*/
	$db->UP_DEL_EXEC(
		$sqlstr,
		$params
	);
	$db->commit_tran();
	$_SESSION["MSG"] = secho($_POST["shouhinNM"])."　が登録されました。";
	$result['success'] = true;
	$result['message'] = "商品が正常に登録されました。";
}catch(\Throwable $e){
	/*$pdo_h->rollBack();
	$sqllog .= rtn_sqllog("rollBack",[]);
	sqllogger($sqllog,$e);
	log_writer2(basename(__FILE__)."[\$_POST]",$_POST,"lv0");
	*/
	$db->Exception_rollback($e,"商品マスタの登録でエラーが発生しました。");
	$_SESSION["MSG"] = "登録が失敗しました。";
	$result['success'] = false;
	$result['message'] = "商品の登録に失敗しました。";
}

$stmt  = null;
$pdo_h = null;

if ($is_api) {
  // cURL呼び出しの場合はリダイレクトせず、JSON形式で結果を返して終了する
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($result, JSON_UNESCAPED_UNICODE);
  exit;
}

$csrf_token=csrf_create();
header("HTTP/1.1 301 Moved Permanently");
header("Location:shouhinMSedit.php?csrf_token=".$csrf_token);
exit();

?>

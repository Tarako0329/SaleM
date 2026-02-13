<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

*/

require "php_header.php";
log_writer("\$_POST",$_POST,"lv3");
log_writer("\$_POST",json_decode($_POST["data"]),"lv3");

//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);

$rtn = csrf_checker(["shouhinMSedit.php","shouhinMSList.php"],["P","C","S"]);
$status = "success";
if($rtn !== true){
	$msg = "セッションが不正です";
	$status = "failure";
}else{
	$sqllog="";
	
	$csrf_token=csrf_create();
	$values = json_decode($_POST["data"],true);
	log_writer("\$values",$values,"lv3");
	try{
		//トランザクション開始

		$pdo_h->beginTransaction();
		$sqllog .= rtn_sqllog("START TRANSACTION",[]);

		//税区分MSから税率の取得 
		$sqlstr="select * from ZeiMS where zeiKBN=?";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue(1, $values["new_zeiKBN"], PDO::PARAM_INT);
		$stmt->execute();
		$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
		$zeikbn = $row[0]["zeiKBN"];
		$zeiritu= $row[0]["zeiritu"];
		$zeihyoujimei= $row[0]["hyoujimei"];

		//新規登録の場合、商品CDを取得
		if(empty($values["shouhinCD"])){
			$sqlstr="select max(shouhinCD) as MCD from ShouhinMS where uid=? group by uid";
			$stmt = $pdo_h->prepare($sqlstr);
			$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
			$stmt->execute();
			$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
			$shouhinCD = $row[0]["MCD"]+1;
		}else{
			$shouhinCD = $values["shouhinCD"];
			//shouhinMSから削除
			$sqlstr="delete from ShouhinMS where shouhinCD=? and uid=?";
			$stmt = $pdo_h->prepare($sqlstr);
			$stmt->bindValue(1, $shouhinCD, PDO::PARAM_INT);
			$stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
			$sqllog .= rtn_sqllog($sqlstr,[$shouhinCD,$_SESSION['user_id']]);
			$stmt->execute();
			$sqllog .= rtn_sqllog("-- execute():正常終了",[]);
		}


		$params["uid"]=$_SESSION['user_id'];
		$params["shouhinCD"]=$shouhinCD;
		$params["shouhinNM"]=$values["shouhinNM"];
		$params["tanka"]=$values["new_tanka"]; 
		$params["zeitanka"]=$values["new_tanka_zei"];
		$params["zeiritu"]=$zeiritu;
		$params["zeiKBN"]=$zeikbn;
		$params["utisu"]=$values["utisu"];
		$params["tani"]=$values["tani"];
		$params["genka_tanka"]=$values["new_genka"];
		$params["hyoujiKBN1"]=$values["hyoujiKBN1"];

		$sqlstr="INSERT into ShouhinMS(uid,shouhinCD,shouhinNM,tanka,tanka_zei,zeiritu,zeiKBN,utisu,tani,genka_tanka,hyoujiKBN1) values(:uid,:shouhinCD,:shouhinNM,:tanka,:zeitanka,:zeiritu,:zeiKBN,:utisu,:tani,:genka_tanka,:hyoujiKBN1)";
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

		$msg = $values["shouhinNM"]."　登録完了。";

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
	,'new_hyoujimei' => $zeihyoujimei
);
header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);

exit();

?>

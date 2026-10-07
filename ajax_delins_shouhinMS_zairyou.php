<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

*/

require "php_header.php";
U::log("\$_POST",$_POST);

//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);
$rtn = csrf_checker(["genka.php"],["P","C","S"]);
$return = "";
$status = "success";
if($rtn !== true){
	$msg = "セッションが不正です";
	$status = "failure";
}else{
	
	try{
		//トランザクション開始

		$db->begin_tran();

		$params["uid"]=$_SESSION['user_id'];
		$params["shouhinCD"]=$_POST["shouhinCD"];

		$sqlstr = "DELETE FROM ShouhinMS‗Genzairyou where `uid` = :uid and shouhinCD = :shouhinCD";
		$return = $db->UP_DEL_EXEC($sqlstr,$params);

		$zairyou = json_decode($_POST["zairyou_list"], true);
		U::log("zairyou", $zairyou);
		foreach($zairyou as $row){
			$params["uid"]	= $_SESSION['user_id'];
			$params["shouhinCD"]	= $_POST["shouhinCD"];
			$params["zairyouCD"]	= $row["zairyouCD"];
			$params["use_volume"]	= $row["use_volume"];
			$params["genka"]	= $row["hiyou"];

			$sqlstr = "INSERT INTO ShouhinMS‗Genzairyou(`uid`,shouhinCD,zairyouCD,use_vol,genka) values(:uid,:shouhinCD,:zairyouCD,:use_volume,:genka)";
			$return = $db->UP_DEL_EXEC($sqlstr,$params);
		}


		$db->commit_tran();

	}catch(\Throwable $e){
		$db->Exception_rollback($e,"商品マスタの原価情報登録でエラー。至急調査してください。");
		$status = "failure";
		$msg = "登録に失敗しました。";
	}
}
$csrf_token=csrf_create();
$return_sts = array(
	"MSG" => $msg
	,"status" => $status
	,"csrf_token" => $csrf_token
	,"new_zairyouCD" => $return
);
header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);

exit();

?>

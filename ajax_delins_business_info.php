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

$rtn = csrf_checker(["analysis_ai_menu.php"],["P","C","S"]);
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
		$params["app"]=$_POST["app"];
		$params["Product_categories"]=$_POST["Product_categories"]; 
		$params["Sales_methods"]=$_POST["Sales_methods"];
		$params["Brand_image"]=$_POST["Brand_image"];
		$params["Monthly_goals"]=$_POST["Monthly_goals"];
		$params["This_year_goals"]=$_POST["This_year_goals"];
		$params["Next_year_goals"]=$_POST["Next_year_goals"];
		$params["Ideal_5_years"]=$_POST["Ideal_5_years"];
		$params["Customer_targets"]=$_POST["Customer_targets"];
		$params["Instagram"]=$_POST["Instagram"];
		$params["X_com"]=$_POST["X_com"];
		$params["facebook"]=$_POST["facebook"];
		$params["Threads"]=$_POST["Threads"];
		$params["tiktok"]=$_POST["tiktok"];
		$params["other_SNS"]=$_POST["other_SNS"];

		//delete
		$sqlstr="DELETE from business_info where uid=:uid and app=:app";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue("uid", $params["uid"], PDO::PARAM_INT);
		$stmt->bindValue("app", $params["app"], PDO::PARAM_STR);
		$sqllog .= rtn_sqllog($sqlstr,$params);
		$stmt->execute();
		$sqllog .= rtn_sqllog("--execute():正常終了",[]);

		//INSERT
		$sqlstr="INSERT into business_info(uid,app,Product_categories,Sales_methods,Brand_image,Monthly_goals,This_year_goals,Next_year_goals,Ideal_5_years,Customer_targets,Instagram,X_com,facebook,Threads,tiktok,other_SNS) values(:uid,:app,:Product_categories,:Sales_methods,:Brand_image,:Monthly_goals,:This_year_goals,:Next_year_goals,:Ideal_5_years,:Customer_targets,:Instagram,:X_com,:facebook,:Threads,:tiktok,:other_SNS)";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue("uid", $params["uid"], PDO::PARAM_INT);
		$stmt->bindValue("app", $params["app"], PDO::PARAM_STR);
		$stmt->bindValue("Product_categories", $params["Product_categories"], PDO::PARAM_STR);
		$stmt->bindValue("Sales_methods", $params["Sales_methods"], PDO::PARAM_STR);
		$stmt->bindValue("Brand_image", $params["Brand_image"], PDO::PARAM_STR);
		$stmt->bindValue("Monthly_goals", $params["Monthly_goals"], PDO::PARAM_STR);
		$stmt->bindValue("This_year_goals", $params["This_year_goals"], PDO::PARAM_STR);
		$stmt->bindValue("Next_year_goals", $params["Next_year_goals"], PDO::PARAM_STR);
		$stmt->bindValue("Ideal_5_years", $params["Ideal_5_years"], PDO::PARAM_STR);
		$stmt->bindValue("Customer_targets", $params["Customer_targets"], PDO::PARAM_STR);
		$stmt->bindValue("Instagram", $params["Instagram"], PDO::PARAM_STR);
		$stmt->bindValue("X_com", $params["X_com"], PDO::PARAM_STR);
		$stmt->bindValue("facebook", $params["facebook"], PDO::PARAM_STR);
		$stmt->bindValue("Threads", $params["Threads"], PDO::PARAM_STR);
		$stmt->bindValue("tiktok", $params["tiktok"], PDO::PARAM_STR);
		$stmt->bindValue("other_SNS", $params["other_SNS"], PDO::PARAM_STR);

		$sqllog .= rtn_sqllog($sqlstr,$params);
		$stmt->execute();
		$pdo_h->commit();
		$sqllog .= rtn_sqllog("commit",[]);
		sqllogger($sqllog,0);

		$msg = "登録完了。";

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

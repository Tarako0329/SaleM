<?php
//log_writer2(basename(__FILE__)."",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";                          //ユーザー向け処理結果メッセージ
$status = "warning";    //bootstrap alert class
$reseve_status=false;               //処理結果セット済みフラグ。
$sqllog="";
$uid="";
log_writer2(basename(__FILE__)." [\$_POST]",$_POST,"lv3");
log_writer2(basename(__FILE__)." [\$_SESSION]",$_SESSION,"lv3");
$rtn = csrf_checker(["index.php","/","","pre_account.php"],["P","S"]);
if($rtn !== true){
	$msg=$rtn;
	$status = "warning";
	$reseve_status = true;
}else{
	$stmt = $pdo_h->prepare("select * from id_map where sub_id = :subid");
	$stmt->bindValue("subid", $_POST["sub_id"], PDO::PARAM_STR);
	$stmt->execute();
	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if(!empty($row[0]["uid"])){
		$uid = $row[0]["uid"];
	}else{
		try{
			$pdo_h->beginTransaction();
			$sqllog .= rtn_sqllog("START TRANSACTION",[]);
			//ユーザID作成
			$stmt = $pdo_h->prepare("select uid from Users where uid = :uid FOR UPDATE");
			while(true){
				//乱数からユーザIDを発行し、重複してなければ使用する
				$params["uid"] = rand(0,99999);
				log_writer2("\$uid",$params["uid"],"lv3");
				$stmt->bindValue("uid", $params["uid"], PDO::PARAM_INT);
				$stmt->execute();
				$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
				if(empty($row[0]["uid"])){
					break;
				}
			}
			$params["sub_id"] = $_POST["sub_id"];
			$params["mail"] = $_POST["mail"];
			$params["pass"] = csrf_create();
			$params["yuukoukigen"] = date('Y-m-d', strtotime(date("Y-m-d") . "+2 month"));
			$params["introducer_id"] = (!empty($_POST["shoukai"]))?$_POST["shoukai"]:NULL;

			$sqlstr="insert into id_map(uid,sub_id) values(:uid,:sub_id)";
			$stmt = $pdo_h->prepare($sqlstr);
			$stmt->bindValue("uid", $params["uid"], PDO::PARAM_INT);
			$stmt->bindValue("sub_id", $params["sub_id"], PDO::PARAM_STR);
			$sqllog .= rtn_sqllog($sqlstr,$params);
			$status=$stmt->execute();
			$sqllog .= rtn_sqllog("-- execute():正常終了",[]);

			$sqlstr="insert into Users(uid,mail,password,question,answer,webrez) values(:uid,:mail,:pass,'あなたの花言葉は？','食パンマ','use')";
			$stmt = $pdo_h->prepare($sqlstr);
			$stmt->bindValue("uid", $params["uid"], PDO::PARAM_INT);
			$stmt->bindValue("mail", $params["mail"], PDO::PARAM_STR);
			$stmt->bindValue("pass", $params["pass"], PDO::PARAM_STR);
			$sqllog .= rtn_sqllog($sqlstr,$params);
			$status=$stmt->execute();
			$sqllog .= rtn_sqllog("-- execute():正常終了",[]);

			$sqlstr="insert into Users_webrez(uid,yuukoukigen,introducer_id) values(:uid,:yuukoukigen,:introducer_id)";
			$stmt = $pdo_h->prepare($sqlstr);
			$stmt->bindValue("uid", $params["uid"], PDO::PARAM_INT);
			$stmt->bindValue("yuukoukigen", $params["yuukoukigen"], PDO::PARAM_STR);
			$stmt->bindValue("introducer_id", $params["introducer_id"], PDO::PARAM_STR);
			$sqllog .= rtn_sqllog($sqlstr,$params);
			$status=$stmt->execute();
			$sqllog .= rtn_sqllog("-- execute():正常終了",[]);

			$pdo_h->commit();
			$sqllog .= rtn_sqllog("commit",[]);
			sqllogger($sqllog,0);

			$uid = $params["uid"];
			$msg = "登録が完了しました。";
			$status = "success";
			$reseve_status=true;
		}catch(Exception $e){
			$pdo_h->rollBack();
			$sqllog .= rtn_sqllog("rollBack",[]);
			sqllogger($sqllog,$e);
			$msg = "システムエラーによる更新失敗。管理者へ通知しました。";
			$status = "danger";
			log_writer2(basename(__FILE__)." [Exception \$e] =>",$e,"lv0");
			$reseve_status=true;
		}
	}
}

$token = csrf_create();

$return_sts = array(
	"MSG" => $msg
	,"status" => $status
	,"token" => $token
	,"uid" => $uid
);
header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);

exit();


function shutdown(){
	// シャットダウン関数
	// スクリプトの処理が完了する前に
	// ここで何らかの操作をすることができます
	// トランザクション中のエラー停止時は自動rollbackされる。
	  $lastError = error_get_last();
	  
	  //直前でエラーあり、かつ、catch処理出来ていない場合に実行
	  if($lastError!==null && $GLOBALS["reseve_status"] === false){
		log_writer2(basename(__FILE__),"shutdown","lv3");
		log_writer2(basename(__FILE__),$lastError,"lv1");
		  
		$emsg = "uid::".$_SESSION['user_id']." ERROR_MESSAGE::予期せぬエラー".$lastError['message'];
		if(EXEC_MODE!=="Local"){
			send_mail(SYSTEM_NOTICE_MAIL,"【WEBREZ-WARNING】".basename(__FILE__)."でシステム停止",$emsg);
		}
		log_writer2(basename(__FILE__)." [Exception \$lastError] =>",$lastError,"lv0");
	
		$token = csrf_create();
		$return_sts = array(
			"MSG" => "システムエラーによる更新失敗。管理者へ通知しました。"
			,"status" => "danger"
			,"csrf_create" => $token
			,"timeout" => false
		);
		header('Content-type: application/json');
		echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);
	  }
  }
  

?>
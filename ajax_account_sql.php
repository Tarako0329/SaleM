<?php
//log_writer2(basename(__FILE__)."",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";                          //ユーザー向け処理結果メッセージ
$alert_status = "alert-warning";    //bootstrap alert class
$reseve_status=false;               //処理結果セット済みフラグ。
$timeout=false;                     //セッション切れ。ログイン画面に飛ばすフラグ

log_writer2(basename(__FILE__)." [\$_POST]",$_POST,"lv3");
log_writer2(basename(__FILE__)." [\$_SESSION]",$_SESSION,"lv3");
$rtn = csrf_checker(["account_create.php"],["P","C","S"]);
if($rtn !== true){
	$msg=$rtn;
	$alert_status = "alert-warning";
	$reseve_status = true;
}else{
	if($_POST["mode"]==="update"){
		$rtn=check_session_userid_for_ajax($pdo_h);
	}else if($_POST["mode"]==="insert"){
		$rtn=true;
	}else{
		$rtn=false;
	}
	
	if($rtn===false){
		$reseve_status = true;
		$msg="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度操作し直して下さい。";
		$_SESSION["EMSG"]="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度操作し直して下さい。";
		$timeout=true;
	}else{
		$logfilename="sid_".$_SESSION['user_id'].".log";
		try{
			$pdo_h->beginTransaction();
			$sqllog .= rtn_sqllog("START TRANSACTION",[]);

			if($_POST["mode"] === "insert"){
				$sqlstr="insert into Users(uid,mail,password,question,answer,yuukoukigen,introducer_id) values(0,?,?,?,?,?,?)";
				$stmt = $pdo_h->prepare($sqlstr);
				$stmt->bindValue(1, $_SESSION["P"][0], PDO::PARAM_STR);
				$stmt->bindValue(2, $_SESSION["P"][1], PDO::PARAM_STR);
				$stmt->bindValue(3, $_SESSION["P"][2], PDO::PARAM_STR);
				$stmt->bindValue(4, $_SESSION["P"][3], PDO::PARAM_STR);
				$stmt->bindValue(5, $_SESSION["P"][4], PDO::PARAM_STR);
				$stmt->bindValue(6, $_SESSION["P"][5], PDO::PARAM_STR);
				$sqllog .= rtn_sqllog($sqlstr,$_SESSION["P"]);
				$status=$stmt->execute();
				$sqllog .= rtn_sqllog("--execute():正常終了",[]);
				//$count = $stmt->rowCount();
				
				if($status){
					$_SESSION["EMSG"]="パスワードを入力し、ログインしてください。<br>[AUTOLOGIN]のチェックを外すと自動ログインを行いません。セキュリティが気になる方は外してください。";
				}
			}elseif($_POST["mode"] === "update"){
			
				if($_SESSION["chk_pass"]==="on"){
					$sqlstr="update Users set mail=:mail,password=:password,question=:question,answer=:answer,loginrez=:loginrez,name=:name,yagou=:yagou,yubin=:yubin,address1=:address1,address2=:address2,address3=:address3,invoice_no=:invoice_no where uid=:uid";
					$stmt = $pdo_h->prepare($sqlstr);
					$stmt->bindValue("password", $_SESSION["P"]["password"], PDO::PARAM_STR);
				}else{
					$sqlstr="update Users set mail=:mail,question=:question,answer=:answer,loginrez=:loginrez,name=:name,yagou=:yagou,yubin=:yubin,address1=:address1,address2=:address2,address3=:address3,invoice_no=:invoice_no ,inquiry_tel=:inquiry_tel ,inquiry_mail=:inquiry_mail where uid=:uid";
					$stmt = $pdo_h->prepare($sqlstr);
				}
				$stmt->bindValue("mail", $_SESSION["P"]["mail"], PDO::PARAM_STR);
				$stmt->bindValue("question", $_SESSION["P"]["question"], PDO::PARAM_STR);
				$stmt->bindValue("answer", $_SESSION["P"]["answer"], PDO::PARAM_STR);
				$stmt->bindValue("loginrez", $_SESSION["P"]["loginrez"], PDO::PARAM_STR);
				$stmt->bindValue("name", $_SESSION["P"]["name"], PDO::PARAM_STR);
				$stmt->bindValue("yagou", $_SESSION["P"]["yagou"], PDO::PARAM_STR);
				$stmt->bindValue("yubin", $_SESSION["P"]["yubin"], PDO::PARAM_STR);
				$stmt->bindValue("address1", $_SESSION["P"]["address1"], PDO::PARAM_STR);
				$stmt->bindValue("address2", $_SESSION["P"]["address2"], PDO::PARAM_STR);
				$stmt->bindValue("address3", $_SESSION["P"]["address3"], PDO::PARAM_STR);
				$stmt->bindValue("uid", $_SESSION["P"]["uid"], PDO::PARAM_INT);
				$stmt->bindValue("invoice_no", $_SESSION["P"]["invoice_no"], PDO::PARAM_INT);
				$stmt->bindValue("inquiry_tel", $_SESSION["P"]["inquiry_tel"], PDO::PARAM_STR);
				$stmt->bindValue("inquiry_mail", $_SESSION["P"]["inquiry_mail"], PDO::PARAM_STR);
				$sqllog .= rtn_sqllog($sqlstr,$_SESSION["P"]);
				$status=$stmt->execute();
				$sqllog .= rtn_sqllog("--execute():正常終了",[]);
				//$count = 1;
			}
			log_writer2(basename(__FILE__)." [\$status]",$status,"lv3");
			log_writer2(basename(__FILE__)." [\$count]",$count,"lv3");
			$pdo_h->commit();
			$sqllog .= rtn_sqllog("commit",[]);
			sqllogger($sqllog,0);
	
			$msg = "登録が完了しました。";
			$alert_status = "alert-success";
		/*
			if($status!==false && $count<>0){
				$pdo_h->commit();
				$sqllog .= rtn_sqllog("commit",[]);
				sqllogger($sqllog,0);
		
				$msg = "登録が完了しました。";
				$alert_status = "alert-success";
			}else{
				$pdo_h->rollBack();
				$sqllog .= rtn_sqllog("rollBack",[]);
				$msg = "失敗。";
				$alert_status = "alert-danger";
				sqllogger($sqlstr,$_SESSION["P"],basename(__FILE__),"ng");
			}
		*/
			$reseve_status=true;
		}catch(Exception $e){
			$pdo_h->rollBack();
			$sqllog .= rtn_sqllog("rollBack",null);
			sqllogger($sqllog,$e);
			$msg = "システムエラーによる更新失敗。管理者へ通知しました。";
			$alert_status = "alert-danger";
			//log_writer2(basename(__FILE__)." [Exception \$e] =>",$e,"lv0");
			$reseve_status=true;
		}
	}
}

$token = csrf_create();

$return_sts = array(
	"MSG" => $msg
	,"status" => $alert_status
	,"csrf_create" => $token
	,"timeout" => $timeout
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
			,"status" => "alert-danger"
			,"csrf_create" => $token
			,"timeout" => false
		);
		header('Content-type: application/json');
		echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);
	  }
  }
  

?>
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

			if($_POST["mode"] === "insert"){
				//試用期間は２ヶ月に変更。
				$kigen=date('Y-m-d', strtotime(date("Y-m-d") . "+2 month"));
				
				//$sqlstr="insert into Users(uid,mail,password,question,answer,loginrez,insdate,yuukoukigen,introducer_id) values(0,?,?,?,?,?,?,?,?)";
				$sqlstr="insert into Users(uid,mail,password,question,answer,insdate,yuukoukigen,introducer_id) values(0,?,?,?,?,?,?,?)";
				$stmt = $pdo_h->prepare($sqlstr);
				$stmt->bindValue(1, $_SESSION["MAIL"], PDO::PARAM_STR);
				$stmt->bindValue(2, $_SESSION["PASS"], PDO::PARAM_STR);
				$stmt->bindValue(3, $_SESSION["QUESTION"], PDO::PARAM_STR);
				$stmt->bindValue(4, $_SESSION["ANSWER"], PDO::PARAM_STR);
				//$stmt->bindValue(5, $_SESSION["LOGINREZ"], PDO::PARAM_STR);
				$stmt->bindValue(5, date("Y-m-d"), PDO::PARAM_STR);
				$stmt->bindValue(6, $kigen, PDO::PARAM_STR);
				$stmt->bindValue(7, $_SESSION["SHOUKAI"], PDO::PARAM_STR);
			
				$status=$stmt->execute();
				$count = $stmt->rowCount();
				
				if($status){
					$_SESSION["EMSG"]="パスワードを入力し、ログインしてください。<br>[AUTOLOGIN]のチェックを外すと自動ログインを行いません。セキュリティが気になる方は外してください。";
					/*
					$stmt2 = $pdo_h->prepare("select uid from Users where mail=?");
					$stmt2->bindValue(1, $_SESSION["MAIL"], PDO::PARAM_STR);
					$stmt2->execute();
					$tmp = $stmt2->fetchAll(PDO::FETCH_ASSOC);
					$_SESSION["user_id"]=$tmp[0]["uid"];
					*/
				}
			}elseif($_POST["mode"] === "update"){
			
				if($_SESSION["chk_pass"]==="on"){
					$sqlstr="update Users set mail=:mail,password=:password,question=:question,answer=:answer,loginrez=:loginrez,name=:name,yagou=:yagou,yubin=:yubin,address1=:address1,address2=:address2,address3=:address3,invoice_no=:invoice_no where uid=:uid";
					$stmt = $pdo_h->prepare($sqlstr);
					$stmt->bindValue("password", $_SESSION["PASS"], PDO::PARAM_STR);
				}else{
					$sqlstr="update Users set mail=:mail,question=:question,answer=:answer,loginrez=:loginrez,name=:name,yagou=:yagou,yubin=:yubin,address1=:address1,address2=:address2,address3=:address3,invoice_no=:invoice_no where uid=:uid";
					$stmt = $pdo_h->prepare($sqlstr);
				}
				$stmt->bindValue("mail", $_SESSION["MAIL"], PDO::PARAM_STR);
				$stmt->bindValue("question", $_SESSION["QUESTION"], PDO::PARAM_STR);
				$stmt->bindValue("answer", $_SESSION["ANSWER"], PDO::PARAM_STR);
				$stmt->bindValue("loginrez", $_SESSION["LOGINREZ"], PDO::PARAM_STR);
				$stmt->bindValue("name", $_SESSION["NAME"], PDO::PARAM_STR);
				$stmt->bindValue("yagou", $_SESSION["YAGOU"], PDO::PARAM_STR);
				$stmt->bindValue("yubin", $_SESSION["zip11"], PDO::PARAM_STR);
				$stmt->bindValue("address1", $_SESSION["addr11"], PDO::PARAM_STR);
				$stmt->bindValue("address2", $_SESSION["ADD2"], PDO::PARAM_STR);
				$stmt->bindValue("address3", $_SESSION["ADD3"], PDO::PARAM_STR);
				$stmt->bindValue("uid", $_SESSION["user_id"], PDO::PARAM_INT);
				$stmt->bindValue("invoice_no", $_SESSION["invoice"], PDO::PARAM_INT);
				$status=$stmt->execute();
				$count = 1;
			}
			log_writer2(basename(__FILE__)." [\$status]",$status,"lv3");
			log_writer2(basename(__FILE__)." [\$count]",$count,"lv3");
			if($status!==false && $count<>0){
				$pdo_h->commit();
				$msg = "登録が完了しました。";
				$alert_status = "alert-success";
				file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",".basename(__FILE__).",UPDATE,succsess,".$sqlstr."\n",FILE_APPEND);
			}else{
				$pdo_h->rollBack();
				$msg = "失敗。";
				$alert_status = "alert-danger";
				file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",".basename(__FILE__).",UPDATE,failed,".$sqlstr."\n",FILE_APPEND);
			}
			$reseve_status=true;
		}catch(Exception $e){
			$pdo_h->rollBack();
			$msg = "システムエラーによる更新失敗。管理者へ通知しました。";
			$alert_status = "alert-danger";
			log_writer2(basename(__FILE__)." [Exception \$e] =>",$e,"lv0");
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
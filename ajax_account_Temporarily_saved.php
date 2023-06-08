<?php
//log_writer2("ajax_UriageDate_update_sql.php",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";                          //ユーザー向け処理結果メッセージ
$alert_status = "alert-warning";    //bootstrap alert class
$reseve_status=false;               //処理結果セット済みフラグ。
$timeout=false;                     //セッション切れ。ログイン画面に飛ばすフラグ
$myname = basename(__FILE__);           //ログファイルに出力する自身のファイル名
//log_writer2($myname,$_POST,"lv3");
$rtn = csrf_checker(["account_create.php"],["P","C","S"]);
if($rtn !== true){
	$msg=$rtn;
	$alert_status = "alert-warning";
	$reseve_status = true;
}else if($_POST["mode"]==="update"){
	$rtn=check_session_userid_for_ajax($pdo_h);
	if($rtn===false){
		$reseve_status = true;
		$msg="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度xxxxxxして下さい。";
		$_SESSION["EMSG"]="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度xxxxxxして下さい。";
		$timeout=true;
	}else{

		try{
			$kensu=0;
			if($_POST["MAIL"]!==$_POST["moto_mail"]){
				$sqlstr="select count(*) as kensu from Users where mail=?";
				$stmt = $pdo_h->prepare($sqlstr);
				$stmt->bindValue(1, $_POST["MAIL"], PDO::PARAM_STR);
				$stmt->execute();
				$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
				$kensu = $row[0]["kensu"];
			}
			
			if($kensu>0){
				$msg="変更されたメールアドレスは他のアカウントとして登録済みです。";
				$alert_status = "alert-warning";
				$reseve_status=true;
			}else{
				$_SESSION["MAIL"] = $_POST["MAIL"];
				$_SESSION["QUESTION"] = $_POST["QUESTION"];
				$_SESSION["ANSWER"] = $_POST["ANSWER"];
				$_SESSION["LOGINREZ"] = empty($_POST["LOGINREZ"])?$_POST["LOGINREZ"]:"";
				$_SESSION["NAME"] = $_POST["NAME"];
				$_SESSION["YAGOU"] = $_POST["YAGOU"];
				$_SESSION["zip11"] = $_POST["zip11"];
				$_SESSION["addr11"] = $_POST["addr11"];
				$_SESSION["ADD2"] = $_POST["ADD2"];
				$_SESSION["ADD3"] = $_POST["ADD3"];
				$_SESSION["invoice"] = $_POST["invoice"];
				$_SESSION["inquiry_tel"] = $_POST["inquiry_tel"];
				$_SESSION["inquiry_mail"] = $_POST["inquiry_mail"];
				$_SESSION["chk_pass"] =  !empty($_POST["chk_pass"])?$_POST["chk_pass"]:"";
				$_SESSION["PASS"] = passEX($_POST["PASS"],$_POST["MAIL"],$key);
				//$_SESSION["SHOUKAI"] = (!empty($_POST["shoukai"])?rot13decrypt2($_POST["shoukai"])-10000:"");
			
				$msg = "更新成功。";
				$alert_status = "alert-success";
				$reseve_status=true;
			}
	
		}catch(Exception $e){
			$pdo_h->rollBack();
			$msg = "システムエラー。管理者へ通知しました。";
			$alert_status = "alert-danger";
			log_writer2($myname." [Exception \$e] =>",$e,"lv0");
			$reseve_status=true;
		}
	}
}else if($_POST["mode"]==="insert"){
	$_SESSION["MAIL"] = $_POST["MAIL"];
	$_SESSION["QUESTION"] = $_POST["QUESTION"];
	$_SESSION["ANSWER"] = $_POST["ANSWER"];
	$_SESSION["PASS"] = passEX($_POST["PASS"],$_POST["MAIL"],$key);
	$_SESSION["SHOUKAI"] = sort_hash($_POST["shoukai"],"dec"); 
	$msg = "更新成功。";
	$alert_status = "alert-success";
	$reseve_status=true;
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
		log_writer2($GLOBALS["myname"],"shutdown","lv3");
		log_writer2($GLOBALS["myname"],$lastError,"lv1");
		  
		$emsg = "uid::".$_SESSION['user_id']." ERROR_MESSAGE::予期せぬエラー".$lastError['message'];
		if(EXEC_MODE!=="Local"){
			send_mail(SYSTEM_NOTICE_MAIL,"【WEBREZ-WARNING】".basename(__FILE__)."でシステム停止",$emsg);
		}
		log_writer2($GLOBALS["myname"]." [Exception \$lastError] =>",$lastError,"lv0");
	
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
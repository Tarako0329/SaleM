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
				$_SESSION["P"]=[];
				$_SESSION["P"]["mail"] = $_POST["MAIL"];
				$_SESSION["P"]["question"] = $_POST["QUESTION"];
				$_SESSION["P"]["answer"] = $_POST["ANSWER"];
				$_SESSION["P"]["zeihasu"] = $_POST["ZEIHASU"];
				//$_SESSION["P"]["loginrez"] = empty($_POST["LOGINREZ"])?$_POST["LOGINREZ"]:"";
				$_SESSION["P"]["loginrez"] = !empty($_POST["LOGINREZ"])?$_POST["LOGINREZ"]:"";
				$_SESSION["P"]["name"] = $_POST["NAME"];
				$_SESSION["P"]["yagou"] = $_POST["YAGOU"];
				$_SESSION["P"]["yubin"] = $_POST["zip11"];
				$_SESSION["P"]["address1"] = $_POST["addr11"];
				$_SESSION["P"]["address2"] = $_POST["ADD2"];
				$_SESSION["P"]["address3"] = $_POST["ADD3"];
				$_SESSION["P"]["invoice_no"] = $_POST["invoice"];
				$_SESSION["P"]["inquiry_tel"] = $_POST["inquiry_tel"];
				$_SESSION["P"]["inquiry_mail"] = $_POST["inquiry_mail"];
				$_SESSION["P"]["Accounting_soft"] = $_POST["Accounting_soft"];
				$_SESSION["P"]["password"] = passEX($_POST["PASS"],$_POST["MAIL"],$key);
				$_SESSION["P"]["uid"] = $_SESSION['user_id'];

				$_SESSION["P"]["chk_pass"] =  !empty($_POST["chk_pass"])?$_POST["chk_pass"]:"";
			
				$msg = "更新成功。";
				$alert_status = "alert-success";
				$reseve_status=true;
			}
	
		}catch(Exception $e){
			$msg = "システムエラー。管理者へ通知しました。";
			$alert_status = "alert-danger";
			log_writer2($myname." [Exception \$e] =>",$e,"lv0");
			$reseve_status=true;
		}
	}
}else if($_POST["mode"]==="insert"){
	$_SESSION["P"]=[];
	$_SESSION["P"]["mail"] = $_POST["MAIL"];
	$_SESSION["P"]["password"] = passEX($_POST["PASS"],$_POST["MAIL"],$key);
	$_SESSION["P"]["question"] = $_POST["QUESTION"];
	$_SESSION["P"]["answer"] = $_POST["ANSWER"];
	//試用期間は２ヶ月に変更。
	$_SESSION["P"]["yuukoukigen"] = date('Y-m-d', strtotime(date("Y-m-d") . "+2 month"));	
	$_SESSION["P"]["introducer_id"] = sort_hash($_POST["shoukai"],"dec"); 
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
<?php
//ユーザ登録、登録情報の修正画面
require "php_header.php";
$shoukai="";
$url="";
$status="warning";

if(csrf_checker(["pre_account.php","shoukai.php"],["C","G","S"])!==true){
	$url="セッションが正しくありませんでした";
	$status = "danger";
	$reseve_status = true;
}else{
	if(!empty($_GET["shoukai"])){
		$shoukai=$_GET["shoukai"];
	}

	try{
		//登録用メール送信
		$to = $_GET["MAIL"];
		$subject = "WEBREZ登録のご案内";
		$mail2=rot13encrypt2($to);
		$url=ROOT_URL."account_create.php?mode=0&acc=".$mail2."&shoukai=".$shoukai;
		$body = <<< "EOM"
			WEBREZ+（ウェブレジプラス）にご興味をもっていただきありがとうございます。
			こちらのURLから登録をお願いいたします。

			$url
			EOM;

		//qdmailでメール送付
		send_mail($to,$subject,$body);

		$status="success";
	}catch(Exception $e){
		log_writer2("Exception \$e",$e,"lv0");
		$status="danger";
	}
}
$stmt=null;
$pdo_h=null;

$return_sts = array(
	"url" => $url
	,"status" => $status
	,"csrf_create" => $token
);

header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);

exit();

?>
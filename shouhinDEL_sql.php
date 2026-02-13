<?php
require "php_header.php";
log_writer2("shouhinDEL_sql.php > \$_GET",$_GET,"lv3");

$rtn = csrf_checker(["shouhinMSList.php"],["G","C","S"]);
if($rtn !== true){
	redirect_to_login($rtn);
}

check_session_userid($pdo_h);
$csrf_token = csrf_create();
//$flg=true;
$sqllog="";
//実績有無の確認
$sqlstr="select count(*) as cnt from UriageData where ShouhinCD=? and uid=?";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, $_GET["cd"], PDO::PARAM_INT);
$stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
$uri = $row[0]["cnt"];
/*if($uri!=0){
	$MSG= "売上実績があるため、".secho($_GET["nm"])." を削除できません。";
	$alert = "alert-warning";
	$flg=false;
}*/
	
try{
	$pdo_h->beginTransaction();
	$sqllog .= rtn_sqllog("START TRANSACTION",[]);
	if($uri==0){
		//shouhinMSから削除
		$sqlstr="delete from ShouhinMS where shouhinCD=? and uid=?";
		$MSG= secho($_GET["nm"])." が削除されました。";
	}else{
		//ShouhinMSのhyoujiNOを999にupdate
		if($_GET["sortNO"]!=="999"){
			$sqlstr="update ShouhinMS set hyoujiNO=999 where shouhinCD=? and uid=?";
			$MSG= secho($_GET["nm"])." は売上実績があります。削除の代わりに表示位置を最後尾に変更します。";
		}else{
			$sqlstr="update ShouhinMS set hyoujiNO=0 where shouhinCD=? and uid=?";
			$MSG= secho($_GET["nm"])." の表示位置を元に戻します。";
		}
	}
	$stmt = $pdo_h->prepare($sqlstr);
	$stmt->bindValue(1, $_GET["cd"], PDO::PARAM_INT);
	$stmt->bindValue(2, $_SESSION['user_id'], PDO::PARAM_INT);
	
	$sqllog .= rtn_sqllog($sqlstr,[$_GET["cd"],$_SESSION['user_id']]);
	$status=$stmt->execute();
	$sqllog .= rtn_sqllog("-- execute():正常終了",[]);
	
	$pdo_h->commit();
	$sqllog .= rtn_sqllog("commit",[]);
	sqllogger($sqllog,0);
	$alert = "alert-success";
}catch(Exception $e){
	$pdo_h->rollBack();
	$sqllog .= rtn_sqllog("rollBack",[]);
	sqllogger($sqllog,$e);
	$MSG = "登録が失敗しました。";
	$alert = "alert-danger";
}

$stmt  = null;
$pdo_h = null;
$return_sts = array(
	"MSG" => $MSG
	,"alert" => $alert
	,"csrf" => $csrf_token
);
//header("HTTP/1.1 301 Moved Permanently");
//header("Location: shouhinMSList.php?csrf_token=".$csrf_token);
header('Content-type: application/json');
echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);


exit();

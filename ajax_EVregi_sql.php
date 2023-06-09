<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。
*/
/*
sleep(5);
$msg = array(
	"MSG" => "アクセス元が不正です。=> ".$_SERVER['HTTP_REFERER']
	,"status" => "alert-danger"
);
*/

//cookie:postのチェックのみ

require "php_header.php";
register_shutdown_function('shutdown');


//タイムアウトの設定(15s)タイムアウト時もcommit前のSQLは無効
//メモ：pdoはプログラムが予期せず終了した場合、自動的にロールバック処理を行うようになっている
if(EXEC_MODE!=="Local")ini_set("max_execution_time",15);
//ini_set("max_execution_time",1); 


$time = date("Y/m/d H:i:s");
$rtn=check_session_userid($pdo_h);
$logfilename="sid_".$_SESSION['user_id'].".log";

if(EXEC_MODE=="Test")sleep(2);
if(EXEC_MODE=="Local")sleep(0);

$rtn = csrf_checker(["EVregi.php"],["P","C"]);
if($rtn !== true){
	$msg = array(
		"MSG" => $rtn
		,"status" => "alert-danger"
	);
	header('Content-type: application/json');
	echo json_encode($msg, JSON_UNESCAPED_UNICODE);
	exit();
}


$MODE=(!empty($_POST["mode"])?$_POST["mode"]:"");
$token = csrf_create();

$E_Flg=0;
$emsg="";
$ins_cnt=0;

$msg = array(
	"MSG" => "更新処理が実行されませんでした。"
	,"status" => "alert-danger"
	,"csrf_create" => $token
);


//入力画面の前回値を記録
//if($_POST["EV"]<>""){
if(filter_input(INPUT_POST,"EV")<>""){
	//イベント名
	$_SESSION["EV"] = $_POST["EV"];
	$stmt = $pdo_h->prepare ( 'call PageDefVal_update(?,?,?,?,?)' );
	$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
	$stmt->bindValue(2, MACHIN_ID, PDO::PARAM_STR);
	$stmt->bindValue(3, "EVregi.php", PDO::PARAM_STR);
	$stmt->bindValue(4, "EV", PDO::PARAM_STR);
	$stmt->bindValue(5, $_POST["EV"], PDO::PARAM_STR);
	$stmt->execute();
}

//売上登録
//$logfilename="sid_".$_SESSION['user_id'].".log";
$array = $_POST["ORDERS"];
$ZeiKbnSummary = $_POST["ZeiKbnSummary"];
$sqlstr = "";
//売上番号の取得
$sqlstr = "select max(UriageNO) as UriageNO from UriageData where uid=?";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();

$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
if(is_null($row[0]["UriageNO"])){
	$UriageNO = 1;  //初回売上時は売上NO[1]をセット
}else{
	$UriageNO = $row[0]["UriageNO"]+1;
}
//echo (string)$UriageNO;
$params=[];
$params["uid"] = $_SESSION['user_id'];
$params["UriageNO"] = $UriageNO;
$params["UriDate"] = filter_input(INPUT_POST,'KEIJOUBI');
$params["insDatetime"] = date("Y/m/d H:i:s");
$params["Event"] = filter_input(INPUT_POST,'EV');
$params["TokuisakiNM"] = filter_input(INPUT_POST,'KOKYAKU');

try{
	$pdo_h->beginTransaction();
	sqllogger("START TRANSACTION",[],basename(__FILE__),"ok");
	$sqlstr = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zeiKBN,genka_tanka)";
	$sqlstr = $sqlstr." values(:uid,:UriageNO,:UriDate,:insDatetime,:Event,:TokuisakiNM,:ShouhinCD,:ShouhinNM,:su,:Utisu,:tanka,:UriageKin,:zeiKBN,:genka_tanka)";

	foreach($array as $row){//本体額明細の登録
		if($row["SU"]==0){continue;}//売上数０はスキップ
		$stmt = $pdo_h->prepare($sqlstr);

		$params["ShouhinCD"] = $row["CD"];
		$params["ShouhinNM"] = $row["NM"];
		$params["su"] = $row["SU"];
		$params["Utisu"] = $row["UTISU"];
		$params["tanka"] = $row["TANKA"];
		$params["UriageKin"] = ($row["SU"] * $row["TANKA"]);
		$params["zeiKBN"] = $row["ZEIKBN"];
		$params["genka_tanka"] = $row["GENKA_TANKA"];

		$stmt->bindValue("uid",  $params["uid"], PDO::PARAM_INT);
		$stmt->bindValue("UriageNO",  $params["UriageNO"], PDO::PARAM_INT);
		$stmt->bindValue("UriDate",  $params["UriDate"], PDO::PARAM_STR);
		$stmt->bindValue("insDatetime",  $params["insDatetime"], PDO::PARAM_STR);
		$stmt->bindValue("Event",  $params["Event"], PDO::PARAM_INT);
		$stmt->bindValue("TokuisakiNM",  $params["TokuisakiNM"], PDO::PARAM_STR);
		$stmt->bindValue("ShouhinCD",  $params["ShouhinCD"], PDO::PARAM_STR);      //商品CD
		$stmt->bindValue("ShouhinNM",  $params["ShouhinNM"], PDO::PARAM_STR);      //商品名
		$stmt->bindValue("su",  $params["su"], PDO::PARAM_INT);      //数量
		$stmt->bindValue("Utisu", $params["Utisu"], PDO::PARAM_INT);      //内数
		$stmt->bindValue("tanka", $params["tanka"], PDO::PARAM_INT);     //単価
		$stmt->bindValue("UriageKin", $params["UriageKin"], PDO::PARAM_INT);     //数量×単価
		$stmt->bindValue("zeiKBN", $params["zeiKBN"], PDO::PARAM_INT);     //税区分
		$stmt->bindValue("genka_tanka", $params["genka_tanka"], PDO::PARAM_INT);     //原価単価

		$stmt->execute();
		sqllogger($sqlstr,$params,basename(__FILE__),"ok");
		$ins_cnt++;
		/*executeの戻り値チェックは不要。失敗時はExeptionが出るのでcatchで対応
			$flg=$stmt->execute();
			if($flg){
				$ins_cnt++;
				$emsg="売上のINSERTは正常終了\n";
				sqllogger($sqlstr,$params,basename(__FILE__),"ok");
			}else{
				$emsg="売上のINSERTでエラー";
				sqllogger($sqlstr,$params,basename(__FILE__),"ng");
				$E_Flg=1;
				break;
			}
		*/
	}

	//インボイス対応（消費税レコードと調整レコードの追加）
	$sqlstr_z = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,zei,zeiKBN)";
	$sqlstr_z .= " values(:uid,:UriageNO,:UriDate,:insDatetime,:Event,:TokuisakiNM,:ShouhinCD,:ShouhinNM,:zei,:zeiKBN)";
	$sqlstr_c = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,UriageKin,zeiKBN)";
	$sqlstr_c .= " values(:uid,:UriageNO,:UriDate,:insDatetime,:Event,:TokuisakiNM,:ShouhinCD,:ShouhinNM,:UriageKin,:zeiKBN)";

	foreach($ZeiKbnSummary as $row){
		$stmt = $pdo_h->prepare($sqlstr_z);

		$params["ShouhinCD"] = "Z".substr("000000".$row["ZEIKBN"],-6);
		$params["ShouhinNM"] = ($row["ZEIRITU"]<>0?$row["ZEIKBNMEI"]." 消費税額":$row["ZEIKBNMEI"]);
		$params["zei"] = $row["SHOUHIZEI"];
		$params["zeiKBN"] = $row["ZEIKBN"];
		
		$stmt->bindValue("uid",  $params["uid"], PDO::PARAM_INT);
		$stmt->bindValue("UriageNO",  $params["UriageNO"], PDO::PARAM_INT);
		$stmt->bindValue("UriDate",  $params["UriDate"], PDO::PARAM_STR);
		$stmt->bindValue("insDatetime",  $params["insDatetime"], PDO::PARAM_STR);
		$stmt->bindValue("Event",  $params["Event"], PDO::PARAM_INT);
		$stmt->bindValue("TokuisakiNM",  $params["TokuisakiNM"], PDO::PARAM_STR);
		$stmt->bindValue("ShouhinCD",  $params["ShouhinCD"], PDO::PARAM_STR);     //商品CD
		$stmt->bindValue("ShouhinNM",  $params["ShouhinNM"], PDO::PARAM_STR);     //商品名
		$stmt->bindValue("zei", $params["zei"], PDO::PARAM_INT);       						//消費税
		$stmt->bindValue("zeiKBN", $params["zeiKBN"], PDO::PARAM_INT);            //税区分
		/*
			$stmt->bindValue("uid",  $_SESSION['user_id'], PDO::PARAM_INT);
			$stmt->bindValue("UriageNO",  $UriageNO, PDO::PARAM_INT);
			$stmt->bindValue("UriDate",  $KEIJOUBI, PDO::PARAM_STR);
			$stmt->bindValue("insDatetime",  $time, PDO::PARAM_STR);
			$stmt->bindValue("Event",  $EV, PDO::PARAM_INT);
			$stmt->bindValue("TokuisakiNM",  $KOKYAKU, PDO::PARAM_STR);
			$stmt->bindValue("ShouhinCD",  "Z".substr("000000".$row["ZEIKBN"],-6), PDO::PARAM_STR);	//商品CD["Z" + 0埋 + 税区分] Z=税
			$stmt->bindValue("ShouhinNM",  ($row["ZEIRITU"]<>0?$row["ZEIKBNMEI"]." 消費税額":$row["ZEIKBNMEI"]), PDO::PARAM_STR);                      //商品名
			$stmt->bindValue("zei", $row["SHOUHIZEI"], PDO::PARAM_INT);       											//消費税
			$stmt->bindValue("zeiKBN", $row["ZEIKBN"], PDO::PARAM_INT);                   					//税区分
		*/
		$stmt->execute();
		$ins_cnt++;
		sqllogger($sqlstr_z,$params,basename(__FILE__),"ok");

		/*executeの戻り値チェックは不要。失敗時はExeptionが出るのでcatchで対応
			$flg=$stmt->execute();

			if($flg){
				$ins_cnt++;
				$emsg="売上消費税のINSERTは正常終了\n";
				sqllogger($sqlstr_z,$params,basename(__FILE__),"ok");
			}else{
				$emsg="売上消費税のINSERTでエラー";
				sqllogger($sqlstr_z,$params,basename(__FILE__),"ng");
				$E_Flg=1;
				break;
			}
		*/
		if($row["CHOUSEIGAKU"]!=0){
			$stmt = $pdo_h->prepare($sqlstr_c);

			$params["ShouhinCD"] = "C".substr("000000".$row["ZEIKBN"],-6);
			$params["ShouhinNM"] = $row["ZEIKBNMEI"]."本体調整額";
			$params["UriageKin"] = $row["CHOUSEIGAKU"];
			$params["zeiKBN"] = $row["ZEIKBN"];
			
			$stmt->bindValue("uid",  $params["uid"], PDO::PARAM_INT);
			$stmt->bindValue("UriageNO",  $params["UriageNO"], PDO::PARAM_INT);
			$stmt->bindValue("UriDate",  $params["UriDate"], PDO::PARAM_STR);
			$stmt->bindValue("insDatetime",  $params["insDatetime"], PDO::PARAM_STR);
			$stmt->bindValue("Event",  $params["Event"], PDO::PARAM_INT);
			$stmt->bindValue("TokuisakiNM",  $params["TokuisakiNM"], PDO::PARAM_STR);
			$stmt->bindValue("ShouhinCD",  $params["ShouhinCD"], PDO::PARAM_STR);     //商品CD
			$stmt->bindValue("ShouhinNM",  $params["ShouhinNM"], PDO::PARAM_STR);     //商品名
			$stmt->bindValue("UriageKin", $params["UriageKin"], PDO::PARAM_INT);      //売上本体調整額
			$stmt->bindValue("zeiKBN", $params["zeiKBN"], PDO::PARAM_INT);            //税区分
			/*
				$stmt->bindValue("uid",  $_SESSION['user_id'], PDO::PARAM_INT);
				$stmt->bindValue("UriageNO",  $UriageNO, PDO::PARAM_INT);
				$stmt->bindValue("UriDate",  $KEIJOUBI, PDO::PARAM_STR);
				$stmt->bindValue("insDatetime",  $time, PDO::PARAM_STR);
				$stmt->bindValue("Event",  $EV, PDO::PARAM_INT);
				$stmt->bindValue("TokuisakiNM",  $KOKYAKU, PDO::PARAM_STR);
				$stmt->bindValue("ShouhinCD",  "C".substr("000000".$row["ZEIKBN"],-6), PDO::PARAM_STR);	//商品CD["C" + 0埋 + 税区分] C=調整
				$stmt->bindValue("ShouhinNM",  $row["ZEIKBNMEI"]."本体調整額", PDO::PARAM_STR);          //商品名
				$stmt->bindValue("UriageKin",  $row["CHOUSEIGAKU"], PDO::PARAM_INT);                    //売上本体調整額
				$stmt->bindValue("zeiKBN", $row["ZEIKBN"], PDO::PARAM_INT);                   					//税区分
			*/
			$stmt->execute();
			$ins_cnt++;
			sqllogger($sqlstr_c,$params,basename(__FILE__),"ok");
	
			/*executeの戻り値チェックは不要。失敗時はExeptionが出るのでcatchで対応
				$flg=$stmt->execute();
				if($flg){
					$ins_cnt++;
					$emsg="調整額のINSERTは正常終了\n";
					file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,success,".$sqllog."\n",FILE_APPEND);
				}else{
					$emsg="調整額のINSERTでエラー";
					file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,failed,".$sqllog."\n",FILE_APPEND);
					$E_Flg=1;
					break;
				}
			*/
		}
	}
	//位置情報、天気情報の付与（uid,売上No,緯度、経度、住所、天気、気温、体感温度、天気アイコンping,無効FLG,insdate,update）
	if(empty($_POST["nonadd"]) && $ins_cnt>0){
		$emsg=$emsg."/位置情報、天気情報　処理開始\n";
		
		$sqlstr = "INSERT INTO `UriageData_GioWeather`(`uid`, `UriNo`, `lat`, `lon`, `weather`, `description`, `temp`, `feels_like`, `icon`) VALUES(?,?,?,?,?,?,?,?,?)";
		$sqllog = "INSERT INTO `UriageData_GioWeather`(`uid`, `UriNo`, `lat`, `lon`, `weather`, `description`, `temp`, `feels_like`, `icon`) ";

		$sqllog = $sqllog."VALUES('".$_SESSION['user_id']."','".$UriageNO."','".$_POST['lat']."','".$_POST['lon']."','".$_POST['weather']."','".$_POST['description']."','".$_POST['temp']."','".$_POST['feels_like']."','".$_POST['icon']."')";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
		$stmt->bindValue(3,  $_POST['lat'], PDO::PARAM_INT);
		$stmt->bindValue(4,  $_POST['lon'], PDO::PARAM_INT);
		$stmt->bindValue(5,  $_POST['weather'], PDO::PARAM_STR);
		$stmt->bindValue(6,  $_POST['description'], PDO::PARAM_INT);
		$stmt->bindValue(7,  $_POST['temp'], PDO::PARAM_INT);
		$stmt->bindValue(8,  $_POST['feels_like'], PDO::PARAM_INT);
		$stmt->bindValue(9,  $_POST['icon'], PDO::PARAM_STR);

		/*executeの戻り値チェックは不要。失敗時はExeptionが出るのでcatchで対応
			$flg=$stmt->execute();

			if($flg){
				$ins_cnt++;
				$emsg="位置・天気のINSERTは正常終了\n";
				file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,success,".$sqllog."\n",FILE_APPEND);
			}else{
				$emsg="位置・天気のINSERTでエラー";
				file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,failed,".$sqllog."\n",FILE_APPEND);
				$E_Flg=1;
			}
		*/
		$stmt->execute();
		$ins_cnt++;
		sqllogger($sqlstr,$params,basename(__FILE__),"ok");
	}else{
		log_writer2("ajax_EVregi_sql.php","Gio insert skip","lv3");
	}
	
	//
	if($E_Flg==0){
		$pdo_h->commit();
		sqllogger("commit",[],basename(__FILE__),"ok");
		file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",EVregi_sql.php,COMMIT,success,売上No".$UriageNO."\n",FILE_APPEND);
		
		$msg = array(
			"MSG" => "売上が登録されました。（売上№：".$UriageNO."）"
			,"status" => "alert-success"
			,"csrf_create" => $token
			,"RyoushuURL" => ROOT_URL."ryoushuu_pdf.php?u=".rot13encrypt2($UriageNO)."&i=".rot13encrypt2($_SESSION["user_id"])
		);
		header('Content-type: application/json');
		echo json_encode($msg, JSON_UNESCAPED_UNICODE);

		$stmt = null;
		$pdo_h = null;
		exit();//success!
	}else{
		//1件でも失敗したらロールバック
		$pdo_h->rollBack();
		sqllogger("rollback",[],basename(__FILE__),"ok");
		file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",EVregi_sql.php,ROLLBACK,success,売上No".$UriageNO."\n",FILE_APPEND);
		
		$emsg=$emsg."/insert処理が失敗し、rollBackが発生しました。";
		$stmt = null;
		$pdo_h = null;
		$E_Flg=1;
	}
	
}catch (Exception $e) {
	$pdo_h->rollBack();
	sqllogger("rollback",[],basename(__FILE__),"ok");
	$emsg = $emsg."/レジ登録でERRORをCATHCしました。：".$e->getMessage();
	file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",EVregi_sql.php,ROLLBACK,success,売上No".$UriageNO."\n",FILE_APPEND);
	$E_Flg=1;
	$stmt = null;
	$pdo_h = null;
}catch(Throwable $t){
	$pdo_h->rollBack();
	sqllogger("rollback",[],basename(__FILE__),"ok");
	$emsg = $emsg."/レジ登録でFATAL ERRORをCATHCしました。：".$t->getMessage();
	file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",EVregi_sql.php,ROLLBACK,success,売上No".$UriageNO."\n",FILE_APPEND);
	$E_Flg=1;
	$stmt = null;
	$pdo_h = null;        
}

if($E_Flg==1){
	$emsg = $emsg."/UriNO::".$UriageNO."　uid::".$_SESSION['user_id'];
	if(EXEC_MODE!=="Local"){
		send_mail(SYSTEM_NOTICE_MAIL,"【WEBREZ-WARNING】EVregi_sql.phpでシステム停止",$emsg);
	}else{
		log_writer2("ajax.EVreg_sql.php",$emsg,"lv3");
	}

	$msg = array(
		"MSG" => "登録が失敗しました。再度実行してもエラーとなる場合は、ご迷惑をおかけしますが復旧までお待ちください。エラーは管理者へ自動通知されました。"
		,"status" => "alert-danger"
		,"csrf_create" => $token
	);
	header('Content-type: application/json');
	echo json_encode($msg, JSON_UNESCAPED_UNICODE);
	$stmt = null;
	$pdo_h = null;
	
	exit();
}

$stmt = null;
$pdo_h = null;

exit();


function shutdown(){
  // シャットダウン関数
  // スクリプトの処理が完了する前に
  // ここで何らかの操作をすることができます
	$lastError = error_get_last();
	
	if($lastError!==null){
		log_writer("ajax_EVregi_sql.php","shutdown");
		log_writer("ajax_EVregi_sql.php",$lastError);
		if(empty($GLOBALS["msg"])===true){
			$emsg = $GLOBALS["emsg"]."/UriNO::".$GLOBALS["UriageNO"]."　uid::".$_SESSION['user_id']." ERROR_MESSAGE::予期せぬエラー".$lastError['message'];
			send_mail(SYSTEM_NOTICE_MAIL,"【WEBREZ-WARNING】EVregi_sql.phpでシステム停止",$emsg);
		
			$token = csrf_create();
			$msg = array(
				"MSG" => "登録が失敗しました。再度実行してもエラーとなる場合は、ご迷惑をおかけしますが復旧までお待ちください。<br>".$lastError['message']
				,"status" => "alert-danger"
				,"csrf_create" => $token
			);
			header('Content-type: application/json');
			echo json_encode($msg, JSON_UNESCAPED_UNICODE);
		}
	}
}
?>



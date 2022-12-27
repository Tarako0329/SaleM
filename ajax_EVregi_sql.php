<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

遷移先のチェック
csrf_chk()                              ：COOKIE・SESSION・POSTのトークンチェック。
csrf_chk_nonsession()                   ：COOKIE・POSTのトークンチェック。
csrf_chk_nonsession_get($_GET[token])   ：COOKIE・GETのトークンチェック。
csrf_chk_redirect($_GET[token])         ：SESSSION・GETのトークンチェック
*/
/*
sleep(5);
$msg[0] = array(
	"EMSG" => "アクセス元が不正です。=> ".$_SERVER['HTTP_REFERER']
	,"status" => "alert-danger"
);
*/

//cookie:postのチェックのみ
require "php_header.php";
if(EXEC_MODE!=="Local")ini_set("max_execution_time",15);//タイムアウトの設定(15s)

$time = date("Y/m/d H:i:s");
$rtn=check_session_userid($pdo_h);
$logfilename="sid_".$_SESSION['user_id'].".log";

if(EXEC_MODE=="Test")sleep(5);
if(EXEC_MODE=="Local")sleep(0);

//リファイラーチェック
if(ROOT_URL."EVregi.php"!==substr($_SERVER['HTTP_REFERER'],0,strlen(ROOT_URL."EvRegi.php"))){
	$msg[0] = array(
		"EMSG" => "アクセス元が不正です。=> ".$_SERVER['HTTP_REFERER']
		,"status" => "alert-danger"
	);
	header('Content-type: application/json');
	echo json_encode($msg, JSON_UNESCAPED_UNICODE);
	exit();
}

$MODE=(!empty($_POST["mode"])?$_POST["mode"]:"");

if(!empty($_POST)){
	if(csrf_chk_nonsession()==false){//cookie:post
		$msg[0] = array(
			"EMSG" => "セッションが正しくありません。"
			,"status" => "alert-danger"
		);
		header('Content-type: application/json');
		echo json_encode($msg, JSON_UNESCAPED_UNICODE);
		exit();
		}
}else{
	$msg[0] = array(
		"EMSG" => "セッションが正しくありません。"
		,"status" => "alert-danger"
	);
	header('Content-type: application/json');
	echo json_encode($msg, JSON_UNESCAPED_UNICODE);
	exit();
}

$token = csrf_create();
$E_Flg=0;
$emsg="";
$ins_cnt=0;

$msg[0] = array(
	"EMSG" => "更新処理が実行されませんでした。"
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

try{
	$pdo_h->beginTransaction();
	$sqlstr = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime,genka_tanka) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0,?)";
	
	$KEIJOUBI = filter_input(INPUT_POST,'KEIJOUBI');
	$EV = filter_input(INPUT_POST,'EV');
	$KOKYAKU = filter_input(INPUT_POST,'KOKYAKU');
	
	foreach($array as $row){
		if($row["SU"]==0){
			//売上数０はスキップ
			continue;
		}
		
		$sqllog = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime,genka_tanka) ";
		$sqllog = $sqllog."values(".$_SESSION['user_id'].",".$UriageNO.",'".$KEIJOUBI."','".$time."','".$EV."','".$KOKYAKU."','".$row["CD"]."','".$row["NM"]."','".$row["SU"]."','".$row["UTISU"]."','".$row["TANKA"]."','".($row["SU"] * $row["TANKA"])."','".($row["SU"] * $row["ZEI"])."','".$row["ZEIKBN"]."',0,'".$row["GENKA_TANKA"]."')";
		
		
		$stmt = $pdo_h->prepare($sqlstr);
		
		$time = date("Y/m/d H:i:s");
		
		$stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
		$stmt->bindValue(3,  $KEIJOUBI, PDO::PARAM_STR);
		$stmt->bindValue(4,  $time, PDO::PARAM_STR);
		$stmt->bindValue(5,  $EV, PDO::PARAM_INT);
		$stmt->bindValue(6,  $KOKYAKU, PDO::PARAM_STR);
		$stmt->bindValue(7,  $row["CD"], PDO::PARAM_INT);                       //商品CD
		$stmt->bindValue(8,  $row["NM"], PDO::PARAM_STR);                       //商品名
		$stmt->bindValue(9,  $row["SU"], PDO::PARAM_INT);                       //数量
		$stmt->bindValue(10, $row["UTISU"], PDO::PARAM_INT);                    //内数
		$stmt->bindValue(11, $row["TANKA"], PDO::PARAM_INT);                    //単価
		$stmt->bindValue(12, ($row["SU"] * $row["TANKA"]), PDO::PARAM_INT);     //数量×単価
		$stmt->bindValue(13, ($row["SU"] * $row["ZEI"]), PDO::PARAM_INT);       //数量×単価税
		$stmt->bindValue(14, $row["ZEIKBN"], PDO::PARAM_INT);                   //税区分
		$stmt->bindValue(15, $row["GENKA_TANKA"], PDO::PARAM_INT);              //原価単価
		
		$flg=$stmt->execute();
		
		if($flg){
			$ins_cnt++;
			$emsg="売上のINSERTは正常終了\n";
			file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,success,".$sqllog."\n",FILE_APPEND);
		}else{
			$emsg="売上のINSERTでエラー";
			file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,failed,".$sqllog."\n",FILE_APPEND);
			$E_Flg=1;
			break;
		}
	}
	
	if($_POST["CHOUSEI_GAKU"]>0 && $E_Flg!=1){
		$sqlstr="SELECT ZeiMS.zeiKBN as ZEIKBN ,1+ZeiMS.zeiritu/100 as zei_per ,sum(UriageKin+Zei) as uriage,T.total ";
		$sqlstr=$sqlstr."FROM UriageData Umei inner join ZeiMS on Umei.zeiKBN = ZeiMS.zeiKBN ";
		$sqlstr=$sqlstr."inner join (select UriageNO,sum(UriageKin+Zei) as total from UriageData WHERE uid=? and UriageNO=? group by UriageNO) as T on Umei.UriageNO = T.UriageNO ";
		$sqlstr=$sqlstr."WHERE uid=? and Umei.UriageNO=? ";
		$sqlstr=$sqlstr."group by ZeiMS.zeiKBN,1+ZeiMS.zeiritu/100";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
		$stmt->bindValue(3,  $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(4,  $UriageNO, PDO::PARAM_INT);
		$flg=$stmt->execute();
		
		$result = $stmt->fetchAll();
		$goukei = 0;
		$i=0;
		foreach($result as $row){
			$chouseigaku = $_POST["CHOUSEI_GAKU"] - $row["total"];
			echo "売上合計:".$row["total"]."<br>";
			echo "調整売上:".$_POST["CHOUSEI_GAKU"]."<br>";
			
			echo $chouseigaku."<br>";
			$chousei_hon = bcdiv(bcmul($chouseigaku , bcdiv($row["uriage"] , $row["total"],5),5),$row["zei_per"],0);//調整額×税率割合÷消費税率
			$chousei_zei = bcsub(bcmul($chouseigaku , bcdiv($row["uriage"] , $row["total"],5),5) ,$chousei_hon,0);
			
			$goukei=$goukei+$chousei_hon+$chousei_zei;
			
			echo $chousei_hon."<br>";
			echo $chousei_zei."<br>";
			
			$sqlstr = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)";
			$sqllog = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime) ";
			$sqllog = $sqllog."values('".$_SESSION['user_id']."','".$UriageNO."','".$_POST["KEIJOUBI"]."','".$time."','".$_POST["EV"]."','".$_POST["KOKYAKU"]."','".(9999-$i)."','割引・割増:税率".(($row["zei_per"]-1)*100)."%分',0,0,0,'".$chousei_hon."','".$chousei_zei."','".$row["ZEIKBN"]."',0)";
			$stmt = $pdo_h->prepare($sqlstr);
			
			$time = date("Y/m/d H:i:s");
	
			$stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
			$stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
			$stmt->bindValue(3,  $_POST["KEIJOUBI"], PDO::PARAM_STR);
			$stmt->bindValue(4,  $time, PDO::PARAM_STR);
			$stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
			$stmt->bindValue(6,  $_POST["KOKYAKU"], PDO::PARAM_STR);
			$stmt->bindValue(7,  9999-$i, PDO::PARAM_INT);                                                          //商品CD
			$stmt->bindValue(8,  "割引・割増:税率".(($row["zei_per"]-1)*100)."%分", PDO::PARAM_STR);  //商品名
			$stmt->bindValue(9,  0, PDO::PARAM_INT);                                                                //数量
			$stmt->bindValue(10, 0, PDO::PARAM_INT);                                                                //内数
			$stmt->bindValue(11, 0, PDO::PARAM_INT);                                                                //単価
			$stmt->bindValue(12, $chousei_hon, PDO::PARAM_INT);                                                     //数量×単価
			$stmt->bindValue(13, $chousei_zei, PDO::PARAM_INT);                                                     //数量×単価税
			$stmt->bindValue(14, $row["ZEIKBN"], PDO::PARAM_INT);                                                   //税区分
			$flg=$stmt->execute();
			
			if($flg){
				$emsg=$emsg."/割引割増のINSERTは正常終了\n";
				file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,success,".$sqllog."\n",FILE_APPEND);
			}else{
				$E_Flg=1;
				$emsg=$emsg."/割引割増のINSERTでエラー";
				file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,failed,".$sqllog."\n",FILE_APPEND);
				break;
			}
			$i++;
		}
		if($goukei!=$chouseigaku){
			//端数あり
			$emsg=$emsg."/割引割増の端数処理開始\n";
			$sqlstr = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)";
			$sqllog = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime) ";
			$sqllog = $sqllog."values('".$_SESSION['user_id']."','".$UriageNO."','".$_POST["KEIJOUBI"]."','".$time."','".$_POST["EV"]."','".$_POST["KOKYAKU"]."','".(9999-$i)."','割引・割増:端数".(($row["zei_per"]-1)*100)."%分',0,0,0,'".($chouseigaku-$goukei)."',0,0,0)";
			$stmt = $pdo_h->prepare($sqlstr);
			
			$time = date("Y/m/d H:i:s");
	
			$stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
			$stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
			//$stmt->bindValue(3,  date("Y/m/d"), PDO::PARAM_STR);
			$stmt->bindValue(3,  $_POST["KEIJOUBI"], PDO::PARAM_STR);
			$stmt->bindValue(4,  $time, PDO::PARAM_STR);
			$stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
			$stmt->bindValue(6,  $_POST["KOKYAKU"], PDO::PARAM_STR);
			$stmt->bindValue(7,  9999-$i, PDO::PARAM_INT);                             //商品CD
			$stmt->bindValue(8,  "割引・割増:端数", PDO::PARAM_STR);  //商品名
			$stmt->bindValue(9,  0, PDO::PARAM_INT);                                //数量
			$stmt->bindValue(10, 0, PDO::PARAM_INT);                                //内数
			$stmt->bindValue(11, 0, PDO::PARAM_INT);                                //単価
			$stmt->bindValue(12, $chouseigaku-$goukei, PDO::PARAM_INT);             //数量×単価
			$stmt->bindValue(13, 0, PDO::PARAM_INT);                                //数量×単価税
			$stmt->bindValue(14, 0, PDO::PARAM_INT);                                //非課税//税区分
			$flg=$stmt->execute();
			 if($flg){
				 $emsg=$emsg."/割引割増の端数調整のINSERTは正常終了\n";
				 file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,success,".$sqllog."\n",FILE_APPEND);
			}else{
				$E_Flg=1;
				$emsg=$emsg."/割引割増の端数調整のINSERTでエラー";
				file_put_contents("sql_log/".$logfilename,$time.",EVregi_sql.php,INSERT,failed,".$sqllog."\n",FILE_APPEND);
			}
		   
		}
		//exit();
	}
	
	//位置情報、天気情報の付与（uid,売上No,緯度、経度、住所、天気、気温、体感温度、天気アイコンping,無効FLG,insdate,update）
	if(empty($_POST["nonadd"]) && $ins_cnt>0){
		$_SESSION["nonadd"]="";
		$tenki=get_weather("insert",$_POST['lat'],$_POST['lon']);
		//file_put_contents("sql_log/".$logfilename,$time.",gio/weather :".$_POST['address']."/".$tenki[0]."/".$tenki[1]."/".$tenki[2]."\n",FILE_APPEND);
		
		$sqlstr = "INSERT INTO `UriageData_GioWeather`(`uid`, `UriNo`, `lat`, `lon`, `weather`, `description`, `temp`, `feels_like`, `icon`) VALUES(?,?,?,?,?,?,?,?,?)";
		$sqllog = "INSERT INTO `UriageData_GioWeather`(`uid`, `UriNo`, `lat`, `lon`, `weather`, `description`, `temp`, `feels_like`, `icon`) ";
		$sqllog = $sqllog."VALUES('".$_SESSION['user_id']."','".$UriageNO."','".$_POST['lat']."','".$_POST['lon']."','".$tenki[0]."','".$tenki[1]."','".$tenki[2]."','".$tenki[3]."','".$tenki[4]."')";
		$stmt = $pdo_h->prepare($sqlstr);
		$stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
		$stmt->bindValue(3,  $_POST['lat'], PDO::PARAM_INT);
		$stmt->bindValue(4,  $_POST['lon'], PDO::PARAM_INT);
		$stmt->bindValue(5,  $tenki[0], PDO::PARAM_STR);
		$stmt->bindValue(6,  $tenki[1], PDO::PARAM_INT);
		$stmt->bindValue(7,  $tenki[2], PDO::PARAM_INT);
		$stmt->bindValue(8,  $tenki[3], PDO::PARAM_INT);
		$stmt->bindValue(9,  $tenki[4], PDO::PARAM_STR);
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
	}else{
		$_SESSION["nonadd"]="checked";
	}
	
	//
	if($E_Flg==0){
		$pdo_h->commit();
		file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",EVregi_sql.php,COMMIT,success,売上No".$UriageNO."\n",FILE_APPEND);
		
		$msg[0] = array(
			"EMSG" => "売上が登録されました。（売上№：".$UriageNO."）"
			,"status" => "alert-success"
			,"csrf_create" => $token
		);
		header('Content-type: application/json');
		echo json_encode($msg, JSON_UNESCAPED_UNICODE);

		$stmt = null;
		$pdo_h = null;
		exit();
	}else{
		//1件でも失敗したらロールバック
		$pdo_h->rollBack();
		file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",EVregi_sql.php,ROLLBACK,success,売上No".$UriageNO."\n",FILE_APPEND);
		
		$emsg=$emsg."/insert処理が失敗し、rollBackが発生しました。";
		$stmt = null;
		$pdo_h = null;
		$E_Flg=1;
	}
	
}catch (Exception $e) {
	$pdo_h->rollBack();
	$emsg = $emsg."/レジ登録でERRORをCATHCしました。：".$e->getMessage();
	file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",EVregi_sql.php,ROLLBACK,success,売上No".$UriageNO."\n",FILE_APPEND);
	$E_Flg=1;
	$stmt = null;
	$pdo_h = null;
	//exit();
}catch(Throwable $t){
	$pdo_h->rollBack();
	$emsg = $emsg."/レジ登録でFATAL ERRORをCATHCしました。：".$t->getMessage();
	file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",EVregi_sql.php,ROLLBACK,success,売上No".$UriageNO."\n",FILE_APPEND);
	$E_Flg=1;
	$stmt = null;
	$pdo_h = null;        
}

if($E_Flg==1){
	//$_SESSION["msg"]= "登録が失敗しました。再度実行してもエラーとなる場合は、ご迷惑をおかけしますが復旧までお待ちください。エラーは管理者へ自動通知されました。";
	$emsg = $emsg."/UriNO::".$UriageNO."　uid::".$_SESSION['user_id'];
	send_mail(SYSTEM_NOTICE_MAIL,"【WEBREZ-WARNING】EVregi_sql.phpでシステム停止",$emsg);

	$msg[0] = array(
		"EMSG" => "登録が失敗しました。再度実行してもエラーとなる場合は、ご迷惑をおかけしますが復旧までお待ちください。エラーは管理者へ自動通知されました。"
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

?>



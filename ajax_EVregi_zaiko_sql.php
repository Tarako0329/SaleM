<?php
//log_writer2("ajax_UriageDate_update_sql.php",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";                          //ユーザー向け処理結果メッセージ
$alert_status = "alert-warning";    //bootstrap alert class
$reseve_status=false;               //処理結果セット済みフラグ。
$timeout=false;                     //セッション切れ。ログイン画面に飛ばすフラグ

$MODE=(!empty($_POST["mode"])?$_POST["mode"]:"");

if(csrf_chk()===false){
    $msg="セッションが正しくありませんでした②";
    $alert_status = "alert-warning";
    $reseve_status = true;
}else if($MODE !== "shuppin_zaiko"){//在庫登録denai
    $msg="更新PG間違い。";
    $alert_status = "alert-danger";
    $reseve_status = true;
}else{
    $rtn=check_session_userid_for_ajax($pdo_h);
    if($rtn===false){
        $reseve_status = true;
        $msg="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度xxxxxxして下さい。";
        $_SESSION["EMSG"]="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度xxxxxxして下さい。";
        $timeout=true;
    }else{
        $logfilename="sid_".$_SESSION['user_id'].".log";
        $array = $_POST["ORDERS"];

        try{
            $pdo_h->beginTransaction();

            //同日同イベントの在庫情報があったらクリアする（delete&insert)
            $sqlstr = "select count(*) as cnt from Zaiko where uid=? and shuppindate=? and hokanbasho=?";
            $stmt = $pdo_h->prepare($sqlstr);
            
            $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(2,  $_POST["KEIJOUBI"], PDO::PARAM_STR);
            $stmt->bindValue(3,  $_POST["EV"], PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if($row[0]["cnt"]!==0){
                $sqlstr = "delete from Zaiko where uid=? and shuppindate=? and hokanbasho=?";
                $stmt = $pdo_h->prepare($sqlstr);
                $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->bindValue(2,  $_POST["KEIJOUBI"], PDO::PARAM_STR);
                $stmt->bindValue(3,  $_POST["EV"], PDO::PARAM_INT);
                $stmt->execute();
            }

            //在庫番号の取得
            $sqlstr = "select max(zaikoNO) as zaikoNO from Zaiko where uid=?";
            $stmt = $pdo_h->prepare($sqlstr);
            $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->execute();

            $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if(is_null($row[0]["zaikoNO"])){
                $zaikoNO = 1;   //初回登録時は在庫NO[1]をセット
            }else{
                $zaikoNO = $row[0]["zaikoNO"]+1;
            }
        
            $sqlstr = "insert into Zaiko(uid,sousa,shuppindate,zaikoNO,hokanbasho,shouhinCD,shouhinNM,su,genka_tanka) values(?,'entry',?,?,?,?,?,?,?)";
            $E_Flg=0;
            foreach($array as $row){
                $sqllog = "insert into Zaiko(uid,sousa,shuppindate,zaikoNO,hokanbasho,shouhinCD,shouhinNM,su,genka_tanka) ";
                $sqllog = $sqllog."values(".$_SESSION['user_id'].",'entry',".$_POST["KEIJOUBI"].",".$zaikoNO.",".$_POST["EV"].",".$row["CD"].",".$row["NM"].",".$row["SU"].",".$row["GENKA_TANKA"].")";
                if($row["SU"]===0){continue;}
                $stmt = $pdo_h->prepare($sqlstr);
            
                $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->bindValue(2,  $_POST["KEIJOUBI"], PDO::PARAM_STR);
                $stmt->bindValue(3,  $zaikoNO, PDO::PARAM_INT);
                $stmt->bindValue(4,  $_POST["EV"], PDO::PARAM_STR);
                $stmt->bindValue(5,  $row["CD"], PDO::PARAM_INT);
                $stmt->bindValue(6,  $row["NM"], PDO::PARAM_STR);
                $stmt->bindValue(7,  $row["SU"], PDO::PARAM_INT);                       //商品CD
                $stmt->bindValue(8,  $row["GENKA_TANKA"], PDO::PARAM_INT);              //商品名

                $flg=$stmt->execute();

                if($flg!==true){
                    file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,failed,".$sqllog."\n",FILE_APPEND);
                    $emsg=$emsg."/在庫登録のinsert処理が失敗し、rollBackが発生しました。";
                    $E_Flg=1;
                    break;  //1件でも失敗したらロールバック
                }else{
                    file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,succsess,".$sqllog."\n",FILE_APPEND);
                }
            }

            if($E_Flg===0){
                $pdo_h->commit();
                $msg = "在庫が登録されました。（在庫№：".$zaikoNO."）";
                $alert_status = "alert-success";
            }else{
                $pdo_h->rollBack();
                $msg = "在庫登録処理が失敗しました。";
                $alert_status = "alert-danger";
            }
            $reseve_status=true;

        }catch(Exception $e){
            $pdo_h->rollBack();
            $msg = "システムエラーによる更新失敗。管理者へ通知しました。";
            $alert_status = "alert-danger";
            log_writer2("ajax_EVregi_zaiko_sql.php [Exception \$e] =>",$e,"lv0");
            $reseve_status=true;
        }
    }
}
$stmt = null;
$pdo_h = null;

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
        log_writer2("ajax_EVregi_zaiko_sql.php","shutdown","lv3");
        log_writer2("ajax_EVregi_zaiko_sql.php",$lastError,"lv1");
          
        $emsg = "/UriNO::".$GLOBALS["zaikoNO"]."　uid::".$_SESSION['user_id']." ERROR_MESSAGE::予期せぬエラー".$lastError['message'];
        if(EXEC_MODE!=="Local"){
            send_mail(SYSTEM_NOTICE_MAIL,"【WEBREZ-WARNING】ajax_EVregi_zaiko_sql.phpでシステム停止",$emsg);
        }
        log_writer2("ajax_EVregi_zaiko_sql.php [Exception \$lastError] =>",$lastError,"lv0");
    
        $token = csrf_create();
        $return_sts = array(
            "MSG" => "システムエラーによる更新失敗。管理者へ通知しました。"
            ,"status" => "alert-danger"
            ,"csrf_create" => $token
        );
        header('Content-type: application/json');
        echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);
      }
  }
  

?>
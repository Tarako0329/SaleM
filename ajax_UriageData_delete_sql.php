<?php
//log_writer2("ajax_UriageDate_update_sql.php",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";  //ユーザー向け処理結果メッセージ
$alert_status = "alert-warning";    //bootstrap alert class
$reseve_status=false; //処理結果セット済みフラグ。
$timeout=false; //セッション切れ。ログイン画面に飛ばすフラグ

//if(csrf_chk()===false){
if(csrf_checker(["UriageData_Correct.php"],["C","P","S"])===false){
    $msg="セッションが正しくありませんでした②";
    $alert_status = "alert-warning";
    $reseve_status = true;
}else{
    $rtn=check_session_userid_for_ajax($pdo_h);
    if($rtn===false){
        $reseve_status = true;
        $msg="長時間未操作のため、処理を中断しました。再度ログインし、もう一度操作して下さい。";
        $_SESSION["EMSG"]="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。";
        $timeout=true;
    }else{
        $logfilename="sid_".$_SESSION['user_id'].".log";

        //更新モード(実行)
        $sql = "delete from UriageData where uid = :w_uid and UriageNO = :w_UriNO and ShouhinCD = :w_shouhinCD";
        //$up_sqllog = "delete from UriageData where uid = '".$_SESSION["user_id"]."' and UriageNO = '".$_POST["UriageNO"]."' and ShouhinCD = '".$_POST["ShouhinCD"]."'";
    
        try{
            $pdo_h->beginTransaction();
            $sqllog .= rtn_sqllog("START TRANSACTION",[]);
            $stmt = $pdo_h->prepare( $sql );
            //bind処理
            $params["w_uid"]=$_SESSION["user_id"];
            $params["w_UriNO"]=$_POST["UriageNO"];
            $params["w_shouhinCD"]=$_POST["ShouhinCD"];
            $stmt->bindValue("w_uid", $params["w_uid"], PDO::PARAM_INT);
            $stmt->bindValue("w_UriNO", $params["w_UriNO"], PDO::PARAM_INT);
            $stmt->bindValue("w_shouhinCD", $params["w_shouhinCD"], PDO::PARAM_INT);
            $sqllog .= rtn_sqllog($sql,$params);
            $status = $stmt->execute();
            $sqllog .= rtn_sqllog("--execute():正常終了",[]);
            //$count = $stmt->rowCount();
            $pdo_h->commit();
            $sqllog .= rtn_sqllog("commit",[]);
            sqllogger($sqllog,0);

            $reseve_status=true;
            $msg = "削除成功。";
            $alert_status = "alert-success";
            /*
            if($status && $count<>0){
                $pdo_h->commit();
                $sqllog .= rtn_sqllog("commit",[]);
                sqllogger($sqllog,0);
        
                $reseve_status=true;
                $msg = "削除成功。";
                $alert_status = "alert-success";
                file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,succsess,".$up_sqllog."\n",FILE_APPEND);
            }else{
                $pdo_h->rollBack();
                $sqllog .= rtn_sqllog("rollBack",[]);
                $reseve_status=true;
                $msg = "削除失敗。";
                $alert_status = "alert-danger";
                file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,failed,".$up_sqllog."\n",FILE_APPEND);
            }
            */
        }catch(Exception $e){
            $pdo_h->rollBack();
            $sqllog .= rtn_sqllog("rollBack",[]);
            sqllogger($sqllog,$e);
            $msg = "システムエラーによる更新失敗。管理者へ通知しました。";
            $alert_status = "alert-danger";
            log_writer2("ajax_UriageData_update_sql.php [Exception \$e] =>",$e,"lv0");
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
    // トランザクション中のUnCatchErrorは自動rollbackされる。
      $lastError = error_get_last();
      
      //直前でエラーあり、かつ、catch処理出来ていない場合に実行
      if($lastError!==null && $GLOBALS["reseve_status"] === false){
        log_writer2(basename(__FILE__),"shutdown","lv3");
        log_writer2(basename(__FILE__),$lastError,"lv1");
          
        $emsg = "/UriNO::".$GLOBALS["UriageNO"]."　uid::".$_SESSION['user_id']." ERROR_MESSAGE::予期せぬエラー".$lastError['message'];
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
<?php
//log_writer2("ajax_UriageDate_update_sql.php",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";                          //ユーザー向け処理結果メッセージ
$alert_status = "alert-warning";    //bootstrap alert class
$reseve_status=false;               //処理結果セット済みフラグ。
$timeout=false;                     //セッション切れ。ログイン画面に飛ばすフラグ

if(csrf_chk()===false){
    $msg="セッションが正しくありませんでした②";
    $alert_status = "alert-warning";
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

        //更新モード(実行)
        $sql = "update UriageData set tanka = :tanka,updDatetime=now() where UriDate = :w_date_from ";
        $up_sqllog = "update UriageData set tanka = '".$_POST["up_uritanka"]."' , updDatetime=now() where UriDate = '".$_POST["w_date_from"]."'";

        try{
            $pdo_h->beginTransaction();
            $stmt = $pdo_h->prepare( $sql );
            //bind処理
            $stmt->bindValue("tanka", $_POST["tanka"], PDO::PARAM_INT);
            $stmt->bindValue("w_date_to", $_POST["w_date_to"], PDO::PARAM_STR);

            $status = $stmt->execute();
            $count = $stmt->rowCount();

            if($status && $count<>0){
                $pdo_h->commit();
                $msg = "更新成功。";
                $alert_status = "alert-success";
                file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,succsess,".$up_sqllog."\n",FILE_APPEND);
            }else{
                $pdo_h->rollBack();
                $msg = "更新失敗。";
                $alert_status = "alert-danger";
                file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,failed,".$up_sqllog."\n",FILE_APPEND);
            }
            $reseve_status=true;
        }catch(Exception $e){
            $pdo_h->rollBack();
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
    // トランザクション中のエラー停止時は自動rollbackされる。
      $lastError = error_get_last();
      
      //直前でエラーあり、かつ、catch処理出来ていない場合に実行
      if($lastError!==null && $GLOBALS["reseve_status"] === false){
        log_writer2("ajax_EVregi_sql.php","shutdown","lv3");
        log_writer2("ajax_EVregi_sql.php",$lastError,"lv1");
          
        $emsg = "/UriNO::".$GLOBALS["UriageNO"]."　uid::".$_SESSION['user_id']." ERROR_MESSAGE::予期せぬエラー".$lastError['message'];
        send_mail(SYSTEM_NOTICE_MAIL,"【WEBREZ-WARNING】EVregi_sql.phpでシステム停止",$emsg);
        log_writer2("ajax_UriageData_update_sql.php [Exception \$lastError] =>",$lastError,"lv0");
    
        $token = csrf_create();
        $return_sts[0] = array(
            "MSG" => "システムエラーによる更新失敗。管理者へ通知しました。"
            ,"status" => "alert-danger"
            ,"csrf_create" => $token
        );
        header('Content-type: application/json');
        echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);
      }
  }
  

?>
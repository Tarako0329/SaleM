<?php
//log_writer2(basename(__FILE__)."",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";                          //ユーザー向け処理結果メッセージ
$alert_status = "alert-warning";    //bootstrap alert class
$reseve_status=false;               //処理結果セット済みフラグ。
$timeout=false;                     //セッション切れ。ログイン画面に飛ばすフラグ
$sqllog="";

$rtn = csrf_checker(["xxx.php","xxx.php"],["P","C","S"]);
if($rtn !== true){
    $msg=$rtn;
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
            $sqllog .= rtn_sqllog("START TRANSACTION",[]);
            sqllogger("START TRANSACTION",[],basename(__FILE__),"ok");
            $stmt = $pdo_h->prepare( $sql );
            //bind処理
            $stmt->bindValue("tanka", $_POST["tanka"], PDO::PARAM_INT);
            $stmt->bindValue("w_date_to", $_POST["w_date_to"], PDO::PARAM_STR);

            $status = $stmt->execute();
            $count = $stmt->rowCount();

            if($status && $count<>0){
                $pdo_h->commit();
                $sqllog .= rtn_sqllog("commit",[]);
                sqllogger($sqllog,0);
        
                $msg = "更新成功。";
                $alert_status = "alert-success";
            }else{
                $pdo_h->rollBack();
                $sqllog .= rtn_sqllog("rollBack",[]);
                $msg = "更新失敗。";
                $alert_status = "alert-danger";
            }
            $reseve_status=true;
        }catch(Exception $e){
            $pdo_h->rollBack();
            $sqllog .= rtn_sqllog("rollBack",[]);
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
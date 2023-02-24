<?php
//log_writer2("ajax_UriageDate_update_sql.php",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";                          //ユーザー向け処理結果メッセージ
$alert_status = "alert-warning";    //bootstrap alert class
$reseve_status=false;               //処理結果セット済みフラグ。
$timeout=false;                     //セッション切れ。ログイン画面に飛ばすフラグ

//$rtn = csrf_checker(["xxx.php","xxx.php"],["P","C","S"]);
$rtn=true;
if($rtn !== true){
    /*
    $msg=$rtn;
    $alert_status = "alert-warning";
    $reseve_status = true;
    */
}else{
    if($rtn===false){
        /*
        $reseve_status = true;
        $msg="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度xxxxxxして下さい。";
        $_SESSION["EMSG"]="長時間操作されていないため、自動ﾛｸﾞｱｳﾄしました。再度ログインし、もう一度xxxxxxして下さい。";
        $timeout=true;
        */
    }else{
        $sqlstr="select count(*) as cnt from Users where mail=?";
        try{
            $stmt = $pdo_h->prepare($sqlstr);
            $stmt->bindValue(1, $_GET["MAIL"], PDO::PARAM_STR);
            $status = $stmt->execute();
            $count = $stmt->rowCount();
            $row = $stmt->fetchAll();
            
            if($status===true){
                if($row[0]["cnt"]>=1){
                    $msg = "メールアドレスは登録済みになります。";
                    $alert_status = "alert-warning";
                }else{
                    $msg = "メールアドレスは登録可能です。";
                    $alert_status = "alert-success";
                }
            }else{
                $msg = "メールアドレスチェック処理失敗。";
                $alert_status = "alert-danger";
            }
            $reseve_status=true;
        }catch(Exception $e){
            $msg = "システムエラー。管理者へ通知しました。";
            $alert_status = "alert-danger";
            log_writer2("ajax_chk_email.php [Exception \$e] =>",$e,"lv0");
            $reseve_status=true;
        }
    }
}


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
        if(EXEC_MODE!=="Local"){
            send_mail(SYSTEM_NOTICE_MAIL,"【WEBREZ-WARNING】EVregi_sql.phpでシステム停止",$emsg);
        }
        log_writer2("ajax_UriageData_update_sql.php [Exception \$lastError] =>",$lastError,"lv0");
    
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
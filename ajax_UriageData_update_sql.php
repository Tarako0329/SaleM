<?php
//log_writer2("ajax_UriageDate_update_sql.php",$sql,"lv3");
require "php_header.php";
register_shutdown_function('shutdown');

$msg = "";                          //ユーザー向け処理結果メッセージ
$alert_status = "alert-warning";    //bootstrap alert class
$reseve_status=false;               //処理結果セット済みフラグ。
$timeout=false;                     //セッション切れ。ログイン画面に飛ばすフラグ
$sqllog="";

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
        
        $where_sql = " where UriDate between :w_date_from and :w_date_to";
        $where_sql = $where_sql." and cast(UriDate as char) like :w_date";
        $where_sql = $where_sql." and concat(Event,TokuisakiNM) like :w_event";
        $where_sql = $where_sql." and cast(ShouhinCD as char) like :w_shouhincd";
        $where_sql = $where_sql." and cast(UriageNO as char) like :w_urino";
        $where_sql = $where_sql." and uid = :w_user_id";
        /*
        $where_sqllog = " where UriDate between '".$_POST["w_date_from"]."' and '".$_POST["w_date_to"]."'";
        $where_sqllog = $where_sqllog." and cast(UriDate as char) like '".$_POST["w_date"]."'";
        $where_sqllog = $where_sqllog." and concat(Event,TokuisakiNM) like '".$_POST["w_event"]."'";
        $where_sqllog = $where_sqllog." and cast(ShouhinCD as char) like '".$_POST["w_shouhincd"]."'";
        $where_sqllog = $where_sqllog." and cast(UriageNO as char) like '".$_POST["w_urino"]."'";
        $where_sqllog = $where_sqllog." and uid = '".$_SESSION["user_id"]."'";
        */

        //更新モード(実行)
        $sql = "update UriageData set ";
        $up_sql = "";
        $up_sqllog = "";
        if(!empty($_POST["chk_uridate"])){
            $up_sql = $up_sql." UriDate = :UriDate , ";
            //$up_sqllog = $up_sqllog." UriDate = '".$_POST["up_uridate"]."' , ";
        }
        if(!empty($_POST["chk_event"])){
            $up_sql = $up_sql." Event = :Event , TokuisakiNM='' , ";
            //$up_sqllog = $up_sqllog." Event = '".$_POST["up_event"]."' , ";
        }
        if(!empty($_POST["chk_kokyaku"])){
            $up_sql = $up_sql." TokuisakiNM = :TokuisakiNM , Event = '' , ";
            //$up_sqllog = $up_sqllog." TokuisakiNM = '".$_POST["up_kokyaku"]."' , ";
        }
        if(!empty($_POST["chk_urikin"])){
            $up_sql = $up_sql." tanka = :tanka , ";
            $up_sql = $up_sql." UriageKin = :tanka2 * `su` , ";
            $up_sql = $up_sql." zei = :zei * `su` , ";
            $up_sql = $up_sql." zeiKBN = :zeiKBN , ";
            /*
            $up_sqllog = $up_sqllog." tanka = '".$_POST["up_uritanka"]."' , ";
            $up_sqllog = $up_sqllog." UriageKin = '".$_POST["up_uritanka"]."' * `su` , ";
            $up_sqllog = $up_sqllog." zei = '".$_POST["up_zei"]."' * `su` , ";
            $up_sqllog = $up_sqllog." zeiKBN = '".$_POST["up_zeikbn"]."' , ";
            */
        }
        if(!empty($_POST["chk_genka"])){
            $up_sql = $up_sql." genka_tanka = :genka_tanka , ";
            //$up_sqllog = $up_sqllog." genka_tanka = '".$_POST["up_urigenka"]."' , ";
        }

        if(!empty($up_sql)){
            //$up_sqlのケツ2文字(, )を削る
            $up_sql = substr($up_sql,0,-2);
            //$up_sqllog = substr($up_sqllog,0,-2);

            //$up_sqllog=$sql.$up_sqllog." ,updDatetime=now() ".$where_sqllog;
            $sql=$sql.$up_sql." ,updDatetime=now() ".$where_sql;

            try{
                $pdo_h->beginTransaction();
                $sqllog .= rtn_sqllog("START TRANSACTION",[]);
    
                $stmt = $pdo_h->prepare( $sql );
                $params=[];
                //bind処理
                if(!empty($_POST["chk_uridate"])){
                    $params["UriDate"]=$_POST["up_uridate"];
                    $stmt->bindValue("UriDate", $params["UriDate"], PDO::PARAM_STR);
                }
                if(!empty($_POST["chk_event"])){
                    $params["Event"]=$_POST["up_event"];
                    $stmt->bindValue("Event", $params["Event"], PDO::PARAM_STR);
                }
                if(!empty($_POST["chk_kokyaku"])){
                    $params["TokuisakiNM"]=$_POST["up_kokyaku"];
                    $stmt->bindValue("TokuisakiNM", $params["TokuisakiNM"], PDO::PARAM_STR);
                }
                if(!empty($_POST["chk_urikin"])){
                    $params["tanka"]=$_POST["up_uritanka"];
                    $params["tanka2"]=$_POST["up_uritanka"];
                    $params["zei"]=$_POST["up_zei"];
                    $params["zeiKBN"]=$_POST["up_zeikbn"];
                    $stmt->bindValue("tanka", $params["tanka"], PDO::PARAM_INT);
                    $stmt->bindValue("tanka2", $params["tanka2"], PDO::PARAM_INT);
                    $stmt->bindValue("zei", $params["zei"], PDO::PARAM_INT);
                    $stmt->bindValue("zeiKBN", $params["zeiKBN"], PDO::PARAM_INT);
                }
                if(!empty($_POST["chk_genka"])){
                    $params["genka_tanka"]=$_POST["up_urigenka"];
                    $stmt->bindValue("genka_tanka", $params["genka_tanka"], PDO::PARAM_INT);
                }
                $params["w_date_from"]=$_POST["w_date_from"];
                $params["w_date_to"]=$_POST["w_date_to"];
                $params["w_date"]=$_POST["w_date"];
                $params["w_event"]=$_POST["w_event"];
                $params["w_shouhincd"]=$_POST["w_shouhincd"];
                $params["w_urino"]=$_POST["w_urino"];
                $params["w_user_id"]=$_SESSION["user_id"];

                $stmt->bindValue("w_date_from", $params["w_date_from"], PDO::PARAM_STR);
                $stmt->bindValue("w_date_to", $params["w_date_to"], PDO::PARAM_STR);
                $stmt->bindValue("w_date", $params["w_date"], PDO::PARAM_STR);
                $stmt->bindValue("w_event", $params["w_event"], PDO::PARAM_STR);
                $stmt->bindValue("w_shouhincd", $params["w_shouhincd"], PDO::PARAM_STR);
                $stmt->bindValue("w_urino", $params["w_urino"], PDO::PARAM_STR);
                $stmt->bindValue("w_user_id", $params["w_user_id"], PDO::PARAM_INT);

                $status = $stmt->execute();
				$sqllog .= rtn_sqllog($sql,$params);
				$status=$stmt->execute();
                $pdo_h->commit();
                $sqllog .= rtn_sqllog("commit",[]);
                sqllogger($sqllog,0);
    
                $msg = "更新成功。";
                $alert_status = "alert-success";
                $reseve_status = true;

                /*
                if($status && $count<>0){
                    $msg = "更新成功。";
                    $alert_status = "alert-success";
                    file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,succsess,".$up_sqllog."\n",FILE_APPEND);
                    $reseve_status = true;
                }else{
                    $msg = "更新失敗。";
                    $alert_status = "alert-danger";
                    file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,failed,".$up_sqllog."\n",FILE_APPEND);
                    $reseve_status = true;
                }
                */
            }catch(Exception $e){
                $pdo_h->rollBack();
                $sqllog .= rtn_sqllog("rollBack",[]);
                sqllogger($sqllog,$e);
                $msg = "システムエラーによる更新失敗。管理者へ通知しました。";
                $alert_status = "alert-danger";
                //log_writer2("ajax_UriageData_update_sql.php [Exception \$e] =>",$e,"lv0");
                $reseve_status = true;
            }

        }else{
            $msg = "更新対象が選択されてません。";
            $alert_status = "alert-warning";
            $reseve_status = true;
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
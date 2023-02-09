<?php
require "php_header.php";
if(csrf_chk()==false){
    $_SESSION["EMSG"]="セッションが正しくありませんでした②";
    exit();
}

$rtn=check_session_userid($pdo_h);
$logfilename="sid_".$_SESSION['user_id'].".log";
$csrf_create = csrf_create();

$msg = "";
$where_sql = " where UriDate between :w_date_from and :w_date_to";
$where_sql = $where_sql." and cast(UriDate as char) like :w_date";
$where_sql = $where_sql." and concat(Event,TokuisakiNM) like :w_event";
$where_sql = $where_sql." and cast(ShouhinCD as char) like :w_shouhincd";
$where_sql = $where_sql." and cast(UriageNO as char) like :w_urino";
$where_sql = $where_sql." and uid = :w_user_id";

$where_sqllog = " where UriDate between '".$_POST["w_date_from"]."' and '".$_POST["w_date_to"]."'";
$where_sqllog = $where_sqllog." and cast(UriDate as char) like '".$_POST["w_date"]."'";
$where_sqllog = $where_sqllog." and concat(Event,TokuisakiNM) like '".$_POST["w_event"]."'";
$where_sqllog = $where_sqllog." and cast(ShouhinCD as char) like '".$_POST["w_shouhincd"]."'";
$where_sqllog = $where_sqllog." and cast(UriageNO as char) like '".$_POST["w_urino"]."'";
$where_sqllog = $where_sqllog." and uid = '".$_SESSION["user_id"]."'";


//更新モード(実行)
$sql = "update UriageData set ";
$up_sql = "";
$up_sqllog = "";
if(!empty($_POST["chk_uridate"])){
    $up_sql = $up_sql." UriDate = :UriDate , ";
    $up_sqllog = $up_sqllog." UriDate = '".$_POST["up_uridate"]."' , ";
}
if(!empty($_POST["chk_event"])){
    $up_sql = $up_sql." Event = :Event , ";
    $up_sqllog = $up_sqllog." Event = '".$_POST["up_event"]."' , ";
}
if(!empty($_POST["chk_kokyaku"])){
    $up_sql = $up_sql." TokuisakiNM = :TokuisakiNM , ";
    $up_sqllog = $up_sqllog." TokuisakiNM = '".$_POST["up_kokyaku"]."' , ";
}
if(!empty($_POST["chk_urikin"])){
    $up_sql = $up_sql." tanka = :tanka , ";
    $up_sql = $up_sql." UriageKin = :tanka2 * `su` , ";
    $up_sql = $up_sql." zei = :zei * `su` , ";
    $up_sql = $up_sql." zeiKBN = :zeiKBN , ";
    $up_sqllog = $up_sqllog." tanka = '".$_POST["up_uritanka"]."' , ";
    $up_sqllog = $up_sqllog." UriageKin = '".$_POST["up_uritanka"]."' * `su` , ";
    $up_sqllog = $up_sqllog." zei = '".$_POST["up_zei"]."' * `su` , ";
    $up_sqllog = $up_sqllog." zeiKBN = '".$_POST["up_zeikbn"]."' , ";
}
if(!empty($_POST["chk_genka"])){
    $up_sql = $up_sql." genka_tanka = :genka_tanka , ";
    $up_sqllog = $up_sqllog." genka_tanka = '".$_POST["up_urigenka"]."' , ";
}

if(!empty($up_sql)){
    //$up_sqlのケツ2文字(, )を削る
    $up_sql = substr($up_sql,0,-2);
    $up_sqllog = substr($up_sqllog,0,-2);
    
    $up_sqllog=$sql.$up_sqllog." ,updDatetime=now() ".$where_sqllog;
    $sql=$sql.$up_sql." ,updDatetime=now() ".$where_sql;
    
    log_writer2("ajax_UriageDate_update_sql.php",$sql,"lv3");
    log_writer2("ajax_UriageDate_update_sql.php",$up_sqllog,"lv3");
    
    $stmt = $pdo_h->prepare( $sql );
    
    if(!empty($_POST["chk_uridate"])){
        $stmt->bindValue("UriDate", $_POST["up_uridate"], PDO::PARAM_STR);
    }
    if(!empty($_POST["chk_event"])){
        $stmt->bindValue("Event", $_POST["up_event"], PDO::PARAM_STR);
    }
    if(!empty($_POST["chk_kokyaku"])){
        $stmt->bindValue("TokuisakiNM", $_POST["up_kokyaku"], PDO::PARAM_STR);
    }
    if(!empty($_POST["chk_urikin"])){
        $stmt->bindValue("tanka", $_POST["up_uritanka"], PDO::PARAM_INT);
        $stmt->bindValue("tanka2", $_POST["up_uritanka"], PDO::PARAM_INT);
        $stmt->bindValue("zei", $_POST["up_zei"], PDO::PARAM_INT);
        $stmt->bindValue("zeiKBN", $_POST["up_zeikbn"], PDO::PARAM_INT);
    }
    if(!empty($_POST["chk_genka"])){
        $stmt->bindValue("genka_tanka", $_POST["up_urigenka"], PDO::PARAM_INT);
    }
    
    $stmt->bindValue("w_date_from", $_POST["w_date_from"], PDO::PARAM_STR);
    $stmt->bindValue("w_date_to", $_POST["w_date_to"], PDO::PARAM_STR);
    $stmt->bindValue("w_date", $_POST["w_date"], PDO::PARAM_STR);
    $stmt->bindValue("w_event", $_POST["w_event"], PDO::PARAM_STR);
    $stmt->bindValue("w_shouhincd", $_POST["w_shouhincd"], PDO::PARAM_STR);
    $stmt->bindValue("w_urino", $_POST["w_urino"], PDO::PARAM_STR);
    $stmt->bindValue("w_user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    
    $status = $stmt->execute();
    $count = $stmt->rowCount();
    if($status && $count<>0){
        $msg = "更新成功。";
        //file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,succsess,".$_SESSION['user_id']."/".$_SESSION["UpNM"]."/".$_SESSION["wheresql"]."\n",FILE_APPEND);
        file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,succsess,".$up_sqllog."\n",FILE_APPEND);
    }else{
        $msg = "更新失敗。";
        //file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,failed,".$_SESSION['user_id']."/".$_SESSION["UpNM"]."/".$_SESSION["wheresql"]."\n",FILE_APPEND);
        file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,failed,".$up_sqllog."\n",FILE_APPEND);
    }
    
}else{
    $msg = "SYSTEM ERROR：更新対象が選択されてません。";
}
header('Content-type: application/json');
echo json_encode($msg, JSON_UNESCAPED_UNICODE);

exit();

?>
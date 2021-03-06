<?php
require "php_header.php";
if(csrf_chk()==false){
    $_SESSION["EMSG"]="セッションが正しくありませんでした②";
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}

$rtn=check_session_userid($pdo_h);
$logfilename="sid_".$_SESSION['user_id'].".log";
$csrf_create = csrf_create();

$msg = "";

if($_POST["mode"] == "del"){
    //削除モード(実行)
    $sql="delete from UriageData where uid = :user_id and UriageNO = :UriNO and ShouhinCD = :ShouhinCD";
    $sqllog="delete from UriageData where uid = ".$_SESSION["user_id"]." and UriageNO = ".$_SESSION["urino"]." and ShouhinCD = ".$_SESSION["cd"]."";
    $stmt = $pdo_h->prepare( $sql );
    $stmt->bindValue("UriNO", $_SESSION["urino"], PDO::PARAM_INT);
    $stmt->bindValue("ShouhinCD", $_SESSION["cd"], PDO::PARAM_INT);
    $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $status = $stmt->execute();
    $count = $stmt->rowCount();
    if($status && $count<>0){
        $_SESSION["MSG"] = "売上を削除しました。";
        //file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,DELETE,succsess,".$_SESSION['user_id']."/".$_SESSION["urino"]."/-/".$_SESSION["cd"]."/-/-/-/-\n",FILE_APPEND);
        file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,DELETE,succsess,".$sqllog."\n",FILE_APPEND);
    }else{
        $_SESSION["MSG"] = "売上削除失敗。";
        //file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,DELETE,failed,".$_SESSION['user_id']."/".$_SESSION["urino"]."/-/".$_SESSION["cd"]."/-/-/-/-\n",FILE_APPEND);
        file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,DELETE,failed,".$sqllog."\n",FILE_APPEND);
    }
}elseif($_POST["mode"]=="Update" && $_SESSION["wheresql"]<>""){
        //更新モード(実行)

        $sql = "update UriageData set ";
        $up_sql = "";
        $up_sqllog = "";
        if(!empty($_SESSION["chk_uridate"])){
            $up_sql = $up_sql." UriDate = :UriDate , ";
            $up_sqllog = $up_sqllog." UriDate = '".$_SESSION["up_uridate"]."' , ";
        }
        if(!empty($_SESSION["chk_event"])){
            $up_sql = $up_sql." Event = :Event , ";
            $up_sqllog = $up_sqllog." Event = '".$_SESSION["up_event"]."' , ";
        }
        if(!empty($_SESSION["chk_kokyaku"])){
            $up_sql = $up_sql." TokuisakiNM = :TokuisakiNM , ";
            $up_sqllog = $up_sqllog." TokuisakiNM = '".$_SESSION["up_kokyaku"]."' , ";
        }
        if(!empty($_SESSION["chk_urikin"])){
            $up_sql = $up_sql." tanka = :tanka , ";
            $up_sql = $up_sql." UriageKin = :tanka2 * `su` , ";
            $up_sql = $up_sql." zei = :zei * `su` , ";
            $up_sql = $up_sql." zeiKBN = :zeiKBN , ";

            $up_sqllog = $up_sqllog." tanka = '".$_SESSION["up_uritanka"]."' , ";
            $up_sqllog = $up_sqllog." UriageKin = '".$_SESSION["up_uritanka"]."' * `su` , ";
            $up_sqllog = $up_sqllog." zei = '".$_SESSION["up_zei"]."' * `su` , ";
            $up_sqllog = $up_sqllog." zeiKBN = '".$_SESSION["up_zeikbn"]."' , ";
        }
        if(!empty($_SESSION["chk_genka"])){
            $up_sql = $up_sql." genka_tanka = :genka_tanka , ";
            $up_sqllog = $up_sqllog." genka_tanka = '".$_SESSION["up_urigenka"]."' , ";
        }
        
        if(!empty($up_sql)){
            //$up_sqlのケツ2文字(, )を削る
            $up_sql = substr($up_sql,0,-2);
            $up_sqllog = substr($up_sqllog,0,-2);
            
            $up_sqllog=$sql.$up_sqllog." ,updDatetime=now() ".$_SESSION["wheresql"];
            $sql=$sql.$up_sql." ,updDatetime=now() ".$_SESSION["wheresql"];

            echo $sql;
            $stmt = $pdo_h->prepare( $sql );
            
            if(!empty($_SESSION["chk_uridate"])){
                $stmt->bindValue("UriDate", $_SESSION["up_uridate"], PDO::PARAM_STR);
            }
            if(!empty($_SESSION["chk_event"])){
                $stmt->bindValue("Event", $_SESSION["up_event"], PDO::PARAM_STR);
            }
            if(!empty($_SESSION["chk_kokyaku"])){
                $stmt->bindValue("TokuisakiNM", $_SESSION["up_kokyaku"], PDO::PARAM_STR);
            }
            if(!empty($_SESSION["chk_urikin"])){
                $stmt->bindValue("tanka", $_SESSION["up_uritanka"], PDO::PARAM_INT);
                $stmt->bindValue("tanka2", $_SESSION["up_uritanka"], PDO::PARAM_INT);
                $stmt->bindValue("zei", $_SESSION["up_zei"], PDO::PARAM_INT);
                $stmt->bindValue("zeiKBN", $_SESSION["up_zeikbn"], PDO::PARAM_INT);
            }
            if(!empty($_SESSION["chk_genka"])){
                $stmt->bindValue("genka_tanka", $_SESSION["up_urigenka"], PDO::PARAM_INT);
            }
            
            $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
            
            $status = $stmt->execute();
            $count = $stmt->rowCount();
            if($status && $count<>0){
                $_SESSION["MSG"] = "更新成功。";
                //file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,succsess,".$_SESSION['user_id']."/".$_SESSION["UpNM"]."/".$_SESSION["wheresql"]."\n",FILE_APPEND);
                file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,succsess,".$up_sqllog."\n",FILE_APPEND);
            }else{
                $_SESSION["MSG"] = "更新失敗。";
                //file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,failed,".$_SESSION['user_id']."/".$_SESSION["UpNM"]."/".$_SESSION["wheresql"]."\n",FILE_APPEND);
                file_put_contents("sql_log/".$logfilename,date("Y-m-d H:i:s").",UriageData_sql.php,UPDATE,failed,".$up_sqllog."\n",FILE_APPEND);
            }
            
        }else{
            $_SESSION["MSG"] = "SYSTEM ERROR：更新対象が選択されてません。";
        }
        
        


        
}
header("HTTP/1.1 301 Moved Permanently");
header("Location: UriageData_Correct.php?mode=Updated&csrf_token=".$csrf_create);
exit();

?>
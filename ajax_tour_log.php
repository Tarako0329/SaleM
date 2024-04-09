<?php
//シェパードツアーガイドの終了記録
//$tourName="help"の場合はセッション変数をクリア
/*
date_default_timezone_set('Asia/Tokyo');
session_start();
*/
require "php_header.php";
//require "functions.php";
require "./vendor/autoload.php";
log_writer2("ajax_tour_log.php POST values ",$_POST,"lv3");
$logfilename="sid_".$_SESSION['user_id'].".log";
$time=date("Y-m-d H:m:s");
if($_POST){
    $tourName=$_POST["tourName"];
    $user_id =$_POST["user_id"];
    $step  =$_POST["step"];
    $status  =$_POST["status"];
}else{
    exit();
}

//file_put_contents("sql_log/".$logfilename,$time.",ajax_tour_log.php,start,success,".$status."\n",FILE_APPEND);
if($status=="save"){
    $_SESSION["tour"]=$step;
}elseif($status=="finish"){
    $step="finish";
    $_SESSION["tour"]="";
}else{
    //
}

if(!empty($status)){
    $pass=dirname(__FILE__);
    /*
    //.envの取得
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    
    define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
    define("USER_NAME", $_ENV["DBUSER"]);
    define("PASSWORD", $_ENV["PASS"]);
    // DBとの接続
    $pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());
    */
    
    //指定のKeyValueが登録済みかを確認
    //$sqlstr="SELECT uid,JSON_VALUE(ToursLog,'$.".$tourName."') as KeyValue FROM Users WHERE uid=?";
    $sqlstr="select uid,JSON_VALUE(ToursLog,'$.".$tourName."') as KeyValue FROM Users_webrez WHERE uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($row[0]["KeyValue"])){
        //KeyValueの追加
        //$sqlstr = "update Users set ToursLog=json_merge(ToursLog, json_object(?, ?)) where uid=?";
        $sql_upd="json_merge(ToursLog, json_object(?, ?))";
        //$sqlstr = "update Users set ToursLog=".$sql_upd." where uid=?";
        $sqlstr = "update Users_webrez set ToursLog=".$sql_upd." where uid=?";
    
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $tourName, PDO::PARAM_STR);
        $stmt->bindValue(2, $step, PDO::PARAM_STR);
        $stmt->bindValue(3, $user_id, PDO::PARAM_INT);
        //file_put_contents("sql_log/".$logfilename,$time.",ajax_tour_log.php,KeyValAdd,success,".$status."\n",FILE_APPEND);
    }else{
        //KeyValueの更新
        //UPDATE Users SET ToursLog = JSON_SET(ToursLog, "$.tutorial", "finish") WHERE id = 2; //keyvalueを更新する場合はこれ
        $sql_upd="JSON_SET(ToursLog, '$.".$tourName."', ?)";
        //$sqlstr = "update Users set ToursLog=".$sql_upd." where uid=?";
        $sqlstr = "update Users_webrez set ToursLog=".$sql_upd." where uid=?";

        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $step, PDO::PARAM_STR);
        $stmt->bindValue(2, $user_id, PDO::PARAM_INT);
        //file_put_contents("sql_log/".$logfilename,$time.",ajax_tour_log.php,KeyValUpd,success,".$status."\n",FILE_APPEND);
    }
    /*
    $sqlstr = "update Users set ToursLog=".$sql_upd." where uid=?";

    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $tourName, PDO::PARAM_STR);
    $stmt->bindValue(2, $step, PDO::PARAM_INT);
    $stmt->bindValue(3, $user_id, PDO::PARAM_INT);
    */
    $flg=$stmt->execute();
    
    //file_put_contents("sql_log/".$logfilename,$time.",ajax_tour_log.php,UPDATE,success,".$status."\n",FILE_APPEND);
}else{
    if($tourName=="help"){
        $_SESSION["tour"]="";
    }else{
        $_SESSION["tour"]=$step;
    }
    //file_put_contents("sql_log/".$logfilename,$time.",ajax_tour_log.php,NoFinish,success,".$status."\n",FILE_APPEND);
}
?>
<?php
require "php_header.php";
$token = csrf_create();

$rtn=check_session_userid($pdo_h);

//入力画面の前回値を記録
$_SESSION["EV"] = $_POST["EV"];
$stmt = $pdo_h->prepare ( 'call PageDefVal_update(?,?,?,?,?)' );
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, MACHIN_ID, PDO::PARAM_STR);
$stmt->bindValue(3, "EVregi.php", PDO::PARAM_STR);
$stmt->bindValue(4, "EV", PDO::PARAM_STR);
$stmt->bindValue(5, $_POST["EV"], PDO::PARAM_STR);
$stmt->execute();

$E_Flg=0;
$_SESSION["msg"]="登録処理が実行されませんでした。";
$emsg="";

//売上登録
if($_POST["commit_btn"] <> ""){
//if(0){
    if(csrf_chk_nonsession()==false){
        $_SESSION["EMSG"]="セッションが正しくありませんでした。";
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: index.php");
        exit();
    }
    $array = $_POST["ORDERS"];
    $sqlstr = "";

    //売上番号の取得
    $sqlstr = "select max(UriageNO) as UriageNO from UriageData where uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->execute();
    
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if(is_null($row[0]["UriageNO"])){
        //初回売上時は売上NO[1]をセット
        $UriageNO = 1;
    }else{
        $UriageNO = $row[0]["UriageNO"]+1;
    }
    //echo (string)$UriageNO;
    
    try{
        $pdo_h->beginTransaction();
        foreach($array as $row){
            if($row["SU"]==0){
                continue;
            }
            $sqlstr = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)";
            $stmt = $pdo_h->prepare($sqlstr);
    
            $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
            //$stmt->bindValue(3,  date("Y/m/d"), PDO::PARAM_STR);
            $stmt->bindValue(3,  $_POST["KEIJOUBI"], PDO::PARAM_STR);
            $stmt->bindValue(4,  date("Y/m/d H:i:s"), PDO::PARAM_STR);
            $stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
            $stmt->bindValue(6,  $_POST["KOKYAKU"], PDO::PARAM_STR);
            $stmt->bindValue(7,  $row["CD"], PDO::PARAM_INT);                       //商品CD
            $stmt->bindValue(8,  $row["NM"], PDO::PARAM_STR);                       //商品名
            $stmt->bindValue(9,  $row["SU"], PDO::PARAM_INT);                       //数量
            $stmt->bindValue(10, $row["UTISU"], PDO::PARAM_INT);                    //内数
            $stmt->bindValue(11, $row["TANKA"], PDO::PARAM_INT);                    //単価
            $stmt->bindValue(12, ($row["SU"] * $row["TANKA"]), PDO::PARAM_INT);     //数量×単価
            $stmt->bindValue(13, ($row["SU"] * $row["ZEI"]), PDO::PARAM_INT);       //数量×単価税
            $stmt->bindValue(14, $row["ZEIKBN"], PDO::PARAM_INT);                   //税区分
            
            $flg=$stmt->execute();
            
            if($flg){
            }else{
                $emsg="売上げのINSERTでエラー";
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
                $stmt = $pdo_h->prepare($sqlstr);
        
                $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
                //$stmt->bindValue(3,  date("Y/m/d"), PDO::PARAM_STR);
                $stmt->bindValue(3,  $_POST["KEIJOUBI"], PDO::PARAM_STR);
                $stmt->bindValue(4,  date("Y/m/d H:i:s"), PDO::PARAM_STR);
                $stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
                $stmt->bindValue(6,  $_POST["KOKYAKU"], PDO::PARAM_STR);
                $stmt->bindValue(7,  9999, PDO::PARAM_INT);                                                             //商品CD
                $stmt->bindValue(8,  rot13encrypt("割引・割増:税率".(($row["zei_per"]-1)*100)."%分"), PDO::PARAM_STR);  //商品名
                $stmt->bindValue(9,  0, PDO::PARAM_INT);                                                                //数量
                $stmt->bindValue(10, 0, PDO::PARAM_INT);                                                                //内数
                $stmt->bindValue(11, 0, PDO::PARAM_INT);                                                                //単価
                $stmt->bindValue(12, $chousei_hon, PDO::PARAM_INT);                                                     //数量×単価
                $stmt->bindValue(13, $chousei_zei, PDO::PARAM_INT);                                                     //数量×単価税
                $stmt->bindValue(14, $row["ZEIKBN"], PDO::PARAM_INT);                                                   //税区分
                $flg=$stmt->execute();
                
                if($flg){
                }else{
                    $E_Flg=1;
                    $emsg=$emsg."/割引割増のINSERTでエラー";
                    break;
                }
            }
            if($goukei!=$chouseigaku){
                //端数あり
                $sqlstr = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)";
                $stmt = $pdo_h->prepare($sqlstr);
        
                $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
                //$stmt->bindValue(3,  date("Y/m/d"), PDO::PARAM_STR);
                $stmt->bindValue(3,  $_POST["KEIJOUBI"], PDO::PARAM_STR);
                $stmt->bindValue(4,  date("Y/m/d H:i:s"), PDO::PARAM_STR);
                $stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
                $stmt->bindValue(6,  $_POST["KOKYAKU"], PDO::PARAM_STR);
                $stmt->bindValue(7,  9999, PDO::PARAM_INT);                             //商品CD
                $stmt->bindValue(8,  rot13encrypt("割引・割増:端数"), PDO::PARAM_STR);  //商品名
                $stmt->bindValue(9,  0, PDO::PARAM_INT);                                //数量
                $stmt->bindValue(10, 0, PDO::PARAM_INT);                                //内数
                $stmt->bindValue(11, 0, PDO::PARAM_INT);                                //単価
                $stmt->bindValue(12, $chouseigaku-$goukei, PDO::PARAM_INT);             //数量×単価
                $stmt->bindValue(13, 0, PDO::PARAM_INT);                                //数量×単価税
                $stmt->bindValue(14, 0, PDO::PARAM_INT);                                //非課税//税区分
                $flg=$stmt->execute();
                 if($flg){
                }else{
                    $E_Flg=1;
                    $emsg=$emsg."/割引割増の端数調整のINSERTでエラー";
                }
               
            }
            //exit();
        }
        
        if($E_Flg==0){
            $pdo_h->commit();
            $_SESSION["msg"]="売上が登録されました。（売上№：".$UriageNO."）";
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: EVregi.php?status=success&mode=".$_POST["mode"]."&csrf_token=".$token);
            $stmt = null;
            $pdo_h = null;
            exit();
        }else{
            //1件でも失敗したらロールバック
            $pdo_h->rollBack();
            
            $emsg=$emsg."/insert処理が失敗し、rollBackが発生しました。";
            $stmt = null;
            $pdo_h = null;
            //exit();
        }
        
    }catch (Exception $e) {
        $pdo_h->rollBack();
        $emsg = $emsg."/レジ登録でERRORをCATHCしました。：".$e->getMessage();
        $E_Flg=1;
        $stmt = null;
        $pdo_h = null;
        //exit();
    }catch(Throwable $t){
        $pdo_h->rollBack();
        $emsg = $emsg."/レジ登録でFATAL ERRORをCATHCしました。：".$t->getMessage();
        $E_Flg=1;
        $stmt = null;
        $pdo_h = null;        
    }
}

if($E_Flg==1){
    $_SESSION["msg"]= "登録が失敗しました。再度実行してもエラーとなる場合は、ご迷惑をおかけしますが復旧までお待ちください。エラーは管理者へ自動通知されました。";
    $emsg = $emsg."/UriNO::".$UriageNO."　uid::".$_SESSION['user_id'];
    send_mail("green.green.midori@gmail.com","【WEBREZ-WARNING】EVregi_sql.phpでシステム停止",$emsg);
}

$stmt = null;
$pdo_h = null;
//Failure
header("HTTP/1.1 301 Moved Permanently");
header("Location: EVregi.php?status=failed&mode=".$_POST["mode"]."&csrf_token=".$token);
exit();

?>



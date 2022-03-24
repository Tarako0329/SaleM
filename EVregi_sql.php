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

//売上登録
if($_POST["commit_btn"] <> ""){
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
    
    $E_Flg=0;
    $hontai=0;
    $zei=0;
    try{
        $pdo_h->beginTransaction();
        foreach($array as $row){
            if($row["SU"]==0){
                continue;
            }
            //$sqlstr = "insert into UriageData values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)";
            $sqlstr = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)";
            $stmt = $pdo_h->prepare($sqlstr);
    
            $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
            $stmt->bindValue(3,  date("Y/m/d"), PDO::PARAM_STR);
            $stmt->bindValue(4,  date("Y/m/d H:i:s"), PDO::PARAM_STR);
            $stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
            $stmt->bindValue(6,  $_POST["KOKYAKU"], PDO::PARAM_STR);
            $stmt->bindValue(7,  $row["CD"], PDO::PARAM_INT);
            $stmt->bindValue(8,  $row["NM"], PDO::PARAM_STR);
            $stmt->bindValue(9,  $row["SU"], PDO::PARAM_INT);
            $stmt->bindValue(10, $row["UTISU"], PDO::PARAM_INT);
            
            if(substr(strval($row["ZEIKBN"]),3,1) =="1" || $row["ZEIKBN"]==0){
                //外税（マスタは税抜単価）
                $stmt->bindValue(11, $row["TANKA"], PDO::PARAM_INT);
                $stmt->bindValue(12, ($row["SU"] * $row["TANKA"]), PDO::PARAM_INT);
            }else{
                //内税（マスタは税込単価）
                $stmt->bindValue(11, $row["TANKA"] - $row["ZEI"], PDO::PARAM_INT);
                $stmt->bindValue(12, ($row["SU"] * ($row["TANKA"] - $row["ZEI"])), PDO::PARAM_INT);
            }
            $stmt->bindValue(13, ($row["SU"] * $row["ZEI"]), PDO::PARAM_INT);
            $stmt->bindValue(14, $row["ZEIKBN"], PDO::PARAM_INT);
            
            $flg=$stmt->execute();
            
            if($flg){
            }else{
                $E_Flg=1;
                break;
            }
        }
        
        if($_POST["CHOUSEI_GAKU"]>0 && $E_Flg!=1){
            $sqlstr="SELECT ZeiMS.zeiKBN as ZEIKBN ,1+ZeiMS.zeiritu/100 as zei_per ,sum(UriageKin+Zei) as uriage,SUM(UriageKin+Zei) OVER () AS total ";
            $sqlstr=$sqlstr."FROM UriageData inner join ZeiMS on UriageData.zeiKBN = ZeiMS.zeiKBN ";
            $sqlstr=$sqlstr."WHERE uid=? and UriageNO=? ";
            $sqlstr=$sqlstr."group by ZeiMS.zeiKBN,ZeiMS.zeiritu";
            $stmt = $pdo_h->prepare($sqlstr);
            $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
            $stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
            $flg=$stmt->execute();
            
            $result = $stmt->fetchAll();
            $goukei = 0;
            foreach($result as $row){
                $chouseigaku = $_POST["CHOUSEI_GAKU"] - $row["total"];

                $chousei_hon = bcdiv(bcmul($chouseigaku , bcdiv($row["uriage"] , $row["total"],5),5),$row["zei_per"],0);//調整額×税率割合÷消費税率
                $chousei_zei = bcsub(bcmul($chouseigaku , bcdiv($row["uriage"] , $row["total"],5),5) ,$chousei_hon,0);
                
                $goukei=$goukei+$chousei_hon+$chousei_zei;
                
                echo $chousei_hon."<br>";
                echo $chousei_zei."<br>";
                
                $sqlstr = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)";
                $stmt = $pdo_h->prepare($sqlstr);
        
                $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
                $stmt->bindValue(3,  date("Y/m/d"), PDO::PARAM_STR);
                $stmt->bindValue(4,  date("Y/m/d H:i:s"), PDO::PARAM_STR);
                $stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
                $stmt->bindValue(6,  $_POST["KOKYAKU"], PDO::PARAM_STR);
                $stmt->bindValue(7,  $row["ZEIKBN"], PDO::PARAM_INT);
                $stmt->bindValue(8,  rot13encrypt("割引・割増:".(($row["zei_per"]-1)*100)."%分"), PDO::PARAM_STR);
                $stmt->bindValue(9,  0, PDO::PARAM_INT);
                $stmt->bindValue(10, 0, PDO::PARAM_INT);
                $stmt->bindValue(11, 0, PDO::PARAM_INT);
                $stmt->bindValue(12, $chousei_hon, PDO::PARAM_INT);
                $stmt->bindValue(13, $chousei_zei, PDO::PARAM_INT);
                $stmt->bindValue(14, $row["ZEIKBN"], PDO::PARAM_INT);
                $flg=$stmt->execute();
                
                if($flg){
                }else{
                    $E_Flg=1;
                    break;
                }
            }
            if($goukei!=$chouseigaku){
                //端数あり
                $sqlstr = "insert into UriageData(uid,UriageNO,UriDate,insDatetime,Event,TokuisakiNM,ShouhinCD,ShouhinNM,su,Utisu,tanka,UriageKin,zei,zeiKBN,updDatetime) values(?,?,?,?,?,?,?,?,?,?,?,?,?,?,0)";
                $stmt = $pdo_h->prepare($sqlstr);
        
                $stmt->bindValue(1,  $_SESSION['user_id'], PDO::PARAM_INT);
                $stmt->bindValue(2,  $UriageNO, PDO::PARAM_INT);
                $stmt->bindValue(3,  date("Y/m/d"), PDO::PARAM_STR);
                $stmt->bindValue(4,  date("Y/m/d H:i:s"), PDO::PARAM_STR);
                $stmt->bindValue(5,  $_POST["EV"], PDO::PARAM_INT);
                $stmt->bindValue(6,  $_POST["KOKYAKU"], PDO::PARAM_STR);
                $stmt->bindValue(7,  9999, PDO::PARAM_INT);
                $stmt->bindValue(8,  rot13encrypt("割引・割増:端数"), PDO::PARAM_STR);
                $stmt->bindValue(9,  0, PDO::PARAM_INT);
                $stmt->bindValue(10, 0, PDO::PARAM_INT);
                $stmt->bindValue(11, 0, PDO::PARAM_INT);
                $stmt->bindValue(12, $chouseigaku-$goukei, PDO::PARAM_INT);
                $stmt->bindValue(13, 0, PDO::PARAM_INT);
                $stmt->bindValue(14, 0, PDO::PARAM_INT);//非課税
                $flg=$stmt->execute();
                
            }
            //exit();
        }
        
        if(E_Flg==0){
            $pdo_h->commit();    
        }else{
            //1件でも失敗したらロールバック
            $pdo_h->rollBack();
            echo "登録が失敗しました。<br>";
            echo "<a href='UriageData.php?csrf_token=".$token."'>レジ画面</a>より再度登録してみて下さい。<br>何度も失敗するようでしたら制作者へご連絡ください。";
            exit();
        }
        
    }catch (Exception $e) {
        $pdo_h->rollBack();
        echo "登録が失敗しました。<br>" . $e->getMessage()."<br>";
        echo "<a href='UriageData.php?csrf_token=".$token."'>レジ画面</a>より再度登録してみて下さい。<br>再三失敗するようでしたら制作者へご連絡ください。<br>";
        echo "UriNO::".$UriageNO."<br>uid::".$_SESSION['user_id'];
        $emsg = "レジ登録でERRORが発生しました。：".$e->getMessage();
        send_mail("green.green.midori@gmail.com","EVregi_sql.phpでシステム停止",$emsg);
        exit();
    }
        

}

$stmt = null;
$pdo_h = null;

header("HTTP/1.1 301 Moved Permanently");
header("Location: EVregi.php?mode=".$_POST["mode"]."&csrf_token=".$token);
exit();

?>



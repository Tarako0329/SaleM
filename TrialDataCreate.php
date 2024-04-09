<?php
//トライアルモード用のデータ作成
require "php_header.php";
 
//ユーザデータの登録
$kigen=date('Y-m-d', strtotime(date("Y-m-d") . "+1 day"));
$sqlstr="insert into Users(uid,mail,password,question,answer) values(0,?,?,?,?)";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, 'hoge', PDO::PARAM_STR);
$stmt->bindValue(2, '', PDO::PARAM_STR);
$stmt->bindValue(3, 'hoge', PDO::PARAM_STR);
$stmt->bindValue(4, 'hoge', PDO::PARAM_STR);
$flg=$stmt->execute();

$sqlstr="insert into Users_webrez(uid,loginrez,insdate,yuukoukigen,introducer_id,name,yagou,yubin,address1,address2,ForcedLogout,invoice_no,inquiry_tel,inquiry_mail,address3) values(0,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, 'hoge', PDO::PARAM_STR);
$stmt->bindValue(5, null, PDO::PARAM_STR);
$stmt->bindValue(6, date("Y-m-d"), PDO::PARAM_STR);
$stmt->bindValue(7, $kigen, PDO::PARAM_STR);
$stmt->bindValue(8, '', PDO::PARAM_STR);
$stmt->bindValue(9, 'トライアル太郎', PDO::PARAM_STR);
$stmt->bindValue(10, 'おためしパン屋さん', PDO::PARAM_STR);
$stmt->bindValue(11, '1231234', PDO::PARAM_STR);
$stmt->bindValue(12, '都道府県', PDO::PARAM_STR);
$stmt->bindValue(13, '市区町村', PDO::PARAM_STR);
$stmt->bindValue(14, 0, PDO::PARAM_INT);
$stmt->bindValue(15, 'T1234567890123', PDO::PARAM_STR);
$stmt->bindValue(16, '000-0000-0000', PDO::PARAM_STR);
$stmt->bindValue(17, 'webrez@greeen-sys.com', PDO::PARAM_STR);
$stmt->bindValue(18, '１－１０－７', PDO::PARAM_STR);
$flg=$stmt->execute();

if($flg){
    $stmt2 = $pdo_h->prepare("select max(uid) as uid from Users");
    $stmt2->execute();
    $tmp = $stmt2->fetchAll(PDO::FETCH_ASSOC);
    $_SESSION["user_id"]=$tmp[0]["uid"];
}else{
    echo "登録が失敗しました。";
    exit();
}

//商品マスタの複写
$sqlstr="insert into ShouhinMS select ?, `shouhinCD`, `shouhinNM`, `tanka`, `tanka_zei`, `zeiritu`, `zeiKBN`, `utisu`, `tani`, `genka_tanka`, `bunrui1`, `bunrui2`, `bunrui3`, `hyoujiKBN1`, `hyoujiKBN2`, `hyoujiKBN3`, `hyoujiNO` from ShouhinMS where uid=2";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
$flg=$stmt->execute();

//売上実績の複写
$sqlstr="insert into UriageData select ?, `UriageNO`, concat('".date('Y')."',RIGHT(`UriDate`,6)), `insDatetime`, `Event`, `TokuisakiNM`, `ShouhinCD`, `ShouhinNM`, `su`, `Utisu`, `tanka`, `UriageKin`, `zei`, `zeiKBN`, `genka_tanka`, `updDatetime` from UriageData where uid=2";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
$flg=$stmt->execute();



header("HTTP/1.1 301 Moved Permanently");
header("Location: menu.php");
exit();
?>
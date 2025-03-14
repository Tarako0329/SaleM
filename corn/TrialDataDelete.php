<?php
chdir(__DIR__);
echo date("Y-m-d")." トライアルデータの削除を処理開始します\n";

date_default_timezone_set('Asia/Tokyo');
require "../vendor/autoload.php";
require "../functions.php";


//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);

try{
    $count=0;
    //該当ユーザの抽出
    $pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());
    //$sqlstr="select * from Users where Yuukoukigen < ?";
    $sqlstr="select * from Users_webrez where Yuukoukigen < ?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, date("Y-m-d"), PDO::PARAM_STR);
    $stmt->execute();

    foreach($stmt as $row){
        
        //ユーザマスタの削除
        $sqlstr="delete from Users where uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $row["uid"], PDO::PARAM_INT);
        $stmt->execute();
        
        //ユーザマスタの削除
        $sqlstr="delete from Users_webrez where uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $row["uid"], PDO::PARAM_INT);
        $stmt->execute();
        
        //商品マスタの削除
        $sqlstr="delete from ShouhinMS where uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $row["uid"], PDO::PARAM_INT);
        $stmt->execute();
        
        //売上実績の削除
        $sqlstr="delete from UriageData where uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $row["uid"], PDO::PARAM_INT);
        $stmt->execute();

        $count++;
    }
}catch (Exception $e) {
    echo $e->getMessage(), "\n";
    echo "トライアルデータの削除処理が異常終了しました\n";
    exit();
}

echo "トライアルデータの削除(".$count." 件処理)を処理終了します\n";
?>
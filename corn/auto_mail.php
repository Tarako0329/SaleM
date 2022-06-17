<?php
chdir(__DIR__);
echo "処理を開始します。\n";

date_default_timezone_set('Asia/Tokyo');
require "../vendor/autoload.php";
require "../functions.php";


//.envの取得
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();
//var_dump($_ENV);
define("DNS","mysql:host=".$_ENV["SV"].";dbname=".$_ENV["DBNAME"].";charset=utf8");
define("USER_NAME", $_ENV["DBUSER"]);
define("PASSWORD", $_ENV["PASS"]);

define("HOST", $_ENV["HOST"]);
define("PORT", $_ENV["PORT"]);
define("FROM", $_ENV["FROM"]);
define("PROTOCOL", $_ENV["PROTOCOL"]);
define("POP_HOST", $_ENV["POP_HOST"]);
define("POP_USER", $_ENV["POP_USER"]);
define("POP_PASS", $_ENV["POP_PASS"]);

$daybefor=[10,5,2,1];

foreach($daybefor as $day){
    echo $day." 日前の処理\n";
    try{
        // DBとの接続
        $pdo_h = new PDO(DNS, USER_NAME, PASSWORD, get_pdo_options());

        //有効期限X日前のお知らせ
        $sqlstr="select * from Users where DATE_SUB(yuukoukigen,INTERVAL ".$day." DAY)=DATE(NOW())";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->execute();
    }catch (PDOException $e){
        echo('Error:'.$e->getMessage()."\n");
        die();
    }
    
    
    foreach($stmt as $row){
        echo "対象あり：".$row["mail"]."\n";
        $to = $row["mail"];
        $subject = "WEBREZ+ 無料トライアル終了１０日前のお知らせ";
    
        $body = <<< "EOM"
            （WEBREZ+より自動送信しております。）
            
            WEBREZ+（ウェブレジプラス）にご興味をもっていただきありがとうございます。
            
            無料トライアル期間の終了まであと$day 日となります。
            
            使い勝手はいかがでしょうか？
            
            このままお使い頂けるようでしたら「WEBREZ+」のトップメニューより「本契約」をお願い致します。
            
            質問等ありましたら、このメールにご返信ください。
            
            今後ともよろしくお願いします。
            
            EOM;
        if(FROM==""){
            //.env にメールアカウント情報が設定されてない場合、phpのsendmailで送付
            define("FROM", "information@WEBREZ.jp");
            $okflg=touroku_mail($to,$subject,$body);
        }else{
            //qdmailでメール送付
            $okflg = send_mail($to,$subject,$body);
        }
    }
}

$stmt=null;
$pdo_h=null;

echo "処理が終了しました。\n";

?>





















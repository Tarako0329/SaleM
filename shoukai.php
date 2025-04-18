<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);
$token = csrf_create();
$msg="";
$sqllog="";
try{
    
    //$sqlstr="select * from Users where uid=?";
    $flg="";
    if(!empty($_POST)){
        $sqlstr="select A.*,B.introducer_id from Users A inner join Users_webrez B on A.uid=B.uid where A.uid=?";
        $ShoukaishaCD=sort_hash(secho($_POST["SHOUKAI"]),"dec");
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $ShoukaishaCD, PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $rowcnt = $stmt->rowCount();
        if($rowcnt==1){
			$pdo_h->beginTransaction();
			$sqllog .= rtn_sqllog("START TRANSACTION",[]);

            //$sqlstr2="update Users set introducer_id=? where uid=? and introducer_id is null";
            $sqlstr2="update Users_webrez set introducer_id=? where uid=? and introducer_id is null";
            $stmt = $pdo_h->prepare($sqlstr2);
            $stmt->bindValue(1, $ShoukaishaCD, PDO::PARAM_INT);
            $stmt->bindValue(2, $_SESSION["user_id"], PDO::PARAM_INT);

            $sqllog .= rtn_sqllog($sqlstr,[$ShoukaishaCD,$_SESSION["user_id"]]);
            $stmt->execute();
            $sqllog .= rtn_sqllog("--execute():正常終了",[]);

            $rowcnt2 = $stmt->rowCount();
            if($rowcnt2==1){
    			$pdo_h->commit();
	    		$sqllog .= rtn_sqllog("commit",[]);
		    	sqllogger($sqllog,0);
                $msg="紹介者CDを登録いたしました。";
                $flg="success";
            }else{
                $pdo_h->rollBack();
                $sqllog .= rtn_sqllog("rollBack",[]);
                sqllogger($sqllog,null);
                $msg="別の紹介者CDが登録されてます。";
                $flg="failed";
            }
        }else{
            $msg="紹介者CDが誤ってます。";
            $flg="failed";
        }
    }
    
    /*
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $ShoukaiCD=sort_hash($row[0]["uid"],"enc");
    $ShoukaishaCD="";
    if($row[0]["introducer_id"]<>""){
        $ShoukaishaCD=sort_hash($row[0]["introducer_id"],"enc");
    }
    */
}catch(Exception $e){
    $pdo_h->rollBack();
    $sqllog .= rtn_sqllog("rollBack",[]);
    sqllogger($sqllog,$e);

    $msg="紹介者CDの登録に失敗しました。";
    $flg="failed";

}

$sqlstr="select A.*,B.introducer_id from Users A inner join Users_webrez B on A.uid=B.uid where A.uid=?";
$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ShoukaiCD=sort_hash($row[0]["uid"],"enc");
$ShoukaishaCD="";
if($row[0]["introducer_id"]<>""){
    //$ShoukaishaCD=sort_hash($row[0]["introducer_id"],"enc");
    $sqlstr="select A.*,B.introducer_id,B.yagou,B.name from Users A inner join Users_webrez B on A.uid=B.uid where A.uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $row[0]["introducer_id"], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ShoukaishaCD = empty($row[0]["yagou"])?$row[0]["name"]:$row[0]["yagou"];
}


?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.php" ;
    ?>
   	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.js"></script><!--make QRコードライブラリ-->
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_menu.css?<?php echo $time; ?>" >
    <TITLE><?php echo $title;?></TITLE>
	<style>
		#qrOutput {
		flex-wrap: wrap;
		align-items: center;
		justify-content: space-around;
		padding: 20px;
		}
	</style>
</head>
<body class='common_body'>
    <header class="header-color common_header">
        <div class="yagou title"><a href="menu.php"><?php echo $title;?></a></div></a></div>
    </header>
    <?php
        if($flg=="success"){
            echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-s' class='lead'></div></div></div></div>";
        }elseif($flg=="failed"){
            echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-e' class='lead'></div></div></div></div>";
        }
    
        $url=ROOT_URL."pre_account.php?shoukai=".$ShoukaiCD;
    ?>
    <div class="container" style="padding-top:15px;">
        <div class='row mb-5'>
            <p>WEBREZ+を紹介していただくと、紹介者・登録者の双方にAMAZONギフト券500円分をプレゼントします。</p>
            <p>紹介された方がWEBREZ＋の初回支払を終えた時点で、プレゼント対象となります。</p>
            <p>AMAZONギフト券はご登録されているE-MAILアドレスにお届けします。</p>
        </div>
        <div class='row mb-3'>
            <!--LINE-->
            <a href='https://line.me/R/share?text=<?php echo urlencode("こちらからWEBREZ+（ウェブレジ＋）の仮登録を行い、有料会員の本登録まで行うとAMAZONギフト500円分をプレゼント！\n".$url."\n\n初回支払完了後に配布されます。")?>'><i class="fa-brands fa-line fa-3x line-green me-3"></i>Lineで紹介する</a>
        </div>
        <div class='row mb-3'>
            <!--FACEBOOK-->
            <a href='http://www.facebook.com/share.php?u=<?php echo $url; ?>' ><i class="fa-brands fa-facebook-square fa-3x facebook-blue me-3"></i>FaceBookでシェアする</a>
        </div>
        <div class='row mb-3'>
            <!--E-Mail-->
            <a href='mailto:@?subject=WEBREZ+（ウェブレジ＋）のご紹介&body=こちらからWEBREZ+（ウェブレジ＋）の仮登録を行い、有料会員の本登録まで行うとAMAZONギフト500円分をプレゼント！%0D%0A<?php echo $url; ?>' ><i class="fa-regular fa-envelope fa-3x me-3"></i>メールで紹介する</a>
        </div>
        <!--TWITTER
        <a href="http://twitter.com/share?text=【ツイート文（日本語が含まれる場合にはURLエンコードが必要）】&url=<?php echo $url; ?>&hashtags=#レジアプリ" rel="nofollow"><i class="fa-brands fa-twitter-square fa-2x twitter-blue"></i></a>
        -->
        <br>

        紹介用ＱＲコードから登録
        <div id="qrOutput">
			<canvas id="qr"></canvas>
		</div>



        <h3>紹介用CD：</h3>
        <p><?php echo $ShoukaiCD; ?></p>
        <hr>
        <h3>紹介者CDの登録：</h3>
        <form method='post' action='shoukai.php'>
            あなたにWEBREZ+を紹介して下さった方の紹介者CDを登録して下さい。
            <input type='text' class='form-control' required="required" style='width:200px;font-size:1.8rem;margin-top:5px;margin-bottom:5px;padding:0;' name='SHOUKAI' <?php if($ShoukaishaCD<>""){echo "value=".$ShoukaishaCD." readonly='readonly'";} ?>>
            <input type='submit' class='btn-primary' style='width:100px;font-size:1.8rem' value= <?php if($ShoukaishaCD<>""){echo "'登録済' disabled";}else{echo "'登　録'";}?>>
        </form>
        
    </div>
    <script>
        /*window.onload = function() {
            //アラート用
            function alert(msg) {
              return $('<div class="alert" role="alert"></div>')
                .text(msg);
            }
            (function($){
              const s = alert('<?php //echo $msg; ?> ').addClass('alert-success');
              const e = alert('<?php //echo $msg; ?> ').addClass('alert-danger');
              // アラートを表示する
              $('#alert-s').append(s);
              // 5秒後にアラートを消す
              setTimeout(() => {
                s.alert('close');
              }, 5000);
              $('#alert-e').append(e);
            })(jQuery);
            console_log("<?php echo $url; ?>","lv3")
        }*/
        const QRout = () =>{
			// 入力された文字列を取得
			let userInput = '<?php echo $url; ?>'
			console_log(userInput)
			var query = userInput.split(' ').join('+');
			// QRコードの生成
			(function() {
				var qr = new QRious({
					element: document.getElementById('qr'), 
					// 入力した文字列でQRコード生成
					value: query
				});
				qr.background = '#FFF'; //背景色
				qr.backgroundAlpha = 1; // 背景の透過率
				qr.foreground = '#1c1c1c'; //QRコード自体の色
				qr.foregroundAlpha = 1.0; //QRコード自体の透過率
				qr.level = 'L'; // QRコードの誤り訂正レベル
				qr.size = 240; // QRコードのサイズ
				// QRコードをflexboxで表示
				document.getElementById('qrOutput').style.display = 'flex';
			})();
			// png出力用コード
			var cvs = document.getElementById("qr");
		}
        QRout()
    </script>
    
</body>

</html>
<?php
    $pdo_h=null;
?>
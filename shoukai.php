<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);
$token = csrf_create();

$sqlstr="select * from Users where uid=?";
$flg="";
if(!empty($_POST)){
    $ShoukaishaCD=rot13decrypt2(secho($_POST["SHOUKAI"]))-10000;
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $ShoukaishaCD, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $rowcnt = $stmt->rowCount();
    if($rowcnt==1){
        $sqlstr2="update Users set introducer_id=? where uid=? and introducer_id is null";
        $stmt = $pdo_h->prepare($sqlstr2);
        $stmt->bindValue(1, $ShoukaishaCD, PDO::PARAM_INT);
        $stmt->bindValue(2, $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $rowcnt2 = $stmt->rowCount();
        if($rowcnt2==1){
            $msg="登録者CDを登録いたしました。";
            $flg="success";
        }else{
            $msg="別の登録者CDが登録されてます。";
            $flg="failed";
        }
    }else{
        $msg="登録者CDが誤ってます。";
        $flg="failed";
    }
}


$stmt = $pdo_h->prepare($sqlstr);
$stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

$ShoukaiCD=rot13encrypt2($row[0]["uid"]+10000);
$ShoukaishaCD="";
if($row[0]["introducer_id"]<>""){
    $ShoukaishaCD=rot13encrypt2($row[0]["introducer_id"]+10000);
}


?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_menu.css?<?php echo $time; ?>" >
    <TITLE><?php echo $title;?></TITLE>
</head>

<script>
window.onload = function() {
    //アラート用
    function alert(msg) {
      return $('<div class="alert" role="alert"></div>')
        .text(msg);
    }
    (function($){
      const s = alert('<?php echo $msg; ?> ').addClass('alert-success');
      const e = alert('<?php echo $msg; ?> ').addClass('alert-danger');
      // アラートを表示する
      $('#alert-s').append(s);
      // 5秒後にアラートを消す
      setTimeout(() => {
        s.alert('close');
      }, 5000);
      $('#alert-e').append(e);
      /* アラートを消さない
      setTimeout(() => {
        e.alert('close');
      }, 5000);
      */
    })(jQuery);
}  
</script>

<header class="header-color">
    <div class="yagou title"><a href="menu.php"><?php echo $title;?></a></div></a></div>
</header>

<body>
    <?php
    if($flg=="success"){
        echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-s' class='lead'></div></div></div></div>";
    }elseif($flg=="failed"){
        echo "<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;' id='alert-e' class='lead'></div></div></div></div>";
    }
    $url="https://green-island.mixh.jp/SaleM/".MODE_DIR."/pre_account.php?shoukai=".$ShoukaiCD;
    ?>
    <div class="container" style="padding-top:15px;">
        WEBREZ+を紹介していただくと、紹介者・登録者の双方にAMAZONギフト券500円分をプレゼントします。<br>
        <br>
        紹介された方がWEBREZ＋の初回支払を終えた時点で、プレゼント対象となります。<br>
        AMAZONギフト券はご登録されているE-MAILアドレスにお届けします。<br>
        <br>
        <!--LINE-->
        <a href='https://line.me/R/share?text=<?php echo urlencode("こちらからWEBREZ+（ウェブレジ＋）の仮登録を行い、有料会員の本登録まで行うとAMAZONギフト500円分をプレゼント！\n".$url."\n\n初回支払完了後に配布されます。")?>'><i class="fa-brands fa-line fa-3x line-green"></i></a>
        <!--FACEBOOK
        <a href='http://www.facebook.com/share.php?u=<?php echo $url; ?>' ><i class="fa-brands fa-facebook-square fa-2x facebook-blue"></i></a>
        -->
        <!--TWITTER
        <a href="http://twitter.com/share?text=【ツイート文（日本語が含まれる場合にはURLエンコードが必要）】&url=<?php echo $url; ?>&hashtags=#レジアプリ" rel="nofollow"><i class="fa-brands fa-twitter-square fa-2x twitter-blue"></i></a>
        -->
        <br>
        紹介CD付URLで紹介する。
        <br>
        <br>
        <h3>紹介用URL：</h3>
        <p><?php echo $url; ?></p>
        <h3>紹介用CD：</h3>
        <p><?php echo $ShoukaiCD; ?></p>
        <hr>
        <h3>紹介者CDの登録：</h3>
        <form method='post' action='shoukai.php'>
            あなたにWEBREZ+を紹介して下さった方の紹介者CDを登録して下さい。
            <input type='text' class='form-control' required="required" style='width:13rem;font-size:1.8rem;margin-top:5px;margin-bottom:5px;padding:0;' name='SHOUKAI' <?php if($ShoukaishaCD<>""){echo "value=".$ShoukaishaCD." readonly='readonly'";} ?>>
            <input type='submit' class='btn-primary' style='width:100px;font-size:1.8rem' value= <?php if($ShoukaishaCD<>""){echo "'登録済' disabled";}else{echo "'登　録'";}?>>
        </form>
        
    </div>
    
</body>

</html>
<?php
    $pdo_h=null;
?>
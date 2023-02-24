<?php
//ユーザ登録、登録情報の修正画面
require "php_header.php";

$shoukai="";
$choufuku_flg=0;
$okflg=0;
if(!empty($_GET["shoukai"])){
    $shoukai=$_GET["shoukai"];
}elseif(!empty($_POST["shoukai"])){
    $shoukai=$_POST["shoukai"];
}

if(filter_input(INPUT_POST,"BTN") == "send"){
    //登録用メール送信
    $to = $_POST["MAIL"];
    $subject = "WEBREZ登録のご案内";
    $mail2=rot13encrypt2($to);
    $url=ROOT_URL."account_create.php?mode=0&acc=".$mail2."&shoukai=".$shoukai;
    $body = <<< "EOM"
        WEBREZ+（ウェブレジプラス）にご興味をもっていただきありがとうございます。
        こちらのURLから登録をお願いいたします。
        
        $url
        EOM;
    if(FROM==""){
        //.env にメールアカウント情報が設定されてない場合、phpのsendmailで送付
        define("FROM", "information@WEBREZ.jp");
        $okflg = touroku_mail($to,$subject,$body);
    }else{
        //qdmailでメール送付
        $okflg = send_mail($to,$subject,$body);
    }
}else{
    //echo "登録が失敗しました。";
}
$stmt=null;
$pdo_h=null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel="stylesheet" href="css/style_account_create.css?<?php echo $time; ?>" >
    <TITLE><?php echo secho($title)." ユーザー登録";?></TITLE>
</head>

<body class='common_body'>
    <header class="header-color common_header" style="flex-wrap:wrap">
        <div class="title" style="width: 100%;"><a href="<?php echo "index.php";?>"><?php echo secho($title);?></a></div>
        <p style="font-size:1rem;color:var(--user-disp-color);">  ユーザー登録</p>
    </header>
    <main id='form1'>
    <form method="post" action="pre_account.php" style="font-size:1.5rem">
        <div class="container" style="padding-top:15px;">
            <input type="hidden" name="shoukai" value=<?php echo $shoukai; ?>>
            <template v-if='MSG!==""'>
		    	<div :class='alert_status' role='alert'>{{MSG}}</div>
		    </template>
            <?php
                if($okflg===0){
                }elseif($okflg===true){
                    echo $_POST['MAIL']." へ登録用のURLを記載したメールを送信いたしました。<br>メールが届いてない場合、メールアドレスが間違えているか、迷惑メールになっている可能性があります。<br>送信元アドレスは ".FROM. "となります。<br>";
                }else{
                    echo $_POST['MAIL']." への登録用メール送信が失敗しました。<br>";
                }
                if($shoukai<>""){
                    echo "<br>紹介者CD付き登録画面<br>紹介者CD：".$shoukai."<br>";
                }
            ?>
            <div v-if='alert_status[1]==="alert-warning"' style='border:1px solid;padding:3px;'>
                <p><a href='index.php'>TOP画面</a>に戻ってログインして下さい。</p>
                <p>パスワードを忘れた場合は<a href='forget_pass_sendurl.php'>コチラ</a>からパスワードの再設定をお願いします。</p>
            </div>
            <div>
                <label for="mail" class="form-label">登録用URLの送信先を指定してください。</label>
                <input v-model='email' type="email" maxlength="40" class="form-control" id="mail" name="MAIL" required="required" >
            </div>
            <div>
                <button type="submit" class="btn btn-primary" style="font-size:1.5rem" name="BTN" value="send">送 信</button>
            </div>
            
        </div>
    </form>
    </main>
	<script>
		const { createApp, ref, onMounted, computed, VueCookies, watch } = Vue
		createApp({
			setup(){
                const email = ref('')
                const MSG = ref('')
                const alert_status = ref(['alert'])
				const email_chk = () => {
					console_log('email_chk start','lv3')

					axios
						.get(`ajax_chk_email.php?MAIL=${email.value}`) 
						.then(async(response) => {
							console_log(`email_chk SUCCESS`,'lv3')
							console_log(response.data,'lv3')
							if(response.data.timeout===true){
								await alert(response.data.MSG)
								if(confirm('ログイン画面に戻りますか？')===true){
									window.location.href = 'index.php';
								}
							}
							MSG.value = response.data.MSG
							alert_status.value[1] = response.data.status
						})
						.catch((error) => {
							console_log(`email_chk ERROR:${error}`,'lv3')
							MSG.value = 'axios 通信エラー'
							alert_status.value[1]='alert-danger'
						})
						.finally(()=>{
						})
						
				}
                watch(email,()=>{
                    email_chk()
                })

                return{
                    email,
                    alert_status,
                    MSG,
                }
			}
		}).mount('#form1');
    </script>
</body>

</html>

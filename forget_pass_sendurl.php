<!DOCTYPE html>
<html lang="ja">
<?php

require "php_header.php";

$okflg="";
$undefined_flg=0;

//if($_POST["BTN"] == "send"){
if(filter_input(INPUT_POST,"BTN") == "send"){
    //メールアドレスが登録されている事を確認

    $sqlstr="select count(*) as kensu from Users where mail=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_POST["MAIL"], PDO::PARAM_STR);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $col1 = $row[0]["kensu"];

    if($col1==0){
        //登録なし
        $undefined_flg=1;
    }else{
        //パスワード再設定メール送信
        $to = (!empty($_POST["NEW_MAIL"])?$_POST["NEW_MAIL"]:$_POST["MAIL"]);
        
        $subject = "WEBREZパスワード再設定";
        $mail2=rot13encrypt2($_POST["MAIL"]);
        $url=ROOT_URL."forget_pass.php?acc=".$mail2;
        $body = <<< "EOM"
            こちらのURLから情報を更新して下さい。
            
            $url
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

?>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel='stylesheet' href='css/style_account_create.css?<?php echo $time; ?>' >
    <TITLE><?php echo secho($title).' ユーザー登録';?></TITLE>
</head>
<header class='header-color common_header' style='flex-wrap:wrap'>
    <div class='title' style='width: 100%;'><a href='<?php echo "index.php";?>'><?php echo secho($title);?></a></div>
    <p style='font-size:1rem;'>  ユーザー登録</p>
</header>

<body class='common_body'>
    <div class='container' style='padding-top:15px;'>
    <div class='col-12 col-md-8' style='font-size:1.5rem;font-weight:800;'>
    <?php
    if($undefined_flg==1){
        //メールアドレスの検索結果が１件以上の場合
        echo "このメールアドレスは登録されてません。入力に間違いがないか、ご確認ください。<br>";
    }elseif($okflg==1){
        echo $to." へ登録用のURLを記載したメールを送信いたしました。メールが届いてない場合、メールアドレスが間違えているか、迷惑メールになっている可能性があります。<br>送信元アドレスは ".FROM. "となります。";
    }
    ?>
    </div>
    <br>
    <div class='col-12 col-md-8'>
    <form method='post' action='forget_pass_sendurl.php' style='font-size:1.5rem' id='form1'>
        
        <div class='form-group'>
            <label for='mail' >WEBREZに登録したメールアドレスを入力し、送信ボタンを押してください。<br>入力されたメールアドレスにパスワード更新用のURLが送信されます。</label>
            <input type='email' v-model='email' maxlength='40' class='form-control' id='mail' name='MAIL' required='required' placeholder='必須' >
            <small v-if='MSG===false' id='mail' class='form-text ' style='color:red;'>このメールアドレスは未登録です。</small>
            <small v-if='MSG===true' id='mail' class='form-text ' style='color:green;'>Good!!</small>
            <br><br>
            <template v-if='MSG===false'>
                <label for='mail' >メールアドレスを変更した場合、こちらに現在のメールアドレスを入力して下さい。</label>
                <input type='email' maxlength='40' class='form-control' id='new_mail' required='required' name='NEW_MAIL' >
            </template>
        </div>
        <div class='col-2 col-md-1' style=' padding:0; margin-top:10px;'>
            <button type='submit' class='btn btn-primary' style='width:200px;height:50px;font-size:1.8rem' name='BTN' value='send'>送 信</button>
        </div>
        <br>
    </form>
    </div>
    </div>
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
                            if(response.data.MSG==='Registered'){
                                MSG.value = true
                            }else if(response.data.MSG==='unRegistered'){
                                MSG.value = false
                            }else{}
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
<?php
    $stmt=null;
    $pdo_h=null;
?>





















<?php
//ユーザ登録、登録情報の修正画面
require "php_header.php";
define("GOOGLE_AUTH",$_ENV["GOOGLE_AUTH"]);
$token = csrf_create();
$shoukai="";
if(!empty($_GET["shoukai"])){
	$shoukai=$_GET["shoukai"];
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php" 
	?>
	<script src="https://accounts.google.com/gsi/client" ></script><!--google login api-->
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
		<div class="container" style="padding-top:15px;">
			<input type="hidden" name="shoukai" value=<?php echo $shoukai; ?>>
			<template v-if='MSG!==""'>
				<div :class='alert_status' role='alert'>{{MSG}}</div>
			</template>
			<div v-if='shoukai!==""'>紹介者CD付き登録画面<br>紹介者CD：{{shoukai}}</div>
			<div v-if='alert_status[1]==="alert-warning"' style='border:1px solid;padding:3px;'>
				<p><a href='index.php'>TOP画面</a>に戻ってログインして下さい。</p>
				<p>パスワードを忘れた場合は<a href='forget_pass_sendurl.php'>コチラ</a>からパスワードの再設定をお願いします。</p>
			</div>
			<div class='mt-3 mb-3'>
				<label for="mail" class="form-label">登録用URLの送信先を指定してください。</label>
				<input v-model='email' type="email" maxlength="40" class="form-control" id="mail" name="MAIL" required="required" >
				<small>
					<p class='mb-0'>{{from_address}} から登録用のURLが記載されたメールが届きます。</p>
					<p>受信できない場合、迷惑メールフィルタなどの設定をご確認ください。</p>
				</small>
			</div>
			<div>
				<button type="button" class="btn btn-primary" style="font-size:1.5rem" @click='send_mail()'>送 信</button>
			</div>
			<hr>
			<div>
				<p>Google ID で登録する方</p>
				<div class="g_id_signin " style='width:268px;margin:auto;'
					data-type="standard"
					data-size="large"
					data-theme="outline"
					data-text="signup_with"
					data-shape="rectangular"
					data-logo_alignment="left">
				</div>
				<div id="g_id_onload"
				data-client_id="<?php echo GOOGLE_AUTH;?>"
				data-callback="handleCredentialResponse"
				data-auto_prompt="false">
				</div>
			</div>
		</div>
		<form style='display:none;' id="form2" method="post" action="logincheck.php">
			<input type='hidden' name='login_type' value='google'>
			<input type='hidden' id='sub_id' name='sub_id'>
			<input type='hidden' id='token' name='token'>
			<input type='hidden' id='uid' name='uid'>
			<input type='hidden' id='AUTOLOGIN2' name='AUTOLOGIN'>
			<input type='submit' id='form2_submit'>
		</form>

	</main>
	<script>
		const { createApp, ref, onMounted, computed, VueCookies, watch } = Vue
		const pre_account = (p_token,p_shoukai) => createApp({
			setup(){
				const email = ref('')
				const MSG = ref('')
				const shoukai = ref(p_shoukai)
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
						//MSG.value = response.data.MSG
						//alert_status.value[1] = response.data.status
						if(response.data.MSG==='Registered'){
							MSG.value = 'メールアドレスは登録済みになります。'
							alert_status.value[1] = 'alert-warning'
						}else if(response.data.MSG==='unRegistered'){
							MSG.value = 'メールアドレスは登録可能です。'
							alert_status.value[1] = 'alert-success'
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
				
				const send_mail = () =>{
					axios
					.get(`ajax_pre_account_mail.php?MAIL=${email.value}&csrf_token=${p_token}&shoukai=${shoukai.value}`) 
					.then((response) => {
						console_log(response.data)
						if(response.data.status==="success"){
							MSG.value = `${email.value} へ登録用URLを送信しました。`
							alert_status.value[1] = "alert-success"
						}else{
							MSG.value = `${email.value} へ登録用URLの送信を失敗しました。`
							alert_status.value[1] = `alert-${response.data.status}`
						}
					})
					.catch((error)=>{
						console_log(error)
						MSG.value = `${email.value} へ登録用URLの送信を失敗しました。`
						alert_status.value[1] = `alert-${response.data.status}`
					})

				}
				const from_address = ref('<?php echo FROM;?>')
				return{
					email,
					alert_status,
					MSG,
					send_mail,
					shoukai,
					from_address,
				}
			}
		})
		pre_account('<?php echo $token."','".$shoukai;?>').mount('#form1')

		function handleCredentialResponse(response) {
  		// decodeJwtResponse() is a custom function defined by you
  		// to decode the credential response.
  		const responsePayload = decodeJwtResponse(response.credential);
		
  		console_log(responsePayload);
			let params = new URLSearchParams();
			params.append("sub_id",responsePayload.sub)
			params.append("name",responsePayload.given_name)
			params.append("mail",responsePayload.email)
			params.append("shoukai","<?php echo $shoukai;?>")
			params.append("csrf_token","<?php echo $token;?>")
			axios.post('ajax_account_subid.php',params)
			.then((respons)=>{
				console_log(respons.data)
				document.getElementById("sub_id").value = responsePayload.sub
				document.getElementById("token").value = respons.data.token
				document.getElementById("uid").value = respons.data.uid
				document.getElementById("AUTOLOGIN2").value = 'on'
				document.getElementById("form2_submit").click()
			})
			.catch((Error)=>{
				console_log(Error)
			})
			
  	}
		function decodeJwtResponse(token) {
			var base64Url = token.split(".")[1];
			var base64 = base64Url.replace(/-/g, "+").replace(/_/g, "/");
			var jsonPayload = decodeURIComponent(
			  atob(base64)
				.split("")
				.map(function (c) {
				  return "%" + ("00" + c.charCodeAt(0).toString(16)).slice(-2);
				})
				.join("")
			);
		
			return JSON.parse(jsonPayload);
		}
	</script>
</body>

</html>

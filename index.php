<?php
require "php_header.php";
define("GOOGLE_AUTH",$_ENV["GOOGLE_AUTH"]);
//log_writer2("index.php > \$_SESSION",$_SESSION,"lv3");
if(substr(EXEC_MODE,0,5)==="Trial" && !empty($_SERVER["REQUEST_URI"])){
	if(substr(EXEC_MODE,-1)==="L"){
		$_SESSION=[];
		setCookie("webrez_token", '', -1, "/", "", TRUE, TRUE); // secure, httponly
		setCookie("user_id", '', -1, "/", "", TRUE, TRUE); // secure, httponly
		//echo EXEC_MODE;
	}
	//echo substr(EXEC_MODE,-1);
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: menu.php");
	exit();
}

//自動ログイン情報の取得
$login_type = (!empty($_COOKIE["login_type"])?$_COOKIE["login_type"]:"normal");

if ($login_type==="auto") {
	header("HTTP/1.1 301 Moved Permanently");
	header("Location: logincheck.php");
}

$errmsg = "";
if(isset($_SESSION["EMSG"])){
	$errmsg="<div style='color:red'>".$_SESSION["EMSG"]."</div>";
	//一度エラーを表示したらクリアする
	$_SESSION["EMSG"]="";
}
$mail="";
if(!empty($_SESSION["MAIL"])){
	$mail=$_SESSION["MAIL"];
}elseif(EXEC_MODE==="Local"){
	$mail="green.green.midori@greeen-sys.com";
}
if($_COOKIE["user_type"]==="google"){
	$g_login="signin_with";
}else{
	$g_login="signup_with";
}
$csrf = csrf_create();
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
	<link rel="stylesheet" href="css/style_index.css?<?php echo $time; ?>" >
	<!--<script src="script/index.js"></script>-->
	<TITLE><?php echo secho($title)." ようこそ";?></TITLE>
</head>
<header  class="header-color common_header" style="flex-wrap:wrap">
	<div class="title" style="width: 100%;"><a href="index.php" ><?php echo secho($title);?></a></div>
	<div style="font-size:1rem;"> ようこそWEBREZへ</div>
</header>
<body class='common_body'>
	<div class="container">
		<div class="card card-container">
			<?php echo $errmsg; ?>
			<form class="form-signin" id="form1" method="post" action="logincheck.php">
				<span id="reauth-email" class="reauth-email"></span>
				<input type="email" id="inputEmail" class="form-control" placeholder="Email address" name="LOGIN_EMAIL" required autofocus value='<?php echo $mail;?>'>
				<input type="password" id="inputPassword" class="form-control" name="LOGIN_PASS" placeholder="Password" required value='<?php echo (EXEC_MODE=="Local"?"000000":""); ?>'>
				<div id="remember" class="checkbox">
					<label>
						<input type="checkbox" id='AUTOLOGIN' name="AUTOLOGIN" checked> AUTOLOGIN 
					</label>
				</div>

				<button class="btn btn-lg btn-primary btn-block btn-signin" type="submit">ロ グ イ ン</button>
				<?php if($g_login==="signin_with"){?>
					<div class="g_id_signin " style='width:268px;margin:auto;'
						data-type="standard"
						data-size="large"
						data-theme="outline"
						data-text="<?php echo $g_login;?>"
						data-shape="rectangular"
						data-logo_alignment="left">
					</div>
				<?php }?>
				<input type="hidden" name="csrf_token" value="<?php echo $csrf; ?>">
			</form><!-- /form -->
			<a href="forget_pass_sendurl.php" class="forgot-password">
				ﾊﾟｽﾜｰﾄﾞを忘れたらｸﾘｯｸ
			</a>
			<hr>
			<a href="pre_account.php" class="btn btn-lg btn-primary btn-block btn-signin mb-3" style="padding-top:8px" >新 規 登 録</a>
			<?php if($g_login==="signup_with"){?>
				<div class="g_id_signin " style='width:268px;margin:auto;'
					data-type="standard"
					data-size="large"
					data-theme="outline"
					data-text="<?php echo $g_login;?>"
					data-shape="rectangular"
					data-logo_alignment="left">
				</div>
			<?php }?>
			<div id="g_id_onload"
				data-client_id="<?php echo GOOGLE_AUTH;?>"
				data-callback="handleCredentialResponse"
				data-auto_prompt="false">
			</div>
			<form style='display:none;' id="form2" method="post" action="logincheck.php">
				<input type='hidden' name='login_type' value='google'>
				<input type='hidden' id='sub_id' name='sub_id'>
				<input type='hidden' id='token' name='token'>
				<input type='hidden' id='uid' name='uid'>
				<input type='hidden' id='AUTOLOGIN2' name='AUTOLOGIN'>
				<input type='submit' id='form2_submit'>
			</form>

		</div><!-- /card-container -->
	</div><!-- /container -->    
	<script>
		window.onload = function() {
			// Enterキーが押された時にSubmitされるのを抑制する
			document.getElementById("form1").onkeypress = (e) => {
				// form1に入力されたキーを取得
				const key = e.keyCode || e.charCode || 0;
				// 13はEnterキーのキーコード
				if (key == 13) {
					// アクションを行わない
					e.preventDefault();
				}
			}
		};
		function handleCredentialResponse(response) {
  		// decodeJwtResponse() is a custom function defined by you
  		// to decode the credential response.
  		const responsePayload = decodeJwtResponse(response.credential);
		
  		console_log(responsePayload);
			let params = new URLSearchParams();
			params.append("sub_id",responsePayload.sub)
			params.append("name",responsePayload.given_name)
			params.append("mail",responsePayload.email)
			params.append("csrf_token","<?php echo $csrf;?>")
			axios.post('ajax_account_subid.php',params)
			.then((respons)=>{
				console_log(respons.data)
				document.getElementById("sub_id").value = responsePayload.sub
				document.getElementById("token").value = respons.data.token
				document.getElementById("uid").value = respons.data.uid
				document.getElementById("AUTOLOGIN2").value = document.getElementById("AUTOLOGIN").value
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

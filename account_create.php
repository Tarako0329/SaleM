<?php
//ユーザ登録、登録情報の修正画面
//$mode 0:新規　1:更新
//acc:mailaddress
require "php_header.php";
$myname = basename(__FILE__);           //ログファイルに出力する自身のファイル名
//log_writer2($myname." \$row[NAME] =>",$row["NAME"],"lv3");
$shoukai=(!empty($_GET["shoukai"])?$_GET["shoukai"]:"");

if($_GET["mode"]==="0" && !empty($_GET["acc"])){
	$mode="insert";
	//同一端末の前回ログイン情報をクリアする
	delete_old_token($cookie_token, $pdo_h);

	//GETからメールアドレスを復元
	//$row[0]["mail"]=rot13decrypt2($_GET["acc"]);
	$new_mail=rot13decrypt2($_GET["acc"]);

}else if($_GET["mode"]==="1"){
	$rtn=csrf_checker(["menu.php","forget_pass.php"],["G","C","S"]);
	if($rtn!==true){
		redirect_to_login($rtn);
	}

	//更新モードの場合、session[usr_id]のチェック
	$rtn=check_session_userid($pdo_h);
	$mode="update";
}else{
	//モード指定なしはNG
	redirect_to_login("想定外のアクセスルートです。");
}
$token=csrf_create();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php" 
	?>
	<!--ページ専用CSS-->
	<link rel='stylesheet' href='css/style_account_create.css?<?php echo $time; ?>' >
	<TITLE><?php echo secho($title)." ユーザー登録";?></TITLE>
	<!--郵便場号から住所取得-->
	<script src='https://ajaxzip3.github.io/ajaxzip3.js' charset='UTF-8'></script>
</head>
<header class='header-color common_header' style='flex-wrap:wrap'>
	<div class='title' style='width: 100%;'><a href='<?php if($mode==="update"){echo "menu.php";}else{echo "index.php";}?>'><?php echo secho($title);?></a></div>
	<p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  ユーザー登録</p>
</header>

<body class='common_body'>
	<div id='accountform'>
		<div class='container' style='padding-top:15px;padding-bottom:50px;'>
			<div class='col-12 col-md-8'>
				<template v-if='MSG!==""'>
					<div v-bind:class='alert_status' role='alert' >{{MSG}}</div>
				</template>
				<form  id='form1' style='font-size:1.5rem' @submit.prevent='on_submit'>
					<input type='hidden' name='csrf_token' :value='csrf'>
					<input type='hidden' name='mode' :value='mode'>
					<input type='hidden' name='shoukai' value=<?php echo $shoukai; ?>>
					<input type='hidden' name='moto_mail' :value='moto_mail'>
					
					<label for='mail' >メールアドレス</label>
					<input v-model='account_u.mail' :='locker' type='email' maxlength='40' class='form-control' id='mail' name='MAIL' required='required' placeholder='必須' >
					<hr>
					<template v-if='mode==="update"'>
						<input type='checkbox' id='chk_pass' name='chk_pass' v-model='pass_change'>
						<label for='chk_pass' >パスワードを変更する</label><br>
					</template>
					<label for='pass'>パスワード(6桁以上)</label>
					<input type='password' minlength='6' class='form-control' id='pass' :='pass_lock'>
					<label for='pass2'>パスワード（確認）</label>
					<input type='password' minlength='6' class='form-control' id='pass2' oninput='Checkpass(this)' name='PASS' :='pass_lock'>
					<hr>
					<label for='question' >秘密の質問(パスワードを忘れたときに使用します)</label>
					<input v-model='account_u.question' :='locker' type='text' maxlength='20' class='form-control mb-3' id='question' name='QUESTION' required='required' placeholder='例：初恋の人の名前' >
					<label for='answer' >答え</label>
					<input v-model='account_u.answer' :='locker' type='text' maxlength='20' class='form-control' id='answer' name='ANSWER' required='required' placeholder='例：ささき' >
					<small id='answer' class='form-text text-muted'>ひらがな・半角英数・スペース不使用を推奨</small>
					<br>
					<template v-if='mode==="update"'>
						<input v-model='account_r.loginrez' :='locker' type='checkbox' class='form-check-input mb-3' id='loginrez' name='LOGINREZ' >
						<label class="form-check-label" for='loginrez' >ログイン後レジ画面表示</label><br>
						<label for='hasushori' >消費税の端数処理</label>
						<select v-model='account_r.ZeiHasu' :='locker' class="form-select form-select-lg" style='font-size:1.5rem' id='hasushori' name='ZEIHASU'>
							<option value=0>切り捨て</option>
							<option value=1>四捨五入</option>
							<option value=2>切り上げ</option>
						</select>

						<br>
						<hr>
						<div>ここから下は請求書・納品書・自動送信メールに使用します。<br>使用しない方は入力不要です。</div>
						<hr>
						<label for='name' >姓名</label>
						<input v-model='account_r.name' :='locker' type='text' class='form-control mb-3' id='name' name='NAME'  >
						<label for='yagou' >屋号</label>
						<input v-model='account_r.yagou' :='locker' type='text' class='form-control mb-3' id='yagou' name='YAGOU'  >
						<label for='invoice' >インボイス登録番号</label>
						<input v-model='account_r.invoice_no' :='locker' type='text' class='form-control mb-3' id='invoice' name='invoice'  >
						<label for='yubin' >郵便番号('-'抜き)</label>
						<input v-model='account_r.yubin' :='locker' type='text' class='form-control mb-3' id='yubin' name='zip11' onKeyUp='AjaxZip3.zip2addr(this,"","addr11","addr11");' >
						<label for='add1' >住所１行目</label>
						<input v-model='account_r.address1' :='locker' type='text' maxlength='20' class='form-control mb-3' id='add1' name='addr11' placeholder='住所1行目' >
						<label for='add2' >住所２行目</label>
						<input v-model='account_r.address2' :='locker' type='text' maxlength='20' class='form-control mb-3' id='add2' name='ADD2' placeholder='住所2行目' >
						<label for='add3' >住所３行目</label>
						<input v-model='account_r.address3' :='locker' type='text' maxlength='20' class='form-control mb-3' id='add3' name='ADD3' placeholder='住所3行目' >
						<label for='inquiry_tel' >問合せ先TEL</label>
						<input v-model='account_r.inquiry_tel' :='locker' type='text' pattern="[0-9]{3,}-[0-9]{3,}-[0-9]{3,}" maxlength='20' class='form-control mb-3' id='inquiry_tel' name='inquiry_tel' placeholder='例：000-0000-0000' >
						<label for='inquiry_mail' >問合せ先MAIL</label>
						<input v-model='account_r.inquiry_mail' :='locker' type='email' maxlength='300' class='form-control mb-3' id='inquiry_mail' name='inquiry_mail' placeholder='メールアドレス' >
					</template>
					<div class='col-12' style=' padding:5px; margin-top:10px;display:flexbox;'>
						<button v-if='step==="check"' type='submit' class='btn btn-primary' style='width:150px;height:40px;font-size:1.5rem'>確 認</button>
						<button v-if='step==="register"' type='button' @click='UpdateValue()' class='btn btn-primary' style='width:150px;height:40px;font-size:1.5rem'>登 録</button>
						<button v-if='step==="register"'type='button' @click='AllUnLock()' class='btn btn-primary' style='width:150px;height:40px;font-size:1.5rem;margin-left:10px;'>戻 る</button>
						<button v-if='step==="next"'type='button' onclick='location="index.php"' class='btn btn-primary' style='width:150px;height:40px;font-size:1.5rem;margin-left:10px;'>ログイン画面へ</button>
					</div>
				</form>
			</div>
		</div>
	</div><!--accountform-->
	<script>
		const { createApp, ref, onMounted, onBeforeMount, computed, VueCookies } = Vue;
		const account_create = (p_mode,p_token) => createApp({
			setup(){
				const mode = ref(p_mode)
				const alert_status = ref(['alert'])
				const MSG = ref('')
				const loader = ref(false)
				const csrf = ref(p_token) 
				const step = ref('check')

				const account_u = ref([])
				const account_r = ref([])
				const moto_mail = ref('')
				const locker = ref({'disabled':false})
				const pass_change = ref(false)
				const AllLock = () =>{
					console_log('OnPress')
					locker.value.disabled=true
				}
				const AllUnLock = () =>{
					console_log('OnPress')
					if(mode.value==="update"){
						step.value = 'check'
						locker.value.disabled=false
					}else if(mode.value==="insert"){
						//step.value="next"
					}
				}
				const on_submit = (e) =>{
					let form_data = new FormData(e.target)
					let params = new URLSearchParams (form_data)
					axios
						.post('ajax_account_Temporarily_saved.php',params) 
						.then((response) => {
							console_log(response.data)
							csrf.value = response.data.csrf_create
							if(response.data.status !== "alert-success"){
								MSG.value = response.data.MSG
								alert_status.value[1] = response.data.status
								console_log(`on_submit NOT SUCCESS`)
							}else{
								AllLock()
								step.value = 'register'
								console_log(`on_submit SUCCESS`)
							}
						})
						.catch((error) => {
							console_log(`on_submit ERROR:${error}`)
						})
						.finally(()=>{
						})
				}
				const UpdateValue = () =>{
					let params = new URLSearchParams ({'csrf_token':csrf.value,'mode':mode.value})
					axios
						.post('ajax_account_sql.php',params) 
						.then((response) => {
							console_log(response.data,"lv3")
							csrf.value = response.data.csrf_create
							if(response.data.status !== "alert-success"){//失敗
								alert(response.data.MSG)
								//MSG.value = response.data.MSG
								//alert_status.value[1] = response.data.status
							}else{
								alert(response.data.MSG)
								console_log(`UpdateValue SUCCESS`)
								if(mode.value==="insert"){
									step.value="next"
								}
							}
						})
						.catch((error) => {
							console_log(`UpdateValue ERROR:${error}`)
						})
						.finally(()=>{
							AllUnLock()
						})

				}
				const pass_lock = computed(() =>{
					if(mode.value==='insert'){
						return {'disabled':false}
					}else if(mode.value==='update'){
						if(pass_change.value){
							return {'disabled':false}
						}else{
							return  {'disabled':true}
						}
					}
        })

				onBeforeMount(()=>{
      		console_log("onBeforeMount")
					if(mode.value==='insert'){
						account_u.value.mail = '<?php echo $new_mail;?>'
					}else{
						GET_USER()
						.then((response)=>{
							console_log(response)
							account_u.value = response.Users[0]
							account_r.value = response.Users_webrez[0]
							account_r.value["loginrez"] = (account_r.value["loginrez"]==="on")?true:false
							moto_mail.value = response.Users[0].mail
							csrf.value = response.token
						})
					}
	    	})

				onMounted(() => {
					console_log('mounted')
					//console_log(account)
				})
				return{
					mode,
					//account,
					account_u,
					account_r,
					moto_mail,
					locker,
					pass_change,
					on_submit,
					AllUnLock,
					UpdateValue,
					step,
					csrf,
					pass_lock,
					MSG,
					alert_status,
				}
			}
		})
		account_create(<?php echo "'".$mode."','".$token."'";?>).mount('#accountform');
	</script><!--vue.js-->
</body>
<script>
	function Checkpass(input){
		//IE対応の為変更
		
		var pass = document.getElementById('pass').value; //メールフォームの値を取得
		var passConfirm = input.value; //メール確認用フォームの値を取得(引数input)
		
		// パスワードの一致確認
		if(pass != passConfirm){
			input.setCustomValidity('パスワードが一致しません'); // エラーメッセージのセット
		}else{
			input.setCustomValidity(''); // エラーメッセージのクリア
		}
	}
	
	// Enterキーが押された時にSubmitされるのを抑制する
	document.getElementById('form1').onkeypress = (e) => {
		// form1に入力されたキーを取得
		const key = e.keyCode || e.charCode || 0;
		// 13はEnterキーのキーコード
		if (key == 13) {
			// アクションを行わない
			//alert('test');
			e.preventDefault();
		}
	}
</script><!--js-->
</html>
<?php
$stmt=null;
$pdo_h=null;
?>






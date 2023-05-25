<?php
//ユーザ登録、登録情報の修正画面
//$mode 0:新規　1:更新
require "php_header.php";
$myname = basename(__FILE__);           //ログファイルに出力する自身のファイル名
//log_writer2($myname." \$row[NAME] =>",$row["NAME"],"lv3");
$shoukai=(!empty($_GET["shoukai"])?$_GET["shoukai"]:"");

if($_GET["mode"]==="0" && !empty($_GET["acc"])){
	$mode="insert";
	//同一端末の前回ログイン情報をクリアする
	//Cookie のトークンを削除
	setCookie("webrez_token", '', -1, "/", null, TRUE, TRUE); // secure, httponly
	//古くなったトークンを削除
	delete_old_token($cookie_token, $pdo_h);
	//セッション変数のクリア
	$_SESSION = array();

	//GETからメールアドレスを復元
	$row[0]["mail"]=rot13decrypt2($_GET["acc"]);
}else if($_GET["mode"]==="1"){
	$rtn=csrf_checker(["menu.php"],["G","C","S"]);
	if($rtn!==true){
		redirect_to_login($rtn);
	}

	//更新モードの場合、session[usr_id]のチェック
	$rtn=check_session_userid($pdo_h);
	$mode="update";
	//更新モード：ユーザ情報取得
	$sqlstr="select * from Users where uid=?";
	$stmt = $pdo_h->prepare($sqlstr);
	$stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
	
}else{
	//モード指定なしはNG
	redirect_to_login("想定外のアクセスルートです。");
}
//log_writer2($myname." \$session[id] =>",$_SESSION["user_id"],"lv3");


$token=csrf_create();

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	//include "head.html" 
	include "head_bs5.html" 
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
		<div class='container' style='padding-top:15px;'>
			<div class='col-12 col-md-8'>
				<template v-if='MSG!==""'>
					<div v-bind:class='alert_status' role='alert' >{{MSG}}</div>
				</template>
				<form  id='form1' style='font-size:1.5rem' @submit.prevent='on_submit'>
					<input type='hidden' name='csrf_token' :value='csrf'>
					<input type='hidden' name='mode' :value='mode'>
					<input type='hidden' name='shoukai' value=<?php echo $shoukai; ?>>
					<input type='hidden' name='moto_mail' :value='account["moto_mail"]'>
					
					<label for='mail' >メールアドレス</label>
					<input v-model='account["mail"]["val"]' :='account["mail"]["lock"]' type='email' maxlength='40' class='form-control' id='mail' name='MAIL' required='required' placeholder='必須' >
					<hr>
					<template v-if='mode==="update"'>
						<input type='checkbox' id='chk_pass' name='chk_pass'>
						<label for='chk_pass' >パスワードを変更する</label><br>
					</template>
					<label for='pass'>パスワード(6桁以上)</label>
					<input type='password' minlength='6' class='form-control' id='pass' :='pass_lock'>
					<label for='pass2'>パスワード（確認）</label>
					<input type='password' minlength='6' class='form-control' id='pass2' oninput='Checkpass(this)' name='PASS' :='pass_lock'>
					<hr>
					<label for='question' >秘密の質問(パスワードを忘れたときに使用します)</label>
					<input v-model='account["question"]["val"]' :='account["question"]["lock"]' type='text' maxlength='20' class='form-control' id='question' name='QUESTION' required='required' placeholder='例：初恋の人の名前' >
					<label for='answer' >答え</label>
					<input v-model='account["answer"]["val"]' :='account["answer"]["lock"]' type='text' maxlength='20' class='form-control' id='answer' name='ANSWER' required='required' placeholder='例：ささき' >
					<small id='answer' class='form-text text-muted'>ひらがな・半角英数・スペース不使用を推奨</small>
					<br>
					<template v-if='mode==="update"'>
						<input v-model='account["loginrez"]["val"]' :='account["loginrez"]["lock"]' type='checkbox'  id='loginrez' name='LOGINREZ' >
						<label for='loginrez' >ログイン後レジ画面表示</label>
						<br>
						<hr>
						<div>ここから下は請求書・納品書・自動送信メールに使用します。<br>使用しない方は入力不要です。</div>
						<hr>
						<label for='name' >姓名</label>
						<input v-model='account["name"]["val"]' :='account["name"]["lock"]' type='text' class='form-control' id='name' name='NAME'  >
						<label for='yagou' >屋号</label>
						<input v-model='account["yagou"]["val"]' :='account["yagou"]["lock"]' type='text' class='form-control' id='yagou' name='YAGOU'  >
						<label for='invoice' >インボイス登録番号</label>
						<input v-model='account["invoice"]["val"]' :='account["invoice"]["lock"]' type='text' class='form-control' id='invoice' name='invoice'  >
						<small id='invoice' class='form-text text-muted'>未登録だと税区分は<span style='color:red'>すべて非課税</span>となります。</small><br><br>
						<label for='yubin' >郵便番号('-'抜き)</label>
						<input v-model='account["yubin"]["val"]' :='account["yubin"]["lock"]' type='text' class='form-control' id='yubin' name='zip11' onKeyUp='AjaxZip3.zip2addr(this,"","addr11","addr11");' >
						<label for='add1' >住所１</label>
						<input value='<?php echo $row[0]["address1"];?>' :readonly='account["address1"]["lock"]["readonly"]' type='text' maxlength='20' class='form-control' id='add1' name='addr11' placeholder='納品書・請求書に使用。住所1行目' >
						<label for='add2' >住所２</label>
						<input v-model='account["address2"]["val"]' :='account["address2"]["lock"]' type='text' maxlength='20' class='form-control' id='add2' name='ADD2' placeholder='納品書・請求書に使用。住所2行目' >
						<label for='add3' >住所３</label>
						<input v-model='account["address3"]["val"]' :='account["address3"]["lock"]' type='text' maxlength='20' class='form-control' id='add3' name='ADD3' placeholder='納品書・請求書に使用。住所3行目' >
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
		const { createApp, ref, onMounted, computed, VueCookies } = Vue;
		createApp({
			setup(){
				const mode = ref('<?php echo $mode; ?>')
				const alert_status = ref(['alert'])
				const MSG = ref('')
				const loader = ref(false)
				const csrf = ref('<?php echo $token; ?>') 
				const step = ref('check')
				
				const account = ref({
					'mail':{
						'val':'<?php echo $row[0]["mail"];?>'
						,'lock':{'readonly':false}
					},
					'moto_mail':'<?php echo $row[0]["mail"];?>',
					'question':{
						'val':'<?php echo $row[0]["question"];?>'
						,'lock':{'readonly':false}
					},
					'answer':{
						'val':'<?php echo $row[0]["answer"];?>'
						,'lock':{'readonly':false}
					},
					'loginrez':{
						'val':'<?php echo $row[0]["loginrez"];?>'
						,'lock':{'readonly':false}
					},
					'name':{
						'val':'<?php echo $row[0]["name"];?>'
						,'lock':{'readonly':false}
					},
					'yagou':{
						'val':'<?php echo $row[0]["yagou"];?>'
						,'lock':{'readonly':false}
					},
					'yubin':{
						'val':'<?php echo $row[0]["yubin"];?>'
						,'lock':{'readonly':false}
					},
					'address1':{
						'val':'<?php echo $row[0]["address1"];?>'
						,'lock':{'readonly':false}
					},
					'address2':{
						'val':'<?php echo $row[0]["address2"];?>'
						,'lock':{'readonly':false}
					},
					'address3':{
						'val':'<?php echo $row[0]["address3"];?>'
						,'lock':{'readonly':false}
					},
					'invoice':{
						'val':'<?php echo $row[0]["invoice_no"];?>'
						,'lock':{'readonly':false}
					}
				})
				const AllLock = () =>{
					console.log('OnPress')
					account.value['mail']['lock']['readonly']=true
					account.value['question']['lock']['readonly']=true
					account.value['answer']['lock']['readonly']=true
					account.value['loginrez']['lock']['readonly']=true
					account.value['name']['lock']['readonly']=true
					account.value['yagou']['lock']['readonly']=true
					account.value['yubin']['lock']['readonly']=true
					account.value['invoice']['lock']['readonly']=true
					account.value['address1']['lock']['readonly']=true
					account.value['address2']['lock']['readonly']=true
					account.value['address3']['lock']['readonly']=true
				}
				const AllUnLock = () =>{
					console.log('OnPress')
					if(mode.value==="update"){
						step.value = 'check'
						account.value['mail']['lock']['readonly']=false
						account.value['question']['lock']['readonly']=false
						account.value['answer']['lock']['readonly']=false
						account.value['loginrez']['lock']['readonly']=false
						account.value['name']['lock']['readonly']=false
						account.value['yagou']['lock']['readonly']=false
						account.value['yubin']['lock']['readonly']=false
						account.value['invoice']['lock']['readonly']=false
						account.value['address1']['lock']['readonly']=false
						account.value['address2']['lock']['readonly']=false
						account.value['address3']['lock']['readonly']=false
					}else if(mode.value==="insert"){
						step.value="next"
					}
				}
				const on_submit = (e) =>{
					let form_data = new FormData(e.target)
					let params = new URLSearchParams (form_data)
					axios
						.post('ajax_account_Temporarily_saved.php',params) 
						.then((response) => {
							csrf.value = response.data.csrf_create
							if(response.data.status !== "alert-success"){
								MSG.value = response.data.MSG
								alert_status.value[1] = response.data.status
							}else{
								AllLock()
								step.value = 'register'
								console.log(`on_submit SUCCESS`)
							}
						})
						.catch((error) => {
							console.log(`on_submit ERROR:${error}`)
						})
						.finally(()=>{
						})
				}
				const UpdateValue = () =>{
					let params = new URLSearchParams ({'csrf_token':csrf.value,'mode':mode.value})
					axios
						.post('ajax_account_sql.php',params) 
						.then((response) => {
							csrf.value = response.data.csrf_create
							if(response.data.status !== "alert-success"){
								alert(response.data.MSG)
								//MSG.value = response.data.MSG
								//alert_status.value[1] = response.data.status
							}else{
								alert(response.data.MSG)
								console.log(`UpdateValue SUCCESS`)
							}
						})
						.catch((error) => {
							console.log(`UpdateValue ERROR:${error}`)
						})
						.finally(()=>{
							AllUnLock()
						})

				}
				const pass_lock = computed(() =>{
					if(mode.value==='insert'){
						return {'readonly':false}
					}else if(mode.value==='update'){
						return  {'readonly':true}
					}
        })
				onMounted(() => {
					console.log('mounted')
					//console.log(account)
				})
				return{
					mode,
					account,
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
		}).mount('#accountform');
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
	document.getElementById('chk_pass').onclick = function(){
		const a = document.getElementById('pass');
		const b = document.getElementById('pass2');
		if(a.required==true){
			a.required=false;
			b.required=false;
			a.placeholder='';
			b.placeholder='';
			a.readOnly='readonly';
			b.readOnly='readonly';
		}else{
			a.required=true;
			b.required=true;
			a.placeholder='必須';
			b.placeholder='必須';
			a.readOnly='';
			b.readOnly='';
		}
	}
	
</script><!--js-->
</html>
<?php
$stmt=null;
$pdo_h=null;
?>






<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);
$token = csrf_create();
$sqlstr="";
//
//log_writer2("\$POST",$_POST,"lv3");
if(!empty($_POST)){

	$ymfrom = $_POST["ymfrom"];
	$ymto = $_POST["ymto"];
	$list = $_POST["list"];
	

}else{
	$ymfrom = (string)date('Y')."-01-01";
	$ymto = (string)date('Y')."-12-31";
	$list = "%";
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php" 
	?>
	<!--ページ専用CSS-->
	<link rel="stylesheet" href="css/style_outputmenu.css?<?php echo $time; ?>" >
	<TITLE><?php echo $title;?></TITLE>
</head>
<body>
	<div id='app'>
	<header class="header-color common_header" style="flex-wrap: wrap;">
		<div class="yagou title" style='width:100%;'>
			<a href="menu.php"><?php echo $title;?></a>
		</div>
		<div class='user_disp' style='width:100%;'>
			発行済み領収書の確認・再発行
		</div>
	</header>
	<main class='common_body'>
		<div class="container" style="padding-top:0px;position:relative;">
		<details class='mb-3'>
  		<summary class='text-end'>help</summary>
  		<button type='button' class='btn btn-primary'><i class="bi bi-filetype-pdf"></i>通常領収書</button>
  		<button type='button' class='btn btn-warning'><i class="bi bi-filetype-pdf"></i>返品発行済</button>
  		<button type='button' class='btn btn-danger'><i class="bi bi-filetype-pdf"></i>返品領収書</button>
		</details>

			<!--<button class='position-fix' style='top:75px' type='button' @click=''>モーダル</button>-->
			<table class="table result_table">
				<thead class='sticky-top' style='top:70px;'>
					<tr>
						<th>領収-売上No</th>
						<th>宛名</th>
						<th rowspan="2">確認</th>
					</tr>
					<tr>
						<th colspan="2">最終発行日時</th>
					</tr>
				</thead>
				<tbody v-for='(list,index) in ryoushu' :key='list.R_No'>
					<tr>
						<td style='border-bottom-width:0px;'>{{list.R_NO}}-{{list.UriNO}}</td>
						<td style='border-bottom-width:0px;'>{{list.Atena}}</td>
						<td rowspan="2" class='align-middle text-center'>
							<button v-if='list.H_saki_RNO > 0' type='button' class='btn btn-warning' @click='open_modal(list.UriNO,list.R_NO,list.Atena,list.H_saki_RNO,list.H_moto_RNO)'><i class="bi bi-filetype-pdf"></i></button>
							<button v-else-if='list.H_moto_RNO > 0' type='button' class='btn btn-danger' @click='open_modal(list.UriNO,list.R_NO,list.Atena,list.H_saki_RNO,list.H_moto_RNO)'><i class="bi bi-filetype-pdf"></i></button>
							<button v-else type='button' class='btn btn-primary' @click='open_modal(list.UriNO,list.R_NO,list.Atena,list.H_saki_RNO,list.H_moto_RNO)'><i class="bi bi-filetype-pdf"></i></button>
						</td>
					</tr>
					<tr>
						<td colspan="2">{{list.LastHakkouDate}}</td>
					</tr>
				</tbody>
			</table>

		</div>

	</main>
	<!--領収書-->
	<div class='modal fade' id='ryoushuu' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content' style='font-size: 2rem; font-weight: 600;'>
				<div class='modal-header'>
					<div class='modal-title' id='myModalLabel' style='text-align:center;width:100%;'>領収書発行</div>
				</div>
				<div class='modal-body text-center ps-5 pe-5'>
					<div class='input-group d-flex justify-content-center align-items-center ps-3 pe-3' >
						<input type='radio' class='btn-check' name='process' value='saihakkou' autocomplete='off' v-model='process' id='saihakkou'>
						<label class='btn btn-outline-primary ' for='saihakkou' style='border-radius:0;'>再発行</label>
						<input type='radio' class='btn-check' name='process' value='shusei' autocomplete='off' v-model='process' id='shusei'>
						<label class='btn btn-outline-primary ' for='shusei' style='border-radius:0;'>修正</label>
						<template v-if='henpin'>
							<input type='radio' class='btn-check' name='process' value='henpin' autocomplete='off' v-model='process' id='henpin' >
							<label class='btn btn-outline-primary ' for='henpin' style='border-radius:0;'>返品</label>
						</template>
						<div class='shadow-sm mt-3 text-start' >
							<p class='fs-4 fw-light' v-html='discription'></p>
						</div>
					</div>

					<div v-if='process==="shusei"' class='mt-3'>
						<label for='oaite' class='form-label'>宛名：</label>
						<input type='text' class='form-control' id='oaite' v-model='oaite' style='font-size: 2rem;'>
						<div class='input-group d-flex justify-content-center align-items-center mt-3' >
							<input type='radio' class='btn-check' name='keishou' value='御中' autocomplete='off' v-model='keishou' id='onchu'>
							<label class='btn btn-outline-primary' for='onchu' style='border-radius:0;font-size: 2rem;'>御中</label>
							<input type='radio' class='btn-check' name='keishou' value='様' autocomplete='off' v-model='keishou' id='sama' >
							<label class='btn btn-outline-warning' for='sama' style='border-radius:0;font-size: 2rem;'>様</label>
						</div>
					</div>
					<div id="qrOutput" class='justify-content-center mt-3'>
						<canvas id="qr"></canvas>
					</div>
				</div>
				<div class='modal-footer'>
					<!--<button type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1' @click='QRout()'><i class="bi bi-qr-code"></i></button>-->
					<button type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1' @click='prv()'><i class="bi bi-filetype-pdf"></i></button>
					<a :href='`https://line.me/R/share?text=`' type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1'>
						<i class="bi bi-line line-green"></i>
					</a>
				</div>
			</div>
		</div>
	</div>
	</div>
	<script>
		const { createApp, ref, onMounted, computed, VueCookies, watch,nextTick  } = Vue;
		createApp({
			setup(){
				const token = ref('<?php echo $token;?>')
				const ymfrom = ref('<?php echo $ymfrom; ?>')
				const ymto = ref('<?php echo $ymto; ?>')
				const uid = ref('<?php echo $_SESSION["user_id"]; ?>')
				const process = ref('')
				const oaite = ref('')
				const keishou = ref('御中')
				const henpin = ref(true)
				const discription = computed(()=>{
					if(process.value==="saihakkou"){
						return "領収書の再発行を行います。"
					}else if(process.value==="shusei"){
						return "あて名書きを再設定し、領収書を発行します。"
					}else if(process.value==="henpin"){
						return "<p>指定した領収書の返品領収書を発行し、マイナスの売上データを計上します。</p><p>一部返品の場合、返品領収書発行後にレジ画面から未返品分の売上を入力し、再度領収書を発行してください。</P>"
					}
				})
				const ryoushu = ref([])
				const get_ryoshu = () =>{
					let params = new URLSearchParams()
					params.append('csrf_token', token.value);
					//params.append('body', "WebRez+ へのURLは以下の通りです。\r\nhttps://");
					axios
					.post('ajax_get_Ryoushu.php',params)
					.then((response) => {
						if(response.data.status==='success'){
							//alert('メールを送信しました。')
							ryoushu.value = response.data.ryoushu_data
						}else{
							alert(response.data.MSG)
						}
						token.value = response.data.csrf_create
					})
					.catch((error) => console_log(`get_UriageList ERROR:${error}`,'lv3'));
				}
				let R_NO
				let U_NO
				let PHP
				const open_modal=(uriage_no,ryoushu_no,atena,H_saki_RNO,H_moto_RNO)=>{
					R_NO = ryoushu_no
					U_NO = uriage_no

					if(H_saki_RNO > 0 || H_moto_RNO > 0){
						henpin.value = false
					}else{
						henpin.value = true
					}

					if(String(atena).endsWith('様') ){
						keishou.value="様"
						oaite.value=String(atena).slice(0,-3)
					}else{
						keishou.value="御中"
						oaite.value=String(atena).slice(0,-4)
					}

					const myModal = new bootstrap.Modal(document.getElementById('ryoushuu'), {})
					myModal.show()

				}

				watch([process,keishou,oaite],()=>{
					if(process.value==="saihakkou"){
						PHP = `ryoushuu_pdf_sai.php?i=${uid.value}&r=${R_NO}`
					}else if(process.value==="shusei"){
						PHP = `ryoushuu_pdf_shu.php?i=${uid.value}&u=${U_NO}&r=${R_NO}&tp=1&k=${keishou.value}&s=${oaite.value}`
					}else if(process.value==="henpin"){
						PHP = `ryoushuu_pdf_henpin.php?i=${uid.value}&u=${U_NO}&r=${R_NO}&tp=1&k=${keishou.value}&s=${oaite.value}`
					}
					QRout()
				})
				const getGUID = () =>{
					let dt = new Date().getTime();
					let uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
							let r = (dt + Math.random()*16)%16 | 0;
							dt = Math.floor(dt/16);
							return (c=='x' ? r :(r&0x3|0x8)).toString(16);
					});
					return uuid;
				}

				const QRout = () =>{
					// 入力された文字列を取得
					let php = PHP
					let userInput = P_ROOT_URL + php + '&qr=' + getGUID()
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
						qr.size = 190; // QRコードのサイズ
						// QRコードをflexboxで表示
						document.getElementById('qrOutput').style.display = 'flex';
					})();
					// png出力用コード
					var cvs = document.getElementById("qr");
				}
				const prv = () =>{
					//プレビュー印刷
					console_log("start prv()")
					let URL = P_ROOT_URL 
					if(process.value!=="saihakkou"){
						if(confirm("表示する領収書をお客様に発行しますか？")===true){
							URL += PHP + (`&sb=on`)
						}else{
							URL += PHP + (`&sb=off`)
						}
					}else{
						URL += PHP
					}
					console_log(URL)
					window.open(URL)
					window.setTimeout(get_ryoshu(),7000)
				}

				onMounted(() => {
					console_log('onMounted','lv3')
					get_ryoshu()
					process.value='saihakkou'
				})
				return{
					ymfrom,
					ymto,
					uid,
					ryoushu,
					open_modal,
					process,
					discription,
					QRout,
					prv,
					oaite,
					keishou,
					henpin,
				}
			}
		}).mount('#app');
	</script>
</body>


</html>
<?php
	$pdo_h=null;
?>
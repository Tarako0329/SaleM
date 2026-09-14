<?php
{
	//<!--Evregi.php-->
	//ヘッド処理
	/*関数メモ
	check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

	【想定して無いページからの遷移チェック】
	csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
	　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。
	*/
	require "php_header.php";
	//php更新処理は15秒でタイムアウトする設定のため
	//axiosの方は余裕を見て20秒でタイムアウトとする timeout60s => 60,000
	$timeout=20000;
	if(EXEC_MODE==="Local"){$timeout=0;}
	
	$status=(!empty($_SESSION["status"])?$_SESSION["status"]:"");
	$_SESSION["status"]="";

	$rtn = csrf_checker(["menu.php"],["G","C"]);
	if($rtn !== true){
		redirect_to_login($rtn);
	}

	//セッションのuserIDがクリアされた場合の再取得処理。
	$rtn=check_session_userid($pdo_h);
	
	//ユーザ情報取得
	//$sql="select yuukoukigen,ZeiHasu from Users where uid=?";
	$sql="select yuukoukigen,ZeiHasu from Users_webrez where uid=?";
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
	$emsg = "";
	
	//有効期限チェック
	if($row[0]["yuukoukigen"]==""){
		//本契約済み
	}elseif($row[0]["yuukoukigen"] < date("Y-m-d")){
		//お試し期間終了
		$root_url = bin2hex(openssl_encrypt(ROOT_URL, 'AES-128-ECB', 1));
		$dir_path =  bin2hex(openssl_encrypt(dirname(__FILE__)."/", 'AES-128-ECB', 1));
		$emsg="お試し期間、もしくは解約後有効期間が終了しました。<br>継続してご利用頂ける場合は<a href='".rot13decrypt2(PAY_CONTRACT_URL)."?system=".TITLE."&sysurl=".$root_url."&dirpath=".$dir_path."'>こちらから本契約をお願い致します </a>";
	}

	//端数処理設定
	$ZeiHasu = $row[0]["ZeiHasu"];

	$token = csrf_create();

	//税区分MSリスト取得
	$sqlstr="select * from ZeiMS order by zeiKBN;";
	$stmt = $pdo_h->query($sqlstr);
	$zeimaster = $stmt->fetchAll(PDO::FETCH_ASSOC);

}
?>
<!DOCTYPE html>
<html lang='ja'>
<head>
	<?php
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include 'head_bs5.php'
	?>
	<!--<script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.min.js"></script>--><!--read QRコードライブラリ-->
	<!--<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.js"></script>--><!--make QRコードライブラリ-->
	<!--ページ専用CSS-->
	<link rel='stylesheet' href='css/style_EVregi.css?<?php echo $time; ?>' >
	<TITLE><?php echo TITLE.' レジ';?></TITLE>
	<style>
		#qrOutput {
			flex-wrap: wrap;
			align-items: center;
			justify-content: space-around;
			padding: 20px;
		}
		.kokyaku_disp{
    	/*INPUT イベント名・店舗名部*/
    	border-right:none;
    	border-left:none;
    	border-top:none;
    	border-bottom:solid 1px;
    	border-color:red;  /*下線・色固定*/
    	/*text-align:center;*/
    	font-size: 1.7rem;
    	font-weight: 600;
    	color: black;
    	height:35px;
    	width:16rem;
    	background: #ff838b;
    	/*background:transparent;*/
    	padding-top:5px;
    	padding-left:10px;
    	white-space: nowrap;
    	overflow: hidden;
		}
	</style>
</head>
<body>
	<div id='register'>
	<form method = 'post' id='form1' @submit.prevent='on_submit'>
		<input :value='csrf' type='hidden' name='csrf_token' >
		<input value='kobetu' type='hidden' name='mode' >
		<input :value="JSON.stringify(order_list)" type='hidden' name='ORDERS' >
		<input :value="JSON.stringify(order_summary.tax_list)" type='hidden' name='ZeiKbnSummary' >
	
		<header class='header-color common_header' style='display:block'>
			<div class='title yagou'><a href='menu.php'><?php echo TITLE;?></a></div>
			<span class=''>
				<span style='color:var(--user-disp-color);font-weight:400;font-size:1.4rem;'>
					売上日：
				</span>
				<input type='date' class='date' style='height:20px;font-size:1.4rem;' name='KEIJOUBI' required='required' v-model='KEIJOUBI'>
			</span>
			
		</header>

		<main class='common_body' id='main_area' style='padding-top:80px;'>
			<div class="container">
				<div class='row'>
					<div class='col-12'>
						<template v-if='MSG!==""'><!--登録結果ステータス表示+領収書ボタン-->
							<div :class='alert_status' role='alert' id='msg_alert'>
								{{MSG}}
								<button v-if='alert_status[1]==="alert-success"' type='button' class='btn btn-primary' @click='open_R()'> 
									領収書
								</button>
							</div>
						</template><!--登録結果ステータス表示+領収書ボタン-->
					</div>

					<div class='col-12'>
						<label class=''>顧客名　　</label>
						<input type='text' class='' name='Kokyaku' v-model='Kokyaku' required='required' style='display:none;'>
						<div role='button' class='kokyaku_disp'  @Click='Kokyaku.value=""'   data-bs-toggle='modal' data-bs-target='#EventSelect'>{{Kokyaku}}</div>
					</div>
					<div class='col-12'>
						<table class='table ' style='margin-top:5px;'>
							<thead class='table-info'>
								<tr>
									<th style='width:40%;'>商品名</th>
									<th style='width:25%;'>単価</th>
									<th style='width:15%;'>数量</th>
									<th style='width:20%;'>金額</th>
								</tr>
								<tr>
									<th ></th>
									<th >原価単価</th>
									<th >税率</th>
									<th ></th>
								</tr>
							</thead>
							<tbody>
								<template v-for='(list,index) in order_list' :key='list.NM'>
									<tr class='table-group-divider'>
										<td><input class='form-control' v-model='list.NM' @Click='set_shouhin_Open(index)'   data-bs-toggle='modal' data-bs-target='#ShouhinSelect'></td>
										<td><input class='form-control' v-model='list.TANKA' type='number' min='0' step='1'></td>
										<td><input class='form-control' v-model='list.SU' type='number' min='0'></td>
										<td class='text-end'>{{(list.TANKA * list.SU)}}</td>
									</tr>
									<tr >
										<td></td>
										<td><input class='form-control' v-model='list.GENKA_TANKA' type='number' min='0' step='1'></td>
										<td>
											<select class='form-select' v-model='list.ZEIKBN'>
												<option v-for='zei in zm' :value='zei.税区分'>{{zei.税区分名}}</option>
											</select>
										</td>
										<td class='text-end pe-3'><i class='fas fa-trash-alt' @click='trush_order(index)'></i></td>
									</tr>
								</template>
								<tr class='table-group-divider'>
									<td colspan='4' style='text-align:center;'>
										<button type='button' class='btn btn-primary' @click='add_order()'>商品追加</button>
									</td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

			</div>
		</main>
		<footer class='rezfooter'>
			<div class="container-fluid" style='padding:0;text-align:center;'>
				<div class='row'>
					<div class='col-12 kaikei' ref='total_area'>
						<span style='font-size:1.6rem;'>お会計</span> ￥<span id='kaikei'> {{order_summary.hontai_total + order_summary.tax_total}} </span>- 
						<span style='font-size:1.6rem;'>内税</span>(<span id='utizei'>{{order_summary.tax_total}}</span>)
					</div>
				</div>
				<div class='row' style='height:60px;'>
					<div class='col-4' style='padding:0;'>
						<button type='button' class='btn--chk' style='border-left:none;border-right:none;' id='dentaku' data-bs-toggle='modal' data-bs-target='#FcModal'>
						</button>
					</div>
					<div class='col-4 ' style='padding:0;'>
						<button type='button' @click='clear_order()' class='btn--chk' id='order_clear_btn'>クリア</button><!-- id='order_clear'-->
					</div>
					<div class='col-4 ' style='padding:0;'>
						<button type='submit'  class='btn--commit ' style='border-left:none;border-right:none;' name='commit_btn' value='uriage_commit'>登　録</button>
					</div>
				</div>
			</div>
		</footer>

	</form>
	<div class="loader-wrap" v-show='loader'>
		<div class="loader">Loading...</div>
	</div>

	<!--顧客登録-->
	<div class='modal fade' id='EventSelect' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered modal-sm'>
			<div class='modal-content' style='font-size: 1.6rem; font-weight: 600;'>
				<div class='modal-header'>
					<div class='modal-title' id='myModalLabel' style='text-align:center;width:100%;'>顧客名入力 or 検索してリスト選択</div>
				</div>
				<div class='modal-body text-center ps-5 pe-5'>
					<input type='text' class='form-control ps-3' v-model='Kokyaku' style='font-size: 2rem;' placeholder="入力 or 検索">
					<div class='evlist_area text-start ps-1'>
						<template v-for='(list,index) in Kokyaku_list_filtered' :key='list.meishou'>
							<div class="form-check ps-3">
								<input class='form-check-input' type='radio' name='ev_select' v-model='Kokyaku' :value=list.meishou :id='`evlist_${index}`' style='border:0;display:none;'>
								<label class="form-check-label" :for='`evlist_${index}`' style='font-size: 1.8rem;'>{{list.meishou}}</label>
							</div>
						</template>
					</div>
					<small style='font-size: 1.2rem;'>イベント名を入力。もしくはリストから選択してください。</small>
					
				</div>
				<div class='modal-footer'>
					<button type='button'  class='btn btn-primary' style='font-size: 2.0rem;width:40%;' @click='clear_EV_input_value()'>クリア</button>
					<button type='button'  class='btn btn-primary' data-bs-dismiss='modal' style='font-size: 2.0rem;width:40%;'>決定</button>
				</div>
			</div>
		</div>
	</div>
	<!--商品登録-->
	<div class='modal fade' id='ShouhinSelect' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered modal-sm'>
			<div class='modal-content' style='font-size: 1.6rem; font-weight: 600;'>
				<div class='modal-header'>
					<div class='modal-title' id='myModalLabel' style='text-align:center;width:100%;'>商品名入力 or 検索してリスト選択</div>
				</div>
				<div class='modal-body text-center ps-5 pe-5'>
					<input type='text' class='form-control ps-3' v-model='order_shouhinNM' style='font-size: 2rem;' placeholder="入力 or 検索">
					<div class='evlist_area text-start ps-1'>
						<template v-for='(list,index) in shouhinMS_filtered' :key='list.shouhinCD'>
							<div class="form-check ps-3">
								<input class='form-check-input' type='radio' name='sh_select' v-model='order_shouhinNM' :value=list.shouhinNM :id='`shouhinCD_${index}`' style='border:0;display:none;'>
								<label class="form-check-label" :for='`shouhinCD_${index}`' style='font-size: 1.8rem;'>{{list.shouhinNM}}</label>
							</div>
						</template>
					</div>
					
				</div>
				<div class='modal-footer'>
					<button type='button'  class='btn btn-primary' style='font-size: 2.0rem;width:40%;' @click='clear_EV_input_value()'>クリア</button>
					<button type='button'  class='btn btn-primary' data-bs-dismiss='modal' style='font-size: 2.0rem;width:40%;' @click='set_shouhin_close()'>決定</button>
				</div>
			</div>
		</div>
	</div>
	<!--領収書-->
	<div class='modal fade' id='ryoushuu' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content' style='font-size: 2rem; font-weight: 600;'>
				<div class='modal-header'>
					<div class='modal-title' id='myModalLabel' style='text-align:center;width:100%;'>領収書発行</div>
				</div>
				<div class='modal-body text-center'>
					<label for='oaite' class='form-label'>宛名：</label>
					<input type='text' class='form-control' id='oaite' v-model='Kokyaku' style='font-size: 2rem;'>
					
					<div style='padding:0;margin-top:10px;'>
						<input type='radio' class='btn-check' name='keishou' value='御中' autocomplete='off' v-model='keishou' id='onchu'>
						<label class='btn btn-outline-primary' for='onchu' style='border-radius:0;font-size: 2rem;'>御中</label>
						<input type='radio' class='btn-check' name='keishou' value='様' autocomplete='off' v-model='keishou' id='sama' >
						<label class='btn btn-outline-warning' for='sama' style='border-radius:0;font-size: 2rem;'>様</label>
					</div>
					<div id="qrOutput">
						<canvas id="qr"></canvas>
					</div>
				</div>
				<div class='modal-footer'>
					<!--<button type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1' @click='QRout()'><i class="bi bi-qr-code"></i></button>-->
					<button type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1' @click='prv()'><i class="bi bi-filetype-pdf"></i></button>
					<a :href='`https://line.me/R/share?text=${send_msg}`' type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1'>
						<i class="bi bi-line line-green"></i>
					</a>
				</div>
			</div>
		</div>
	</div>
	</div><!-- <div  id='register'> -->
	<script>
		var GSI = {};
		// Enterキーが押された時にSubmitされるのを抑制する
		document.getElementById("form1").onkeypress = (e) => {
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


	<script src="regi_vue.js?<?php echo $time; ?>"></script>
	<script>
		REZ_APP('<?php echo $_SESSION["user_id"]."','".$timeout;?>').mount('#register');
	</script><!--Vue3-->

</body>
</html>
<?php
$stmt = null;
$pdo_h = null;
?>
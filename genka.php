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
	$sql="SELECT yuukoukigen,ZeiHasu from Users_webrez where uid=?";
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
	<!-- Big.js ライブラリの読み込み (CDN) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/big.js/6.2.1/big.min.js"></script>	
	<link rel='stylesheet' href='css/style_EVregi.css?<?php echo $time; ?>' >
	<TITLE><?php echo TITLE.' レジ';?></TITLE>
	<style>
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
		<input :value='csrf' type='hidden' name='csrf_token' >
		<input value='kobetu' type='hidden' name='mode' >
	
		<header class='header-color common_header' style='display:block'>
			<div class='title yagou'><a href='menu.php'><?php echo TITLE;?></a></div>
		</header>

		<main class='common_body' id='main_area' style='padding-top:80px;'>
			<div class="container-fluid ps-5 pe-5">
				<div class='row'>
					<div class='col-12'>
						<label class=''>商品名　　</label>
						<div role='button' class='kokyaku_disp' data-bs-toggle='modal' data-bs-target='#ShouhinSelect'>{{saved_ShouhinMS.shouhinNM}}</div>
					</div>
					<div class='col-12'>
						<table class='table ' style='margin-top:5px;'>
							<thead class='table-info'>
								<tr>
									<th style='width:50px;'></th>
									<th style='width:auto;'>製造LOT</th>
									<th style='width:auto;min-width:100px;'>原価単価</th>
									<th style='width:auto;'>販売LOT</th>
									<th style='width:auto;min-width:100px;'>販売原価</th>
									<th style='width:auto;'>原価率</th>
									<th style='width:auto;min-width:100px;'>販売価格</th>
									<th style='width:90px;'></th>
								</tr>
							</thead>
							<tbody>
								<tr class="table-secondary">
									<th></th>
									<td>{{saved_ShouhinMS.S_lot}}</td>
									<td>{{saved_ShouhinMS.SEIZOU_GENKA_TANKA}}</td>
									<td>{{saved_ShouhinMS.H_lot}}</td>
									<td>{{saved_ShouhinMS.GENKA_TANKA}}</td>
									<td>{{saved_ShouhinMS.G_per}}</td>
									<td></td>
									<td></td>
								</tr>
								<tr>
									<th>NEW</th>
									<td><input class="form-control" type="number" v-model="edit_ShouhinMS.S_lot"></td>
									<td>{{edit_S_genka_tanka.toLocaleString()}}</td>
									<td><input class="form-control" type="number" v-model="edit_ShouhinMS.H_lot"></td>
									<td>{{edit_H_genka_tanka.toLocaleString()}}</td>
									<td><input class="form-control" type="number" v-model="edit_ShouhinMS.G_per"></td>
									<td>{{edit_hanbai_tanka.toLocaleString()}}</td>
									<td><button class="btn btn-primary p-1" style='width:80px;' @click="save_ShouhinMS()">登録</button></td>
								</tr>
							</tbody>
						</table>
					</div>

					<div class='col-7'>
						<table class='table caption-top ' style='margin-top:5px;'>
							<caption><br>原材料リスト</caption>
							<thead class='table-success'>
								<tr>
									<th style='width:auto;'>材料名</th>
									<th style='width:auto;'>価格(税込)</th>
									<th style='width:70px;'>税率</th>
									<th style='width:auto;'>内容量</th>
									<th style='width:auto;'>単位</th>
									<th style='width:55px;'></th>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td><input type="text" class="form-control" v-model="edit_zairyouMS.hinmei"></td>
									<td><input type="number" class="form-control" v-model="edit_zairyouMS.value"></td>
									<td>
										<select class="form-select" v-model="edit_zairyouMS.zeikbn">
											<option v-for="list in zm" :key="list.税区分" :value="list.税区分">{{list.税区分名}}</option>
										</select>
										</td>
									<td><input type="number" class="form-control" v-model="edit_zairyouMS.volum"></td>
									<td><input type="text" class="form-control" v-model="edit_zairyouMS.unit"></td>
									<td><button class="btn btn-success p-1" @click="add_zairyouMS">登録</button></td>
								</tr>
								<tr v-for="(list,index) in zairyouMS" :key="list.zairyouCD">
									<td>{{list.hinmei}}</td>
									<td>{{list.value}}</td>
									<td>{{list.zeikbn}}</td>
									<td>{{list.volum}}</td>
									<td>{{list.unit}}</td>
									<td><input type="checkbox" class="form-check-input" v-model="list.used" ></td>
								</tr>
							</tbody>
						</table>
					</div>
					<div class='col-5'>
						<table class='table caption-top' style='margin-top:5px;'>
							<caption>１製造LOTに利用する材料<br>※使用量の単位は原材料リストに合わせる</caption>
							<thead class='table-info'>
								<tr>
									<th style='width:auto;'>材料名</th>
									<th style='width:auto;'>使用量</th>
									<th style='width:auto;'>材料費</th>
								</tr>
							</thead>
							<tbody>
								<tr v-for="list in shouhinMS_zairyou" :key="list.zairyouCD">
									<td>{{list.hinmei}}</td>
									<td><input type="text" class="form-control" v-model="list.use_volume"></td>
									<td>{{list.hiyou}}</td>
								</tr>
								<tr>
									<td colspan="3" class="text-center"><button class="btn btn-primary p-1" style='width:80px;' @click="save_genka()">登録</button></td>
								</tr>
							</tbody>
						</table>
					</div>
				</div>

			</div>
		</main>
		<!--<footer class='rezfooter'>
			<button type='button' class='btn btn-primary' style='width:100%;font-size:2rem;' @click='save_genka()'>登録</button>
		</footer>-->

	<div class="loader-wrap" v-show='loader'>
		<div class="loader">Loading...</div>
	</div>

	<!--商品登録-->
	<div class='modal fade' id='ShouhinSelect' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered modal-sm'>
			<div class='modal-content' style='font-size: 1.6rem; font-weight: 600;'>
				<div class='modal-header'>
					<div class='modal-title' id='myModalLabel' style='text-align:center;width:100%;'>商品名入力 or 検索してリスト選択</div>
				</div>
				<div class='modal-body text-center ps-5 pe-5'>
					<input type='text' class='form-control ps-3' v-model='set_shouhinNM' style='font-size: 2rem;' placeholder="入力 or 検索">
					<div class='evlist_area text-start ps-1'>
						<template v-for='(list,index) in shouhinMS_filtered' :key='list.shouhinCD'>
							<div class="form-check ps-3">
								<input class='form-check-input' type='radio' name='sh_select' v-model='set_shouhinNM' :value=list.shouhinNM :id='`shouhinCD_${index}`' style='border:0;display:none;'>
								<label class="form-check-label" :for='`shouhinCD_${index}`' style='font-size: 1.8rem;'>{{list.shouhinNM}}</label>
							</div>
						</template>
					</div>
					
				</div>
				<div class='modal-footer'>
					<button type='button'  class='btn btn-primary' data-bs-dismiss='modal' style='font-size: 2.0rem;width:40%;' @click='set_shouhin_close()'>決定</button>
				</div>
			</div>
		</div>
	</div>

	</div><!-- <div  id='register'> -->
	<script src="genka_vue.js?<?php echo $time; ?>"></script>
	<script>
		REZ_APP('<?php echo $_SESSION["user_id"]."','".$timeout;?>').mount('#register');
	</script><!--Vue3-->

</body>
</html>
<?php
$stmt = null;
$pdo_h = null;
?>
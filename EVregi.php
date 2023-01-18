<?php
{
	//<!--Evregi.php-->
	//ヘッド処理
	/*関数メモ
	check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

	【想定して無いページからの遷移チェック】
	csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
	　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

	遷移先のチェック
	csrf_chk()                              ：COOKIE・SESSION・POSTのトークンチェック。
	csrf_chk_nonsession()                   ：COOKIE・POSTのトークンチェック。
	csrf_chk_nonsession_get($_GET[token])   ：COOKIE・GETのトークンチェック。
	csrf_chk_redirect($_GET[token])         ：SESSSION・GETのトークンチェック
	*/
	require "php_header.php";
	//php更新処理は15秒でタイムアウトする設定のため
	//axiosの方は余裕を見て20秒でタイムアウトとする timeout60s => 60,000
	$timeout=20000;
	if(EXEC_MODE==="Local"){$timeout=0;}

	//log_writer2("EVregi.php _SESSION values ",$_SESSION,"lv3");
	//log_writer2("EVregi.php _POST values ",$_POST,"lv3");

	$status=(!empty($_SESSION["status"])?$_SESSION["status"]:"");
	$_SESSION["status"]="";
	$HTTP_REFERER=(empty($_SERVER['HTTP_REFERER'])?"":$_SERVER['HTTP_REFERER']);

	if(csrf_chk_nonsession()===false){//POST:COOKIEチェック
		if($status=="login_redirect"){
			//ログイン画面から直通でアクセス
			log_writer("EVregi.php  $status ",$status);
		}else{
			$_SESSION["EMSG"]="セッションが正しくありませんでした。".filter_input(INPUT_POST,"csrf_token");
			log_writer2("EVregi.php","セッションが正しくありませんでした。","lv3");
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: index.php");
			exit();
		}
	}else{
		log_writer2("EVregi.php","csrf_chk_nonsession clear","lv3");
	}

	//セッションのuserIDがクリアされた場合の再取得処理。
	$rtn=check_session_userid($pdo_h);
	
	//有効期限チェック
	$sql="select yuukoukigen from Users where uid=?";
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if($row[0]["yuukoukigen"]==""){
		//本契約済み
	}elseif($row[0]["yuukoukigen"] < date("Y-m-d")){
		//お試し期間終了
		$root_url = bin2hex(openssl_encrypt(ROOT_URL, 'AES-128-ECB', 1));
		$dir_path =  bin2hex(openssl_encrypt(dirname(__FILE__)."/", 'AES-128-ECB', 1));
		$emsg="お試し期間、もしくは解約後有効期間が終了しました。<br>継続してご利用頂ける場合は<a href='".PAY_CONTRACT_URL."?system=".$title."&sysurl=".$root_url."&dirpath=".$dir_path."'>こちらから本契約をお願い致します </a>";
	}

	$token = csrf_create();

	$alert_msg=(!empty($_SESSION["msg"])?$_SESSION["msg"]:"");
	$RG_MODE=(!empty($_POST["mode"])?$_POST["mode"]:$_GET["mode"]);

	if($RG_MODE===""){
		echo "error rezi mode nothing!";
		exit();
	}

	//イベント名の取得
	//セッション -> DB
	$event = (!empty($_SESSION["EV"])?$_SESSION["EV"]:"");
	if(empty($event)){
		$sql = "select value,updatetime from PageDefVal where uid=? and machin=? and page=? and item=?";
		$stmt = $pdo_h->prepare($sql);
		$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(2, MACHIN_ID, PDO::PARAM_STR);
		$stmt->bindValue(3, "EVregi.php", PDO::PARAM_STR);
		$stmt->bindValue(4, "EV", PDO::PARAM_STR);
		$stmt->execute();

		if($stmt->rowCount()==0){
			$event = "";
		}else{
			$buf = $stmt->fetch();
			$date = new DateTime($buf["updatetime"]);

			//指定した書式で日時を取得する
			//echo $date->format('Y-m-d');
			if($date->format('Y-m-d')!=date("Y-m-d")){
				//イベントの日付が前日以前の場合はクリア
				$event = "";
			}else{
				$_SESSION["EV"] = $buf["value"];
				$event = $buf["value"];
			}
		}
	}
}
?>
<!DOCTYPE html>
<html lang='ja'>
<head>
	<?php
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include 'head_bs5.html'
	?>
	<!--ページ専用CSS-->
	<link rel='stylesheet' href='css/style_EVregi.css?<?php echo $time; ?>' >
	<TITLE><?php echo $title.' レジ';?></TITLE>
</head>

<body>
	<div  id='register'>
	<form method = 'post' id='form1' @submit.prevent='on_submit'>
		<input v-model='csrf' type='hidden' name='csrf_token' >
		<input v-model='rg_mode' type='hidden' name='mode'> <!--レジor個別売上or在庫登録-->
		<input type='hidden' :name='labels["EV_input_hidden"]' value=''>
	
		<header class='header-color common_header' style='display:block'>
			<div class='title yagou'><a href='menu.php'><?php echo $title;?></a></div>
			<span class='item_1'>
			<span style='color:var(--user-disp-color);font-weight:400;'>
				{{labels['date_type']}}
			</span>
			<input type='date' class='date' style='height:20%' name='KEIJOUBI' required='required' v-model='labels["date_ini"]'>
			</span>
			<input type='text' class='ev' :name='labels["EV_input_name"]' v-model='labels["EV_input_value"]' required='required' :placeholder='labels["EV_input_placeholder"]'>
			<div class='address_disp' :style='`${labels["address"]}; position:fixed;top:55px;right:5px;color:var(--user-disp-color);max-width:50%;height:15px;`'>
				<input type='checkbox' name='nonadd' id='nonadd' v-model='labels_address_check'>
				<label for='nonadd' id='address_disp' class='item_101' :style='labels_address_style' onclick='gio_onoff()'>{{vjusho}}</label>
			</div>
		</header>
		<div class='header-select header-color' >
			<select v-model='get_scroll_target' @change='scroller(this)' class='form-control item_16' style='font-size:1.2rem;padding:0;'> 
				<option value='description'>カテゴリートップへ移動できます</option>
				<option value='#jump_0'>TOP</option>
				<template v-for='(list,index) in shouhinMS_filter' :key='list.shouhinCD'>
					<template v-if='index===0 && list.disp_category!==""'>
						<option :value='`#jump_${index}`'>
							{{list.disp_category}}
						</option>
					</template>
					<template v-if='index!==0 && list.disp_category !== shouhinMS_filter[index-1].disp_category'>
						<option :value='`#jump_${index}`'>
							{{list.disp_category}}
						</option>
					</template>
				</template>
			</select>
			<a href="#" style='color:inherit;margin-left:10px;margin-right:10px;margin-top:5px;' data-bs-toggle='modal' data-bs-target='#modal_help1'>
				<i class="fa-regular fa-circle-question fa-lg logoff-color"></i>
			</a>
			
			<a class='item_15' href='javascript:void(0)' @Click='panel_changer()' style='color:inherit;margin-left:10px;margin-right:10px;margin-top:5px;'>
				<i class='fa-solid fa-arrow-rotate-right fa-lg logoff-color'></i>
			</a>
		</div>
		<div class='header-plus-minus d-flex justify-content-center align-items-center item_4' style='font-size:1.4rem;font-weight:700;'>
			<span style='position:fixed;top:95px;left:10px;' id='gio_exec'></span><!--開発モードでGET_GIO実行時の通知に使用-->
			<i class="fa-regular fa-circle-question fa-lg logoff-color"></i><!--スペーシングのため白アイコンを表示-->
			<div style='padding:0;'>
				<input type='radio' class='btn-check' name='options' value='plus' autocomplete='off' v-model='pm' id='plus_mode' checked>
				<label class='btn btn-outline-primary' for='plus_mode'>　▲　</label>
				<input type='radio' class='btn-check' name='options' value='minus' autocomplete='off' v-model='pm' id='minus_mode' >
				<label class='btn btn-outline-warning' for='minus_mode'>　▼　</label>
			</div>
			<a href="#" style='color:inherit;margin-left:5px;' data-bs-toggle='modal' data-bs-target='#modal_help2'>
				<i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i>
			</a>
			<a href="#" style='color:inherit;position:fixed;top:110px;right:10px;' data-bs-toggle='modal' data-bs-target='#modal_uriagelist'>
				<i class="fa-solid fa-cash-register fa-2x awesome-color-panel-border-same"></i>
			</a>
		</div>
		<main class='common_body'>
			<div class="container-fluid">
				<template v-if='MSG!==""'>
					<div v-bind:class='alert_status' role='alert'>{{MSG}}</div>
				</template>
				<div class='accordion item_11 item_12' id="accordionExample">
					<div v-if='chk_register_show==="register"' class='row' style='padding-top:5px;'>
						<hr>
						<div class='accordion-item'>
							<h2 class='accordion-header' id='headingOne'>
								<button type='button' class='accordion-button collapsed' style='font-size:2.2rem;' data-bs-toggle='collapse' data-bs-target='#collapseOne' aria-expanded='false' aria-controls='collapseOne'>
									割引・割増
								</button>
							</h2>
							<div id='collapseOne' class='accordion-collapse collapse' aria-labelledby='headingOne' data-bs-parent='#accordionExample'>
					      <div class='accordion-body'>
									<div class='row'>
										<div class='col-1 col-md-0' ></div>
										<div class='col-10 col-md-7' >
											<p>お会計額：￥{{pay}}</p>
											<label for='CHOUSEI_GAKU' class='form-label'>変更後お会計額</label>
											<input type='number' class='form-control order tanka' 
											style='font-size:2.2rem;width:100%;border:solid;border-top:none;border-right:none;border-left:none;' name='CHOUSEI_GAKU' id='CHOUSEI_GAKU'>
										<br>
										</div>
										<div class='col-1' ></div>
									</div>
      					</div>
    					</div>
						</div>
					</div>
				</div><!--割引処理-->
				<div id='jump_0'><hr ></div>
				<div class='row item_3' id=''>
					<template v-for='(list,index) in shouhinMS_filter' :key='list.shouhinCD'>
						<template v-if='index===0'>
							<div class='row' style='background:var(--jumpbar-color);margin-top:5px;' >
								<div class='col-12' :id='`jump_${index}`' style='color:var(--categ-font-color);'><a href='#jump_".$befor."' class='btn-updown'><i class='fa-solid fa-angles-up'></i></a>
									{{list.disp_category}}<a href='#jump_".$next."'  class='btn-updown'><i class='fa-solid fa-angles-down'></i></a>
								</div>
							</div>
						</template>
						<template v-if='index!==0 && list.disp_category !== shouhinMS_filter[index-1].disp_category'>
							<div class='row' style='background:var(--jumpbar-color);margin-top:5px;' >
								<div class='col-12' :id='`jump_${index}`' style='color:var(--categ-font-color);'><a href='#jump_".$befor."' class='btn-updown'><i class='fa-solid fa-angles-up'></i></a>
									{{list.disp_category}}<a href='#jump_".$next."'  class='btn-updown'><i class='fa-solid fa-angles-down'></i></a>
								</div>
							</div>
						</template>
						<div class ='col-md-3 col-sm-6 col-6 items'>
							<button type='button' @click="ordercounter" class='btn-view btn--rezi' :id="`btn_menu_${list.shouhinCD}`" :value = "index">{{list.shouhinNM}}
							</button>
							<div v-if='pm==="minus"' class='btn-view btn--rezi-minus bg-warning minus_disp'></div>
							<input type='hidden' :name ="`ORDERS[${index}][CD]`" :value = "list.shouhinCD">
							<input type='hidden' :name ="`ORDERS[${index}][NM]`" :value = "list.shouhinNM">
							<input type='hidden' :name ="`ORDERS[${index}][UTISU]`" :value = "list.utisu">
							<input type='hidden' :name ="`ORDERS[${index}][ZEIKBN]`" :value = "list.zeiKBN">
							<input type='hidden' :name ="`ORDERS[${index}][TANKA]`" :value = "list.tanka">
							<input type='hidden' :name ="`ORDERS[${index}][ZEI]`" :value = "list.tanka_zei">  
							<input type='hidden' :name ="`ORDERS[${index}][GENKA_TANKA]`" :value = "list.genka_tanka">
							<div class ='ordered'>
									￥<input type='number' readonly='readonly' class='order tanka' :value="list.zeikomigaku">
									× <input type='number' v-model='list.ordercounter' readonly='readonly' :name ="`ORDERS[${index}][SU]`" :id="`suryou_${list.shouhinCD}`" class='order su' style='display:inline;'>
							</div>
						</div>
					</template>
				</div>
			</div><!--オーダーパネル部分-->
		</main>
		<footer class='rezfooter'>
			<div class="container-fluid" style='padding:0;text-align:center;'>
				<div class='row'>
					<div class='col-12 kaikei'>
						<span style='font-size:1.6rem;'>お会計</span> ￥<span id='kaikei'> {{ pay }} </span>- <span style='font-size:1.6rem;'>内税</span>(<span id='utizei'>{{kaikei_zei}}</span>)
					</div>
				</div>
				<div class='row' style='height:60px;'>
					<div class='col-4' style='padding:0;'>
						<button type='button' class='btn--chk item_5' style='border-left:none;border-right:none;' id='dentaku' data-bs-toggle='modal' data-bs-target='#FcModal'>
							{{labels['btn_name']}}
						</button>
					</div>
					<div class='col-4 item_10' style='padding:0;'>
						<button type='button' @click='reset_order()' v-if='chk_register_show==="chk"' class='btn--chk item_8'>クリア</button><!-- id='order_clear'-->
						<button type='button' @click='btn_changer("chk")' v-if='chk_register_show==="register"' class='btn--chk '>戻　る</button><!-- id='order_return'-->
					</div>
					<div class='col-4 item_9' style='padding:0;'>
						<button type='submit' v-if='chk_register_show==="register"' class='btn--commit item_13' style='border-left:none;border-right:none;' name='commit_btn' value='uriage_commit'>登　録</button>
						<button type='button' @click='btn_changer("register")' v-if='chk_register_show==="chk"' class='btn--chk ' style='border-left:none;border-right:none;' >確　認</button><!--id='order_chk'-->
					</div>
				</div>
			</div>
		</footer>
		<input type='hidden' name='lat' :value='vlat'>
		<input type='hidden' name='lon' :value='vlon'>
	</form>
	<div class="loader-wrap" v-show='loader'>
		<div class="loader">Loading...</div>
	</div>
	<!--モーダル電卓(FcModal)-->
	<div class='modal fade' id='FcModal' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content item_6' style='font-size: 3.0rem; font-weight: 800;'>
				<div class='modal-header'>
					<!--<div class='modal-title' id='myModalLabel'>電　卓</div>-->
				</div>
				<div class='modal-body'>
					<!--電卓-->
					<table style='margin:auto;width:86%'>
						<tbody>
						<tr><td colspan='3' style='text-align:center;background:lightgreen;color:#fff;font-size:2.0rem;font-weight:600;'>計　算</td></tr>
						<tr><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>7</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>8</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>9</button></td></tr>
						<tr><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>4</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>5</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>6</button></td></tr>
						<tr><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>1</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>2</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>3</button></td></tr>
						<tr><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>0</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>00</button></td><td class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>C</button></td></tr>
						<tr><td colspan='3' class='dentaku--cellsize'><button class='btn btn-primary btn--dentaku' @click='keydown'>ちょうど</button></td></tr>
						</tbody>
					</table>
					<div style='margin:0 7%'>
					<input type='hidden' id='azukari_val'>
					<input type='hidden' id='oturi_val'>

					<p>お預り：￥<span id='azukari'>{{deposit}}</span></p>
					<p>お会計：￥<span id='seikyuu'>{{ pay }}</span></p>
					<p>お釣り：￥<span id='oturi'>{{oturi}}</span></p>
					</div>
				<div class='modal-footer'>
					<button type='button'  class='item_7 btn btn-primary' data-bs-dismiss='modal' style='font-size: 2.0rem;width:100%;'>閉じる</button>
				</div>
			</div>
		</div>
		</div>
	</div>
	<!--売上リスト-->
	<div class='modal fade' id='modal_uriagelist' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered' >
			<div class='modal-content' style='font-size:1.2rem; font-weight:400;'>
				<div class='modal-header'>
					<div class='container'><div class='row'><div class='col-12'><div style='padding-top:5px;text-align:center;font-size:1.5rem;font-weight:600;' class='lead alert-success'>本日の売上</div></div></div></div>
				</div>
				<div class='modal-body'>
					<div class='urilist'>
					<table class="table table-sm" style='font-family:"Meiryo UI";'>
						<thead class='header-color' style='color:var(--title-color);'>
							<tr>
								<th>No</th>
								<th>時刻</th>
								<th>商品</th>
								<th>数量</th>
								<th>税込売上</th>
							</tr>
						</thead>
						<tbody >
							<template v-for='(row,index) in UriageList' :Key='row.UriageNO+row.ShouhinCD'>
								<template v-if='row.UriageNO===row.lastNo'>
									<tr class='table-success'>
										<td>{{row.UriageNO}}</td><td>{{row.insDatetime}}</td><td>{{row.ShouhinNM}}</td><td>{{row.su}}</td><td>{{row.ZeikomiUriage}}</td>
									</tr>
								</template>
								<template v-if='row.UriageNO!==row.lastNo'>
									<tr>
										<td>{{row.UriageNO}}</td><td>{{row.insDatetime}}</td><td>{{row.ShouhinNM}}</td><td>{{row.su}}</td><td>{{row.ZeikomiUriage}}</td>
									</tr>
								</template>	
							</template>
						</tbody>
					</table>
					</div>
				</div>
				<div class='modal-footer' style='font-size:2.5rem;font-weight:600;'>
					合計：{{total_uriage}} 円
				</div>
			</div>
		</div>
	</div>
	<!--加算減算モードのヘルプ(modal_help2)-->
	<div class='modal fade' id='modal_help2' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content' style='font-size: 1.5rem; font-weight: 600;'>
				<div class='modal-header'>
					<!--<div class='modal-title' id='myModalLabel'>電　卓</div>-->
				</div>
				<div class='modal-body'>
					<input type='radio' class='btn-check' name='options1' value='minus' autocomplete='off' v-model='pm' id='minus_mode' checked>
					<label class='btn btn-outline-warning' for='minus_mode'>　▼　</label>
					をタップすると、注文数を減らせるようになります。<br>
					<input type='radio' class='btn-check' name='options2' value='plus' autocomplete='off' v-model='pm' id='plus_mode' checked>
					<label class='btn btn-outline-primary' for='plus_mode'>　▲　</label>

					をタップすると元に戻ります。<br>
				</div>
				<div class='modal-footer'>
					<!--<button type='button' class='btn btn-default' data-dismiss='modal'>閉じる</button>-->
					<button type='button'  data-bs-dismiss='modal'>閉じる</button>
				</div>
			</div>
		</div>
	</div>
	<!--分類表示切替のヘルプ(modal_help1)-->
	<div class='modal fade' id='modal_help1' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content' style='font-size: 1.5rem; font-weight: 600;'>
				<div class='modal-header'>
					<!--<div class='modal-title' id='myModalLabel'>電　卓</div>-->
				</div>
				<div class='modal-body text-center'>
					<i class="fa-solid fa-arrow-rotate-right fa-lg"></i> をタップすると、商品登録時に設定した分類ごとにパネルが表示されます。<br>
					タップするごとに（大分類⇒中分類⇒小分類⇒50音順）の順番でループします。<br>
				</div>
				<div class='modal-footer'>
					<button type='button'  data-bs-dismiss='modal'>閉じる</button>
				</div>
			</div>
		</div>
	</div>

	</div><!-- <div  id='register'> -->
	<script>
		const { createApp, ref, onMounted, computed, VueCookies } = Vue;
		createApp({
			setup(){
				//スクロールスムース
				const get_scroll_target = ref('description')
				
				const scroller = (target_id) => {
					console.log('scroller start')
					let itemHeight
					const target_elem = document.querySelector(get_scroll_target.value)
					itemHeight = target_elem.getBoundingClientRect().top + window.pageYOffset - 170
					
					scrollTo(0, itemHeight);
				}

				//売上取得関連
				const UriageList = ref([])		//売上リスト
				const get_UriageList = () => {//売上リスト取得ajax
					console.log("get_UriageList start");
					let params = new URLSearchParams();
					params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
					axios
					.post('ajax_get_Uriage.php',params)
					.then((response) => (UriageList.value = [...response.data]
															//,console.log('get_UriageList succsess')
															))
					.catch((error) => console.log(`get_UriageList ERROR:${error}`));
				}//売上リスト取得ajax

				//商品マスタ取得関連
				const shouhinMS = ref([])			//商品マスタ
				const disp_category = ref(4)		//パネルの分類別表示設定変更用

				const get_shouhinMS = () => {//商品マスタ取得ajax
					console.log("get_shouhinMS start");
					let params = new URLSearchParams();
					params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
					axios
					.post('ajax_get_ShouhinMS.php',params)
					.then((response) => (shouhinMS.value = [...response.data]
															//,console.log('get_shouhinMS succsess')
															))
					.catch((error) => console.log(`get_shouhinMS ERROR:${error}`));
				}//商品マスタ取得ajax

				const total_uriage = computed(() =>{
					let sum_uriage = 0
					UriageList.value.forEach((list) => {
						sum_uriage += Number(list.ZeikomiUriage)
					})
					return sum_uriage
				})
 
				const shouhinMS_filter = computed(() => {//商品パネルのソート・フィルタ
					let order_panel = ([])
					if (chk_register_show.value === "chk"){//表示対象のみを返す
						order_panel = shouhinMS.value.filter((shouhin) => {
							return (shouhin.hyoujiKBN1.includes('on') );
						});
					}else if(chk_register_show.value === "register"){//表示対象かつ注文数１以上を返す
						order_panel = shouhinMS.value.filter((shouhin) => {
							return (shouhin.hyoujiKBN1.includes('on') && shouhin.ordercounter > 0);
						});
					}

					//カテゴリーグループ,税込み額の追加
					order_panel.forEach((list)=> {
						if(disp_category.value===1){
							list['disp_category'] = list.category1
						}else if(disp_category.value===2){
							list['disp_category'] = list.category12
						}else if(disp_category.value===3){
							list['disp_category'] = list.category123
						}else {
							list['disp_category'] = ''
						}
						list['zeikomigaku']=Number(list.tanka) + Number(list.tanka_zei)
					})

					return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
						return (a.category > b.category?1:-1)
						return (a.shouhinNM > b.shouhinNM?1:-1)
						return 0
					})
				})//商品パネルのソート・フィルタ

				const panel_changer = () => {
					if(disp_category.value >= 4){
						disp_category.value=1
					}else{
						disp_category.value ++
					}
				}

				onMounted(() => {
					console.log('onMounted')
					get_shouhinMS()
					get_UriageList()
					v_get_gio()
				})

				//オーダー処理関連
				const pay = ref(0)		//会計税込金額
				const kaikei_zei = ref(0)		//会計消費税
				const chk_register_show = ref('chk')		//確認・登録ボタンの表示
				const btn_changer = (args) => {
					chk_register_show.value = args
				}
				const pm = ref('plus')
				const ordercounter = (e) => {//注文増減ボタン
					//console.log(e.target.value)
					//console.log(shouhinMS_filter.value[e.target.value])
					let index = e.target.value
					
					if(pm.value==="plus"){
						shouhinMS_filter.value[index].ordercounter ++
						pay.value = Number(pay.value) + Number(shouhinMS_filter.value[index].tanka) + Number(shouhinMS_filter.value[index].tanka_zei)
						kaikei_zei.value = Number(kaikei_zei.value) + Number(shouhinMS_filter.value[index].tanka_zei)
					}else if(pm.value==="minus"){
						if(shouhinMS_filter.value[index].ordercounter - 1 < 0){
							alert("注文数は０以下にはできません。")
							return 0
						}
						shouhinMS_filter.value[index].ordercounter --
						pay.value = Number(pay.value) - Number(shouhinMS_filter.value[index].tanka) - Number(shouhinMS_filter.value[index].tanka_zei)
						kaikei_zei.value = Number(kaikei_zei.value) - Number(shouhinMS_filter.value[index].tanka_zei)
					}
					return 0
				}
				const reset_order = () => {//オーダーリセット
					shouhinMS_filter.value.forEach((list)=>list.ordercounter=0)
					pay.value = 0
					kaikei_zei.value = 0
				}
				
				const alert_status = ref(['alert'])
				const MSG = ref('')
				const loader = ref(false)
				const csrf = ref('<?php echo $token; ?>')

				const on_submit = (e) => {//登録・submit/
					console.log('on_submit start')
					loader.value = true
					v_get_gio()	//住所再取得
					//console.log(e.target)
					let form_data = new FormData(e.target)
					let params = new URLSearchParams (form_data)
					
					axios
						.post('ajax_EVregi_sql.php',params,{timeout: <?php echo $timeout; ?>}) //php側は15秒でタイムアウト
						.then((response) => {
							console.log(`on_submit SUCCESS`)
							//console.log(response.data)
							MSG.value = response.data[0].EMSG
							alert_status.value[1]=response.data[0].status
							csrf.value = response.data[0].csrf_create

							if(response.data[0].status==='alert-success'){
								reset_order()
								btn_changer('chk')
							}
						})
						.catch((error) => {
							console.log(`on_submit ERROR:${error}`)
							MSG.value = error.response.data[0].EMSG
							csrf.value = error.response.data[0].csrf_create
							alert_status.value[1]='alert-danger'
						})
						.finally(()=>{
							get_UriageList()
							loader.value = false
						})
				}

				//電卓処理関連
				const deposit = ref(0)
				const oturi = computed(() =>{//おつりの計算
					return Number(deposit.value) - Number(pay.value)
				})
				const keydown = (e) => {//電卓ボタンの処理
					//console.log(e.target.innerHTML)
					if(e.target.innerHTML==="C"){
						deposit.value = 0
					}else if(e.target.innerHTML==="ちょうど"){
						deposit.value = pay.value
					}else{
						deposit.value = Number(deposit.value.toString() + e.target.innerHTML.toString())
					}
				}
				
				//Gioコーディング
				const vlat = ref('')		//緯度
				const vlon = ref('')		//経度
				const vjusho = ref('')
				const v_get_gio = () =>{//緯度経度取得
					console.log('get_gio start')
					let address = []

					navigator.geolocation.getCurrentPosition(
						geoLoc => {
							vlat.value = geoLoc.coords.latitude
							vlon.value = geoLoc.coords.longitude

							axios
							.get('https://mreversegeocoder.gsi.go.jp/reverse-geocoder/LonLatToAddress',{params:{lat:geoLoc.coords.latitude,lon:geoLoc.coords.longitude}})
							.then((response) => (
								address = response.data.results
								//,console.log(address)
								// 変換表から都道府県などを取得
								,muniData = GSI.MUNI_ARRAY[address.muniCd]
								
								// 都道府県コード,都道府県名,市区町村コード,市区町村名 に分割
								,[prefCode, pref, muniCode, city] = muniData.split(',')
								//${pref}${city}${data.lv01Nm}->県・市区町村・番地
								,vjusho.value = (`${city}${address.lv01Nm}`).replace(/\s+/g, "")
								//,jusho_es = escape(jusho.replace(/\s+/g, ""))								
								))
							.catch((error) => console.log(`get_gio ERROR:${error}`))
						},
						err => console.error({err}),
					);

				}

				//細かな表示設定など
				const rg_mode = ref('<?php echo $RG_MODE; ?>')	//レジモード
				const labels_address_check = ref()
				const labels = computed(() =>{
					//let labels = []
					let rtn_labels = {}
					if(rg_mode.value !== 'shuppin_zaiko'){
						rtn_labels={date_type:"売上日",date_ini:'<?php echo (string)date("Y-m-d");?>',btn_name:'釣　銭'}
					}else{
						rtn_labels={date_type:"出店日",date_ini:'',btn_name:''}
					}
					if(rg_mode.value === 'kobetu'){
						rtn_labels.EV_input_name='KOKYAKU'
						rtn_labels.EV_input_hidden='EV'
						rtn_labels.EV_input_placeholder='顧客名'
						rtn_labels.EV_input_value=''
					}else{
						rtn_labels.EV_input_name='EV'
						rtn_labels.EV_input_hidden='KOKYAKU'
						rtn_labels.EV_input_placeholder='イベント名等'
						rtn_labels.EV_input_value='<?php echo $event; ?>'
					}
					if(rg_mode.value !== 'evrez'){
						rtn_labels.address='display:none'
						labels_address_check.value = true
					}else{
						rtn_labels.address=''
						labels_address_check.value = false
					}
					return rtn_labels
				})

				const labels_address_style = computed(() =>{
					if(labels_address_check.value===true){
						return 'text-decoration:line-through;'
					}else{
						return ''
					}
				})

				return{
					get_shouhinMS,
					shouhinMS_filter,
					ordercounter,
					pay,
					kaikei_zei,
					pm,
					deposit,
					oturi,
					keydown,
					chk_register_show,
					btn_changer,
					reset_order,
					on_submit,
					alert_status,
					MSG,
					loader,
					csrf,
					get_UriageList,
					UriageList,
					disp_category,
					panel_changer,
					total_uriage,
					vlat,
					vlon,
					vjusho,
					v_get_gio,
					rg_mode,
					labels,
					labels_address_style,
					labels_address_check,
					scroller,
					get_scroll_target,
				}
			}
		}).mount('#register');

	</script><!--Vue3-->
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
</body>

<!--シェパードナビ
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php require "ajax_func_tourFinish.php";?>
<script>//チュートリアル
	
	const TourMilestone = '<?php echo $_SESSION["tour"];?>';
	
	const tutorial_7 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_7'
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>売上計上日はここで変更します。<br><br>過去の売上を入れ忘れた場合、ここの日付を変更して売上登録をして下さい。</p>`,
		attachTo: {
			element: '.item_1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>出店しているイベント名を入力します。<br><br>今回は適当に入れてください。</p>`,
		attachTo: {
			element: '.item_2',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>大まかな出店場所を表示してます。<br><br>端末のGPS機能を使用してます。<br>明らかに変な住所が表示されている場合はチェックを入れて無効にしてください。</p>`,
		attachTo: {
			element: '.item_101',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>お会計はメニューをタップした数だけカウントされます。<br><br>試しに何回かタップしてみてください。</p>`,
		attachTo: {
			element: '.item_3',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
				<input type='radio' class='btn-check' name='options' value='minus' autocomplete='off' v-model='pm' id='minus_mode' >
				<label class='btn btn-outline-warning' for='minus_mode'>　▼　</label>
				<br><p class='tour_discription'>を選択すると、メニュータップ時にマイナスされるようになります。</p>`,
		attachTo: {
			element: '.item_4',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
				<input type='radio' class='btn-check' name='options' value='plus' autocomplete='off' v-model='pm' id='plus_mode' checked>
				<label class='btn btn-outline-primary' for='plus_mode'>　▲　</label>
				<br><p class='tour_discription'>を選択すると、元に戻ります。</p>`,
		attachTo: {
			element: '.item_4',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>お釣り計算機が表示されます。<!--<br><br>釣銭ボタンを押して表示してみてください。--></p>`,
		attachTo: {
			element: '.item_5',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>受取金額を入力して「計算」ボタンを押すとお釣りが表示されます。<br><br>ここでは計算するだけで、何も登録されません。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>再度「釣銭」ボタンを押すと計算機が非表示になります。<!--<br><br>釣銭ボタンを何度か押してしてみてください。--></p>`,
		attachTo: {
			element: '.item_5',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「クリア」ボタンを押すと、全ての数量を０にクリアします。</p>`,
		attachTo: {
			element: '.item_8',
			on: 'top'
		},
		buttons: [
			{
				text: 'back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「確認」ボタンを押すと、注文したメニューのみが表示されます。<br><br>この状態で注文結果の最終確認を行います。<br><br>ボタン名が「確認」から「登録」に変更されます。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「確認」ボタンを押して下さい。</p>`,
		attachTo: {
			element: '.item_9',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>注文内容を修正したい場合は「戻る」ボタンを押すと、ひとつ前の状態に戻ります。
				<br>表示が「クリア」のままの場合、「Back」をタップして「確認」を押してください。
				<br><br><span style='color:red;'>今回は「戻る」は押さずに「NEXT」をタップしてください。</span></p>`,
		attachTo: {
			element: '.item_10',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>次に、「割引・割増」について説明します。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「割引・割増」ボタンをタップしてください。
				<br>
				<br>ボタンが表示されていない場合、「Back」ボタンで前に戻り、「確認」ボタンを押してください。</p>`,
		attachTo: {
			element: '.item_11',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>まとめ買いに対する割引や、サービス時間超過に対する追加料金等で<span style='color:red;'>『支払総額』を変更したい場合</span>に使用します。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>割引割増を使用すると、売上げの実績は以下の表に登録されます。
				<br>
				<br>例：割引（3,300円を3,000円）<br>商品A：1,100円<br>商品B：1,100円<br>商品C：1,100円<br><span style='color:red;'>割引：-300円</span></p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「変更後の金額を入力」をタップし、割引後・割増後の総額を入力すると、下のお会計額が変更されます。</p>`,
		attachTo: {
			element: '.item_12',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>最後に「登録」ボタンを押すと、売上げの登録が完了します。</p>`,
		attachTo: {
			element: '.item_9',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>いろいろ操作してみて最後に「登録」をタップしてください。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>売上が登録されると画面上部に緑色のメッセージバーが表示されます。
				<br>(しばらくすると自動で消えます。)</p>`,
		buttons: [
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>次に、レジ画面のカテゴリー別表示についてです。</p>`,
		buttons: [
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>商品登録時にカテゴリーを設定すると、設定したカテゴリーごとに商品を纏めて表示ます。</p>`,
		buttons: [
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>ここをタップするとカテゴリー別表示に変更されます。<br>試しにタップしてください。</p>`,
		attachTo: {
			element: '.item_15',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>カテゴリー別表示の場合、ここのリストにカテゴリーが表示されるようになります。<br>目的のカテゴリーを選択すると、その付近まで画面が自動でスライドするようになります</p>`,
		attachTo: {
			element: '.item_16',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'><i class="fa-solid fa-arrow-rotate-right fa-lg  awesome-color-panel-border-same"></i>をタップするごとにカテゴリーの粒度が「大→中→小→分別なし」の順で切り替わるので、ご自由に設定して下さい。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「商品登録～レジの使い方」までの説明は以上となります。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>次はレジで登録した売上げの確認に移ります。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.next
			}
		]
	});
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>画面上部の「WebRez＋」をタップしてメニュー画面に戻ってください。
				<br>
				<br><span style='font-size:1rem;color:green;'>※進捗を保存しました。</span>
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7.back
			},
			{
				text: 'Next',
				action: tutorial_7.complete
			}
		]
	});
	if(TourMilestone=="tutorial_4"){
		tutorial_7.start(tourFinish,'tutorial','');
	}
	
</script><!--チュートリアル-->
<script>
	//在庫機能のヘルプ
	const shuppin_zaiko_help2 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'shuppin_zaiko_help2'
	});
	shuppin_zaiko_help2.addStep({
		title: `<p class='tour_header'>出品在庫機能</p>`,
		text: `<p class='tour_discription'>在庫の登録画面はレジ画面とほぼ同じです。</p>`,
		buttons: [
			{
				text: 'Next',
				action: shuppin_zaiko_help2.next
			}
		]
	});
	shuppin_zaiko_help2.addStep({
		title: `<p class='tour_header'>出品在庫機能</p>`,
		text: `<p class='tour_discription'>イベント等の出店日を指定します。</p>`,
		attachTo: {
			element: '.item_1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Next',
				action: shuppin_zaiko_help2.next
			}
		]
	});
	shuppin_zaiko_help2.addStep({
		title: `<p class='tour_header'>出品在庫機能</p>`,
		text: `<p class='tour_discription'>出店予定のイベント名を入力します。</p>`,
		attachTo: {
			element: '.item_2',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: shuppin_zaiko_help2.back
			},
			{
				text: 'Next',
				action: shuppin_zaiko_help2.next
			}
		]
	});
	shuppin_zaiko_help2.addStep({
		title: `<p class='tour_header'>出品在庫機能</p>`,
		text: `<p class='tour_discription'>後は通常のレジと同じ要領で、商品名を出品予定の数だけタップして、「確認」⇒「登録」と進みます。</p>`,
		buttons: [
			{
				text: 'Back',
				action: shuppin_zaiko_help2.back
			},
			{
				text: 'Next',
				action: shuppin_zaiko_help2.next
			}
		]
	});
	shuppin_zaiko_help2.addStep({
		title: `<p class='tour_header'>出品在庫機能（修正について）</p>`,
		text: `<p class='tour_discription'>登録した内容が間違えていた場合、同じ日付・同じイベント名を入力し、全て打ち直して登録して下さい。
				<br>すると、前回登録した内容は削除され、今回入力した内容が反映されます。</p>`,
		buttons: [
			{
				text: 'Back',
				action: shuppin_zaiko_help2.back
			},
			{
				text: 'Next',
				action: shuppin_zaiko_help2.next
			}
		]
	});
	shuppin_zaiko_help2.addStep({
		title: `<p class='tour_header'>出品在庫機能（削除について）</p>`,
		text: `<p class='tour_discription'>登録した内容を削除したい場合、誤って登録した日付とイベント名を指定し、何も商品を選択せず、空で登録して下さい。</p>`,
		buttons: [
			{
				text: 'Back',
				action: shuppin_zaiko_help2.back
			},
			{
				text: 'Next',
				action: shuppin_zaiko_help2.next
			}
		]
	});
	shuppin_zaiko_help2.addStep({
		title: `<p class='tour_header'>出品在庫機能（登録結果について）</p>`,
		text: `<p class='tour_discription'>登録した内容は「売上実績」画面を「商品集計」モードで表示すると、「出品数」という項目で確認することが出来ます。</p>`,
		buttons: [
			{
				text: 'Back',
				action: shuppin_zaiko_help2.back
			},
			{
				text: 'Next',
				action: shuppin_zaiko_help2.next
			}
		]
	});
	shuppin_zaiko_help2.addStep({
		title: `<p class='tour_header'>出品在庫機能</p>`,
		text: `<p class='tour_discription'>出品在庫機能の説明は以上で終了です。</p>`,
		buttons: [
			{
				text: 'Back',
				action: shuppin_zaiko_help2.back
			},
			{
				text: 'complete',
				action: shuppin_zaiko_help2.complete
			}
		]
	});

	function help(){
		shuppin_zaiko_help2.start(tourFinish,'','');
	}
</script><!--出品在庫機能help-->
<script>
	//天気機能のリリース
	const new_releace_002 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'new_releace_002'
	});
	new_releace_002.addStep({
		title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
		text: `<p class='tour_discription'>大まかな出店場所を表示してます。<br><br>端末のGPS機能を使用してます。</p>`,
		attachTo: {
			element: '.item_101',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: new_releace_002.back
			},
			{
				text: 'Next',
				action: new_releace_002.next
			}
		]
	});
	new_releace_002.addStep({
		title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
		text: `<p class='tour_discription'>ここに表示されている住所から天気・気温を取得します。<br><br>明らかに変な住所が表示されている場合はチェックを入れて無効にしてください。</p>`,
		attachTo: {
			element: '.item_101',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: new_releace_002.back
			},
			{
				text: 'Next',
				action: new_releace_002.next
			}
		]
	});
	new_releace_002.addStep({
		title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
		text: `<p class='tour_discription'>次の売上から、『売上実績』の画面に売上時の天気、気温が表示されるようになります。</p>`,
		buttons: [
			{
				text: 'Back',
				action: new_releace_002.back
			},
			{
				text: 'Next',
				action: new_releace_002.next
			}
		]
	});
	new_releace_002.addStep({
		title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
		text: `<p class='tour_discription'>今回の追加機能は以上となります。</p>`,
		buttons: [
			{
				text: 'Back',
				action: new_releace_002.back
			},
			{
				text: 'OK',
				action: new_releace_002.next
			}
		]
	});
	 if(TourMilestone=="new_releace_002"){
		new_releace_002.start(tourFinish,'new_releace_002','finish');
	}

</script><!--天気機能リリースヘルプ（次回機能リリース時は不要となる）-->
<script>
	/*ジオ・コーディング*/

	//GIO機能のオンオフ説明
	const gio_on = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'gio_on'
	});
	gio_on.addStep({
		title: `<p class='tour_header'>位置情報</p>`,
		text: `<p class='tour_discription'>位置情報は有効です。
			<br>
			<br>現在地が正しくない場合、同じ場所を再度タップして無効にしてください。
			<br></p>`,
		buttons: [
			{
				text: 'OK',
				action: gio_on.next
			}
		]
	});
	const gio_off = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'gio_off'
	});
	gio_off.addStep({
		title: `<p class='tour_header'>位置情報</p>`,
		text: `<p class='tour_discription'>位置情報は無効です。
			<br>
			<br>現在地に問題が無い場合、同じ場所を再度タップして有効にしてください。
			<br></p>`,
		buttons: [
			{
				text: 'OK',
				action: gio_off.next
			}
		]
	});
	let gio_onoff = () => {
    if(address_disp.style.textDecoration=='line-through'){
      address_disp.style.textDecoration='';
      gio_on.start(tourFinish,'','');
    }else{
      address_disp.style.textDecoration='line-through';
      gio_off.start(tourFinish,'','');
    }
  }


</script><!--ジオコーディング-->
<script src="https://maps.gsi.go.jp/js/muni.js"></script><!--gio住所逆引リスト-->
</html>
<?php

$stmt = null;
$pdo_h = null;
?>



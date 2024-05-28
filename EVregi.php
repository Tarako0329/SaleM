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

	if($status==="login_redirect"){
		//ログイン画面から直通でアクセス
		log_writer2("EVregi.php  \$status ",$status,"lv3");
	}else{
		$rtn = csrf_checker(["menu.php"],["G","C"]);
		if($rtn !== true){
				redirect_to_login($rtn);
		}
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

	$RG_MODE=(!empty($_GET["mode"])?$_GET["mode"]:"");

	if($RG_MODE===""){
		redirect_to_login("error rezi mode nothing!");
	}

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
	</style>
</head>
<body>
	<div id='register'>
	<form method = 'post' id='form1' @submit.prevent='on_submit'>
		<input v-model='csrf' type='hidden' name='csrf_token' >
		<input v-model='rg_mode' type='hidden' name='mode'> <!--レジor個別売上or在庫登録-->
		<input type='hidden' :name='labels["EV_input_hidden"]' value=''>
	
		<header class='header-color common_header' style='display:block'>
			<div class='title yagou'><a href='menu.php'><?php echo TITLE;?></a></div>
			<span class='item_1'>
				<span style='color:var(--user-disp-color);font-weight:400;'>
					{{labels['date_type']}}
				</span>
				<input type='date' class='date' style='height:20%' name='KEIJOUBI' required='required' v-model='labels["date_ini"]'>
			</span>
			<input type='text' class='ev item_2' :name='labels["EV_input_name"]' v-model='labels["EV_input_value"]' required='required' :placeholder='labels["EV_input_placeholder"]'>
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
		<div class='header-plus-minus d-flex justify-content-center align-items-center item_4' style='font-size:1.4rem;font-weight:700;'><!--カート編集など-->
			<div class="form-check form-switch" style='position:fixed;top:105px;left:10px;padding:0;'>
				<p style='margin-bottom:2px;'>端数自動調整</p>
				<input type='checkbox' style='margin:0;' v-model='auto_ajust' class='form-check-input'  id='chousei'><!---->
				<label v-if='auto_ajust!==true' style='margin-left:3px;' class='form-check-label' for='chousei'>OFF</label><!---->
				<label v-if='auto_ajust===true' style='margin-left:3px;' class='form-check-label' for='chousei'>ON</label><!-- -->
			</div>
			<div v-if="cartbtn_show" style='padding:0;'>
				<button v-if='order_panel_show_flg===true' type='button' class='btn btn-primary' @click='order_panel_show("show")'>カート編集</button>
				<button v-if='order_panel_show_flg===false' type='button' class='btn btn-primary' @click='order_panel_show("close")'>戻る</button>
			</div>
			<a href="#" style='color:inherit;position:fixed;top:110px;right:10px;' data-bs-toggle='modal' data-bs-target='#modal_uriagelist' id='UriToday'>
				<i class="fa-solid fa-cash-register fa-2x awesome-color-panel-border-same"></i>
			</a>
		</div><!--カート編集など-->
		<div v-if='chk_register_show==="chk"' class='header-plus-minus d-flex justify-content-center align-items-center ' style='font-size:1.4rem;font-weight:700;top: 156px;height:52px;' id='tax_changer'><!--イートイン/テイクアウト-->
			<div style='padding:0;'>
				<input type='radio' class='btn-check' name='ZeiChange' value='10' autocomplete='off' v-model='ZeiChange' id='eatin'>
				<label class='btn btn-outline-danger ' for='eatin' style='border-radius:0;'>イートイン</label>
				<input type='radio' class='btn-check' name='ZeiChange' value='8' autocomplete='off' v-model='ZeiChange' id='takeout'>
				<label class='btn btn-outline-danger ' for='takeout' style='border-radius:0;'>テイクアウト</label>
				<input type='radio' class='btn-check' name='ZeiChange' value='0' autocomplete='off' v-model='ZeiChange' id='defo' >
				<label class='btn btn-outline-primary ' for='defo' style='border-radius:0;'>戻る</label>
			</div><!--イートイン/テイクアウト-->
		</div>
		<div v-if='chk_register_show==="register"' class='container header-plus-minus text-center' style='padding:10px;'><!--割引・割増-->
			<button type='button' style='width:80%;max-width:500px;' class='btn btn-outline-primary' data-bs-toggle='modal' data-bs-target='#waribiki' id='item_11'>割引・割増</button>
		</div><!--割引・割増-->
		<main class='common_body' id='main_area'>
			<?php 
				if(!empty($emsg)){
					echo $emsg;
					exit;
				}
			?>
			<div class="container-fluid">
				<div class='row'>

					<div class='col-lg-3 col-md-4 col-sm-12 col-12'><!--注文内容-->
						<div class='order_list' ref='order_list_area'>
							<div class='text-center'> 税込表示 </div>
							<template v-for='(list,index) in order_list' :key='list.CD'>
								<div class='container'>
									<div class='order_item'>{{list.NM}}</div>
									<div style='display:flex;'>
										<div class='order_su' >{{list.SU}}点</div>
										<div class='order_kin'>¥{{(list.SU * (list.TANKA + list.TANKA_ZEI)).toLocaleString()}}</div>
									</div>
									<div id='side-pm' style='display:flex;border-bottom:solid var(--panel-bd-color) 0.3px;padding-bottom:3px;position:relative;'>
										<button type='button' class='order_list_area_btn plus' @click='order_list_pm(index,1)'></button>
										<button type='button' class='order_list_area_btn minus' @click='order_list_pm(index,-1)'></button>
										<select v-model='list.ZEIKBN' @change='order_list_change_tax(index,$event)' class="form-select form-select-lg " style='margin-left:10px;padding-left:10px;position:absolute;right:3px;max-width:90px;'>
											<?php
												foreach($zeimaster as $row){
													echo "<option value=".secho($row["zeiKBN"]).">".secho($row["hyoujimei"])."</option>\n";
												}
											?> 
										</select>
									</div>
								</div>
								<input type='hidden' :name ="`ORDERS[${index}][CD]`" :value = "list.CD">
								<input type='hidden' :name ="`ORDERS[${index}][NM]`" :value = "list.NM">
								<input type='hidden' :name ="`ORDERS[${index}][UTISU]`" :value = "list.UTISU">
								<input type='hidden' :name ="`ORDERS[${index}][ZEIKBN]`" :value = "list.ZEIKBN">
								<input type='hidden' :name ="`ORDERS[${index}][TANKA]`" :value = "list.TANKA">
								<input type='hidden' :name ="`ORDERS[${index}][ZEI]`" :value = "list.TANKA_ZEI">  
								<input type='hidden' :name ="`ORDERS[${index}][GENKA_TANKA]`" :value = "list.GENKA_TANKA">
								<input type='hidden' :name ="`ORDERS[${index}][SU]`" :value = "list.SU">
							</template>	
							<template v-for='(list,index) in hontai' :key='list.CD'>
								<div class='container'>
									<template v-if='list.調整額<0'>
										<div class='order_item'>値引({{list.税区分名}}分)</div>
										<div style='display:flex;border-bottom:solid var(--panel-bd-color) 0.3px;padding-bottom:3px;'>
											<div class='order_su' >1点</div>
											<div class='order_kin text-danger'>¥{{(list.調整額 + list.税調整額).toLocaleString()}}</div>
										</div>
									</template>
									<template v-if='list.調整額>0'>
										<div class='order_item'>値増({{list.税区分名}}分)</div>text-danger
										<div style='display:flex;border-bottom:solid var(--panel-bd-color) 0.3px;padding-bottom:3px;'>
											<div class='order_su' >1点</div>
											<div class='order_kin'>¥{{(list.調整額 + list.税調整額).toLocaleString()}}</div>
										</div>
									</template>
								</div>
							</template>
						</div>
					</div><!--注文内容-->

					<div v-show='order_panel_show_flg' class='col-lg-9 col-md-8 col-sm-12 col-12'><!--オーダーパネル部分-->
						<div class="container-fluid">
							<template v-if='MSG!==""'><!--登録結果ステータス表示+領収書ボタン-->
								<div v-bind:class='alert_status' role='alert' id='msg_alert'>
									{{MSG}}
									<button type='button' class='btn btn-primary' @click='open_R()'> 
										領収書
									</button>
								</div>
							</template><!--登録結果ステータス表示+領収書ボタン-->
							<div id='jump_0'><hr></div> 
							<div class='row' id=''>
								<template v-for='(list,index) in shouhinMS_filter' :key='list.shouhinCD'>
									<template v-if='(index===0) || (index!==0 && list.disp_category !== shouhinMS_filter[index-1].disp_category)'><!--カテゴリーバー-->
										<div class='row' style='background:var(--jumpbar-color);margin-top:5px;' >
											<div class='col-12' :id='`jump_${index}`' style='color:var(--categ-font-color);'>
												<span class='btn-updown'><i class='fa-solid fa-angles-down'></i></span>
												{{list.disp_category}}
												<span class='btn-updown'><i class='fa-solid fa-angles-down'></i></span>
											</div>
										</div>
									</template><!--カテゴリーバー-->
									<div class ='col-lg-2 col-md-3 col-sm-6 col-6 items'>
										<template v-if='encodeURI(list.shouhinNM).replace(/%../g, "*").length <= 24'><!--メニュー文字数に応じてフォントサイズを変更-->
											<button type='button' @click="ordercounter($event)" class='btn-view btn--rezi' :id="`btn_menu_${list.shouhinCD}`" :value = "index">
												{{list.shouhinNM}}
											</button>
										</template>
										<template v-if='encodeURI(list.shouhinNM).replace(/%../g, "*").length > 24'><!--メニュー文字数に応じてフォントサイズを変更-->
											<button type='button' @click="ordercounter($event)" class='btn-view btn--rezi' style='font-size:1.2rem;' :id="`btn_menu_${list.shouhinCD}`" :value = "index">
												{{list.shouhinNM}}
											</button>
										</template>
										<div class='btn--rezi-tax text-right'>{{list.hyoujimei}}</div>
										<div class ='ordered'>
												￥{{list.zeikomigaku.toLocaleString()}} ×{{list.ordercounter.toLocaleString()}}
										</div>
									</div>
								</template>
							</div>
						</div>
					</div><!--オーダーパネル部分-->

				</div>
			</div>
		</main>
		<footer class='rezfooter'>
			<div class="container-fluid" style='padding:0;text-align:center;'>
				<div class='row'>
					<div class='col-12 kaikei' ref='total_area'>
						<span style='font-size:1.6rem;'>お会計</span> ￥<span id='kaikei'> {{pay.toLocaleString()}} </span>- 
						<span style='font-size:1.6rem;'>内税</span>(<span id='utizei'>{{kaikei_zei.toLocaleString()}}</span>)
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
		<div><!--hidden block-->
			<input type='hidden' name='lat' :value='vlat'>
			<input type='hidden' name='lon' :value='vlon'>
			<input type='hidden' name='weather' :value='weather'>
			<input type='hidden' name='description' :value='description'>
			<input type='hidden' name='temp' :value='temp'>
			<input type='hidden' name='feels_like' :value='feels_like'>
			<input type='hidden' name='icon' :value='icon'>
			<template v-for='(list,index) in hontai' :key='list.税率'>
				<input type='hidden' :name ="`ZeiKbnSummary[${index}][ZEIKBN]`" :value = "list.税区分">
				<input type='hidden' :name ="`ZeiKbnSummary[${index}][ZEIKBNMEI]`" :value = "list.税区分名">
				<input type='hidden' :name ="`ZeiKbnSummary[${index}][ZEIRITU]`" :value = "list.税率">
				<input type='hidden' :name ="`ZeiKbnSummary[${index}][CHOUSEIGAKU]`" :value = "list.調整額">
				<input type='hidden' :name ="`ZeiKbnSummary[${index}][HONTAIGAKU]`" :value = "list.本体額">
				<input type='hidden' :name ="`ZeiKbnSummary[${index}][SHOUHIZEI]`" :value = "list.消費税">
				<input type='hidden' :name ="`ZeiKbnSummary[${index}][ZEICHOUSEIGAKU]`" :value = "list.税調整額">
			</template>
			<template v-for='(list,index) in order_list' :key='list.CD'>
				<input type='hidden' :name ="`OrderList[${index}][CD]`" :value = "list.CD">
				<input type='hidden' :name ="`OrderList[${index}][NM]`" :value = "list.NM">
			</template>
		</div>
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
					<template v-if='Revised_pay===""'><p>お会計：￥<span id='seikyuu'>{{ pay }}</span></p></template>
					<template v-if='Revised_pay!==""'><p>お会計：￥<span id='seikyuu'>{{ Revised_pay }}</span></p></template>
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
								<th>時刻</th>
								<th>商品</th>
								<th>数量</th>
								<th>売上金額</th>
							</tr>
						</thead>
						<tbody >
							<template v-for='(row,index) in UriageList' :Key='row.UriageNO+row.ShouhinCD'>
								<template v-if='row.UriageNO===row.lastNo'>
									<template v-if='index===0'>
										<tr class='table-success'>
											<td colspan='4'><a href="#" style='color:inherit;' @click='open_R(row.URL)'>売上No：{{row.UriageNO}}</a></td>
										</tr>
									</template>
									<tr class='table-success'>
										<td>{{row.insDatetime.slice(-8)}}</td><td>{{row.ShouhinNM}}</td><td align='right'>{{row.su}}</td><td align='right'>{{Number(row.ZeikomiUriage).toLocaleString()}}</td>
									</tr>
								</template>
								
								<template v-if='row.UriageNO!==row.lastNo'>
									<template v-if='UriageList[index-1].UriageNO!==row.UriageNO'>
										<tr>
											<td colspan='4'><a href="#" style='color:inherit;' @click='open_R(row.URL)'>売上No：{{row.UriageNO}}</a></td>
										</tr>
									</template>
									<tr>
										<td>{{row.insDatetime.slice(-8)}}</td><td>{{row.ShouhinNM}}</td><td align='right'>{{row.su}}</td><td align='right'>{{Number(row.ZeikomiUriage).toLocaleString()}}</td>
									</tr>
								</template>	

							</template>
						</tbody>
					</table>
					</div>
				</div>
				<div class='modal-footer' style='font-size:2.5rem;font-weight:600;'>
					合計：{{Number(total_uriage).toLocaleString()}} 円
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
	<!--領収書-->
	<div class='modal fade' id='ryoushuu' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content' style='font-size: 2rem; font-weight: 600;'>
				<div class='modal-header'>
					<div class='modal-title' id='myModalLabel' style='text-align:center;width:100%;'>領収書発行</div>
				</div>
				<div class='modal-body text-center'>
					<label for='oaite' class='form-label'>宛名：</label>
					<input type='text' class='form-control' id='oaite' v-model='oaite' style='font-size: 2rem;'>
					
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
					<button type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1' @click='QRout()'><i class="bi bi-qr-code"></i></button>
					<button type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1' @click='prv()'><i class="bi bi-display"></i></button>
					<a :href='`https://line.me/R/share?text=${send_msg}`' type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1'>
						<i class="bi bi-line line-green"></i>
					</a>
					<!--<a :href='DL_URL' download='RyoushuuSho.pdf' type='button' style='font-size: 2rem;' class='btn btn-outline-primary'>
						<i class="bi bi-download"></i>
					</a>-->
				</div>
			</div>
		</div>
	</div>
	<!--割引画面-->
	<div class='modal fade' id='waribiki' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content' style='font-size: 2rem; font-weight: 600;' id='item_12'>
				<div class='modal-header'>
					<div class='modal-title' id='myModalLabel' style='text-align:center;width:100%;'>割引・割増</div>
				</div>
				<div class='modal-body'>
					<div class='row'>
						<input type='hidden' v-model='Revised_pay' name='CHOUSEI_GAKU' id='CHOUSEI_GAKU'>
						<div style='display:flexbox;font-size:2rem'>
							適用後 お会計額　￥{{Revised_pay.toLocaleString()}}
							<template v-if="CHOUSEI_TYPE==='paroff'">({{par.toLocaleString()}}% OFF)</template>
							<template v-if="CHOUSEI_TYPE==='paron'">({{par.toLocaleString()}}% ON)</template>
							<template v-if="CHOUSEI_TYPE==='zou'">　(+{{par.toLocaleString()}})</template>
							<template v-if="CHOUSEI_TYPE==='gen'">　(-{{par.toLocaleString()}})</template>
							<template v-if="CHOUSEI_TYPE==='sougaku'"></template>
						</div>
						<div style='padding:5px;width:100%;'>
							<input type='radio' class='btn-check' value='paroff' autocomplete='off' v-model='CHOUSEI_TYPE' id='paroff'>
							<label class='btn btn-outline-danger ' for='paroff' style='border-radius:0;width:25%;padding:1px 0;'>％OFF</label>
							<input type='radio' class='btn-check' value='paron' autocomplete='off' v-model='CHOUSEI_TYPE' id='paron'>
							<label class='btn btn-outline-danger ' for='paron' style='border-radius:0;width:25%;padding:1px 0;'>％ON</label>
							<input type='radio' class='btn-check' value='gen' autocomplete='off' v-model='CHOUSEI_TYPE' id='gen'>
							<label class='btn btn-outline-danger ' for='gen' style='border-radius:0;width:25%;padding:1px 0;'>値引</label>
							<input type='radio' class='btn-check' value='zou' autocomplete='off' v-model='CHOUSEI_TYPE' id='zou'>
							<label class='btn btn-outline-danger ' for='zou' style='border-radius:0;width:25%;padding:1px 0;'>値増</label>
							<input type='radio' class='btn-check' value='sougaku' autocomplete='off' v-model='CHOUSEI_TYPE' id='sougaku'>
							<label class='btn btn-outline-danger ' for='sougaku' style='border-radius:0;width:25%;padding:1px 0;'>金額指定</label>
						</div>
						<table style='margin:auto;width:86%'>
							<tbody>
							<tr><td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>7</button></td>
							<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>8</button></td>
							<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>9</button></td></tr>
							<tr><td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>4</button></td>
							<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>5</button></td>
							<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>6</button></td></tr>
							<tr><td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>1</button></td>
							<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>2</button></td>
							<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>3</button></td></tr>
							<tr><td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>0</button></td>
							<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>00</button></td>
							<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>C</button></td></tr>
							<tr v-if="CHOUSEI_TYPE!=='sougaku'">
								<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>＋</button></td>
							<td class='waribiki--cellsize'><button type='button' class='btn btn-primary btn--waribiki' @click='keydown_waribiki'>－</button></td>
							<td class='waribiki--cellsize'></td></tr>
							</tbody>
						</table>


					</div>
				</div>
				<div class='modal-footer'>
					<button class='btn btn-primary' type='button' data-bs-dismiss='modal' @click='Revised()'>決　定</button>
				</div>
			</div>
		</div>
	</div>

	</div><!-- <div  id='register'> -->
	<script src="EVregi_vue.js?<?php echo $time; ?>"></script>
	<script>
		REZ_APP('<?php echo $_SESSION["user_id"]."','".$timeout."','".$RG_MODE; ?>').mount('#register');

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
	
	let TourMilestone = '<?php echo $_SESSION["tour"];?>';
	
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
		text: `<p class='tour_discription'>お会計はメニューをタップした数だけカウントされます。</p>`,
		attachTo: {
			element: '#main_area',
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
		text: `<p class='tour_discription'>試しに何回かタップしてみてください。</p>`,
		attachTo: {
			element: '#main_area',
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
		text: `<p class='tour_discription'>数量減は「カート編集」ボタンでカートを表示し、「＋－」ボタンで調整します。</p>`,
		attachTo: {
			element: '#main_area',
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
		text: `<p class='tour_discription'>「戻る」ボタンでレジ画面に戻ってください。</p>`,
		attachTo: {
			element: '#main_area',
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
		text: `<p class='tour_discription'>このエリアで税率を変更できます。</p>`,
		attachTo: {
			element: '#tax_changer',
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
		text: `<p class='tour_discription'>
			全てのメニューに以下の税率が適用されます。
			<br>イートイン：１０％
			<br>テイクアウト：８％
			<br>戻る：商品登録時の税率
			<br>
			<br><span style='color:red'>８％にしてはいけない商品にも適用できてしまうのでご注意ください。</span>
			</p>`,
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
	/*
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
				<input type='radio' class='btn-check' name='options' value='minus' autocomplete='off' id='minus_mode' >
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
				<input type='radio' class='btn-check' name='options' value='plus' autocomplete='off' id='plus_mode' checked>
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
	*/
	tutorial_7.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>お釣り計算機が表示されます。<br><br>釣銭ボタンをタップしてください。</p>`,
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
		text: `<p class='tour_discription'>受取金額を入力して「計算」ボタンをタップするとお釣りが表示されます。<br><br>ここでは計算するだけで、何も登録されません。</p>`,
		attachTo: {
			element: '#main_area',
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
		text: `<p class='tour_discription'>閉じるボタン、もしくは計算機のエリア外をタップすると計算機は非表示になります。</p>`,
		attachTo: {
			element: '#main_area',
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
		text: `<p class='tour_discription'>「確認」ボタンを押すと、注文内容が表示され、「確認」ボタンが「登録」ボタンに変更されます。</p>`,
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
		text: `<p class='tour_discription'>適当に注文を入れ、「確認」ボタンを押して下さい。</p>`,
		attachTo: {
			element: '#main_area',
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
		text: `<p class='tour_discription'>注文済の個数を変更する場合は「＋－」ボタン、もしくは「戻る」ボタンで戻ってオーダーを追加します。</p>`,
		/*attachTo: {
			element: '.item_10',
			on: 'top'
		},*/
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
			element: '#item_11',
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
		text: 
			`<p class='tour_discription'>
			以下の方法で変更後金額を設定し、「決定」をタップすると支払総額が変更されます。
			<br>
			<br>・金額指定：変更後の支払総額を入力
			<br>・[%OFF][%ON]：増減する割合を指定
			<br>・[値引][値増]：増減する金額を指定
			</p>`,
		attachTo: {
			element: '#main_area',
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
		text: `<p class='tour_discription'>最後に「登録」ボタンを押すと、売上げの登録が完了します。</p>`,
		attachTo: {
			element: '#main_area',
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
				action: tutorial_7.complete
			}
		]
	});

	const tutorial_7_1 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_7_1'
	});

	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>売上が登録されると画面上部に緑色のメッセージバーが表示されます。</p>`,
		attachTo: {
			element: '#msg_alert',
			on: 'top'
		},
		buttons: [
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「領収書」ボタンをタップすると、領収書が発行できます。</p>`,
		attachTo: {
			element: '#msg_alert',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: 
		`<p class='tour_discription'>
		宛名欄・敬称を選択し、下記いずれかの方法でお客様に発行できます。
			<br><button type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1''><i class="bi bi-qr-code"></i></button>：お客様の携帯からQRコードを読取
			<br><button type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1'><i class="bi bi-display"></i></button>：プレピューを表示し、スマホの機能で印刷やメール転送など
			<br><button type='button' style='font-size: 2rem;' class='btn btn-outline-primary me-1'><i class="bi bi-line line-green"></i></button>：Lineで転送
		</p>`,
		attachTo: {
			element: '#main_area',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: 
		`<p class='tour_discription'>
		領収書発行エリアの外をタップすると、発行画面を閉じます。（タップして閉じてください）
		</p>`,
		attachTo: {
			element: '#main_area',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: 
		`<p class='tour_discription'>
		レジマークをタップすると本日の売上明細が表示されます。
		</p>`,
		attachTo: {
			element: '#UriToday',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: 
		`<p class='tour_discription'>
		レジマークをタップして下さい。
		</p>`,
		attachTo: {
			element: '#main_area',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: 
		`<p class='tour_discription'>
		売上明細に表示されている「売上No」をタップすると、該当の売上に対する領収書発行画面が開きます。
		</p>`,
		attachTo: {
			element: '#main_area',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: 
		`<p class='tour_discription'>
		それぞれ、エリア外をタップして閉じてください。
		</p>`,
		attachTo: {
			element: '#main_area',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});



	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>次に、レジ画面のカテゴリー別表示についてです。</p>`,
		buttons: [
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>商品登録時にカテゴリーを設定すると、設定したカテゴリーごとに商品を纏めて表示ます。</p>`,
		buttons: [
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>ここをタップするとカテゴリー別表示に変更されます。<br>試しにタップしてください。</p>`,
		attachTo: {
			element: '.item_15',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>カテゴリー別表示の場合、ここのリストにカテゴリーが表示されるようになります。<br>目的のカテゴリーを選択すると、その付近まで画面が自動でスライドするようになります</p>`,
		attachTo: {
			element: '.item_16',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'><i class="fa-solid fa-arrow-rotate-right fa-lg  awesome-color-panel-border-same"></i>をタップするごとにカテゴリーの粒度が「大→中→小→分別なし」の順で切り替わるので、ご自由に設定して下さい。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「商品登録～レジの使い方」までの説明は以上となります。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>次はレジで登録した売上げの確認に移ります。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.next
			}
		]
	});
	tutorial_7_1.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>画面上部の「WebRez＋」をタップしてメニュー画面に戻ってください。
				<br>
				<br><span style='font-size:1rem;color:green;'>※進捗を保存しました。</span>
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_7_1.back
			},
			{
				text: 'Next',
				action: tutorial_7_1.complete
			}
		]
	});
	if(TourMilestone=="tutorial_4"){
		tutorial_7.start(tourFinish,'tutorial','save');
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
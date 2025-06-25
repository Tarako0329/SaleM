<?php
	require "php_header.php";

	$rtn = csrf_checker(["shouhinMSList.php","shouhinMSList_sql.php","shouhinDEL_sql.php","menu.php"],["G","C","S"]);
	if($rtn !== true){
			redirect_to_login($rtn);
	}
	//セッションのIDがクリアされた場合の再取得処理。
	$rtn=check_session_userid($pdo_h);

	$csrf_create = csrf_create();
	//ユーザ情報取得
	$sql="select yuukoukigen,ZeiHasu from Users_webrez where uid=?";
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

	//端数処理設定
	$ZeiHasu = $row[0]["ZeiHasu"];


?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php"
	?>
	<!--ページ専用CSS-->
	<link rel='stylesheet' href='css/style_ShouhinMSL.css?<?php echo $time; ?>' >
	<TITLE><?php echo $title." 取扱商品 確認・編集";?></TITLE>
</head>
<BODY>
	<div id='app'>
		<header class='header-color common_header' style='flex-wrap:wrap'>
			<div class='title' style='width: 60%;'>
				<a href='menu.php'><?php echo $title;?></a>
			</div>
			<div class='right' style='width: 40%;padding-top:10px;display:flex;'>
				<div style='width: 20px;padding-top:5px;'><i class="fa-solid fa-magnifying-glass fa-2x logoff-color"></i></div>
				<div style='width:200px' ><input type='search' v-model='search_word' class='form-control' style='margin-left:5px;width:78%;max-width:200px;' placeholder='Search'></div>
				
			</div>
			<p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  取扱商品 確認・編集 画面</p>
			<?php if(empty($_SESSION["tour"])){?>
			<a href="#" style='color:inherit;position:fixed;top:40px;right:5px;' onclick='help()'><i class="bi bi-question-circle Qicon logoff-color"></i></a>
			<?php }?>
		</header>
		<div class='header2'>
			<div class='container-fluid'>
				<div style='padding:0 5px;' id='tax_inout'>
					<input type='radio' class='btn-check' name='options' value='IN' autocomplete='off' v-model='upd_zei_kominuki' id='plus_mode'>
					<label class='btn btn-outline-primary' style='font-size:1.2rem;border-radius:0;' for='plus_mode'>税込入力</label>
					<input type='radio' class='btn-check' name='options' value='NOTIN' autocomplete='off' v-model='upd_zei_kominuki' id='minus_mode'>
					<label class='btn btn-outline-primary' style='font-size:1.2rem;border-radius:0;' for='minus_mode'>税抜入力</label>
				</div>
				<div style='position:fixed;right:10px;top:75px;width:120px;display:block;'>
					<div>
						<select v-model='chk_register_show' class='form-select form-select-lg' id='disp_change'>
							<option value='all'>全て表示</option>
							<option value='on'>レジON</option>
							<option value='off'>レジOFF</option>
						</select>
					</div>
					<div style='display:flex;' id='order_by'>
						<select v-model='order_by[0]' class='form-select form-select-lg' style='margin-bottom:5px;'>
							<option value='seq'>登録順</option>
							<option value='name'>名称順</option>
						</select>
						<button @click='up_or_down' class='btn btn-primary' style='height:25px;padding:0px 10px;font-size:1.2rem;margin-top:0px;margin-left:5px;' type='button' id=''>
							{{order_by[1]}}
						</button>
					</div>
				</div>
			</div>
		</div>
	
		<main class='common_body' >
			<div class='container-fluid'>
				<template v-if='MSG!==""'>
					<div :class='alert_status' role='alert'>{{MSG}}</div>
				</template>
				<input type='hidden' name='csrf_token' :value='csrf'>
				
				<table class='table result_table item_1' style='width:100%;max-width:630px;table-layout: fixed;'>
					<thead>
						<tr style='height:30px;'>
							<th class='th1' scope='col' colspan='3' style='width:auto;padding:0px 5px 0px 0px;'>ID:商品名</th>
							<th class='th1 text-center' scope='col'>レジ</th>
						</tr>
						<tr style='height:30px;'>
							<th scope='col' colspan='2' style=''>税込価格(内消費税) / 税率</th>
							<th scope='col' class='text-center'>原価</th>
							<th></th>
						</tr>
						<!--<tr>
							<th class='th2 text-center' scope='col'>新価格</th>
							<th class='th2 text-center' scope='col' class=''>税率(%)</th>
							<th class='th2 text-center' scope='col' class=''>新原価</th>
							<th class='th2 text-center' scope='col'>削除</th>
						</tr>-->
					</thead>
					<tbody>
						<template v-for='(list,index) in shouhinMS_filter' :key='list.shouhinCD'>
							<tr>
								<td style='font-size:1.7rem;font-weight:700;' colspan='3'>{{list.shouhinCD}}:{{list.shouhinNM}}</td><!--商品名-->
								<td style='padding:10px 10px;'>
									<div class="form-check form-switch">
										<input type='checkbox' class='form-check-input' v-model='list.disp_rezi' :id='`${list.shouhinCD}`' @change='show_rez(index)'>
										<label v-if='list.disp_rezi!==true' class='form-check-label' :for='`${list.shouhinCD}`' style='font-size:1.2rem;'>非表示</label>
										<label v-if='list.disp_rezi===true' class='form-check-label' :for='`${list.shouhinCD}`'>表示</label>
									</div>
								</td>
							</tr>
							<tr style='border-bottom:3px;'>
								<td class='text-start ps-4' colspan='2'>
									¥{{(Number(list.tanka) + Number(list.tanka_zei)).toLocaleString()}}　(¥{{Number(list.tanka_zei).toLocaleString()}}) / {{list.hyoujimei}}
								</td>
								<td class='text-end pe-4' colspan='1'>
									¥{{Number(list.genka_tanka).toLocaleString()}}
								</td>
								<td class='text-end'>
									<button class='btn btn-primary' type='button' @click='custum_ONOFF(index)'>{{list.btn_name_for_shouhinMS_mente}}</button>
								</td>
							</tr>
							<tr v-show='list.cate_chk' class="table-success">
								<td>
									<label class='ms-2' :for='`new_val_${index}`'>新価格</label>
									<input type='number' class='form-control text-end pe-3' style='width:100%;' v-model='list.new_kakaku' @blur='set_new_value(index,`#new_val_${index}`)' :id='`new_val_${index}`'>
								</td>
								<td>
									<label class='ms-2' :for='`new_zei_${index}`'>税率</label>
									<select v-model='list.new_zeiKBN' @change='set_new_value(index,`#new_val_${index}`)' :id ='`new_zei_${index}`' class='form-select form-select-lg text-center pe-4' 
									style='font-size:1.7rem;width:100%;height:30px;'><!--税区分 -->
										<template v-for='(list,index) in ZeiMS' :key='list.税区分名'>
											<option :value="list.税区分">{{list.税区分名}}</option>
										</template>
									</select>
								</td>
								<td class=''>
									<label class='ms-2' :for='`new_zei_${index}`'>原価</label>
									<input type='number' class='form-control text-end pe-3' style='width:100%;' v-model='list.new_genka'>
								</td>
								<td class='pt-5 text-center'>
									<a href='#' @click='delete_item(index,`shouhinDEL_sql.php?cd=${list.shouhinCD}&nm=${list.shouhinNM}&csrf_token=${csrf}&sortNO=${list.hyoujiNO}`)'>
										<i v-if='list.hyoujiNO!==999' class='fa-regular fa-trash-can fa-2x'></i>
										<span v-else>位置を戻す</span>
									</a>
								</td><!--削除アイコン-->
								<input type='hidden' :name ='`ORDERS[${index}][shouhinCD]`' :value='list.shouhinCD'>
								<input type='hidden' :name ='`ORDERS[${index}][tanka]`' :value='list.tanka'>
								<input type='hidden' :name ='`ORDERS[${index}][shouhizei]`' :value='list.tanka_zei'>
							</tr>
							<tr v-show='list.cate_chk' style='border-bottom:3px double ;' class="table-success">
								<td colspan=4 class='ps-5 fw-bold text-danger'>
									新価格：税込　¥{{(Number(list.new_tanka) + Number(list.new_tanka_zei)).toLocaleString()}}　内税(¥{{Number(list.new_tanka_zei).toLocaleString()}}) 
								</td>
							</tr>
						</template>
					</tbody>
				</table>
			</div>
			<!--<template v-for='(list,index) in shouhinMS_BK_filter' :key='list.shouhinCD'>
			</template>-->
			<!--比較用の変更前商品マスタも呼び出ししないとソートされないため、ダミーで呼び出し-->

		</main>
	<div class="loader-wrap" v-show='loader'>
		<div class="loader">Loading...</div>
	</div>
	</div>
	<script>
		window.onload = function() {
			// Enterキーが押された時にSubmitされるのを抑制する
			document.getElementById("app").onkeypress = (e) => {const key = e.keyCode || e.charCode || 0;}
		};
	</script><!--js-->
	<script src="shouhinMSList_vue.js?<?php echo $time; ?>"></script>
	<script>
		REZ_APP("shouhinMSList.php").mount('#app');
	</script><!--Vue3js-->
</BODY>
<!--シェパードナビshepherd
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php require "ajax_func_tourFinish.php";?>
<script>
	const TourMilestone = '<?php echo $_SESSION["tour"];?>';

	const tutorial_12 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_12'
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'> 登録済み商品の修正画面になります。
				</p>`,
		buttons: [
			{
				text: 'Next',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'> 登録した商品の「価格変更」やレジへの「表示/非表示」の切替などを行います。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'> レジの「表示/非表示」の切替は</p>
			<div class="tour_discription form-check form-switch">
				<input type='checkbox' class='form-check-input' checked>
				<label class='form-check-label' >表示</label>
			</div>
			<p class='tour_discription'>のチェック有無で行います。</p>
			<p class='tour_discription'>切替えは即レジに反映されます。</p>
		`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'><button class='btn btn-primary'>変更</button>をタップすると価格、税率、原価の編集エリア、及び商品削除アイコンが表示されます。 </p>
		`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'>「販売価格」の変更時に「税込金額入力」 とするか 「税抜金額入力」の切替は、こちらで選択して下さい。<br></p>
			<p class='tour_discription'>選択結果に合わせて、消費税額は自動計算されます。</p>
		`,
		attachTo: {
			element: '#tax_inout',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'><button class='btn btn-primary'>確定</button>をタップすると価格、税率、原価の編集内容が登録されます。 </p>
		`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>なお、商品の価格等を修正しても過去の売上金額、原価が変更されることはありません。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'finish',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'>編集エリアに表示される<i class='fa-regular fa-trash-can'></i>　マークをタップすると削除を確認するメッセージが表示され、OKをタップすると削除されます。</p>
			<p class='tour_discription'>なお、売上実績がある商品は削除出来ませんが、表示位置が最後尾に固定されます。</p>
		`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'> 右上のリストボックスをタップすると、一覧で表示されてる商品を、レジ画面表示「チェック有」「チェック無し」「全件表示」の３パターンに切り替える事が可能です。
				</p>`,
		attachTo: {
			element: '#disp_change',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'> 商品の並び順はここで変更できます。
				<br>三角マークは昇順・降順の切り替えに使います。
				</p>`,
		attachTo: {
			element: '#order_by',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.next
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'> 以上でチュートリアルは終了です。<br>その他、不明点等ございましたらトップ画面の「お問い合わせはコチラ」からお気軽にお問い合わせください。
				</p>`,
		attachTo: {
			element: '#order_by',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Finish',
				action: tutorial_12.complete
			}
		]
	});




	
	const helpTour = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'helpTour'
	});
	helpTour.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'> レジの「表示/非表示」の切替は</p>
			<div class="tour_discription form-check form-switch">
				<input type='checkbox' class='form-check-input' checked>
				<label class='form-check-label' >表示</label>
			</div>
			<p class='tour_discription'>のチェック有無で行います。</p>
			<p class='tour_discription'>切替えは即レジに反映されます。</p>
		`,
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'Next',
				action: helpTour.next
			}
		]
	});
	helpTour.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'><button class='btn btn-primary'>変更</button>をタップすると価格、税率、原価の編集エリア、及び商品削除アイコンが表示されます。 </p>
		`,
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'Next',
				action: helpTour.next
			}
		]
	});
	helpTour.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'>「販売価格」の変更時に「税込金額入力」 とするか 「税抜金額入力」の切替は、こちらで選択して下さい。</p>
			<p class='tour_discription'>選択結果に合わせて、消費税額は自動計算されます。</p>
		`,
		attachTo: {
			element: '#tax_inout',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'Next',
				action: helpTour.next
			}
		]
	});
	helpTour.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'><button class='btn btn-primary'>確定</button>をタップすると価格、税率、原価の編集内容が登録されます。 </p>
		`,
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'Next',
				action: helpTour.next
			}
		]
	});
	helpTour.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>なお、商品の価格等を修正しても過去の売上金額、原価が変更されることはありません。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'Next',
				action: helpTour.next
			}
		]
	});
	helpTour.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `
			<p class='tour_discription'>編集エリアに表示される<i class='fa-regular fa-trash-can'></i>　マークをタップすると削除を確認するメッセージが表示され、OKをタップすると削除されます。</p>
			<p class='tour_discription'>なお、売上実績がある商品は削除出来ませんが、表示位置が最後尾に固定されます。</p>
		`,
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'Next',
				action: helpTour.next
			}
		]
	});
	helpTour.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'> 右上のリストボックスをタップすると、一覧で表示されてる商品を、レジ画面表示「チェック有」「チェック無し」「全件表示」の３パターンに切り替える事が可能です。
				</p>`,
		attachTo: {
			element: '#disp_change',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'Next',
				action: helpTour.next
			}
		]
	});
	helpTour.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'> 商品の並び順はここで変更できます。
				<br>三角マークは昇順・降順の切り替えに使います。
				</p>`,
		attachTo: {
			element: '#order_by',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'Finish',
				action: helpTour.complete
			}
		]
	});

	if(TourMilestone=="tutorial_11"){
		tutorial_12.start(tourFinish,'tutorial','');
	}

	function help(){
		helpTour.start(tourFinish,'help','');
	}

</script>
</html>
<?php
$_SESSION["MSG"] = "";
$_SESSION["alert"]="";
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>
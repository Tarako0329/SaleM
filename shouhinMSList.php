<?php
	require "php_header.php";

	$rtn = csrf_checker(["shouhinMSList.php","shouhinMSList_sql.php","shouhinDEL_sql.php","menu.php"],["G","C","S"]);
	if($rtn !== true){
			redirect_to_login($rtn);
	}
	//セッションのIDがクリアされた場合の再取得処理。
	$rtn=check_session_userid($pdo_h);

	$csrf_create = csrf_create();
	//$MSG = (empty($_SESSION["MSG"])?"":$_SESSION["MSG"]);
	//$ALERT = (empty($_SESSION["alert"])?"":$_SESSION["alert"]);
	//ユーザ情報取得
	//$sql="select yuukoukigen,ZeiHasu from Users where uid=?";
	$sql="select yuukoukigen,ZeiHasu from Users_webrez where uid=?";
	$stmt = $pdo_h->prepare($sql);
	$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

	//端数処理設定
	$ZeiHasu = $row[0]["ZeiHasu"];

	//税区分M取得.基本変動しないので残す
	$ZEIsql="select * from ZeiMS order by zeiKBN;";
	$stmt = $pdo_h->query($ZEIsql);
	$ZEIresult = $stmt->fetchAll(PDO::FETCH_ASSOC);



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
	<!--<form method='post' action='shouhinMSList_sql.php' id='form1'>-->
	<form method='post' @submit.prevent='on_submit'>
		<header class='header-color common_header' style='flex-wrap:wrap'>
			<div class='title' style='width: 60%;'>
				<a href='menu.php'><?php echo $title;?></a>
			</div>
			<div class='right' style='width: 40%;padding-top:10px;display:flex;'>
				<div style='width: 20px;padding-top:5px;'><i class="fa-solid fa-magnifying-glass fa-2x logoff-color"></i></div>
				<div style='width:200px' ><input type='search' v-model='search_word' class='form-contral' style='margin-left:5px;width:78%;max-width:200px;' placeholder='Search'></div>
				
			</div>
			<p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  取扱商品 確認・編集 画面</p>
			<?php if(empty($_SESSION["tour"])){?>
			<a href="#" style='color:inherit;position:fixed;top:50px;right:5px;' onclick='help()'><i class="fa-regular fa-circle-question fa-lg logoff-color"></i></a>
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
						<select v-model='chk_register_show' class='form-select form-select-lg item_0'>
							<option value='all'>全て表示</option>
							<option value='on'>レジON</option>
							<option value='off'>レジOFF</option>
						</select>
					</div>
					<div style='display:flex;'>
						<select v-model='order_by[0]' class='form-select form-select-lg' style='margin-bottom:5px;'>
							<option value='seq'>登録順</option>
							<option value='name'>名称順</option>
						</select>
						<button @click='up_or_down' class='btn btn-primary' style='height:25px;padding:0px 10px;font-size:1.2rem;margin-top:0px;margin-left:5px;' type='button' id='order_by'>
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
				<!--<input type='hidden' name='csrf_token' value='<?php //echo $csrf_create; ?>'>-->
				<input type='hidden' name='csrf_token' :value='csrf'>
				
				<table class='table result_table item_1' style='width:100%;max-width:630px;table-layout: fixed;'>
					<thead>
						<tr style='height:30px;'>
							<th class='th1' scope='col' colspan='2' style='width:auto;padding:0px 5px 0px 0px;'>ID:商品名</th>
							<th class='th1' scope='col'>レジ</th>
							<th class='th1' scope='col'>現税込価格</th>
						</tr>
						<tr style='height:30px;'>
							<th scope='col' >単価変更</th>
							<th scope='col' style='color:red;'>本体額</th>
							<th scope='col' style='color:red;'>消費税</th>
							<th scope='col' >新税込価格</th>
						</tr>
						<tr>
							<th class='th2' scope='col'>税率(%)</th>
							<th class='th2' scope='col' class=''>原価</th>
							<th class='th2' scope='col' class=''>内容量(単位)</th>
							<th class='th2 text-center' scope='col'>削除</th>
						</tr>
					
					</thead>
					<!--<tbody v-for='(list,index) in shouhinMS_filter' :key='list.shouhinCD'>-->
					<tbody>
						<template v-for='(list,index) in shouhinMS_filter' :key='list.shouhinCD'>
						<tr>
							<td style='font-size:1.7rem;font-weight:700;' colspan='2'>{{list.shouhinCD}}:{{list.shouhinNM}}</td><!--商品名-->
							<td style='padding:10px 10px;'>
								<div class="form-check form-switch">
									<input type='checkbox' :name ='`ORDERS[${index}][hyoujiKBN1]`' class='form-check-input' v-model='list.disp_rezi' :id='`${list.shouhinCD}`'>
									<label v-if='list.disp_rezi!==true' class='form-check-label' :for='`${list.shouhinCD}`' style='font-size:1.2rem;'>非表示</label>
									<label v-if='list.disp_rezi===true' class='form-check-label' :for='`${list.shouhinCD}`'>表示</label>
								</div>
							</td>
							<td style='font-size:1.7rem;padding:10px 15px 10px 10px;' class='text-end'><!--変更前価格-->
								￥{{Number(list.moto_kin).toLocaleString()}}
							</td>
						</tr>
						<tr>
							<td><input @blur='set_new_value(index,`#new_val_${index}`)' :id='`new_val_${index}`' type='number' class='form-contral text-end pe-3' style='width:100%;' placeholder='新価格' ></td>   <!--単価修正欄 -->
							<td style='font-size:1.7rem;padding:10px 15px 10px 10px;color:blue;' class='text-end'>
								￥{{Number(list.tanka).toLocaleString()}}
							</td><!--登録単価-->
							<td style='font-size:1.7rem;padding:10px 15px 10px 10px;color:blue;' class='text-end'><!--list.tanka_zei-->
								￥{{Number(list.tanka_zei).toLocaleString()}}
							</td> 
							<td style='font-size:1.7rem;padding:10px 15px 10px 10px;color:blue;' class='text-end'><!--list.tanka_zei-->
								￥{{(Number(list.tanka) + Number(list.tanka_zei)).toLocaleString()}}
							</td> 
						</tr>
						<tr style='border-bottom:3px;'>
							<td>
								<select v-model='list.zeiKBN' @change='set_new_value(index,`#new_val_${index}`)' :name ='`ORDERS[${index}][zeikbn]`' class='form-select form-select-lg' 
								style='font-size:1.7rem;width:100%;height:30px;'><!--税区分 -->
									<?php
									foreach($ZEIresult as $row){
										echo "<option value=".secho($row["zeiKBN"]).">".secho($row["hyoujimei"])."</option>\n";
									}
									?>
								</select>

							</td>
							<!--<td><input type='number' :name ='`ORDERS[${index}][genka]`' class='form-contral' style='width:100%;text-align:right;padding-right:15px;' :value='list.genka_tanka'></td>-->
							<td><input type='number' :name ='`ORDERS[${index}][genka]`' class='form-contral text-end pe-1' style='width:100%;' v-model='list.genka_tanka'></td>

							<td class=''>
								<input type='number' :name ='`ORDERS[${index}][utisu]`' class='form-contral text-end pe-1' style='width:60%;' v-model='list.utisu'>
								<input type='text'   :name ='`ORDERS[${index}][tani]`' class='form-contral text-end pe-1' style='width:35%;' v-model='list.tani'>
							</td>
							<td class=' text-center'>
								<!--<a href='#' @click='delete_item(list.shouhinNM,`shouhinDEL_sql.php?cd=${list.shouhinCD}&nm=${list.shouhinNM}&csrf_token=<?php echo $csrf_create; ?>`)'>-->
								<a href='#' @click='delete_item(list.shouhinNM,`shouhinDEL_sql.php?cd=${list.shouhinCD}&nm=${list.shouhinNM}&csrf_token=${csrf}`)'>
									<i class='fa-regular fa-trash-can fa-2x'></i>
								</a>
							</td><!--削除アイコン-->
							<input type='hidden' :name ='`ORDERS[${index}][shouhinCD]`' :value='list.shouhinCD'>
							<input type='hidden' :name ='`ORDERS[${index}][tanka]`' :value='list.tanka'>
							<input type='hidden' :name ='`ORDERS[${index}][shouhizei]`' :value='list.tanka_zei'>
						</tr>
						</template>
					</tbody>
				</table>
			</div>
			<template v-for='(list,index) in shouhinMS_BK_filter' :key='list.shouhinCD'>
			</template><!--比較用の変更前商品マスタも呼び出ししないとソートされないため、ダミーで呼び出し-->

		</main>
		<footer class='common_footer'>
			<button type='button' @click='chk_onoff()' class='btn--chk item_3' style='border-radius:0;' name='commit_btn' >{{btn_name}}</button>
			<button v-if='chk==="on"' type='submit' class='btn--chk item_3' style='border-radius:0;border-left: thick double #32a1ce;' name='commit_btn' >登　録</button>
		</footer>
	</form>
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
		REZ_APP().mount('#app');
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
		text: `<p class='tour_discription'> 商品一覧の修正画面になります。
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
		text: `<p class='tour_discription'> 登録した商品の「価格変更」やレジへの「表示/非表示」の切替はこの状態(縦画面表示)で行えます。
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
		text: `<p class='tour_discription'> 右上のリストボックスをタップすると、レジ画面の表示対象チェックが入っているもの、いないもの、全件表示と切り替える事が可能です。
				</p>`,
		attachTo: {
			element: '.item_0',
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
		text: `<p class='tour_discription'>試しにタップして変更してみてください。
				</p>`,
		attachTo: {
			element: '#order_by',
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
		text: `<p class='tour_discription'>商品の価格を変更する際は「新価格」欄に変更後の価格を入力して下さい。
				</p>`,
		attachTo: {
			element: '.item_1',
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
		text: `<p class='tour_discription'>「新価格」の「税込/税抜」設定は、こちらで選択して下さい。
				</p>`,
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
		text: `<p class='tour_discription'>レジへの「表示/非表示」の切替は「レジ」行のチェック有無で切り替えます。
				</p>`,
		attachTo: {
			element: '.item_1',
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
		text: `<p class='tour_discription'>その他の項目についても、コチラの画面で修正したい部分を打ち変えることで修正が可能です。
				</p>`,
		attachTo: {
			element: '.item_1',
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
		text: `<p class='tour_discription'>修正が完了したら「登録」ボタンをタップすると、変更内容が登録されます。
				</p>`,
		attachTo: {
			element: '.item_3',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.nextAndSave
			}
		]
	});
	tutorial_12.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>なお、こちらで商品の価格等を修正しても<span style='color:red;'>過去の売上が変更されることはありません。</span>
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
		text: `<p class='tour_discription'>試しに項目を修正し、「登録」してみてください。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_12.back
			},
			{
				text: 'Next',
				action: tutorial_12.complete
			}
		]
	});

	const tutorial_13 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: true,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_13'
	});
	tutorial_13.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>最後に、登録した商品情報の削除について説明します。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_13.back
			},
			{
				text: 'Next',
				action: tutorial_13.next
			}
		]
	});
	tutorial_13.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'><span style='color:red;'>なお、「売上」実績のある商品は削除出来ません。</span>
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_13.back
			},
			{
				text: 'Next',
				action: tutorial_13.next
			}
		]
	});
	tutorial_13.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>今回登録した商品が不要な商品でしたら<i class='fa-regular fa-trash-can'></i>　マークをタップして削除して下さい。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_13.back
			},
			{
				text: 'Next',
				action: tutorial_13.next
			}
		]
	});
	tutorial_13.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>以上でチュートリアルは終了となります。
				<br>お疲れ様でした。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_13.back
			},
			{
				text: 'Next',
				action: tutorial_13.complete
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
		text: `<p class='tour_discription'> 登録した商品の「価格変更」やレジへの「表示/非表示」の切替はこの状態(縦画面表示)で行えます。
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
		text: `<p class='tour_discription'> 画面を横にすると他の項目も表示され、修正可能な状態となります。
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
		text: `<p class='tour_discription'> 画面を横にしてみてください。
				<br>PCの場合、ブラウザの幅を拡大縮小すると表示が切り替わります。
				<br>タブレットの場合は最初から全て表示されているかと思います。
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
		text: `<p class='tour_discription'><i class='fa-regular fa-trash-can'></i>　マークをタップすると削除を確認する画面に移動します。
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
		text: `<p class='tour_discription'> 右上のリストボックスをタップすると、レジ画面の表示対象チェックが入っているもの、いないもの、全件表示と切り替える事が可能です。
				</p>`,
		attachTo: {
			element: '.item_0',
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
			element: '.item_01',
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
	});    helpTour.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>商品の価格を変更する際は「新価格」欄をタップして変更後の価格を入力して下さい。
				</p>`,
		attachTo: {
			element: '.item_1',
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
		text: `<p class='tour_discription'>入力した「新価格」が「税込か税抜」かは、こちらで選択して下さい。
				</p>`,
		attachTo: {
			element: '.item_2',
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
		text: `<p class='tour_discription'>レジへの「表示/非表示」の切替は「レジ」行のチェック有無で切り替えます。
				</p>`,
		attachTo: {
			element: '.item_1',
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
		text: `<p class='tour_discription'>その他の項目についても、コチラの画面で修正したい部分をタップして打ち変えることで修正が可能です。
				</p>`,
		attachTo: {
			element: '.item_1',
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
		text: `<p class='tour_discription'>修正が完了したら「登録」ボタンをタップすると、変更内容が登録されます。
				</p>`,
		attachTo: {
			element: '.item_3',
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
		text: `<p class='tour_discription'>なお、こちらで商品の価格等を修正しても過去の売上が変更されることはありません。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'finish',
				action: helpTour.complete
			}
		]
	});

	if(TourMilestone=="tutorial_11"){
		tutorial_12.start(tourFinish,'tutorial','');
	}/*else if(TourMilestone=="tutorial_12"){
		tutorial_13.start(tourFinish,'tutorial','finish');
	}*/

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
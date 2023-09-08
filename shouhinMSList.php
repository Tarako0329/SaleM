<?php
	require "php_header.php";

	$rtn = csrf_checker(["shouhinMSList.php","shouhinMSList_sql.php","shouhinDEL_sql.php","menu.php"],["G","C","S"]);
	if($rtn !== true){
			redirect_to_login($rtn);
	}
	
	$csrf_create = csrf_create();
	$MSG = (empty($_SESSION["MSG"])?"":$_SESSION["MSG"]);

	//税区分M取得.基本変動しないので残す
	$ZEIsql="select * from ZeiMS order by zeiKBN;";
	$ZEIresult = $pdo_h->query($ZEIsql);

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
	<form method='post' action='shouhinMSList_sql.php' id='form1'>
		<header class='header-color common_header' style='flex-wrap:wrap'>
			<div class='title' style='width: 100%;'><a href='menu.php'><?php echo $title;?></a></div>
			<p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  取扱商品 確認・編集 画面</p>
			<?php if(empty($_SESSION["tour"])){?>
			<a href="#" style='color:inherit;position:fixed;top:42px;right:5px;' onclick='help()'><i class="fa-regular fa-circle-question fa-lg logoff-color"></i></a>
			<?php }?>
		</header>
		<div class='header2'>
			<div class='container-fluid'>
				<div style='padding:0 5px;'>
					<input type='radio' class='btn-check' name='options' value='komi' autocomplete='off' v-model='upd_zei_kominuki' id='plus_mode'>
					<label class='btn btn-outline-primary' style='font-size:1.2rem;border-radius:0;' for='plus_mode'>税込入力</label>
					<input type='radio' class='btn-check' name='options' value='nuki' autocomplete='off' v-model='upd_zei_kominuki' id='minus_mode'>
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
						<button @click='up_or_down' class='btn btn-primary' style='height:25px;padding:0px 10px;font-size:1.2rem;margin-top:0px;margin-left:5px;' type='button'>
							{{order_by[1]}}
						</button>
					</div>
				</div>
			</div>
		</div>
	
		<main class='common_body'>
			<div class='container-fluid'>
				<template v-if='MSG!==""'>
					<div :class='alert_status' role='alert'>{{MSG}}</div>
				</template>
				<input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>
				<table class='table result_table item_1' style='width:100%;max-width:630px;table-layout: fixed;'>
					<thead>
					
						<tr style='height:30px;'>
							<th class='th1' scope='col' colspan='2' style='width:auto;padding:0px 5px 0px 0px;'>ID:商品名</th>
							<th class='th1' scope='col'>元税込価格</th>
							<th class='th1' scope='col'>レジ</th>
						</tr>
						<tr style='height:30px;'>
							<th scope='col' >単価変更</th>
							<th scope='col' style='color:red;'>本体額</th>
							<th scope='col' >税率(%)</th>
							<th scope='col' style='color:red;'>消費税</th>
						</tr>
						<tr>
							<th class='th2' scope='col'>原価</th>
							<th class='th2' scope='col' class=''>内容量</th><!--d-none d-sm-table-cell-->
							<th class='th2' scope='col' class=''>単位</th>
							<th class='th2' scope='col'>削除</th>
						</tr>
					
					</thead>
					<tbody v-for='(list,index) in shouhinMS_filter' :key='list.shouhinCD'>
						<tr>
							<td style='font-size:1.7rem;font-weight:700;' colspan='2'>{{list.shouhinCD}}:{{list.shouhinNM}}</td><!--商品名-->
							<td style='font-size:1.7rem;padding:10px 15px 10px 10px;' align='right'><!--変更前価格-->
								￥{{Number(list.moto_kin).toLocaleString()}}
							</td>
							<td style='padding:10px 10px;'>
								<!--<input type='checkbox' :name ='`ORDERS[${index}][hyoujiKBN1]`' class='form-check-input' style='transform:scale(1.4);' v-model='list.disp_rezi'>-->
								<div class="form-check form-switch">
									<input type='checkbox' :name ='`ORDERS[${index}][hyoujiKBN1]`' class='form-check-input' v-model='list.disp_rezi' :id='`${list.shouhinCD}`'>
									<label v-if='list.disp_rezi!==true' class='form-check-label' :for='`${list.shouhinCD}`'>非表示</label>
									<label v-if='list.disp_rezi===true' class='form-check-label' :for='`${list.shouhinCD}`'>表示</label>
								</div>
							</td>
						</tr>
						<tr>
							<td><input @blur='set_new_value(index,`#new_val_${index}`)' :id='`new_val_${index}`' type='number' class='form-contral' style='width:100%;text-align:center' placeholder='新価格' ></td>   <!--単価修正欄 -->
							<td style='font-size:1.7rem;padding:10px 15px 10px 10px;' align='right'>
								<!--<input type='hidden' readonly='readonly' :name ='`ORDERS[${index}][tanka]`' class='form-contral' style='font-size:1.7rem;width:7rem;background-color:#fff;border:0;' :value='list.tanka'>-->
								￥{{Number(list.tanka).toLocaleString()}}
							</td><!--登録単価-->
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
							<td style='font-size:1.7rem;padding:10px 15px 10px 10px;' align='right'><!--list.tanka_zei-->
								<!--<input type='number' readonly='readonly' :name ='`ORDERS[${index}][shouhizei]`' class='form-contral' style='font-size:1.7rem;width:7rem;background-color:#fff;border:0' :value='list.tanka_zei'>-->
								￥{{Number(list.tanka_zei).toLocaleString()}}
							</td>
						</tr>
						<tr>
							<td><input type='number' :name ='`ORDERS[${index}][genka]`' class='form-contral' style='width:100%;text-align:right;padding-right:15px;' :value='list.genka_tanka'></td>
							<td class=''><input type='number' :name ='`ORDERS[${index}][utisu]`' class='form-contral' style='width:100%;text-align:right;padding-right:15px;' :value='list.utisu'></td>
							<td class=''><input type='text'   :name ='`ORDERS[${index}][tani]`' class='form-contral' style='width:100%;text-align:right;padding-right:15px;' :value='list.tani'></td>
							<td class=''>
								<a href='#' @click='delete_item(list.shouhinNM,`shouhinDEL_sql.php?cd=${list.shouhinCD}&nm=${list.shouhinNM}&csrf_token=<?php echo $csrf_create; ?>`)'>
									<i class='fa-regular fa-trash-can fa-2x'></i>
								</a>
							</td><!--削除アイコン-->
							<input type='hidden' :name ='`ORDERS[${index}][shouhinCD]`' :value='list.shouhinCD'>
							<input type='hidden' :name ='`ORDERS[${index}][tanka]`' :value='list.tanka'>
							<input type='hidden' :name ='`ORDERS[${index}][shouhizei]`' :value='list.tanka_zei'>
						</tr>
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
	</script><!--js-->

	<script>
		const { createApp, ref, onMounted, computed, VueCookies, watch } = Vue
		createApp({
			setup(){
				//商品マスタ取得関連
				const shouhinMS = ref([])			//商品マスタ
				const shouhinMS_BK = ref([])	//商品マスタ修正前バックアップ
				const get_shouhinMS = () => {//商品マスタ取得ajax
					console_log("get_shouhinMS start",'lv3')
					let params = new URLSearchParams()
					params.append('user_id', '<?php echo $_SESSION["user_id"];?>')
					axios
					.post('ajax_get_ShouhinMS.php',params)
					.then((response) => {
						//console_log(response.data,'lv3')
						shouhinMS.value = [...response.data]
						shouhinMS_BK.value = JSON.parse(JSON.stringify(shouhinMS.value))
					})
					.catch((error) => {
						console_log(`get_shouhinMS ERROR:${error}`,'lv3')
					})
					return 0;
				};//商品マスタ取得ajax
				
				//商品マスタのソート・フィルタ関連
				const chk_register_show = ref('all')	//フィルタ
				const order_by = ref(['seq','▼'])			//ソート（項目・昇順降順）
				const chk = ref('off')
				const btn_name = ref('確　認')
				const btn_type = ref('button')
				const chk_onoff = () =>{
					if(chk.value==='off'){
						chk.value='on'
						btn_name.value='戻　る'
						//alert('表示されてる内容でよろしければ「登録」してください。')
					}else{
						chk.value='off'
						btn_name.value='確　認'
					}
				}
				const up_or_down = () =>{
					if(order_by.value[1]==='▼'){
						order_by.value[1]='▲'
					}else{
						order_by.value[1]='▼'
					}
				}
				const shouhinMS_filter = computed(() => {//商品マスタのソート・フィルタ
					let order_panel = ([])
					if(chk.value==='on'){
						let j=0 
						for (let i = 0; i < shouhinMS.value.length; ++i) {
							if(JSON.stringify(shouhinMS.value[i]) !== JSON.stringify(shouhinMS_BK.value[i])){
								console_log(`chk on ${i} UNmatch`,"lv3")
								order_panel[j] = shouhinMS.value[i]
								j++
							}else{
								console_log(`chk on ${i} match`,"lv3")
							}
						}
						return order_panel
					}else if (chk_register_show.value === "on"){//表示対象のみを返す
						order_panel = shouhinMS.value.filter((shouhin) => {
							return (shouhin.hyoujiKBN1 && shouhin.hyoujiKBN1.includes('on') );
						});
					}else if (chk_register_show.value === "off"){//表示対象外のみを返す
						order_panel = shouhinMS.value.filter((shouhin) => {
							return (shouhin.hyoujiKBN1===null || !shouhin.hyoujiKBN1.includes('on') );
						});
					}else{//全件表示
						order_panel = shouhinMS.value
					}
					//checkbox にあわせて on -> true に変更
					order_panel.forEach((list)=> {
						if(list.hyoujiKBN1==='on'){
							list['disp_rezi'] = true
						}else{
							list['disp_rezi'] = false
						}
					})
					//最後にソートして返す
					if(order_by.value[0]==='name'){
						return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
							return (order_by.value[1]==='▼'?(a.shouhinNM < b.shouhinNM?1:-1):(a.shouhinNM > b.shouhinNM?1:-1))
						})
					}else if(order_by.value[0]==='seq'){
						return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
							return (order_by.value[1]==='▼'?(a.shouhinCD < b.shouhinCD?1:-1):(a.shouhinCD > b.shouhinCD?1:-1))
						})
					}else{}
				})//商品マスタのソート・フィルタ

				const shouhinMS_BK_filter = computed(() => {//商品マスタバックアップもソート・フィルタ
					let order_panel = ([])
					if (chk_register_show.value === "on"){//表示対象のみを返す
						order_panel = shouhinMS_BK.value.filter((shouhin) => {
							return (shouhin.hyoujiKBN1 && shouhin.hyoujiKBN1.includes('on') );
						});
					}else if (chk_register_show.value === "off"){//表示対象外のみを返す
						order_panel = shouhinMS_BK.value.filter((shouhin) => {
							return (shouhin.hyoujiKBN1===null || !shouhin.hyoujiKBN1.includes('on') );
						});
					}else{
						order_panel = shouhinMS_BK.value
					}
					order_panel.forEach((list)=> {
						if(list.hyoujiKBN1==='on'){
							list['disp_rezi'] = true
						}else{
							list['disp_rezi'] = false
						}
					})
					if(order_by.value[0]==='name'){
						return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
							return (order_by.value[1]==='▼'?(a.shouhinNM < b.shouhinNM?1:-1):(a.shouhinNM > b.shouhinNM?1:-1))
						})
					}else if(order_by.value[0]==='seq'){
						return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
							return (order_by.value[1]==='▼'?(a.shouhinCD < b.shouhinCD?1:-1):(a.shouhinCD > b.shouhinCD?1:-1))
						})
					}else{}
				})//商品マスタバックアップもソート・フィルタ

				//更新関連
				const upd_zei_kominuki = ref('komi')
				const return_tax = (kingaku,zeikbn,kominuki) => {
					//console_log('return_tax start','lv3')
					let zeiritu
					if(zeikbn==='0'){
						zeiritu=0
					}else if(zeikbn==='1001'){
						zeiritu=8
					}else if(zeikbn==='1101'){
						zeiritu=10
					}else{
						return 0
					}

					if(kominuki==='komi'){
						//return Math.floor(kingaku - (kingaku / (1 + zeiritu / 100)))
						return Math.trunc(kingaku - (kingaku / (1 + zeiritu / 100)))
					}else{
						//return Math.floor(kingaku * (zeiritu / 100));
						return Math.trunc(kingaku * (zeiritu / 100));
					}
				}
				const set_new_value = (index,new_val_id) => {
					//単価入力欄から本体と消費税を算出し、セットする
					//console_log(`set_new_value start (${index}:${new_val_id})`,'lv3')
					const new_val = document.querySelector(new_val_id).value
					let tax = return_tax(new_val, shouhinMS.value[index].zeiKBN.toString(), upd_zei_kominuki.value)
					//console_log(`set_new_value start (${index}:${new_val_id} new_val = ${new_val})`,'lv3')

					if(new_val !== ''){
						if(upd_zei_kominuki.value==='komi'){
							shouhinMS.value[index].tanka = Number(new_val) - Number(tax)
							shouhinMS.value[index].tanka_zei = tax
						}else{
							shouhinMS.value[index].tanka = new_val
							shouhinMS.value[index].tanka_zei = tax
						}
					}else if(shouhinMS.value[index].zeiKBN !== shouhinMS_BK.value[index].zeiKBN){
						shouhinMS.value[index].tanka_zei = return_tax(shouhinMS.value[index].tanka, shouhinMS.value[index].zeiKBN.toString(), 'nuki')
					}else{//新価格が空白の場合、本体・税額・税区分を元に戻す
						shouhinMS.value[index].tanka = shouhinMS_BK.value[index].tanka
						shouhinMS.value[index].tanka_zei = shouhinMS_BK.value[index].tanka_zei
						shouhinMS.value[index].zeiKBN = shouhinMS_BK.value[index].zeiKBN
					}
				}
				watch(upd_zei_kominuki,() => {
					shouhinMS.value.forEach((row,index) => {
						set_new_value(index,`#new_val_${index}`)
					})
				})
				const delete_item = (item,link) =>{
					console_log(item,'lv3')
					console_log(link,'lv3')
					if(confirm(`${item} を削除します。よろしいですか？`)===true){
						window.location.href = link
					}
					
				}

				const MSG = ref('<?php echo $_SESSION["MSG"]; ?>')
				const alert_status = ref(['alert','<?php echo $_SESSION["alert"]; ?>'])
				onMounted(() => {
					console_log('onMounted','lv3')
					get_shouhinMS()
				})

				return{
					shouhinMS,
					shouhinMS_BK,
					set_new_value,
					upd_zei_kominuki,
					chk_register_show,
					shouhinMS_filter,
					shouhinMS_BK_filter,
					order_by,
					up_or_down,
					chk_onoff,
					MSG,
					alert_status,
					btn_name,
					btn_type,
					delete_item,
					chk,
				}
			}
		}).mount('#form1');
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
		text: `<p class='tour_discription'> その他の項目については画面を横にすると表示され、修正可能な状態となります。
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
		text: `<p class='tour_discription'> 画面を横にしてみてください。
				<br>PCの場合、ブラウザの幅を拡大縮小すると表示が切り替わります。
				<br>タブレットの場合は最初から全て表示されているかと思います。
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
			element: '.item_01',
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
			element: '.item_0',
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
		text: `<p class='tour_discription'>商品の価格を変更する際は「新価格」欄をタップして変更後の価格を入力して下さい。
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
		text: `<p class='tour_discription'>入力した「新価格」が「税込か税抜」かは、こちらで選択して下さい。
			  </p>`,
		attachTo: {
			element: '.item_2',
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
		text: `<p class='tour_discription'>その他の項目についても、コチラの画面で修正したい部分をタップして打ち変えることで修正が可能です。
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
		text: `<p class='tour_discription'>なお、こちらで商品の価格等を修正しても過去の売上が変更されることはありません。
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
		text: `<p class='tour_discription'><span style='color:red;'>ちなみに、削除しようとしている商品の「売上」が１件でも登録されていると削除する事は出来ません。</span>
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
		text: `<p class='tour_discription'>画面を横にして頂くと右端に<i class='fa-regular fa-trash-can'></i>　マークが表示されます。
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
		text: `<p class='tour_discription'><i class='fa-regular fa-trash-can'></i>　マークをタップすると削除を確認する画面に移動します。
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
	}else if(TourMilestone=="tutorial_12"){
		tutorial_13.start(tourFinish,'tutorial','finish');
	}

	function help(){
		helpTour.start(tourFinish,'help','');
	}

</script>
<script>
	/*
	function send(){
		const form2 = document.getElementById('form2');
		form2.submit();
	}
	*/
</script>
</html>
<?php
$_SESSION["MSG"] = "";
$_SESSION["alert"]="";
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>
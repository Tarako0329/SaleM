<?php {
	require "php_header.php";

	$rtn = csrf_checker(["menu.php"],["G","C","S"]);
	if($rtn !== true){
			redirect_to_login($rtn);
	}
	
	$csrf_create = csrf_create();

}?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php" 
	?>
	<!--ページ専用CSS-->
	<link rel='stylesheet' href='css/style_ShouhinMSCategoryEdit.css?<?php echo $time; ?>' >
	<TITLE><?php echo $title." 取扱商品 確認・編集";?></TITLE>
</head>

<body class='common_body media_body'>
	<div  id='page'>
	<header class='header-color common_header' style='flex-wrap:wrap'>
		<div class='title' style='width: 100%;'><a href='menu.php'><?php echo $title;?></a></div>
		<p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  取扱商品 カテゴリー一括修正 画面</p>
		<?php 
		if(empty($_SESSION["tour"])){
			echo "<a href='#' style='color:inherit;position:fixed;top:5px;right:5px;' onclick='help()'><i class='fa-regular fa-circle-question fa-lg logoff-color'></i></a>";
		}
		?>
	</header>
	<main @click='sujest_OFF'>
		<form method='post' id='form' @submit.prevent='on_submit'>
			<div class='header2'>
				<div style='display:flex;height:25px;margin:5px;'>
					<select v-model="cate_lv" @change='get_categorys' class='form-select form-select-lg' id='help0' name='categry' style='width:100px;' required='required'>
						<option value=''>項目選択</option>
						<option value='cate1' selected>大ｶﾃｺﾞﾘｰ</option>
						<option value='cate2' >中ｶﾃｺﾞﾘｰ</option>
						<option value='cate3' >小ｶﾃｺﾞﾘｰ</option>
					</select>
					<select v-model="over_cate" @change='get_sujest_list' class='form-select form-select-lg' style='width:200px;margin-left:5px'  id='help1'>
						<option disabled selected value='%'>上位分類を選択</option>
						<template v-for='list in categorys'>
							<option v-bind:value='list.LIST'>{{list.LIST}}</option>
						</template>
					</select>
				</div>
				<div style='display:block;margin:5px;' id='help2'>
					<input v-model='set_category' @focus='sujest_ON' type='text' name='upd_bunrui' required='required' placeholder='カテゴリー名を入力' 
					class='form-control' style='max-width:305px;' id='input_category'>
					<div v-show='sujestOnOff' style='background-color:antiquewhite;max-width:305px;'>
					<template v-for='(sujest,index) in sujest_filter'>
						<div class='class="form-check"'>
						<input class="form-check-input" name='radiolists' type='radio' v-bind:value='sujest.LIST' v-bind:id="`radiolist${index}`" style='border:0;display:none;'>
						<label class="form-check-label" v-bind:for="`radiolist${index}`" style='width:100%;padding-left:5px;'> {{sujest.LIST}}</label>
						</div>
					</template>
					</div>
				</div>
			</div>
			<div class='container-fluid'>
				<template v-if='MSG!==""'>
					<div v-bind:class='alert_status' role='alert'>{{MSG}}</div>
				</template>
					<input type='hidden' name='csrf_token' v-model='csrf'>
				<table class='table table-striped table-bordered MSLIST'>
					<thead class='table-light'>
						<tr style='height:30px;'>
							<th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;'>レ</th>
							<th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;' > ID:商品名</th>
							<th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;' > カテゴリー(大>中>小)</th>
						</tr>
					</thead>
					<tbody>
					<template v-for='(list,index) in shouhinMS_filter' v-bind:key='list.shouhinCD'>
						<tr>
							<td><input type='checkbox' :name ="`ORDERS[${index}][chk]`" style='width:2rem;padding-left:10px;' v-model='list.cate_chk'></td>
							<td>{{list.shouhinCD}}:{{list.shouhinNM}}</td>
							<td style='padding-left:5px;'>{{list.category}}</td>
							<input type='hidden' v-bind:name ="`ORDERS[${index}][shouhinCD]`" v-bind:value='list.shouhinCD'>
						</tr>
					</template>
					</tbody>
				</table>
			</div>
			<footer class='common_footer' id='help3'>
				<button type='submit' class='btn--chk' style='border-radius:0;' name='commit_btn' >登　録</button>
			</footer>
		</form>
	</main>
	</div>
<script>
	const { createApp, ref, onMounted, computed } = Vue;
	createApp({
		setup(){
			const cate_lv = ref('cate1')
			const over_cate = ref([])
			const categorys = ref([])
			const sujest_list = ref([])
			const set_category = ref('')
			const sujestOnOff = ref(false)
			const MSG = ref('')
			const csrf = ref('<?php echo $csrf_create; ?>')
			const alert_status = ref(['alert'])

			//商品マスタ全件を取得
			const shouhinMS = ref([])

			onMounted(() => {
				console_log('onMounted')
				//get_shouhinMS()
				GET_SHOUHINMS()
					.then((response)=>{
						shouhinMS.value = response
						console_log('get_shouhinMS succsess')
					})
					.catch((error) => {
						console_log(`get_shouhinMS ERROR:${error}`)
					})

				get_categorys()
				
			})

			/*const get_shouhinMS = () => {
				console_log("get_shouhinMS start");
				let params = new URLSearchParams();
				params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
				axios
				.post('ajax_get_ShouhinMS.php',params)
				.then((response) => (shouhinMS.value = [...response.data]
									,console_log('get_shouhinMS succsess')
									))
				.catch((error) => console_log(`get_shouhinMS ERROR:${error}`));
			}*/
			const shouhinMS_filter = computed(() => {
				let searchWord = over_cate.value.toString().trim();

				shouhinMS.value.sort((a,b) => {
					return (a.category > b.category?1:-1)
					return (a.shouhinNM > b.shouhinNM?1:-1)
					return 0
				})

				if (searchWord === "%") return shouhinMS.value;
				return shouhinMS.value.filter((shouhin) => {
				  return (
					shouhin.category.includes(searchWord) 
				  );
				});
			})

			const get_categorys = () => {
				console_log(`get_categorys started :${cate_lv.value}`)
				if(cate_lv.value ==="cate1"){
					over_cate.value = '%'
					console_log('get_categorys(cate1) succsess')
				}else{
					let params = new URLSearchParams();
					params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
					params.append('output', 'select');
					params.append('list_type', cate_lv.value);
					params.append('serch_word', '');
					
					axios
					.post('ajax_get_MSCategory_list.php',params)
					.then((response) => {
						categorys.value = [...response.data]
						over_cate.value = categorys.value[0].LIST
						console_log('get_categorys succsess')
						//,console_log(response.data))
					})
					.catch((error) => console_log(`get_categorys ERROR:${error}`));
				}
				
			}

			const get_sujest_list = () => {
				console_log("get_sujest_list start");
				let params = new URLSearchParams();
				params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
				params.append('output', 'suggest');
				params.append('list_type', cate_lv.value);
				params.append('serch_word', over_cate.value);
				axios
				.post('ajax_get_MSCategory_list.php',params)
				.then((response) => {
					sujest_list.value = [...response.data]
					console_log('get_sujest_list succsess')
					//,console_log(response.data)
				})
				.catch((error) => console_log(`get_sujest_list ERROR:${error}`));
			}
			
			const sujest_filter = computed(() => {
				let searchWord = set_category.value.toString().trim();
				if (searchWord === "") return sujest_list.value;
				return sujest_list.value.filter((sujest) => {
				  return (
					sujest.LIST.includes(searchWord) 
				  );
				});

			})
			
			const sujest_ON = () => {
				get_sujest_list()
				sujestOnOff.value = true
			}
			const sujest_OFF = (e) => {
				console_log(e.target.id);
				console_log(e.target.name);
				if(e.target.name === 'radiolists'){
					set_category.value = e.target.value
				}
				if(e.target.id.toString() !== "input_category"){
					console_log('サジェストオフ')
					sujestOnOff.value = false
				}
			}

			const on_submit = (e) => {
				console_log('on_submit start')
				console_log(e.target)
				let form_data = new FormData(e.target)
				let params = new URLSearchParams (form_data)
				axios
					.post('ajax_shouhinMSCategoryEdit_sql.php',params)
					.then((response) => (console_log(`on_submit succsess`)
										,console_log(response.data)
										,MSG.value = response.data[0].EMSG
										,csrf.value = response.data[0].csrf_create
										,alert_status.value[1]=response.data[0].status
										))
					.catch((error) => console_log(`on_submit ERROR:${error}`))
					.finally(()=>{
						//get_shouhinMS()
						GET_SHOUHINMS()
						.then((response)=>{
							shouhinMS.value = response
							console_log('get_shouhinMS succsess')
						})
						.catch((error) => {
							console_log(`get_shouhinMS ERROR:${error}`)
						})

					})
			}
			return{
				over_cate,
				cate_lv,
				get_categorys,
				categorys,
				shouhinMS_filter,
				sujest_list,
				get_sujest_list,
				sujest_filter,
				set_category,
				sujestOnOff,
				sujest_ON,
				sujest_OFF,
				MSG,
				on_submit,
				csrf,
				alert_status,
			}
		}
	}).mount('#page');
	
</script><!--vue-->    
<script>
	// Enterキーが押された時にSubmitされるのを抑制する
	document.getElementById("page").onkeypress = (e) => {
		// form1に入力されたキーを取得
		const key = e.keyCode || e.charCode || 0;
		// 13はEnterキーのキーコード
		if (key == 13) {
			// アクションを行わない
			e.preventDefault();
		}
	}
</script>

</body>
<!--シェパードナビshepherd
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php require "ajax_func_tourFinish.php";?>
<script>
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
		title: `<p class='tour_header'>ヘルプ</p>`,
		text: `
			<p class='tour_discription'> 設定するカテゴリーのレベルを指定します。<br>
			例：大（食品）＞中（惣菜）＞小（お弁当）
			</p>`,
		attachTo: {
			element: '#help0',
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
		title: `<p class='tour_header'>ヘルプ</p>`,
		text: `
			<p class='tour_discription'>カテゴリーレベルで「中・小」を選択すると、上位のカテゴリーを指定して表示を絞ることができます。
			</p>`,
		attachTo: {
			element: '#help1',
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
		title: `<p class='tour_header'>ヘルプ</p>`,
		text: `
			<p class='tour_discription'>設定したいカテゴリー名を入力します。<br>
			下の表でチェックを入れた商品すべてにカテゴリー名が適用されます。(上書き可能)
			</p>`,
		attachTo: {
			element: '#help2',
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
		title: `<p class='tour_header'>ヘルプ</p>`,
		text: `
			<p class='tour_discription'>『登録』ボタンを押すと内容が登録されます。
			</p>`,
		attachTo: {
			element: '#help3',
		},
		buttons: [
			{
				text: 'Back',
				action: helpTour.back
			},
			{
				text: 'OK',
				action: helpTour.next
			}
		]
	});

	
	function help(){
		helpTour.start(tourFinish,'help','');
	}

</script><!--help-->
</html>
<?php
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>
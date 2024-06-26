<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。
log_writer2("test",$SLVresult,"lv3");
遷移先のチェック
*/
{
	require "php_header.php";

	//var_dump($_GET);
	//var_dump($_POST);

	$rtn = csrf_checker(["analysis_menu.php","analysis_uriagejisseki.php","analysis_abc.php"],["G","C","S"]);
	if($rtn !== true){
		$rtn = csrf_checker(["analysis_menu.php","analysis_uriagejisseki.php","analysis_abc.php"],["P","C","S"]);
		if($rtn !== true){
			redirect_to_login($rtn);
		}
	}

	$rtn=check_session_userid($pdo_h);
	$csrf_create = csrf_create();

	$list = "%";
	$analysis_type=$_GET["sum_tani"];
	
	$category="%";
	$category_lv="0";


	//検索年月リスト ユーザの最初の売上年月～今年12月までのリストを作成する
	$SLVsql = "select DATE_FORMAT(min(UriDate), '%Y-%m') as min_uridate from UriageData where uid = :user_id";
	$stmt = $pdo_h->prepare($SLVsql);
	$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
	$stmt->execute();
	$SLVresult = $stmt->fetchAll(PDO::FETCH_ASSOC);

	$next_ymd = date('Y-m-d',strtotime($SLVresult[0]["min_uridate"]."-01"));
	$next_ym = date('Ym',strtotime($next_ymd));
	for($i=0;$next_ym<=date("Y")."12";$i++){
		
		$SLVresult[$i]["display"] = date('Y年m月',strtotime($next_ymd));
		$SLVresult[$i]["fromValue"] = date('Y-m-d',strtotime($next_ymd));
		$SLVresult[$i]["toValue"] = date('Y-m-d',strtotime($next_ymd." last day of this month"));

		$next_ymd = date('Y-m-d',strtotime($next_ymd." +1 month"));
		$next_ym = date('Ym',strtotime($next_ymd));
		
	}

	//$_SESSION["Event"]      =(empty($_POST["list"])?"%":$_POST["list"]);

}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php" ;
	?>
	<!--ページ専用CSS-->
	<link rel="stylesheet" href="css/style_analysis.css?<?php echo $time; ?>" >

	<script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js' integrity='sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==' crossorigin='anonymous' referrerpolicy='no-referrer'></script>    
	
	<TITLE><?php echo $title." 売上分析";?></TITLE>
</head>
<BODY>
	<div id='app'> 
	<header class='header-color common_header' style='flex-wrap:wrap;height:50px'>
		<div class='title' style='width: 100%;'><a :href='url'><?php echo $title;?></a></div>
	</header>
	<main class='common_body' style='padding-top:55px;width:100%;'>
		<div class='container-fluid'>
		<div class='row'>
		<div class='col-md-12' style='padding:5px;background:white'>
			<input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>
			<div class='container-fluid'>
				<div class='row'>
					<div class='col-12 d-inline-flex ' style='padding-top:3px;'>
						<div>集計期間:</div>
						<div class='' id='YM' style='width:200px;'>
							<input type='radio' class='btn-check' name='options' autocomplete='off' id='ym' checked>
							<label @click='change_mode_ym()' class='btn btn-outline-primary' for='ym' style='font-size:1.2rem;height:25px;border-radius:0;padding-top:2px;'>年月</label>
							<input type='radio' class='btn-check' name='options' autocomplete='off' id='ymd'> 
							<label @click='change_mode_ymd()' class='btn btn-outline-primary' for='ymd' style='font-size:1.2rem;height:25px;border-radius:0;padding-top:2px;'>年月日</label>
						</div>
					</div>
				</div>
				<div v-if='serch_ym===true' class='row'>
					<div class='col-5'>
						<select v-model='date_from' name='date_from' class='form-select form-select-lg' style='margin:5px;' >
						<template v-for='(list,index) in ym_list' :key='list.Value'>
							<option :value='list.fromValue'>{{list.display}}</option>
						</template>
						</select>
					</div>
					<div class='col-2 text-center ' style='padding-top:8px;'>から</div>
					<div class='col-5'>
						<select v-model='date_to' name='date_to' class='form-select form-select-lg' style='margin:5px;' >
						<template v-for='(list,index) in ym_list' :key='list.Value'>
							<option :value='list.toValue'>{{list.display}}</option>
						</template>
						</select>
					</div>
				</div>
				<div v-if='serch_ym===false' class='row'>
					<div class='col-5'>
						<input v-model='date_from' type='date' class='form-control' style='margin:5px;' name='date_from'>
					</div>
					<div class='col-2 text-center' style='padding-top:8px;'>から</div>
					<div class='col-5'>
						<input v-model='date_to' type='date' class='form-control'  style='margin:5px;' name='date_to'>
					</div>
				</div>
				<div class='row'>
					<div class='col-6'>
						<select v-model='analysis_type' name='sum_tani' class='form-select form-select-lg' style='margin:5px' ><!--集計単位-->
							<template v-for='(list,index) in bunseki_menu' :key='list.sort'>
								<option :value='list.val'>{{list.name}}</option>
							</template>
						</select>
					</div>
					<div class='col-6'>
						<select v-model='ev_selected' name='list' class='form-select form-select-lg' style='margin:5px'>
							<option value=''>イベントで絞る</option>
							<template v-for='(list,index) in ev_list' :key='list.LIST'>
								<option :value='list.CODE'>{{list.LIST}}</option>
							</template>
						</select>
					</div>
				</div>
			</div>
		</div>
		</div>
		<div class='row'>
		<div class='col-md-9' id='chart_area_upper_div'>
			<div id='chart_area' style='width:95%;'> <canvas id='ChartCanvas'></canvas></div>
		</div>
		<div class='col-md-3' style='padding:5px'>
			<table v-if='analysis_type!=="urikire"' class='table-striped table-bordered result_table item_0 tour_uri1' style='margin-top:10px;margin-bottom:20px;'><!--white-space:nowrap;-->
				<thead>
					<tr>
						<template v-for='(list,index) in table_labels' :key='list'>
							<th scope='col' style='width:auto;'>{{list}}</th>
						</template>
					</tr>
				</thead>
				<tbody v-for='(row,index) in table_data' :key='row.Labels'>
					<tr>
						<template v-for='(data,index) in row' :key='data'>
							<td v-if='isNaN(data)==false'><div class='text-end'>{{Number(data).toLocaleString()}}</div></td>
							<td v-else>{{data}}</td>
							</template>
					</tr>
				</tbody>
			</table>
			<table v-if='analysis_type==="urikire"' class='table-striped table-bordered result_table item_0 tour_uri1' style='margin-top:10px;margin-bottom:20px;'><!--white-space:nowrap;-->
				<thead>
					<!--<tr>
						<th scope='col' style='width:auto;'>日付</th>
						<th scope='col' colspan="2" style='width:auto;'>Event</th>
					</tr>-->
					<tr>
						<th scope='col' colspan="2" style='width:auto;'>商品</th>
						<th scope='col' style='width:auto;'>出品数</th>
						<th scope='col' style='width:auto;'>完売時刻</th>
					</tr>
				</thead>
				<tbody v-for='(row,index) in table_data' :key='row.UriDate+row.Event+row.ShouhinNM'>
					<tr v-if='index===0'>
						<td colspan="4" class='link'>{{row.UriDate}}:{{row.Event}}</td>
					</tr>
					<tr v-else-if='table_data[index].UriDate+table_data[index].Event !== table_data[index-1].UriDate+table_data[index-1].Event'>
						<td colspan="4" class='link'>{{row.UriDate}}:{{row.Event}}</td>
					</tr>
					<tr>
						<td></td>
						<td>{{row.ShouhinNM}}</td>
						<td class='text-end'>{{Number(row.shuppin_su).toLocaleString()}}</td>
						<td>{{row.売切日時}}</td>
					</tr>
				</tbody>
			</table>
		</div>
		</div><!--row-->
		</div>
	</main>
	</div>
	
	<script>
		var GSI = {}
		function send2(category,lv){
			const form1 = document.getElementById('form1');

			let req = document.createElement('input');
			req.type = 'hidden';
			req.name = 'category';
			req.value = category;
			form1.appendChild(req);

			let req2 = document.createElement('input');
			req2.type = 'hidden';
			req2.name = 'category_lv';
			req2.value = lv;
			form1.appendChild(req2);

			form1.submit();
		}
	</script><!--js-->
	<script>
		const { createApp, ref, onMounted, computed, VueCookies, watch, watchEffect } = Vue
		createApp({
			setup(){
				//chart_type(bar or doughnut)
				const analysis_type = ref('<?php echo $analysis_type; ?>')
				const bunseki_menu = ref(BUNSEKI_MENU)
				var category_lv = 0 //商品分類ごとの売上円グラフで使用。0：大分類　1：中分類　2：小分類
				var over_category = ""   //商品分類ごとの売上円グラフで使用。クリックした分類の下分類の円グラフを表示する際に使用
				var myChart
				
				const drow_chart = (chart_type) => {
					console_log('drow_chart start','lv3')
					if (myChart) {
						console_log('myChart.destroy','lv3')
						myChart.destroy();
					}
					const ctx = document.getElementById('ChartCanvas').getContext('2d');
					let params = {
						type: chart_type,
						data: {
							labels: chart_labels.value//['test']
							,datasets: [{
								//label: "test2"
								label: "売上実績"
								,data: chart_datasets.value//[1000]
								,backgroundColor:chart_color.value//['rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.5)']
								//,barThickness:5
								//,barPercentage: 0.4
							}]
						},
						options: {
								scales: {
									x: {
										//beginAtZero: true
									}
								},
								responsive: true,
								//maintainAspectRatio: false,
								indexAxis: 'y'
						}
					}
					if(chart_type==='bar'){
						params.data.datasets[0]['maxBarThickness'] = 20
						params.data.datasets[0]['barPercentage'] = 0.9
						params.options.maintainAspectRatio = false
					
					}else if(chart_type==='doughnut'){
						params.options={events: ['click']}
						params.options={
							onClick: function (e, el,chart) {
									//円グラフタップ時の子分類データ取得処理を記述
									if (! el || el.length === 0) return;
									console_log('onClick : label ' + chart.data.labels[el[0].index]);
									console_log('onClick : category_lv ' + category_lv);
									console_log('onClick : label ' + e);
									//send2(chart.data.labels[el[0].index],<?php //echo ($category_lv+1); ?>);
									if(category_lv>=2){
										category_lv = 0
									}else{
										category_lv += 1
									}
									over_category = chart.data.labels[el[0].index]
									console_log('onClick : category_lv ' + category_lv);
									console_log('onClick : over_category ' + over_category);
									get_analysis_data()
								}
						}
					}else if(chart_type==='line'){
						params.data.labels = chart_x.value//['X軸1','X軸2','X軸3','X軸4']
						params.options = {}
						//以下繰り返す
						for(let i=0;i<chart_labels.value.length;i++){
							params.data.datasets[i] = {
								borderColor: 'rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.8)',
								label:chart_labels.value[i],
								tension: 0.2,
								pointRadius:5,
								hitRadius:15,
								pointHoverRadius:8,
								data: chart_datasets.value[i]
							}
						}
					}
					
					myChart = new Chart(ctx, params);
				}
				const serch_ym = ref(true)
				const change_mode_ymd = () =>{
					serch_ym.value = false
				}
				const change_mode_ym = () =>{
					serch_ym.value = true
				}
				const ev_list = ref([])
				const ev_selected = ref('')
				const date_from = ref('<?php echo date("Y")."-01-01"; ?>')
				const date_to = ref('<?php echo date("Y")."-12-31"; ?>')

				const get_event = () => {//期間内のイベント一覧取得ajax
					console_log("get_event start",'lv3')
					let params = new URLSearchParams()
					params.append('user_id', '<?php echo $_SESSION["user_id"];?>')
					params.append('date_from', date_from.value)
					params.append('date_to', date_to.value)
					params.append('list_type', 'Event')
					axios
					.post('ajax_get_event_list.php',params)
					.then((response) => {
						console_log(response.data,'lv3')
						ev_list.value = [...response.data]
					})
					.catch((error) => {
						console_log(`get_event ERROR:${error}`,'lv3')
					})
					return 0;
				};//イベントリスト取得ajax

				const analysis_data = ref([])
				const CSRF = ref('<?php echo $csrf_create; ?>')
				const chart_type = ref('')
				const chart_labels = ref([])
				const chart_datasets = ref([])
				const chart_color = ref([])
				const chart_x = ref([])
				const table_labels = ref([])
				const table_data = ref([])
				const get_analysis_data = () => {//売上分析データ取得ajax
					console_log("get_analysis_data start",'lv3')
					let params = new URLSearchParams()
					if(analysis_type.value != '12'){//ジャンル別売上円グラフ以外
						category_lv = 0
						over_category = ""
					}
					params.append('user_id', '<?php echo $_SESSION["user_id"];?>')
					params.append('date_from', date_from.value)
					params.append('date_to', date_to.value)
					params.append('analysis_type', analysis_type.value)
					params.append('event', ev_selected.value)
					params.append('tokui', ev_selected.value)
					params.append('csrf_token', CSRF.value)
					params.append('category_lv', category_lv)
					params.append('over_category', over_category)
					console_log(category_lv)
					console_log(params)

					axios
					.post('ajax_get_analysi_uridata.php',params)
					.then((response) => {
						console_log(response.data,'lv3')
						CSRF.value = response.data.csrf_create
						chart_type.value = response.data.chart_type
						//chart_labels.value = response.data.labels
						chart_datasets.value = response.data.data
						if(response.data.chart_type==='doughnut'){
							chart_labels.value = response.data.labels_long
						}else{
							chart_labels.value = response.data.labels
						}
						if(response.data.chart_type!=='line'){
							for(let i=0;i<=chart_datasets.value.length;i++){
								chart_color.value[i]='rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.8)'
							}
						}
						//グラフエリアのサイズ設定
						document.getElementById("chart_area").style.display='block'
						document.getElementById("chart_area_upper_div").style.display='block'

						if(response.data.chart_type==='bar'){//棒グラフはデータ数に応じて変える
							//document.getElementById("chart_area").style.height='750px'
							if(Number(chart_datasets.value.length) * 30 < 150){
								document.getElementById("chart_area").style.height='150px'
							}else{
								document.getElementById("chart_area").style.height=`${Number(chart_datasets.value.length) * 30}px`
							}
						}else if(response.data.chart_type==='-'){//グラフ不要
							document.getElementById("chart_area").style.display='none'
							document.getElementById("chart_area_upper_div").style.display='none'
						}else{
							document.getElementById("chart_area").style.height='100%'
						}

						console_log(document.getElementById("chart_area").style.height)
						
						if(response.data.chart_type==='line'){
							let hour = response.data.xStart
							for(let i=0;hour<=response.data.xEnd;i++){
								chart_x.value[i] = hour
								hour++
							}
							for(let i=0;i<chart_datasets.value.length;i++){
								chart_datasets.value[i] = chart_datasets.value[i].slice(response.data.xStart,response.data.xEnd+1)
							}
						}

						//table_labels.value = [...response.data.aryColumn]
						table_labels.value = response.data.aryColumn
						//table_data.value = [...response.data.result]
						table_data.value = response.data.result

						if(analysis_type.value === "Area_tanka_1"){
							chart_labels.value.forEach((item,index)=>{
								//console_log(item)
								let muniData = GSI.MUNI_ARRAY[item]
								let [prefCode, pref, muniCode, city] = muniData.split(',')
								//item = `${pref}${city}`
								chart_labels.value[index] = `${city.replace(/\s+/g, "")}`
							})

							table_data.value.forEach((row,index)=>{
								console_log(row.Labels)
								let muniData = GSI.MUNI_ARRAY[row.Labels]
								let [prefCode, pref, muniCode, city] = muniData.split(',')
								//item = `${pref}${city}`
								table_data.value[index]["Labels"] = `${pref.replace(/\s+/g, "")}${city.replace(/\s+/g, "")}`
							})
						}
						if(analysis_type.value === "Area_tanka_2"){
							chart_labels.value.forEach((item,index)=>{
								//console_log(item)
								let [muniCd,jusho] = item.split(',')
								let muniData = GSI.MUNI_ARRAY[muniCd]
								let [prefCode, pref, muniCode, city] = muniData.split(',')
								//item = `${pref}${city}`
								chart_labels.value[index] = `${city.replace(/\s+/g, "")}${jusho}`
							})

							table_data.value.forEach((row,index)=>{
								console_log(row.Labels)
								let [muniCd,jusho] = row.Labels.split(',')
								let muniData = GSI.MUNI_ARRAY[muniCd]
								let [prefCode, pref, muniCode, city] = muniData.split(',')
								//item = `${pref}${city}`
								table_data.value[index]["Labels"] = `${pref.replace(/\s+/g, "")}${city.replace(/\s+/g, "")}${jusho}`
							})
						}
						if(response.data.chart_type!=='-'){
							drow_chart(response.data.chart_type)
						}
					})
					.catch((error) => {
						console_log(`get_analysis_data ERROR:${error}`,'lv3')
					})
					.finally(()=>{
						//console_log(myChart,'lv3')
					})
					return 0;
				};//売上分析データ取得ajax
				
				watch([date_from,date_to,analysis_type,ev_selected],() => {
					get_analysis_data()
				})
				watch([date_from,date_to],() => {
					get_event()
				})

				const ym_list = ref([
					<?php
						foreach($SLVresult as $row){
							echo "{display:'".$row["display"]."',fromValue:'".$row["fromValue"]."',toValue:'".$row["toValue"]."'},";
						}
					?>
				])
				const url = computed(() =>{
					return 'analysis_menu.php?csrf_token=' + CSRF.value
				})
				onMounted(() => {
					get_event()
					get_analysis_data()
					console_log(GSI)
				})
				return{
					bunseki_menu,
					ev_list,
					get_event,
					serch_ym,
					change_mode_ymd,
					change_mode_ym,
					date_from,
					date_to,
					ym_list,
					analysis_type,
					ev_selected,
					CSRF,
					url,
					chart_labels,
					chart_datasets,
					//chart_type,
					//chart_color,
					//chart_x,
					table_labels,
					table_data,
				}
			}
		}).mount('#app');
	</script><!--chart.js-->
	<script src="https://maps.gsi.go.jp/js/muni.js"></script><!--gio住所逆引リスト-->
</BODY>
</html>
<?php
$stmt = null;
$pdo_h = null;
?>



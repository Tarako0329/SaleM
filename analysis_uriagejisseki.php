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
	$SLVsql = "select DATE_FORMAT(min(UriDate), '%Y%m') as min_uridate from UriageData where uid = :user_id";
	$stmt = $pdo_h->prepare($SLVsql);
	$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
	$stmt->execute();
	$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
/*
	$next_ymd = date('Y-m-d',strtotime($result[0]["min_uridate"]."-01"));
	$next_ym = date('Ym',strtotime($next_ymd));
	for($i=0;$next_ym<=date("Y")."12";$i++){
		
		$SLVresult[$i]["display"] = date('Y年m月',strtotime($next_ymd));
		$SLVresult[$i]["fromValue"] = date('Y-m-d',strtotime($next_ymd));
		$SLVresult[$i]["toValue"] = date('Y-m-d',strtotime($next_ymd." last day of this month"));

		$next_ymd = date('Y-m-d',strtotime($next_ymd." +1 month"));
		$next_ym = date('Ym',strtotime($next_ymd));
		
	}
*/
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
	<style>
		.btn-check-label{
			font-size:1.2rem;
			height:25px;
			/*width:*/
			border-radius:0;
			padding-top:2px;
		}
		.serch_val_input{
			width: 130px;
		}
		.serch_val_input2{
			width: 200px;
		}
	</style>
	<script src='https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js' integrity='sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==' crossorigin='anonymous' referrerpolicy='no-referrer'></script>    
	<TITLE><?php echo $title." 売上分析";?></TITLE>
</head>
<BODY>
	<div id='app'> 
	<header class='header-color common_header' style='flex-wrap:wrap;height:50px'>
		<div class='title' style='width: 100%;'><a :href='url'><?php echo $title;?></a></div>
	</header>
	<main class='common_body' style='padding-top:55px;width:100%;'>
		<div class='container'>
			<div class='row'>
				<div class='col-md-12' style='padding:5px;background:white'>
					<input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>
					<div class='container-fluid'>
						<div class='row'><!--検索条件設定エリア-->
							<div class='col-12 d-inline-flex ' style='padding-top:3px;'>
								<div class='input-group ms-3' style=''>
									<input type='radio' class='btn-check' name='options' autocomplete='off' id='nm' >
									<label  class='btn btn-outline-black btn-check-label ps-0' for='nm' style='pointer-events:none;'>集計期間</label>
									<input type='radio' class='btn-check' name='options' autocomplete='off' id='y' v-model='serch_ym' value='Y'>
									<label  class='btn btn-outline-primary btn-check-label' for='y' style=''>単年</label>
									<input type='radio' class='btn-check' name='options' autocomplete='off' id='y-y' v-model='serch_ym' value='Y-Y'>
									<label  class='btn btn-outline-primary btn-check-label' for='y-y' style=''>年度</label>
									<input type='radio' class='btn-check' name='options' autocomplete='off' id='ym' v-model='serch_ym' value='YM'>
									<label  class='btn btn-outline-primary btn-check-label' for='ym' style=''>年月</label>
									<input type='radio' class='btn-check' name='options' autocomplete='off' id='ymd' v-model='serch_ym' value='YMD'> 
									<label class='btn btn-outline-primary btn-check-label' for='ymd' style=''>日付</label>
								</div>
							</div>
						</div><!--検索条件設定エリア-->
						<div v-if='serch_ym==="Y"' class='row'>
							<div class='col-12 d-inline-flex'>
								<select v-model='date_from' name='date_from' class='form-select form-select-lg serch_val_input' style='margin:5px;' >
									<template v-for='(list,index) in y_list' :key='list.Value'>
										<option :value='list.fromValue'>{{list.display}}</option>
									</template>
								</select>
							</div>
						</div>
						<div v-if='serch_ym==="Y-Y"' class='row'>
							<div class='col-12 d-inline-flex'>
								<select v-model='date_from' name='date_from' class='form-select form-select-lg serch_val_input' style='margin:5px;' >
								<template v-for='(list,index) in y_list' :key='list.Value'>
									<option :value='list.fromValue'>{{list.display}}</option>
								</template>
								</select>
								<div class='text-center ms-3 me-3' style='padding-top:8px;'>から</div>
								<select v-model='date_to' name='date_to' class='form-select form-select-lg serch_val_input' style='margin:5px;' >
								<template v-for='(list,index) in y_list' :key='list.Value'>
									<option :value='list.toValue'>{{list.display}}</option>
								</template>
								</select>
							</div>
						</div>
						<div v-if='serch_ym==="YM"' class='row'>
							<div class='col-12 d-inline-flex'>
								<select v-model='date_from' name='date_from' class='form-select form-select-lg serch_val_input' style='margin:5px;' >
								<template v-for='(list,index) in ym_list' :key='list.Value'>
									<option :value='list.fromValue'>{{list.display}}</option>
								</template>
								</select>
								<div class='text-center ms-3 me-3' style='padding-top:8px;'>から</div>
								<select v-model='date_to' name='date_to' class='form-select form-select-lg serch_val_input' style='margin:5px;' >
								<template v-for='(list,index) in ym_list' :key='list.Value'>
									<option :value='list.toValue'>{{list.display}}</option>
								</template>
								</select>
							</div>
						</div>
						<div v-if='serch_ym==="YMD"' class='row'>
							<div class='col-12 d-inline-flex'>
								<input v-model='date_from' type='date' class='form-control serch_val_input' style='margin:5px;' name='date_from'>
								<div class='text-center ms-3 me-3' style='padding-top:8px;'>から</div>
								<input v-model='date_to' type='date' class='form-control serch_val_input'  style='margin:5px;' name='date_to'>
							</div>
						</div>
						<div class='row'>
							<div class='col-6 d-inline-flex'>
								<select v-model='analysis_type' name='sum_tani' class='form-select form-select-lg serch_val_input2' style='margin:5px' ><!--集計単位-->
									<template v-for='(list,index) in bunseki_menu' :key='index'>
										<option :value='list.val'>{{list.name}}</option>
									</template>
								</select>
								<select v-model='ev_selected' name='list' class='form-select form-select-lg serch_val_input2' style='margin:5px'>
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
			<div class='row' id='chart_area_upper_row'>
				<div class='col-md-9' id='chart_area'>
					<canvas id='ChartCanvas'></canvas>
				</div>
				<div class='col-md-3' style='padding:5px'>
					<table v-if='analysis_type!=="urikire"' class='table-striped table-bordered result_table item_0 tour_uri1' style='margin-top:10px;margin-bottom:20px;'><!--white-space:nowrap;-->
						<thead>
							<tr>
								<template v-for='(list,index) in table_labels' :key='index'>
									<th scope='col' style='width:auto;'>{{list}}</th>
								</template>
							</tr>
						</thead>
						<tbody v-for='(row,index) in table_data' :key='row.UKEY'>
							<tr>
								<template v-for='(data,index) in row' :key='index'>
									<template v-if='index!=="UKEY"'>
										<td v-if='isNaN(data)==false'>
											<div class='text-end'>{{Number(data).toLocaleString()}}</div>
										</td>
										<td v-else>{{data}}</td>
									</template>
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
						<tbody v-for='(row,index) in table_data' :key='index'>
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
	<script src="https://maps.gsi.go.jp/js/muni.js"></script><!--gio住所逆引リスト-->
	<script src="analysis_uriagejisseki.js?<?php echo $time; ?>"></script>
	<script>
		analysis_uriagejisseki(<?php echo "'".$analysis_type."',".$_SESSION["user_id"].",'".$csrf_create."',".$result[0]["min_uridate"] ;?>).mount('#app');
	</script>
</BODY>
</html>
<?php
$stmt = null;
$pdo_h = null;
?>

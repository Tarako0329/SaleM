<?php
{
	//memo !empty()　は 変数未定義、空白、NULLの場合にfalseを返す
	require "php_header.php";

	$rtn = csrf_checker(["menu.php"],["G","C","S"]);
	if($rtn !== true){
  	  redirect_to_login($rtn);
	}

	$rtn=check_session_userid($pdo_h);
	$csrf_create = csrf_create();
	
	//税区分M取得.基本変動しないので残す
	$ZEIsql="select * from ZeiMS order by zeiKBN;";
	$ZEIresult = $pdo_h->query($ZEIsql);
	
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php";
	?>
	<!--ページ専用CSS-->
	<link rel='stylesheet' href='css/style_UriageData_Correct.css?<?php echo $time; ?>' >
	<TITLE><?php echo TITLE." 売上実績";?></TITLE>
</head>
<body>
	<div id='app'>
	<header class='header-color common_header'>
		<div class='title' style='width: 100%;height:37px;'>
			<a href='menu.php'><?php echo TITLE;?></a>
		</div>
		<div style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>期間：{{UriDateFrom}} ～ {{UriDateTo}}</div>
		<div v-if='filter_flg[0]' style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>
			<button type='button' class='btn-view' @click='reset_filter' style='padding:1px 3px;font-size:1rem;background-color: var(--panel-bk-color);margin-right:5px;'><i class="fa-solid fa-filter fa-lg "></i>解除</button>
			<i class="fa-solid fa-filter fa-lg awesome-color-white"></i>：{{filter_flg[1]}}
		</div>
		<a href="#" style='position:fixed;color:inherit;right:15px;top:45px;' data-bs-toggle='modal' data-bs-target='#modal_help1'>
				<i class="fa-regular fa-circle-question fa-lg awesome-color-white"></i>
		</a>
	</header>
	<div class='header_menu' style='border-bottom:solid var(--panel-bd-color) 0.5px;padding:0;'>
		<nav class="navbar navbar-expand" style='padding:0;width:90%;'>
			<div class="container-fluid" >
				<div class="navbar-brand" style='padding:5px;font-weight:800;'>売上実績<br>メニュー</div>
	  	  <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" 
	  	  aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
	  	    <span class="navbar-toggler-icon"></span>
	  	  </button>
	  	  <div class="collapse navbar-collapse" id="navbarSupportedContent">
	  	    <ul class="navbar-nav me-auto mb-2 mb-lg-0">
	  	      <li class="nav-item">
	  	        <button :class="btn_class[0]" @click='Type_changer("sum_events")'>イベント別集計</button>
	  	      </li>
	  	      <li class="nav-item">
	  	        <button :class="btn_class[1]" @click='Type_changer("sum_items")'>商品別集計</button>
	  	      </li>
	  	      <li class="nav-item">
	  	        <button :class="btn_class[2]" @click='Type_changer("rireki")'>売上明細</button>
	  	      </li>
	  	    </ul>
	  	  </div>
	  	</div>
		</nav>
		<div style='width:20%;max-width:60px;text-align:right;' class='item_2'><!--修正モードのトグルボタン-->
			<span style='margin-bottom:0px'>修正モード</span>
			<div class="switchArea">
				<input v-model='UriageData_Correct_mode' type="checkbox" id="switch1">
				<label for="switch1" ><span></span></label>
				<div id="swImg" ></div>
			</div>
		</div><!--修正モードのトグルボタン-->
	</div>

	<main class='common_body' id='body' :style='common_body_style'>
		<div class='container-fluid'>
			<template v-if='MSG!==""'>
				<div v-bind:class='alert_status' role='alert'>{{MSG}}</div>
			</template>
			
			<div id='uritable'>
				<table class='table-striped table-bordered result_table item_0 tour_uri1' style='margin-top:10px;margin-bottom:20px;'><!--white-space:nowrap;-->
					<thead>
					<tr>
						<th scope='col' style='width:10px;'></th>
						<th scope='col' style='width:130px;'>商品</th>
						<th v-if='Type==="sum_items"' scope='col' style='width:35px;'>出品数</th>
						<th scope='col' style='width:30px;'>数</th>
						<th v-if='Type==="sum_items"' scope='col' style='width:35px;'>残数</th>
						<th scope='col' style='width:60px;' class='d-none d-md-table-cell'>単価</th>
						<th scope='col' style='width:60px;'>売上</th>
						<th scope='col' style='width:60px;' class='d-none d-md-table-cell'>税</th>
						<th scope='col' style='width:50px;'>原価</th>
						<th scope='col' style='width:60px;'>粗利</th>
						<!--<th v-if='Type==="rireki"' scope='col' class='d-none d-md-table-cell'>天候</th>-->
						<th v-if='Type==="rireki"' scope='col' style='width:20px;'></th>
					</tr>
					</thead>
					<tbody v-for='(list,index) in UriageList_filter' :key='list.uid + list.UriDate + list.UriageNO + list.ShouhinCD'>
						<!--売上日+Event行-->
						<tr v-if='(index===0) || (index!==0 && list.UriDate + list.Event !== UriageList_filter[index-1].UriDate + UriageList_filter[index-1].Event)' class='tr_stiky'>
							<td :colspan='colspan' class='tr_stiky' style='white-space:nowrap;'>
								<span role='button' class='link' @click='set_filter("UriDate",list.UriDate,"")'> 売上日：{{list.UriDate}}</span>
								<span role='button' class='link' @click='set_filter("Event",list.Event+list.TokuisakiNM,"")'>『{{list.Event}}{{list.TokuisakiNM}}』</span>
								<img v-if='(list.icon.length>=5) && (Type!=="rireki")' style='height:20px;' :src='`https://openweathermap.org/img/wn/${list.icon}`'>
								<template v-if='(Type!=="rireki")'>（<span style='color:red;'>{{list.max_temp}}</span>/<span style='color:blue;'>{{list.min_temp}}</span>）</template>
							</td>
							<td class='text-right d-none d-md-table-cell'></td>
							<td class='text-right d-none d-md-table-cell'></td>
							<td v-if='Type==="rireki"' class='text-right d-none d-md-table-cell'></td>
							<td v-if='Type==="rireki"' class='text-right d-table-cell d-md-none'></td>
							<!--<td></td>-->
						</tr><!--売上日+Event行-->
						<tr v-if='(index===0 && (Type==="rireki")) || (index!==0 && list.UriageNO !== UriageList_filter[index-1].UriageNO)'><!--売上No行-->
							<td :colspan='colspan' role='button' @click='set_filter("UriNO",list.UriageNO,"")'>
								<span class='link'>
									No.{{list.UriageNO}}
								</span>
								<img style='margin-left:5px;height:20px;' v-if='list.icon.length>=5' :src='`https://openweathermap.org/img/wn/${list.icon}`'>
								<span>（{{list.temp}}℃ {{list.description}}）</span>
							</td>
							<td class='text-right d-none d-md-table-cell'></td>
							<td class='text-right d-none d-md-table-cell'></td>
							<!--<td v-if='Type==="rireki"' class='text-right d-none d-md-table-cell'></td>-->
							<td v-if='Type==="rireki"'>
								<template v-if='list.RNO==0'>
								<a @click='delete_Uriage(list.UriageNO, "%")' href='#'>
									<i class='fa-regular fa-trash-can'></i>
								</a>
								</template>
							</td>
						</tr><!--売上No行-->
						<tr><!--売上明細行-->
							<!--
							<td v-if='list.UriageNO%2===0' role='button' class='text-center' @click='set_filter("UriNO",list.UriageNO,"")'><span class='link'>★</span></td>
							<td v-if='list.UriageNO%2!==0' role='button' class='text-center' @click='set_filter("UriNO",list.UriageNO,"")'><span class='link'>☆</span></td>
							-->
							<td></td>
							<td role='button' class='link' @click='set_filter("ShouhinCD",list.ShouhinCD,list.ShouhinNM)'>{{list.ShouhinNM}}</td>
							<td align='right' v-if='Type==="sum_items"' class='text-right'>{{Number(list.shuppin_su)}}</td>
							<td align='right' class='text-right'>{{Number(list.su)}}</td>
							<td align='right' v-if='Type==="sum_items"' class='text-right'>{{Number(list.zan_su)}}</td>
							<td align='right' class='text-right d-none d-md-table-cell'>{{Number(list.tanka).toLocaleString()}}</td>
							<td align='right' class='text-right'>{{Number(list.UriageKin).toLocaleString()}}</td>
							<td align='right' class='text-right d-none d-md-table-cell'>{{Number(list.zei).toLocaleString()}}</td>
							<td align='right' class='text-right'>{{Number(list.genka).toLocaleString()}}</td>
							<td align='right' class='text-right'>{{Number(list.arari).toLocaleString()}}</td>
							<!--
							<td v-if='Type==="rireki"' class='d-none d-md-table-cell'>
								<img v-if='list.icon.length>=5' style='height:20px;' :src='`https://openweathermap.org/img/wn/${list.icon}`'>（<span>{{list.temp}}℃ </span><span>{{list.description}}</span>）
							</td>
							-->
							<td v-if='Type==="rireki"' >
								<template v-if='list.RNO==0 && list.zeiKBN==0'><!--領収書未発行かつ非課税売上のみ削除可能-->
								<a @click='delete_Uriage(list.UriageNO, list.ShouhinCD)' href='#'>
									<i class='fa-regular fa-trash-can'></i>
								</a>
								</template>
							</td>
						</tr><!--売上明細行-->
					</tbody>
				</table>
			</div>
		</div>
	</main>
	<footer v-if='UriageData_Correct_mode===false' class='common_footer'>
		<div class='kaikei'>
			合計(税込)：￥{{(sum_uriage + sum_uriage_zei).toLocaleString()}}-<br>
			<span style='font-size:1.3rem;'>内訳(本体+税)：￥{{sum_uriage.toLocaleString()}} + {{sum_uriage_zei.toLocaleString()}}</span>
		</div>
		<div class='right1 item_1'>
			<button type='button' class='btn--chk' style='border-radius:0;' data-bs-toggle='modal' data-bs-target='#UriModal'>検　索</button>
		</div>
	</footer>
	<div class="loader-wrap" v-show='loader'>
		<div class="loader">Loading...</div>
	</div>

	<!--修正エリア-->
	<div v-if='UriageData_Correct_mode' class='footer_update_area'>
		<form class='form-horizontal update_areas tour_uri2' @submit.prevent='on_submit_Uriage_Update'>
						
			<input type='hidden' name='csrf_token' :value='csrf'>
			
			<input type='hidden' name='up_uritanka' :value='upd_hontai'>
			<input type='hidden' name='up_zei' :value='upd_zei_kin'>

			<input type='hidden' name='w_date_from' :value='UriDateFrom'>
			<input type='hidden' name='w_date_to' :value='UriDateTo'>
			<input type='hidden' name='w_date' :value='filter_Uridate'>
			<input type='hidden' name='w_event' :value='filter_Event'>
			<input type='hidden' name='w_shouhincd' :value='filter_Shouhin'>
			<input type='hidden' name='w_urino' :value='filter_UriNo'>

			<div class='row mb-2'>
				<p style='color:red;margin-bottom: 2px;font-size: large;'>※上記データが更新対象となります。</p>
				<p style='color:red;margin-bottom: 2px;font-size: large;'>※<span style='color:blue;'>青字の項目</span>のタップで絞込みできます。</p>
			</div>
			<div class='row mb-2'><!--売上日/help icon-->
				<div class="col-11" style='display:flex;'>
  	    	<div class="form-check">
    	    	<input class="form-check-input" type="checkbox" id="chk_uridate" name='chk_uridate' onchange='chk_visible(this,"#up_uridate")' >
      	  	<label class="form-check-label" for="chk_uridate">売上日</label>
      		</div>
					<input type='date' style='font-size:1.5rem;width:250px;background-color:#999999;' name='up_uridate' id='up_uridate' maxlength='10'  class='form-control'>
    		</div>
				<div class="col-1">
					<a href="#" style='color:inherit;' onclick='urihelp()'>
						<i class="fa-regular fa-circle-question fa-2x awesome-color-panel-border-same"></i>
					</a>
				</div>
			</div><!--売上日/help icon-->
			<div class='row mb-2'><!--イベント名-->
				<div class="col-11" style='display:flex;'>
  	    	<div class="form-check">
    	    	<input class="form-check-input" type="checkbox" id="chk_event" name='chk_event' onchange='chk_visible(this,"#up_event")' >
      	  	<label class="form-check-label" for="chk_event">ｲﾍﾞﾝﾄ名</label>
      		</div>
					<input type='text' style='font-size:1.5rem;width:250px;background-color:#999999;' name='up_event' id='up_event' maxlength='10'  class='form-control'>
    		</div>
			</div><!--イベント名-->
			<div class='row mb-2'><!--顧客名-->
				<div class="col-11" style='display:flex;'>
  	    	<div class="form-check">
    	    	<input class="form-check-input" type="checkbox" id="chk_kokyaku" name='chk_kokyaku' onchange='chk_visible(this,"#up_kokyaku")' >
      	  	<label class="form-check-label" for="chk_kokyaku">顧客名</label>
      		</div>
					<input type='text' style='font-size:1.5rem;width:250px;background-color:#999999;' name='up_kokyaku' id='up_kokyaku' maxlength='10'  class='form-control'>
    		</div>
			</div><!--顧客名-->
			<template v-if='false'>
			<div class='row mb-1'><!--売上単価-->
				<div class="col-11" style='display:flex;'>
  	    	<div class="form-check">
    	    	<input class="form-check-input" type="checkbox" id="chk_urikin" name='chk_urikin' onchange='chk_visible(this,"#up_tanka")' >
      	  	<label class="form-check-label" for="chk_urikin">売上単価</label>
      		</div>
					<input v-model='upd_tanka' type='number' style='font-size:1.5rem;width:100px;background-color:#999999;' maxlength='10' id='up_tanka' class='form-control'>
					<div style='padding:0 5px;'>
						<input type='radio' class='btn-check' name='options' value='komi' autocomplete='off' v-model='upd_zei_kominuki' id='plus_mode' checked>
						<label class='btn btn-outline-primary' style='font-size:1.2rem;padding:1px;' for='plus_mode'>税込</label>
						<input type='radio' class='btn-check' name='options' value='nuki' autocomplete='off' v-model='upd_zei_kominuki' id='minus_mode' >
						<label class='btn btn-outline-primary' style='font-size:1.2rem;padding:1px;' for='minus_mode'>税抜</label>
					</div>
					<select v-model='upd_zei_kbn' class='form-select' style='padding-top:0;height:20px;width:80px;' name='up_zeikbn'>
						<option value=''></option>
						<?php
							foreach($ZEIresult as $row){
								echo "<option value=".secho($row["zeiKBN"]).">".secho($row["hyoujimei"])."</option>\n";
							}
						?>
					</select>
    		</div>
			</div><!--売上単価-->
			<div class='row mb-2'><!--売上単価計算結果-->
				<div class="col-11" style='display:flex;'>
  	    	<div class="form-check">
						<!--space-->
      		</div>
					税込単価：{{(upd_hontai+upd_zei_kin).toLocaleString()}}（本体：{{upd_hontai.toLocaleString()}}　消費税：{{upd_zei_kin.toLocaleString()}}-）
    		</div>
			</div><!--売上単価計算結果-->
			</template>
			<div class='row mb-2'><!--原価単価-->
				<div class="col-11" style='display:flex;'>
  	    	<div class="form-check">
    	    	<input class="form-check-input" type="checkbox" id="chk_genka" name='chk_genka' onchange='chk_visible(this,"#up_urigenka")'>
      	  	<label class="form-check-label" for="chk_genka">原価単価</label>
      		</div>
					<input type='number' style='font-size:1.5rem;width:250px;background-color:#999999;' name='up_urigenka' id='up_urigenka' maxlength='10'  class='form-control'>
    		</div>
			</div><!--原価単価-->
			<div class='row mb-2'><!--ボタン-->
				<div class="col-12" style='padding-left:80px;' >
					<button @click='btn_controler()' type='button' class='btn-lg btn-primary' style='padding-left:30px;padding-right:30px;'>{{btn_controle[0]}}</button>
					<button v-if='btn_controle[1]' type='submit' class='btn-lg btn-warning' style='padding-left:30px;padding-right:30px;margin-left:10px;'>更　新</button>
    		</div>
			</div><!--ボタン-->
		</form><!--修正エリア-->
	</div><!--修正エリア-->
	<!--売上実績検索条件-->
	<div class='modal fade' id='UriModal' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content' style='font-size:1.5rem; font-weight: 600;background-color:rgba(255,255,255,0.8);'>
							
				<form class='form-horizontal' method='post' action='UriageData_Correct.php' id='form3'>
					<input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>
					<input type='hidden' name='mode' value='select'>
					<div class='modal-header'>
						<div class='modal-title' id='myModalLabel'>表示条件変更</div>
					</div>
					<div class='modal-body'>
						<div>
							<label for='uridate' class='control-label'>売上日～：</label>
							<input v-model='UriDateFrom' type='date' style='font-size:1.5rem;' name='UriDateFrom' maxlength='10' id='uridate' class='form-control'>
						</div>
						<div>
							<label for='uridateto' class='control-label'>～売上日：</label>
							<input v-model='UriDateTo' type='date' style='font-size:1.5rem;' name='UriDateTo' maxlength='10' id='uridateto' class='form-control'>
						</div>
						<!--
						<div>
							<label for='Event' class='control-label'>イベント/顧客名：</label>
							<select v-model='Event' name='Event' style='font-size:1.5rem;padding-top:0;' id='Event' class='form-control' aria-describedby='EvHelp'>
								<option value=''></option>
								!--//Ajaxで取得に変更--
							</select>
							<small id='EvHelp' class='form-text text-muted'>売上日の期間を変更すると選択肢が更新されます。</small>
						</div>
						-->
						<div>
							<label for='Type' class='control-label'>表示：上で指定した期間中の</label>
							<select v-model='Type' @change='get_UriageList()' name='Type' style='font-size:1.5rem;padding-top:0;' id='Type' class='form-control'>
								<option value='rireki' >売上履歴</option>
								<option value='sum_items' >売上を日付＞イベント＞商品単位で集計</option>
								<option value='sum_events' >売上を日付＞イベント単位で集計</option>
							</select>
						</div>
					</div>
					<div class='modal-footer'>
						<button type='button' @click='get_UriageList()' style='font-size:1.5rem;color:#fff' class='btn btn-primary' data-bs-dismiss="modal">決　定</button>
					</div>
				</form>
			</div>
		</div>
	</div><!--売上実績検索条件-->
	<!--help1-->
	<div class='modal fade' id='modal_help1' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content' style='font-size:1.2rem; font-weight: 600;background-color:rgba(255,255,255,0.8);'>
				<!--
				<div class='modal-header'>
					<div class='modal-title' id='myModalLabel'>help</div>
				</div>
				-->
				<div class='modal-body'>
					<h4 style='margin-bottom:0;'>ボタンについて</h4>
					<div style='border:solid thin var(--panel-bd-color);border-radius:3px;padding:10px;margin-bottom:5px;'>
						<li class='btn-view' style='font-size:1.2rem;padding:2px'>イベント集計</li>　<p>全期間の売上を『日付＞イベント』単位で集計して表示</p>
						<li class='btn-view' style='font-size:1.2rem;padding:2px'>商品集計</li>　<p>現在表示されている売上を『日付＞イベント＞商品』単位で集計して表示</p>
						<li class='btn-view' style='font-size:1.2rem;padding:2px'>会計明細</li>　<p>現在表示している売上のお会計明細を表示</p>
					</div>
					<h4 style='margin-bottom:0;'>表の操作</h4>
					<div style='border:solid thin var(--panel-bd-color);border-radius:3px;padding:10px;margin-bottom:5px;'>
						<p>イベント集計モードで表示している場合、「<span style='color:blue;'>日付</span>」「<span style='color:blue;'>イベント名</span>」をタップすると明細を表示。</p>
						<p><span style='color:blue;'>青文字</span>をタップすると、タップしたデータと同じ条件で絞り込まれます。</p>
						<p>例：イベント名をタップすると、同名のイベントの売上のみが表示</p>
					</div>        
					<h4 style='margin-bottom:0;'>修正モードについて</h4>
					<div style='border:solid thin var(--panel-bd-color);border-radius:3px;padding:10px;'>
						右上の「<span style='color:blue;'>修正モード</span>」をONにすると、誤入力した売上を修正できます。
					</div>
				</div>
				<div class='modal-footer'>
				</div>
			</div>
		</div>
	</div><!--help1-->
	</div><!--app-->
	
	<script>
		const { createApp, ref, onMounted, computed, VueCookies } = Vue;
		createApp({
			setup(){
				const MSG = ref('')
				const alert_status = ref(['alert'])
				const csrf = ref('<?php echo $csrf_create; ?>')
				//売上取得関連
				const UriageList = ref([])		//売上リスト
				const UriDateFrom = ref('<?php echo date("Y")."-01-01"; ?>')
				const UriDateTo = ref('<?php echo date("Y")."-12-31"; ?>')
				const Type = ref('rireki')
				const btn_class = ref(['btn-view','btn-view','btn-view btn-selected'])
				const get_UriageList = () => {//売上リスト取得ajax
					console_log("get_UriageList start",'lv3');
					let params = new URLSearchParams()
					params.append('user_id', '<?php echo $_SESSION["user_id"];?>')
					params.append('UriDateFrom', UriDateFrom.value)
					params.append('UriDateTo', UriDateTo.value);
					params.append('Type', Type.value);
					axios
					.post('ajax_get_Uriage2.php',params)
					.then((response) => {UriageList.value = [...response.data]
															//console_log('get_UriageList succsess','lv3')
						})
					.catch((error) => console_log(`get_UriageList ERROR:${error}`,'lv3'));
				}//売上リスト取得ajax
				const Type_changer = (Hyou_Type) => {
					console_log(`Type_changer ${Hyou_Type}`,'lv3')
					Type.value = Hyou_Type

					btn_class.value[0] = 'btn-view'
					btn_class.value[1] = 'btn-view'
					btn_class.value[2] = 'btn-view'
					if(Hyou_Type==='sum_events'){
						btn_class.value[0] = 'btn-view btn-selected'
					}else if(Hyou_Type==='sum_items'){
						btn_class.value[1] = 'btn-view btn-selected'
					}else{
						btn_class.value[2] = 'btn-view btn-selected'
					}
					get_UriageList()
				}

				//フィルター関連
				const filter_Uridate = ref('%')
				const filter_Event = ref('%')
				const filter_Shouhin = ref('%')
				const filter_UriNo = ref('%')
				const filter_flg = ref([false,''])
				const set_filter = (colum,word,word2) =>{
					console_log(`set_filter start params(${colum} , ${word})`,'lv3')
					if(colum==='UriDate'){filter_Uridate.value = word}
					if(colum==='Event'){filter_Event.value = word}
					if(colum==='ShouhinCD'){
						filter_Shouhin.value = word
						word = word2
					}
					if(colum==='UriNO'){filter_UriNo.value = word}
					filter_flg.value[0] = true
					filter_flg.value[1] = `${filter_flg.value[1]}${word}＞`
				}
				const reset_filter = () =>{
					console_log(`reset_filter start`,'lv3')
					filter_Uridate.value = '%'
					filter_Event.value = '%'
					filter_Shouhin.value = '%'
					filter_UriNo.value = '%'
					filter_flg.value[0] = false
					filter_flg.value[1] = ''
				}
				const UriageList_filter = computed(() => {
					if(filter_flg.value[0]===false){
						return UriageList.value
					}
					return UriageList.value.filter((row) => {
						let serch_cols = ''
						let serch_words = ''
						if(filter_Uridate.value!=='%'){
							serch_cols = row.UriDate.toString()
							serch_words = filter_Uridate.value.toString()
						}
						if(filter_Event.value!=='%'){
							//serch_cols = serch_cols + row.Event.toString() + row.TokuisakiNM.toString()
							serch_cols = serch_cols + (row.Event + row.TokuisakiNM).toString()
							serch_words = serch_words + filter_Event.value.toString()
						}
						if(filter_Shouhin.value!=='%'){
							serch_cols = serch_cols + row.ShouhinCD.toString()
							serch_words = serch_words + filter_Shouhin.value.toString()
						}
						if(filter_UriNo.value!=='%'){
							serch_cols = serch_cols + row.UriageNO.toString()
							serch_words = serch_words + filter_UriNo.value.toString()
						}
						return (serch_cols === serch_words)
					})
				})
				const colspan = computed(()=>{//表タイプ毎の日付・イベント行のセル結合数返す
					if(Type.value==='sum_items'){
						return 8
					}else if(Type.value==='sum_events'){
						return 6
					}else{//rireki
						return 7-1
					}
				})
				const sum_uriage = computed(() => {//表示売上データの売上本体合計
					return UriageList_filter.value.reduce(function(sum, element){
  					return Number(sum) + Number(element.UriageKin);
					}, 0)
				})
				const sum_uriage_zei = computed(() => {//表示売上データの消費税合計
					return UriageList_filter.value.reduce(function(sum, element){
  					return Number(sum) + Number(element.zei);
					}, 0)
				})

				//更新処理関連
				const loader = ref(false)
				const upd_tanka = ref('')
				const upd_zei_kbn = ref('1101')
				const upd_zei_kominuki = ref('komi')
				const UriageData_Correct_mode = ref(false)
				const btn_controle = ref(['確　認',false]) //ボタン名・更新ボタン表示有無
				const common_body_style = computed(() => {
					if(UriageData_Correct_mode.value===false){
						return 'padding-bottom:80px;'
					}else{
						return 'padding-bottom:280px;'
					}
					
				})
				const upd_zei_kin = computed(() => {
					if(upd_tanka.value!==''){
						return return_tax(upd_tanka.value,upd_zei_kbn.value,upd_zei_kominuki.value)
					}
					return '-'
				})
				const upd_hontai = computed(() => {
					if(upd_zei_kominuki.value==='nuki'){
						return upd_tanka.value
					}else{
						return upd_tanka.value - upd_zei_kin.value
					}
				})
				const return_tax = (kingaku,zeikbn,kominuki) => {
					console_log('return_tax','lv3')
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

					if(upd_zei_kbn.value===0){
						return 0
					}
					if(kominuki==='komi'){
						return kingaku - Math.round(kingaku / (1 + zeiritu / 100))
					}else{
						return Math.round(kingaku * (zeiritu / 100));
					}
				}
				const btn_controler = () =>{
					if(btn_controle.value[1]){
						btn_controle.value[0] = '確　認'
						btn_controle.value[1] = false
					}else{
						btn_controle.value[0] = '戻　る'
						btn_controle.value[1] = true
					}
				}
				const where_sql = computed(() => {
					return `where `
				})

				const on_submit_Uriage_Update = async(e) => {//登録・submit/
					console_log('on_submit_Uriage_Update start','lv3')

					if(confirm('売上データを更新してもよいですか？')===false){
						alert('処理を中断しました。')
						return 0
					}
					loader.value = true
					let form_data = new FormData(e.target)
					let params = new URLSearchParams (form_data)
					
					await axios
						.post('ajax_UriageData_update_sql.php',params) //php側は15秒でタイムアウト,{timeout: <?php //echo $timeout; ?>}
						.then(async(response) => {
							console_log(`on_submit_Uriage_Update SUCCESS`,'lv3')
							console_log(response.data,'lv3')
							if(response.data.timeout===true){
								await alert(response.data.MSG)
								if(confirm('ログイン画面に戻りますか？')===true){
									window.location.href = 'index.php';
								}
							}
							MSG.value = response.data.MSG
							alert_status.value[1] = response.data.status
							csrf.value = response.data.csrf_create
						})
						.catch((error) => {
							console_log(`on_submit_Uriage_Update ERROR:${error}`,'lv3')
							//MSG.value = error.response.data[0].EMSG
							MSG.value = 'axios 通信エラー'
							csrf.value = error.response.data[0].csrf_create
							alert_status.value[1]='alert-danger'
						})
						.finally(()=>{
							get_UriageList()
							loader.value = false
						})
				}

				const delete_Uriage = (UriNO,ShouhinCD) => {//登録・submit/
					console_log('delete_Uriage start','lv3')

					if(confirm('売上データを削除してもよいですか？')===false){
						alert('処理を中断しました。')
						return 0
					}
					
					loader.value = true
					let params = new URLSearchParams ()
					params.append('csrf_token',csrf.value)
					params.append('UriageNO',UriNO)
					params.append('ShouhinCD',ShouhinCD)
					
					axios
						.post('ajax_UriageData_delete_sql.php',params) //php側は15秒でタイムアウト,{timeout: <?php //echo $timeout; ?>}
						.then(async(response) => {
							console_log(`delete_Uriage SUCCESS`,'lv3')
							console_log(response.data,'lv3')
							if(response.data.timeout===true){
								await alert(response.data.MSG)
								if(confirm('ログイン画面に戻りますか？')===true){
									window.location.href = 'index.php';
								}
							}
							MSG.value = response.data.MSG
							alert_status.value[1] = response.data.status
							csrf.value = response.data.csrf_create
						})
						.catch((error) => {
							console_log(`delete_Uriage ERROR:${error}`,'lv3')
							//MSG.value = error.response.data[0].EMSG
							MSG.value = 'axios 通信エラー'
							csrf.value = error.response.data[0].csrf_create
							alert_status.value[1]='alert-danger'
						})
						.finally(()=>{
							get_UriageList()
							loader.value = false
						})
						
				}

				onMounted(() => {
					console_log('onMounted','lv3')
					get_UriageList()
				})

				return{
					MSG,
					alert_status,
					csrf,
					UriageList,
					get_UriageList,
					UriDateFrom,
					UriDateTo,
					Type,
					colspan,
					set_filter,
					UriageList_filter,
					filter_Uridate,
					filter_Event,
					filter_Shouhin,
					filter_UriNo,
					Type_changer,
					btn_class,
					filter_flg,
					reset_filter,
					upd_zei_kbn,
					upd_tanka,
					upd_hontai,
					upd_zei_kin,
					upd_zei_kominuki,
					UriageData_Correct_mode,
					common_body_style,
					btn_controle,
					btn_controler,
					on_submit_Uriage_Update,
					sum_uriage,
					sum_uriage_zei,
					delete_Uriage,
					loader,
				}
			}
		}).mount('#app');
	</script><!--Vue3.js-->
	<script>
		document.onkeypress = function(e) {
			if (e.key === 'Enter') {
				return false;
			}
		}
		const chk_visible = (me,you) => {
			const chkbox = document.querySelector(`#${me.id}`)
			const inputbox = document.querySelector(you)
			if(chkbox.checked===true){
				inputbox.required = true
				inputbox.style.backgroundColor = '#fff'
			}else if(chkbox.checked===false){
				inputbox.required = false
				inputbox.style.backgroundColor = '#999999'
			}
		}
	</script><!--js-->
</body>


<script>
	var update_areas=document.getElementsByClassName('update_areas');
	var common_footer=document.getElementsByClassName('common_footer');
	var mode_switch=document.getElementById('switch1');
	var body=document.getElementById('body');
	var uritable=document.getElementById('uritable');

	//mode_switch.onclick = function (){
	var chang_mode = function(){
		let wh = window.innerHeight;//ブラウザの縦サイズ取得
		
		//ヘッダとフッタ分をマイナスして縦幅を算出
		var normal_vw = wh - 105 - 110;
		var update_vw = wh - 105 - 300;
		console.log('full:' + wh +' normal:' + normal_vw + ' update_vw:' + update_vw);
		if(mode_switch.checked==true && mode_switch.readOnly == false){
			update_areas[0].style.display='block';
			common_footer[0].style.display='none';
			//[1].style.display='block';
			body.style.paddingBottom='330px';
			uritable.style.height=update_vw +'px';
		}else{
			update_areas[0].style.display='none';
			common_footer[0].style.display='flex';
			//update_areas[1].style.display='none';
			body.style.paddingBottom='100px';
			uritable.style.height= normal_vw +'px';
		}
	}
	//chang_mode();
	
	//チェックボックスのチェック有無で必須か否かを切り替え
	/*
	document.getElementById('chk_uridate').onclick = function(){
		const a = document.getElementById('up_uridate');
		if(a.required==true){
			a.required=false;
		}else{
			a.required=true;
		}
	}
	document.getElementById('chk_event').onclick = function(){
		const a = document.getElementById('up_event');
		if(a.required==true){
			a.required=false;
		}else{
			a.required=true;
		}
	}
	document.getElementById('chk_kokyaku').onclick = function(){
		const a = document.getElementById('up_kokyaku');
		if(a.required==true){
			a.required=false;
		}else{
			a.required=true;
		}
	}
	document.getElementById('chk_urikin').onclick = function(){
		const a = document.getElementById('UpUriTanka');
		const b = document.getElementById('zeikbn'); 
		if(a.required==true){
			a.required=false;
			b.required=false;
		}else{
			a.required=true;
			b.required=true;
		}
	}
	document.getElementById('chk_genka').onclick = function(){
		const a = document.getElementById('up_urigenka');
		if(a.required==true){
			a.required=false;
		}else{
			a.required=true;
		}
	}
	*/
	
	//更新対象の有無を確認。無い場合はsubmitしない
	function check_update(){
		var flg = false;
		if(document.getElementById('chk_uridate').checked==true){
			return true;
		}
		if(document.getElementById('chk_event').checked==true){
			return true;
		}
		if(document.getElementById('chk_kokyaku').checked==true){
			return true;
		}
		if(document.getElementById('chk_urikin').checked==true){
			return true;
		}
		if(document.getElementById('chk_genka').checked==true){
			return true;
		}
		alert("更新対象がありません。");
		return false;
	}
</script>
<!--シェパードナビshepherd
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php require "ajax_func_tourFinish.php";?>
<script>
	const TourMilestone = '<?php echo $_SESSION["tour"];?>';

	const tutorial_9 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: true,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_9'
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>レジで売上を入力した当日に「売上実績」を開くと当日の売上明細が表示されます。
			   </p>`,
		attachTo: {
			element: '.item_0',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>
				<a href='#' class='btn-view' style='padding:4px;'>イベント集計</a>
				<a href='#' class='btn-view' style='padding:4px;'>商品集計</a>
				<a href='#' class='btn-view' style='padding:4px;'>会計明細</a>
				<br>
				<br>画面上部にあるこれらのボタンをタップすると、売上実績の表示方法を変更できます。
				<br>
				<br><i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i> をタップするとボタンの説明と表の操作方法を確認出来ます。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>過去の売上を確認したい場合は「検索」からも行う事が出来ます。
				<br>
				<br>ボタンをタップすると、検索用の画面が表示され、再度タップすると表示が消えます。
				<br>
				<br>
			   </p>`,
		attachTo: {
			element: '.item_1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>ためしにタップして画面を確認してみてください。
				<br>
				<br>(確認したら、検索画面を閉じた状態で「Next」をタップしてください。)
			   </p>`,
		attachTo: {
			element: '.item_1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>レジで打ち間違えた場合、この画面から売上の修正を行う事が出来ます。
			   </p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>この「修正モード」をタップしてONに変更すると、修正用の画面に切り替わります。
				<br>
				<br>今回は説明しませんが、修正が必要となったら修正モードに切り替えて<i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i> マークより使い方を確認して下さい。
			   </p>`,
		attachTo: {
			element: '.item_2',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>次に、レジで登録した売上を消す方法を説明します。
			   </p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.nextAndSave
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'><a href='#'><i class='fa-regular fa-trash-can'></i></a> マークをタップすることで売上を消す事が出来ます。
			   </p>`,
		attachTo: {
			element: '.item_0',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>今回はチュートリアルの一環で売上を登録してますので、売上を全て削除して下さい。
			   </p>`,
		attachTo: {
			element: '.item_0',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.complete
			}
		]
	});

	const tutorial_10 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: true,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_10'
	});
	tutorial_10.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>全ての売上を削除したら「WebRez+」をタップしてトップメニューに移動して下さい。
				<br>
				<br><span style='font-size:1rem;color:green;'>※進捗を保存しました。</span></p>`,
		buttons: [
			{
				text: 'Next',
				action: tutorial_10.complete
			}
		]
	});


	if(TourMilestone=="tutorial_8"){
		tutorial_9.start(tourFinish,'tutorial','');
	}else if(TourMilestone=="tutorial_9"){
		tutorial_10.start(tourFinish,'tutorial','save');
	}


	const tutorial_UriageShusei = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: true,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_UriageShusei'
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>売上明細エリアに表示されている内容を一括で修正します。
				<br>青字の項目をタップすることで対象の絞り込みが出来ます。
			   </p>`,
		attachTo: {
			element: '.tour_uri1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>日付、イベント名、商品名等をタップし、修正したいデータのみが表示されている状態にしてください。
			   </p>`,
		attachTo: {
			element: '.tour_uri1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>ここで修正対象と修正値の選択・入力を行います。
			   </p>`,
		attachTo: {
			element: '.tour_uri2',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>例えば、X月X日のイベント名が間違えてた！
				<br>といった場合、上の売上データで日付のみを選択した状態にします。
			   </p>`,
		attachTo: {
			element: '.tour_uri1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>次に、こちらで「イベント名」にチェックを入れ
				<br>その横の入力欄に本来のイベント名を入力します。
			   </p>`,
		attachTo: {
			element: '.tour_uri3',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>最後に「確認」ボタンをタップすると
				<br>修正対象のデータと修正内容を確認する画面に移動しますので、問題なければ「更新」ボタンをタップします。
				<br><br>間違えていた場合は「キャンセル」ボタンをタップしてください。
			   </p>`,
		attachTo: {
			element: '.tour_uri4',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>売上げの修正方法については以上となります。
				<br>
				<br>わからない事がありましたらトップ画面下の「お問い合わせ」よりお願いします。
			   </p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Finish',
				action: tutorial_UriageShusei.complete
			}
		]
	});    
	function urihelp(){
		tutorial_UriageShusei.start(tourFinish,'urihelp','');
	}
</script>    
</html>
<?php
$EVresult  = null;
$TKresult = null;
$stmt = null;
$pdo_h = null;

?>



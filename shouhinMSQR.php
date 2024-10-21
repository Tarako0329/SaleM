<?php
	require "php_header.php";

	$rtn = csrf_checker(["shouhinMSQR.php","menu.php"],["C","S"]);
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


?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php";
	require "ajax_func_tourFinish.php";
	?>
	<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
    <link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>

	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.js"></script><!--make QRコードライブラリ-->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js" integrity="sha512-XMVd28F1oH/O71fzwBnV7HucLxVwtxf26XV8P4wPk26EDxuGZ91N8bsOttmnomcCD3CS5ZMRL50H0GgOHvegtg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
    <!--QRファイル名sjisエンコードAPI-->
    <script>window.TextEncoder = window.TextDecoder = null;</script>
    <script src="https://cdn.jsdelivr.net/npm/text-encoding@0.6.4/lib/encoding-indexes.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/text-encoding@0.6.4/lib/encoding.js"></script>


	<!--ページ専用CSS-->
	<link rel='stylesheet' href='css/style_ShouhinMSL.css?<?php echo $time; ?>' >
	<style>
	    .header2{
	        height:65px;
	    }
	    .common_body{
	        padding-top:135px;
	    }
	</style>
	<TITLE><?php echo $title." 取扱商品 QR作成";?></TITLE>
</head>
<BODY>
	<div id='app'>
	<form method='post' @submit.prevent=''>
		<header class='header-color common_header' style='flex-wrap:wrap'>
			<div class='title' style='width: 60%;' id='to_top'>
				<a href='menu.php'><?php echo $title;?></a>
			</div>
			<div class='right' style='width: 40%;padding-top:10px;display:flex;'>
				<div style='width: 20px;padding-top:5px;'><i class="fa-solid fa-magnifying-glass fa-2x logoff-color"></i></div>
				<div style='width:200px' ><input type='search' v-model='search_word' class='form-contral' style='margin-left:5px;width:78%;max-width:200px;' placeholder='Search'></div>
				
			</div>
			<p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  取扱商品 QR作成</p>

			<a href="#" style='color:inherit;position:fixed;top:40px;right:5px;' onclick='help_start()'><i class="bi bi-question-circle Qicon logoff-color"></i></a>

		</header>
		<div class='header2'>
			<div class='container-fluid'>
				<div style='display:flex;'>
					<div class='me-3'>
						<select v-model='QR_DLtype' class='form-select form-select-lg ps-3' style='width: 150px;' id='QR_DLtype'>
							<option value='none'>ﾀﾞｳﾝﾛｰﾄﾞ対象選択</option>
							<option value='all'>全商品QR</option>
							<option value='rez'>レジ表示商品QRのみ</option>
							<option value='chk'>QR出力check商品QRのみ</option>
						</select>
					</div>
					<div class='me-3'>
						<select v-model='chk_register_show' class='form-select form-select-lg item_0' style='width: 90px;' id='chk_register_show'>
							<option value='all'>全て表示</option>
							<option value='on'>レジON</option>
							<option value='off'>レジOFF</option>
						</select>
					</div>
					<div style='display:flex;' id='order_by'>
						<select v-model='order_by[0]' class='form-select form-select-lg' style='margin-bottom:5px;width: 90px;'>
							<option value='seq'>登録順</option>
							<option value='name'>名称順</option>
						</select>
						<button @click='up_or_down' class='btn btn-primary' style='height:25px;padding:0px 10px;font-size:1.2rem;margin-top:0px;margin-left:5px;' type='button'>
							{{order_by[1]}}
						</button>
					</div>
				</div>
				<!--<div style='display:flex;'>
					<button type='button' class='btn btn-sm btn-primary fs-5 ps-3 pe-3' data-bs-toggle='modal' data-bs-target='#modal_help1' id='qr_size'>QRコードサイズ調整</button>
				</div>-->
			</div>
		</div>
	
		<main class='common_body' >
			<div class='container-fluid'>
				<input type='hidden' name='csrf_token' :value='csrf'>
				
				<table class='table result_table item_1' style='width:100%;max-width:630px;table-layout: fixed;font-size:12px;'>
					<thead>
						<tr style='height:30px;'>
							<th class='th1' scope='col' colspan='2' >ID:商品名</th>
							<th class='th1 text-center' scope='col'>レジ</th>
							<th class='th1' scope='col'>税込価格</th>
							<th class='th1 text-center' scope='col'>税率(%)</th>
							<th class='th1 text-center' scope='col'>QR出力</th>
						</tr>
					</thead>
					<tbody>
						<template v-for='(list,index) in shouhinMS_filter' :key='list.shouhinCD'>
						<tr>
							<td style='font-size:1.3rem;font-weight:700;' colspan='2'>{{list.shouhinCD}}:{{list.shouhinNM}}</td><!--商品名-->
							<td style='padding:10px 10px;' class='text-center'>
								<!--<div class="form-check form-switch">
									<input type='checkbox' :name ='`ORDERS[${index}][hyoujiKBN1]`' class='form-check-input' v-model='list.disp_rezi' :id='`${list.shouhinCD}`'>
									<label v-if='list.disp_rezi!==true' class='form-check-label' :for='`${list.shouhinCD}`' style='font-size:1.2rem;'>非表示</label>
									<label v-if='list.disp_rezi===true' class='form-check-label' :for='`${list.shouhinCD}`'>表示</label>
								</div>-->
								<template v-if='list.disp_rezi!==true' >非表示</template>
								<template v-if='list.disp_rezi===true'><p style='color:blue;'>表示</p></template>
							</td>
							<td style='' class='text-end pe-3'><!--税込価格-->
								￥{{Number(list.moto_kin).toLocaleString()}}
							</td>
							<td>{{list.hyoujimei}}</td>
							<td class=' text-center'>
								<input type='checkbox' class='form-checkbox' v-model='list.cate_chk'>
							</td><!--削除アイコン-->
						</tr>

							<input type='hidden' :name ='`ORDERS[${index}][shouhinCD]`' :value='list.shouhinCD'>
							<input type='hidden' :name ='`ORDERS[${index}][tanka]`' :value='list.tanka'>
							<input type='hidden' :name ='`ORDERS[${index}][shouhizei]`' :value='list.tanka_zei'>

						</template>
					</tbody>
				</table>
			</div>
			<canvas id='qr' style='display: none;'></canvas>
			<canvas id='qr2'></canvas>
		</main>
		<footer class='common_footer'>
			<!--<button type='button' @click='qr_zip_download()' class='btn--chk' style='border-radius:0;font-size:20px;' id='dl_btn' >QRコードダウンロード</button>-->
			<button type='button' class='btn--chk' style='border-radius:0;font-size:20px;' id='dl_btn'  data-bs-toggle='modal' data-bs-target='#modal_help1'>QRコードダウンロード</button>
		</footer>
	</form>
	<div class="loader-wrap" v-show='loader'>
		<div class="loader">Loading...</div>
	</div>
	<div class='modal fade' id='modal_help1' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
		<div class='modal-dialog  modal-dialog-centered'>
			<div class='modal-content' >
				<div class='modal-header'>
					<div class='modal-title'>QRコードサイズ調整</div>
				</div>
				<div class='modal-body'>
                    <div class='row ps-5 pe-5'>
				        <p>1.5cm以上ですと、スムーズに読み込めます</p>
						<select class='form-select' v-model='qr_size'>
							<option value='38'>1.0cm</option>
							<option value='42'>1.1cm</option>
							<option value='45'>1.2cm</option>
							<option value='49'>1.3cm</option>
							<option value='53'>1.4cm</option>
							<option value='57'>1.5cm</option>
							<option value='60'>1.6cm</option>
							<option value='64'>1.7cm</option>
							<option value='68'>1.8cm</option>
							<option value='72'>1.9cm</option>
							<option value='76'>2.0cm</option>
						</select>
					</div>
					<div class='row ps-5 pe-5'>
						<div class='text-center p-5' style='height:250px;'>
							<canvas id='qr_sample' style='display:none;'></canvas>
							<canvas id='qr_sample2'></canvas>
						</div>
					</div>
					<div class='row'>
						<p>※注：QRコードは１つのファイルにまとめてダウンロードされます。(zip形式)</p>
						<p>ダウンロードしたファイルは「zipファイル解凍ソフト」を利用することで、画像ファイルに変換されます。</p>
						<p>iphone/アンドロイドなどの携帯端末でラベルを作成する場合、アプリストアから「zipファイル解凍」のアプリを検索・入手し、処理することでQRコードの画像ファイルが利用可能となります。</p>
					</div>
				</div>
				<div class='modal-footer'>
					<button class='btn btn-secondary' type='button' data-bs-dismiss='modal'>閉じる</button>
					<button class='btn btn-primary' type='button' @click='qr_zip_download()'>ダウンロード</button>
				</div>
			</div>
		</div>
	</div><!--help1-->	
	</div>
	<script>
		window.onload = function() {
			// Enterキーが押された時にSubmitされるのを抑制する
			document.getElementById("app").onkeypress = (e) => {const key = e.keyCode || e.charCode || 0;}
		};
	</script><!--js-->
    <script>
    /*	const TourMilestone = sessionStorage.getItem('tourname');
    	if(TourMilestone){
    	    new_releace_start()
    	}
    */
    	const help = new Shepherd.Tour({
    		useModalOverlay: true,
    		defaultStepOptions: {
    			classes: 'tour_modal',
    			scrollTo: false,
    			cancelIcon:{
    				enabled:true
    			}
    		},
    		tourName:'help'
    	});
    	help.addStep({
    		title: `<p class='tour_header'>ヘルプ</p>`,
    		text: `<p class='tour_discription'> QRスキャンで利用するQRコード画像(PNG)をZIP形式でまとめてダウンロードします。
    				<br>
    				<br>商品のラベルにQRコードの画像を追加するなどしてご利用ください。
    				</p>`,
    		buttons: [
    			{
    				text: 'Next',
    				action: help.next
    			}
    		]
    	});
    	help.addStep({
    		title: `<p class='tour_header'>ヘルプ</p>`,
    		text: `<p class='tour_discription'> QR画像をダウンロードする商品を選択します。
    				</p>`,
    		attachTo: {
    			element: '#QR_DLtype',
    			on: 'bottom'
    		},
    		buttons: [
    			{
    				text: 'Back',
    				action: help.back
    			},
    			{
    				text: 'Next',
    				action: help.next
    			}
    		]
    	});
    	help.addStep({
    		title: `<p class='tour_header'>ヘルプ</p>`,
    		text: `<p class='tour_discription'> 下に表示する商品一覧を（全商品・レジON・レジOFF）から選択できます。
    				</p>`,
    		attachTo: {
    			element: '#chk_register_show',
    			on: 'bottom'
    		},
    		buttons: [
    			{
    				text: 'Back',
    				action: help.back
    			},
    			{
    				text: 'Next',
    				action: help.next
    			}
    		]
    	});
    	help.addStep({
    		title: `<p class='tour_header'>ヘルプ</p>`,
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
    				action: help.back
    			},
    			{
    				text: 'Next',
    				action: help.next
    			}
    		]
    	});
    	help.addStep({
    		title: `<p class='tour_header'>ヘルプ</p>`,
    		text: `<p class='tour_discription'>『ダウンロード対象』を設定したらコチラからダウンロードしてください。
    				<br>子ウィンドウが開き、ＱＲコードのサイズを選択・ダウンロードが可能です。
    				<br>
    				<br>ダウロードしたファイルはZIP形式となります。解凍することで画像ファイルが利用可能となりますので、各商品のラベルに追加するなどしてご利用ください。
    				</p>`,
    		attachTo: {
    			element: '#dl_btn',
    			on: 'auto'
    		},
    		buttons: [
    			{
    				text: 'Back',
    				action: help.back
    			},
    			{
    				text: 'OK',
    				action: help.next
    			}
    		]
    	});
    	
        const new_releace_005_1 = new Shepherd.Tour({
            useModalOverlay: true,
            defaultStepOptions: {
                classes: 'tour_modal',
                scrollTo: true,
                cancelIcon:{
                    enabled:true
                }
            },
            tourName:'new_releace_005_1'
        });
        new_releace_005_1.addStep({
            title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
            text: `<p class='tour_discription'>
                <br>続いてレジ画面の説明に移ります。
                <br>`,
            attachTo: {
                element: '#to_top',
                on: 'bottom'
            },
            cancelIcon:{
                enabled:false
            }
        });
    
    	const help_start = async()=>{
    		//help.start(tourFinish,'help','');
    		help.start(tourFinish,'new_releace_005','');
    	}
    	const new_releace_start = async()=>{
    		await help_start()
    		await new_releace_005_1.start(tourFinish,'new_releace_005','');
    	}
    
    </script>
    <script src="shouhinMSList_vue.js?<?php echo $time; ?>"></script>
	<script>
		REZ_APP().mount('#app');
	</script><!--Vue3js-->
</BODY>
<!--<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>-->
<?php //require "ajax_func_tourFinish.php";?>
<!--<script>
/*	const TourMilestone = sessionStorage.getItem('tourname');
	if(TourMilestone){
	    new_releace_start()
	}
*/
	const help = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'help'
	});
	help.addStep({
		title: `<p class='tour_header'>ヘルプ</p>`,
		text: `<p class='tour_discription'> QRスキャンで利用するQRコード画像(PNG)をZIP形式でまとめてダウンロードします。
				<br>
				<br>販売されている各商品のラベルにQRコードの画像を追加するなどしてご利用ください。
				</p>`,
		buttons: [
			{
				text: 'Next',
				action: help.next
			}
		]
	});
	help.addStep({
		title: `<p class='tour_header'>ヘルプ</p>`,
		text: `<p class='tour_discription'> ダウンロードする商品のリストを選択します。
				</p>`,
		attachTo: {
			element: '#QR_DLtype',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: help.back
			},
			{
				text: 'Next',
				action: help.next
			}
		]
	});
	help.addStep({
		title: `<p class='tour_header'>ヘルプ</p>`,
		text: `<p class='tour_discription'> 商品一覧の表示を（全商品・レジON・レジOFF）から選択できます。
				</p>`,
		attachTo: {
			element: '#chk_register_show',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: help.back
			},
			{
				text: 'Next',
				action: help.next
			}
		]
	});
	help.addStep({
		title: `<p class='tour_header'>ヘルプ</p>`,
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
				action: help.back
			},
			{
				text: 'Next',
				action: help.next
			}
		]
	});
	/*help.addStep({
		title: `<p class='tour_header'>ヘルプ</p>`,
		text: `<p class='tour_discription'>ダウンロードするQRコードのサイズを設定します。
				</p>`,
		attachTo: {
			element: '#qr_size',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: help.back
			},
			{
				text: 'Next',
				action: help.next
			}
		]
	});*/
	help.addStep({
		title: `<p class='tour_header'>ヘルプ</p>`,
		text: `<p class='tour_discription'>『ダウンロード対象』を設定したらコチラからダウンロードしてください。
				<br>子ウィンドウが開き、ＱＲコードのサイズを選択・ダウンロードが可能です。
				<br>
				<br>ダウロードしたファイルはZIP形式となります。解凍することで画像ファイルが利用可能となりますので、各商品のラベルに追加するなどしてご利用ください。
				</p>`,
		attachTo: {
			element: '#dl_btn',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: help.back
			},
			{
				text: 'OK',
				action: help.next
			}
		]
	});

	const help_start = ()=>{
		help.start(tourFinish,'help','');
	}
	const new_releace_start = ()=>{
		help.start(tourFinish,'new_releace_005','finish');
	}

</script>-->
</html>
<?php
$_SESSION["MSG"] = "";
$_SESSION["alert"]="";
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>
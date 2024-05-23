<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

*/
require "php_header.php";

$rtn = csrf_checker(["menu.php","shouhinMSedit.php"],["G","C","S"]);
if($rtn !== true){
	redirect_to_login($rtn);
}

//セッションのIDがクリアされた場合の再取得処理。
$rtn=check_session_userid($pdo_h);

//ユーザ情報取得
//$sql="select yuukoukigen,ZeiHasu from Users where uid=?";
$sql="select yuukoukigen,ZeiHasu from Users_webrez where uid=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

//端数処理設定
$ZeiHasu = $row[0]["ZeiHasu"];


//税区分MSリスト取得
$sqlstr="select * from ZeiMS order by zeiKBN;";
$stmt = $pdo_h->query($sqlstr);
$zeimaster = $stmt->fetchAll(PDO::FETCH_ASSOC);
$csrf_token=csrf_create();

$success_msg = (!empty($_SESSION["MSG"])?$_SESSION["MSG"]:"");
$_SESSION["MSG"]=null;

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php" 
	?>
	<!--ページ専用CSS--><link rel="stylesheet" href="css/style_ShouhinMSedit.css?<?php echo $time; ?>" >
	<TITLE><?php echo secho($title)." 取扱商品登録画面";?></TITLE>
</head>
<body class='common_body'>
	<header class="header-color common_header" style="flex-wrap:wrap">
		<div class="title" style="width: 100%;"><a href="menu.php" class='item_15'><?php echo secho($title);?></a></div>
		<p style="font-size:1rem;color:var(--user-disp-color);font-weight:400;">  取扱商品登録画面</p>
		<a href="#" style='color:inherit;position:fixed;top:75px;right:5px;' onclick='help()'><i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i></a>
	</header>
	<form method="post" id="form1" class="" action="shouhinMSedit_sql.php" autocomplete="off">
		<main class="container" style="padding-top:20px;padding-bottom:100px;">
			<?php
				if(!empty($success_msg)){
					echo "<div class='alert alert-success ms-3 me-3' role='alert' style=''>".$success_msg."</div>";//class="form-label"
				}
			?>
			<!--<div class='row mb-3 item_1'
				data-bs-placement='top' data-bs-trigger='click' data-bs-custom-class='custom-tooltip' data-bs-toggle='tooltip' title='１度登録すると商品名は変更できません。'>-->
			<div class='row mb-3 item_1'>
				<label for='shouhinNM' class='col-3 col-sm-2 col-form-label'>商品名</label>
				<div class='col-8' >
					<input type='text' class='form-control form-control-lg' id='shouhinNM' name='shouhinNM' required='required' placeholder=''>
					<small id='zkomitankaHelp' class='form-text text-muted'>１度登録すると商品名は変更できません。</small>
				</div>
			</div>
			<!--<div class='row mb-3 item_2'
			data-bs-placement='top' data-bs-trigger='click' data-bs-custom-class='custom-tooltip' data-bs-toggle='tooltip' title='消費税は『税率』と『税抜・税込』の選択に応じて自動計算されます。'>-->
			<div class='row mb-3 item_2'>
				<label for='new_tanka' class='col-3 col-sm-2 col-form-label'>商品単価</label>
				<div class='col-8'>
					<input type='number' v-model='new_tanka' class='form-control form-control-lg' id='new_tanka' required='required' placeholder=''>
					<small id='zkomitankaHelp' class='form-text text-muted'>消費税は『税率』と『税抜・税込』の選択に応じて自動計算されます。</small>
				</div>
			</div>
			<div class='row mb-3 item_3'>
				<div class='col-3 col-sm-2'></div>
				<div class='col-8'>
					<div class='input-group'>
						<input type='radio' v-model='kominuki' class='btn-check' name='options' id='option1' value='IN' autocomplete='off'>
						<label class='btn btn-outline-primary' style='font-size:1.2rem;border-radius:0;margin-right:0;' for='option1'>税込</label>
						<input type='radio' v-model='kominuki' class='btn-check' name='options' id='option2' value='NOTIN' autocomplete='off'>
						<label class='btn btn-outline-primary' style='font-size:1.2rem;border-radius:0;margin-left:0px;' for='option2'>税抜</label>
					</div>
				</div>
			</div>
			<div class='row mb-3 item_4' id='test99'>
				<label for='zeikbn' class='col-3 col-sm-2 col-form-label'>税区分</label>
				<div class='col-8'>
					<select v-model='zeikbn' class='form-select form-select-lg' style='font-size:1.2rem;' id='zeikbn' name='zeikbn' required='required'>
					<?php
					foreach($zeimaster as $row){
						echo "<option value=".secho($row["zeiKBN"]).">".secho($row["hyoujimei"])."</option>\n";
					}
					?> 
					</select>
				</div>
			</div>
			<div class='item_5' id='item_5'>
				<div class='row mb-1'>
					<label for='tanka' class='col-3 col-sm-2 col-form-label'>税抜単価</label>
					<div class='col-8'>
						<input type='number' v-model='tanka' style='background-color:lightgray;' readonly class='form-control form-control-lg' id='tanka' name='tanka' >
					</div>
				</div>
				<div class='row mb-1'>
					<label for='shouhizei' class='col-3 col-sm-2 col-form-label'>消費税</label>
					<div class='col-8'>
						<input type='number' v-model='shouhizei' style='background-color:lightgray;' readonly class='form-control form-control-lg' id='shouhizei' name='shouhizei' >
					</div>
				</div>                
				<div class='row mb-3'>
					<label for='zkomitanka' class='col-3 col-sm-2 col-form-label'>税込単価</label>
					<div class='col-8'>
						<input type='number' v-model='zkomitanka' style='background-color:lightgray;' readonly class='form-control form-control-lg' id='zkomitanka' aria-describedby='zkomitankaHelp'>
						<small id='zkomitankaHelp' class='form-text text-muted'>レジ画面に表示される金額は税込価格です。</small>
					</div>
				</div>
			</div>
			<!--<div class='row mb-3 item_6'
				data-bs-placement='top' data-bs-trigger='click' data-bs-custom-class='custom-tooltip' data-bs-toggle='tooltip' title='原価を入れると利益の把握に役立ちます。'>-->
			<div class='row mb-3 item_6'>
				<label for='genka' class='col-3 col-sm-2 col-form-label'>原価単価</label>
				<div class='col-8'>
					<input type='number' class='form-control form-control-lg' id='genka' name='genka' aria-describedby='genka'>
					<small id='genka' class='form-text text-muted'>おおよその原材料費。<span class='text-danger'>確定申告には使いません。</span></small>
				</div>
			</div>
			<div class='row mb-3 item_7'>
				<label for='utisu' class='col-3 col-sm-2 col-form-label'>内容量</label>
				<div class='col-8'>
					<input type='number' class='form-control form-control-lg' id='utisu' name='utisu' placeholder='個数・グラム等'>
				</div>
			</div>
			<div class='row mb-3 item_8'>
				<label for='tani' class='col-3 col-sm-2 col-form-label'>単位</label>
				<div class='col-8'>
					<input type='text' class='form-control form-control-lg' id='tani' name='tani' placeholder='内容量の単位（g,個）等'>
				</div>
			</div>
			<!--<div class='row mb-3'>
				<hr aria-describedby='setumei'>
				<small id='setumei' class='form-text text-muted'>カテゴリー別画面でまとめて登録可能です</small>
			</div>
			<div class='item_9'>
				<div class='row mb-3'>
					<label for='bunrui1' class='col-3 col-sm-2 col-form-label'>大カテゴリー</label>
					<div class='col-8'>
						<input type='text' class='form-control form-control-lg' id='bunrui1' name='bunrui1' placeholder='例：物販'>
					</div>
				</div>
				<div class='row mb-3'>
					<label for='bunrui2' class='col-3 col-sm-2 col-form-label'>中カテゴリー</label>
					<div class='col-8'>
						<input type='text' class='form-control form-control-lg' id='bunrui2' name='bunrui2' placeholder='例：食品'>
					</div>
				</div>
				<div class='row mb-3'>
					<label for='bunrui3' class='col-3 col-sm-2 col-form-label'>小カテゴリー</label>
					<div class='col-8'>
						<input type='text' class='form-control form-control-lg' id='bunrui3' name='bunrui3' placeholder='例：惣菜'>
					</div>
				</div>
			</div>-->
			<div class='row mb-3 item_12'>
				<label for='hyoujiKBN1' class='col-3 col-sm-2 col-form-label'>レジ表示</label>
				<div class='col-8'>
					<input type='checkbox' class='form-check-input' id='hyoujiKBN1' name='hyoujiKBN1' checked='checked'>
				</div>
				
			</div>
			<input type='hidden' name='csrf_token' value='<?php echo $csrf_token; ?>'>
			<!--<input type='hidden' style='width:50%' id='hyoujiNO' name='hyoujiNO' placeholder='レジ表示順。未指定の場合は「カテゴリー大>中>小>商品名」の五十音順' value=0>
			<input type='hidden' id='hyoujiKBN2' name='hyoujiKBN2' value=''>
			<input type='hidden' id='hyoujiKBN3' name='hyoujiKBN3' value=''>-->
		</main>
		<footer class='common_footer'>
			<button type='submit' class='btn--chk item_13' style='border-radius:0;max-width:400px;' value='登録' >登　録</button>
		</footer>
	</form>
	<script>
		document.getElementById("form1").onkeypress = (e) => {
			// form1に入力されたキーを取得
			const key = e.keyCode || e.charCode || 0;
			// 13はEnterキーのキーコード
			if (key == 13) {
				// アクションを行わない
				e.preventDefault();
			}
		}    
	</script>
	<script>
		const { createApp, ref, onMounted, computed, VueCookies, watch,nextTick  } = Vue;
		createApp({
			setup(){
				const tanka = ref(0)
				const new_tanka = ref('')
				const shouhizei = ref(0)
				const zkomitanka = ref(0)
				const kominuki = ref('IN')
				const zeikbn = ref('')
				/*const zm = [
					<?php
					//reset($zeimaster);
					//foreach($zeimaster as $row2){
					//	echo "{税区分:".$row2["zeiKBN"].",税率:".($row2["zeiritu"]/100)."},\n";
					//}
					?> 
				]*/

				watch([zeikbn,new_tanka,kominuki],() => {
					console_log(new_tanka.value,)
					if(new_tanka.value===''){
						console_log('watch skip')
						return
					}
					let zmrec = ([])
					//zmrec = zm.filter((list)=>{
					zmrec = ZEIM.filter((list)=>{
						return list.税区分 == zeikbn.value
					})
					const values = get_value(Number(new_tanka.value),Number(zmrec[0]["税率"]),kominuki.value)
					tanka.value = values[0]["本体価格"]
					shouhizei.value = values[0].消費税
					zkomitanka.value = values[0].税込価格
					if(values[0].E !== 'OK'){
						alert('指定の税込額は税率計算で端数が発生するため実現できません')
					}
				})
				onMounted(() => {
					//console_log(get_value(1000,0.1,'IN'),'lv3')
					console_log('onMounted','lv3')
					/*
					const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
					const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
					
					const exampleEl = document.getElementById('test99')
					const tooltip = new bootstrap.Tooltip(exampleEl,{boundary: document.body,title:'test99'})
					console_log(tooltip,)
					*/
				})
				return{
					tanka,
					new_tanka,
					shouhizei,
					zkomitanka,
					kominuki,
					zeikbn,
				}                
			}
		}).mount('#form1');
	</script>
</body>
<!--シェパードナビ
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php require "ajax_func_tourFinish.php";?>
<script>
	const TourMilestone = '<?php echo $_SESSION["tour"];?>';
	
	const tutorial_2 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_2'
	});
	<?php if(!empty($_SESSION["tour"])){?>
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>レジに表示する商品の登録画面について説明します。</p>`,
		buttons: [
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
	<?php }?>
	/*
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「商品名」の入力欄です。<br><br><span style='color:red;'>「商品名」のみ、一度登録すると変更できません。</span><p>`,
		attachTo: {
			element: '.item_1',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
	*/
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「商品単価」の入力欄です。<br><br>「商品単価」は税込・税抜のどちらでも入力可能です。</p>`,
		attachTo: {
			element: '.item_2',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「商品単価」に入力した金額が『税込』か『税抜』かを選択します。<br><br>「非課税」の場合は「税込」のままで大丈夫です。</p>`,
		attachTo: {
			element: '.item_3',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
	/*
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「消費税率」の入力欄です。</p>`,
		attachTo: {
			element: '.item_4',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
	*/
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>選択した「消費税率」から消費税が自動計算されます。</p>`,
		attachTo: {
			element: '#item_5',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>商品製作費(概算でOK)の入力欄です。<br><br>入力しておくと、売上実績等で利益が算出されます。<br><span style='color:red;'>※確定申告のソフトには連携しません。</span></p>`,
		attachTo: {
			element: '.item_6',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>纏め売り商品の内訳数入力欄です。<br>箱詰め等で纏め売りしてる場合、何個入りもしくは何グラム入りなど、内容量を入力できます。<br><br>※売上分析に利用する予定。</p>`,
		attachTo: {
			element: '.item_7',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>内容量の単位の入力欄です。<br>例：枚、個、グラムなど</p>`,
		attachTo: {
			element: '.item_8',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
	/*
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>商品の大カテゴリー入力欄です。（<span style='font-weight:bold;'>大</span>>中>小）
		<br>入力すると、以下のメリットがあります。
		<br>・レジ画面：カテゴリーで纏めて表示され、商品を探しやすくなります
		<br>・売上分析：カテゴリーごとの集計・分析ができます</p>`,
		attachTo: {
			element: '.item_9',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});
   */
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>チェックを外すと「レジ画面」の表示対象外となります。</p>`,
		attachTo: {
			element: '.item_12',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.nextAndSave
			}
		]
	});
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>「登録」ボタンを押すと、登録されます。
				<br><br><span style='color:red;'>※登録した内容は削除可能ですが、1件でも売上登録されると削除不可となります</span></p>`,
		attachTo: {
			element: '.item_13',
			on: 'top'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next
			}
		]
	});

	<?php if(!empty($_SESSION["tour"])){?>
	tutorial_2.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>まずは１件、登録してみましょう。<br><br><span style='color:red;'>チュートリアルの最後に削除できますので仮の商品でも可です。</span></p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_2.back
			},
			{
				text: 'Next',
				action: tutorial_2.next //complete
			}
		]
	});
	
	<?php }?>

	const tutorial_3 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: false,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_3'
	});
	tutorial_3.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>登録が成功すると、画面上部に緑色のバーでメッセージが表示されます。</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_3.back
			},
			{
				text: 'Next',
				action: tutorial_3.next //complete
			}
		]
	});
	tutorial_3.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>余裕があったら追加で何件か商品を登録してみてください。
				<br>
				<br><span style='font-size:1rem;color:green;'>※進捗を保存しました。</span></p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_3.back
			},
			{
				text: 'Next',
				action: tutorial_3.nextAndSave //complete
			}
		]
	});
	tutorial_3.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>ココをタップすると、ひとつ前のメニューに戻ります。<br>登録作業が終わったらタップしてください。<br><br><span style='color:red;'>※全画面共通の操作なので覚えてくださいね</span></p>`,
		attachTo: {
			element: '.item_15',
			on: 'bottom'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_3.back
			},
		   {
				text: 'Next',
				action: tutorial_3.complete //complete
			}
		]
	});

	
	if(TourMilestone=='tutorial_1'){
		tutorial_2.start(tourFinish,'tutorial','');
	}else if(TourMilestone=='tutorial_2'){
		tutorial_3.start(tourFinish,'tutorial','save');
	}

	function help(){
		tutorial_2.start(tourFinish,'help','');
	}
</script>

</html>

<?php
$stmt  = null;
$pdo_h = null;
?>



















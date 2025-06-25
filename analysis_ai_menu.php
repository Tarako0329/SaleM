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
$sql="select yuukoukigen,ZeiHasu from Users_webrez where uid=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

//売上データの取得をUriageMeisaiから行う
$sql="SELECT 
		UriageNO as 売上番号
		,UriDate as 売上計上日
		,Event as 売上計上イベント名
		,TokuisakiNM as イベント以外の売上先
		,ShouhinNM as 商品名
		,su as 売上個数
		,tanka as 売上単価
		,UriageKin as 売上金額
		,genka_tanka as 原価単価
		,genka as 売上原価
		,IFNULL(bunrui1,'未設定') as 商品分類大
		,IFNULL(bunrui2,'未設定') as 商品分類中
		,IFNULL(bunrui3,'未設定') as 商品分類小
		,address as イベント開催住所
		,weather as 売上時の天気
		,weather_discription as 売上時の天気詳細
		,temp as 売上時の気温
		,feels_like as 売上時の体感温度
	from UriageMeisai 
	where uid=? and UriDate between ? and ?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->bindValue(2, '2024-01-01', PDO::PARAM_STR);
$stmt->bindValue(3, '2024-12-31', PDO::PARAM_STR);
//$stmt->bindValue(2, date("Y-m-d"), PDO::PARAM_STR);
$stmt->execute();
$shouhin_rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

//log_writer2("\$shouhin_rows",json_encode($shouhin_rows,JSON_UNESCAPED_UNICODE),"lv3");

$ask = "一流経営コンサルタントとして、次に渡す１年分の売上明細をもとに、今後の売上を増やすアドバイスをレポート形式で出力。出るべきイベント、地域、天気・気温との関連。注力すべき商品群とそうでない商品の選定など。売上明細は次の通り。".json_encode($shouhin_rows,JSON_UNESCAPED_UNICODE);


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
		<a href="#" style='color:inherit;position:fixed;top:75px;right:5px;' onclick='help()'><i class="bi bi-question-circle Qicon awesome-color-panel-border-same"></i></a>
	</header>
	<main>
		<div id='form1'>
		<?php
			echo gemini_api($ask,'plain');
		?>
		</div>
	</main>
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

				watch([zeikbn,new_tanka,kominuki],() => {
					console_log(new_tanka.value,)
					if(new_tanka.value===''){
						console_log('watch skip')
						return
					}
					let zmrec = ([])
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



















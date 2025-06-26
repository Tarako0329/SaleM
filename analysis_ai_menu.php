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
$sql="SELECT yuukoukigen,ZeiHasu from Users_webrez where uid=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

//ビジネスインフォを取得
$sql_bi = "SELECT 
	Product_categories as 取扱商品のジャンル
	, Sales_methods as 販売方法
	, Brand_image as ブランドイメージ
	, Monthly_goals as 月毎の目標
	, This_year_goals as 今年度の目標
	, Next_year_goals as 来年度の目標
	, Ideal_5_years as 年後の理想
	, Customer_targets as 顧客ターゲット
	, uid
	from business_info where uid=?";
$stmt = $pdo_h->prepare($sql_bi);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$business_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

//$business_infoが0件だった場合、business_infoにuidとapp=webrezを挿入
if(count($business_info) === 0){
	$stmt = $pdo_h->prepare("insert into business_info (uid,app) values (?,?)");
	$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
	$stmt->bindValue(2, "webrez", PDO::PARAM_STR);
	$stmt->execute();

	$stmt = $pdo_h->prepare($sql_bi);
	$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
	$stmt->execute();
	$business_info = $stmt->fetchAll(PDO::FETCH_ASSOC);
}



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

$ask = "
	一流経営コンサルタントとして、
	次に渡す１年分の売上明細をもとに、今後の売上を増やすためのレポートを出力。
	レポートはhtmlを利用してグラフ、表などを駆使しビジュアルを整える。
	htmlのみを出力してください。
	分析のポイントとして
	・出るべきイベント、
	・地域、天気・気温との関連。
	・注力すべき商品群とそうでない商品の選定。
	・競合他社との比較。
	・取扱商品から見る業種の傾向と今後のトレンド。
	・類似のイベント名は同じイベントとしてとらえる。
	売上明細は次の通り。".json_encode($shouhin_rows,JSON_UNESCAPED_UNICODE);
//$answer = gemini_api($ask,'plain');
//log_writer2("\$result gemini",$result,"lv3");

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
		<p style="font-size:1rem;color:var(--user-disp-color);font-weight:400;">  A.I分析レポート</p>
		<a href="#" style='color:inherit;position:fixed;top:45px;right:5px;' onclick='help()'><i class="bi bi-question-circle logoff-color"></i></a>
	</header>
	<main>
		<div id='app'>
			<!--your_bussinessの入力フォーム-->
			<form id="form1" method="post" action="#">
				<div class="container">
					<div class="row">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">あなたのビジネス情報</h5>
								</div>
								<div class="card-body">
									<div class="mb-3">
										<label for="Product_categories" class="form-label">取扱商品のジャンル</label>
										<input type="text" class="form-control" id="Product_categories" v-model="your_bussiness.取扱商品のジャンル">
									</div>
									<div class="mb-3">
										<label for="Sales_methods" class="form-label">販売方法</label>
										<input type="text" class="form-control" id="Sales_methods" v-model="your_bussiness.販売方法">
									</div>
									<div class="mb-3">
										<label for="Brand_image" class="form-label">ブランドイメージ</label>
										<input type="text" class="form-control" id="Brand_image" v-model="your_bussiness.ブランドイメージ">
									</div>
									<div class="mb-3">
										<label for="Monthly_goals" class="form-label">月毎の目標</label>
										<input type="text" class="form-control" id="Monthly_goals" v-model="your_bussiness.月毎の目標">
									</div>
									<div class="mb-3">
										<label for="This_year_goals" class="form-label">今年度の目標</label>
										<input type="text" class="form-control" id="This_year_goals" v-model="your_bussiness.今年度の目標">
									</div>
									<div class="mb-3">
										<label for="Next_year_goals" class="form-label">来年度の目標</label>
										<input type="text" class="form-control" id="Next_year_goals" v-model="your_bussiness.来年度の目標">
										
									</div>
									<div class="mb-3">
										<label for="Ideal_years" class="form-label">5年後の理想</label>
										<input type="text" class="form-control" id="Ideal_years" v-model="your_bussiness.年後の理想">
									</div>
									<div class="mb-3">
										<label for="Customer_targets" class="form-label">顧客ターゲット</label>
										<input type="text" class="form-control" id="Customer_targets" v-model="your_bussiness.顧客ターゲット">
									</div>
								</div>
								<div class="card-footer text-end">
									<button type="button" class="btn btn-primary" @click="get_gemini_response" :disabled="loading">
										<span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
										{{ loading ? '生成中...' : 'レポート生成' }}
									</button>
								</div>
							</div>
						</div>
					</div>
					<div class="row mt-4">
						<div class="col-12">
							<div class="card">
								<div class="card-header">
									<h5 class="card-title">AI分析レポート</h5>
								</div>
								<iframe class="card-body" :src="iframe_url">
								</iframe>
							</div>
						</div>
					</div>
				</div>
			</form>
			<div class="loader-wrap" v-show='loading'>
				<div class="loader">Loading...</div>
			</div>
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
				const your_bussiness = ref(<?php echo json_encode($business_info[0],JSON_UNESCAPED_UNICODE); ?>);
				const your_sales_data = ref(<?php echo json_encode($shouhin_rows,JSON_UNESCAPED_UNICODE); ?>);
				const your_ask = ref(`
					あなたは一流の経営コンサルタントです。
					次に渡す１年分の売上明細と、私のビジネス情報をもとに、今後の売上を増やすためのレポートを提案してください。
					レポートはhtmlを利用してグラフ、表などを駆使しビジュアルを整えてください。
					htmlのみを出力してください。
					分析のポイントとして
					・出るべきイベント、
					・地域、天気・気温との関連。
					・注力すべき商品群とそうでない商品の選定。
					・競合他社との比較。
					・取扱商品から見る業種の傾向と今後のトレンド。
					・類似のイベント名は同じイベントとしてとらえる。
					売上明細は次の通り。${JSON.stringify(your_sales_data.value)}
					私のビジネス情報は次の通り。${JSON.stringify(your_bussiness.value)}
				`);
				const gemini_response = ref('');
				const loading = ref(false);


				const get_gemini_response = async () => {
					loading.value = true;
					try {
						//console_log(your_ask.value)
						const form = new FormData();
						form.append('Article', your_ask.value);
						form.append('type', 'one');
						form.append('answer_type', 'html');

						const response = await axios.post('ajax_chk_gemini.php', form, {headers: {'Content-Type': 'multipart/form-data'}});
						gemini_response.value = response.data.result;
					} catch (error) {
						console.error('Error fetching Gemini response:', error);
						gemini_response.value = '<p style="color:red;">レポートの取得中にエラーが発生しました。</p>';
					} finally {
						loading.value = false;
					}
				};

				onMounted(() => {
					const iframe_url = `${your_sales_data.value[0].uid}_gemini_report.html`
					//get_gemini_response();
				});

				return {
					your_bussiness,
					your_sales_data,
					your_ask,
					gemini_response,
					loading,
					get_gemini_response,
				};
			}
		}).mount('#app');
	</script>
</body>
</html>

<?php
$stmt  = null;
$pdo_h = null;
?>



















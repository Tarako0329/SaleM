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
$csrf_token=csrf_create();

//ユーザ情報取得
$sql="SELECT * from Users where uid=?";
$stmt = $pdo_h->prepare($sql);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$user_info = $stmt->fetchAll(PDO::FETCH_ASSOC);

//ビジネスインフォを取得
$sql_bi = "SELECT 
	Product_categories as 取扱商品のジャンル
	, Sales_methods as 販売方法
	, Brand_image as ブランドイメージ
	, Instagram
	, X_com
	, facebook
	, Threads
	, tiktok
	, other_SNS
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

//売上分析に最適なロール集を$ai_roleにセット
$ai_roles = [
	'データアナリスト',
	'経営コンサルタント',
	'売上戦略コンサルタント',
	'マーケティング戦略家',
	'商品ポートフォリオマネージャー',
	'ビジネスアドバイザー',
	'財務分析官',
	'市場調査員',
	'事業開発コンサルタント',
	'データサイエンティスト',
	'成長戦略アドバイザー',
	'イノベーションコンサルタント'
];

//レポート種類
$report_types = [
	//["name" =>'ウィークリーレポート (先週)', "value" =>'weekly'],
	//["name" =>'月次レポート (先月)', "value" =>'monthly'],
	//["name" =>'月次レポート (先月と今月)', "value" =>'monthly2'],
	//["name" =>'年次レポート (昨年)', "value" =>'yearly'],
	//["name" =>'年次レポート (昨年と今年)', "value" =>'yearly2'],
	["name" =>'直近１２ヵ月レポート', "value" =>'12month'],
	//["name" =>'過去５年と今後の見通し', "value" =>'5years']
];

$report_type = "・レポートはHTMLで作成し、ミニファイされたHTMLのみを出力する。
	・一切の改行と余分なスペースを含まない最小限のHTMLコードを生成してください。インデントは使用しないでください。
	・bootstrap5.3.0フレームワークを使う。
	・スマートホンでも見やすいようにレスポンシブデザインで作成。
	・売上分析用のJSONデータは全てtableタグを使用して表を作成。
	・表と分析結果を織り交ぜる。
	・表はTableタグを使用する
	・カテゴリー別売上データを円グラフにする。
	・小カテゴリー別売上ランキングデータは表にする。
	・読みやすさを重視し、口語体で作成。
	・金額はカンマ区切り。
	・小数以下は無視する。
	・金額は正確に集計する。
	・ABC分析についての補足説明を入れる。
	・最後にまとめの一言を入れてレポートを終わる。
";
	//
$report_type = str_replace([" ", "　","\t"], "", $report_type);

$ai_settings_def = [
	/*[
		'ai_role' => 'データアナリスト',
		'report_name' => 'weekly',
		'your_ask' => "レポートで知りたいことは次の通り。
			・今週の商品別売上実績
			・1日ごとの売上をグラフと表で
			・イベントごとの売上実績
			・地域、天気と売上の関連。
			・売上が期待できる住所エリア
			・取扱商品から見る業種の傾向と今後のトレンド。
			・SNSを利用している場合、ビジネス情報を元にSNS毎の活用方法について。
			・売上分析用データとビジネス情報を元に今後の成長戦略の立案",
		'report_type' => $report_type
	],[
		'ai_role' => 'データアナリスト',
		'report_name' => 'monthly',
		'your_ask' => "レポートで知りたいことは次の通り。
			・目標と現状とのギャップの確認及び、ギャップを埋めるための提案。
			・注力すべき商品とそうでない商品の選定。
			・出るべきイベント
			・地域、天気と売上の関連。
			・売上が期待できる住所エリア
			・取扱商品から見る業種の傾向と今後のトレンド。
			・SNSを利用している場合、ビジネス情報を元にSNS毎の活用方法について。
			・売上分析用データとビジネス情報を元に今後の成長戦略の立案",
		'report_type' => $report_type
	],[
		'ai_role' => 'データアナリスト',
		'report_name' => 'monthly2',
		'your_ask' => "レポートで知りたいことは次の通り。
			・目標と現状とのギャップの確認及び、ギャップを埋めるための提案。
			・注力すべき商品とそうでない商品の選定。
			・出るべきイベント
			・地域、天気と売上の関連。
			・売上が期待できる住所エリア
			・取扱商品から見る業種の傾向と今後のトレンド。
			・SNSを利用している場合、ビジネス情報を元にSNS毎の活用方法について。
			・売上分析用データとビジネス情報を元に今後の成長戦略の立案",
		'report_type' => $report_type
	],[
		'ai_role' => 'データアナリスト',
		'report_name' => 'yearly',
		'your_ask' => "レポートで知りたいことは次の通り。
			・目標と現状とのギャップの確認及び、ギャップを埋めるための提案。
			・注力すべき商品とそうでない商品の選定。
			・出るべきイベント
			・地域、天気と売上の関連。
			・売上が期待できる住所エリア
			・取扱商品から見る業種の傾向と今後のトレンド。
			・SNSを利用している場合、ビジネス情報を元にSNS毎の活用方法について。
			・売上分析用データとビジネス情報を元に今後の成長戦略の立案",
		'report_type' => $report_type
	],[
		'ai_role' => 'データアナリスト',
		'report_name' => 'yearly2',
		'your_ask' => "レポートで知りたいことは次の通り。
			・目標と現状とのギャップの確認及び、ギャップを埋めるための提案。
			・注力すべき商品とそうでない商品の選定。
			・出るべきイベント
			・地域、天気と売上の関連。
			・売上が期待できる住所エリア
			・取扱商品から見る業種の傾向と今後のトレンド。
			・SNSを利用している場合、ビジネス情報を元にSNS毎の活用方法について。
			・売上分析用データとビジネス情報を元に今後の成長戦略の立案",
		'report_type' => $report_type
	],*/[
		'ai_role' => 'データアナリスト',
		'report_name' => '12month',
		'your_ask' => "レポートの構成は次の通り。
			1. 目標と現状の確認、および目標達成のための提案
			現在の目標と現状を明確にし、季節や今後のイベントなどを考慮した目標達成戦略を提案します。
			2. 月ごとの売上推移
			月ごとの売上推移を棒グラフと表で視覚的に示します。
			3. 季節別売上トップ5と分析
			季節ごとの売上トップ5の商品を表にまとめ、そこから得られる分析結果を提示します。
			4. ABC分析に基づく商品選定
			ABC分析の表を用いて、注力すべき商品とそうでない商品を明確に選定します。
			5. 商品カテゴリー別売上分析
			大カテゴリー別の売上集計結果を分析し、円グラフで表示します。
			中カテゴリー別の売上集計結果を分析し、円グラフで表示します。
			小カテゴリー分類別の売上ランキング結果を分析します。
			6. イベント戦略
			イベント別平均売上表から、参加すべきイベントとそうでないイベントを取捨選択します。
			平均売上トップ10のイベントにおける商品売上のABC分析結果のA,B+のみの表を作成・分析し、注力すべき製品を特定します。
			7. 地域・天気と売上の関連性
			地域や天気と売上の関連性を分析し、売上が期待できる住所エリアを特定します。
			8. 業種の傾向と今後のトレンド
			取扱商品から現在の業種の傾向を把握し、今後のトレンドについて考察します。
			9. SNS活用戦略
			SNSを利用している場合、ビジネス情報に基づき、SNSごとの具体的な活用方法と有効なハッシュタグについて提案します。
			10. 商品別売上ランキング
			商品ごとの売上ランキングをトップ10とワースト10で表示します。
			11. 今後の成長戦略の立案
			売上分析データとビジネス情報を総合的に活用し、粗利を最大化すべく、今後の成長戦略を立案します。",
		'report_type' => $report_type
	]/*,[
		'ai_role' => 'データアナリスト',
		'report_name' => '5years',
		'your_ask' => "レポートで知りたいことは次の通り。
			・目標と現状とのギャップの確認及び、ギャップを埋めるための提案。
			・注力すべき商品とそうでない商品の選定。
			・出るべきイベント
			・地域、天気と売上の関連。
			・売上が期待できる住所エリア
			・取扱商品から見る業種の傾向と今後のトレンド。
			・SNSを利用している場合、ビジネス情報を元にSNS毎の活用方法について。
			・売上分析用データとビジネス情報を元に今後の成長戦略の立案",
		'report_type' => $report_type
	]*/
];
//$ai_settings_def["your_ask"]から空白、tabを削除,改行は残す
$i=0;
foreach($ai_settings_def as $row){
	$ai_settings_def[$i]["your_ask"] = trim(str_replace([" ", "　","\t"], "", $row["your_ask"]));
	$i++;
}
//log_writer2("\$ai_settings_def", $ai_settings_def, "lv3");

$sql_ai = "SELECT * from analysis_ai_setting where uid=? order by upd_datetime desc";
$stmt = $pdo_h->prepare($sql_ai);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$ai_settings = $stmt->fetchAll(PDO::FETCH_ASSOC);

//初期値は直近１２ヵ月
$last_report = "12month";
if(count($ai_settings) > 0){
	//登録済のanalysis_ai_settingを取得
	//複数レポートに対応出来たら直近出力したレポート種類をセットする　を有効にする
	//$last_report = $ai_settings[0]["report_name"] ?? "12month";
} else {
	//$ai_settings_defをanalysis_ai_settingに挿入
	$stmt = $pdo_h->prepare("insert into analysis_ai_setting (uid,ai_role,report_name,your_ask,report_type) values (?,?,?,?,?)");
	foreach($ai_settings_def as $row){
		$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
		$stmt->bindValue(2, $row["ai_role"], PDO::PARAM_STR);
		$stmt->bindValue(3, $row["report_name"], PDO::PARAM_STR);
		$stmt->bindValue(4, $row["your_ask"], PDO::PARAM_STR);
		$stmt->bindValue(5, $row["report_type"], PDO::PARAM_STR);
		$stmt->execute();
	}

	$ai_settings = $ai_settings_def;
}
//log_writer2("\$ai_settings", $ai_settings, "lv3");

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php" 
	?>
	<style>
		.accordion{
			--bs-accordion-btn-bg:#c0fbff ; 
		}
		</style>
	<!--ページ専用CSS--><link rel="stylesheet" href="css/style_ShouhinMSedit.css?<?php echo $time; ?>" >
	<TITLE><?php echo secho($title)." 取扱商品登録画面";?></TITLE>
</head>
<body class='common_body'>
	<header class="header-color common_header" style="flex-wrap:wrap">
		<div class="title" style="width: 100%;"><a href="menu.php" class='item_15'><?php echo secho($title);?></a></div>
		<p style="font-size:1rem;color:var(--user-disp-color);font-weight:400;">  A.I分析レポート</p>
		<a href="#" style='color:inherit;position:fixed;top:45px;right:5px;' onclick='help()'><i class="bi bi-question-circle logoff-color"></i></a>	
	</header>
	<div id='app'>
		<main style='padding-bottom:200px;'>
			<!--your_bussinessの入力フォーム-->
			<div class="container">
				<div class="accordion mt-3" id="accordionExample">
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed fs-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
								<p class='m-0'>ビジネス情報を入力（タップすると開きます）<br><span class='fs-5'>入力することでより具体的なレポートとなります。</span></p>
							</button>
						</h2>
						<div id="collapseOne" class="collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
							<div class="card-body mt-3">
								<div class="mb-3 text-primary">
									<p class='m-0'>hint:文章作成が苦手な人は、箇条書きでも大丈夫です。</p>
								</div>
								<div class="mb-3">
									<label for="Product_categories" class="form-label">取扱商品のジャンル</label>
									<textarea class="form-control" id="Product_categories" v-model="your_bussiness.取扱商品のジャンル" rows="3"></textarea>
								</div>
								<div class="mb-3">
									<label for="Sales_methods" class="form-label">販売方法</label>
									<textarea class="form-control" id="Sales_methods" v-model="your_bussiness.販売方法" rows="3"></textarea>
								</div>
								<div class="mb-3">
									<label for="Brand_image" class="form-label">ブランドイメージ/コンセプト</label>
									<textarea class="form-control" id="Brand_image" v-model="your_bussiness.ブランドイメージ" rows="3"></textarea>
								</div>
								<div class="mb-3">
									<label for="Customer_targets" class="form-label">顧客ターゲット/ペルソナ</label>
									<textarea class="form-control" id="Customer_targets" v-model="your_bussiness.顧客ターゲット" rows="3"></textarea>
								</div>
								<div class="mb-3">
									<!--利用しているSNSの種類をinput type checkboxで選択。選択肢は[Instagram,X.com,facebook,Threads,tiktok-->
									<label for="sns_type" class="form-label">利用しているSNSの種類</label>
									<div>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="checkbox" id="sns_instagram" value="use" v-model="your_bussiness.Instagram">
											<label class="form-check-label" for="sns_instagram">Instagram</label>
										</div>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="checkbox" id="sns_x" value="use" v-model="your_bussiness.X_com">
											<label class="form-check-label" for="sns_x">X.com</label>
										</div>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="checkbox" id="sns_facebook" value="use" v-model="your_bussiness.facebook">
											<label class="form-check-label" for="sns_facebook">Facebook</label>
										</div>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="checkbox" id="sns_threads" value="use" v-model="your_bussiness.Threads">
											<label class="form-check-label" for="sns_threads">Threads</label>
										</div>
										<div class="form-check form-check-inline">
											<input class="form-check-input" type="checkbox" id="sns_tiktok" value="use" v-model="your_bussiness.tiktok">
											<label class="form-check-label" for="sns_tiktok">TikTok</label>
										</div>
										<div class="">
											<label class="form-label" for="other_SNS">その他のSNS</label>
											<input class="form-control" type="text" id="other_SNS" v-model="your_bussiness.other_SNS">
										</div>
									</div>
								</div>
								<div class="mb-3">
									<label for="Monthly_goals" class="form-label">月毎の目標</label>
									<textarea class="form-control" id="Monthly_goals" v-model="your_bussiness.月毎の目標" rows="2"></textarea>
								</div>
								<div class="mb-3">
									<label for="This_year_goals" class="form-label">今年度の目標</label>
									<textarea class="form-control" id="This_year_goals" v-model="your_bussiness.今年度の目標" rows="2"></textarea>
								</div>
								<div class="mb-3">
									<label for="Next_year_goals" class="form-label">来年度の目標</label>
									<textarea class="form-control" id="Next_year_goals" v-model="your_bussiness.来年度の目標" rows="2"></textarea>
								</div>
								<div class="mb-3">
									<label for="Ideal_years" class="form-label">5年後の理想</label>
									<textarea class="form-control" id="Ideal_years" v-model="your_bussiness.年後の理想" rows="2"></textarea>
								</div>
							</div>
							<div class="card-footer text-end pt-3 pb-3">
								<button type="button" class="btn btn-primary me-3" @click="ins_bussiness" >ビジネス情報登録</button>
							</div>
						</div>
					</div><!-- accordion-item -->
					<div class="accordion-item">
						<h2 class="accordion-header">
							<button class="accordion-button collapsed fs-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo">
								<p class='m-0'>レポートの体裁など<br><span class='fs-5 '>AIプロンプトに多少理解がある方はいじってみてください</span></p>
							</button>
						</h2>
						<div id="collapseTwo" class="collapse" aria-labelledby="headingOne" data-bs-parent="#accordionExample">
							<div class="card-body mt-3">
								<div class="mb-3 text-primary">
									<p class='m-0'>hint:文章作成が苦手な人は、箇条書きでも大丈夫です。</p>
								</div>
								<div class="mb-3">
									<label for="Product_categories" class="form-label">レポート体裁</label>
									<textarea class="form-control" id="Product_categories" v-model="ai_settings[ai_setting_i].report_type" rows="20"></textarea>
								</div>
						</div>
					</div><!-- accordion-item -->
				</div><!-- accordion -->
				<div class="row mt-3">
					<div class="col-12 ">
					<div class="mb-3">
							<label for='ai_role' class='form-label'>AIに求める役割</label>
							<select class='form-select'  id='ai_role' v-model='ai_settings[ai_setting_i].ai_role'>
								<option v-for="role in ai_roles" :key="role" :value="role">{{ role }}</option>
							</select>
						</div>
						<div class="mb-3">
							<label for='report_name' class='form-label'>レポート種類</label>
							<select class='form-select form-select-lg' v-model='report_name' id='report_name'>
								<option value="">選択してください</option>
								<template v-for='list in report_types' :key='list.value' >
									<option :value="list.value">{{list.name}}</option>
								</template>
							</select>
						</div>
						<div class="mb-3">
							<label for='mail' class='form-label'>レポート送付先メールアドレス</label>
							<input type='text' class='form-control' v-model='mail' id='mail'>
						</div>
						<div class="mb-3">
							<label for="Product_categories" class="form-label">レポート作成依頼</label>
							<textarea class="form-control" id="Product_categories" v-model="ai_settings[ai_setting_i].your_ask" rows="20"></textarea>
						</div>
					</div>
				</div>
			</div>
		</main>
		<footer class='fixed-bottom ' style='background-color: #f5f5f5;'>
			<div class='container' style='height: 120px;'>
				<div class="col-12 pt-3">
					<div class="form-check">
					  <input class="form-check-input" type="checkbox" v-model='save_setting' id="flexCheckDefault">
					  <label class="form-check-label" for="flexCheckDefault">
					    上記設定を保存してレポート作成
					  </label>
					</div>
					<div class='d-flex'>
						<button type="button" class="btn btn-primary me-3" @click="get_gemini_response" :disabled="loading">
							<span v-if="loading" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
							{{ loading ? '生成中...' : 'レポート生成' }}
						</button>
						<button type="button" class='btn btn-warning' @click='ai_setting_modosu'>初期値に戻す</button>
					</div>
				</div>
			</div>
		</footer>
	</div><!--#app-->
	<script>
		const { createApp, ref, onMounted, computed, VueCookies, watch,nextTick  } = Vue;
		createApp({
			setup(){
				const your_bussiness = ref(<?php echo json_encode($business_info[0],JSON_UNESCAPED_UNICODE); ?>);
				
				const ai_settings_def = ref(<?php echo json_encode($ai_settings_def,JSON_UNESCAPED_UNICODE); ?>);
				const ai_settings = ref(<?php echo json_encode($ai_settings,JSON_UNESCAPED_UNICODE); ?>);
				const ai_roles = ref(<?php echo json_encode($ai_roles, JSON_UNESCAPED_UNICODE); ?>);
				const report_types = ref(<?php echo json_encode($report_types, JSON_UNESCAPED_UNICODE); ?>);
				
				const report_name = ref('<?php echo $last_report; ?>')
				const report_i = computed(() => {
					//report_nameの値
					let index = 0
					report_types.value.forEach((list,i) => {
						if(list.value === report_name.value){
							index = i
						}
					});
					return index
				})

				const ai_setting_i = computed(() => {
					//ai_settings[][report_name]=report_name.valueとなるai_settingsを返す
					let index = 0
					ai_settings.value.forEach((list,i) => {
						if(list.report_name === report_name.value){
							index = i
						}
					});
					//return ai_settings.value[index]
					return index
				})

				const ai_setting_def_i = computed(() => {
					//ai_settings[][report_name]=report_name.valueとなるai_settingsを返す
					let index = 0
					ai_settings_def.value.forEach((list,i) => {
						if(list.report_name === report_name.value){
							index = i
						}
					});
					//return ai_settings_def.value[index]
					return index
				})

				const ai_setting_modosu = () =>{
					//ai_roleなどをデフォルトに戻す
					console_log(`ai_setting_modosu start`)
					console_log(`${ai_settings_def.value[ai_setting_def_i.value]}`)
					ai_settings.value[ai_setting_i.value] = ai_settings_def.value[ai_setting_def_i.value]
				}
				const mail = ref('<?php echo $user_info[0]["mail"]; ?>')

				const save_setting = ref(true)	//プロンプト・ビジネス情報の保存可否

				const gemini_response = ref('');
				const loading = ref(false);
				const iframe_url = ref(`${your_bussiness.value.uid}_gemini_report.html`)
				let csrf_token = '<?php echo $csrf_token;?>'

				const get_gemini_response = async () => {
					loading.value = true;
					if(save_setting.value){
						ins_bussiness()
					}
					try {
						//console_log(your_ask.value)
						const form = new FormData();
						form.append('Article', `あなたはベテランの${ai_settings.value[ai_setting_i.value].ai_role}です。\n最後に提示する売上分析用のJSONデータをもとに、次の売上分析レポートを作成してください。レポート名：『${report_types.value[report_i.value].name}』、${ai_settings.value[ai_setting_i.value].your_ask}\n\n次の出力様式を守ってください。\n${ai_settings.value[ai_setting_i.value].report_type}\n私のビジネス情報は次の通り。${JSON.stringify(your_bussiness.value)}`);
						form.append('type', 'one');
						//form.append('answer_type', 'html');
						form.append('report_name', report_name.value);
						form.append('title', report_types.value[report_i.value].name);
						form.append('save_setting', save_setting.value);
						form.append('ai_role', ai_settings.value[ai_setting_i.value].ai_role);
						form.append('your_ask', ai_settings.value[ai_setting_i.value].your_ask);
						form.append('report_type', ai_settings.value[ai_setting_i.value].report_type);
						form.append('mail', mail.value);

						const response = await axios.post('ajax_gemini_make_report.php', form, {headers: {'Content-Type': 'multipart/form-data'}});
						console_log(response.data)
						gemini_response.value = response.data.result;
					} catch (error) {
						console.error('Error fetching Gemini response:', error);
						gemini_response.value = '<p style="color:red;">レポートの取得中にエラーが発生しました。</p>';
					} finally {
						loading.value = false;
					}
				}

				const ins_bussiness = () =>{
					//your_bussinessをaxios.postでajax_delins_business_info.phpに送信
					let params = new URLSearchParams()
					params.append('app', 'webrez')
					params.append('Product_categories', your_bussiness.value.取扱商品のジャンル)
					params.append('Sales_methods', your_bussiness.value.販売方法)
					params.append('Brand_image', your_bussiness.value.ブランドイメージ)
					params.append('Monthly_goals', your_bussiness.value.月毎の目標)
					params.append('This_year_goals', your_bussiness.value.今年度の目標)
					params.append('Next_year_goals', your_bussiness.value.来年度の目標)
					params.append('Ideal_5_years', your_bussiness.value.年後の理想)
					params.append('Customer_targets', your_bussiness.value.顧客ターゲット)
					params.append('Instagram', your_bussiness.value.Instagram)
					params.append('X_com', your_bussiness.value.X_com)
					params.append('facebook', your_bussiness.value.facebook)
					params.append('Threads', your_bussiness.value.Threads)
					params.append('tiktok', your_bussiness.value.tiktok)
					params.append('other_SNS', your_bussiness.value.other_SNS)
					params.append('csrf_token', csrf_token)

					axios
					.post('ajax_delins_business_info.php',params)
					.then((response) => {
						console_log(response.data,'lv3')
						csrf_token = response.data.csrf_create
						if(response.data.status!=="success"){
							alert(response.data.MSG)
						}
					})
					.catch((error) => {
						console_log(`ins_bussiness ERROR:${error}`,'lv3')
					})
					.finally(()=>{
						//console_log(myChart,'lv3')
					})
					return 0;

				}

				const make_report = () =>{
					if(save_setting.value===true){
						ins_bussiness()
					}
					get_gemini_response()
				}

				onMounted(() => {
					//get_gemini_response();
				});

				return {
					your_bussiness,
					//your_sales_data,
					ai_roles,
					//ai_role,
					//your_ask,
					gemini_response,
					loading,
					get_gemini_response,
					iframe_url,
					ins_bussiness,
					//report_type,
					save_setting,
					make_report,
					report_name,
					report_types,
					ai_setting_modosu,
					mail,
					ai_settings,
					ai_settings_def,
					ai_setting_i,
					ai_setting_def_i,
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

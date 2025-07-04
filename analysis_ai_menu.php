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
$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

//ビジネスインフォを取得
$sql_bi = "SELECT 
	Product_categories as 取扱商品のジャンル
	, Sales_methods as 販売方法
	, Brand_image as ブランドイメージ
	, Instagram
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

//analysis_ai_settingを取得
$sql_ai_setting = "SELECT 
	ai_role
	,data_range
	,your_ask
	,report_type
	from analysis_ai_setting where uid=?";
$stmt = $pdo_h->prepare($sql_ai_setting);
$stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
$stmt->execute();
$ai_setting = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
	'ウィークリーレポート (先週)',
	'月次レポート (先月)',
	'月次レポート (先月と今月)', 
	'年次レポート (昨年)', 
	'年次レポート (昨年と今年)',
	'直近１２ヵ月レポート', 
	'過去５年と今後の見通し', 
];

$ai_setting_def = [
	'ai_role' => 'データアナリスト',
	'data_range' => '直近１２ヵ月レポート',
	'your_ask' => "レポートで知りたいことは次の通り。
		・目標と現状とのギャップの確認及び、ギャップを埋めるための提案。
		・注力すべき商品とそうでない商品の選定。
		・出るべきイベント
		・地域、天気と売上の関連。
		・売上が期待できる住所エリア
		・取扱商品から見る業種の傾向と今後のトレンド。
		・インスタのアカウント設定がある場合はインスタもチェック。活用方法のアドバイスを下さい。",
	'report_type' => "・レポートはHTMLで作成し、HTMLのみを出力する。
		・最適なHTMLフレームワークを使う。
		・レスポンシブデザインを採用。
		・売上分析用のJSONデータをもとに表を作成。
		・レポート名に適した範囲の売上実績表のみを作成する。
		・読みやすさを重視し、口語体で作成。
		・金額はカンマ区切り。
		・小数以下は無視する。"
];
//$ai_setting_def["your_ask"]から空白、tabを削除,改行は残す
$ai_setting_def["your_ask"] = str_replace([" ", "　","\t"], "", $ai_setting_def["your_ask"]);
$ai_setting_def["your_ask"] = trim($ai_setting_def["your_ask"]);

$ai_setting_def["report_type"] = str_replace([" ", "　","\t"], "", $ai_setting_def["report_type"]);
$ai_setting_def["report_type"] = trim($ai_setting_def["report_type"]);


if(count($ai_setting) > 0){
	$ai_setting = $ai_setting[0];
} else {
	$ai_setting = $ai_setting_def;
}
log_writer2("analysis_ai_setting", $ai_setting, "lv3");

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
									<label for="Instagram" class="form-label">InstagramアカウントID</label>
									<input class="form-control" id="Instagram" v-model="your_bussiness.Instagram" >
								</div>
								<div class="mb-3">
									<label for="Monthly_goals" class="form-label">月毎の目標</label>
									<textarea class="form-control" id="Monthly_goals" v-model="your_bussiness.月毎の目標" rows="3"></textarea>
								</div>
								<div class="mb-3">
									<label for="This_year_goals" class="form-label">今年度の目標</label>
									<textarea class="form-control" id="This_year_goals" v-model="your_bussiness.今年度の目標" rows="3"></textarea>
								</div>
								<div class="mb-3">
									<label for="Next_year_goals" class="form-label">来年度の目標</label>
									<textarea class="form-control" id="Next_year_goals" v-model="your_bussiness.来年度の目標" rows="3"></textarea>
								</div>
								<div class="mb-3">
									<label for="Ideal_years" class="form-label">5年後の理想</label>
									<textarea class="form-control" id="Ideal_years" v-model="your_bussiness.年後の理想" rows="3"></textarea>
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
									<textarea class="form-control" id="Product_categories" v-model="report_type" rows="5"></textarea>
								</div>
								<!--<div class="mb-3">
									<label for="Sales_methods" class="form-label">販売方法</label>
									<textarea class="form-control" id="Sales_methods" v-model="your_bussiness.販売方法" rows="3"></textarea>
								</div>
							</div>
							<div class="card-footer text-end">
								<button type="button" class="btn btn-primary me-3" @click="ins_bussiness" >ビジネス情報登録</button>
							</div>-->
						</div>
					</div><!-- accordion-item -->
				</div><!-- accordion -->
				<div class="row mt-3">
					<div class="col-12 ">
					<div class="mb-3">
							<label for='ai_role' class='form-label'>AIに求める役割</label>
							<select class='form-select' v-model='ai_role' id='ai_role'>
								<option v-for="role in ai_roles" :key="role" :value="role">{{ role }}</option>
							</select>
						</div>
						<div class="mb-3">
							<label for='data_range' class='form-label'>レポート種類</label>
							<select class='form-select form-select-lg' v-model='data_range' id='data_range'>
								<option value="">選択してください</option>
								<template v-for='item in report_types' :key='item.value' >
									<option :value="item">{{item}}</option>
								</template>
							</select>
						</div>
						<div class="mb-3">
							<label for='mail' class='form-label'>レポート送付先メールアドレス</label>
							<input type='text' class='form-control' v-model='mail' id='mail'>
						</div>
						<div class="mb-3">
							<label for="Product_categories" class="form-label">レポート作成依頼</label>
							<textarea class="form-control" id="Product_categories" v-model="your_ask" rows="20"></textarea>
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
		document.getElementById("app").onkeypress = (e) => {
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
				
				const ai_setting_def = <?php echo json_encode($ai_setting_def,JSON_UNESCAPED_UNICODE); ?>;
				const ai_roles = ref(<?php echo json_encode($ai_roles, JSON_UNESCAPED_UNICODE); ?>);
				const report_types = ref(<?php echo json_encode($report_types, JSON_UNESCAPED_UNICODE); ?>);

				const ai_role = ref('<?php echo $ai_setting["ai_role"]; ?>')
				const data_range = ref('<?php echo $ai_setting["data_range"]; ?>')
				const your_ask = ref(`<?php echo $ai_setting["your_ask"]; ?>`);
				const report_type = ref(`<?php echo $ai_setting["report_type"]; ?>`)

				const ai_setting_modosu = () =>{
					//ai_roleなどをデフォルトに戻す
					ai_role.value = ai_setting_def.ai_role;
					data_range.value = ai_setting_def.data_range;
					your_ask.value = ai_setting_def.your_ask;
					report_type.value = ai_setting_def.report_type;
				}
				const mail = ref('<?php echo $row[0]["mail"]; ?>')

				const save_setting = ref(true)	//プロンプト・ビジネス情報の保存可否

				const gemini_response = ref('');
				const loading = ref(false);
				const iframe_url = ref(`${your_bussiness.value.uid}_gemini_report.html`)
				let csrf_token = '<?php echo $csrf_token;?>'

				const get_gemini_response = async () => {
					loading.value = true;
					try {
						//console_log(your_ask.value)
						const form = new FormData();
						form.append('Article', `あなたはベテランの${ai_role.value}です。\n最後に提示する売上分析用のJSONデータをもとに、次の売上分析レポートを作成してください。レポート名：『${data_range.value}』、${your_ask.value}\n\n次の出力様式にを守ってください。\n${report_type.value}\n私のビジネス情報は次の通り。${JSON.stringify(your_bussiness.value)}`);
						form.append('type', 'one');
						//form.append('answer_type', 'html');
						form.append('data_range', data_range.value);
						form.append('save_setting', save_setting.value);
						form.append('ai_role', ai_role.value);
						form.append('your_ask', your_ask.value);
						form.append('report_type', report_type.value);
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
					ai_role,
					your_ask,
					gemini_response,
					loading,
					get_gemini_response,
					iframe_url,
					ins_bussiness,
					report_type,
					save_setting,
					make_report,
					data_range,
					report_types,
					ai_setting_modosu,
					mail,
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

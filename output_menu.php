<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);
$token = csrf_create();
$ua = $_SERVER['HTTP_USER_AGENT'];
$domain = $_SERVER['HTTP_HOST'];
log_writer2("\$_SERVER['HTTP_USER_AGENT']",$_SERVER['HTTP_USER_AGENT'],"lv3");
//log_writer2("\$_SERVER['HTTP_HOST']",$_SERVER['HTTP_HOST'],"lv3");

$tanmatu="";
if ((strpos($ua, 'Android') !== false) && (strpos($ua, 'Mobile') !== false) || (strpos($ua, 'iPhone') !== false) || (strpos($ua, 'Windows Phone') !== false)) {
  //スマホの場合に読み込むソースを記述
  $tanmatu="phone";

} elseif ((strpos($ua, 'Android') !== false) || (strpos($ua, 'iPad') !== false)) { 
  //タブレットの場合に読み込むソースを記述
  $tanmatu="tabret";

} else { 
  //PCの場合に読み込むソースを記述
  $tanmatu="PC";
}
$sqlstr="";
//
//log_writer2("\$POST",$_POST,"lv3");
if(!empty($_POST)){

	$ymfrom = $_POST["ymfrom"];
	$ymto = $_POST["ymto"];
	$list = $_POST["list"];
	
	if($_POST["soft"]==="yayoi"){
		$sqlstr="select Uridate,sum(UriageKin+zei) as zeikomi,CONCAT('売上No:',UriageNo) as 売上No,case when zeiKBN='1001' then '軽減税率8%' else '' end as 税率";
		$sqlstr=$sqlstr." from UriageData where uid=? and Uridate between ? and ?";
		$sqlstr=$sqlstr." group by Uridate,CONCAT('売上No:',UriageNo),case when zeiKBN='1001' then '軽減税率8%' else '' end";
		$sqlstr=$sqlstr." order by Uridate,売上No";
	}elseif($_POST["soft"]==="freee"){
		$sqlstr="select '収入' as 収支,CURDATE()+0,Uridate,'".$_POST["kamoku"]."' as 科目,sum(UriageKin) as 売上,'外税',sum(zei) as 税額,CONCAT('売上No:',UriageNo) as 備考,'".$_POST["nyukin_kamoku"]."' as 決済口座";
		$sqlstr=$sqlstr." from UriageData where uid=? and Uridate between ? and ?";
		$sqlstr=$sqlstr." group by CURDATE()+0,Uridate,CONCAT('売上No:',UriageNo)";
		$sqlstr=$sqlstr." order by Uridate,備考";
	}elseif($_POST["soft"]==="moneyforward"){
		$sqlstr="select '' as 取引NO,DATE_FORMAT(Uridate,'%Y/%m/%d') as 取引日,'".$_POST["nyukin_kamoku"]."' as 借方科目,'' as 借方補助,'' as 借方部門,'' as 借方取引先,'対象外' as 借方税区分,'' as 借方インボイス,sum(UriageKin+zei) as 借方金額,0 as 借方税,'".$_POST["kamoku"]."' as 貸方科目,'' as 貸方補助,'' as 貸方部門,'' as 貸方取引先,if(A.zeiKBN=0,'対象外',CONCAT('課税売上',B.hyoujimei)) as 貸方税区分,'' as 貸方インボイス,sum(UriageKin+zei) as 貸方金額,'' as 貸方税額,CONCAT('売上No:',UriageNo) as 摘要,'' as 仕訳メモ,'' as タグ,'' as MF仕訳タイプ,'' as 決算整理仕訳,'' as 作成日,'' as 作成者,'' as 最終更新日時,'' as 最終更新者";
		$sqlstr=$sqlstr." from UriageData A inner join ZeiMS B on A.zeiKBN = B.zeiKBN where uid=? and Uridate between ? and ?";
		$sqlstr=$sqlstr." group by Uridate,CONCAT('売上No:',UriageNo),if(A.zeiKBN=0,'対象外',CONCAT('課税売上',B.hyoujimei))";
		$sqlstr=$sqlstr." order by Uridate";

	}

	$stmt = $pdo_h->prepare($sqlstr);
	$stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
	$stmt->bindValue(2, $ymfrom, PDO::PARAM_INT);
	$stmt->bindValue(3, $ymto, PDO::PARAM_INT);
	$stmt->execute();
	$row = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if($_POST["soft"]==="yayoi"){
		output_csv($row,$ymfrom."-".$ymto);
	}elseif($_POST["soft"]==="freee"){
		output_xlsx($row,$ymfrom."-".$ymto);
	}elseif($_POST["soft"]==="moneyforward"){
		$head = ["取引No","取引日","借方勘定科目","借方補助科目","借方部門","借方取引先","借方税区分","借方インボイス","借方金額(円)","借方税額","貸方勘定科目","貸方補助科目","貸方部門","貸方取引先","貸方税区分","貸方インボイス","貸方金額(円)","貸方税額","摘要","仕訳メモ","タグ","MF仕訳タイプ","決算整理仕訳","作成日時","作成者","最終更新日時","最終更新者"];
		array_unshift($row, $head);
		output_csv($row,$ymfrom."-".$ymto);
	}
}else{
	$ymfrom = (string)date('Y')."-01-01";
	$ymto = (string)date('Y')."-12-31";
	$list = "%";
}

?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head_bs5.php" 
	?>
	<!--ページ専用CSS-->
	<link rel="stylesheet" href="css/style_outputmenu.css?<?php echo $time; ?>" >
	<TITLE><?php echo $title;?></TITLE>
</head>
<main>
	<header class="header-color common_header">
		<div class="yagou title"><a href="menu.php"><?php echo $title;?></a></div></a></div>
	</header>

	<body class='common_body'>
		<div class="container" style="padding-top:10px;" id='form1'>
		<div v-if='tanmatsu!=="PC"' style="font-size:1.5rem">
			<div class='alert alert-warning'>ＰＣからの操作をお願いします。</div>
			<p>ＰＣで下記URLへアクセスしてください。</p>
			<p>URL：https://<?php echo $domain;?></p>
			<label class='mt-3' for='mail'>URLをメールで送る</label>
			<input class='form-control' style="font-size:1.5rem;max-width:400px;width:100%;" type='mail' id='mail' name='mail' v-model='mail' required='required' >
			<small>
				<p class='mb-0'><?php echo FROM;?> からURLが記載したメールが届きます。</p>
				<p>受信できない場合、迷惑メールフィルタなどの設定をご確認ください。</p>
			</small>

			<button type='button' class='btn btn-primary mt-3' @click='sendmail'>メール送信</button>
			<!--<hr>
				ITに強い方はブラウザアクセスに切替えて、このまま「連携データ出力」を行い、ダウンロードしたデータを確定申告ソフトに取り込んでください。
				<br>データをメールで転送、クラウドに保存等の手段でPCからアクセス可能とする等の方法が考えられます。
				<br>なお、その場合の操作方法は端末により様々ですので、WebRezのサポート対象外です。
				<p><a href='https://<?php //echo $domain;?>' target="_blank" rel="noopener noreferrer">https://<?php //echo $domain;?></a></p>
			<hr class='mb-5'>-->
		</div>
		<form v-if='tanmatsu==="PC"' method='post' action='#' style="font-size:1.5rem">
			<label for='soft'>連携会計システムの選択</label>
			<select class='form-select mb-3' style="font-size:1.5rem;padding:0;max-width:400px;width:100%;" name='soft' id='soft' v-model='soft' required='required' >
				<option value='yayoi'>やよいの青色申告 オンライン</option>
				<option value='moneyforward'>MoneyForwardクラウド</option>
				<option value='freee'>freee会計</option>
			</select>
			<div class="box29">
				<div class="box-title mb-3">連携手順</div>
				<ol v-if='soft==="yayoi"' style='list-style-position: inside;'>
					<li>連携データを出力</li>
					<li>やよいの青色申告を開く</li>
					<li>スマート取引取込　を選択</li>
					<li>CSVファイル取込　を起動</li>
					<li>出力した連携データを指定して取込（※）</li>
				</ol>
				<ol v-if='soft==="moneyforward"' style='list-style-position: inside;'>
					<li>各計上科目 を設定</li>
					<li>連携データを出力</li>
					<li>MoneyForwardクラウドを開く</li>
					<li>［会計帳簿］＞「仕訳帳」メニュー を選択</li>
					<li>［インポート］＞「仕訳帳」を選択</li>
					<li> [ファイル選択]で出力した連携データを指定して取込（※）</li>
				</ol>
				<ol v-if='soft==="freee"' style='list-style-position: inside;'>
					<li>各計上科目 を設定</li>
					<li>連携データを出力</li>
					<li>freee会計を開く</li>
					<li>［取引］メニュー を選択</li>
					<li>［エクセルインポート］　を起動</li>
					<li>出力した連携データを指定して取込（※）</li>
				</ol>
				<div class='ps-3 mt-3'>
					<a :href='manual_url' class='btn btn-primary' target="_blank">（※）取込の詳しい手順はコチラ</a>
					<p class='mt-0 p-1'><small>ご利用ソフトのヘルプページを表示します。</small></p>
				</div>
				<div class='ps-3 mt-3'>
					<a :href='delete_manual_url' class='btn btn-primary' target="_blank">会計ソフトに取込んだデータの削除手順はコチラ</a>
					<p class='mt-0 p-1'><small>ご利用ソフトのヘルプページを表示します。</small></p>
				</div>
			</div>

			<div v-if='soft==="freee"' class='mb-5'>
				<label for='kamoku'>売上計上科目名</label>
				<input class='form-control mb-3' style="font-size:1.5rem;max-width:200px;" type='text' id='kamoku' name='kamoku' v-model='kamoku' required='required' >
				<label for='nyukin_kamoku'>売上入金先科目名</label>
				<input class='form-control mb-1' style="font-size:1.5rem;max-width:200px;" type='text' id='nyukin_kamoku' name='nyukin_kamoku' v-model='nyukin_kamoku' required='required' >
				<small>[事業主貸] [現金] など</small>
			</div>
			<div v-if='soft==="moneyforward"' class='mb-5'>
				<label for='kamoku'>売上計上科目名</label>
				<input class='form-control mb-3' style="font-size:1.5rem;max-width:200px;" type='text' id='kamoku' name='kamoku' v-model='kamoku' required='required' >
				<label for='nyukin_kamoku'>売上入金先科目名</label>
				<input class='form-control mb-1' style="font-size:1.5rem;max-width:200px;" type='text' id='nyukin_kamoku' name='nyukin_kamoku' v-model='nyukin_kamoku' required='required' >
				<small>[事業主貸] [現金] など</small>
			</div>
			<label for='ymfrom'> 出力対象期間</label>
			<input class='form-control' style="font-size:1.5rem;max-width:200px;" type='date' id='ymfrom' name='ymfrom' v-model='ymfrom' required='required' >
			<label for='ymto'>から</label>
			<input class='form-control mb-3' style="font-size:1.5rem;max-width:200px;" type='date' id='ymto' name='ymto' v-model='ymto' required='required' >
			
			<input class='btn btn-primary mt-5' type='submit' value='連携データ出力' style='width:200px;height:70px;'>
		</form>
		</div>

	</body>
</main>
<script>
	const { createApp, ref, onMounted, computed, VueCookies, watch,nextTick  } = Vue;
	createApp({
		setup(){
			const token = ref('<?php echo $token;?>')
			const ymfrom = ref('<?php echo $ymfrom; ?>')
			const ymto = ref('<?php echo $ymto; ?>')
			const tanmatsu = ref('<?php echo $tanmatu; ?>')
			const soft = ref('yayoi')
			const mail = ref('')
			const uri_label = computed(()=>{
				if(soft.value==="yayoi"){
					return '売上計上名目'
				}else if(soft.value==="freee"){
					return '売上計上科目'
				}
			})
			const kamoku = computed(()=>{
				if(soft.value==="yayoi"){
					return ''
				}else if(soft.value==="freee"){
					return '売上高'
				}else if(soft.value==="moneyforward"){
					return '売上高'
				}
			})
			const nyukin_kamoku = computed(()=>{
				if(soft.value==="yayoi"){
					return ''
				}else if(soft.value==="freee"){
					return '現金'
				}else if(soft.value==="moneyforward"){
					return '現金'
				}
			})
			const kamoku_set = computed(()=>{
				if(soft.value==="yayoi"){
					return false
				}else if(soft.value==="freee"){
					return true
				}else if(soft.value==="moneyforward"){
					return true
				}
			})

			const manual_url = computed(()=>{
				let url
				if(soft.value==="yayoi"){
					return 'https://support.yayoi-kk.co.jp/subcontents.html?page_id=27061&grade_id=Blue'
				}else if(soft.value==="freee"){
					return 'https://support.freee.co.jp/hc/ja/articles/216527163-Excel-%E8%B2%A9%E5%A3%B2%E7%AE%A1%E7%90%86%E3%82%BD%E3%83%95%E3%83%88%E3%81%8B%E3%82%89%E3%83%87%E3%83%BC%E3%82%BF%E3%82%92%E5%8F%96%E3%82%8A%E8%BE%BC%E3%82%80-%E3%82%A8%E3%82%AF%E3%82%BB%E3%83%AB%E3%82%A4%E3%83%B3%E3%83%9D%E3%83%BC%E3%83%88#h_01FZD6MB131E4HB2NHXX5XYN03'
				}else if(soft.value==="moneyforward"){
					return 'https://biz.moneyforward.com/support/account/guide/import-books/ib01.html'
				}

			})
			const delete_manual_url = computed(()=>{
				let url
				if(soft.value==="yayoi"){
					return 'https://support.yayoi-kk.co.jp/faq_Subcontents.html?page_id=27321&grade_id=Blue'
				}else if(soft.value==="freee"){
					return 'https://support.freee.co.jp/hc/ja/articles/360000683243-%E3%82%A2%E3%83%83%E3%83%97%E3%83%AD%E3%83%BC%E3%83%89%E3%81%97%E3%81%9F%E6%98%8E%E7%B4%B0%E3%82%92%E5%89%8A%E9%99%A4%E3%81%99%E3%82%8B%E3%81%AB%E3%81%AF%E3%81%A9%E3%81%86%E3%81%99%E3%82%8C%E3%81%B0%E3%82%88%E3%81%84%E3%81%A7%E3%81%99%E3%81%8B#:~:text=%EF%BC%BB%E5%8F%A3%E5%BA%A7%EF%BC%BD%E3%83%A1%E3%83%8B%E3%83%A5%E3%83%BC%20%E2%86%92%EF%BC%BB%E6%98%8E%E7%B4%B0,%E3%81%97%E3%81%A6%E5%89%8A%E9%99%A4%E3%81%97%E3%81%BE%E3%81%99%E3%80%82'
				}else if(soft.value==="moneyforward"){
					return 'https://biz.moneyforward.com/support/account/faq/import-features/import018.html'
				}

			})


			const sendmail = () =>{
				if(mail.value.length==0){
					alert('メールアドレスを入力して下さい')
					return
				}
				let params = new URLSearchParams()
				params.append('csrf_token', token.value);
				params.append('mail', mail.value);
				params.append('subject', "【WebRez+】より送信");
				params.append('body', "WebRez+ へのURLは以下の通りです。\r\nhttps://<?php echo $domain;?>");
				axios
				.post('ajax_sendmail.php',params)
				.then((response) => {
					if(response.data.status==='success'){
						alert('メールを送信しました。')
					}else{
						alert(response.data.MSG)
					}
					token.value = response.data.csrf_create
				})
				.catch((error) => console_log(`get_UriageList ERROR:${error}`,'lv3'));
			}

			onMounted(() => {
				console_log('onMounted','lv3')
				if(window.matchMedia('(display-mode: standalone)').matches){
					// ここにPWA環境下でのみ実行するコードを記述
					console_log('onPWA','lv3')
					tanmatsu.value = "pwa"
				}
			})
			return{
				ymfrom,
				ymto,
				soft,
				uri_label,
				kamoku,
				nyukin_kamoku,
				kamoku_set,
				tanmatsu,
				mail,
				sendmail,
				manual_url,
				delete_manual_url
			}                
		}
	}).mount('#form1');
	</script>

</html>
<?php
	$pdo_h=null;
?>
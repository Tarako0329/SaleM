<?php
require "php_header.php";
$rtn=check_session_userid($pdo_h);
$token = csrf_create();
$ua = $_SERVER['HTTP_USER_AGENT'];
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
        $sqlstr="select '収入' as 収支,CURDATE()+0,Uridate,'".$_POST["kamoku"]."' as 科目,sum(UriageKin) as 売上,'外税',sum(zei) as 税額,CONCAT('売上No:',UriageNo) as 備考,'事業主貸' as 決済口座";
        $sqlstr=$sqlstr." from UriageData where uid=? and Uridate between ? and ?";
        $sqlstr=$sqlstr." group by CURDATE()+0,Uridate,CONCAT('売上No:',UriageNo)";
        $sqlstr=$sqlstr." order by Uridate,備考";

    }

    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->bindValue(2, $ymfrom, PDO::PARAM_INT);
    $stmt->bindValue(3, $ymto, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    output_csv($row,$ymfrom."-".$ymto);
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
    <link rel="stylesheet" href="css/style_menu.css?<?php echo $time; ?>" >
    <TITLE><?php echo $title;?></TITLE>
</head>
<main>
    <header class="header-color common_header">
        <div class="yagou title"><a href="menu.php"><?php echo $title;?></a></div></a></div>
    </header>

    <body class='common_body'>
        <div class="container" style="padding-top:15px;" id='form1'>
        <div v-if='tanmatsu!=="PC"' >
            売上データを会計・確定申告用ソフトに取り込むためのファイルを出力します。
            確定申告に使用しているPC等からWebRez＋(下記URL)へアクセスしてください。
            URL：https://webrez.greeen-sys.com
        </div>
        <!--<form v-if='tanmatsu==="PC"' method='post' action='#' style="font-size:1.5rem">-->
        <form method='post' action='#' style="font-size:1.5rem" target="_blank">
            <label for='soft'>連携会計システムの選択</label>
            <select class='form-select mb-3' style="font-size:1.5rem;padding:0;max-width:400px;" name='soft' id='soft' v-model='soft'>
                <option value='yayoi'>やよいの青色申告 オンライン</option>
                <option value='freee'>freee会計(確定申告)</option>
            </select>
            <div v-if='kamoku_set'>
                <label for='kamoku'>{{uri_label}}</label>
                <input class='form-control mb-5' style="font-size:1.5rem;max-width:200px;" type='text' id='kamoku' name='kamoku' v-model='kamoku'>
            </div>
            <label for='ymfrom'> 出力対象期間</label>
            <input class='form-control' style="font-size:1.5rem;max-width:200px;" type='date' id='ymfrom' name='ymfrom' v-model='ymfrom'>
            <label for='ymto'>から</label>
            <input class='form-control mb-3' style="font-size:1.5rem;max-width:200px;" type='date' id='ymto' name='ymto' v-model='ymto'>
            
            <input class='btn btn-primary mt-5' type='submit' value='CSV出力' style='width:200px;height:100px;'>
        </form>
        </div>

    </body>
</main>
<script>
	const { createApp, ref, onMounted, computed, VueCookies, watch,nextTick  } = Vue;
    createApp({
		setup(){
			const ymfrom = ref('<?php echo $ymfrom; ?>')
			const ymto = ref('<?php echo $ymto; ?>')
			const tanmatsu = ref('<?php echo $tanmatu; ?>')
            const soft = ref('yayoi')
            const uri_label = computed(()=>{
                if(soft.value==="yayoi"){
                    return '売上計上名目'
                }else if(soft.value==="freee"){
                    return '売上計上科目'
                }
            })
            const kamoku = computed(()=>{
                if(soft.value==="yayoi"){
                    return '売上'
                }else if(soft.value==="freee"){
                    return '売上高'
                }
            })
            const kamoku_set = computed(()=>{
                if(soft.value==="yayoi"){
                    return false
                }else if(soft.value==="freee"){
                    return true
                }
            })

            const manual_url = computed(()=>{
                if(soft.value==="yayoi"){
                    return 'https://support.yayoi-kk.co.jp/subcontents.html?page_id=27061'
                }else if(soft.value==="freee"){
                    return 'https://support.freee.co.jp/hc/ja/articles/216527163-Excel-%E8%B2%A9%E5%A3%B2%E7%AE%A1%E7%90%86%E3%82%BD%E3%83%95%E3%83%88%E3%81%8B%E3%82%89%E3%83%87%E3%83%BC%E3%82%BF%E3%82%92%E5%8F%96%E3%82%8A%E8%BE%BC%E3%82%80-%E3%82%A8%E3%82%AF%E3%82%BB%E3%83%AB%E3%82%A4%E3%83%B3%E3%83%9D%E3%83%BC%E3%83%88#h_01FZD6MB131E4HB2NHXX5XYN03'
                }
            })

			onMounted(() => {
				console_log('onMounted','lv3')
			})
			return{
				ymfrom,
				ymto,
                soft,
                uri_label,
                kamoku,
                kamoku_set,
                tanmatsu,
			}                
		}
	}).mount('#form1');
	</script>

</html>
<?php
    $pdo_h=null;
?>
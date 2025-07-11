<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

チュートリアル
start(ajax関数名(固定値),ツアー名称(DBに登録する名称),ステータス(finish;完了,save;保存(次回途中から始まる),'空白：$_SESSION["tour"]にnewで指定しtourNameをセット)'
*/
{
    require "php_header.php";
    if((EXEC_MODE==="Trial" || EXEC_MODE==="TrialL") && !empty($_GET["trid"])){
        $_SESSION["user_id"] = $_GET["trid"];
    }
    //log_writer2("before_check_session_userid",$_SESSION,"lv3");
    $rtn=check_session_userid($pdo_h); 
    //deb_echo($_SESSION["user_id"]);
    $token = csrf_create();
    $logoff=false;
    $Max_color_No=2;
    
    $action="";
    $bell_action="";
    $bell_size="fa-lg";
    $bell_msg="";
    
    if(!empty($_GET["action"])){
        $action = $_GET["action"];
    }

    if($action=="logout"){
        delete_old_token($_COOKIE["webrez_token"],$pdo_h);
        redirect_to_login("ログオフしました");
        exit();
    }else{
        $_SESSION["PK"]=PKEY;
        $_SESSION["SK"]=SKEY;
        
        $_SESSION["URL"]=ROOT_URL."subscription.php";   //支払成功後にアクセスするURL
        $_SESSION["PLAN_M"]=PLAN_M;
        $_SESSION["PLAN_Y"]=PLAN_Y;
        $_SESSION["SUBID"]="";      //strip subscription idをクリア
        
        //ユーザ情報の取得
        $sql="select * from Users_webrez where uid=?";
        $stmt = $pdo_h->prepare($sql);
        $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $msg="";

        //契約・解約関連は各時で実装したファイルを指定する
        $root_url = bin2hex(openssl_encrypt(ROOT_URL, 'AES-128-ECB', "1"));
        $dir_path = bin2hex(openssl_encrypt(dirname(__FILE__)."/", 'AES-128-ECB', "1"));
        
        //強制ログアウト処理
        if($row[0]["ForcedLogout"]==true){
            //system更新を有効にするためにログアウトが必要な場合の処理
            $sql="update Users set ForcedLogout=false where uid=?";
            $stmt = $pdo_h->prepare($sql);
            $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
            $stmt->execute();
            ?>
            <!DOCTYPE html>
            <html lang='ja'>
            <script>
            if ('serviceWorker' in navigator) {
                window.navigator.serviceWorker.getRegistrations()
                .then(registrations => {
                    for(let registration of registrations) {
                        registration.unregister();
                        console_log('Service Worker is delete');
                    }
                });
            }
            </script>
            </html>
            <?php
            redirect_to_login("システム更新のため、強制ログオフしました");
            exit();
        }
        //契約状況の確認
        if(EXEC_MODE==="Product"){
            if(empty($row[0]["keiyakudate"])===true){
                if(strtotime($row[0]["yuukoukigen"]) < strtotime(date("Y-m-d"))){
                    //有効期限切れ。申込日から即課金
                    $_SESSION["KIGEN"] = strtotime("+3 day");
                    $msg= "<p class='mb-1 fs-3' style='color:red;'>無料お試し期間は終了しました。</p>";
                    $msg.="<p class='mb-1 fs-3'>引続きのご利用は<a href='".rot13decrypt2(PAY_CONTRACT_URL)."?system=".$title."&sysurl=".$root_url."&dirpath=".$dir_path."'>本契約</a>をお願いいたします。</p>";
                }else{
                    //試用期間、もしくは支払済み期間の翌日から課金
                    $_SESSION["KIGEN"] = strtotime($row[0]["yuukoukigen"] ."+1 day");
                    $msg= "無料お試し期間(".$row[0]["yuukoukigen"]." まで無料)<br>";
                }
                $kigen = $row[0]["yuukoukigen"];
                if((strtotime($row[0]["yuukoukigen"]) - strtotime(date("Y-m-d")))/ (60 * 60 * 24) >= 0 && (strtotime($row[0]["yuukoukigen"]) - strtotime(date("Y-m-d")))/ (60 * 60 * 24) <= 7){
                    $msg = "<p class='mb-1 fs-3' style='color:red;'>残り ".(strtotime($row[0]["yuukoukigen"]) - strtotime(date("Y-m-d")))/ (60 * 60 * 24)."日 で無料期間が終了します。</p>";
                    $msg.="<p class='mb-1 fs-3'>引続きのご利用は<a href='".rot13decrypt2(PAY_CONTRACT_URL)."?system=".$title."&sysurl=".$root_url."&dirpath=".$dir_path."'>本契約</a>をお願いいたします。</p>";
                    $msg.="<p class='mb-1 fs-3'>ご契約完了後、無料期間終了をもって本契約に切り替わります。</p>";
                }
                /*if(EXEC_MODE=="Trial"){
                    $_SESSION["KIGEN"] = strtotime($row[0]["yuukoukigen"] ."+1 day");
                    $msg= "無料お試し期間(".$row[0]["yuukoukigen"]." まで無料)<br>";
                }*/
            
                $plan=0;
            }else{
                //契約済
                $plan=1;
                if(empty($row[0]["Accounting_soft"])){
                    $msg = "<p class='mb-1 fs-3' style='color:red;'>確定申告に利用しているソフトを『ユーザ情報』より登録してください。</p>";
                    $msg .= "<p class='mb-1 fs-5'>登録いただいたソフトがWebRez未対応の場合、対応するためにご協力をお願いする場合がございます。</p>";
                }
            }
        }else if(EXEC_MODE=="Trial" || EXEC_MODE=="TrialL"){
            $_SESSION["KIGEN"] = strtotime($row[0]["yuukoukigen"] ."+1 day");
            $msg= "お試し期間( ～".$row[0]["yuukoukigen"]." )<br>期間を過ぎると入力内容はすべてクリアされます。ご自由に操作して下さい。<br>";
        }
        //ユーザ名・屋号の取得
        if($row[0]["yagou"]<>""){
            $user=$row[0]["yagou"];
        }elseif($row[0]["name"]<>""){
            $user=$row[0]["name"];
        }else{
            $user=$row[0]["mail"];
        }
    
        
        //新機能リリース通知
        $sqlstr="SELECT uid, insdate,JSON_VALUE(ToursLog,'$.new_releace_007') as ToursLog FROM Users_webrez WHERE uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //deb_echo($row[0]["ToursLog"]);
        if(empty($row[0]["ToursLog"]) && $row[0]["insdate"] < RELEACE_DATE){
            //新機能リリース通知 未確認
            //新機能リリース日より前に登録したユーザを対象とする
            //$bell_action="blink";
            //$bell_size="fa-2x";
            //$bell_msg="お知らせ";
            $version = "new_releace_007";
        }else{
            //新機能リリース通知 確認済み
            $version="";
        }
    
    }


    if(empty($_SESSION["tour"])){
        //ツアー中でない場合、チュートリアルが終わっているか確認する
        $sqlstr="SELECT uid,JSON_VALUE(ToursLog,'$.tutorial') as tutorial FROM Users_webrez WHERE uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        //log_writer2("JSON_VALUE",$row,"lv3");
        if(empty($row[0]["tutorial"])){
            //チュートリアル未実施
            $_SESSION["tour"]="tutorial_1";
        }elseif($row[0]["tutorial"]=="finish"){
            //チュートリアル完了
            
        }else{
            //チュートリアル実施中（再開）
            $_SESSION["tour"]=$row[0]["tutorial"];
        }
    }else{
        log_writer2("\$_SESSION['tour']",$_SESSION["tour"],"lv3");
    }

    $array = [
        'レジ<p style="font-size:11px;margin:0;">マルシェ等、店舗型販売</p>'=>['EVregi.php?mode=evrez&csrf_token='.$token,'rez']
        ,'個別売上レジ<p style="font-size:11px;margin:0;">受注販売・個人オーダー等</p>'=>['EVregi.php?mode=kobetu&csrf_token='.$token,'k_rez']
        ,'商品登録'=>['shouhinMSedit.php?csrf_token='.$token,'s_tou']
        ,'商品一覧'=>['shouhinMSList.php?csrf_token='.$token,'s_itiran']
        ,'商品QR作成'=>['shouhinMSQR.php?csrf_token='.$token,'qr_itiran']
        ,'商品ｶﾃｺﾞﾘｰ設定'=>['shouhinMSCategoryEdit.php?csrf_token='.$token,'s_itiran']
        ,'出品在庫登録'=>['EVregi.php?mode=shuppin_zaiko&csrf_token='.$token,'z_rez']
        ,'売上実績'=>['UriageData_Correct.php?mode=select&first=first&Type=rireki&diplay=where&csrf_token='.$token,'uri']
        ,'売上分析'=>['analysis_menu.php?csrf_token='.$token,'bunseki']
        ,'A.I分析ﾚﾎﾟｰﾄ'=>['analysis_ai_menu.php?csrf_token='.$token,'bunseki_ai']
        ,'領収書<p style="font-size:11px;margin:0;">再発行・返品処理</p>'=>['ryoushu_menu.php?csrf_token='.$token,'ryoushu']
        ,'ユーザ情報'=>['account_create.php?mode=1&csrf_token='.$token,'user']
        ,'確定申告'=>['output_menu.php?csrf_token='.$token,'kaikei']
        ,'ｱﾌﾟﾘを紹介する'=>['shoukai.php?csrf_token='.$token,'shoukai']
    ];
    
    //契約・解約関連は各時で実装したファイルを指定する
    $root_url = bin2hex(openssl_encrypt(ROOT_URL, 'AES-128-ECB', "1"));
    $dir_path = bin2hex(openssl_encrypt(dirname(__FILE__)."/", 'AES-128-ECB', "1"));
    
    if(EXEC_MODE=="Product"){
        if($plan==0){
            $array2 = ['本契約へ'=>[rot13decrypt2(PAY_CONTRACT_URL)."?system=".$title."&sysurl=".$root_url."&dirpath=".$dir_path,'keiyaku']];
        }else{
            $array2 = ['契約解除へ'=>['sub_cancel.php','kaijo']];
        }
    }else if(EXEC_MODE=="Test" || EXEC_MODE=="Local"){
        if($plan==0){
            $array2 = ['本契約へ'=>[rot13decrypt2(PAY_CONTRACT_URL)."?system=".$title."&sysurl=".$root_url."&dirpath=".$dir_path,'keiyaku'],'機能テスト'=>['sample.php?a=a','kinoutest']];
        }else{
            $array2 = ['契約解除へ'=>['sub_cancel.php','kaijo'],'機能テスト'=>['sample.php','kinoutest']];
        }
    }else{
        $array2=array();
    }





}
?>

<!DOCTYPE html>
<html lang='ja'>
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.php" 
    ?>
    <!--ページ専用CSS-->
    <link rel='stylesheet' href='css/style_menu.css?<?php echo $time; ?>' >
    <TITLE><?php echo $title;?></TITLE>
</head>

<body class='common_body' >
    <header class='common_header header-color' style='display:block'>
        <?php
        if($logoff==false){
        ?>
            <div class='yagou title'><a href='menu.php'><?php echo $title;?></a></div>
            <div class='user_disp'>LogIn：<?php echo $user; ?></div>
            <div style='position:fixed;top:3px;right:3px;'><a href='menu.php?action=logout'><i class='fa-solid fa-right-from-bracket fa-lg logoff-color'></i></a></div>
        <?php
        }else{
        ?>
            <div class='yagou title'><a href='index.php'><?php echo $title;?></a></div>
        <?php
        }
        ?>
        <div class='<?php echo $bell_action;?> logoff-color' style='position:fixed;top:35px;right:5px;' id='bell'>
            <a href="#" style='color:var(--user-disp-color);' onclick='new_releace_start()'>
                <?php echo $bell_msg; ?><i class="fa-regular fa-bell <?php echo $bell_size;?> logoff-color"></i>
            </a>
        </div>
    </header>
    <div style='position:fixed;top:70px;right:0;' class='rainbow-color'>
        <b><a href='#' onclick='ColorChange()'>COLOR<i class='fa-solid fa-rotate-right fa-lg rainbow-color'></i></a></b>
    </div>
    <div class='container-fluid' style='padding-top:5px;'>
        <?php if(EXEC_MODE<>"Trial" || EXEC_MODE<>"TrialL"){?>
        <div class='col-12 text-center mb-3'>
            <button type='button' class='btn btn-info' style='display:none;margin:auto;' onClick='document.getElementById("pwa_info_btn").click()' id='install_info_btn'>インストール手順はコチラ</button>
        </div>
        <?php } ?>
    <?php
        
    //deb_echo(ROOT_URL);
    //deb_echo(EXEC_MODE."：uid_".$_SESSION["user_id"]);

    echo $msg;

    $i=0;
    echo "<div class='row'>";
	foreach(array_merge($array,$array2) as $key=>$vals){
        //echo "  <div class ='col-md-3 col-sm-6 col-6 menu menu_".$i."' style='padding:5px;'>\n";
        echo "  <div class ='col-md-3 col-sm-6 col-6 menu menu_".$vals[1]."' id='menu_".$vals[1]."' style='padding:5px;'>\n";
        if($vals[1]=="rez"){//通常レジ
            echo "      <button class='btn--topmenu btn-view' onClick=postFormRG('".$vals[0]."','evrez','".$token."')>".$key."</button>\n";
        }else if($vals[1]=="k_rez"){//個別売り
            echo "      <button class='btn--topmenu btn-view' onClick=postFormRG('".$vals[0]."','kobetu','".$token."')>".$key."</button>\n";
        }else if($vals[1]=="z_rez"){//在庫登録
            echo "      <button class='btn--topmenu btn-view' onClick=postFormRG('".$vals[0]."','shuppin_zaiko','".$token."')>".$key."</button>\n";
        }else{
            echo "      <a href='".$vals[0]."' class='btn--topmenu btn-view'>".$key."</a>\n";
        }
        echo "  </div>\n";
        $i++;
	}
    echo "</div>";
	
?> 
              
    </div>
    <footer class='common_footer' style='padding:5px;'>
        <p style='padding:5px;'>お問い合わせはコチラ</p>
        <a href='https://lin.ee/HLSLl23' style='padding:5px;'><i class="fa-brands fa-line fa-2x line-green"></i></a>
        <!--<a href='https://green-island.mixh.jp/wdps/%e3%81%8a%e5%95%8f%e3%81%84%e5%90%88%e3%82%8f%e3%81%9b/' style='padding:5px;' target='_blank' rel='noopener noreferrer'><i class="fa-solid fa-square-envelope fa-2x"></i></a>-->
        <p style='position:fixed;right:10px;bottom:0;'><?php echo VERSION;?></p>
    </footer>
    <?php
        $icon="img/icon-192x192.png";
        require "install_modal.php"
    ?>
</body>
<!--シェパードナビshepherd
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php 
    require "ajax_func_tourFinish.php";
    $hello=(EXEC_MODE=="Trial"?"WebRez+に興味を持って頂きありがとうございます。":"ご登録ありがとうございます。");
?>
<script><!--チュートリアル-->
    const TourMilestone = '<?php echo $_SESSION["tour"];?>';

    const tutorial_1 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'tutorial_1'
    });
    tutorial_1.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 
              <?php echo $hello; ?>
              <br>
              <br>これから簡単にWEBREZの使い方を説明します。
              <br>10～20分ほどの操作になります。
              <br>
              <br>「後で見る」をタップすると、この画面への再アクセス時に再開されます。</p>`,
        buttons: [
            {
                text: '後で見る',
                action: tutorial_1.cancel
            },
            {
                text: 'Next',
                action: tutorial_1.next
            }
        ]
    });
    tutorial_1.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>＜WebRez+の基本的な使い方＞
                <br><a href="#" ><i class="bi bi-question-circle Qicon awesome-color-panel-border-same"></i></a>マークをタップするとヘルプが表示されます。
                <br>
                <br>色々試しながら使い方を覚えたい方は「不要」をタップすると、以降のチュートリアルは表示されません。
                </p>`,
        buttons: [
            {
                text: '不要',
                action: tutorial_1.skip
            },
            {
                text: '後で見る',
                action: tutorial_1.cancel
            },
            {
                text: 'Next',
                action: tutorial_1.next
            }
        ]
    });
    tutorial_1.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>途中で右上の「ｘ」をタップするとチュートリアルは強制終了します。
               <br>その場合、再度この画面にアクセスした際に自動的に再開します。
               </p>`,
        buttons: [
            {
                text: 'Next',
                action: tutorial_1.next
            }
        ]
    });
    tutorial_1.addStep({
        title: `<p class='tour_header'>チュートリアル。</p>`,
        text: `<p class='tour_discription'> チュートリアルの流れは以下の通りです。
              <br>
              <br>１．取扱商品の登録
              <br>２．レジの使い方
              <br>３．売上実績の確認/修正/削除
              <br>４．商品情報の修正/削除
              <br></p>`,
        buttons: [
            {
                text: '不要',
                action: tutorial_1.skip
            },
            {
                text: '後で見る',
                action: tutorial_1.cancel
            },
            {
                text: 'Next',
                action: tutorial_1.next
            }
        ]
    });
    tutorial_1.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>まずはレジに表示する商品を登録しましょう。</p>`,
        buttons: [
            {
                text: 'Next',
                action: tutorial_1.next
            }
        ]
    });
    tutorial_1.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「商品登録」ボタンをタップしてください。</p>`,
        attachTo: {
            element: '.menu_s_tou',
            on: 'bottom'
        },
        cancelIcon:{
            enabled:false
        }
    });

    const tutorial_4 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'tutorial_4'
    });
    tutorial_4.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>続いて「レジ」画面の説明に移ります。</p>`,
        buttons: [
            {
                text: 'Next',
                action: tutorial_4.nextAndSave
            }
        ]
    });
    tutorial_4.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「レジ」ボタンをタップしてください。</p>`,
        attachTo: {
            element: '.menu_rez',
            on: 'bottom'
        },
        cancelIcon:{
            enabled:false
        }
    });
    
    const tutorial_8 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'tutorial_8'
    });
    tutorial_8.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>続いて「売上実績」を確認します。</p>`,
        cancelIcon:{
            enabled:false
        },
        buttons: [
            {
                text: 'Next',
                action: tutorial_8.nextAndSave
            }
        ]
    });
    tutorial_8.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「売上実績」ボタンをタップしてください。</p>`,
        attachTo: {
            element: '.menu_uri',
            on: 'bottom'
        },
        cancelIcon:{
            enabled:false
        }
    });

    const tutorial_11 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'tutorial_11'
    });
    tutorial_11.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>最後に、レジ用に登録した商品情報の修正について説明します。</p>`,
        cancelIcon:{
            enabled:false
        },
        buttons: [
            {
                text: 'Next',
                action: tutorial_11.nextAndSave
            }
        ]
    });
    tutorial_11.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>「商品一覧」ボタンをタップしてください。</p>`,
        attachTo: {
            element: '.menu_s_itiran',
            on: 'bottom'
        },
        cancelIcon:{
            enabled:false
        }
    });
    console_log(TourMilestone)
    if(TourMilestone=="tutorial_1"){
        tutorial_1.start(tourFinish,'tutorial','');
    }else　if(TourMilestone=="tutorial_3" || TourMilestone=="tutorial_4"){
        tutorial_4.start(tourFinish,'tutorial','');    
    }else　if(TourMilestone=="tutorial_7_1"){
        tutorial_8.start(tourFinish,'tutorial','');    
    //}else　if(TourMilestone=="tutorial_10"){
    }else　if(TourMilestone=="tutorial_9"){
        tutorial_11.start(tourFinish,'tutorial','');    
    }
    
</script><!--チュートリアル-->
<!--チュートリアル以外のヘルプ・出品在庫-->
<script>
    const new_releace = '<?php echo $version;?>'
    const new_releace_name = sessionStorage.getItem('tourname');

    const new_releace_005 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'new_releace_005'
    });
    new_releace_005.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'><span style='color:blue'>「QRスキャン登録」機能</span>を追加しました。
            <br>
            <br>一般的なレジでよく見る、バーコードを『ピッ！』とやるやつです。
            <br>
            <br>使い方の説明は２分程度です。
            <br>
            <br>後で見たい方は「後で見る」をタップしてください。
            <br>「お知らせ」をタップすると、案内が再開します。
            <br>`,
        buttons: [
            {
                text: '後で見る',
                action: new_releace_005.cancel
            },
 			{
				text: 'Next',
				action: new_releace_005.next
			}
        ],
        cancelIcon:{
            enabled:false
        }
    });
    new_releace_005.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'><span style='color:blue'>「QRスキャン登録」機能</span>を追加しました。
            <br>
            <br>レジ画面の　<i class="bi bi-qr-code-scan awesome-color-panel-border-same fs-1"></i>　をタップするとQR読取モードになります。
            <br>
            <br>『QRスキャン登録』は、<span style='color:red;'>レジ表示ON/OFFの設定不要</span>で全ての商品に利用できます。
            <br>
            <br>また、商品価格などを変更しても<span style='color:red;'>QRコードは変更なしで利用可能です。</span>
            <br>`,
        buttons: [
            {
                text: '戻る',
                action: new_releace_005.back
            },
 			{
				text: 'Next',
				action: new_releace_005.next
			}
        ],
        cancelIcon:{
            enabled:false
        }
    });
    new_releace_005.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'>
            <br>スキャン用のQRコードはコチラのメニューから作成します。
            <br>`,
        /*buttons: [
			{
				text: 'Next',
				action: new_releace_005.next
			}
        ],*/
        attachTo: {
            element: '#menu_qr_itiran',
            on: 'bottom'
        },
        cancelIcon:{
            enabled:false
        }
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
            <br>レジ画面に移動します。
            <br>`,
        /*buttons: [
			{
				text: 'Next',
				action: new_releace_005.next
			}
        ],*/
        attachTo: {
            element: '#menu_rez',
            on: 'bottom'
        },
        cancelIcon:{
            enabled:false
        }
    });
    const new_releace_005_2 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'new_releace_005_2'
    });
    new_releace_005_2.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'>
            <br>レジ機能に「バーコードでピッと」やる機能を追加しましたが、お使いの端末では利用出来ないようです。
            <br>
            <br>safari/chromeからカメラへのアクセスを許可するか、
            <br>カメラ付き端末でレジを起動すると、右上のベルマークから利用方法が確認できます。
            <br>`,
        buttons: [
			{
				text: 'OK',
				action: new_releace_005_2.next
			}
        ],

        cancelIcon:{
            enabled:false
        }
    });
    const new_releace_006 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'new_releace_006'
    });
    new_releace_006.addStep({
        title: `<p class='tour_header'>「商品一覧」リニューアルのお知らせ</p>`,
        text: `<p class='tour_discription'>
            商品一覧の画面を使いやすくリニューアルしました。<br>
            ポイントは以下の通りです。<br></p>
            <ul class='fs-3'>
            <li class='mb-3' style='line-height:23px;'>レジの表示/非表示切換はダイレクトに反映</li>
            <li class='mb-3' style='line-height:23px;'><i class='fa-regular fa-trash-can '></i>ボタンで削除できない商品は、削除しない代わりに表示位置を一番最後に。</li>
            <li class='mb-3' style='line-height:23px;'>１商品ずつ変更・登録することで変更漏れを減らす</li>
            </ul>
            <br>
            <p class='tour_discription'>もし操作方法がわからなかったら、商品一覧画面の右上<i class="bi bi-question-circle Qicon"></i>マークをタップしてください。

            </p>`,
        buttons: [
            /*{
                text: '後で見る',
                action: new_releace_006.cancel
            },*/
 			{
				text: 'Finish',
				action: new_releace_006.complete
			}
        ],
        cancelIcon:{
            enabled:false
        }
    });
    const new_releace_007 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'new_releace_007'
    });
    new_releace_007.addStep({
        title: `<p class='tour_header'>「A.I分析ﾚﾎﾟｰﾄ」を追加しました</p>`,
        text: `<p class='tour_discription'>
            A.Iによる分析レポート作成機能をリリースしました。<br>
            使い方は以下の通りです。<br></p>
            <ol class='fs-3'>
            <li class='mb-3' style='line-height:23px;'>ご自身のビジネス情報や目標などを入力</li>
            <li class='mb-3' style='line-height:23px;'>A.Iの役割を選択</li>
            <li class='mb-3' style='line-height:23px;'>A.Iに聞きたいことを記入する。</li>
            </ol>
            <br>
            <p class='tour_discription'>おススメの初期値が入力済みですので、まずはそのままレポート作成してみてください。</p>
            <br>
            `,
        buttons: [
            /*{
                text: '後で見る',
                action: new_releace_006.cancel
            },*/
 			{
				text: 'Finish',
				action: new_releace_007.complete
			}
        ],
        attachTo: {
            element: '#menu_bunseki_ai',
            on: 'bottom'
        },
        cancelIcon:{
            enabled:false
        }
    });

    const new_releace_start = async() => {
        //新機能のリリース通知はこの関数で呼び出すツアーを更新する
        //shuppin_zaiko_help1.start(tourFinish,'new_releace_001','');
        //new_releace_002.start(tourFinish,'new_releace_002',''); 
        //new_releace_003.start(tourFinish,'new_releace_003','finish'); 
        //new_releace_004.start(tourFinish,'new_releace_004','finish'); 
        /*
        const devices = await navigator.mediaDevices.enumerateDevices();
        const cam_dev = devices.filter((device) => device.kind === "videoinput")
        if(cam_dev.length!==0){
            new_releace_005.start(tourFinish,'new_releace_005',''); 
            sessionStorage.setItem('tourname', 'new_releace_005');
        }else{
            console_log("cant use camera")
            new_releace_005_2.start(tourFinish,'new_releace_005','finish'); 
        }
        */
        //new_releace_006.start(tourFinish,'new_releace_006','finish'); 
        new_releace_007.start(tourFinish,'new_releace_007','finish'); 
    }
    
    if(new_releace && !new_releace_name){
        new_releace_start()
    }
    if(new_releace_name==='new_releace_005'){
        new_releace_005_1.start(tourFinish,'new_releace_005',''); 
    }
    window.onload = function(){
        if (window.matchMedia('(display-mode: standalone)').matches) {
    		// PWAとして起動された場合の処理
            console_log("PWA")
    	} else {
    		//alert('ブラウザとして起動されました');
    		const userAgent = navigator.userAgent;
      	    if (
      	        userAgent.indexOf('Windows') !== -1 ||
      	        userAgent.indexOf('Macintosh') !== -1 ||
      	        userAgent.indexOf('Linux') !== -1
      	    ) {
      	        // パソコン.なにもしない
                console_log("パソコン")
      	    } else {
      	        // パソコン以外。インストールを勧める
    	        document.getElementById("install_info_btn").style.display = 'block'
      	    }
    	}
    }
</script>
<!--pwa対応部-->
<script>
/*  //PWA対応 オフライン対応メニュー以外を非表示にする
    var menu = document.getElementsByClassName("menu");   //全メニュー
    if(navigator.onLine){
        alert('on-line');
        for (let i = 0; i < menu.length; i++) {
            menu.item(i).style.display = 'block';
        }
    }else{
        alert('off-line');
        for (let i = 1; i < menu.length; i++) {
            menu.item(i).style.display = 'none';
        }
        
    }
*/    
</script>
<!--pwa対応部(メニューのURL_GETをPOSTに変更)-->
<script>
    function postFormRG(url,mode,token) {
    
        var form = document.createElement('form');
        var request_mode = document.createElement('input');
        var request_token = document.createElement('input');
    
        form.method = 'POST';
        form.action = url;
    
        request_mode.type = 'hidden'; //入力フォームが表示されないように
        request_mode.name = 'mode';
        request_mode.value = mode;
    
        request_token.type = 'hidden'; //入力フォームが表示されないように
        request_token.name = 'csrf_token';
        request_token.value = token;
    
        form.appendChild(request_mode);
        form.appendChild(request_token);
        document.body.appendChild(form);
    
        form.submit();
    
    }
    const ColorChange = () =>{
        console_log('ColorChange start')
        COLOR_NO.No = Number(COLOR_NO.No) + Number(1)
        if(Number(COLOR_NO.No)>=3){
            COLOR_NO.No = Number(0)
        }
        console_log(COLOR_NO)
        IDD_Write('LocalParameters',[COLOR_NO])
        set_color(COLOR_NO)
    }
</script>
</html>
<?php
    $pdo_h=null;
?>
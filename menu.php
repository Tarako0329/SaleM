<!DOCTYPE html>
<html lang='ja'>
<!--menu.php-->
<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

遷移先のチェック
csrf_chk()                              ：COOKIE・SESSION・POSTのトークンチェック。
csrf_chk_nonsession()                   ：COOKIE・POSTのトークンチェック。
csrf_chk_nonsession_get($_GET[token])   ：COOKIE・GETのトークンチェック。
csrf_chk_redirect($_GET[token])         ：SESSSION・GETのトークンチェック

チュートリアル
start(ajax関数名(固定値),ツアー名称(DBに登録する名称),ステータス(finish;完了,save;保存(次回途中から始まる),'空白：$_SESSION["tour"]にnewで指定しtourNameをセット)'
*/

require "php_header.php";
$rtn=check_session_userid($pdo_h);
//deb_echo($_SESSION["user_id"]);
$token = csrf_create();
$logoff=false;
$Max_color_No=2;

$action="";

if(!empty($_GET["action"])){
    $action = $_GET["action"];
}

if($action=="color_change"){
    //配色の変更・保存
    $color_No = $_GET["color"]+1;
    if($color_No>$Max_color_No){
        $color_No=0;
    }
    $stmt = $pdo_h->prepare ( 'call PageDefVal_update(?,?,?,?,?)' );
    $stmt->bindValue(1, $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindValue(2, MACHIN_ID, PDO::PARAM_STR);
    $stmt->bindValue(3, "menu.php", PDO::PARAM_STR);
    $stmt->bindValue(4, "COLOR", PDO::PARAM_STR);
    $stmt->bindValue(5, $color_No, PDO::PARAM_STR);
    $stmt->execute();
}

if($action=="logout"){
    $_SESSION = array();// セッション変数を全て解除する
    
    // セッションを切断するにはセッションクッキーも削除する。
    // Note: セッション情報だけでなくセッションを破壊する。
    if (isset($_COOKIE[session_name()])) {
        setCookie(session_name(), '', -1, "/", '.'.MAIN_DOMAIN, TRUE, TRUE); 
    }
    setCookie("webrez_token", '', -1, "/", null, TRUE, TRUE); 

    session_destroy();// 最終的に、セッションを破壊する
    $logoff=true;
}else{
    $_SESSION["PK"]=PKEY;
    $_SESSION["SK"]=SKEY;
    
    $_SESSION["URL"]=ROOT_URL."subscription.php";   //支払成功後にアクセスするURL
    $_SESSION["PLAN_M"]=PLAN_M;
    $_SESSION["PLAN_Y"]=PLAN_Y;
    $_SESSION["SUBID"]="";      //strip subscription idをクリア
    
    //ユーザ情報の取得
    $sql="select * from Users where uid=?";
    $stmt = $pdo_h->prepare($sql);
    $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $msg="";
    //強制ログアウト処理
    if($row[0]["ForcedLogout"]==true){
        //system更新を有効にするためにログアウトが必要な場合の処理
        $sql="update Users set ForcedLogout=false where uid=?";
        $stmt = $pdo_h->prepare($sql);
        $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        header("HTTP/1.1 301 Moved Permanently");
        header("Location: menu.php?action=logout&ForcedLogout=true");
        exit();
    }
    //契約状況の確認
    if($row[0]["yuukoukigen"]<>""){
        if(strtotime($row[0]["yuukoukigen"]) < strtotime(date("Y-m-d"))){
            //有効期限切れ。申込日から即課金
            $_SESSION["KIGEN"] = strtotime("+3 day");
            $msg= "有効期限切れ<br>";
        }else{
            //試用期間、もしくは支払済み期間の翌日から課金
            $_SESSION["KIGEN"] = strtotime($row[0]["yuukoukigen"] ."+1 day");
            $msg= "有効期限付き(".$row[0]["yuukoukigen"]." まで)<br>";
        }
        $plan=0;
    }else{
        //契約済
        $plan=1;
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
    $sqlstr="SELECT uid,JSON_VALUE(ToursLog,'$.new_releace_002') as ToursLog FROM Users WHERE uid=?";
    $stmt = $pdo_h->prepare($sqlstr);
    $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    //deb_echo($row[0]["ToursLog"]);
    if(empty($row[0]["ToursLog"])){
        //新機能リリース通知 未確認
        $bell_action="blink";
        $bell_size="fa-2x";
        $bell_msg="tap here！";
    }else{
        //新機能リリース通知 確認済み
        $bell_action="";
        $bell_size="fa-lg";
        $bell_msg="";
    }

}
?>


<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel='stylesheet' href='css/style_menu.css?<?php echo $time; ?>' >
    <TITLE><?php echo $title;?></TITLE>
</head>

<script>
</script>

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
    <div class='<?php echo $bell_action;?> logoff-color' style='position:fixed;top:35px;right:5px;'>
        <a href="#" style='color:var(--user-disp-color);' onclick='new_releace_start()'>
            <?php echo $bell_msg; ?><i class="fa-regular fa-bell <?php echo $bell_size;?> logoff-color"></i>
        </a>
    </div>
</header>

<body class='common_body' >
    <div style='position:fixed;top:70px;right:0;' class='rainbow-color'><b><a href='menu.php?action=color_change&color=<?php echo $color_No; ?>'>COLOR<i class='fa-solid fa-rotate-right fa-lg rainbow-color'></i></a></b></div>
    <div class='container-fluid' style='padding-top:15px;'>

<?php
    deb_echo(ROOT_URL);
    deb_echo(EXEC_MODE."：uid_".$_SESSION["user_id"]);

    if($logoff){
        if($_GET["ForcedLogout"]==true){
            echo "システム更新時に追加した機能を有効にするため、強制ログアウトしました。<br>お手数ですが、再度ログインしてご利用ください。<br><br>";
        }else{
            echo "ログオフしました。<br>";
        }
        echo "<a href='index.php'>再ログインする</a>";
        echo "</body>\n";
        ?>
        <script>
            //
            window.navigator.serviceWorker.getRegistrations()
            .then(registrations => {
                for(let registration of registrations) {
                    registration.unregister();
                    console.log('Service Worker is delete');
                }
            });
            //window.location.reload(true); 
        </script>
        <?php
        
        exit();
    }
    echo $msg;

    if(EXEC_MODE=="Trial"){
        echo "有効期限を過ぎると初期状態に戻りますので、ご自由に操作して下さい。<br>";
    }

    $array = [
        'レジ'=>['EVregi.php?mode=evrez','rez']
        ,'個別売上'=>['EVregi.php?mode=kobetu','k_rez']
        ,'商品登録'=>['shouhinMSedit.php?csrf_token='.$token,'s_tou']
        ,'商品一覧'=>['shouhinMSList.php?csrf_token='.$token,'s_itiran']
        ,'商品ｶﾃｺﾞﾘｰ'=>['shouhinMSCategoryEdit.php?csrf_token='.$token,'s_itiran']
        ,'出品在庫登録'=>['EVregi.php?mode=shuppin_zaiko','z_rez']
        ,'売上実績'=>['UriageData_Correct.php?mode=select&first=first&Type=rireki&diplay=where&csrf_token='.$token,'uri']
        ,'売上分析'=>['analysis_menu.php?csrf_token='.$token,'bunseki']
        ,'ユーザ情報'=>['account_create.php?mode=1&csrf_token='.$token,'user']
        ,'会計連携'=>['output_menu.php?csrf_token='.$token,'kaikei']
        ,'紹介者ID'=>['shoukai.php?csrf_token='.$token,'shoukai']
        //,'ヘルプ'=>['help_menu.php']
    ];
    
    //契約・解約関連は各時で実装したファイルを指定する
    $root_url = bin2hex(openssl_encrypt(ROOT_URL, 'AES-128-ECB', null));
    $dir_path = bin2hex(openssl_encrypt(dirname(__FILE__)."/", 'AES-128-ECB', null));
    
    if(EXEC_MODE!="Trial"){
        if($plan==0){
            $array2 = ["本契約"=>[PAY_CONTRACT_URL."?system=".$title."&sysurl=".$root_url."&dirpath=".$dir_path,'keiyaku']];
        }else{
            $array2 = ['契約解除へ'=>['sub_cancel.php','kaijo']];
        }
    }else{
        $array2=array();
    }
    $i=0;
    echo "<div class='row'>";
	foreach(array_merge($array,$array2) as $key=>$vals){
        //echo "  <div class ='col-md-3 col-sm-6 col-6 menu menu_".$i."' style='padding:5px;'>\n";
        echo "  <div class ='col-md-3 col-sm-6 col-6 menu menu_".$vals[1]."' style='padding:5px;'>\n";
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
</body>
<footer class='common_footer' style='padding:5px;'>
    <p style='padding:5px;'>お問い合わせはコチラ</p>
    <a href='https://lin.ee/HLSLl23' style='padding:5px;'><i class="fa-brands fa-line fa-2x line-green"></i></a>
    <a href='https://green-island.mixh.jp/wdps/%e3%81%8a%e5%95%8f%e3%81%84%e5%90%88%e3%82%8f%e3%81%9b/' style='padding:5px;' target='_blank' rel='noopener noreferrer'><i class="fa-solid fa-square-envelope fa-2x"></i></a>
</footer>
<!--シェパードナビshepherd
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<?php
    if(empty($_SESSION["tour"])){
        //ツアー中でない場合、チュートリアルが終わっているか確認する
        //$sqlstr="SELECT * FROM Users WHERE JSON_CONTAINS(ToursLog, '\"finish\"', '$.tutorial') and uid=?";
        $sqlstr="SELECT uid,JSON_VALUE(ToursLog,'$.tutorial') as tutorial FROM Users WHERE uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(empty($row[0]["tutorial"])){
            //チュートリアル未実施
            $_SESSION["tour"]="tutorial_1";
        }elseif($row[0]["tutorial"]=="finish"){
            //チュートリアル完了
            
        }else{
            //チュートリアル実施中（再開）
            $_SESSION["tour"]=$row[0]["tutorial"];
        }
    }
    
?>
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php 
    require "ajax_func_tourFinish.php";
    $hello=(EXEC_MODE=="Trial"?"WebRez+に興味を持って頂きありがとうございます。":"ご登録ありがとうございます。");
?>
<script>
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
              <br>これから簡単にWEBREZの使い方(チュートリアル)を説明します。
              <br>10～20分ほどの操作になります。
              <br>
              <br>時間的に後にしたい方は「後で見る」タップして下さい。
              <br>「後で見る」をタップすると、次回トップ画面にアクセスした際に再度チュートリアルが開始されます。</p>`,
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
                <br>所々に<a href="#" ><i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i></a>マークがあり、タップするとヘルプが表示されます。
                <br>
                <br>「登録/削除」ボタンをタップしない限り、売上等の登録済みのデータが変更される事ありません。
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
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>
                チュートリアルを進めていくと途中で
                <br><br><span style='color:green;'>※進捗を保存しました。</span>
                <br>と表示されます。
                <br>以降は「ｘ」で終了しても、次回ログイン時にチュートリアルの続きから開始されます。
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
              <br>１．レジに表示する商品の登録
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

    if(TourMilestone=="tutorial_1"){
        tutorial_1.start(tourFinish,'tutorial','');
    }else　if(TourMilestone=="tutorial_3"){
        tutorial_4.start(tourFinish,'tutorial','');    
    }else　if(TourMilestone=="tutorial_7"){
        tutorial_8.start(tourFinish,'tutorial','');    
    }else　if(TourMilestone=="tutorial_10"){
        tutorial_11.start(tourFinish,'tutorial','');    
    }
    
</script>
<!--チュートリアル以外のヘルプ・出品在庫-->
<script>
    const shuppin_zaiko_help1 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'shuppin_zaiko_help1'
    });
    /*
    shuppin_zaiko_help1.addStep({
        title: `<p class='tour_header'>出品在庫機能</p>`,
        text: `<p class='tour_discription'>出品在庫機能をリリースしました。</p>`,
        buttons: [
            {
                text: 'Next',
                action: shuppin_zaiko_help1.nextAndSave
            }
        ],
        cancelIcon:{
            enabled:false
        }
    });
    */
    shuppin_zaiko_help1.addStep({
        title: `<p class='tour_header'>出品在庫機能</p>`,
        text: `<p class='tour_discription'>各イベントの出品数を登録できます。
                <br>出品数を登録する事で、完売したのか、何が売れ残ったのかを把握できます。
                <br>また、イベント終了時の在庫確認・レジ打ち漏れの確認も簡単になります。</p>`,
       attachTo: {
            element: '.menu_z_rez',
            on: 'auto'
        },
        cancelIcon:{
            enabled:false
        }
    });
    function shuppin_zaiko_help_start(){
        //start(ajax関数名(固定値),ツアー名称(チュートリアル等),ステータス(finish;完了,save;保存(次回途中から始まる),'空白：$_SESSION["tour"]にnewで指定しtourNameをセット)'
        shuppin_zaiko_help1.start(tourFinish,'',''); 
    }
</script>
<script>
    const new_releace_002 = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: true,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'new_releace_002'
    });
    new_releace_002.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'>売上時の天気、気温を記録できるようになりました。
            <br>
            <br>この機能により、将来的に売上分析の指標として、天気・気温と売上の相関関係を提供できるようになります。</p>`,
        buttons: [
            {
                text: 'Next',
                action: new_releace_002.nextAndSave
            }
        ],
        cancelIcon:{
            enabled:false
        }
    });
    new_releace_002.addStep({
        title: `<p class='tour_header'>新規機能追加のお知らせ</p>`,
        text: `<p class='tour_discription'>端末のGPS機能を使用します。
                <br>レジ画面を開く際に「位置情報を・・・」と聞かれますので「有効」にしてください。
                <br>
                <br>それでは「レジ」をタップしてください。</p>`,
       attachTo: {
            element: '.menu_rez',
            on: 'auto'
        },
        cancelIcon:{
            enabled:false
        }
    });
    function new_releace_start(){
        //新機能のリリース通知はこの関数で呼び出すツアーを更新する
        //shuppin_zaiko_help1.start(tourFinish,'new_releace_001','');
        new_releace_002.start(tourFinish,'new_releace_002',''); 
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
</script>
</html>
<?php
    $pdo_h=null;
?>
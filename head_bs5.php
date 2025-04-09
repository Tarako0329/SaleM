<!-- headタグの共通部分 -->

    <meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1'>
    <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <!--<link rel='apple-touch-icon' href='apple-touch-icon.png'>-->

    <meta name="msapplication-square70x70logo" content="img/site-tile-70x70.png">
    <meta name="msapplication-square150x150logo" content="img/site-tile-150x150.png">
    <meta name="msapplication-wide310x150logo" content="img/site-tile-310x150.png">
    <meta name="msapplication-square310x310logo" content="img/site-tile-310x310.png">
    <meta name="msapplication-TileColor" content="#0078d7">
    <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="img/favicon.ico">
    <link rel="icon" type="image/vnd.microsoft.icon" href="img/favicon.ico">
    <link rel="apple-touch-icon" sizes="57x57" href="img/apple-touch-icon-57x57.png">
    <link rel="apple-touch-icon" sizes="60x60" href="img/apple-touch-icon-60x60.png">
    <link rel="apple-touch-icon" sizes="72x72" href="img/apple-touch-icon-72x72.png">
    <link rel="apple-touch-icon" sizes="76x76" href="img/apple-touch-icon-76x76.png">
    <link rel="apple-touch-icon" sizes="114x114" href="img/apple-touch-icon-114x114.png">
    <link rel="apple-touch-icon" sizes="120x120" href="img/apple-touch-icon-120x120.png">
    <link rel="apple-touch-icon" sizes="144x144" href="img/apple-touch-icon-144x144.png">
    <link rel="apple-touch-icon" sizes="152x152" href="img/apple-touch-icon-152x152.png">
    <link rel="apple-touch-icon" sizes="180x180" href="img/apple-touch-icon-180x180.png">
    <link rel="icon" type="image/png" sizes="36x36" href="img/android-chrome-36x36.png">
    <link rel="icon" type="image/png" sizes="48x48" href="img/android-chrome-48x48.png">
    <link rel="icon" type="image/png" sizes="72x72" href="img/android-chrome-72x72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="img/android-chrome-96x96.png">
    <link rel="icon" type="image/png" sizes="128x128" href="img/android-chrome-128x128.png">
    <link rel="icon" type="image/png" sizes="144x144" href="img/android-chrome-144x144.png">
    <link rel="icon" type="image/png" sizes="152x152" href="img/android-chrome-152x152.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/android-chrome-192x192.png">
    <link rel="icon" type="image/png" sizes="256x256" href="img/android-chrome-256x256.png">
    <link rel="icon" type="image/png" sizes="384x384" href="img/android-chrome-384x384.png">
    <link rel="icon" type="image/png" sizes="512x512" href="img/android-chrome-512x512.png">
    <link rel="icon" type="image/png" sizes="36x36" href="img/icon-36x36.png">
    <link rel="icon" type="image/png" sizes="48x48" href="img/icon-48x48.png">
    <link rel="icon" type="image/png" sizes="72x72" href="img/icon-72x72.png">
    <link rel="icon" type="image/png" sizes="96x96" href="img/icon-96x96.png">
    <link rel="icon" type="image/png" sizes="128x128" href="img/icon-128x128.png">
    <link rel="icon" type="image/png" sizes="144x144" href="img/icon-144x144.png">
    <link rel="icon" type="image/png" sizes="152x152" href="img/icon-152x152.png">
    <link rel="icon" type="image/png" sizes="160x160" href="img/icon-160x160.png">
    <link rel="icon" type="image/png" sizes="192x192" href="img/icon-192x192.png">
    <link rel="icon" type="image/png" sizes="196x196" href="img/icon-196x196.png">
    <link rel="icon" type="image/png" sizes="256x256" href="img/icon-256x256.png">
    <link rel="icon" type="image/png" sizes="384x384" href="img/icon-384x384.png">
    <link rel="icon" type="image/png" sizes="512x512" href="img/icon-512x512.png">
    <link rel="icon" type="image/png" sizes="16x16" href="img/icon-16x16.png">
    <link rel="icon" type="image/png" sizes="24x24" href="img/icon-24x24.png">
    <link rel="icon" type="image/png" sizes="32x32" href="img/icon-32x32.png">

    <!-- Bootstrap5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <!-- Bootstrap Javascript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <!-- fontawesome -->
    <link href="css/FontAwesome/6.1.1-web/css/all.css" rel="stylesheet">
    <!-- オリジナル CSS -->
    <!--サイト共通-->
    <link rel='stylesheet' href='css/style.css?<?php echo $time; ?>' >
    <link id='style_color' rel='stylesheet' href='<?php echo empty($_SESSION["ColorCSS"])?"":$_SESSION["ColorCSS"] ;?>' >

    <!--Vue.js-->
    <!--<script src="https://unpkg.com/vue@next"></script>-->
    <script src="https://cdn.jsdelivr.net/npm/vue@3.4.4"></script>
    <script src="https://unpkg.com/vue-cookies@1.8.2/vue-cookies.js"></script>
    <!--ajaxライブラリ-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script>axios.defaults.baseURL = <?php echo "'".ROOT_URL."'" ?>;</script>
	
	<script src="https://cdnjs.cloudflare.com/ajax/libs/decimal.js/9.0.0/decimal.min.js"></script><!--小数演算ライブラリ-->

    <?php if(EXEC_MODE==="Trial"){?>
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-JR0V5BW6PW"></script>
        <script>
          window.dataLayer = window.dataLayer || [];
          function gtag(){dataLayer.push(arguments);}
          gtag('js', new Date());
        
          gtag('config', 'G-JR0V5BW6PW');
        </script>
    <?php } ?>

    <script>//グローバル変数
        var KANKYO = <?php echo "'".EXEC_MODE."'" ;?>;
        var ZEIHASU = <?php echo empty($ZeiHasu)?0:$ZeiHasu ;?>;
        var WEATHER_ID = '<?php echo WEATHER_ID; ?>'
        var COLOR_NO 

        var D_ROOT_URL = '<?php echo ROOT_URL; ?>'  //サブドメインURL
        var P_ROOT_URL   //ダイレクトパスURL
        if (KANKYO==="Local"){
            P_ROOT_URL = '<?php echo ROOT_URL; ?>' 
        }else if (KANKYO==="Test"){
            P_ROOT_URL = 'https://greeen-sys.com/SaleM/TEST/' 
        }else if (KANKYO==="Trial"){
            P_ROOT_URL = 'https://greeen-sys.com/SaleM/WebRez_Trial/' 
        }else if (KANKYO==="Product"){
            P_ROOT_URL = 'https://greeen-sys.com/SaleM/WebRez/' 
        }
        const ZEIM = [//税区分マスタ
					{税区分:0,税区分名:'非課税',税率:0},
					{税区分:1001,税区分名:'8%',税率:0.08},
					{税区分:1101,税区分名:'10%',税率:0.1},
				]

        const BUNSEKI_MENU = [
            {sort:10,val:1,  name:"売上実績(日計)"},
            {sort:20,val:2,  name:"売上実績(月計)"},
            {sort:30,val:3,  name:"売上実績(年計)"},
            {sort:40,val:12, name:"ジャンル別売上比"},
            {sort:50,val:4,  name:"売上ランキング(金額)"},
            {sort:60,val:5,  name:"売上ランキング(個数)"},
            {sort:65,val:'Ev_Avr_uri_rank',  name:"ｲﾍﾞﾝﾄ別平均総売上ﾗﾝｷﾝｸﾞ"},
            {sort:70,val:6,  name:"客単価実績(ｲﾍﾞﾝﾄ開催ごと)"},
            {sort:80,val:7,  name:"ｲﾍﾞﾝﾄ別客単価ﾗﾝｷﾝｸﾞ"},
            {sort:85,val:'Area_tanka_1',  name:"エリア(市区)別客単価RANK"},
            {sort:86,val:'Area_tanka_2',  name:"エリア(市区町)別客単価RANK"},
            {sort:90,val:8,  name:"来客数実績(ｲﾍﾞﾝﾄ開催ごと)"},
            {sort:100,val:9, name:"ｲﾍﾞﾝﾄ別平均来客数ﾗﾝｷﾝｸﾞ"},
            {sort:110,val:10,name:"売れる勢い"},
            {sort:120,val:11,name:"来客数推移"},
            {sort:130,val:'urikire',name:"売切分析"},
        ]
    </script>
    <script src="script/function.js?<?php echo $time; ?>"></script>
    <script src="script/indexeddb.js?<?php echo $time; ?>"></script>


    <link rel='manifest' href='manifest.webmanifest'>
    <script>/*serviceWorker*/
        /*
        if('serviceWorker' in navigator){
        	navigator.serviceWorker.register('serviceworker.js').then(function(){
        		console_log("Service Worker is registered!!");
        	});
        }
        */
        
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('serviceworker.js')
                .then(registration => {
                    // 登録成功
                    console_log("Service Worker is registered!!");
                    
                    //serviceworker.js　の更新確認(bit単位で比較し相違があったら更新する。らしい)
                    /*
                    registration.onupdatefound = function() {
                        console_log('Service Worker is Updated');
                        registration.update();
                    }
                    */
                })
                .catch(err => {
                    // 登録失敗
                    console_log("Service Worker is Oops!!");
            });
        }

        if(window.matchMedia('(display-mode: standalone)').matches){
            // ここにPWA環境下でのみ実行するコードを記述
        }
        //スマフォで:active :hover を有効に
        document.getElementsByTagName('html')[0].setAttribute('ontouchstart', '');
    </script>
    <script>/*indexedDb*/
        //配色のCSSをセット
        const set_color = (jsonobj) =>{
            console_log('set_color start')
            
            if(jsonobj===undefined){
                document.getElementById('style_color').href=`css/style_color_0.css?<?php echo $time; ?>`
                COLOR_NO = {id:'menu_color',No:'0'}
            }else{
                document.getElementById('style_color').href=`css/style_color_${jsonobj.No}.css?<?php echo $time; ?>`
                COLOR_NO = jsonobj
            }

            axios
			.get(`ajax_set_session_param.php?ColorCSS=css/style_color_${jsonobj.No}.css?<?php echo $time; ?>`)
			.then((response) => {
			})
			.catch((error)=>{
				//console_log(`ajax_set_session_param ERROR:${error}`)
			})

            console_log(COLOR_NO)
        }
        IDD_Read('LocalParameters','menu_color',set_color)
        window.addEventListener('DOMContentLoaded', () => {
            
        })
        
    </script>
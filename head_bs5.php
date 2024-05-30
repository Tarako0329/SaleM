<!-- headタグの共通部分 -->

    <meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1'>
    <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <link rel='apple-touch-icon' href='apple-touch-icon.png'>

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
	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.js"></script><!--QRコードライブラリ-->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/decimal.js/9.0.0/decimal.min.js"></script><!--小数演算ライブラリ-->

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
            {sort:1,val:1,  name:"売上実績(日計)"},
            {sort:2,val:2,  name:"売上実績(月計)"},
            {sort:3,val:3,  name:"売上実績(年計)"},
            {sort:4,val:12, name:"ジャンル別売上比"},
            {sort:5,val:4,  name:"売上ランキング(金額)"},
            {sort:6,val:5,  name:"売上ランキング(個数)"},
            {sort:7,val:6,  name:"客単価実績(イベントごと)"},
            {sort:8,val:7,  name:"平均客単価ランキング"},
            {sort:9,val:8,  name:"来客数実績(イベントごと)"},
            {sort:10,val:9, name:"平均来客数ランキング"},
            {sort:11,val:10,name:"売れる勢い"},
            {sort:12,val:11,name:"来客数推移"},
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
<!-- headタグの共通部分 -->

    <meta name='viewport' content='width=device-width, initial-scale=1, maximum-scale=1'>
    <META http-equiv='Content-Type' content='text/html; charset=UTF-8'>
    <link rel='apple-touch-icon' href='apple-touch-icon.png'>

    <!-- Bootstrap5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">
    <!-- Bootstrap Javascript -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js" integrity="sha384-geWF76RCwLtnZ8qwWowPQNguL3RmwHVBC9FhGdlKrxdiJJigb/j/68SIy3Te4Bkz" crossorigin="anonymous"></script>
    <!-- fontawesome -->
    <link href="css/FontAwesome/6.1.1-web/css/all.css" rel="stylesheet">
    <!-- オリジナル CSS -->
    <!--サイト共通-->
    <link rel='stylesheet' href='css/style.css?<?php echo $time; ?>' >
    <link rel='stylesheet' href='css/style_color_<?php echo $color_No;?>.css?<?php echo $time; ?>' >

    <!--Vue.js-->
    <script src="https://unpkg.com/vue@next"></script>
    <script src="https://unpkg.com/vue-cookies@1.8.2/vue-cookies.js"></script>
    <!--ajaxライブラリ-->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.19.0/axios.min.js"></script>
    <script>axios.defaults.baseURL = <?php echo "'".ROOT_URL."'" ?>;</script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/qrious/4.0.2/qrious.js"></script><!--QRコードライブラリ-->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/decimal.js/9.0.0/decimal.min.js"></script><!--小数演算ライブラリ-->

    <script>
        var KANKYO = <?php echo "'".EXEC_MODE."'" ;?>;
        var ZEIHASU = <?php echo $ZeiHasu ;?>;
    </script>
    <script src="script/function.js"></script>


    <link rel='manifest' href='manifest.webmanifest'>
    <script>
        /*
        if('serviceWorker' in navigator){
        	navigator.serviceWorker.register('serviceworker.js').then(function(){
        		console.log("Service Worker is registered!!");
        	});
        }
        */
        
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('serviceworker.js')
                .then(registration => {
                    // 登録成功
                    console.log("Service Worker is registered!!");
                    
                    //serviceworker.js　の更新確認(bit単位で比較し相違があったら更新する。らしい)
                    registration.onupdatefound = function() {
                        console.log('Service Worker is Updated');
                        registration.update();
                    }
                })
                .catch(err => {
                    // 登録失敗
                    console.log("Service Worker is Oops!!");
            });
        }

        if(window.matchMedia('(display-mode: standalone)').matches){
            // ここにPWA環境下でのみ実行するコードを記述
        }
        //スマフォで:active :hover を有効に
        document.getElementsByTagName('html')[0].setAttribute('ontouchstart', '');
    </script>
<?php
/*関数メモ
check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

【想定して無いページからの遷移チェック】
csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

遷移先のチェック
*/
require "php_header.php";

$rtn = csrf_checker(["menu.php","analysis_uriagejisseki.php","analysis_abc.php"],["G","C","S"]);
if($rtn !== true){
    redirect_to_login($rtn);
}

$rtn=check_session_userid($pdo_h);
$token = csrf_create();

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
    <style>
        .tooltip-inner {
            /* 長いコンテンツのツールチップが適切に表示されるようにします */
            max-width: 400px;
        }
    </style>
    <TITLE><?php echo $title;?></TITLE>
</head>

<body class=''>
    <header class="header-color common_header" style='display:block'>
        <div class="yagou title">
            <a href="menu.php"><?php echo $title;?></a>
        </div>
        <div style='color:var(--user-disp-color);font-weight:400;'>データ分析メニュー</div>
    </header>
    <main class="common_body">
        <div class="container" id='app'>
            <div class='row'>
            <template v-for='(list,index) in menu' :key='index'>
                <div class ='col-md-4 col-sm-6 col-12 mb-3' >
                    <a :href='`${list.url}${tokenValue}`' class='btn--topmenu btn-view' style='font-size:1.5rem;width:80%;height:50px;padding:12px 10px;'>{{list.name}}</a>
                    <i class='bi bi-question-circle Qicon awesome-color-panel-border-same ms-2'
                       data-bs-placement='top' data-bs-trigger='click' role='button'
                       data-bs-custom-class='custom-tooltip' data-bs-toggle='tooltip' data-bs-html='true' :title='`${list.tips}`'
                       @click="toggleFlip(list)" :class="{ 'is-flipped': list.isFlipped }"></i>
                </div>
            </template>
            </div>
        </div>
    </main>
    <script>
        const { createApp, ref, onMounted, computed, VueCookies, watch, watchEffect } = Vue
		createApp({
            setup(){
                // 各メニューアイテムに isFlipped 状態を追加して初期化します
                const menu = ref(BUNSEKI_MENU.map(item => ({ ...item, isFlipped: false })));
                const tokenValue = ref('<?php echo $token;?>'); // PHPのトークンをリアクティブな変数に格納

                const toggleFlip = (item) => {
                    item.isFlipped = !item.isFlipped; // アイテムのisFlipped状態を反転させます
                    // オプション: 短時間で元に戻す場合
                    // setTimeout(() => {
                    //   item.isFlipped = false;
                    // }, 800); // 0.8秒後に元に戻ります
                };

                onMounted(() => {
                    const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]')
                    const tooltipList = [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl))
                });
                return{
                    menu,
                    tokenValue, // tokenValue をテンプレートで使用できるように返します
                    toggleFlip  // toggleFlip メソッドをテンプレートで使用できるように返します
                }
            }
        }).mount('#app');
    </script>

</body>

</html>
<?php
    $pdo_h=null;
?>
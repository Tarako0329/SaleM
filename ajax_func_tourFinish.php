<script>
    var tourFinish = function (tourName,step,status){
        //ツアーガイドが終わったらユーザマスタに記録。途中の場合はセッション変数に記録する
        $.ajax({
            // 通信先ファイル名
            type        : 'POST',
            url         : 'ajax_tour_log.php',
            data        :{
                            user_id     :'<?php echo $_SESSION["user_id"];?>',
                            tourName    :tourName,
                            step        :step,
                            status      :status//ツアー完了："finish"　途中："save" ブランク：$_SESSION["tour"]にstepを代入
                        }
            },
        ).done(
            // 通信が成功した時
            function(data) {
                //console.log("通信成功");
            }
        ).fail(
            // 通信が失敗した時
            function(XMLHttpRequest, textStatus, errorThrown){
                alert("通信失敗");
                console.log("通信失敗2");
                console.log("XMLHttpRequest : " + XMLHttpRequest.status);
                console.log("textStatus     : " + textStatus);
                console.log("errorThrown    : " + errorThrown.message);
            }
        )
    };
</script>
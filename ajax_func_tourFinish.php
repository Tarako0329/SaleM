<script>
    var tourFinish = function (tourName,step,status){
        //ツアーガイドが終わったらユーザマスタに記録。途中の場合はセッション変数に記録する

        let params_value = {
                            user_id     :'<?php echo $_SESSION["user_id"];?>',
                            tourName    :tourName,
                            step        :step,
                            status      :status//ツアー完了："finish"　途中："save" ブランク：$_SESSION["tour"]にstepを代入
                        }
        let params = new URLSearchParams (params_value)
		axios
			.post('ajax_tour_log.php',params) //php側は15秒でタイムアウト
			.then((response) => {
				console.log(`tourFinish SUCCESS`)
			})
			.catch((error) => {
				console.log(`tourFinish ERROR:${error}`)
			})
			.finally(()=>{
			})
    };
</script>
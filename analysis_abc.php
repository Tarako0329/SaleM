<?php
    /*関数メモ
    check_session_userid：セッションのユーザIDが消えた場合、自動ログインがオフならログイン画面へ、オンなら自動ログインテーブルからユーザIDを取得

    【想定して無いページからの遷移チェック】
    csrf_create()：SESSIONとCOOKIEに同一トークンをセットし、同内容を返す。(POSTorGETで遷移先に渡す)
    　　　　　　　 headerでリダイレクトされた場合、COOKIEにセットされないので注意。

    */

    require "php_header.php";

    $rtn = csrf_checker(["analysis_menu.php","analysis_uriagejisseki.php","analysis_abc.php"],["G","C","S"]);
    if($rtn !== true){
        $rtn = csrf_checker(["analysis_menu.php","analysis_uriagejisseki.php","analysis_abc.php"],["P","C","S"]);
        if($rtn !== true){
            redirect_to_login($rtn);
        }
    }

    $rtn=check_session_userid($pdo_h);
    $csrf_create = csrf_create();

    if(!empty($_POST)){
        $ymfrom = $_POST["ymfrom"];
        $ymto = $_POST["ymto"];
        $list = $_POST["list"];
        $analysis_type=$_POST["sum_tani"];
    }else{
        $ymfrom = (int)((string)date('Y')."01");
        $ymto = (string)date('Y')."12";
        $list = "%";
        $analysis_type=$_GET["sum_tani"];
    }
    //get_getsumatsu($ymfrom);
    //deb_echo($list);
    /*
    $cols=0;
    if($analysis_type==1 ){//全商品（金額）
        $sqlstr = "select tmp.* ,sum(税抜売上) over() as 総売上 from (select ShouhinNM as ShouhinNM ,sum(UriageKin) as 税抜売上 from UriageData ";
        $gp_sqlstr = "group by ShouhinNM) tmp order by 税抜売上 desc";
        $aryColumn = ["商品名","税抜売上"];
        $cols=2;
    }elseif($analysis_type==2 ){//イベントごと
        $sqlstr = "select tmp.* ,sum(税抜売上) over(PARTITION BY Event) as 総売上 from (select Event,ShouhinNM as ShouhinNM ,sum(UriageKin) as 税抜売上 from UriageData ";
        $gp_sqlstr = "group by Event,ShouhinNM) tmp order by Event,税抜売上 desc";
        $aryColumn = ["商品名","税抜売上"];
        $cols=3;
    }
    $sqlstr = $sqlstr." where ShouhinCD<9900 and DATE_FORMAT(UriDate, '%Y%m') between :ymfrom and :ymto AND uid = :user_id ";
    $sqlstr = $sqlstr." and (Event like :event OR TokuisakiNM like :tokui )";
    $sqlstr = $sqlstr." ".$gp_sqlstr;

    //deb_echo($sqlstr);
    $_SESSION["Event"]      =(empty($_POST["list"])?"%":$_POST["list"]);
    
    $stmt = $pdo_h->prepare( $sqlstr );
    $stmt->bindValue("ymfrom", $ymfrom, PDO::PARAM_INT);
    $stmt->bindValue("ymto", $ymto, PDO::PARAM_INT);
    $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->bindValue("event", $list, PDO::PARAM_STR);
    $stmt->bindValue("tokui", $list, PDO::PARAM_STR);
    $rtn=$stmt->execute();
    if($rtn==false){
        deb_echo("失敗<br>");
    }
    $result=$stmt->fetchAll();
    */
    //検索年月リスト ユーザの最初の売上年月～今年12月までのリストを作成する
    $SLVsql = "select DATE_FORMAT(min(UriDate), '%Y-%m') as min_uridate from UriageData where uid = :user_id";
    $stmt = $pdo_h->prepare($SLVsql);
    $stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
    $stmt->execute();
    $SLVresult = $stmt->fetchAll();

    $next_ymd = date('Y-m-d',strtotime($SLVresult[0]["min_uridate"]."-01"));
    $next_ym = date('Ym',strtotime($next_ymd));
    for($i=0;$next_ym<=date("Y")."12";$i++){
        
        $SLVresult[$i]["display"] = date('Y年m月',strtotime($next_ymd));
        $SLVresult[$i]["fromValue"] = date('Y-m-d',strtotime($next_ymd));
        $SLVresult[$i]["toValue"] = date('Y-m-d',strtotime($next_ymd." last day of this month"));

        $next_ymd = date('Y-m-d',strtotime($next_ymd." +1 month"));
        $next_ym = date('Ym',strtotime($next_ymd));
        
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
    <link rel="stylesheet" href="css/style_analysis.css?<?php echo $time; ?>" >
    <TITLE><?php echo $title." 売上分析";?></TITLE>
</head>
<body class='common_body' style='padding-top:55px'>
    <div id='app'> 
    <header class="header-color common_header" style="flex-wrap:wrap;height:50px">
        <div class="title" style="width: 100%;">
            <a :href="url"><?php echo $title;?></a>
        </div>
    </header>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-3" style='padding:5px;background:white'>
                <form class="form" method="post" action="analysis_abc.php" style='font-size:1.5rem'>
                    <input type='hidden' name='csrf_token' value='CSRF'>
                    集計期間:
                    <select v-model='date_from' name='ymfrom' class="form-select form-select-lg" style="padding:0;width:11rem;display:inline-block;margin:5px">
                        <template v-for='(list,index) in ym_list' :key='list.Value'>
                            <option :value='list.fromValue'>{{list.display}}</option>
                        </template>
                    </select>
                    から
                    <select v-model='date_to' name='ymto' class="form-select form-select-lg" style="padding:0;width:11rem;display:inline-block;margin:5px">
                        <template v-for='(list,index) in ym_list' :key='list.Value'>
                            <option :value='list.toValue'>{{list.display}}</option>
                        </template>
                    </select>

                    <select v-model='analysis_type' name='sum_tani' class="form-select form-select-lg" style="padding:0;width:auto;max-width:100%;display:inline-block;margin:5px" ><!--集計単位-->
                        <option value='13'>商品別ABC分析</option>
                        <option value='14'>イベント・店舗/商品別ABC分析</option>
                    </select>
                    <select v-model='ev_selected' name='list' class="form-select form-select-lg" style="padding:0;width:auto;max-width:100%;display:inline-block;margin:5px" >
                        <template v-for='(list,index) in ev_list' :key='list.Value'>
                            <option :value='list.CODE'>{{list.LIST}}</option>
                        </template>
                    </select>
                </form>
            </div>
            <div class="col-md-9" style='padding:5px'>
                <table class='table-striped table-bordered result_table item_0 tour_uri1' style='margin-top:10px;margin-bottom:20px;'><!--white-space:nowrap;-->
                    <tr><td colspan='4' align='center' style='padding-top:5px;font-size:32px;font-weight:700;'>{{first_event}}</td></tr>
			    		<tr>
                            <template v-for='(list,index) in table_labels' :key='list'>
                                <th scope='col'style='width:auto;'>{{list}}</th>
                            </template>
                        </tr>
                    <tbody>
                        <template v-for='(row,index) in table_data' :key='row.Labels'>
                            <template v-if='(index!==0) && row.Event !== table_data[index - 1].Event'>
                                <tr><td colspan='4' align='center' style='padding-top:5px;font-size:32px;font-weight:700;'>{{table_data[index].Event}}</td></tr>
			    	            <tr>
                                    <template v-for='(list,index) in table_labels' :key='list'>
                                        <th scope='col' style='width:auto;'>{{list}}</th>
                                    </template>
                                </tr>
                            </template>
                            <tr>
                            <td>{{row.Event}} : {{row.ShouhinNM}}</td>
                            <td align='right' >{{Number(row.税抜売上).toLocaleString()}}</td>
                            <td align='right' >{{Number(row.売上占有率).toLocaleString()}}</td>
                            <td align='center' >{{row.rank}}</td>
                            </tr>
                        </template>
                    </tbody>
                </table>

            </div>
        </div><!--row-->
    </div>
    </div><!--app-->
    <script>
        const { createApp, ref, onMounted, computed, VueCookies, watch, watchEffect } = Vue
		createApp({
			setup(){
                const ym_list = ref([<?php
                        $i=0;
                        foreach($SLVresult as $row){
                            if($i!==0){
                                echo ",";
                            }
                            echo "{display:'".$row["display"]."',fromValue:'".$row["fromValue"]."',toValue:'".$row["toValue"]."'}";
                            $i++;
                        }
                    ?>])
                const date_from = ref('<?php echo date("Y")."-01-01"; ?>')
                const date_to = ref('<?php echo date("Y")."-12-31"; ?>')
                const ev_list = ref([])
				const get_event = () => {//期間内のイベント一覧取得ajax
					console_log("get_event start",'lv3')
					let params = new URLSearchParams()
					params.append('user_id', '<?php echo $_SESSION["user_id"];?>')
					params.append('date_from', date_from.value)
					params.append('date_to', date_to.value)
					params.append('list_type', 'Event')
					axios
					.post('ajax_get_event_list.php',params)
					.then((response) => {
						console_log(response.data,'lv3')
						ev_list.value = [...response.data]
					})
					.catch((error) => {
						console_log(`get_event ERROR:${error}`,'lv3')
					})
					return 0;
				};//イベントリスト取得ajax
                watch([date_from,date_to],() => {
                    get_event()
                })
                const table_labels = ref([])
                const table_data = ref([])
                const analysis_type = ref(14)
                const ev_selected = ref('')
                const first_event = ref('')
                const CSRF = ref('<?php echo $csrf_create;?>')
                const get_analysis_data = () => {//売上分析データ取得ajax
					console_log("get_analysis_data start",'lv3')
					let params = new URLSearchParams()
					params.append('user_id', '<?php echo $_SESSION["user_id"];?>')
					params.append('date_from', date_from.value)
					params.append('date_to', date_to.value)
					params.append('analysis_type', analysis_type.value)
					params.append('event', ev_selected.value)
					params.append('tokui', ev_selected.value)
					params.append('csrf_token', CSRF.value)

					axios
					.post('ajax_get_analysi_uridata.php',params)
					.then((response) => {
						console_log(response.data,'lv3')
                        CSRF.value = response.data.csrf_create
                        table_labels.value = [...response.data.aryColumn]
                        table_data.value = [...response.data.result]
                        first_event.value = table_data.value[0].Event
					})
					.catch((error) => {
						console_log(`get_analysis_data ERROR:${error}`,'lv3')
					})
                    .finally(()=>{
                        //console_log(myChart,'lv3')
                    })
					return 0;
				};//売上分析データ取得ajax

                onMounted(() => {
                    get_event()
                    get_analysis_data()
                })
                const url = computed(() =>{
                    return 'analysis_menu.php?csrf_token=' + CSRF.value
                })
                watch([date_from,date_to,analysis_type,ev_selected],() => {
                    get_analysis_data()
                })
                return{
                    ym_list
                    ,date_from
                    ,date_to
                    ,ev_list
                    ,get_event
                    ,analysis_type
                    ,ev_selected
                    ,CSRF
                    ,url
                    ,table_labels
                    ,table_data
                    ,first_event
                }
            }
        }).mount('#app');
    </script>
</body>

</html>
<?php
$stmt = null;
$pdo_h = null;
?>



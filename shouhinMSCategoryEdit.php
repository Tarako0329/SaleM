<?php {
    require "php_header.php";

    if(isset($_GET["csrf_token"]) || empty($_POST)){
        if(csrf_chk_redirect($_GET["csrf_token"])==false){
            $_SESSION["EMSG"]="セッションが正しくありませんでした。";
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: index.php");
            exit();
        }
    }

    $csrf_create = csrf_create();

    $MSG = (!empty($_SESSION["MSG"])?$_SESSION["MSG"]:"");
    $_SESSION["MSG"]="";
}?>
<!DOCTYPE html>
<html lang="ja">
<head>
    <?php 
    //共通部分、bootstrap設定、フォントCND、ファビコン等
    include "head_bs5.html" 
    ?>
    <!--ページ専用CSS-->
    <link rel='stylesheet' href='css/style_ShouhinMSCategoryEdit.css?<?php echo $time; ?>' >
    <TITLE><?php echo $title." 取扱商品 確認・編集";?></TITLE>
</head>
<link rel="stylesheet" href="css/jQuery-UI-1.12.1.min.css">

<body class='common_body media_body'>
    <div  id='page'>
    <header class='header-color common_header' style='flex-wrap:wrap'>
        <div class='title' style='width: 100%;'><a href='menu.php'><?php echo $title;?></a></div>
        <p style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'>  取扱商品 カテゴリー一括修正 画面</p>
        <?php 
        if(empty($_SESSION["tour"])){
            echo "<a href='#' style='color:inherit;position:fixed;top:5px;right:5px;' onclick='help()'><i class='fa-regular fa-circle-question fa-lg logoff-color'></i></a>";
        }
        ?>
    </header>
    <main @click='sujest_OFF'>
        <!--<form method='post' action='shouhinMSCategoryEdit_sql.php' id='form'>-->
				<form method='post' id='form' @submit.prevent='on_submit'>
            <div class='header2'>
                <div style='display:flex;height:25px;margin:5px;'>
                    <select v-model="cate_lv" @change='get_categorys' class='form-select form-select-lg' id='categry' name='categry' style='width:100px;' required='required'>
                        <option value=''>項目選択</option>
                        <option value='cate1' selected>ｶﾃｺﾞﾘｰ１</option>
                        <option value='cate2' >ｶﾃｺﾞﾘｰ２</option>
                        <option value='cate3' >ｶﾃｺﾞﾘｰ３</option>
                    </select>
                    <select v-model="over_cate" @change='get_sujest_list' class='form-select form-select-lg' style='width:200px;margin-left:5px' >
                        <option disabled selected value='%'>上位分類を選択</option>
                        <template v-for='list in categorys'>
                            <option v-bind:value='list.LIST'>{{list.LIST}}</option>
                        </template>
                    </select>
                </div>
                <div style='display:block;margin:5px;'>
                    <input v-model='set_category' @focus='sujest_ON' type='text' name='upd_bunrui' required='required' placeholder='カテゴリー名を入力' 
                    class='form-control' style='max-width:305px;' id='input_category'>
                    <div v-show='sujestOnOff' style='background-color:antiquewhite;max-width:305px;'>
                    <template v-for='(sujest,index) in sujest_filter'>
                        <div class='class="form-check"'>
                        <input class="form-check-input" name='radiolists' type='radio' v-bind:value='sujest.LIST' v-bind:id="`radiolist${index}`" style='border:0;display:none;'>
                        <label class="form-check-label" v-bind:for="`radiolist${index}`" style='width:100%;padding-left:5px;'> {{sujest.LIST}}</label>
                        </div>
                    </template>
                    </div>
                </div>
            </div>
            <div class='container-fluid'>
                <template v-if='MSG!==""'>
                    <div v-bind:class='alert_status' role='alert'>{{MSG}}</div>
                </template>
								<input type='hidden' name='csrf_token' v-model='csrf'>
                <table class='table table-striped table-bordered item_1 MSLIST'>
                    <thead class='table-light'>
                        <tr style='height:30px;'>
                            <th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;'>レ</th>
                            <th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;' > ID:商品名</th>
                            <th class='th1' scope='col' style='width:auto;padding:0px 5px 0px 0px;' > カテゴリー(1>2>3)</th>
                        </tr>
                    </thead>
                    <tbody>
                    <template v-for='(list,index) in shouhinMS_filter' v-bind:key='list.shouhinCD'>
                        <tr>
                            <td><input type='checkbox' v-bind:name ="`ORDERS[${index}][chk]`" style='width:2rem;padding-left:10px;'></td><td>{{list.shouhinCD}}:{{list.shouhinNM}}</td>
                            <td style='padding-left:5px;'>{{list.category}}</td>
                            <input type='hidden' v-bind:name ="`ORDERS[${index}][shouhinCD]`" v-bind:value='list.shouhinCD'>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>
            <footer class='common_footer'>
                <button type='submit' class='btn--chk item_3' style='border-radius:0;' name='commit_btn' >登　録</button>
            </footer>
        </form>
    </main>
    </div>
<script>
    const { createApp, ref, onMounted, computed } = Vue;
    createApp({
        setup(){
						const cate_lv = ref('cate1')
            const over_cate = ref([])
            const categorys = ref([])
            const sujest_list = ref([])
            const set_category = ref('')
            const sujestOnOff = ref(false)
            const MSG = ref('')
						const csrf = ref('<?php echo $csrf_create; ?>')
						const alert_status = ref(['alert'])

            //商品マスタ全件を取得
            const shouhinMS = ref([])

            onMounted(() => {
                console.log('onMounted')
								get_shouhinMS()
                get_categorys()
                
            })

            const get_shouhinMS = () => {
                console.log("get_shouhinMS start");
                let params = new URLSearchParams();
                params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
                axios
                .post('ajax_get_ShouhinMS.php',params)
                .then((response) => (shouhinMS.value = [...response.data]
                                    ,console.log('get_shouhinMS succsess')
                                    ))
                .catch((error) => console.log(`get_shouhinMS ERROR:${error}`));
            }
            const shouhinMS_filter = computed(() => {
                let searchWord = over_cate.value.toString().trim();

								shouhinMS.value.sort((a,b) => {
									return (a.category > b.category?1:-1)
									return (a.shouhinNM > b.shouhinNM?1:-1)
									return 0
								})

                if (searchWord === "%") return shouhinMS.value;
                return shouhinMS.value.filter((shouhin) => {
                  return (
                    shouhin.category.includes(searchWord) 
                  );
                });
            })

            const get_categorys = () => {
                console.log(`get_categorys started :${cate_lv.value}`)
                if(cate_lv.value ==="cate1"){
                    over_cate.value = '%'
                    console.log('get_categorys(cate1) succsess')
                }else{
                    let params = new URLSearchParams();
                    params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
                    params.append('output', 'select');
                    params.append('list_type', cate_lv.value);
                    params.append('serch_word', '');
                    
                    axios
                    .post('ajax_get_MSCategory_list.php',params)
                    .then((response) => (categorys.value = [...response.data]
                                        ,over_cate.value = categorys.value[0].LIST
                                        ,console.log('get_categorys succsess')
                                        //,console.log(response.data))
                                        ))
                    .catch((error) => console.log(`get_categorys ERROR:${error}`));
                }
                
            }

            const get_sujest_list = () => {
                console.log("get_sujest_list start");
                let params = new URLSearchParams();
                params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
                params.append('output', 'suggest');
                params.append('list_type', cate_lv.value);
                params.append('serch_word', over_cate.value);
                axios
                .post('ajax_get_MSCategory_list.php',params)
                .then((response) => (sujest_list.value = [...response.data]
                                    ,console.log('get_sujest_list succsess')
                                    //,console.log(response.data)
                                    ))
                .catch((error) => console.log(`get_sujest_list ERROR:${error}`));
            }
            
            const sujest_filter = computed(() => {
                let searchWord = set_category.value.toString().trim();
                if (searchWord === "") return sujest_list.value;
                return sujest_list.value.filter((sujest) => {
                  return (
                    sujest.LIST.includes(searchWord) 
                  );
                });

            })
            
            const sujest_ON = () => {
                get_sujest_list()
                sujestOnOff.value = true
            }
            const sujest_OFF = (e) => {
                console.log(e.target.id);
                console.log(e.target.name);
                if(e.target.name === 'radiolists'){
                    set_category.value = e.target.value
                }
                if(e.target.id.toString() !== "input_category"){
                    console.log('サジェストオフ')
                    sujestOnOff.value = false
                }
            }

						const on_submit = (e) => {
							console.log('on_submit start')
							console.log(e.target)
							let form_data = new FormData(e.target)
							let params = new URLSearchParams (form_data)
							axios
								.post('ajax_shouhinMSCategoryEdit_sql.php',params)
								.then((response) => (console.log(`on_submit succsess`)
																		,console.log(response.data)
																		,MSG.value = response.data[0].EMSG
																		,csrf.value = response.data[0].csrf_create
																		,alert_status[1] = response.data[0].status
																		//,alert_status.value = ['alert', 'alert-danger']
																		))
								.catch((error) => console.log(`on_submit ERROR:${error}`))
						}
            return{
                over_cate,
                cate_lv,
                get_categorys,
                categorys,
                shouhinMS_filter,
                sujest_list,
                get_sujest_list,
                sujest_filter,
                set_category,
                sujestOnOff,
                sujest_ON,
                sujest_OFF,
                MSG,
								on_submit,
								csrf,
								alert_status,
            }
        }
    }).mount('#page');
    
</script><!--vue-->    
<script>
    // Enterキーが押された時にSubmitされるのを抑制する
    document.getElementById("page").onkeypress = (e) => {
        // form1に入力されたキーを取得
        const key = e.keyCode || e.charCode || 0;
        // 13はEnterキーのキーコード
        if (key == 13) {
            // アクションを行わない
            e.preventDefault();
        }
    }
</script>

</body>
<!--シェパードナビshepherd
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php require "ajax_func_tourFinish.php";?>
<script>
    const TourMilestone = '<?php echo $_SESSION["tour"];?>';
    const helpTour = new Shepherd.Tour({
        useModalOverlay: true,
        defaultStepOptions: {
            classes: 'tour_modal',
            scrollTo: false,
            cancelIcon:{
                enabled:true
            }
        },
        tourName:'helpTour'
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 登録した商品の「価格変更」やレジへの「表示/非表示」の切替はこの状態(縦画面表示)で行えます。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 画面を横にすると他の項目も表示され、修正可能な状態となります。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 画面を横にしてみてください。
                <br>PCの場合、ブラウザの幅を拡大縮小すると表示が切り替わります。
                <br>タブレットの場合は最初から全て表示されているかと思います。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'><i class='fa-regular fa-trash-can'></i>　マークをタップすると削除を確認する画面に移動します。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 右上のリストボックスをタップすると、レジ画面の表示対象チェックが入っているもの、いないもの、全件表示と切り替える事が可能です。
              </p>`,
        attachTo: {
            element: '.item_0',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'> 商品の並び順はここで変更できます。
                <br>三角マークは昇順・降順の切り替えに使います。
                </p>`,
        attachTo: {
            element: '.item_01',
            on: 'bottom'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>商品の価格を変更する際は「新価格」欄をタップして変更後の価格を入力して下さい。
              </p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>入力した「新価格」が「税込か税抜」かは、こちらで選択して下さい。
              </p>`,
        attachTo: {
            element: '.item_2',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>レジへの「表示/非表示」の切替は「レジ」行のチェック有無で切り替えます。
              </p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>その他の項目についても、コチラの画面で修正したい部分をタップして打ち変えることで修正が可能です。
              </p>`,
        attachTo: {
            element: '.item_1',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>修正が完了したら「登録」ボタンをタップすると、変更内容が登録されます。
              </p>`,
        attachTo: {
            element: '.item_3',
            on: 'auto'
        },
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'Next',
                action: helpTour.next
            }
        ]
    });
    helpTour.addStep({
        title: `<p class='tour_header'>チュートリアル</p>`,
        text: `<p class='tour_discription'>なお、こちらで商品の価格等を修正しても過去の売上が変更されることはありません。
              </p>`,
        buttons: [
            {
                text: 'Back',
                action: helpTour.back
            },
            {
                text: 'finish',
                action: helpTour.complete
            }
        ]
    });
    
    function help(){
        helpTour.start(tourFinish,'help','');
    }

</script><!--help-->
</html>
<?php
$stmt  = null;
$stmt2 = null;
$pdo_h = null;
?>
const { createApp, ref, onMounted, computed, VueCookies, watch,nextTick  } = Vue;
const REZ_APP = (p_uid,p_timeout,p_mode) => createApp({
	setup(){
		const zm = [//税区分マスタ
			/*
			<?php
				reset($zeimaster);
				foreach($zeimaster as $row2){
						echo "{税区分:".$row2["zeiKBN"].",税区分名:'".$row2["hyoujimei"]."',税率:".($row2["zeiritu"]/100)."},\n";
				}
			?> 
			*/
			{税区分:0,税区分名:'非課税',税率:0},
			{税区分:1001,税区分名:'8%',税率:0.08},
			{税区分:1101,税区分名:'10%',税率:0.1},
		]

		//スクロールスムース
		const get_scroll_target = ref('description')
		const scroller = (target_id) => {//ヘッダー部のメニュージャンルを選択時に対象のジャンルまでスクロールする機能
			console_log(`*****【 scroller start 】*****`) 
			let itemHeight
			const target_elem = document.querySelector(get_scroll_target.value)
			itemHeight = target_elem.getBoundingClientRect().top + window.pageYOffset - 220
			
			scrollTo(0, itemHeight);
			console_log(`*****【 scroller end 】*****`) 
		}

		//売上取得関連
		const UriageList = ref([])		//売上リスト
		const get_UriageList = () => {//売上リスト取得ajax
			console_log(`*****【 get_UriageList start 】*****`);
			let params = new URLSearchParams();
			//params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
			params.append('user_id', p_uid);

			axios
			.post('ajax_get_Uriage.php',params)
			.then((response) => {
				UriageList.value = response.data
				console_log('get_UriageList succsess')
			})
			.catch((error) => {
				console_log(`get_UriageList ERROR:${error}`)
			})
			.finally(()=>{
				console_log(`*****【 get_UriageList end 】*****`);
			});
			
		}//売上リスト取得ajax
		const total_uriage = computed(() =>{//売上リストの合計売上額
			let sum_uriage = 0
			UriageList.value.forEach((list) => {
				sum_uriage += Number(list.ZeikomiUriage)
			})
			return sum_uriage
		})//売上リストの合計売上額
		const Konyusha_su = computed(()=>{
			let ninzu = 0
			let UriNO = 0
			UriageList.value.forEach((list) => {
				if(list.UriageNO != UriNO){
					ninzu = Number(ninzu) + 1
					UriNO = list.UriageNO
				}
			})
			return ninzu
		})


		//商品マスタ取得関連
		const shouhinMS = ref([])			//商品マスタ
		const disp_category = ref(0)		//パネルの分類別表示設定変更用

		const shouhinMS_filter = computed(() => {//商品パネルのソート・フィルタ:表示対象のみを返す or 表示対象かつ注文数１以上を返す
			let order_panel = ([])
			if (chk_register_show.value === "chk"){//表示対象のみを返す(商品マスタの[レジ表示]chk)
				order_panel = shouhinMS.value.filter((shouhin) => {
					return (shouhin.hyoujiKBN1 && shouhin.hyoujiKBN1.includes('on') );
				});
			}else if(chk_register_show.value === "register"){//表示対象かつ注文数１以上を返す
				order_panel = shouhinMS.value.filter((shouhin) => {
					return (shouhin.hyoujiKBN1 && shouhin.hyoujiKBN1.includes('on') && shouhin.ordercounter > 0);
				});
			}

			//カテゴリーグループ,税込み額の追加
			order_panel.forEach((list)=> {
				if(disp_category.value===1){
					list['disp_category'] = list.category1
				}else if(disp_category.value===2){
					list['disp_category'] = list.category12
				}else if(disp_category.value===3){
					list['disp_category'] = list.category123
				}else {
					list['disp_category'] = ''
				}
				list['zeikomigaku']=Number(list.tanka) + Number(list.tanka_zei)
			})

			return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
				return (a.category > b.category?1:-1)
				return (a.shouhinNM > b.shouhinNM?1:-1)
				return 0
			})
		})//商品パネルのソート・フィルタ

		const category = ref('')
		const panel_changer = () => {//商品パネルのカテゴリー表示切替
			if(disp_category.value >= 4){
				disp_category.value=1
			}else{
				disp_category.value ++
			}
			IDD_Write('LocalParameters',[{id:'category',category:disp_category.value}])
		}//商品パネルのカテゴリー表示切替
		watch([disp_category],()=>{
			if(disp_category.value==1){
				category.value = '大'
			}else if(disp_category.value==2){
				category.value = '大>中'
			}else if(disp_category.value==3){
				category.value = '大>中>小'
			}else if(disp_category.value==4){
				category.value = 'OFF'
			}
		})
		const set_category = (jsonobj) =>{
			console_log('set_category start')
			console_log(jsonobj)
			if(jsonobj===undefined){
				disp_category.value=1
				return
			}
			//category = jsonobj
			//console_log(category)
			disp_category.value = Number(jsonobj.category)
	}


		//オーダー処理関連
		const pay = ref(0)		//会計税込金額
		const kaikei_zei = ref(0)		//会計消費税
		const Revised_pay = ref('')	//値引値増時に指定した金額
		let pay_bk	//値引値増処理時の処理前税込支払金額バックアップ用
		let kaikei_zei_bk	////値引値増処理時の処理前消費税額バックアップ用
		const chk_register_show = ref('chk')		//確認・登録ボタンの表示
		const hontai = ref([])	//注文明細を税区分単位に集計し、連想配列で保存(消費税額を本体額合計から算出するため)
		const auto_ajust = ref(true)	//税込単価の合計＝支払額になるように調整するか否かのフラグ
		const auto_ajust_change=()=>{
			console_log(auto_ajust.value)
			if(confirm("注文はクリアされます。よろしいですか？")){
				reset_order()
			}else{
				if(auto_ajust.value){
					auto_ajust.value=false
				}else{
					auto_ajust.value=true
				}
			}
		}
		
		const btn_changer = (args) => {	//確認ボタン・戻るボタンを押したとき
			console_log("*****【 btn_changer start 】*****")
			if(Number(pay.value)===0){
				alert('商品が選択されてません')
				return 
			}
			chk_register_show.value = args
			if(args==='register'){	//登録モード
				barcode_mode('close')
				pay_bk = pay.value
				kaikei_zei_bk = kaikei_zei.value
				let rtn = chk_csrf()	//token紛失のチェック
				document.getElementById('main_area').style.paddingTop = '140px'
				if(auto_ajust.value===true){
					console_log(`自動端数調整開始`)
					/*let zeiritu
					let zeikomi
					
					let chouseigo*/
					let zeikomisougaku = 0
					let utizei = 0
					let index = -1
					let rtn_val
					for(const row of hontai.value){
						index++
						rtn_val = []
						rtn_val = get_value(Number(row['本体額']) + Number(row['消費税']),Number(row['税率']),"IN")
						console_log("税率ごとの税込額から本体額を算出")
						console_log(rtn_val)
						console_log(`明細合計本体額：${row['本体額']}`)
						row['調整額'] = rtn_val[0].本体価格 - row['本体額']
						row['税調整額'] = rtn_val[0].消費税 - row['消費税']

						zeikomisougaku += Number(row['本体額']) + Number(row['調整額']) + Number(row['消費税']) + Number(row['税調整額'])
						utizei += Number(row['消費税']) + Number(row['税調整額'])
					}
					if(pay_bk !== zeikomisougaku){
						alert("調整失敗")
						console_log(`それでもだめなのか！：${Number(pay_bk) - Number(zeikomisougaku)}`)
					}
					kaikei_zei.value = utizei
				}
				order_panel_show("show")
			}else if(args==='chk'){				//戻る時は調整額を０にクリアする
				document.getElementById('main_area').style.paddingTop = '200px'
				for(const row of hontai.value){
					row['調整額'] = 0
					row['税調整額'] = 0
					//row['消費税'] = row['消費税bk']
				}
				pay.value = pay_bk
				kaikei_zei.value = kaikei_zei_bk
				Revised_pay.value =''
				order_panel_show("close")
			}
			console_log("*****【 btn_changer end 】*****")
		}

		const ZeiChange = ref('0')	//商品マスタの税率変更スイッチ（0：商品マスタ初期値 8:テイクアウト 8% 10:イートイン10%）
		watch([ZeiChange],() => {		//商品マスタの税率変更スイッチ
			console_log(`*****【 watch ZeiChange(${ZeiChange.value}) start 】*****`)
			if(ZeiChange.value==='0'){//商品マスタの状態に戻す
				shouhinMS.value.forEach((list) =>{
					list.tanka_zei = Number(list.bk_tanka_zei)
					list.zeikomigaku = Number(list.tanka) + Number(list.bk_tanka_zei)
					list.zeiritu = Number(list.bk_zeiritu)
					list.zeiKBN = list.bk_zeiKBN
					list.hyoujimei = list.bk_hyoujimei
				})
			}else if(ZeiChange.value==='8' ||ZeiChange.value==='10'){
				shouhinMS.value.forEach((list) =>{
					let values = get_value(Number(list.tanka),Number(ZeiChange.value)/100,'NOTIN')
					list.tanka_zei = values[0].消費税
					list.zeikomigaku = values[0].税込価格
					list.zeiritu = Number(ZeiChange.value)
					list.zeiKBN = ZeiChange.value==='8' ? 1001 : 1101
					list.hyoujimei = ZeiChange.value==='8' ? '8%' : '10%'
				})
			}else{
				
			}
			console_log(`*****【 watch ZeiChange(${ZeiChange.value}) end 】*****`)
		})//商品マスタの税率変更スイッチ

		const ordercounter = (e) => {//注文増減ボタン
			//メニューボタンタップ時はe.target,スキャン方式の場合はe=shouhinMSのindex
			console_log('start ordercounter()')
			//console_log(e.target)
			console_log(e)
			//console_log(shouhinMS_filter.value[e.target.value])

			if(chk_register_show.value==="register"){
				alert('『戻る』ボタンをタップしてから増減してください。')
				return 0
			}

			let index,index2
			if(e.target){
				e.target.disabled = true		//ボタン連打対応：処理が終わるまでボタンを無効にする
				index = e.target.value
				//shouhinMSのINDEXを取得
				index2 = shouhinMS.value.findIndex(list=>list.shouhinCD===shouhinMS_filter.value[index].shouhinCD)
				console_log(`index2:${index2}`)
				}else{
					index = e
					index2 = e
				}
			//let index = (e.target)?e.target.value:e


			//オーダーパネルの数増減
			//shouhinMS_filter.value[index].ordercounter = Number(shouhinMS_filter.value[index].ordercounter) + Number(1)
			shouhinMS.value[index2].ordercounter = Number(shouhinMS.value[index2].ordercounter) + Number(1)

			//オーダーリストの数増減
			//console_log(`${shouhinMS_filter.value[index].shouhinCD}:${shouhinMS_filter.value[index].hyoujimei}`)
			const order_list_index = order_list.value.findIndex(//同一商品・同一税率の注文があった場合、該当レコードのindexを取得。ない場合は[-1]を返す
				//item => item.CD == shouhinMS_filter.value[index].shouhinCD && item.ZEIKBN == shouhinMS_filter.value[index].zeiKBN
				item => item.CD == shouhinMS.value[index2].shouhinCD && item.ZEIKBN == shouhinMS.value[index2].zeiKBN
			)
			
			if(order_list_index === -1){
				order_list.value.unshift({
					/*
					CD:shouhinMS_filter.value[index].shouhinCD
					,NM:shouhinMS_filter.value[index].shouhinNM
					,SU:1
					,UTISU:shouhinMS_filter.value[index].utisu
					,TANKA:Number(shouhinMS_filter.value[index].tanka)
					,TANKA_ZEI:Number(shouhinMS_filter.value[index].tanka_zei)
					,ZEIKBN:shouhinMS_filter.value[index].zeiKBN
					,ZEIRITUNM:shouhinMS_filter.value[index].hyoujimei
					,ZEIRITU:Number(shouhinMS_filter.value[index].zeiritu) 
					,GENKA_TANKA:Number(shouhinMS_filter.value[index].genka_tanka)
					,order_panel_index:index
					*/
					CD:shouhinMS.value[index2].shouhinCD
					,NM:shouhinMS.value[index2].shouhinNM
					,SU:1
					,UTISU:shouhinMS.value[index2].utisu
					,TANKA:Number(shouhinMS.value[index2].tanka)
					,TANKA_ZEI:Number(shouhinMS.value[index2].tanka_zei)
					,ZEIKBN:shouhinMS.value[index2].zeiKBN
					,ZEIRITUNM:shouhinMS.value[index2].hyoujimei
					,ZEIRITU:Number(shouhinMS.value[index2].zeiritu) 
					,GENKA_TANKA:Number(shouhinMS.value[index2].genka_tanka)
					,order_panel_index:index
				});
			}else{
				order_list.value[order_list_index].SU = Number(order_list.value[order_list_index].SU) + Number(1)
			}
			calculation()	//消費税再計算
			if(e.target){e.target.disabled = false}//ボタン連打対応：処理が終わったらボタンを有効に戻す
			
			nextTick (() => {//DOMが更新された後に処理を行う
				resize()
			})
			return 0
		}

		const order_list_pm = (index,value) =>{
			//同一パネルでイートインとテイクアウトが混在した場合にパネルからのマイナスカウント操作が
			//煩雑になるため、マイナスはオーダーリストからのみとする
			//console_log(index)
			//オーダーリストの増減
			if(chk_register_show.value==="register"){
				alert('『戻る』ボタンをタップしてから増減してください。')
				return 
			}
			order_list.value[index].SU = Number(order_list.value[index].SU) + Number(value)
			if(Number(order_list.value[index].SU) < 0){
				order_list.value[index].SU = Number(0)
				return
			}
			//オーダーパネルの増減
			shouhinMS_filter.value[order_list.value[index].order_panel_index].ordercounter += Number(value)
			if(shouhinMS_filter.value[order_list.value[index].order_panel_index].ordercounter < 0){
				shouhinMS_filter.value[order_list.value[index].order_panel_index].ordercounter = Number(0)
			}
			calculation()	//消費税再計算
		}

		const order_list_change_tax = (index,e) => {
			console_log(`order_list_change_tax start [${index} ${e.target.value}]`)
			let zmrec = ([])
			zmrec = zm.filter((list)=>{
				return list.税区分 == e.target.value
			})

			const values = get_value(Number(order_list.value[index].TANKA),Number(zmrec[0]["税率"]),'NOTIN')
			
			order_list.value[index].TANKA_ZEI = values[0].消費税
			order_list.value[index].ZEIRITUNM = zmrec[0]["税区分名"]
			order_list.value[index].ZEIRITU = zmrec[0]["税率"]*100
			calculation()
		}

		const calculation = () =>{//税率ごとの本体額総合計から消費税を計算する(インボイス対応)・自動調整ONの場合はマスタの単価・税単価を合算する
			console_log("*****【calculation start】*****")
			let hontai_index
			hontai.value = []
			order_list.value.forEach((row)=>{
				console_log(row)
				console_log(`${row.NM} SU:${row.SU} 税区分:${row.ZEIKBN}`)
				hontai_index = hontai.value.findIndex(//同一商品・同一税率の注文があった場合、該当レコードのindexを取得。ない場合は[-1]を返す
					item => Number(item.税区分) === Number(row.ZEIKBN)
				)
				console_log(hontai_index)
				if(hontai_index===-1){
					console_log("push")
					hontai.value.push({
					'税区分':Number(row.ZEIKBN) 
					,'税区分名':row.ZEIRITUNM 
					,'税率':Number(row.ZEIRITU)/100
					,'本体額':Number(row.TANKA) * Number(row.SU)
					,'調整額':0
					,'消費税':Number(row.TANKA_ZEI) * Number(row.SU)
					,'税調整額':0
					})
					console_log(hontai.value)
				}else{
					console_log("add")
					hontai.value[hontai_index].本体額 = Number(hontai.value[hontai_index].本体額) + (Number(row.TANKA) * Number(row.SU))
					hontai.value[hontai_index].消費税 = Number(hontai.value[hontai_index].消費税) + (Number(row.TANKA_ZEI) * Number(row.SU))
				}
			})
			
			pay.value=Number(0)
			kaikei_zei.value=Number(0)
			if(auto_ajust.value!==true){
				let values
				for(const row of hontai.value){
					values = get_value(Number(row['本体額']) + Number(row['調整額']),(Number(row['税率'])),'NOTIN')
					row["消費税"] = values[0].消費税
					pay.value = Number(pay.value) + Number(row['本体額']) + Number(row['調整額']) + Number(row['消費税']) + Number(row['税調整額'])		//税込額
					kaikei_zei.value = Number(kaikei_zei.value) + Number(row['消費税']) + Number(row['税調整額']) 	//内消費税
					values = []
				}
			}else{
				hontai.value.forEach((row)=>{
					pay.value = Number(pay.value) + Number(row['本体額']) + Number(row['調整額']) + Number(row['消費税']) + Number(row['税調整額'])		//税込額
					kaikei_zei.value = Number(kaikei_zei.value) + Number(row['消費税']) + Number(row['税調整額']) 	//内消費税
				})
			}
			console_log("*****【calculation end】*****")
		}

		const CHOUSEI_TYPE = ref('sougaku')
		const par = ref(0)
		const keydown_waribiki = (e) => {//電卓ボタンの処理
			//console_log(e.target.innerHTML)
			if(e.target.innerHTML==="C"){
				par.value = 0
				Revised_pay.value=""
				return
			}
			if(CHOUSEI_TYPE.value==='sougaku'){
				Revised_pay.value = Number(Revised_pay.value.toString() + e.target.innerHTML.toString())
				return
			}
			
			par.value = Number(par.value.toString() + e.target.innerHTML.toString())
			console_log(par.value)
			if(CHOUSEI_TYPE.value==='zou' || CHOUSEI_TYPE.value==='gen'){
				if(CHOUSEI_TYPE.value==='zou'){
					Revised_pay.value = Number(pay.value) + Number(par.value)
				}else if(CHOUSEI_TYPE.value==='gen'){
					Revised_pay.value = Number(pay.value) - Number(par.value)
				}
				return
			}

			let motoseikyu = new Decimal(Number(pay.value))
			let wariai = new Decimal(Number(par.value))
			let zougen = motoseikyu.mul(wariai.div(100))
			console_log(zougen)
			if(CHOUSEI_TYPE.value==='paroff'){
				Revised_pay.value = Number(pay.value) - Number(zougen)
			}else if(CHOUSEI_TYPE.value==='paron'){
				Revised_pay.value = Number(pay.value) + Number(zougen)
			}else{
				par.value = Number(par.value.toString() + e.target.innerHTML.toString())
			}
			
		}

		watch([CHOUSEI_TYPE],() => {
			console_log('watch CHOUSEI_TYPE')
			Revised_pay.value = ''
			par.value=0
		})

		const Revised = () => {//総額からの値引き値増し処理
			//１.税込支払総額の税率ごとの割合を算出する（8%=>3割 ,10%=>7割 等)
			//２.税込値引額を１で出した割合に分割する（値引200円=> 8%分:60円, 10%分:140円)
			//３.２で算出した税率ごとの値引額を本体額と消費税額に分解する
			console_log("*****【 Revised start:値引き値増し処理 】*****")
			if(Revised_pay.value!=="" && Revised_pay.value !== pay.value){//Revised_pay.value:修正後税込金額
				let sagaku_zan = Revised_pay.value
				let Revised_pay_val = new Decimal(Revised_pay.value)
				let pay_val = new Decimal(pay.value)
				let index = -1
				let wariai,siharai,rtn_val,target_val //支払総額における税区分ごとの金額割合,税区分ごとの元支払額,get_valueの戻り値格納,割引後金額

				console_log(`支払総額 ￥${pay_val} を ￥${Revised_pay_val} に変更します`)
				for(const row of hontai.value){
					//税区分ごとに請求額の割合を算出し、調整額に掛ける
					index++
					rtn_val = []
					siharai = new Decimal(Number(row['本体額']) + Number(row['消費税']))
					wariai = siharai.div(pay_val)
					console_log(`適用前⇒ ${pay.value} 内税率:${row['税率']*100}%分 [税込:${siharai} 本体:${row['本体額']}(税:${row['消費税']})] 割合:${Math.round(wariai*100)}%`)

					target_val = Math.round((Revised_pay_val.mul(wariai)))
					rtn_val = get_value(target_val,Number(row['税率']),'IN') ;//console_log(rtn_val)

					row["調整額"] = (rtn_val[0].本体価格 - Number(row['本体額']))	//割引税抜本体
					row["税調整額"] = (rtn_val[0].消費税 - Number(row['消費税']))	//割引税抜本体
					
					console_log(`現在額:${Number(row['本体額'])+ Number(row["消費税"])} - 目標額:${Math.round(target_val)} = ${Number(row['本体額'])+ Number(row["消費税"]) - Math.round(target_val)}`)
					
					//調整額＝変更後税込額/税率-変更前本体額
					console_log(`調整額:${Number(row["調整額"])+Number(row['税調整額'])} = 本体(${row["調整額"]}) + 税(${(row['税調整額'])})`)
					sagaku_zan = sagaku_zan - (Number(row['本体額']) + Number(row['調整額']) + Number(row["消費税"]) + Number(row['税調整額']))
				}
				if(sagaku_zan !== 0){
					if(CHOUSEI_TYPE.value !== 'paroff' && CHOUSEI_TYPE.value !== 'paron'){
						alert(`消費税端数の関係で指定した額に変更できませんでした。差額(${sagaku_zan}円)`)
					}
				}
				pay.value = 0
				kaikei_zei.value = 0
				for(const row of hontai.value){
					pay.value += Number(row['本体額']) + Number(row['消費税']) + Number(row['調整額']) + Number(row['税調整額'])
					kaikei_zei.value += Number(row['消費税']) + Number(row['税調整額'])
				}
			}else{
				console_log("調整スキップ")
			}
			console_log("*****【 Revised end:値引き値増し処理 】*****")
		}
		
		const reset_order = () => {//オーダーリセット
			shouhinMS_filter.value.forEach((list)=>list.ordercounter=0)
			order_list.value = []
			pay.value = 0
			pay_bk = 0
			kaikei_zei.value = 0
			kaikei_zei_bk = 0
			hontai.value = []
			Revised_pay.value = ''
		}
		
		const total_area = ref()
		const resize = () =>{//fontsizeの調整
			//console_log(total_area.value.style["fontSize"])
			//console_log(total_area.value.style)

			let size = total_area.value.style["fontSize"].slice(0,-3)

			if(total_area.value.offsetHeight < total_area.value.scrollHeight){
				while(total_area.value.offsetHeight < total_area.value.scrollHeight){
					size = size - 0.1
					total_area.value.style = `font-size:${size}rem;`
					//console_log(`${total_area.value.offsetHeight}:${total_area.value.scrollHeight}(${size})`) 
				}
			}
		}

		const alert_status = ref(['alert'])
		const MSG = ref('')
		const loader = ref(false)
		const csrf = ref('') 
		const rtURL = ref('')

		const chk_csrf = () =>{
			console_log(`ajax_getset_token start`)
			if(csrf.value==null || csrf.value==''){
				axios
				.get('ajax_getset_token.php')
				.then((response) => {
					csrf.value = response.data
					console_log(response.data)
				})
				.catch((error)=>{
					console_log(`ajax_getset_token ERROR:${error}`)
				})
			}else{
				console_log(`ajax_getset_token OK:${csrf.value}`)
			}
			return 0
		} 
		const rg_mode = ref(p_mode)	//レジモード

		const on_submit = async(e) => {//登録・submit/
			console_log('on_submit start')
			loader.value = true
			rtn = await v_get_gio()	//住所再取得
			console_log('after v_get_gio?')

			let form_data = new FormData(e.target)
			let params = new URLSearchParams (form_data)

			let php_name = ''
			if(rg_mode.value==='shuppin_zaiko'){
				php_name = 'ajax_EVregi_zaiko_sql.php'
			}else{
				php_name = 'ajax_EVregi_sql.php'
			}
			await axios.post(php_name,params,{timeout:p_timeout }) //php側は15秒でタイムアウト
				.then((response) => {
					console_log(`on_submit SUCCESS`)
					//console_log(response.data)
					MSG.value = response.data.MSG
					alert_status.value[1]=response.data.status
					csrf.value = response.data.csrf_create
					rtURL.value = response.data.RyoushuURL
					if(response.data.status==='alert-success'){
						reset_order()
						order_panel_show("close")
						total_area.value.style["fontSize"]="3.3rem"
						chk_register_show.value = "chk"
						ZeiChange.value='0'
					}
				})
				.catch((error) => {
					console_log(`on_submit ERROR:${error}`)
					MSG.value = error.response.data.MSG
					csrf.value = error.response.data.csrf_create
					alert_status.value[1]='alert-danger'
				})
				.finally(()=>{
					get_UriageList()
					//IDD_Write('LocalParameters',[{id:'EventName',EventName:labels.value["EV_input_value"]}])
					const today = new Date().toLocaleDateString('sv-SE')
					//IDD_Write('LocalParameters',[{id:'EventName',EventName:EV_input_value.value}])
					IDD_Write('LocalParameters',[{id:'EventName',EventName:EV_input_value.value,LastUseDate:today}])
					
					document.getElementById('main_area').style.paddingTop = '215px'
					loader.value = false

					if(TourMilestone=="tutorial_7" || TourMilestone=="tutorial_4"){
						TourMilestone = "tutorial_7"
						tutorial_7_1.start(tourFinish,'tutorial','save');
					}
				
				})
		}

		//電卓処理関連
		const deposit = ref(0)
		const oturi = computed(() =>{//おつりの計算
			if(Revised_pay.value!==""){
				return Number(deposit.value) - Number(Revised_pay.value)
			}else{
				return Number(deposit.value) - Number(pay.value)
			}
		})
		const keydown = (e) => {//電卓ボタンの処理
			//console_log(e.target.innerHTML)
			if(e.target.innerHTML==="C"){
				deposit.value = 0
			}else if(e.target.innerHTML==="ちょうど"){
				if(Revised_pay.value!==""){
					deposit.value = Revised_pay.value
				}else{
					deposit.value = pay.value
				}
			}else{
				deposit.value = Number(deposit.value.toString() + e.target.innerHTML.toString())
			}
		}
		
		//Gioコーディング
		const vlat = ref('')		//緯度
		const vlon = ref('')		//経度
		const weather = ref('')
		const description = ref('')
		const temp = ref('')
		const feels_like = ref('')
		const icon = ref('')

		const vjusho = ref('')
		const muniCd = ref('')
		const lv01Nm = ref('')
		const v_get_gio = () =>{//緯度経度,天気情報取得
			return new Promise(resolve => {
				console_log('v_get_gio start')
				if(labels_address_check.value===true){
					console_log('v_get_gio no_exec')
					resolve(false)	//位置情報なし
				}else{
					navigator.geolocation.getCurrentPosition(
					//navigator.geolocation.watchPosition(
						async (geoLoc) => {
							vlat.value = geoLoc.coords.latitude
							vlon.value = geoLoc.coords.longitude
							let rtn_val

							//GioCodeから住所を取得
							const res_add = axios.get('https://mreversegeocoder.gsi.go.jp/reverse-geocoder/LonLatToAddress',{params:{lat:geoLoc.coords.latitude,lon:geoLoc.coords.longitude}})
							//GioCodeから天気を取得
							const res_weat = axios.get('https://api.openweathermap.org/data/2.5/weather',{
								//params:{lat:geoLoc.coords.latitude,lon:geoLoc.coords.longitude,units:'metric',APPID:'<?php echo WEATHER_ID; ?>'}
								params:{lat:geoLoc.coords.latitude,lon:geoLoc.coords.longitude,units:'metric',APPID:WEATHER_ID}
								,timeout: 5000
							})

							await res_add
							.then((response) => {
								console_log(response.data)
								let address = response.data.results
								// 変換表から都道府県などを取得
								let muniData = GSI.MUNI_ARRAY[address.muniCd]
								// 都道府県コード,都道府県名,市区町村コード,市区町村名 に分割
								let [prefCode, pref, muniCode, city] = muniData.split(',')
								//${pref}${city}${data.lv01Nm}->県・市区町村・番地
								vjusho.value = (`${city}${address.lv01Nm}`).replace(/\s+/g, "")

								console_log(muniData)
								muniCd.value = address.muniCd		//DB登録用
								lv01Nm.value = address.lv01Nm		//DB登録用
								//,jusho_es = escape(jusho.replace(/\s+/g, ""))								
							})
							.catch((error) => {
								console_log(`v_get_gio[address] ERROR:${error}`)
								rtn_val = false
							})

							await res_weat
							.then((response) => {
								//console_log(response.data)
								weather.value = response.data.weather[0].main
								description.value = response.data.weather[0].description
								temp.value = response.data.main.temp
								feels_like.value = response.data.main.feels_like
								icon.value = response.data.weather[0].icon + '.png'
							})
							.catch((error) => {
								console_log(`v_get_gio[weather] ERROR:${error}`)
								rtn_val = false
							})

							console_log('v_get_gio finish')
							if(rtn_val===false){
								resolve(false) //axiosでエラー
								labels_address_check.value=true //住所対象外のチェックを入れる
							}else{
								resolve(true)
							}
						},
						(err) => {
							console.error({err})
							resolve(false)	//位置情報なし
						}
					)
				}
			})
		}

		//領収書
		const keishou = ref('様')
		const oaite = ref('上')
		const URL = ref('')
		const DL_URL = ref('')
		const send_msg = ref('')	//LINEで領収書を送る時のメッセージ
		const getGUID = () =>{
			/*
			let dt = new Date().getTime();
			let uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
					let r = (dt + Math.random()*16)%16 | 0;
					dt = Math.floor(dt/16);
					return (c=='x' ? r :(r&0x3|0x8)).toString(16);
			});
			return uuid;
			*/
			return GET_GUID()
		}
		const QRout = () =>{
			// 入力された文字列を取得
			let guid = getGUID()
			let userInput = URL.value + '&qr=' + guid + '&tp=1&k=' + keishou.value + '&s=' + oaite.value
			console_log(userInput)
			var query = userInput.split(' ').join('+');
			// QRコードの生成
			GET_QRCODE(query,240,'qr')
			/*
			(function() {
				var qr = new QRious({
					element: document.getElementById('qr'), 
					// 入力した文字列でQRコード生成
					value: query
				});
				qr.background = '#FFF'; //背景色
				qr.backgroundAlpha = 1; // 背景の透過率
				qr.foreground = '#1c1c1c'; //QRコード自体の色
				qr.foregroundAlpha = 1.0; //QRコード自体の透過率
				qr.level = 'L'; // QRコードの誤り訂正レベル
				qr.size = 240; // QRコードのサイズ
				// QRコードをflexboxで表示
				document.getElementById('qrOutput').style.display = 'flex';
			})();*/
			// png出力用コード
			//var cvs = document.getElementById("qr");
		}
		const prv = () =>{
			//プレビュー印刷
			if(confirm("表示する領収書をお客様に発行しますか？")===true){
				DL_URL.value = URL.value + (`&sb=on&tp=1&k=${keishou.value}&s=${oaite.value}`)
			}else{
				DL_URL.value = URL.value + (`&sb=off&tp=1&k=${keishou.value}&s=${oaite.value}`)
			}
			window.open(P_ROOT_URL + DL_URL.value, '_blank')
		}

		const open_R = (setURL) =>{
			if(setURL!==undefined){
				URL.value = setURL
			}else{
				URL.value = rtURL.value
			}
			DL_URL.value = URL.value + (`&sb=on&tp=1&k=${keishou.value}&s=${oaite.value}`)
			send_msg.value = `${oaite.value}　${keishou.value}\n\nお買い上げ、ありがとうございます。\n領収書はこちらからダウンロードしてください。\n${(D_ROOT_URL+DL_URL.value)}`
			axios.get(`ajax_rtn_urlencode.php?url=${encodeURIComponent(send_msg.value)}`)
			.then((response)=>{
				//console_log(response.data)
				send_msg.value = response.data
				const myModal = new bootstrap.Modal(document.getElementById('ryoushuu'), {})
				myModal.show()
			})
		}

		watch([oaite,keishou],()=>{
			DL_URL.value = URL.value + (`&sb=on&tp=1&k=${keishou.value}&s=${oaite.value}`)
			send_msg.value = `${oaite.value}　${keishou.value}\n\nお買い上げ、ありがとうございます。\n領収書はこちらからダウンロードしてください。\n${(D_ROOT_URL+DL_URL.value)}`
			axios.get(`ajax_rtn_urlencode.php?url=${encodeURIComponent(send_msg.value)}`)
			.then((response)=>{
				//console_log(response.data)
				send_msg.value = response.data
			})
		})


		const order_list_area = ref()
		const cartbtn_show = ref()
		const order_list_area_set = () => {
			//レジ画面を最初に表示したタイミングで実行
			//画面サイズに応じてカートの表示・非表示を制御
			console_log('order_list_area_set start')
			let sizeH = window.innerHeight
			let sizeW = window.innerWidth

			//order_list_area.value.style=`height:${sizeH-350}px;`
			if(sizeW<=767){
				//order_list_area.value.style =`height:${sizeH-355}px;display:NONE;`
				order_list_area.value.style =`display:NONE;`
				cartbtn_show.value = true
			}else{
				order_list_area.value.style =`width:100%;height:${sizeH-355}px;display:BLOCK;`
				cartbtn_show.value = false
			}
		}
		const order_list = ref([])
		const order_panel_show_flg = ref(true)
		const order_panel_show = (status) => {
			//status=> show close barcode
			console_log(`order_panel_show now:chk_register_show.value=>${chk_register_show.value}`)
			let sizeW = window.innerWidth
			let sizeH = Number(window.innerHeight)
			let H_minus
			if(status==='barcode'){
				H_minus = Number(540)	//360 + 180
			}else{
				H_minus = Number(360)
			}
			let W_minus = Number(31)
			console_log(`order_panel_show now:sizeH=>${sizeH}`)
			
			if(sizeW<=767){
				if(status==='show' || status==='barcode'){
					order_panel_show_flg.value=false
					//order_list_area.value.style =`width:90%;height:${sizeH-H_minus}px;display:BLOCK;`
					if(chk_register_show.value==="chk"){
						order_list_area.value.style =`width:${sizeW-W_minus}px;height:${sizeH-H_minus}px;display:BLOCK;margin-left:10px;margin-right:17px;`
					}else{
						order_list_area.value.style =`width:${sizeW-W_minus}px;height:${sizeH-H_minus+45}px;display:BLOCK;margin-left:10px;margin-right:17px;`
					}
				}else{
					order_panel_show_flg.value=true
					order_list_area.value.style =`display:NONE;`
				}
			}

		}

		//細かな表示設定など
		const EV_input_value= ref('')
		const set_EventName = (jsonobj) =>{
				console_log('set_EventName start')
				//console_log(jsonobj)
				if(jsonobj===undefined){return}
				if(jsonobj.LastUseDate===undefined){return}
				EventName = jsonobj
				console_log(EventName)

				const today = new Date().toLocaleDateString('sv-SE')
				
				if(EventName.LastUseDate === today){
					EV_input_value.value = EventName.EventName
				}else{
					EV_input_value.value = ""
				}
		}

		//IDD_Read('LocalParameters','EventName',set_EventName)

		const labels_address_check = ref()
		const labels = computed(() =>{
			//let labels = []
			let rtn_labels = {}
			let today = new Date().toLocaleDateString('sv-SE')
			if(rg_mode.value !== 'shuppin_zaiko'){
				//rtn_labels={date_type:"売上日",date_ini:'<?php echo (string)date("Y-m-d");?>',btn_name:'釣　銭'}
				rtn_labels={date_type:"売上日",date_ini:today,btn_name:'釣　銭'}
			}else{
				rtn_labels={date_type:"出店日",date_ini:'',btn_name:''}
			}
			if(rg_mode.value === 'kobetu'){
				rtn_labels.EV_input_name='KOKYAKU'
				rtn_labels.EV_input_hidden='EV'
				rtn_labels.EV_input_placeholder='顧客名'
			}else{
				rtn_labels.EV_input_name='EV'
				rtn_labels.EV_input_hidden='KOKYAKU'
				rtn_labels.EV_input_placeholder='イベント名等'
			}
			if(rg_mode.value !== 'evrez'){
				rtn_labels.address='display:none'
				labels_address_check.value = true
			}else{
				rtn_labels.address=''
				labels_address_check.value = false
			}
			return rtn_labels
		})

		const labels_address_style = computed(() =>{
			if(labels_address_check.value===true){
				return 'text-decoration:line-through;'
			}else{
				return ''
			}
		})
		const EventList = ref([])
		const clear_EV_input_value = () =>{
			EV_input_value.value = ""
		}
		const EventList_filter = computed(() => {
			let searchWord = EV_input_value.value

			if (String(searchWord).length === "0") {return EventList.value;}
			return EventList.value.filter((row) => {
				return (
				row.meishou.includes(searchWord) 
				);
			});
		})

		const getEventList = () =>{
			console_log(`*****【 getEventList start 】*****`);
			let params = new URLSearchParams();
			//params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
			params.append('user_id', p_uid);
			params.append('regi_mode', rg_mode.value);

			axios
			.post('ajax_get_event_list_for_regi.php',params)
			.then((response) => {
				console_log('getEventList succsess')
				console_log(response.data)
				EventList.value = response.data
			})
			.catch((error) => {
				console_log(`getEventList ERROR:${error}`)
			})
			.finally(()=>{
				console_log(`*****【 getEventList end 】*****`);
			});

		}

		const barcode_cam_area = ref(false)
		let video  //onMountで定義
		let canvas //onMountで定義

		const barcode_mode = (p_mode) =>{//QR読取カメラ起動
		  //p_mode:start/restart/close
			if(barcode_cam_area.value === false && p_mode==='close'){
				return 0
			}
			if((barcode_cam_area.value === true && p_mode==='start' )|| p_mode==='close'){//QRボタンを再度タップしたときはエリアを閉じる
				order_panel_show("close")
				barcode_cam_area.value=false
				video.srcObject.getTracks().forEach(track => track.stop())
				return 0
			}
			barcode_cam_area.value = true
			/*
			setTimeout(() => {
				read_qr()
			}, 500);
			*/
			
			navigator.mediaDevices
				.getUserMedia({
					audio: false,
					video: {
						facingMode: {
							exact: 'environment'
						}
					}
				})
				.then((stream) => {
					order_panel_show("barcode")
					video.srcObject = stream
					video.onloadedmetadata = function(e) {
						video.play()
					}
					setTimeout(() => {
						read_qr()
					}, 500);
				})
				.catch((err) =>{
						alert('Error!!')
						alert(err)
						alert('カメラがないか、ブラウザからカメラを起動できない設定になっています。')
						alert('safari/chrome の設定を確認してください。')
						order_panel_show("close")
						barcode_cam_area.value=false
						stream.getTracks().forEach(track => track.stop())
				})
				
		}
		
		let G_reading_flg = true
		const read_qr = () =>{//QR読取処理
			const QR_DIV = document.getElementById('qr_code_reader_camera');
			const width = QR_DIV.clientWidth
			const height = QR_DIV.clientHeight
			canvas.width = (video.videoWidth===0?width:video.videoWidth)
			canvas.height = height
			const ctx = canvas.getContext('2d');

			ctx.font = '10px';
			//ctx.fillStyle = '#0069b3';
			ctx.fillText('カメラに切り替わらない場合、iOS/Androidをアップデートを試して下さい', 20, 50);

			ctx.drawImage(video, 0, 210, video.videoWidth, 180, 0, 0, canvas.width, 180);
			const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height)

			// jsQRに渡す/QRコードから商品CDを取得する
			const code = jsQR(imageData.data, canvas.width, canvas.height)
			if(code && G_reading_flg){
			    G_reading_flg = false       //読込停止
			    loader.value = true         //ローダー表示
			    canvas.style.display = 'block'    //画像データ表示ON
			    video.style.display = 'none'      //VideoOFF
			    
				qr_order(code.data) //読取した商品CDをオーダーに追加する
				//qr_order(107) //読取した商品CDをオーダーに追加する
				setTimeout(() => {
				    scan_result.value = ''
    			    canvas.style.display = 'none'
	    		    video.style.display = 'block'
				    loader.value = false
				    G_reading_flg = true
				    read_qr()
				}, 2000)
			}else{
				setTimeout(() => {
				    read_qr()
				    
				}, 100)
			}
		}
		const scan_result = ref('')
		const qr_order = (p_ShouhinCD) => {
			const order_list_index = shouhinMS.value.findIndex(//読取した商品NOから連想配列のINDEXを取得する
				list => String(p_ShouhinCD) === String(list.shouhinCD)
			)
			
			if(order_list_index >= 0){
				ordercounter(order_list_index)
			}else{
			  loader.value = false
				scan_result.value = `該当する商品はありません。スキャン結果:${p_ShouhinCD}`
			}
		}

		onMounted(() => {
			//console_log(get_value(1000,0.1,'IN'))
			console_log('onMounted')
			chk_csrf()
			total_area.value.style["fontSize"]="3.3rem"
			//get_shouhinMS()
			GET_SHOUHINMS()
			.then((response)=>{
				shouhinMS.value = response
				console_log('get_shouhinMS succsess')
			})
			.catch((error) => {
				console_log(`get_shouhinMS ERROR:${error}`)
			})

			get_UriageList()
			v_get_gio()
			order_list_area_set()
			IDD_Read('LocalParameters','category',set_category)
			if(rg_mode.value !== 'kobetu'){
				getEventList()
				IDD_Read('LocalParameters','EventName',set_EventName)
			}else if(rg_mode.value === 'kobetu'){
				getEventList()
			}
			window.addEventListener('resize', order_list_area_set)
			video  = document.getElementById('js-video')
			canvas = document.getElementById('js-canvas')
			//canvas.width = video.videoWidth;
			//canvas.height = video.videoHeight;
		})
		return{
			//get_shouhinMS,
			shouhinMS_filter,
			shouhinMS,
			ordercounter,
			pay,
			kaikei_zei,
			//pm,
			deposit,
			oturi,
			keydown,
			chk_register_show,
			btn_changer,
			reset_order,
			on_submit,
			alert_status,
			MSG,
			loader,
			csrf,
			get_UriageList,
			UriageList,
			Konyusha_su,
			disp_category,
			category,
			panel_changer,
			total_uriage,
			vlat,
			vlon,
			muniCd,
			lv01Nm,
			vjusho,
			v_get_gio,
			rg_mode,
			labels,
			labels_address_style,
			labels_address_check,
			scroller,
			get_scroll_target,
			weather,
			description,
			temp,
			feels_like,
			icon,
			Revised_pay,
			keishou,
			QRout,
			send_msg,
			DL_URL,
			oaite,
			prv,
			hontai,
			Revised,
			open_R,
			total_area,
			auto_ajust,
			auto_ajust_change,
			order_list_area,
			order_list,
			order_list_pm,
			ZeiChange,
			order_panel_show_flg,
			order_list_change_tax,
			order_panel_show,
			cartbtn_show,
			CHOUSEI_TYPE,
			keydown_waribiki,
			par,
			EventList,
			EventList_filter,
			EV_input_value,
			clear_EV_input_value,
			barcode_cam_area,
			barcode_mode,
			qr_order,
			scan_result,
		}
	}
})


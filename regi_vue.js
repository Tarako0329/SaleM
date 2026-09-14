const { createApp, ref, onMounted, computed, VueCookies, watch,nextTick  } = Vue;
const REZ_APP = (p_uid,p_timeout) => createApp({
	setup(){
		const zm = ref([//税区分マスタ
			{税区分:0,税区分名:'非課税',税率:0},
			{税区分:1001,税区分名:'8%',税率:0.08},
			{税区分:1101,税区分名:'10%',税率:0.1},
		])

		const shouhinMS = ref([])			//商品マスタ
		const KEIJOUBI = ref('')			//売上日
		const Kokyaku = ref('')				//顧客名
		const order_list = ref([{//注文リスト
			"CD":'',
			"NM":'',
			"SU":null,
			"UTISU":null,
			"TANKA":null,
			"GENKA_TANKA":null,
			"ZEIKBN":null,
		}])
		const order_summary = computed(()=>{
			//order_listを税区分ごとに集計して{税区分:税額}の配列を返す
			let tax_list = []
			let tax_total = 0
			let hontai_total = 0
			zm.value.forEach(zei => {
				let tax = 0
				let hontai = 0
				order_list.value.forEach(row => {
					if(Number(row.ZEIKBN) === Number(zei.税区分)) {
						hontai += Number(row.TANKA) * Number(row.SU)
					}
				})
				if(zei.税率 > 0) {
					tax = Math.round(hontai * zei.税率)
					tax_total += tax
				}
				hontai_total += hontai
				tax_list.push({
					ZEIKBN: zei.税区分
					,ZEIKBNMEI: zei.税区分名
					,ZEIRITU: zei.税率
					,CHOUSEIGAKU: 0
					,HONTAIGAKU: hontai
					,SHOUHIZEI: tax
					,ZEICHOUSEIGAKU: 0
				})
			})
			/*
				array (
    		  'ZEIKBN' => '1101',
    		  'ZEIKBNMEI' => '10%',
    		  'ZEIRITU' => '0.1',
    		  'CHOUSEIGAKU' => '0',
    		  'HONTAIGAKU' => '1000',
    		  'SHOUHIZEI' => '100',
    		  'ZEICHOUSEIGAKU' => '0',
    		),
			*/
			return {"tax_list": tax_list, "tax_total": tax_total, "hontai_total": hontai_total}
		})

		const add_order = () =>{//注文リストに空行を追加
			console_log('add_order start')
			order_list.value.push({
				"CD":'',
				"NM":'',
				"SU":null,
				"UTISU":null,
				"TANKA":null,
				"GENKA_TANKA":null,
				"ZEIKBN":null,
			})
		}
		const trush_order = (index) =>{//注文リストの指定行を削除
			console_log('trush_order start')
			if(order_list.value.length<=1){
				alert('注文リストは1行以上必要です。')
				return
			}
			order_list.value.splice(index,1)
		}
		const clear_order = () =>{//注文リストを初期化
			console_log('clear_order start')
			order_list.value = [{
				"CD":'',
				"NM":'',
				"SU":null,
				"UTISU":null,
				"TANKA":null,
				"GENKA_TANKA":null,
				"ZEIKBN":null,
				"SHOUHIZEI":null,
			}]
		}
		
		//商品選択・入力モーダル用
		const order_shouhinNM = ref('')	//order_listに登録する商品名
		const selected_shouhin_index = ref(null)	//選択された商品のインデックス
		const shouhinMS_filtered = computed(()=>{
			if(order_shouhinNM.value==''){
				return shouhinMS.value
			}
			return shouhinMS.value.filter(item => item.shouhinNM.includes(order_shouhinNM.value))
		})
		const set_shouhin_Open = (index) =>{//商品選択モーダルを開く
			selected_shouhin_index.value = index
			order_shouhinNM.value = order_list.value[index].NM ?? ''
		}
		const set_shouhin_close = () =>{//商品選択モーダルを閉じる＆商品名をorder_listに登録
			if(shouhinMS_filtered.value.length==1 && order_shouhinNM.value === shouhinMS_filtered.value[0].shouhinNM){
				order_list.value[selected_shouhin_index.value].CD = shouhinMS_filtered.value[0].shouhinCD
				order_list.value[selected_shouhin_index.value].NM = shouhinMS_filtered.value[0].shouhinNM
				//order_list.value[selected_shouhin_index.value].SU = shouhinMS_filtered.value[0].su
				order_list.value[selected_shouhin_index.value].UTISU = shouhinMS_filtered.value[0].Utisu ?? 0
				order_list.value[selected_shouhin_index.value].TANKA = shouhinMS_filtered.value[0].tanka
				order_list.value[selected_shouhin_index.value].GENKA_TANKA = shouhinMS_filtered.value[0].genka_tanka
				order_list.value[selected_shouhin_index.value].ZEIKBN = shouhinMS_filtered.value[0].zeiKBN

			}else{
				order_list.value[selected_shouhin_index.value].NM = order_shouhinNM.value
			}
			selected_shouhin_index.value = null
			order_shouhinNM.value = ''
		}

		/*
		$params["ShouhinCD"] = $row["CD"];
		$params["ShouhinNM"] = $row["NM"];
		$params["su"] = $row["SU"];
		$params["Utisu"] = $row["UTISU"];
		$params["tanka"] = $row["TANKA"];
		$params["UriageKin"] = ($row["SU"] * $row["TANKA"]);
		$params["zeiKBN"] = $row["ZEIKBN"];
		$params["genka_tanka"] = $row["GENKA_TANKA"];
		*/

		//顧客選択・入力モーダル用
		const Kokyaku_list = ref([])			//顧客リスト
		const Kokyaku_list_filtered = computed(()=>{
			if(Kokyaku.value==''){
				return Kokyaku_list.value
			}
			return Kokyaku_list.value.filter(item => item.meishou.includes(Kokyaku.value))
		})

	

		const alert_status = ref(['alert'])
		const MSG = ref('')
		const loader = ref(false)
		const csrf = ref('') 

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

		const on_submit = async(e) => {//登録・submit/
			console_log('on_submit start')
			loader.value = true
			let form_data = new FormData(e.target)

			await axios.post('ajax_EVregi_sql.php',form_data,{timeout:p_timeout }) //php側は15秒でタイムアウト
				.then((response) => {
					console_log(response.data)
					MSG.value = response.data.MSG
					alert_status.value[1]=response.data.status
					csrf.value = response.data.csrf_create
					rtURL.value = response.data.RyoushuURL
					if(response.data.status==='alert-success'){
						clear_order()
						console_log(`on_submit SUCCESS`)
						//reset_order()
						//order_panel_show("close")
					}else{
						console_log(`on_submit ERROR`)
						//order_panel_show("close")
					}
				})
				.catch((error) => {
					console_log(`on_submit ERROR:${error}`)
					MSG.value = error.response.data.MSG
					csrf.value = error.response.data.csrf_create
					alert_status.value[1]='alert-danger'
				})
				.finally(()=>{
					//const today = new Date().toLocaleDateString('sv-SE')
					loader.value = false
				})	
		}

		const getKokyakuList = () =>{
			console_log(`*****【 getKokyakuList start 】*****`);
			let params = new URLSearchParams();
			//params.append('user_id', '<?php echo $_SESSION["user_id"];?>');
			params.append('user_id', p_uid);
			params.append('regi_mode', 'kobetu');

			axios
			.post('ajax_get_event_list_for_regi.php',params)
			.then((response) => {
				console_log('getKokyakuList succsess')
				console_log(response.data)
				Kokyaku_list.value = response.data
			})
			.catch((error) => {
				console_log(`getKokyakuList ERROR:${error}`)
			})
			.finally(()=>{
				console_log(`*****【 getKokyakuList end 】*****`);
			});

		}

		//領収書
		const keishou = ref('様')
		//const oaite = ref('上')
		const URL = ref('')				//領収書用URL
		const DL_URL = ref('')
		const send_msg = ref('')	//LINEで領収書を送る時のメッセージ
		const rtURL = ref('')
		const prv = () =>{
			//プレビュー印刷
			if(confirm("表示する領収書をお客様に発行しますか？")===true){
				DL_URL.value = URL.value + (`&sb=on&tp=1&k=${keishou.value}&s=${Kokyaku.value}`)
			}else{
				DL_URL.value = URL.value + (`&sb=off&tp=1&k=${keishou.value}&s=${Kokyaku.value}`)
			}
			window.open(P_ROOT_URL + DL_URL.value, '_blank')
		}

		const open_R = (setURL) =>{
			if(setURL!==undefined){
				URL.value = setURL
			}else{
				URL.value = rtURL.value
			}
			DL_URL.value = URL.value + (`&sb=on&tp=1&k=${keishou.value}&s=${Kokyaku.value}`)
			send_msg.value = `${Kokyaku.value}　${keishou.value}\n\nお買い上げ、ありがとうございます。\n領収書はこちらからダウンロードしてください。\n${(D_ROOT_URL+DL_URL.value)}`
			axios.get(`ajax_rtn_urlencode.php?url=${encodeURIComponent(send_msg.value)}`)
			.then((response)=>{
				//console_log(response.data)
				send_msg.value = response.data
				const myModal = new bootstrap.Modal(document.getElementById('ryoushuu'), {})
				myModal.show()
			})
		}


		onMounted(async() => {
			console_log('onMounted')
			try{
				chk_csrf()
				getKokyakuList()
				shouhinMS.value = await GET_SHOUHINMS()
				console_log('get_shouhinMS succsess')
			}catch(e){
				console_log(`onMounted ERROR:${e}`)
			}
		})
		return{
			zm,
			shouhinMS,
			on_submit,
			alert_status,
			MSG,
			loader,
			csrf,
			order_list,
			Kokyaku_list,
			KEIJOUBI,
			Kokyaku,
			Kokyaku_list,
			Kokyaku_list_filtered,
			add_order,
			trush_order,
			order_shouhinNM,
			shouhinMS_filtered,
			set_shouhin_Open,
			selected_shouhin_index,
			set_shouhin_close,
			order_summary,
			clear_order,
			open_R,
			prv,
			URL,
			DL_URL,
			keishou,
			send_msg
		}
	}
})


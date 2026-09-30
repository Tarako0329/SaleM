const { createApp, ref, onMounted, computed, VueCookies, watch,nextTick  } = Vue;
const REZ_APP = (p_uid,p_timeout) => createApp({
	setup(){
		const zm = ref([//税区分マスタ
			{税区分:0,税区分名:'非課税',税率:0},
			{税区分:1001,税区分名:'8%',税率:0.08},
			{税区分:1101,税区分名:'10%',税率:0.1},
		])

		const shouhinMS = ref([])			//商品マスタ
		const zairyouMS = ref([])			//材料マスタ

		const get_zairyouMS = async() =>{
			console_log('get_zairyouMS start')
			const form = new FormData()
			form.append("csrf_token",csrf.value)

			await axios.post("ajax_get_zairyouMS.php",form)//,{headers:{'Content-Tyoe':'u\multipart/form-data'}}
			.then((response) =>{
				//alert(response.data.status)
				console_log(response.data)
				csrf.value = response.data.csrf_token
				zairyouMS.value = response.data.rows
			})
			.catch((error) =>{
				alert(error)
			})
			return 0;
		}

		const new_zairyouMS = ref({
				zairyouCD:'0',
				hinmei:'',
				value:0,
				zeikbn:'',
				volum:0,
			})			//材料マスタ追加用
		const shouhinMS_zairyou = ref([])			//商品材料マスタ
		
		const custum_inf = ref({
			shouhinCD:""
			,shouhinNM:""
			,S_lot:0
			,H_lot:0
			,SEIZOU_GENKA_TANKA:0
			,GENKA_TANKA:0
			,G_per:0.00
		})

		const add_zairyouMS = () =>{//材料リストにnew材料リストを追加
			console_log('add_zairyouMS start')
			const form = new FormData()
			form.append("zairyouCD",new_zairyouMS.value.zairyouCD)
			form.append("hinmei",new_zairyouMS.value.hinmei)
			form.append("value",new_zairyouMS.value.value)
			form.append("zeikbn",new_zairyouMS.value.zeikbn)
			form.append("volum",new_zairyouMS.value.volum)
			form.append("csrf_token",csrf.value)

			axios.post("ajax_insup_zairyouMS.php",form)//,{headers:{'Content-Tyoe':'u\multipart/form-data'}}
			.then((response) =>{
				alert(response.data.status)
				console_log(response.data)
				csrf.value = response.data.csrf_token
				zairyouMS.value.push({...new_zairyouMS.value})
			})
			.catch((error) =>{
				alert(error)
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
				custum_inf.value.shouhinCD = shouhinMS_filtered.value[0].shouhinCD
				custum_inf.value.shouhinNM = shouhinMS_filtered.value[0].shouhinNM
				custum_inf.value.H_lot = shouhinMS_filtered.value[0].Utisu ?? 0	//販売LOT
				custum_inf.value.S_lot = shouhinMS_filtered.value[0].S_lot ?? 0	//製造LOT
				custum_inf.value.G_per = shouhinMS_filtered.value[0].G_per ?? 0.00	//製造LOT

				//custum_inf.value.SEIZOU_GENKA_TANKA = shouhinMS_filtered.value[0].seizou_genka_tanka 	//製造原価単価
				custum_inf.value.GENKA_TANKA = shouhinMS_filtered.value[0].genka_tanka
				//custum_inf.value.ZEIKBN = shouhinMS_filtered.value[0].zeiKBN

			}else{
				//order_list.value[selected_shouhin_index.value].NM = order_shouhinNM.value
			}
			selected_shouhin_index.value = null
			order_shouhinNM.value = ''
		}

		const alert_status = ref(['alert'])
		const MSG = ref('')
		const loader = ref(false)
		const csrf = ref('') 

		const chk_csrf = async() =>{
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

		/*const on_submit = async(e) => {//登録・submit/
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
		}*/




		onMounted(async() => {
			console_log('onMounted')
			try{
				await chk_csrf()
				shouhinMS.value = await GET_SHOUHINMS()
				console_log('get_shouhinMS succsess')
				rtn = await get_zairyouMS()
				console_log('get_zairyouMS succsess')
			}catch(e){
				console_log(`onMounted ERROR:${e}`)
			}
		})
		return{
			zm,
			shouhinMS,
			//on_submit,
			alert_status,
			MSG,
			loader,
			csrf,
			trush_order,
			order_shouhinNM,
			shouhinMS_filtered,
			set_shouhin_Open,
			selected_shouhin_index,
			set_shouhin_close,

			custum_inf,
			zairyouMS,
			new_zairyouMS,
			add_zairyouMS,
		}
	}
})


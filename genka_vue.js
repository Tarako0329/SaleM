const { createApp, ref, onMounted, computed, VueCookies, watch,nextTick  } = Vue;
const REZ_APP = (p_uid,p_timeout) => createApp({
	setup(){
		const is_edit_shouhin = ref(false)	//商品マスタの編集モード
		const zm = ref([//税区分マスタ
			{税区分:0,税区分名:'非課税',税率:0},
			{税区分:1001,税区分名:'8%',税率:0.08},
			{税区分:1101,税区分名:'10%',税率:0.1},
		])

		const shouhinMS = ref([])			//商品マスタ
		const zairyouMS = ref([])			//材料一覧マスタ
		const edit_zairyouMS = ref({	//材料一覧マスタ追加用
			zairyouCD:'0',
			hinmei:'',
			value:0,
			zeikbn:'',
			volum:0,
			use_volume:0,
			unit:'',
			used:false,
		})

		const get_zairyouMS = async() =>{//材料一覧マスタを取得
			console_log('get_zairyouMS start')
			const form = new FormData()
			form.append("csrf_token",csrf.value)

			await axios.post("ajax_get_zairyouMS.php",form)
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
		
		const saved_ShouhinMS = ref({
			shouhinCD:""
			,shouhinNM:""
			,S_lot:0
			,H_lot:0
			,SEIZOU_GENKA_TANKA:0
			,GENKA_TANKA:0
			,G_per:0.00
			,gen_gen_tanka:0
			,hanbai_tanka:0
		})

		const edit_ShouhinMS = ref({})
		const edit_S_genka_tanka = computed(()=>{
			if(edit_ShouhinMS.value.S_lot==0){
				return 0
			}
			return Number((total_hiyou.value / edit_ShouhinMS.value.S_lot).toFixed(1))
		})
		const edit_H_genka_tanka = computed(()=>{
			if(edit_ShouhinMS.value.S_lot==0){
				return 0
			}
			return Number((total_hiyou.value / edit_ShouhinMS.value.S_lot * edit_ShouhinMS.value.H_lot).toFixed(1))
		})
		const edit_hanbai_tanka = computed(()=>{
			if(edit_ShouhinMS.value.S_lot==0 || edit_ShouhinMS.value.H_lot==0){
				return 0
			}
			return Number((total_hiyou.value / edit_ShouhinMS.value.S_lot * edit_ShouhinMS.value.H_lot / edit_ShouhinMS.value.G_per).toFixed(1))
		})

		const add_zairyouMS = () =>{//材料一覧リストにnew材料を追加(DB登録＋配列追加)
			console_log('add_zairyouMS start')
			const form = new FormData()
			form.append("zairyouCD",edit_zairyouMS.value.zairyouCD)
			form.append("hinmei",edit_zairyouMS.value.hinmei)
			form.append("value",edit_zairyouMS.value.value)
			form.append("zeikbn",edit_zairyouMS.value.zeikbn)
			form.append("volum",edit_zairyouMS.value.volum)
			form.append("unit",edit_zairyouMS.value.unit)
			form.append("csrf_token",csrf.value)

			axios.post("ajax_insup_zairyouMS.php",form)//,{headers:{'Content-Tyoe':'u\multipart/form-data'}}
			.then((response) =>{
				alert(response.data.status)
				console_log(response.data)
				csrf.value = response.data.csrf_token
				edit_zairyouMS.value.zairyouCD = response.data.new_zairyouCD
				zairyouMS.value.push({...edit_zairyouMS.value})
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
		
		const shouhinMS_zairyou = computed(()=>{//材料一覧でチェックされた材料の一覧
			let zairyou = zairyouMS.value.filter(item => (item.used == true || item.used == 'true'))
			zairyou.forEach(item => {
				const value = Big(item.value)
				const volum = Big(item.volum)
				const use_volume = Big(item.use_volume)
				item.hiyou = value.div(volum).mul(use_volume).toFixed(1)
			})
			return zairyou
		})

		const total_hiyou = computed(()=>{//商品材料マスタの材料費合計
			let total_hiyou = 0
			shouhinMS_zairyou.value.forEach(item => {
				total_hiyou = total_hiyou + Number(item.hiyou)
			})
			return total_hiyou
		})

		const save_genka = async() =>{//商品の原材料リスト登録
			console_log('save_genka start')
			const form = new FormData()
			form.append("shouhinCD",edit_ShouhinMS.value.shouhinCD)
			form.append("shouhinNM",edit_ShouhinMS.value.shouhinNM)
			form.append("zairyou_list",JSON.stringify(shouhinMS_zairyou.value))
			form.append("csrf_token",csrf.value)
			axios.post("ajax_delins_shouhinMS_zairyou.php",form)
			.then((response) =>{
				alert(response.data.status)
				console_log(response.data)
				csrf.value = response.data.csrf_token
			})
			.catch((error) =>{
				alert(error)
			})
		}

		const save_ShouhinMS = async() =>{//製造LOTなどの情報登録
			console_log('save_ShouhinMS start')
			const form = new FormData()
			form.append("shouhinCD",edit_ShouhinMS.value.shouhinCD)
			form.append("shouhinNM",edit_ShouhinMS.value.shouhinNM)
			form.append("S_lot",edit_ShouhinMS.value.S_lot)
			form.append("H_lot",edit_ShouhinMS.value.H_lot)
			form.append("seizou_genka_tanka",edit_ShouhinMS.value.SEIZOU_GENKA_TANKA)
			form.append("GENKA_TANKA",edit_ShouhinMS.value.GENKA_TANKA)
			form.append("G_per",edit_ShouhinMS.value.G_per)
			form.append("csrf_token",csrf.value)
			let rtn = await axios.post("ajax_upd_shouhinMS_lot.php",form)
			.then((response) =>{
				alert(response.data.status)
				console_log(response.data)
				csrf.value = response.data.csrf_token
			})
			.catch((error) =>{
				alert(error)
			})
		}
		
		//商品選択・入力モーダル用
			const set_shouhinNM = ref('')	//編集する商品の設定用
			const selected_shouhin_index = ref(null)	//選択された商品のインデックス
			const shouhinMS_filtered = computed(()=>{//商品マスタから編集対象商品を抽出
				if(set_shouhinNM.value==''){
					return shouhinMS.value
				}
				return shouhinMS.value.filter(item => item.shouhinNM.includes(set_shouhinNM.value))
			})
			const set_shouhin_Open = (index) =>{//商品選択モーダルを開く
				selected_shouhin_index.value = index
				set_shouhinNM.value = order_list.value[index].NM ?? ''
			}
			const set_shouhin_close = () =>{//商品選択モーダルを閉じる＆商品情報をsaved_ShouhinMSとedit_ShouhinMSに登録
				if(shouhinMS_filtered.value.length==1 && set_shouhinNM.value === shouhinMS_filtered.value[0].shouhinNM){
					console_log(shouhinMS_filtered.value[0])
					saved_ShouhinMS.value.shouhinCD = shouhinMS_filtered.value[0].shouhinCD
					saved_ShouhinMS.value.shouhinNM = shouhinMS_filtered.value[0].shouhinNM
					saved_ShouhinMS.value.H_lot = shouhinMS_filtered.value[0].utisu ?? 0	//販売LOT
					saved_ShouhinMS.value.S_lot = shouhinMS_filtered.value[0].S_lot ?? 0	//製造LOT
					saved_ShouhinMS.value.G_per = shouhinMS_filtered.value[0].G_per ?? 0.00	//原価率
					saved_ShouhinMS.value.hanbai_tanka = shouhinMS_filtered.value[0].tanka ?? 0.00	//販売単価
					
					//saved_ShouhinMS.value.SEIZOU_GENKA_TANKA = shouhinMS_filtered.value[0].seizou_genka_tanka 	//製造原価単価
					saved_ShouhinMS.value.GENKA_TANKA = shouhinMS_filtered.value[0].genka_tanka
					saved_ShouhinMS.value.gen_tanka = shouhinMS_filtered.value[0].tanka
					//saved_ShouhinMS.value.ZEIKBN = shouhinMS_filtered.value[0].zeiKBN

					edit_ShouhinMS.value = {...saved_ShouhinMS.value}

					//材料が登録されている場合は取得
					const form = new FormData()
					form.append("shouhinCD",saved_ShouhinMS.value.shouhinCD)
					form.append("csrf_token",csrf.value)
					axios.post("ajax_get_shouhinMS_zairyou.php",form)
					.then((response) =>{
						console_log(response.data)
						csrf.value = response.data.csrf_token
						zairyouMS.value = response.data.zairyou_zaiko
						is_edit_shouhin.value = true
					})
					.catch((error) =>{
						alert(error)
					})

				}else{
					//order_list.value[selected_shouhin_index.value].NM = set_shouhinNM.value
					is_edit_shouhin.value = false
				}
				selected_shouhin_index.value = null
				set_shouhinNM.value = ''
			}
		//商品選択・入力モーダル用ここまで
		
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
			is_edit_shouhin,
			zm,
			shouhinMS,
			loader,
			csrf,
			trush_order,
			set_shouhinNM,
			shouhinMS_filtered,
			set_shouhin_Open,
			selected_shouhin_index,
			set_shouhin_close,

			saved_ShouhinMS,
			edit_ShouhinMS,
			zairyouMS,
			edit_zairyouMS,
			add_zairyouMS,
			shouhinMS_zairyou,
			total_hiyou,
			edit_S_genka_tanka,
			edit_H_genka_tanka,
			edit_hanbai_tanka,
			save_genka,
			save_ShouhinMS,
		}
	}
})


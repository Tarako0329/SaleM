const { createApp, ref, onMounted, computed, VueCookies, watch } = Vue
const REZ_APP = () => createApp({
	setup(){
		const loader = ref(false)
		//商品マスタ取得関連
		const shouhinMS = ref([])			//商品マスタ
		const shouhinMS_BK = ref([])	//商品マスタ修正前バックアップ
		const ZeiMS = ref(ZEIM)

		//商品マスタのソート・フィルタ関連
		const chk_register_show = ref('all')	//フィルタ
		const order_by = ref(['seq','▼'])			//ソート（項目・昇順降順）
		const chk = ref('off')
		const search_word = ref('')
		const btn_name = ref('確　認')
		const btn_type = ref('button')
		const chk_onoff = () =>{
			if(chk.value==='off'){
				chk.value='on'
				btn_name.value='戻　る'
				//alert('表示されてる内容でよろしければ「登録」してください。')
			}else{
				chk.value='off'
				btn_name.value='確　認'
			}
		}
		const up_or_down = () =>{
			if(order_by.value[1]==='▼'){
				order_by.value[1]='▲'
			}else{
				order_by.value[1]='▼'
			}
		}
		const shouhinMS_filter = computed(() => {//商品マスタのソート・フィルタ
			let order_panel = ([])
			if(chk.value==='on'){
				let j=0 
				for (let i = 0; i < shouhinMS.value.length; ++i) {
					if(JSON.stringify(shouhinMS.value[i]) !== JSON.stringify(shouhinMS_BK.value[i])){
						console_log(`chk on ${i} UNmatch`,"lv3")
						order_panel[j] = shouhinMS.value[i]
						j++
					}else{
						console_log(`chk on ${i} match`,"lv3")
					}
				}
				return order_panel
			}else if (chk_register_show.value === "on"){//表示対象のみを返す
				order_panel = shouhinMS.value.filter((shouhin) => {
					return (shouhin.hyoujiKBN1 && shouhin.hyoujiKBN1.includes('on') );
				});
			}else if (chk_register_show.value === "off"){//表示対象外のみを返す
				order_panel = shouhinMS.value.filter((shouhin) => {
					return (shouhin.hyoujiKBN1===null || !shouhin.hyoujiKBN1.includes('on') );
				});
			}else{//全件表示
				order_panel = shouhinMS.value
			}
					
			//checkbox にあわせて on -> true に変更
			order_panel.forEach((list)=> {
				if(list.hyoujiKBN1==='on'){
					list['disp_rezi'] = true
				}else{
					list['disp_rezi'] = false
				}
			})

			if(search_word.value!=''){
				return shouhinMS.value.filter((shouhin) => {
					return (shouhin.shouhinNM.includes(search_word.value) );
				});
			}

			//最後にソートして返す
			if(order_by.value[0]==='name'){
				return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
					return (order_by.value[1]==='▼'?(a.shouhinNM < b.shouhinNM?1:-1):(a.shouhinNM > b.shouhinNM?1:-1))
				})
			}else if(order_by.value[0]==='seq'){
				return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
					return (order_by.value[1]==='▼'?(a.shouhinCD < b.shouhinCD?1:-1):(a.shouhinCD > b.shouhinCD?1:-1))
				})
			}else{}
		})//商品マスタのソート・フィルタ

		const shouhinMS_BK_filter = computed(() => {//商品マスタバックアップもソート・フィルタ
			let order_panel = ([])
			if (chk_register_show.value === "on"){//表示対象のみを返す
				order_panel = shouhinMS_BK.value.filter((shouhin) => {
					return (shouhin.hyoujiKBN1 && shouhin.hyoujiKBN1.includes('on') );
				});
			}else if (chk_register_show.value === "off"){//表示対象外のみを返す
				order_panel = shouhinMS_BK.value.filter((shouhin) => {
					return (shouhin.hyoujiKBN1===null || !shouhin.hyoujiKBN1.includes('on') );
				});
			}else{
				order_panel = shouhinMS_BK.value
			}
			order_panel.forEach((list)=> {
				if(list.hyoujiKBN1==='on'){
					list['disp_rezi'] = true
				}else{
					list['disp_rezi'] = false
				}
			})
			if(order_by.value[0]==='name'){
				return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
					return (order_by.value[1]==='▼'?(a.shouhinNM < b.shouhinNM?1:-1):(a.shouhinNM > b.shouhinNM?1:-1))
				})
			}else if(order_by.value[0]==='seq'){
				return order_panel.sort((a,b) => {//フィルタ結果をソートして親に返す
					return (order_by.value[1]==='▼'?(a.shouhinCD < b.shouhinCD?1:-1):(a.shouhinCD > b.shouhinCD?1:-1))
				})
			}else{}
		})//商品マスタバックアップもソート・フィルタ

		//更新関連
		const upd_zei_kominuki = ref('IN')
		const return_tax = (kingaku,zeikbn,kominuki) => {
			console_log('return_tax start')
			//console_log(zm)
			console_log(ZEIM)
			let zmrec = ([])
			zmrec = ZEIM.filter((list)=>{
					return list.税区分 == zeikbn
			})
			const values = get_value(Number(kingaku),Number(zmrec[0]["税率"]),kominuki)
			return values;
		}
		const set_new_value = (index,new_val_id) => {
			//単価入力欄から本体と消費税を算出し、セットする
			const new_val = document.querySelector(new_val_id)
			let values 
			console_log(`set_new_value start (index => ${index} new_val_id => ${new_val_id} new_val => ${new_val})`)
			if(new_val.value !== ''){
				values = return_tax(new_val.value, shouhinMS_filter.value[index].zeiKBN, upd_zei_kominuki.value)
				shouhinMS_filter.value[index].tanka = values[0]["本体価格"]
				shouhinMS_filter.value[index].tanka_zei = values[0].消費税
				if(values[0].E !== 'OK'){
					alert('指定の税込額は税率計算で端数が発生するため実現できません')
					new_val.value = values[0].税込価格
				}
			}else if(shouhinMS_filter.value[index].zeiKBN !== shouhinMS_BK_filter.value[index].zeiKBN){
				//税率のみ変更した場合は現在の単価から算出する
				values = return_tax(shouhinMS_filter.value[index].tanka, shouhinMS_filter.value[index].zeiKBN, 'NOTIN')
				shouhinMS_filter.value[index].tanka_zei = values[0].消費税
			}else{//新価格が空白の場合、本体・税額・税区分を元に戻す
				shouhinMS_filter.value[index].tanka = shouhinMS_BK_filter.value[index].tanka
				shouhinMS_filter.value[index].tanka_zei = shouhinMS_BK_filter.value[index].tanka_zei
				shouhinMS_filter.value[index].zeiKBN = shouhinMS_BK_filter.value[index].zeiKBN
			}
		}

		watch(upd_zei_kominuki,() => {
			//shouhinMS.value.forEach((row,index) => {
			shouhinMS_filter.value.forEach((row,index) => {
				set_new_value(index,`#new_val_${index}`)
			})
		})
		const delete_item = (item,link) =>{
			console_log(item)
			console_log(link)
			if(confirm(`${item} を削除します。よろしいですか？`)===true){
				loader.value = true
				//window.location.href = link
				axios.get(link)
				.then((response)=>{
					console_log(`delete_item SUCCESS`)
					//console_log(response.data)
					MSG.value = response.data.MSG
					alert_status.value[1]=response.data.alert
					csrf.value = response.data.csrf
					if(alert_status.value[1] === "alert-success"){
						GET_SHOUHINMS()
						.then((response)=>{
							shouhinMS.value = []
							shouhinMS_BK.value = []
							shouhinMS.value = response
							shouhinMS_BK.value = JSON.parse(JSON.stringify(shouhinMS.value))
							console_log('GET_SHOUHINMS succsess')
						})
						.catch((error) => {
							console_log(`GET_SHOUHINMS ERROR:${error}`)
						})
					}
				})
				.catch((error) => {
					console_log(`delete_item ERROR:${error}`)
					MSG.value = error.response.data.MSG
					csrf.value = error.response.data.csrf_create
					alert_status.value[1]='alert-danger'
				})
				.finally(()=>{
					loader.value = false
				})
			}
		}

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

		
		const MSG = ref('')
		
		const alert_status = ref(['alert'])

		const on_submit = (e) => {//登録・submit/
			console_log('on_submit start')
			loader.value = true
			let form_data = new FormData(e.target)
			let params = new URLSearchParams (form_data)
			axios
				.post('shouhinMSList_sql.php',params) //php側は15秒でタイムアウト
				.then((response) => {
					console_log(`on_submit SUCCESS`)
					//console_log(response.data)
					MSG.value = response.data.MSG
					alert_status.value[1]=response.data.status
					csrf.value = response.data.csrf_create
					if(alert_status.value[1] === "alert-success"){
						GET_SHOUHINMS()
						.then((response)=>{
							shouhinMS.value = []
							shouhinMS_BK.value = []
							shouhinMS.value = response
							shouhinMS_BK.value = JSON.parse(JSON.stringify(shouhinMS.value))
							console_log('GET_SHOUHINMS succsess')
						})
						.catch((error) => {
							console_log(`GET_SHOUHINMS ERROR:${error}`)
						})
						chk_onoff()
						window.scroll({top: 0,behavior: "smooth",});
						if(TourMilestone=="tutorial_11"){
							tutorial_13.start(tourFinish,'tutorial','finish');
						}
					
					}
				})
				.catch((error) => {
					console_log(`on_submit ERROR:${error}`)
					MSG.value = error.response.data.MSG
					csrf.value = error.response.data.csrf_create
					alert_status.value[1]='alert-danger'
				})
				.finally(()=>{
					loader.value = false
				})
		}

		const QR_DLtype = ref('none')
		const qr_download = (px) =>{
			//QRコード連続ダウンロード
      const link = document.createElement('a');
			
			shouhinMS.value.forEach((list)=>{
				if(list.disp_rezi){
					url = GET_QRCODE(String(list.shouhinCD),Number(px),'qr').toDataURL('image/png')
					// ダウンロードリンクを作成
					link.href = url
					link.download = `${list.shouhinCD}_${list.shouhinNM}.png`; // 保存するファイル名を設定
					
					// ダウンロードリンクをクリック
					link.click();
				}
			})
		}

		const qr_zip_download = (px) =>{
			//const blob_list = 
			create_qr(px)
			.then(async(response)=>{
				console_log('create_qr response')
				//console_log(response)
				const zip_blob = await generateZipBlob(response,"QR_files")
				console_log('zip_blob is')
				//console_log(zip_blob)

				const a = document.createElement('a');
				a.href = URL.createObjectURL(zip_blob);
				//a.href = 'blob:'+GET_DIRECT_URL(URL.createObjectURL(zip_blob))
				a.download = 'QR_codes.zip';
				//console_log(a.href)
				//console_log(URL.createObjectURL(zip_blob))

				a.style.display = 'none';
				document.body.appendChild(a);
				a.click();
				document.body.removeChild(a);
	
			})
		}

		const create_qr = (px) =>{
			return new Promise((resolve, reject) => {
				let blobs = []
				const msLength = shouhinMS.value.length
				shouhinMS.value.forEach((list,index)=>{
					//イメージデータ取得
					const QR_canvas = GET_QRCODE(String(list.shouhinCD),Number(px),'qr')
					const context = QR_canvas.getContext("2d");//2次元描画
					const imageData = context.getImageData(0, 0, QR_canvas.width, QR_canvas.height)
					//複写・再描画
					const QR_canvas2 = document.getElementById("qr2")
					const {width, height} = QR_canvas;
					QR_canvas2.width = width
					QR_canvas2.height = height + Number(12)
					const context2 = QR_canvas2.getContext("2d");//2次元描画
					context2.putImageData(imageData, 0, 0);

					//商品名を追加
					//context2.font = '10px Roboto medium';
					context2.font = '10px';
					//context2.fillStyle = '#0069b3';
					context2.fillText(list.shouhinNM, 0, px+Number(9),width);

					QR_canvas2.toBlob((b) => { 
						if((QR_DLtype.value==='rez' && list.disp_rezi) || (QR_DLtype.value==='chk' && list.cate_chk) || (QR_DLtype.value==='all')){
							blobs.push({
								"name":`ID_${list.shouhinCD}.png`
								,"content":b
							})
						}
						
						if(msLength===index+1){
							resolve(blobs)
						}
					}, 'image/png', 1.0);
				})
			});
		}

    const generateZipBlob = (nameContentPairs, folder_name) => {
			console_log('generateZipBlob exec')
			console_log(nameContentPairs)
			const zip = new JSZip();
			const folder = zip.folder(folder_name);
			nameContentPairs.forEach((list) => {
				console_log(list)

				const file_name = (list.name);
				const content = list.content;
				folder.file(file_name, content);
			});

			console_log('folder is')
			console_log(folder)
			return zip.generateAsync({ type: 'blob' }); // デフォルトで無圧縮
		};

		onMounted(() => {
			console_log('onMounted')
			//get_shouhinMS()
			chk_csrf()
			GET_SHOUHINMS()
			.then((response)=>{
				shouhinMS.value = response
				shouhinMS_BK.value = JSON.parse(JSON.stringify(shouhinMS.value))
				console_log('GET_SHOUHINMS succsess')
			})
			.catch((error) => {
				console_log(`GET_SHOUHINMS ERROR:${error}`)
			})
		})

		return{
			loader,
			shouhinMS,
			shouhinMS_BK,
			set_new_value,
			upd_zei_kominuki,
			chk_register_show,
			shouhinMS_filter,
			shouhinMS_BK_filter,
			order_by,
			up_or_down,
			chk_onoff,
			MSG,
			alert_status,
			btn_name,
			btn_type,
			delete_item,
			chk,
			search_word,
			csrf,
			on_submit,
			ZeiMS,
			qr_zip_download,
			qr_download,
			//blobs,
			QR_DLtype,

			create_qr,
		}
	}
});

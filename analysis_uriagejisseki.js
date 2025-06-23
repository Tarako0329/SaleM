const { createApp, ref, onMounted, computed, VueCookies, watch, watchEffect } = Vue
const analysis_uriagejisseki = (p_analysis_type,p_uid,p_csrf_create,p_ym) => createApp({
	setup(){
		const analysis_type = ref(p_analysis_type)
		const bunseki_menu = ref(BUNSEKI_MENU)
		var category_lv = 0 //商品分類ごとの売上円グラフで使用。0：大分類　1：中分類　2：小分類
		var over_category = ""   //商品分類ごとの売上円グラフで使用。クリックした分類の下分類の円グラフを表示する際に使用
		var myChart
		var chart_val_label = ""
		
		const get_analysis_data = () => {//売上分析データ取得ajax(データ取得→グラフ描画)
			console_log("get_analysis_data start")
			let params = new URLSearchParams()
			if(analysis_type.value === 'abc'){
				console_log("get_analysis_data ABC分析")
				//'analysis_abc.php?sum_tani=2&csrf_token='へ移動
				window.location.href = `analysis_abc.php?sum_tani=2&csrf_token=${CSRF.value}`
				return 0
			}else if(analysis_type.value != '12'){//ジャンル別売上円グラフ以外
				category_lv = 0
				over_category = ""
			}
			params.append('user_id', p_uid)
			params.append('date_from', date_from.value)
			params.append('date_to', date_to.value)
			params.append('analysis_type', analysis_type.value)
			params.append('event', ev_selected.value)
			params.append('tokui', ev_selected.value)
			params.append('csrf_token', CSRF.value)
			params.append('category_lv', category_lv)
			params.append('over_category', over_category)
			console_log(category_lv)
			console_log(params)

			axios
			.post('ajax_get_analysi_uridata.php',params)
			.then((response) => {
				console_log(response.data,'lv3')
				CSRF.value = response.data.csrf_create
				chart_type.value = response.data.chart_type
				chart_datasets.value = response.data.data
				grafu_discription.value = response.data.grafu_discription
				chart_val_label = response.data.chart_val_label
				chart_labels.value = []

				if(response.data.chart_type==='doughnut'){
					chart_labels.value = response.data.labels_long
					chart_color.value = []
					for(let i=0;i<=chart_datasets.value['uri'].length;i++){
						chart_color.value[i]='rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.8)'
					}
				}else{
					chart_labels.value = response.data.labels
				}

				if(response.data.chart_type==='bar'){//売上と粗利のカラー設定
					chart_color.value = []
					/*for(let i=0;i<=chart_datasets.value.length;i++){
						chart_color.value[i]='rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.8)'
					}*/
					chart_color.value[0]='rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.8)'
					chart_color.value[1]='rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.8)'
				}

				//グラフエリアのサイズ設定
				document.getElementById("chart_area").style.display='block'

				if(response.data.chart_type==='bar'){//棒グラフはデータ数に応じて変える
					//document.getElementById("chart_area").style.height='750px'
					console_log(chart_datasets.value['uri'].length)
					if(Number(chart_datasets.value['uri'].length) * 30 < 170){
						console_log('chart_area height 150px')
						document.getElementById("chart_area").style.height='200px'
					}else{
						document.getElementById("chart_area").style.height=`${Number(chart_datasets.value['uri'].length) * 30}px`
						console_log('chart_area height not 150px')
					}
				}else if(response.data.chart_type==='-'){//グラフ不要
					document.getElementById("chart_area").style.display='none'
					//document.getElementById("chart_area_upper_div").style.display='none'
				}else if(response.data.chart_type==='doughnut'){//円グラフはmax-height指定
					document.getElementById("chart_area_upper_row").style.maxHeight='500px'
					document.getElementById("chart_area_upper_row").style.height='100%'
					document.getElementById("chart_area").style.height='100%'
					document.getElementById("chart_area").style.width='100%'
					document.getElementById("chart_area").style.maxWidth='500px'
				}else{
					document.getElementById("chart_area").style.height='100%'
				}

				if(response.data.chart_type==='line'){
					let hour = response.data.xStart
					chart_x.value = [] //初期化
					for(let i=0;hour<=Number(response.data.xEnd);i++){
						chart_x.value[i] = hour
						hour++
					}
					for(let i=0;i<chart_datasets.value.length;i++){
						chart_datasets.value[i] = chart_datasets.value[i].slice(response.data.xStart,response.data.xEnd+1)
					}
				}

				table_labels.value = response.data.aryColumn
				table_data.value = response.data.result

				if(analysis_type.value === "Area_tanka_1"){
					chart_labels.value.forEach((item,index)=>{
						//console_log(item)
						let muniData = GSI.MUNI_ARRAY[item]
						let [prefCode, pref, muniCode, city] = muniData.split(',')
						//item = `${pref}${city}`
						chart_labels.value[index] = `${city.replace(/\s+/g, "")}`
					})

					table_data.value.forEach((row,index)=>{
						console_log(row.Labels)
						let muniData = GSI.MUNI_ARRAY[row.Labels]
						let [prefCode, pref, muniCode, city] = muniData.split(',')
						//item = `${pref}${city}`
						table_data.value[index]["Labels"] = `${pref.replace(/\s+/g, "")}${city.replace(/\s+/g, "")}`
					})
				}
				if(analysis_type.value === "Area_tanka_2"){
					chart_labels.value.forEach((item,index)=>{
						//console_log(item)
						let [muniCd,jusho] = item.split(',')
						let muniData = GSI.MUNI_ARRAY[muniCd]
						let [prefCode, pref, muniCode, city] = muniData.split(',')
						//item = `${pref}${city}`
						chart_labels.value[index] = `${city.replace(/\s+/g, "")}${jusho}`
					})

					table_data.value.forEach((row,index)=>{
						console_log(row.Labels)
						let [muniCd,jusho] = row.Labels.split(',')
						let muniData = GSI.MUNI_ARRAY[muniCd]
						let [prefCode, pref, muniCode, city] = muniData.split(',')
						//item = `${pref}${city}`
						table_data.value[index]["Labels"] = `${pref.replace(/\s+/g, "")}${city.replace(/\s+/g, "")}${jusho}`
					})
				}
				if(response.data.chart_type!=='-'){
					drow_chart(response.data.chart_type)
				}
			})
			.catch((error) => {
				console_log(`get_analysis_data ERROR:${error}`,'lv3')
			})
			.finally(()=>{
				//console_log(myChart,'lv3')
			})
			return 0
		}//売上分析データ取得ajax
	
		const drow_chart = (chart_type) => {//データ取得関数内でコールされる
			console_log('drow_chart start','lv3')
			if (myChart) {
				console_log('myChart.destroy','lv3')
				myChart.destroy()
			}
			const ctx = document.getElementById('ChartCanvas').getContext('2d')
			let params = {
				type: chart_type,
				data: {
					labels: chart_labels.value//['test']
					,datasets: [
						/*{
							label: chart_val_label	//label: "売上実績"
							,data: chart_datasets.value['uri']//[1000]
							,backgroundColor:chart_color.value[0]//['rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.5)']
						},
						{
							label: "粗利"
							,data: chart_datasets.value['arari']//[1000]
							,backgroundColor:chart_color.value[1]//['rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.5)']
						},*/
					]
				},
				options: {
						scales: {
							x: {
								//beginAtZero: true
								stacked: true
							},
							y: {
								//beginAtZero: true
								stacked: true
							}
						},
						responsive: true,
						//maintainAspectRatio: false,
						indexAxis: 'y'
				}
			}
			//データセット
			if(chart_type==='bar'){
				//chart_datasets.value['arari']に値がある時はparams.data.datasetsに['araru']を追加
				if(chart_datasets.value['arari']){
					params.data.datasets = [
						{
							label: "粗利",
							data: chart_datasets.value['arari'],
							backgroundColor: chart_color.value[1],
						},
					]
				}
				params.data.datasets.push({
					label: chart_val_label
					,data: chart_datasets.value['uri']//[1000]
					,backgroundColor:chart_color.value[0]//['rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.5)']
				})
			}else{
				params.data.datasets.push({
					label: chart_val_label
					,data: chart_datasets.value['uri']//[1000]
					,backgroundColor:chart_color.value//['rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.5)']
				})
			}

			if(chart_type==='bar'){
				params.data.datasets[0]['maxBarThickness'] = 20
				params.data.datasets[0]['barPercentage'] = 0.9
				params.options.maintainAspectRatio = false
			}else if(chart_type==='doughnut'){
				params.options.maintainAspectRatio = false
				params.options={events: ['click']}
				params.options={
					onClick: function (e, el,chart) {
						//円グラフタップ時の子分類データ取得処理を記述
						if (! el || el.length === 0) return
						console_log('onClick : label ' + chart.data.labels[el[0].index])
						console_log('onClick : category_lv ' + category_lv)
						console_log('onClick : label ' + e)
						
						if(category_lv>=2){
							category_lv = 0
						}else{
							category_lv += 1
						}
						over_category = chart.data.labels[el[0].index]
						console_log('onClick : category_lv ' + category_lv)
						console_log('onClick : over_category ' + over_category)
						get_analysis_data()
					}
				}
			}else if(chart_type==='line'){
				params.data.labels = chart_x.value//['X軸1','X軸2','X軸3','X軸4']
				params.options = {}
				//以下繰り返す
				for(let i=0;i<chart_labels.value.length;i++){
					params.data.datasets[i] = {
						borderColor: 'rgba('+(~~(256 * Math.random()))+','+(~~(256 * Math.random()))+','+ (~~(256 * Math.random()))+', 0.8)',
						label:chart_labels.value[i],
						tension: 0.2,
						pointRadius:5,
						hitRadius:15,
						pointHoverRadius:8,
						data: chart_datasets.value[i]
					}
				}
			}
			myChart = new Chart(ctx, params)
		}

		const serch_ym = ref('Y') // Y:単年 Y-Y:複数年 ym：年月範囲 ymd:日付範囲指定

		const today = new Date() // 現在の日付と時刻を持つDateオブジェクトを作成
		const year = today.getFullYear() // Dateオブジェクトから西暦（年）を取得
		const month = today.getMonth() // Dateオブジェクトから月を取得 (0-11)

		// 今月末の日付を計算
		const lastDayOfMonth = new Date(year, month + 1, 0)
		const formattedLastDay = `${lastDayOfMonth.getFullYear()}-${(lastDayOfMonth.getMonth() + 1).toString().padStart(2, '0')}-${lastDayOfMonth.getDate().toString().padStart(2, '0')}`
		const ev_selected = ref('')
		const date_from = ref(`${year}-01-01`)
		const date_to = ref(formattedLastDay)
		
		const ev_list = ref()

		const get_event = () => {//期間内のイベント一覧取得ajax
			console_log("get_event start",'lv3')
			let params = new URLSearchParams()
			params.append('user_id', p_uid)
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
			return 0
		}
		
		const CSRF = ref(p_csrf_create)
		const chart_type = ref('')
		const chart_labels = ref([])
		const chart_datasets = ref([])
		const chart_color = ref([])
		const chart_x = ref([])
		const table_labels = ref([])
		const table_data = ref([])
		const grafu_discription = ref('')

	
		watch([date_from,serch_ym],() => {
			if(serch_ym.value === 'Y'){
				//y_listのfrom_Value=datefromとなるレコードのtoValueをdate_toにセット
				const selected_y = y_list.value.find(item => item.fromValue === date_from.value)
				if (selected_y) {
					date_to.value = selected_y.toValue
				}
			}
		})
		watch([date_from,date_to,analysis_type,ev_selected],() => {
			get_analysis_data()
		})
		watch([date_from,date_to],() => {
			get_event()
		})

		const ym_list = ref()
		//引数に年月を指定。指定されたから今月までのリストを{"display":YYYY年MM月,"fromValue":月初日(yyyy-mm-dd),"toValue":月末日(yyyy-mm-dd)}のJSONで返す関数
		const create_ym_list = (start_ym) => {
			console_log('create_ym_list start')
			const today = new Date()
			const current_year = today.getFullYear()
			const current_month = today.getMonth() + 1 // getMonth()は0から始まるため+1

			const start_year = parseInt(start_ym.substring(0, 4))
			const start_month = parseInt(start_ym.substring(4, 6))

			let year = start_year
			let month = start_month
			const ym_list_temp = []

			while (year < current_year || (year === current_year && month <= current_month)) {
				const display = `${year}年${month.toString().padStart(2, '0')}月`
				const fromValue = `${year}-${month.toString().padStart(2, '0')}-01`
				const lastDay = new Date(year, month, 0).getDate() // 翌月の0日目は当月末日
				const toValue = `${year}-${month.toString().padStart(2, '0')}-${lastDay}`
				ym_list_temp.push({ display, fromValue, toValue })

				month++
				if (month > 12) {
					month = 1
					year++
				}
			}
			ym_list.value = ym_list_temp
			//return ym_list_temp
		}
		const y_list = ref()
		//引数に年月を指定。指定されたから今年までのリストを{"display":YYYY年,"fromValue":年始日(yyyy-mm-dd),"toValue":年末日(yyyy-mm-dd)}のJSONで返す関数
		const create_y_list = (start_y) => {
			console_log('create_y_list start')
			const today = new Date()
			const current_year = today.getFullYear()
			const current_month = today.getMonth() // getMonth()は0から始まるためそのまま使用
			const start_year = parseInt(start_y)

			let year = start_year
			const y_list_temp = []

			while (year <= current_year) {
				const display = `${year}年`
				const fromValue = `${year}-01-01`

				let toValue
				if (year === current_year) {
					// 今年の場合は今月末日を計算
					const lastDayOfCurrentMonth = new Date(current_year, current_month + 1, 0).getDate()
					toValue = `${current_year}-${(current_month + 1).toString().padStart(2, '0')}-${lastDayOfCurrentMonth.toString().padStart(2, '0')}`
				} else {
					// 過去の年の場合は12月31日
					toValue = `${year}-12-31`
				}
				y_list_temp.push({ display, fromValue, toValue })

				year++
			}
			y_list.value = y_list_temp
			//return y_list_temp
		}

		const url = computed(() =>{
			return 'analysis_menu.php?csrf_token=' + CSRF.value
		})
		onMounted(() => {
			console_log('onMounted')
			console_log(p_ym)
			get_event()
			get_analysis_data()
			create_ym_list(p_ym.toString())
			create_y_list(p_ym.toString().substring(0,4))
			console_log(GSI)
		})
		return{
			bunseki_menu,
			ev_list,
			get_event,
			serch_ym,
			//change_mode_ymd,
			//change_mode_ym,
			date_from,
			date_to,
			ym_list,
			y_list,
			analysis_type,
			grafu_discription,
			ev_selected,
			CSRF,
			url,
			chart_labels,
			chart_datasets,
			//chart_type,
			chart_color,
			//chart_x,
			table_labels,
			table_data,
		}
	}
})
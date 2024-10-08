//js 共通関数格納予定
const console_log=(log,lv)=>{
  //lv:all=全環境 undefined=本番以外
  //console.log(lv)
  if(lv==="all"){
    console.log(log)
  }/*else if(lv==="lv2" && KANKYO!=="Product"){
    console.log(log)
  }*/else if((lv==="lv3" || lv===undefined) && (KANKYO!=="Product")){
    //console.log(KANKYO)
    console.log(log)
  }else{
    return 0;
  }
} 

const get_value = (value,zei,kbn) => {
  //値段、税率、値段が税込か税抜か をパラメータで渡すとreturnを配列で返す
  //value:入力値
  //zei:税率(0 ～ 1)
  //kbn:IN-税込 NOTIN-本体
  //return [本体価格,消費税,税込価格,E:msg]
  let hontai
  let zeigaku
  let zeikomi
  let zeiritu = new Decimal(zei)
  let temp,temp2
  let msg = 'OK'

  console_log(`[function.js@get_value] params[${value}, %:${zei}, :${kbn}] ::端数${ZEIHASU}`,'lv3')

  if(ZEIHASU===0 || ZEIHASU===1 || ZEIHASU===2){
  }else{
    alert('ユーザ情報から消費税端数処理の方法を設定してください')
    return
  }
  
  if(zei===0){
    return [{本体価格:value,消費税:0,税込価格:value,E:msg}]
  }else if(kbn==='NOTIN'){
    temp = new Decimal(Number(value))
    hontai = value
    
    if(ZEIHASU===0){//切捨て
      zeigaku = Math.trunc(temp.mul(zeiritu))
    }else if(ZEIHASU===1){//四捨五入
      zeigaku = Math.round(temp.mul(zeiritu))
    }else if(ZEIHASU===2){//切上げ
      zeigaku = Math.ceil(temp.mul(zeiritu))
    }
  }else if(kbn==='IN'){
    temp = new Decimal(Number(value))
    if(ZEIHASU===0){//切捨て
      hontai = Math.ceil(temp.div(zeiritu.add(1)))  //本体額を算出
      temp2 = new Decimal(hontai)                   //本体額を算出
      zeigaku = Math.trunc(temp2.mul(zeiritu))      //本体額から消費税を算出
  
    }else if(ZEIHASU===1){//四捨五入
      hontai = Math.round(temp.div(zeiritu.add(1)))  //本体額を算出
      temp2 = new Decimal(hontai)                   //本体額を算出
      zeigaku = Math.round(temp2.mul(zeiritu))      //本体額から消費税を算出
  
    }else if(ZEIHASU===2){//切上げ
      hontai = Math.trunc(temp.div(zeiritu.add(1)))   //本体額を算出
      temp2 = new Decimal(hontai)                     //本体額を算出
      zeigaku = Math.ceil(temp2.mul(zeiritu))         //本体額から消費税を算出
  
    }
  }else{
    alert('ERROR:get_value')
    return
  }
  zeikomi = Number(hontai) + Number(zeigaku)
  if(kbn==='IN' && value!==zeikomi){
    console_log(`${value}:${zeikomi}`)
    zeigaku = Number(zeigaku) - (Number(zeikomi) - Number(value))
    zeikomi = Number(hontai) + Number(zeigaku)
    //msg = '税込金額は設定できません'
    console_log("悪魔の調整")
  }else{
    //msg=''
  }
  return [{本体価格:hontai,消費税:zeigaku,税込価格:zeikomi,E:msg}]
}

const GET_USER = ()=>{//ユーザマスタ取得
	return new Promise((resolve, reject) => {
		GET_USER_SHORI(resolve);
	});
}
const GET_USER_SHORI = (resolve) =>{
  let obj
  axios
  .get(`ajax_get_userms.php`)
  .then((response) => {
    obj = response.data
    console_log('ajax_get_userms.php succsess')
  })
  .catch((error)=>{
    console_log('ajax_get_userms.php ERROR')
    console_log(error)
  })
  .finally(()=>{
    resolve(obj)
  })
}

const GET_SHOUHINMS = ()=>{//商品マスタ取得
	return new Promise((resolve, reject) => {
		GET_SHOUHINMS_SHORI(resolve);
	});
}
const GET_SHOUHINMS_SHORI = (resolve) =>{
  let obj
  axios
  .get(`ajax_get_ShouhinMS.php`)
  .then((response) => {
    obj = response.data
    console_log('ajax_get_ShouhinMS.php succsess')
  })
  .catch((error)=>{
    console_log('ajax_get_ShouhinMS.php ERROR')
    console_log(error)
  })
  .finally(()=>{
    resolve(obj)
  })
}
const GET_GUID = () =>{
	let dt = new Date().getTime();
	let uuid = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
			let r = (dt + Math.random()*16)%16 | 0;
			dt = Math.floor(dt/16);
			return (c=='x' ? r :(r&0x3|0x8)).toString(16);
	});
	return uuid;
}

const GET_QRCODE = (p_string,p_size,p_canvas_id) =>{
  //p_string:QR化する文字列
  //p_size：サイズ
  //p_canvas_id：QRコードを表示するキャンバスID

  //console_log()
  // QRコードの生成
  const qr = new QRious({
    element: document.getElementById(p_canvas_id), //canvasタグのID
    // 入力した文字列でQRコード生成
    value: p_string
  });
  qr.background = '#FFF'; //背景色
  qr.backgroundAlpha = 1; // 背景の透過率
  qr.foreground = '#1c1c1c'; //QRコード自体の色
  qr.foregroundAlpha = 1.0; //QRコード自体の透過率
  qr.level = 'L'; // QRコードの誤り訂正レベル
  qr.size = p_size; // QRコードのサイズ

  // png出力用コード
  return document.getElementById(p_canvas_id);
}

const GET_DIRECT_URL = (url) =>{
  //サブドメイン表記のURLを直接参照URLに変換
  console_log(url)
  let index = url.lastIndexOf('/')
  return P_ROOT_URL + url.substr(index+Number(1))
}

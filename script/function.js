//js 共通関数格納予定
const console_log=(log,lv)=>{
  //lv:all=全環境 undefined=本番以外
  //console.log(lv)
  if(lv==="all"){
    console.log(log)
  }/*else if(lv==="lv2" && KANKYO!=="Product"){
    console.log(log)
  }*/else if((lv==="lv3" || lv===undefined) && (KANKYO!=="Product")){
    console.log(KANKYO)
    console.log(log)
  }else{
    return 0;
  }
}

const get_value=(value,zei,kbn) => {
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

  console_log(`NewVal:${value}, %:${zei}, :${kbn} ::端数${ZEIHASU}`,'lv3')

  if(ZEIHASU===0 || ZEIHASU===1 || ZEIHASU===2){
  }else{
    alert('ユーザ情報から端数処理設定を読み込んでください')
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
    msg = '税込金額は設定できません'
  }else{
    //msg=''
  }
  return [{本体価格:hontai,消費税:zeigaku,税込価格:zeikomi,E:msg}]
}
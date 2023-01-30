<?php
{
	//memo !empty()　は 変数未定義、空白、NULLの場合にfalseを返す
	require "php_header.php";
	
	
	if((empty($_GET["mode"])?"":$_GET["mode"])=="Updated"){
		if(csrf_chk_redirect($_GET["csrf_token"])==false){
			$_SESSION["EMSG"]="セッションが正しくありませんでした。③";
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: index.php");
			exit();
		}
	}elseif(isset($_GET["csrf_token"]) || empty($_POST)){
		if(csrf_chk_nonsession_get($_GET["csrf_token"])==false){
			$_SESSION["EMSG"]="セッションが正しくありませんでした。①";
			header("HTTP/1.1 301 Moved Permanently");
			header("Location: index.php");
			exit();
		}
	}elseif(csrf_chk()==false){
		$_SESSION["EMSG"]="セッションが正しくありませんでした②";
		header("HTTP/1.1 301 Moved Permanently");
		header("Location: index.php");
		exit();
	}
	
	$rtn=check_session_userid($pdo_h);
	$csrf_create = csrf_create();
	
	//deb_echo("UID：".$_SESSION["user_id"]);
	
	$msg = "";
	$upd_msg = "";
	$NextType="";
	function param_clear(){
		$_SESSION["Uridate2"]="%";
		$_SESSION["Event"]="%";
		$_SESSION["UriNO"]="%";
		$_SESSION["shouhinCD"]="%";
		$_SESSION["shouhinNM"]="%";
		$_SESSION["wheresql"]="";
	}
	
	{
		//POST
		if(!empty($_POST)){
			$_SESSION["UriFrom"]    =(empty($_POST["UriDateFrom"])?(string)date("Y-m-d"):$_POST["UriDateFrom"]);
			$_SESSION["UriTo"]      =(empty($_POST["UriDateTo"])?(string)date("Y-m-d"):$_POST["UriDateTo"]);
			$_SESSION["Event"]      =(empty($_POST["Event"])?"%":$_POST["Event"]);
			$mode = (empty($_POST["mode"])?"":$_POST["mode"]);
			$Type = (empty($_POST["Type"])?"":$_POST["Type"]);
			$display="where";
			
			//検索モーダル使用時は絞り込みを解除
			$_SESSION["Uridate2"]="%";
			$_SESSION["UriNO"]="%";
			$_SESSION["shouhinCD"]="%";
			
			//更新条件を保存
			$_SESSION["chk_uridate"]      =(empty($_POST["chk_uridate"])?"":$_POST["chk_uridate"]);
			$_SESSION["up_uridate"]      =(empty($_POST["up_uridate"])?"":$_POST["up_uridate"]);
			$upd_msg = $upd_msg.(empty($_POST["up_uridate"])?"":"売上日：".$_POST["up_uridate"]."<br>");
			$_SESSION["chk_event"]      =(empty($_POST["chk_event"])?"":$_POST["chk_event"]);
			$_SESSION["up_event"]      =(empty($_POST["up_event"])?"":$_POST["up_event"]);
			$upd_msg = $upd_msg.(empty($_POST["up_event"])?"":"イベント名：".$_POST["up_event"]."<br>");
			$_SESSION["chk_kokyaku"]      =(empty($_POST["chk_kokyaku"])?"":$_POST["chk_kokyaku"]);
			$_SESSION["up_kokyaku"]      =(empty($_POST["up_kokyaku"])?"":$_POST["up_kokyaku"]);
			$upd_msg = $upd_msg.(empty($_POST["up_kokyaku"])?"":"顧客名：".$_POST["up_kokyaku"]."<br>");
			$_SESSION["chk_urikin"]      =(empty($_POST["chk_urikin"])?"":$_POST["chk_urikin"]);
			$_SESSION["up_zeikbn"]      =(empty($_POST["up_zeikbn"])?"":$_POST["up_zeikbn"]);
			$_SESSION["up_uritanka"]      =(empty($_POST["up_uritanka"])?"":$_POST["up_uritanka"]);
			$_SESSION["up_zei"]      =(empty($_POST["up_zei"])?"":$_POST["up_zei"]);
			$upd_msg = $upd_msg.(empty($_POST["chk_urikin"])?"":"商品単価(税込)：".($_POST["up_uritanka"]+$_POST["up_zei"])."(".$_POST["up_uritanka"]." + ".$_POST["up_zei"].")<br>");
			$_SESSION["chk_genka"]      =(empty($_POST["chk_genka"])?"":$_POST["chk_genka"]);
			$_SESSION["up_urigenka"]      =(empty($_POST["up_urigenka"])?"":$_POST["up_urigenka"]);
			$upd_msg = $upd_msg.(empty($_POST["chk_genka"])?"":"原価単価：".$_POST["up_urigenka"]."<br>");
		}
		//GET
		if(!empty($_GET)){
			//初回アクセスはGETで来るので日付に今日をセット
			if(!empty($_GET["first"])){
				$_SESSION["MSG"]="本日の売上";
				$_SESSION["UriFrom"]=date("Y-m-d");
				$_SESSION["UriTo"]=date("Y-m-d");
				$_SESSION["UriageData_Correct_mode"]="false";
	
				param_clear();
			}
			$mode=(empty($_GET["mode"])?"":$_GET["mode"]);
			$Type=(empty($_GET["Type"])?"rireki":$_GET["Type"]);
			
			if((empty($_GET["display"])?"":$_GET["display"])=="all"){
				$_SESSION["UriFrom"]="2000-01-01";
				$_SESSION["UriTo"]="2099-12-31";
	
				param_clear();
			}
			//日付＞イベント＞商品・売上No
			if(!empty($_GET["ad1"])){
				$_SESSION["Uridate2"]=rot13decrypt2($_GET["ad1"]);
				$_SESSION["Event"]="%";
				$_SESSION["UriNO"]="%";
				$_SESSION["shouhinCD"]="%";
			}
			if(!empty($_GET["ad2"])){
				$_SESSION["Event"]=rot13decrypt2($_GET["ad2"]);
				$_SESSION["UriNO"]="%";
				$_SESSION["shouhinCD"]="%";
			}
			if(!empty($_GET["ad3"])){$_SESSION["UriNO"]=rot13decrypt2($_GET["ad3"]);}
			if(!empty($_GET["ad4"])){$_SESSION["shouhinCD"]=rot13decrypt2($_GET["ad4"]);}
			if(!empty($_GET["ad5"])){$_SESSION["shouhinNM"]=rot13decrypt2($_GET["ad5"]);}
	
			//deleteモード
			$_SESSION["urino"]=(empty($_GET["urino"])?"":$_GET["urino"]);
			$_SESSION["cd"]=(empty($_GET["cd"])?"":$_GET["cd"]);
		}
		//SESSION
		$_SESSION["UriageData_Correct_mode"]=(empty($_SESSION["UriageData_Correct_mode"])?"false":$_SESSION["UriageData_Correct_mode"]);
	}
	//var_dump($_SESSION);
	
	if($mode=="select"){
		$wheresql="where uid = :user_id AND UriDate >= :UriDate AND UriDate <= :UriDateTo and concat(Event,TokuisakiNM) like :Event ";  //検索モーダル部
		$wheresql=$wheresql."AND UriDate like :UriDate2 AND UriageNO like :UriNO AND ShouhinCD like :shouhinCD ";    //絞り込み対応部
		for($i=0;$i<4;$i++){
			if($Type=="rireki"){
				//履歴明細取得
				$sql = "select U.*,UGW.icon,UGW.temp,UGW.description, max(UGW.temp) OVER (PARTITION BY U.uid,U.UriDate,U.Event) as max_temp, min(UGW.temp) OVER (PARTITION BY U.uid,U.UriDate,U.Event) as min_temp ";
				$sql = $sql."from (select * ,su*genka_tanka as genka,UriageKin-(su*genka_tanka) as arari from UriageData ".$wheresql.") as U ";
				$sql = $sql."left join UriageData_GioWeather as UGW on U.uid = UGW.uid and U.UriageNO = UGW.UriNo order by U.UriDate desc,U.Event,U.UriageNO";
				
				$NextType=$Type;
			}elseif($Type=="sum_items"){
				//商品単位で集計
				$sql="select UriDate,UriageNO,Event,TokuisakiNM, ShouhinCD, ShouhinNM,shuppin_su,uri_su as su,zan_su, tanka,UriageKin,zei,genka,arari,icon,max_temp,min_temp from UriageDataSummary ";
				$sql = $sql.$wheresql."order by UriDate desc,Event,TokuisakiNM,ShouhinNM ";
				
				$NextType=$Type;
			}elseif($Type=="sum_events"){
				//イベント単位で集計
				if($i==3){
					//指定期間に実績がなかったらオール表示とする
					$_SESSION["UriFrom"]="2000-01-01";
					$_SESSION["UriTo"]="2099-12-31";
				}
				$_SESSION["MSG"]="";
				$sql = "select U.UriDate,'-' as UriageNO,U.Event,U.TokuisakiNM,'-' as ShouhinCD,'-' as ShouhinNM,0 as su,0 as tanka,sum(U.UriageKin) as UriageKin,sum(U.zei) as zei,sum(U.su*U.genka_tanka) as genka,sum(U.UriageKin-(U.su*U.genka_tanka)) as arari ";
				$sql = $sql.",max(UGW.icon) as icon,max(UGW.temp) as max_temp,min(UGW.temp) as min_temp ";
				$sql = $sql."from (select * from UriageData ".$wheresql.") as U left join UriageData_GioWeather as UGW on U.uid = UGW.uid and U.UriageNO = UGW.UriNo ";
				$sql = $sql." group by U.UriDate,U.Event,U.TokuisakiNM order by U.UriDate desc,U.Event,U.TokuisakiNM";
				$NextType="rireki";
			}
			
			//削除した後に表示する履歴のwhere文をセッションに保存
			$_SESSION["wheresql"]="where uid = :user_id AND UriDate >= '".$_SESSION["UriFrom"]."' AND UriDate <= '".$_SESSION["UriTo"]."' and concat(Event,TokuisakiNM) like '".$_SESSION["Event"]."' ";
			$_SESSION["wheresql"]=$_SESSION["wheresql"]."AND UriDate like '".$_SESSION["Uridate2"]."' AND UriageNO like '".$_SESSION["UriNO"]."' AND ShouhinCD like '".$_SESSION["shouhinCD"]."' ";
		
			$stmt = $pdo_h->prepare( $sql );
			$stmt->bindValue("UriDate", $_SESSION["UriFrom"], PDO::PARAM_STR);
			$stmt->bindValue("UriDateTo", $_SESSION["UriTo"], PDO::PARAM_STR);
			$stmt->bindValue("Event", $_SESSION["Event"], PDO::PARAM_STR);
		
			$stmt->bindValue("UriDate2", $_SESSION["Uridate2"], PDO::PARAM_STR);
			$stmt->bindValue("UriNO", $_SESSION["UriNO"], PDO::PARAM_INT);
			$stmt->bindValue("shouhinCD", $_SESSION["shouhinCD"], PDO::PARAM_INT);
			
			$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
			$rtn=$stmt->execute();
			if($rtn==false){
				deb_echo("失敗した場合は不正値が渡されたとみなし、wheresqlを破棄<br>");
				$_SESSION["wheresql"]="";
			}
			$result = $stmt->fetchAll();
			$rowcnt = $stmt->rowCount();
			if($rowcnt!==0){
				break;
			}else{
				if($Type=="rireki"){
					$Type="sum_items";
				}elseif($Type=="sum_items"){
					$Type="sum_events";
				}
			}
		}
	}elseif($mode=="del"){
		//削除モード(確認)
		$btnm = "削　除";
		$msg ="この売上を削除しますか？<br>";
		$sql = "select * ,su*genka_tanka as genka,UriageKin-(su*genka_tanka) as arari from UriageData where uid = :user_id and UriageNO = :UriNO and ShouhinCD = :ShouhinCD order by UriageNO";
		$stmt = $pdo_h->prepare( $sql );
		$stmt->bindValue("UriNO", $_SESSION["urino"], PDO::PARAM_INT);
		$stmt->bindValue("ShouhinCD", $_SESSION["cd"], PDO::PARAM_INT);
		//deb_echo($_SESSION["wheresql"]);
		$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
		$rtn=$stmt->execute();
		if($rtn==false){
			deb_echo("失敗した場合は不正値が渡されたとみなし、wheresqlを破棄<br>");
			$_SESSION["wheresql"]="";
		}
		$result = $stmt->fetchAll();
		//$rowcnt = $stmt->rowCount();
		
	}elseif($mode=="Updated" || $mode=="Update"){
		//更新対象・更新結果の表示
		$btnm = "更　新";
		$msg="更新対象データを確認してください。";
		if($mode=="Updated"){
			//更新後は更新モードオフ
			$_SESSION["UriageData_Correct_mode"]="false";
			$msg="";
		}
		$sql = "select * ,su*genka_tanka as genka,UriageKin-(su*genka_tanka) as arari from UriageData ".$_SESSION["wheresql"]." order by UriageNO";
		//deb_echo($sql);
		$stmt = $pdo_h->prepare( $sql );
		$stmt->bindValue("user_id", $_SESSION["user_id"], PDO::PARAM_INT);
		$rtn=$stmt->execute();
		if($rtn==false){
			deb_echo("失敗した場合は不正値が渡されたとみなし、wheresqlを破棄<br>");
			$_SESSION["wheresql"]="";
		}
		$result = $stmt->fetchAll();
		//$rowcnt = $stmt->rowCount();
	}else{
		echo "想定外エラー";
		exit();
	}
	
	//税区分M取得
	$ZEIsql="select * from ZeiMS order by zeiKBN;";
	$ZEIresult = $pdo_h->query($ZEIsql);
	
	//表示条件
	$joken="";
	$joken="期間：".($_SESSION["Uridate2"]=="%"?$_SESSION["UriFrom"]." ～ ".$_SESSION["UriTo"]:$_SESSION["Uridate2"]);
	$joken=$joken.($_SESSION["Event"]=="%"?"":" / ".$_SESSION["Event"]);
	$joken=$joken.($_SESSION["UriNO"]=="%"?"":" / 売上№".$_SESSION["UriNO"]);
	$joken=$joken.($_SESSION["shouhinCD"]=="%"?"":" / ".$_SESSION["shouhinNM"]);
	//deb_echo ("session:".$_SESSION["UriageData_Correct_mode"]);
}
?>
<!DOCTYPE html>
<html lang="ja">
<head>
	<?php 
	//共通部分、bootstrap設定、フォントCND、ファビコン等
	include "head.html" 
	?>
	<!--ページ専用CSS-->
	<link rel='stylesheet' href='css/style_UriageData_Correct.css?<?php echo $time; ?>' >
	<!--<script src='https://ajax.googleapis.com/ajax/libs/jquery/3.4.1/jquery.min.js'></script>-->
	<script>
		window.onload = function() {
			//アラート用
			function alert(msg) {
			  return $('<div class="alert" role="alert"></div>')
				.text(msg);
			}
			(function($){
			  const s = alert('<?php echo $_SESSION["MSG"]; ?>').addClass('alert-success');
			  // アラートを表示する
			  $('#alert-1').append(s);
			  //5秒後にアラートを消す
			  /*
			  setTimeout(() => {
				s.alert('close');
			  }, 5000);
			  */
			
			})(jQuery);

			function set_session_param(param){
				//検索用のイベント・顧客・商品リストを取得
				//id名[List]のリストデータを[date_from]～[date_to]に発生した[get_list_type]に更新
				$.ajax({
					// 通信先ファイル名
					type        : 'POST',
					url         : 'ajax_set_session_param.php',
					data        :{
									mode:$(param)[0].checked
								}
					},
				).done(
					// 通信が成功した時
					function(data) {
						console.log("通信成功");
					}
				).fail(
					// 通信が失敗した時
					function(XMLHttpRequest, textStatus, errorThrown){
						console.log("通信失敗2");
						console.log("XMLHttpRequest : " + XMLHttpRequest.status);
						console.log("textStatus     : " + textStatus);
						console.log("errorThrown    : " + errorThrown.message);
					}
				)};

			$('#switch1').change(function(){
				set_session_param('#switch1');
			});

			function getAllData(List,date_from,date_to,get_list_type){
				//検索用のイベント・顧客・商品リストを取得
				//id名[List]のリストデータを[date_from]～[date_to]に発生した[get_list_type]に更新
				$.ajax({
					// 通信先ファイル名
					type        : 'POST',
					url         : 'ajax_get_event_list.php',
					//dataType    : 'application/json',
					data        :{
									user_id     :'<?php echo $_SESSION["user_id"];?>',
									date_from   :$(date_from)[0].value,
									date_to     :$(date_to)[0].value,
									list_type   :get_list_type //イベントリスト or 商品リスト
								}
					},
				).done(
					// 通信が成功した時
					function(data) {
						//selectの子要素をすべて削除
						$(List).children().remove();
						$(List).append("<option value=''></option>\n");
						// 取得したレコードをeachで順次取り出す
						$.each(data, function(key, value){
							// appendで追記していく
							if(get_list_type=='Event'){
								if(value.LIST == '<?php echo $_SESSION["Event"]; ?>'){
									$(List).append("<option value='" + value.LIST + "' selected>" + value.LIST + "</option>\n");
								}else{
									$(List).append("<option value='" + value.LIST + "'>" + value.LIST + "</option>\n");
								}
							}else if(get_list_type=='Shouhin'){
								$(List).append("<option value='" + value.CODE + "'>" + value.CODE+ ":" + value.LIST + "</option>\n");
							}
						});
						/*
						console.log("通信成功");
						console.log(data);
						*/
					}
				).fail(
					// 通信が失敗した時
					function(XMLHttpRequest, textStatus, errorThrown){
						console.log("通信失敗2");
						console.log("XMLHttpRequest : " + XMLHttpRequest.status);
						console.log("textStatus     : " + textStatus);
						console.log("errorThrown    : " + errorThrown.message);
					}
				)};
				
			//起動時にリストを取得
			getAllData('#Event','#uridate','#uridateto','Event');
				
			//検索モーダルの売上日を変更すると、イベントリストを更新
			$('#uridate').change(function(){
				getAllData('#Event','#uridate','#uridateto','Event');
			});
			$('#uridateto').change(function(){
				getAllData('#Event','#uridate','#uridateto','Event');
			});

			document.onkeypress = function(e) {
				if (e.key === 'Enter') {
					return false;
				}
			}

		};//window.onload

	
	</script>    
	
	<TITLE><?php echo $title." 売上実績";?></TITLE>
</head>
 
<header class='header-color common_header' style='flex-wrap:wrap'>
	<div class='title' style='width: 100%;'><a href='menu.php'><?php echo $title;?></a></div>
	<div style='font-size:1rem;color:var(--user-disp-color);font-weight:400;'> <?php echo $joken;?></div>
	<a href="#" style='position:fixed;color:inherit;right:15px;top:45px;' data-toggle='modal' data-target='#modal_help1'>
			<i class="fa-regular fa-circle-question fa-lg awesome-color-white"></i>
	</a>
</header>
<div class='header_menu' style='border-bottom:solid var(--panel-bd-color) 0.5px;'>
	<nav class="navbar navbar-expand" style='padding:0;width:90%;'>
	<div class="container-fluid" >
/		<div class="navbar-brand" style='padding:5px;font-weight:800;'>売上実績<br>メニュー</div>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" 
    aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <li class="nav-item">
          <a class="nav-link btn-view" href="UriageData_Correct.php?mode=select&Type=sum_events&display=all&csrf_token=<?php echo $csrf_create; ?>">イベント別集計</a>
        </li>
        <li class="nav-item">
          <a class="nav-link btn-view" href="UriageData_Correct.php?mode=select&Type=sum_items&csrf_token=<?php echo $csrf_create; ?>">商品別集計</a>
        </li>
        <li class="nav-item">
          <a class="nav-link active btn-view" href="UriageData_Correct.php?mode=select&Type=rireki&csrf_token=<?php echo $csrf_create; ?>">売上明細</a>
        </li>
      </ul>
    </div>
  </div>
	</nav>
	<div style='width:20%;max-width:60px;text-align:right;' class='item_2'>
		<span style='margin-bottom:0px'>修正モード</span>
		<div class="switchArea">
			<input type="checkbox" id="switch1" onClick='chang_mode()' <?php if($_SESSION["UriageData_Correct_mode"]==="true"){echo "checked";} if($mode=="Update"){echo " readOnly ='readOnly' disabled ";} ?>>
			<label for="switch1" <?php if($mode=="Update"){echo " style='border-color:gray;'";}?>><span></span></label>
			<div id="swImg" <?php if($mode=="Update"){echo " style='background:gray;'";}?>></div>
		</div>
	</div>

	<?php
		echo "<p style='font-size:1.3rem'>".$msg."</p>\n";
	?>
</div>

<body class='common_body' id='body' >
	<div class='container-fluid'>
	<?php
		//アラート
		if($_SESSION["MSG"]!=""){
			echo "<div class='container'><div class='row'><div class='col-12'><div style='padding:0px 40px;text-align:center;font-size:1.5rem;' id='alert-1' class='lead'></div></div></div></div>";
		}
		$_SESSION["MSG"]="";
		
	?>
	<div style='overflow:auto;' id='uritable'>
	<table class='table-striped table-bordered result_table item_0 tour_uri1' style='margin-top:10px;margin-bottom:20px;'><!--white-space:nowrap;-->
		<thead>
			<tr>
				<th scope='col' style='width:20px;'></th>
				<th scope='col' style='width:130px;'>商品</th>
				<?php if($Type=="sum_items"){echo "<th scope='col' style='width:35px;'>出品数</th>";} ?>
				<th scope='col' style='width:30px;'>数</th>
				<?php if($Type=="sum_items"){echo "<th scope='col' style='width:35px;'>残数</th>";} ?>
				<th scope='col' style='width:60px;' class='d-none d-sm-table-cell'>単価</th>
				<th scope='col' style='width:60px;'>売上</th>
				<th scope='col' style='width:60px;' class='d-none d-sm-table-cell'>税</th>
				<th scope='col' style='width:50px;'>原価</th>
				<th scope='col' style='width:60px;'>粗利</th>
				<th scope='col' class='d-none d-sm-table-cell'>天候</th>
				<th scope='col' style='width:20px;'></th>
			</tr>
		</thead>
<?php    
$Goukei=0;
$GoukeiZei=0;
$GoukeiZeikomi=0;
$uridate="";

$colspan=($Type=="sum_items"?"12":"10");
$mark="";
foreach($result as $row){
	if($uridate!=$row["UriDate"].$row["Event"]){
		echo "<tr class='tr_stiky'><td colspan='".$colspan."' class='tr_stiky' style='white-space:nowrap;'><a href='UriageData_Correct.php?mode=select&ad1=".rot13encrypt2($row["UriDate"])."&Type=".$NextType."&csrf_token=".$csrf_create."'> 売上日：".$row["UriDate"]."</a> ";
		echo "<a href='UriageData_Correct.php?mode=select&ad2=".rot13encrypt2($row["Event"].$row["TokuisakiNM"])."&Type=".$NextType."&csrf_token=".$csrf_create."'>『".$row["Event"].$row["TokuisakiNM"]."』</a>";
		if(!empty($row["icon"])){
			echo "<img style='height:20px;' src='https://openweathermap.org/img/wn/".$row["icon"]."'>（<span style='color:red;'>".$row["max_temp"]."</span>/<span style='color:blue;'>".$row["min_temp"]."</span>）";
		}
		echo "</td></tr>\n";
	}
	if($row["UriageNO"] !=="-"){
		$mark=($row["UriageNO"]%2?"★":"☆");
	}
	echo "<tr><td class='text-center'><a href='UriageData_Correct.php?mode=select&ad3=".rot13encrypt2($row["UriageNO"])."&Type=".$NextType."&csrf_token=".$csrf_create."'>".$mark."</a></td>";
	echo "<td><a href='UriageData_Correct.php?mode=select&ad4=".rot13encrypt2($row["ShouhinCD"])."&ad5=".rot13encrypt2($row["ShouhinNM"])."&Type=".$NextType."&csrf_token=".$csrf_create."'>".($row["ShouhinNM"])."</a></td>";
	if($Type=="sum_items"){echo "<td class='text-right'>".$row["shuppin_su"]."</td>";}
	echo "<td class='text-right'>".$row["su"]."</td>";
	if($Type=="sum_items"){echo "<td class='text-right'>".$row["zan_su"]."</td>";}
	echo "<td class='text-right d-none d-sm-table-cell'>".return_num_disp($row["tanka"])."</td><td class='text-right'>".return_num_disp($row["UriageKin"])."</td>";
	echo "<td class='text-right d-none d-sm-table-cell'>".$row["zei"]."</td><td class='text-right'>".return_num_disp($row["genka"])."</td><td class='text-right'>".return_num_disp($row["arari"])."</td>\n";
	if(($Type=="rireki") && ($mode == "select") || ($mode == "Updated")){
		//履歴表示の時だけ削除可能
		if(!empty($row["icon"])){
			echo "<td class='d-none d-sm-table-cell'><img style='height:20px;' src='https://openweathermap.org/img/wn/".$row["icon"]."'>（<span>".$row["temp"]."℃ </span><span>".$row["description"]."</span>）</td>";
		}else{
			echo "<td class='d-none d-sm-table-cell'></td>";
		}
		echo "<td><a href='UriageData_Correct.php?cd=".$row["ShouhinCD"]."&urino=".$row["UriageNO"]."&csrf_token=".$csrf_create."&mode=del'><i class='fa-regular fa-trash-can'></i></a></td>";
	}else{
		echo "<td class='d-none d-sm-table-cell'></td><td></td>";
	}
	echo "</tr>\n";
	$Goukei = $Goukei + $row["UriageKin"];
	$GoukeiZei = $GoukeiZei + $row["zei"];
	$GoukeiZeikomi = $GoukeiZeikomi + $row["UriageKin"] + $row["zei"];
	$uridate=$row["UriDate"].$row["Event"];
}
?>
	</table>
	</div>
<?php
if($mode=="Update" || $mode=="del"){
?>
	<br>
	<?php 
	if($mode=="Update"){echo "<p style='font-size:1.3rem'>上に表示されているデータを更新します。<br><br>更新箇所<br>".$upd_msg."<br>よろしければ「更新」ボタンを押してください。</p>\n";} 
	?>
	<form method='post' action='UriageData_sql.php' id='form1'>
		<input type='hidden' name='mode' value='<?php echo $mode;?>'>
		<input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>
		<a href='UriageData_Correct.php?mode=select&first=first&Type=rireki&diplay=where&csrf_token=<?php echo $csrf_create; ?>' class='btn btn-secondary'>キャンセル</a>
		<button type='submit' style='font-size:1.5rem;color:#fff' class='btn btn-primary' ><?php echo $btnm; ?></button>
	</form>
<?php
}
?>
	<!--修正エリア-->
	<form class='form-horizontal update_areas footer_update_area tour_uri2' method='post' action='UriageData_Correct.php' style='display:none;' id='form2' onsubmit='return check_update()'>
		<hr>
		<input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>
		<input type='hidden' name='mode' value='Update'>
		<div >
			<table class='tour_uri3' style='width:90%;max-width:370px;'>
			<tr>
				<td><input type='checkbox' name='chk_uridate' id='chk_uridate'></td>
				<td>売上日</td>
				<td colspan='4'><input type='date' style='font-size:1.5rem;' name='up_uridate' id='up_uridate' maxlength='10'  class='form-control'></td>
			</tr>
			<tr>
				<td><input type='checkbox' name='chk_event' id='chk_event'></td>
				<td>イベント名</td>
				<td colspan='4'><input type='text' style='font-size:1.5rem;' name='up_event' id='up_event' maxlength='10' class='form-control'></td>
			</tr>
			<tr>
				<td><input type='checkbox' name='chk_kokyaku' id='chk_kokyaku'></td>
				<td>顧客名</td>
				<td colspan='4'><input type='text' style='font-size:1.5rem;' name='up_kokyaku' id='up_kokyaku' maxlength='10' class='form-control'></td>
			</tr>
			<tr>
				<td><input type='checkbox' name='chk_urikin' id='chk_urikin'></td>
				<td>売上単価</td>
				<td style='width:9rem'>単価変更</td><td></td><td colspan='1' style='width:9.5rem' >税区分</td>
			</tr>
			<tr>
				<td colspan='2'></td>
				<td colspan='1' ><input type='number' style='font-size:1.5rem;width:90%;' id='UpUriTanka' maxlength='10' class='form-control'  onchange='zei_math()' ></td>
				<td colspan='1'>
					<div class='btn-group btn-group-toggle' data-toggle='buttons'>
						<label class='btn btn-outline-primary active' style='padding:1px;font-size:1.2rem;' onchange='zei_math()' >
							<input type='radio' name='options' id='option1' value='zeikomi' autocomplete='off' checked> 税込
						</label>
						<label class='btn btn-outline-primary' style='padding:1px;font-size:1.2rem;' onchange='zei_math()' >
							<input type='radio' name='options' id='option2' value='zeinuki' autocomplete='off'> 税抜
						</label>

					</div>
				</td>
				<td colspan='1'>
					<select class='form-control' style='padding-top:0;' id='zeikbn' name='up_zeikbn' onchange='zei_math()' >
						<option value=''></option>
						<?php
						foreach($ZEIresult as $row){
							echo "<option value=".secho($row["zeiKBN"]).">".secho($row["hyoujimei"])."</option>\n";
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan='2'></td>
				<td colspan='1' style='width:9rem'>税抜単価</td><td style='width:7rem'>消費税</td><td colspan='1' style='width:9rem'>税込単価</td>
			</tr>
			<tr>
				<td colspan='2'></td>
				<td colspan='1' ><input type='number' style='font-size:1.5rem;width:90%;' name='up_uritanka' id='UpTanka' maxlength='10' class='form-control' ></td>
				<td><input type='number' style='font-size:1.5rem;width:90%;' name='up_zei' id='UpZei' maxlength='10' class='form-control' ></td>
				<td colspan='1' ><input type='number' style='font-size:1.5rem;width:90%;' id='UpUriZei' maxlength='10' class='form-control' ></td>
			</tr>
			<tr>
				<td><input type='checkbox' name='chk_genka' id='chk_genka'></td>
				<td>原価単価</td>
				<td colspan='4'><input type='number' style='font-size:1.5rem;' name='up_urigenka' maxlength='10' id='up_urigenka' class='form-control'></td>
			</tr>
			<tr >
				<td colspan='2'></td>
				<td colspan='4' style='padding-top:5px;'><button type='submit' style='font-size:1.5rem;color:#fff' class='btn btn-primary tour_uri4'>確　認</button></td>
			</tr>
			</table>
			<a href="#" style='color:inherit;position:fixed;bottom:250px;right:5px;' onclick='urihelp()'>
				<i class="fa-regular fa-circle-question fa-2x awesome-color-panel-border-same"></i>
			</a>
		</div>
	</form>
	</div>
</body>

<footer class='common_footer'>
	<div class='kaikei'>
		合計(税込)：￥<?php echo return_num_disp($GoukeiZeikomi) ?>-<br>
		<span style='font-size:1.3rem;'>内訳(本体+税)：￥<?php echo return_num_disp($Goukei)." + ".return_num_disp($GoukeiZei) ?></span>
	</div>
	<div class='right1 item_1'>
		<button type='button' class='btn--chk' style='border-radius:0;' data-toggle='modal' data-target='#UriModal'>検　索</button>
	</div>

</footer>

<!--売上実績検索条件-->
<div class='modal fade' id='UriModal' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
	<div class='modal-dialog  modal-dialog-centered'>
		<div class='modal-content' style='font-size:1.5rem; font-weight: 600;background-color:rgba(255,255,255,0.8);'>
			
			<form class='form-horizontal' method='post' action='UriageData_Correct.php' id='form3'>
				<input type='hidden' name='csrf_token' value='<?php echo $csrf_create; ?>'>
				<input type='hidden' name='mode' value='select'>
				<div class='modal-header'>
					<div class='modal-title' id='myModalLabel'>表示条件変更</div>
				</div>
				<div class='modal-body'>
					<div>
						<label for='uridate' class='control-label'>売上日～：</label>
						<input type='date' style='font-size:1.5rem;' name='UriDateFrom' maxlength='10' id='uridate' class='form-control' value='<?php echo $_SESSION["UriFrom"]; ?>'>
					</div>
					<div>
						<label for='uridateto' class='control-label'>～売上日：</label>
						<input type='date' style='font-size:1.5rem;' name='UriDateTo' maxlength='10' id='uridateto' class='form-control' value='<?php echo $_SESSION["UriTo"]; ?>'>
					</div>
					<div>
						<label for='Event' class='control-label'>イベント/顧客名：</label>
						<select name='Event' style='font-size:1.5rem;padding-top:0;' id='Event' class='form-control' aria-describedby='EvHelp'>
							<option value=''></option>
							<?php
							//Ajaxで取得に変更
							?>
						</select>
						<small id='EvHelp' class='form-text text-muted'>売上日の期間を変更すると選択肢が更新されます。</small>
					</div>
					<div>
						<label for='Type' class='control-label'>表示：上で指定した期間中の</label>
						<select name='Type' style='font-size:1.5rem;padding-top:0;' id='Type' class='form-control'>
							<option value='rireki' >売上履歴</option>
							<option value='sum_items' >売上を日付＞イベント＞商品単位で集計</option>
							<option value='sum_events' >売上を日付＞イベント単位で集計</option>
						</select>
					</div>
				</div>
				<div class='modal-footer'>
					<button type='submit' style='font-size:1.5rem;color:#fff' class='btn btn-primary' >決　定</button>
				</div>
			</form>
		</div>
	</div>
</div>
<!--help1-->
<div class='modal fade' id='modal_help1' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
	<div class='modal-dialog  modal-dialog-centered'>
		<div class='modal-content' style='font-size:1.2rem; font-weight: 600;background-color:rgba(255,255,255,0.8);'>
			<!--
			<div class='modal-header'>
				<div class='modal-title' id='myModalLabel'>help</div>
			</div>
			-->
			<div class='modal-body'>
				<h4 style='margin-bottom:0;'>ボタンについて</h4>
				<div style='border:solid thin var(--panel-bd-color);border-radius:3px;padding:10px;margin-bottom:5px;'>
					<li class='btn-view' style='font-size:1.2rem;padding:2px'>イベント集計</li>　<p>全期間の売上を『日付＞イベント』単位で集計して表示</p>
					<li class='btn-view' style='font-size:1.2rem;padding:2px'>商品集計</li>　<p>現在表示されている売上を『日付＞イベント＞商品』単位で集計して表示</p>
					<li class='btn-view' style='font-size:1.2rem;padding:2px'>会計明細</li>　<p>現在表示している売上のお会計明細を表示</p>
				</div>
				<h4 style='margin-bottom:0;'>表の操作</h4>
				<div style='border:solid thin var(--panel-bd-color);border-radius:3px;padding:10px;margin-bottom:5px;'>
					<p>イベント集計モードで表示している場合、「<span style='color:blue;'>日付</span>」「<span style='color:blue;'>イベント名</span>」をタップすると明細を表示。</p>
					<p><span style='color:blue;'>青文字</span>をタップすると、タップしたデータと同じ条件で絞り込まれます。</p>
					<p>例：イベント名をタップすると、同名のイベントの売上のみが表示</p>
				</div>        
				<h4 style='margin-bottom:0;'>修正モードについて</h4>
				<div style='border:solid thin var(--panel-bd-color);border-radius:3px;padding:10px;'>
					右上の「<span style='color:blue;'>修正モード</span>」をONにすると、誤入力した売上を修正できます。
				</div>
			</div>
			<div class='modal-footer'>
			</div>
		</div>
	</div>
</div>
<script>
	
	var select = document.getElementById('zeikbn');
	var tanka = document.getElementById('UpUriTanka');
	var UpTanka = document.getElementById('UpTanka');
	var shouhizei = document.getElementById('UpZei');
	var zkomitanka = document.getElementById('UpUriZei');
	var kominuki = document.getElementsByName('options')
	var zei_math = function(){
		if(select.value=='0'){
			zkomitanka.value=tanka.value;
			shouhizei.value=0;
			UpTanka.value = tanka.value;
		}else if(kominuki[0].checked){//税込
			switch(select.value){
				case '1001':
					zkomitanka.value=tanka.value * 1;
					shouhizei.value=tanka.value - Math.round(tanka.value / (1 + 8 / 100));
					UpTanka.value = Math.round(tanka.value / (1 + 8 / 100));
					break;
				case '1101':
					zkomitanka.value=tanka.value * 1;
					shouhizei.value=tanka.value - Math.round(tanka.value / (1 + 10 / 100));
					UpTanka.value = Math.round(tanka.value / (1 + 10 / 100));
					break;
			}
		}else if(kominuki[1].checked){//税抜
			switch(select.value){
				case '1001':
					zkomitanka.value=Math.round(tanka.value * (1 + 8 / 100));
					shouhizei.value=Math.round(tanka.value * (8 / 100));
					UpTanka.value = tanka.value;
					break;
				case '1101':
					zkomitanka.value=Math.round(tanka.value * (1 + 10 / 100));
					shouhizei.value=Math.round(tanka.value * (10 / 100));
					UpTanka.value = tanka.value;
					break;
			}
		}else{
			//
		}
	}

	var update_areas=document.getElementsByClassName('update_areas');
	var common_footer=document.getElementsByClassName('common_footer');
	var mode_switch=document.getElementById('switch1');
	var body=document.getElementById('body');
	var uritable=document.getElementById('uritable');

	//mode_switch.onclick = function (){
	var chang_mode = function(){
		let wh = window.innerHeight;//ブラウザの縦サイズ取得
		
		//ヘッダとフッタ分をマイナスして縦幅を算出
		var normal_vw = wh - 105 - 110;
		var update_vw = wh - 105 - 300;
		console.log('full:' + wh +' normal:' + normal_vw + ' update_vw:' + update_vw);
		if(mode_switch.checked==true && mode_switch.readOnly == false){
			update_areas[0].style.display='block';
			common_footer[0].style.display='none';
			//[1].style.display='block';
			body.style.paddingBottom='330px';
			uritable.style.height=update_vw +'px';
		}else{
			update_areas[0].style.display='none';
			common_footer[0].style.display='flex';
			//update_areas[1].style.display='none';
			body.style.paddingBottom='100px';
			uritable.style.height= normal_vw +'px';
		}
	}
	chang_mode();
	
	//チェックボックスのチェック有無で必須か否かを切り替え
	document.getElementById('chk_uridate').onclick = function(){
		const a = document.getElementById('up_uridate');
		if(a.required==true){
			a.required=false;
		}else{
			a.required=true;
		}
	}
	document.getElementById('chk_event').onclick = function(){
		const a = document.getElementById('up_event');
		if(a.required==true){
			a.required=false;
		}else{
			a.required=true;
		}
	}
	document.getElementById('chk_kokyaku').onclick = function(){
		const a = document.getElementById('up_kokyaku');
		if(a.required==true){
			a.required=false;
		}else{
			a.required=true;
		}
	}
	document.getElementById('chk_urikin').onclick = function(){
		const a = document.getElementById('UpUriTanka');
		const b = document.getElementById('zeikbn'); 
		if(a.required==true){
			a.required=false;
			b.required=false;
		}else{
			a.required=true;
			b.required=true;
		}
	}
	document.getElementById('chk_genka').onclick = function(){
		const a = document.getElementById('up_urigenka');
		if(a.required==true){
			a.required=false;
		}else{
			a.required=true;
		}
	}
	
	//更新対象の有無を確認。無い場合はsubmitしない
	function check_update(){
		var flg = false;
		if(document.getElementById('chk_uridate').checked==true){
			return true;
		}
		if(document.getElementById('chk_event').checked==true){
			return true;
		}
		if(document.getElementById('chk_kokyaku').checked==true){
			return true;
		}
		if(document.getElementById('chk_urikin').checked==true){
			return true;
		}
		if(document.getElementById('chk_genka').checked==true){
			return true;
		}
		alert("更新対象がありません。");
		return false;
	}
</script>
<!--シェパードナビshepherd
<script src="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/js/shepherd.min.js"></script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/shepherd.js@9.1.1/dist/css/shepherd.css"/>
-->
<script src="shepherd/shepherd.min.js?<?php echo $time; ?>"></script>
<link rel="stylesheet" href="shepherd/shepherd.css?<?php echo $time; ?>"/>
<?php require "ajax_func_tourFinish.php";?>
<script>
	const TourMilestone = '<?php echo $_SESSION["tour"];?>';

	const tutorial_9 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: true,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_9'
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>レジで売上を入力した当日に「売上実績」を開くと当日の売上明細が表示されます。
			   </p>`,
		attachTo: {
			element: '.item_0',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>
				<a href='#' class='btn-view' style='padding:4px;'>イベント集計</a>
				<a href='#' class='btn-view' style='padding:4px;'>商品集計</a>
				<a href='#' class='btn-view' style='padding:4px;'>会計明細</a>
				<br>
				<br>画面上部にあるこれらのボタンをタップすると、売上実績の表示方法を変更できます。
				<br>
				<br><i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i> をタップするとボタンの説明と表の操作方法を確認出来ます。
				</p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>過去の売上を確認したい場合は「検索」からも行う事が出来ます。
				<br>
				<br>ボタンをタップすると、検索用の画面が表示され、再度タップすると表示が消えます。
				<br>
				<br>
			   </p>`,
		attachTo: {
			element: '.item_1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>ためしにタップして画面を確認してみてください。
				<br>
				<br>(確認したら、検索画面を閉じた状態で「Next」をタップしてください。)
			   </p>`,
		attachTo: {
			element: '.item_1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>レジで打ち間違えた場合、この画面から売上の修正を行う事が出来ます。
			   </p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>この「修正モード」をタップしてONに変更すると、修正用の画面に切り替わります。
				<br>
				<br>今回は説明しませんが、修正が必要となったら修正モードに切り替えて<i class="fa-regular fa-circle-question fa-lg awesome-color-panel-border-same"></i> マークより使い方を確認して下さい。
			   </p>`,
		attachTo: {
			element: '.item_2',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>次に、レジで登録した売上を消す方法を説明します。
			   </p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.nextAndSave
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'><a href='#'><i class='fa-regular fa-trash-can'></i></a> マークをタップすることで売上を消す事が出来ます。
			   </p>`,
		attachTo: {
			element: '.item_0',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.next
			}
		]
	});
	tutorial_9.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>今回はチュートリアルの一環で売上を登録してますので、売上を全て削除して下さい。
			   </p>`,
		attachTo: {
			element: '.item_0',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_9.back
			},
			{
				text: 'Next',
				action: tutorial_9.complete
			}
		]
	});

	const tutorial_10 = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: true,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_10'
	});
	tutorial_10.addStep({
		title: `<p class='tour_header'>チュートリアル</p>`,
		text: `<p class='tour_discription'>全ての売上を削除したら「WebRez+」をタップしてトップメニューに移動して下さい。
				<br>
				<br><span style='font-size:1rem;color:green;'>※進捗を保存しました。</span></p>`,
		buttons: [
			{
				text: 'Next',
				action: tutorial_10.complete
			}
		]
	});


	if(TourMilestone=="tutorial_8"){
		tutorial_9.start(tourFinish,'tutorial','');
	}else if(TourMilestone=="tutorial_9" && 'Updated'=='<?php echo $mode; ?>'){
		tutorial_10.start(tourFinish,'tutorial','save');
	}


	const tutorial_UriageShusei = new Shepherd.Tour({
		useModalOverlay: true,
		defaultStepOptions: {
			classes: 'tour_modal',
			scrollTo: true,
			cancelIcon:{
				enabled:true
			}
		},
		tourName:'tutorial_UriageShusei'
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>売上明細エリアに表示されている内容を一括で修正します。
				<br>青字の項目をタップすることで対象の絞り込みが出来ます。
			   </p>`,
		attachTo: {
			element: '.tour_uri1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>日付、イベント名、商品名等をタップし、修正したいデータのみが表示されている状態にしてください。
			   </p>`,
		attachTo: {
			element: '.tour_uri1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>ここで修正対象と修正値の選択・入力を行います。
			   </p>`,
		attachTo: {
			element: '.tour_uri2',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>例えば、X月X日のイベント名が間違えてた！
				<br>といった場合、上の売上データで日付のみを選択した状態にします。
			   </p>`,
		attachTo: {
			element: '.tour_uri1',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>次に、こちらで「イベント名」にチェックを入れ
				<br>その横の入力欄に本来のイベント名を入力します。
			   </p>`,
		attachTo: {
			element: '.tour_uri3',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>最後に「確認」ボタンをタップすると
				<br>修正対象のデータと修正内容を確認する画面に移動しますので、問題なければ「更新」ボタンをタップします。
				<br><br>間違えていた場合は「キャンセル」ボタンをタップしてください。
			   </p>`,
		attachTo: {
			element: '.tour_uri4',
			on: 'auto'
		},
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Next',
				action: tutorial_UriageShusei.next
			}
		]
	});
	tutorial_UriageShusei.addStep({
		title: `<p class='tour_header'>売上の修正</p>`,
		text: `<p class='tour_discription'>売上げの修正方法については以上となります。
				<br>
				<br>わからない事がありましたらトップ画面下の「お問い合わせ」よりお願いします。
			   </p>`,
		buttons: [
			{
				text: 'Back',
				action: tutorial_UriageShusei.back
			},
			{
				text: 'Finish',
				action: tutorial_UriageShusei.complete
			}
		]
	});    
	function urihelp(){
		tutorial_UriageShusei.start(tourFinish,'urihelp','');
	}
</script>    
</html>
<?php
$EVresult  = null;
$TKresult = null;
$stmt = null;
$pdo_h = null;

?>



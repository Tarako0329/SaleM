<?php
// =========================================================
// オリジナルログ出力(error_log)
// =========================================================
function log_writer($pgname,$msg){
    $log = print_r($msg,true);
    file_put_contents("error_log","[".date("Y/m/d H:i:s")."] ORG_LOG from [".$_SERVER["PHP_SELF"]." -> ".$pgname."] => ".$log."\n",FILE_APPEND);
}
function log_writer2($pgname,$msg,$kankyo){
    //$kankyo:lv0=全環境+メール通知 lv1=全環境 lv2=本番以外 lv3=テスト・ローカル環境のみ
    
    if($kankyo==="lv0"){
        log_writer($pgname,$msg);
        $log = print_r($msg,true);
        
        send_mail(SYSTEM_NOTICE_MAIL,"【重要】".TITLE."でシステムエラー発生",$log);
        
    }else if($kankyo==="lv1"){
        log_writer($pgname,$msg);
    }else if($kankyo==="lv2" && EXEC_MODE!=="Product"){
        log_writer($pgname,$msg);
    }else if($kankyo==="lv3" && (EXEC_MODE==="Test" || EXEC_MODE==="Local" || EXEC_MODE==="TrialL")){
        log_writer($pgname,$msg);
    }else{
        return;
    }
}

// =========================================================
// 数字を3桁カンマ区切りで返す(整数のみ対応)
// =========================================================
function return_num_disp($number) {
    //$return_number = "";
    //$zan_mojisu = 0;
    $return_number = null;
    if(preg_match('/[^0-9]/',$number)==0){//0～9以外が存在して無い場合、数値として処理
        $shori_moji_su = mb_strlen($number) - 3;
        $zan_mojisu = null;
        
        while($shori_moji_su > 0){
            $return_number = $return_number.",".mb_substr($number,$shori_moji_su,3);
            $zan_mojisu = $shori_moji_su;
            $shori_moji_su = $shori_moji_su - 3;
        }
        
        $return_number = mb_substr($number,0,$zan_mojisu).$return_number;
    }else{
        $return_number = $number;
    }
    return $return_number;
}
// =========================================================
// トークンを作成
// =========================================================
function get_token() {
    $TOKEN_LENGTH = 16;//16*2=32桁
    $bytes = openssl_random_pseudo_bytes($TOKEN_LENGTH);
    return bin2hex($bytes);
}
// =========================================================
// トークンの削除(指定のトークン もしくは　期限切れのトークンを一括削除)
// =========================================================
function delete_old_token($token, $pdo) {
    
    $date = new DateTime("- 7 days");
    
    $sql = "DELETE FROM AUTO_LOGIN WHERE TOKEN = ? or REGISTRATED_TIME < ?;";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $token, PDO::PARAM_STR);
    $stmt->bindValue(2, $date->format('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stmt->execute();
    setCookie("webrez_token", '', -1, "/", "", TRUE, TRUE); // secure, httponly
    $_SESSION = array();
}

// =========================================================
// 自動ログイン処理
// =========================================================
function check_auto_login($cookie_token, $pdo) {
    if($_COOKIE["login_type"]==="normal"){//自動ログインしない
        return "一定の期間、操作が行われなかったため、自動ログオフしました。";
    }
    $sql = "SELECT * FROM AUTO_LOGIN WHERE TOKEN = ? AND REGISTRATED_TIME >= ?;";
    $date = new DateTime("- 7 days");   //2週間前の日付を取得
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(1, $cookie_token, PDO::PARAM_STR);
    $stmt->bindValue(2, $date->format('Y-m-d H:i:s'), PDO::PARAM_STR);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($rows) == 1) {//自動ログイン成功
    	$_SESSION['user_id'] = $rows[0]['USER_ID'];
    	return true;
        //return "test msg::自動ログインの有効期限が切れてます。";
    } else {//自動ログイン失敗
    	setCookie("webrez_token", '', -1, "/", "", TRUE, TRUE); // secure, httponly
        setCookie("login_type", "normal", time()+999*999*999, "/", "", TRUE, TRUE); // secure, httponly
    	delete_old_token($cookie_token, $pdo);  //古くなったトークンを削除
        
    	return "自動ログインの有効期限が切れてます。";
    }
}

// =========================================================
// $_SESSION[user_id]の存在チェック
// =========================================================
function check_session_userid($pdo_h){
    if(substr(EXEC_MODE,0,5)==="Trial"){
        if(empty($_COOKIE["user_id"]) && empty($_SESSION["user_id"])){
            //セッション・クッキーのどちらにもIDが無い場合、ID発行を行う
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: TrialDataCreate.php");
            exit(); 
        }else if((!empty($_SESSION["user_id"]) && empty($_COOKIE["user_id"])) || (!empty($_SESSION["user_id"]) && $_COOKIE["user_id"] != $_SESSION["user_id"])){
            //クッキーが空　もしくは　セッションありかつセッション＜＞クッキーの場合
            //クッキーにセッションの値をセットする
            setCookie("user_id", $_SESSION["user_id"], time()+60*60*24, "/", "", TRUE, TRUE);
        }else if(!empty($_COOKIE["user_id"]) && empty($_SESSION["user_id"])){
            //セッションが空の場合、クッキーからIDを取得する
            $_SESSION["user_id"]=$_COOKIE["user_id"];
        }
        
        //取得できたIDがDBに存在するか確認
        $sqlstr="select * from Users where uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if (count($rows) == 0) {
            //IDは取得できたがDB側にデータが無い場合もID再発行
            header("HTTP/1.1 301 Moved Permanently");
            header("Location: TrialDataCreate.php");
            exit();
        }
        
    }else{
        //log_writer2("function.php[func:check_session_userid] $_SESSION values ",$_SESSION,"lv3");
        if(empty($_SESSION["user_id"])){
            //セッションのIDがクリアされた場合の再取得処理。
            if(empty($_COOKIE['webrez_token'])){
                log_writer2("func:check_session_userid","cookieのwebrez_tokenが存在してない。useridの取得手段がないのでログイン画面へ","lv3");
                redirect_to_login("セッションが切れてます。");
                exit();
            }
            $rtn=check_auto_login($_COOKIE['webrez_token'],$pdo_h);
            if($rtn!==true){
                redirect_to_login($rtn);
                exit();
            }
        }
        if(!($_SESSION["user_id"]<>"")){
            //念のための最終チェック
            redirect_to_login("ユーザーＩＤの再取得に失敗しました。[error:1]");
            exit();
        }
        //取得できたUIDがDBに存在するか確認
        $sqlstr="select * from Users where uid=?";
        $stmt = $pdo_h->prepare($sqlstr);
        $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
        if (count($rows) == 0) {
            //IDは取得できたがDB側にデータが無い場合もID再発行
            redirect_to_login("ユーザーＩＤの再取得に失敗しました。[error:2]");
            exit();
        }
    }
    return true;
}

// =========================================================
// $_SESSION[user_id]の存在チェック for ajax
// =========================================================
function check_session_userid_for_ajax($pdo_h){
    $rtn_val = true;
    //$_SESSION["user_id"]=null;
    if(empty($_SESSION["user_id"])){//セッションのIDがクリアされた場合の再取得処理。
        
        if(empty($_COOKIE['webrez_token'])){
            log_writer2("func:check_session_userid_for_ajax","cookie[webrez_token] is nothing、useridの取得手段なし。[login type:".$_COOKIE["login_type"]."]","lv3");
            $rtn_val = false;
        }else{
            $rtn=check_auto_login($_COOKIE['webrez_token'],$pdo_h);
            log_writer2("func:check_session_userid_for_ajax [check_auto_login return value]",$rtn,"lv3");
            if($rtn!==true){
                $rtn_val = false;
            }else{
                if(!($_SESSION["user_id"]<>"")){//念のための最終チェック
                    log_writer2("func:check_session_userid_for_ajax","ユーザーＩＤの再取得に失敗しました。[error:1]","lv3");
                    $rtn_val = false;
                }else{//取得できたUIDがDBに存在するか確認
                    $sqlstr="select * from Users where uid=?";
                    $stmt = $pdo_h->prepare($sqlstr);
                    $stmt->bindValue(1, $_SESSION["user_id"], PDO::PARAM_INT);
                    $stmt->execute();
                    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
                    if (count($rows) === 0) {
                        //IDは取得できたがDB側にデータが無い場合もID再発行
                        log_writer2("func:check_session_userid_for_ajax","ユーザーＩＤの再取得に失敗しました。[error:2]","lv3");
                        $rtn_val = false;
                    }else{
                        $rtn_val = true;
                    }
                }
            }
        }
    }

    return $rtn_val;
}

// =========================================================
// データ更新時のセキュリティ対応（セッション・クッキー・ポストのチェック）
//　一元化 (リファイラ[xxx.php,xxx.php],[S:session,C:cookie,G:get,P:post])
// =========================================================
function csrf_checker($from,$chkpoint=null){
    //リファイラーチェック
    $chkflg=false;
    foreach($from as $row){
        if(false !== strpos($_SERVER['HTTP_REFERER'],ROOT_URL.$row)){
            $chkflg=true;
            log_writer2("func:csrf_checker","HTTP_REFERER success \$_SERVER[".$_SERVER['HTTP_REFERER']."]","lv3");
            log_writer2("func:csrf_checker","HTTP_REFERER success ParamUrl[".ROOT_URL.$row."]","lv3");
            break;
        }
    }
    if($chkflg===true){
        $i=0;
        $csrf="";
        $checked="";
        foreach($chkpoint as $row){
            if($row==="S"){
                $csrf_ck = (!empty($_SESSION["csrf_token"])?$_SESSION["csrf_token"]:"\$_SESSION empty");
                $checked=$checked."S";
                unset($_SESSION['csrf_token']) ; // セッション側のトークンを削除し再利用を防止
            }else if($row==="C"){
                $csrf_ck = (!empty($_COOKIE["csrf_token"])?$_COOKIE["csrf_token"]:"\$_COOKIE empty");
                $checked=$checked."C";
                setCookie("csrf_token", '', -1, "/", "", TRUE, TRUE); // secure, httponly// クッキー側のトークンを削除し再利用を防止
            }if($row==="G"){
                $csrf_ck = (!empty($_GET["csrf_token"])?$_GET["csrf_token"]:"\$_GET empty");
                $checked=$checked."G";
            }if($row==="P"){
                $csrf_ck = (!empty($_POST["csrf_token"])?$_POST["csrf_token"]:"\$_POST empty");
                $checked=$checked."P";
            }
            if($i!==0){
                if($csrf !== $csrf_ck){
                    $chkflg=false;
                    log_writer2("func:csrf_checker","CSRF failed [".$checked."]","lv3");
                    log_writer2("func:csrf_checker","CSRF failed [".$csrf."]","lv3");
                    log_writer2("func:csrf_checker","CSRF failed [".$csrf_ck."]","lv3");
                    $chkflg = "セッションが正しくありません";
                    break;
                }else{
                    log_writer2("func:csrf_checker","CSRF success [".$checked."]","lv3");
                    log_writer2("func:csrf_checker","CSRF success [".$csrf."]","lv3");
                    log_writer2("func:csrf_checker","CSRF success [".$csrf_ck."]","lv3");
                }
            }
            $csrf=$csrf_ck;
            $i++;
        }
    }else{
        log_writer2("func:csrf_checker","HTTP_REFERER failed \$_SERVER[".$_SERVER['HTTP_REFERER']."]","lv3");
        log_writer2("func:csrf_checker","HTTP_REFERER failed ParamUrl[".ROOT_URL.$row."]","lv3");
        $chkflg = "アクセス元が不正です";
    }
    
    return $chkflg;
}


function csrf_create(){
    //INPUT HIDDEN で呼ぶ
    $token = get_token();
    $_SESSION['csrf_token'] = $token;

	//自動ログインのトークンを１週間の有効期限でCookieにセット
    setCookie("csrf_token", $token, time()+60*60*24*2, "/", "", TRUE, TRUE);
    
    return $token;
}

// =========================================================
// 不可逆暗号化
// =========================================================
function passEx($str,$uid,$key){
	if(strlen($str)>0 and !empty($uid)){
		$rtn = crypt($str,$key);
		for($i = 0; $i < 1000; $i++){
			$rtn = substr(crypt($rtn.$uid,$key),2);
		}
	}else{
		$rtn = $str;
	}
	return $rtn;
}
// =========================================================
// 可逆暗号(日本語文字化け対策)
// 22.05.11 商品名の暗号化運用を止めるため、既存関数を無効化。以降、暗号化したい場合はver2を使用する
// =========================================================
function rot13encrypt ($str) {
	//暗号化
    //return str_rot13(base64_encode($str));
    return $str;
}

function rot13decrypt ($str) {
	//暗号化解除
    //return base64_decode(str_rot13($str));
    return $str;
}

function rot13encrypt2 ($str) {
	//暗号化
    //return str_rot13(base64_encode($str)); 復号化するときに文字化けが発生したので変更
    //return bin2hex(openssl_encrypt($str, 'AES-128-ECB', null));
    return bin2hex(openssl_encrypt($str, "AES-128-ECB", "1"));
}
function rot13decrypt2 ($str) {
	//暗号化解除
    //return base64_decode(str_rot13($str)); 復号化するときに文字化けが発生したので変更
    //return openssl_decrypt(hex2bin($str), 'AES-128-ECB', null);
    return openssl_decrypt(hex2bin($str), "AES-128-ECB", "1");
}

// =========================================================
// XSS対策 post get を echo するときに使用
// =========================================================
function secho($s) {
    return htmlspecialchars($s, ENT_QUOTES, "UTF-8");
}

// =========================================================
// テスト環境のみ出力
// =========================================================
function deb_echo($s){
    if(EXEC_MODE=="Test" || EXEC_MODE=="Local"){
        echo $s."<br>";
    }
}
// =========================================================
// PDO の接続オプション取得
// =========================================================
function get_pdo_options() {
  return array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
               PDO::MYSQL_ATTR_MULTI_STATEMENTS => false,   //sqlの複文禁止 "select * from hoge;delete from hoge"みたいなの
               PDO::ATTR_EMULATE_PREPARES => false);        //同上
}


// =========================================================
// メール送信 
// =========================================================
function send_mail($to,$subject,$body){
	//$to		: 送信先アドレス
	//$subject	: 件名
	//$body		: 本文

	//SMTP送信
    if(EXEC_MODE==="Local"){
        log_writer2("\$body",$body,"lv3");
        return true;
    }

    require_once('qdmail.php');
    require_once('qdsmtp.php');

    $mail = new Qdmail();
    $mail -> smtp(true);
    $param = array(
        'host'=> HOST,
        'port'=> PORT ,
        'from'=> FROM,
        'protocol'=>PROTOCOL,
    	'pop_host'=>POP_HOST,
    	'pop_user'=>POP_USER,
    	'pop_pass'=>POP_PASS,
    );
    $mail->smtpServer($param);
    $mail->charsetBody('UTF-8','base64');
    $mail->kana(true);
    $mail->errorDisplay(false);
    $mail->smtpObject()->error_display = false;
    $mail->logLevel(1);
	//$mail->logPath('./log/');
	//$mail->logFilename('anpi.log');
	//$smtp ->timeOut(10);
	
    $mail ->to($to);
    $mail ->from(FROM , 'WEBREZ-info');
    $mail ->subject($subject);
    $mail ->text($body);

    //送信
    $return_flag = $mail ->send();
    return $return_flag;
}
// =========================================================
// メール送信 
// =========================================================
function send_htmlmail($to,$subject,$body){
	//$to		: 送信先アドレス
	//$subject	: 件名
	//$body		: 本文

	//SMTP送信
    if(EXEC_MODE==="Local"){
        log_writer2("\$body",$body,"lv3");
        return true;
    }

    require_once('qdmail.php');
    require_once('qdsmtp.php');

    $mail = new Qdmail();
    $mail -> smtp(true);
    mb_language('ja');
    mb_internal_encoding('UTF-8');
    $param = array(
        'host'=> HOST,
        'port'=> PORT ,
        'from'=> FROM,
        'protocol'=>PROTOCOL,
    	'pop_host'=>POP_HOST,
    	'pop_user'=>POP_USER,
    	'pop_pass'=>POP_PASS,
    );
    $mail->smtpServer($param);
    $mail->charsetBody('UTF-8','base64');
    $mail->kana(true);
    $mail->errorDisplay(false);
    $mail->smtpObject()->error_display = false;
    $mail->logLevel(1);
	//$mail->logPath('./log/');
	//$mail->logFilename('anpi.log');
	//$smtp ->timeOut(10);
	
    $mail ->to($to);
    $mail ->from(FROM , 'WEBREZ-info');
    $mail ->subject($subject);
    $mail ->html($body);

    //送信
    $return_flag = $mail ->send();
    return $return_flag;
}

// =========================================================
// GUID取得
// =========================================================
function getGUID(){
    if (function_exists('com_create_guid')){
        return com_create_guid();
    }else {
        mt_srand((int)microtime()*10000);//optional for php 4.2.0 and up.
        $charid = strtoupper(md5(uniqid(rand(), true)));
        $hyphen = chr(45);// "-"
        $uuid = chr(123)// "{"
            .substr($charid, 0, 8).$hyphen
            .substr($charid, 8, 4).$hyphen
            .substr($charid,12, 4).$hyphen
            .substr($charid,16, 4).$hyphen
            .substr($charid,20,12)
            .chr(125);// "}"
        return $uuid;
    }
}

// =========================================================
// CSV出力
// =========================================================
function output_csv($data,$kikan){
    $date = date("Ymd");
    
    
    // データ行の文字コード変換・加工
    foreach ($data as $data_key => $line) {
        foreach ($line as $line_key => $value) {
            $data[$data_key][$line_key] = mb_convert_encoding($value, "SJIS", "UTF-8");
        }
    }

    header("Content-Type: application/octet-stream");
    header("Content-Disposition: attachment; filename=webrez_uriage_{$date}_{$kikan}.csv");
    foreach ($data as $key => $line) {
        echo implode(",", $line ) . "\r\n";
    }
    exit;
}
// =========================================================
// XLSX出力
// =========================================================
function output_xlsx($data,$kikan){
    // ファイル名
    $temp_file = 'template/freee.xlsx';
    $gen_file = 'template/freee_'.date("Ymd").'_'.$kikan.'.xlsx';


    // テンプレートファイルを読み込み
    $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($temp_file);
    $worksheet = $spreadsheet->getActiveSheet();

    // 書き込み
    $worksheet->fromArray($data, null, 'A2');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;'); 
    header("Content-Disposition: attachment; filename=\"{$gen_file}\"");
    header('Cache-Control: max-age=0'); 
    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
exit();
}

// =========================================================
// 日付未指定時にルールに沿ってYMDを返す
// =========================================================
function rtn_date($date,$mode){
    //rtn_date(empty($date),$mode)
    //$date:チェックする日付　$mode:日付が空白の場合　today=今日　min=0000-00-00 max=2999-12-31 を返す
    
    if($date==false){
        //何かしら入ってる
        $rtn_date = (string)$date;
    }elseif($mode=="today"){
        $rtn_date = (string)date("Y-m-d");
    }elseif($mode=="min"){
        $rtn_date = "0000-00-00";
    }elseif($mode=="max"){
        $rtn_date = "2999-12-31";
    }else{
        $rtn_date = "";
    }
    
    return $rtn_date;
}

// =========================================================
// 検索ワード未指定時にワイルドカード(%)を返す
// =========================================================
function rtn_wildcard($word){
    //rtn_wildcard(empty($word))で使用する
    if($word==true){
        //空白の場合
        return "%";
    }else{
        return $word;
    }
}


// =========================================================
// 登録メール(メールサーバーを使わない場合PHPから送信)
// =========================================================
function touroku_mail($to,$subject,$body){
    $mail2=rot13encrypt2($to);
    $s_name=$_SERVER['SCRIPT_NAME'];
    $dir_a=explode("/",$s_name,-1);
    
    // 送信元
    $from = "From: テスト送信者<information@WEBREZ.jp>";
    
    // メールタイトル
    $subject = "WEBREZ＋ 登録案内";
    
    // メール送信
    mail($to, $subject, $body, $from);
    return 1;
}

function get_getsumatsu($ym){
    if(strlen($ym)<>6){
        return $ym;
    }
    $yyyymm = substr($ym,0,4)."-".substr($ym,4,2);
    
    return date('Y-m-d',strtotime($yyyymm.' last day of this month'));
}


// =========================================================
// ログイン画面へ飛ばす
// =========================================================
function redirect_to_login($message) {
	$_SESSION = array();
	session_destroy();
	session_start();
    if(EXEC_MODE!=="Local"){
        //session_regenerate_id(true);
    }
    setCookie("login_type", "", -1, "/", "", TRUE, TRUE);
    setCookie("webrez_token", "", -1, "/", "", TRUE, TRUE);
    setCookie("csrf_token", "", -1, "/", "", TRUE, TRUE);

    $_SESSION["EMSG"] = $message;
    log_writer2("function.php[func:redirect_to_login] \$_SESSION values ",$_SESSION,"lv3");

    header("HTTP/1.1 301 Moved Permanently");
    header("Location: index.php");
    exit();
}

function sort_hash($val,$type){
    $rtn="";
    $hashids = new Hashids\Hashids('this is salt',6);
    if($type==="enc"){
        $rtn = $hashids->encode($val);    
    }else if($type==="dec"){
        $tmp = $hashids->decode($val);
        $rtn = $tmp[0];
    }else{

    }

    return $rtn;
}

function sqllogger($logsql,$e){//(sqlログ,Exception $e:$eセット時はメール通知あり)
    //SQL文はトランザクション単位で共通ログファイルに書き込みを行う。
    //エラーをキャッチした場合、ユーザーID別のログファイルにも書き込みを行う。
    $logfilename="esql_sid_".$_SESSION['user_id'].".log";
    $userid = (!empty($_SESSION['user_id'])?$_SESSION['user_id']:"-");
    $callphp = debug_backtrace();
    $phpname = substr($callphp[0]["file"], (strrpos($callphp[0]["file"],"\\") +1));

    if(!empty($logsql)){
        file_put_contents("sql_log/".date("Y-m-d").".log", $logsql,FILE_APPEND);
    }
    if(!empty($e)){//主にロールバック時
        $elog = print_r($e,true);
        $eMsg = date("Y-m-d H:i:s")."\t".$userid."\t".$phpname."\t"."/*".$e->getMessage()."*/\n";
        file_put_contents("sql_log/".date("Y-m-d").".log", $eMsg, FILE_APPEND);

        file_put_contents("sql_log/".$logfilename,$logsql,FILE_APPEND);
        file_put_contents("sql_log/".$logfilename,"/*".$elog."*/\n",FILE_APPEND);
        log_writer2($phpname." [Exception \$e] =>",$e,"lv0");
    }
    
}

function rtn_sqllog($sql,$params){//(sql,パラメータ[],phpファイル名)w:書き込み r:整形SQLリターン
    $logsql=$sql.";";
    $i=0;
    $userid = (!empty($_SESSION['user_id'])?$_SESSION['user_id']:"-");
    $callphp = debug_backtrace();
    $phpname = substr($callphp[0]["file"], (strrpos($callphp[0]["file"],"\\") +1));

    if(strstr($logsql,"?")!==false){
        while(strstr($logsql,"?")!==false){
            $logsql = strstr($logsql,"?",true).(!is_null($params[$i])?"\"".$params[$i]."\"":"null").substr(strstr($logsql,"?"), ((strlen(strstr($logsql,"?"))-1)*(-1))) ;
            $i++;
        }
    }else{
        foreach(array_keys($params) as $row){
            $logsql = str_replace(":".$row,(!is_null($params[$row])?"\"".$params[$row]."\"":"null"),$logsql);
        }
    }
    return date("Y-m-d H:i:s")."\t".$userid."\t".$phpname."\t".$logsql."\n";
}

// =========================================================
// fatal error　実行関数
// =========================================================
function shutdown_ajax($filename){
	// シャットダウン関数
	// スクリプトの処理が完了する前に
	// ここで何らかの操作をすることができます
	// トランザクション中のエラー停止時は自動rollbackされる。
	  $lastError = error_get_last();
	  
	  //直前でエラーあり、かつ、catch処理出来ていない場合に実行
	  if($lastError!==null && $GLOBALS["reseve_status"] === false){
		log_writer2($filename,"shutdown","lv3");
		log_writer2($filename,$lastError,"lv1");
		  
		$emsg = "uid::".$_SESSION['user_id']." ERROR_MESSAGE::予期せぬエラー".$lastError['message'];
		if(EXEC_MODE!=="Local"){
			send_mail(SYSTEM_NOTICE_MAIL,"【".TITLE." - WARNING】".$filename."でシステム停止",$emsg,"","");
		}
		log_writer2($filename." [Exception \$lastError] =>",$lastError,"lv0");
	
		$token = csrf_create();
		$return_sts = array(
			"MSG" => "システムエラーによる更新失敗。管理者へ通知しました。"
			,"status" => "danger"
			,"csrf_create" => $token
			,"timeout" => false
		);
		header('Content-type: application/json');
		echo json_encode($return_sts, JSON_UNESCAPED_UNICODE);
	  }
  }
// =========================================================
// GeminiAPI
// =========================================================
function gemini_api($p_ask,$p_type, $response_schema = null){
	//$p_type: 'json' (decode response to PHP array) or 'plain' (raw text response) or 'html' or 'php'
	//$response_schema: An object describing the expected JSON schema for the model's response.
	/*$response_schemaサンプル
	  $response_schema = [
        'type' => 'object',
        'properties' => [
            'check_results' => [
                'type' => 'object',
                'properties' => [
                    '自動返信' => ['type' => 'string', 'description' => '自動返信メールのチェック結果'],
                    '受付確認' => ['type' => 'string', 'description' => '受付確認メールのチェック結果'],
                    '支払確認' => ['type' => 'string', 'description' => '支払確認メールのチェック結果'],
                    '発送連絡' => ['type' => 'string', 'description' => '発送連絡メールのチェック結果'],
                    'キャンセル受付' => ['type' => 'string', 'description' => 'キャンセル受付メールのチェック結果'],
                ],
                'required' => ['自動返信', '受付確認', '支払確認', '発送連絡', 'キャンセル受付']	//必須項目
            ]
        ],
        'required' => ['check_results']	//必須項目
    ];
	*/
	$url = GEMINI_URL.GEMINI;
	$request_payload =  [
		'contents' => [
			[
                'role' => 'user',
				'parts' => [
					['text' => $p_ask]
				]
			]
		]
	];
	if ($response_schema !== null) {
		// Ensure generationConfig exists
		if (!isset($request_payload['generationConfig'])) {
			$request_payload['generationConfig'] = [];
		}
		$request_payload['generationConfig']['responseMimeType'] = 'application/json';
		$request_payload['generationConfig']['responseSchema'] = $response_schema;
	}
	
    $_SESSION["talk_id_".$_SESSION["user_id"]][] = $request_payload;

	$options = [
		'http' => [
			'method' => 'POST',
			'header' => [
				'Content-Type: application/json',
			],
			'content' => json_encode($_SESSION["talk_id_".$_SESSION["user_id"]]),
		],
	];
	
	$context = stream_context_create($options);
	$response = file_get_contents($url, false, $context);
	
	$emsg = "";
	$result = "";
	if ($response === false) {
		$emsg = 'Gemini呼び出しに失敗しました。時間をおいて、再度実行してみてください。';
	}else{
		$result_decoded = json_decode($response, true);
		//log_writer2(" [gemini_api \$result_decoded] =>",$result_decoded,"lv3");
		if (isset($result_decoded['candidates'][0]['content']['parts'][0]['text'])) {
			$result = $result_decoded['candidates'][0]['content']['parts'][0]['text'];
			// finishReasonのチェックを追加
			if (isset($result_decoded['candidates'][0]['finishReason']) && $result_decoded['candidates'][0]['finishReason'] !== 'STOP') {
				$emsg .= 'Geminiの応答が途中で終了した可能性があります。理由: ' . $result_decoded['candidates'][0]['finishReason'];
			}
		} elseif (isset($result_decoded['error'])) {
			$emsg = "Gemini API Error: " . $result_decoded['error']['message'];
			$result = json_encode($result_decoded['error']); // Store error details as JSON string
		} else {
			$emsg = 'Geminiからの予期しない応答形式です。';
			$result = $response; // Store raw response
		}
	}

    
	if($p_type==="json"){
		$result = str_replace('```json','',$result);
		$result = str_replace('```','',$result);
		$result = str_replace("\r\n","",$result);
		$result = str_replace("\n","",$result);
		$result = str_replace("\r","",$result);
		$result = str_replace(" ","",$result);
		
		// Only decode if there's no pre-existing error message from the API call itself
		if (empty($emsg)) {
			$decoded_json = json_decode($result, true);
			if (json_last_error() !== JSON_ERROR_NONE) {
				$emsg = 'Geminiが返したテキストのJSONデコードに失敗しました: ' . json_last_error_msg() . ". Raw text: " . $result;
			}
			$result = $decoded_json;
		}	
	}else if($p_type==="html"){
		$result = str_replace('```html','',$result);
		$result = str_replace('```','',$result);
	}else if($p_type==="php"){
		$result = str_replace('```php','',$result);
		$result = str_replace('```','',$result);
	}
	$rtn = array(
		'emsg' => $emsg,
		'result' => $result
	);
	
	return $rtn;
}
function gemini_api_kaiwa($p_ask,$p_type,$p_subject){
	//$p_type:json or plain
	//$_SESSION[$p_subject][] に会話履歴を格納
	log_writer2(" [gemini_api_kaiwa \$p_ask] =>",$p_ask,"lv3");
	
	$url = GEMINI_URL.GEMINI;

	// 現在のユーザー入力を会話履歴に追加
	$_SESSION[$p_subject][] = [
		'role' => 'user',
		'parts' => [
			['text' => $p_ask]
		]
	];
	$data = [
		'contents' => $_SESSION[$p_subject]
		// 必要に応じて safety_settings や generation_config もここに追加
	];
	
	$options = [
		'http' => [
			'method' => 'POST',
			'header' => [
				'Content-Type: application/json',
			],
			'content' => json_encode($data),
		],
	];
	
	$context = stream_context_create($options);
	$response = file_get_contents($url, false, $context);
	
	$emsg = "";
	$result = "";
	if ($response === false) {
		$emsg = 'Gemini呼び出しに失敗しました。時間をおいて、再度実行してみてください。';
	}else{
		$result_decoded = json_decode($response, true);
		if (isset($result_decoded['candidates'][0]['content']['parts'][0]['text'])) {
			$result = $result_decoded['candidates'][0]['content']['parts'][0]['text'];
			// finishReasonのチェックを追加
			if (isset($result_decoded['candidates'][0]['finishReason']) && $result_decoded['candidates'][0]['finishReason'] !== 'STOP') {
				$emsg .= 'Geminiの応答が途中で終了した可能性があります。理由: ' . $result_decoded['candidates'][0]['finishReason'];
			}
		} else {
			$emsg = 'Geminiからの予期しない応答形式です。';
			$result = $response; // Store raw response
		}
	}

	// Geminiの応答を会話履歴に追加
	$_SESSION[$p_subject][] = [
		'role' => 'model',
		'parts' => [
			['text' => $result]
		]
	];

	if($p_type==="json"){
		$result = str_replace('```json','',$result);
		$result = str_replace('```','',$result);
		$result = str_replace("\r\n","",$result);
		$result = str_replace("\n","",$result);
		$result = str_replace("\r","",$result);
		$result = str_replace(" ","",$result);
		
		$result = json_decode($result, true);
	}else{
		//$result = $response;
	}

	$rtn = array(
		'emsg' => $emsg,
		'result' => $result
	);
	//log_writer2(" [gemini_api_kaiwa \$rtn] =>",$rtn,"lv3");
	return $rtn;
}

function countGeminiTokensWithCurl(array $parts): ?int
{
    /*
        echo "--- テキストのみのトークンカウント (cURL) ---\n";
        $textParts = [
            ['text' => 'こんにちは、Gemini APIについて質問があります。']
        ];
        $tokenCountText = countGeminiTokensWithCurl($textParts);

        if ($tokenCountText !== null) {
            echo "テキストのトークン数: " . $tokenCountText . "\n\n";
        }
    */

    $url = GEMINI_URL_TOKEN.GEMINI;
           

    $payload = [
        'contents' => [
            [
                'parts' => $parts
            ]
        ]
    ];

    $jsonPayload = json_encode($payload);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // レスポンスを文字列として取得
    curl_setopt($ch, CURLOPT_POST, true);           // POSTリクエスト
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonPayload); // POSTデータの指定
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonPayload)
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);

    curl_close($ch);

    if ($response === false) {
        echo "cURLエラー: " . $curlError . "\n";
        return null;
    }

    if ($httpCode !== 200) {
        echo "APIエラー（HTTPコード: {$httpCode}）: " . $response . "\n";
        return null;
    }

    $body = json_decode($response, true);

    if (isset($body['totalTokens'])) {
        return $body['totalTokens'];
    } else {
        echo "エラー: レスポンスに 'totalTokens' が見つかりません。\n";
        return null;
    }
}

?>
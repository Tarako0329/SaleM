<?php
// =========================================================
// オンラインリンク　設定ファイル
// =========================================================



// =========================================================
// 不可逆暗号化
// =========================================================
function passEx($str,$uid,$key){
	if(strlen($str)<=8 and !empty($uid)){
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
// =========================================================
function rot13encrypt ($str) {
	//暗号化
    return str_rot13(base64_encode($str));
}

function rot13decrypt ($str) {
	//暗号化解除
    return base64_decode(str_rot13($str));
}

// =========================================================
// バージョン差分修正SQL実行
// =========================================================
function updatedb($SV, $USER, $PASS, $DBNAME,$version,$comment){
    //引数；サーバ、ID、パス、DB名、PGバージョン
    //版管理ルール
    //1.000
    //1：大規模改善
    //0.01～0.99：小規模改善
    //0.001～0.009：バグ改修
    $mysqli_fc = new mysqli($SV, $USER, $PASS, $DBNAME);
    
    $sqlstr="select max(version) as version from version;";
    $result = $mysqli_fc->query( $sqlstr );
    $row_cnt = $result->num_rows;
    $row = $result->fetch_assoc(); 


    if((double)$row["version"]<1.010 && (double)$row["version"]<(double)$version){//DBのバージョン＜PGのバージョン
        //差分SQL実行
        echo $row["version"]." now version no<br>";
        echo (string)$version." version up complete!! <br>";
        $sqlstr = "insert into version values(1.01,'index.php追加/nセキュリティ向上/nメンテナンス性向上/nお釣り電卓機能追加 他');";
	    $stmt = $mysqli_fc->query("LOCK TABLES version WRITE");
	    $stmt = $mysqli_fc->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli_fc->query("UNLOCK TABLES");
    }
    
    if((double)$row["version"]<1.020 && (double)$row["version"]<(double)$version){//DBのバージョン＜PGのバージョン
        //差分SQL実行
        //echo (string)$version."<br>";
        
        $sqlstr = "ALTER TABLE `ShouhinMS` CHANGE `hyoujiKBN1` `hyoujiKBN1` VARCHAR(2) NULL DEFAULT NULL COMMENT 'レジ表示';";
	    $stmt = $mysqli_fc->query("LOCK TABLES ShouhinMS WRITE");
	    $stmt = $mysqli_fc->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli_fc->query("UNLOCK TABLES");
        
        $sqlstr = "update ShouhinMS set hyoujiKBN1='';";
	    $stmt = $mysqli_fc->query("LOCK TABLES ShouhinMS WRITE");
	    $stmt = $mysqli_fc->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli_fc->query("UNLOCK TABLES");

        $sqlstr = "insert into version values(1.02,'売上確認画面、商品登録画面の改善　他');";
	    $stmt = $mysqli_fc->query("LOCK TABLES version WRITE");
	    $stmt = $mysqli_fc->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli_fc->query("UNLOCK TABLES");
	    echo (string)$version." version up complete!! <br>";
    }

    if((double)$row["version"]<1.030 && (double)$row["version"]<(double)$version){//DBのバージョン＜PGのバージョン
        //差分SQL実行
        echo $row["version"]." now version no<br>";
        echo (string)$version." version up complete!! <br>";
        $sqlstr = "insert into version values(1.03,'セキュリティ向上　デザインの統一　メンテナンス性向上　他');";
	    $stmt = $mysqli_fc->query("LOCK TABLES version WRITE");
	    $stmt = $mysqli_fc->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli_fc->query("UNLOCK TABLES");
    }

    if((double)$row["version"]<1.032 && (double)$row["version"]<(double)$version){//DBのバージョン＜PGのバージョン
        //差分SQL実行
        echo $row["version"]." now version no<br>";
        echo (string)$version." version up complete!! <br>";
        $sqlstr = "insert into version values(1.031,'".$comment."');";
	    $stmt = $mysqli_fc->query("LOCK TABLES version WRITE");
	    $stmt = $mysqli_fc->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli_fc->query("UNLOCK TABLES");
    }

    if((double)$row["version"]<1.04 && (double)$row["version"]<(double)$version){//DBのバージョン＜PGのバージョン
        //差分SQL実行
        //商品マスタの税区分桁数増
        $sqlstr = "ALTER TABLE `ShouhinMS` CHANGE `zeiKBN` `zeiKBN` INT(5) NOT NULL;";
	    $stmt = $mysqli_fc->query("LOCK TABLES ShouhinMS WRITE");
	    $stmt = $mysqli_fc->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli_fc->query("UNLOCK TABLES");
	    
        echo $row["version"]." now version no<br>";
        echo (string)$version." version up complete!! <br>";
        $sqlstr = "insert into version values(1.04,'".$comment."');";
	    $stmt = $mysqli_fc->query("LOCK TABLES version WRITE");
	    $stmt = $mysqli_fc->prepare($sqlstr);
	    $stmt->execute();
	    $stmt = $mysqli_fc->query("UNLOCK TABLES");
    }

    $ver="version ".(string)$row["version"];
    //echo $ver."<br>";
    
    $mysqli_fc->close();
}


?>



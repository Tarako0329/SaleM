<?php
    require "php_header.php";

    $sqlstr = "select * from UriageData_GioWeather order by ins_datetime desc";
    $stmt = $pdo_h->prepare($sqlstr);
    
    $stmt->execute();
    $result = $stmt->fetchall(PDO::FETCH_ASSOC);


    foreach($result as $row){
        $url = 'https://mreversegeocoder.gsi.go.jp/reverse-geocoder/LonLatToAddress?lat='.$row["lat"].'&lon='.$row["lon"];
        $RESULT = json_decode(file_get_contents($url), true);
        print_r($RESULT["results"]["muniCd"]);
        print_r($RESULT["results"]["lv01Nm"]);
        
        $stmt = $pdo_h->prepare("update UriageData_GioWeather set MUNI = '".$RESULT["results"]["muniCd"]."', address = '".$RESULT["results"]["lv01Nm"]."' where uid=".$row["uid"]." and UriNo =".$row["UriNo"]);
        $stmt->execute();
        //print_r(json_encode(file_get_contents($url), JSON_UNESCAPED_UNICODE));
        //break;
        usleep(500000);
    }
    


?>
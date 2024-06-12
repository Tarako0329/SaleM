<?php
$url = 'https://mreversegeocoder.gsi.go.jp/reverse-geocoder/LonLatToAddress?lat=43.0686718333333&lon=141.351173694444';

print_r(get_headers($url));

print_r(get_headers($url, true));
?>
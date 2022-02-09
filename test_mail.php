<?php
function touroku_mail($mail){
$mail2=rot13encrypt($mail);
// 送信元
$from = "From: テスト送信者<no-reply@webrez.com>";
 
// メールタイトル
$subject = "WEBREZ＋ 登録案内";
 
// メール本文
$body = <<< "EOM"
WEBREZ+（ウェブレジプラス）にご興味をもっていただきありがとうございます。
こちらのURLから登録をお願いいたします。

https://green-island.mixh.jp/SaleM/TEST/account_create.php?mode=0&acc=$mail2
EOM;
 
// メール送信
mail($mail, $subject, $body, $from);
return 0;
}
?>
 
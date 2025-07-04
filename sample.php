<?php
    require "php_header.php";

    $textParts = [
        ['text' => "このガイドでは、Gemini API のさまざまな課金オプションの概要、課金の有効化方法と使用状況のモニタリング方法、課金に関するよくある質問（FAQ）の回答について説明します。"]
    ];
    $token_count = countGeminiTokensWithCurl($textParts);

    log_writer2("\$token_count",$token_count,"lv3");

?>
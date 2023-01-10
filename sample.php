<?php

function shutdown()
{
    // これがシャットダウン関数で、
    // スクリプトの処理が完了する前に
    // ここで何らかの操作をすることができます
		$lastError = error_get_last();
		echo "stop:".$lastError;
}

register_shutdown_function('shutdown');

ini_set("max_execution_time",5); 
sleep(10);
echo "end";
?>
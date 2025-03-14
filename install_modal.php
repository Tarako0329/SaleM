<?php
  //ファビコンパス
	$icon="img/icon-192x192.png";
  /*
    JSで起動時に実行
  	if (window.matchMedia('(display-mode: standalone)').matches) {
			// PWAとして起動された場合の処理
		} else {
			//alert('ブラウザとして起動されました');
			const userAgent = navigator.userAgent;
  		if (
  		  userAgent.indexOf('Windows') !== -1 ||
  		  userAgent.indexOf('Macintosh') !== -1 ||
  		  userAgent.indexOf('Linux') !== -1
  		) {
  		  // パソコン.なにもしない
  		} else {
  		  // パソコン以外。インストールを勧める
				document.getElementById("pwa_info_btn").click()
  		}

		}
  */
?>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=browser_updated" />
<button style='display:none;' data-bs-toggle='modal' data-bs-target='#pwa_info' id='pwa_info_btn'>pwa_info</button>
<div class='modal fade' id='pwa_info' tabindex='-1' role='dialog' aria-labelledby='basicModal' aria-hidden='true'>
	<div class='modal-dialog  modal-dialog-centered'>
		<div class='modal-content' style=''>
				<div class='modal-header'>
    			<h5 class="modal-title">インストール手順</h5>
    			<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class='modal-body container'>
					<div class="row">
						<div class='col-12 fs-6'>
							<p class='mb-1'>ご登録ありがとうございます。</p>
							<p>下記手順でインストールすることで、アプリケーション風に利用可能となります。(ブラウザでも利用可能です)</p>
						</div>
					</div>
					<ul class="nav nav-underline">
					  <li class="nav-item">
					    <a class="nav-link active" href="#" data-bs-toggle="tab" data-bs-target="#iphone">iPhone/Safari</a>
					  </li>
					  <li class="nav-item">
					    <a class="nav-link" href="#" data-bs-toggle="tab" data-bs-target="#android">Android/Chrome</a>
					  </li>
					</ul>
					<div class='tab-content'>
						<div class='row ps-3 pt-3 tab-pane active' style='font-size:12px;' id='iphone'>
							<p>当アプリはPWAという技術を使い、APPストアを介さずにアプリケーション風に利用可能となってます。</p>
							<ol>
							  <li>
							    <p class='mb-0'>共有ボタンをタップ</p>
							    <p class='mb-0'>画面下部にある共有ボタン<i class="bi bi-box-arrow-up ms-1 me-1 fs-5"></i>をタップします。</p>
							  </li>
							  <li>
							    <p class='mb-0 mt-2'>ホーム画面に追加をタップ</p>
							    <p class='mb-0'>表示されたメニューから「ホーム画面に追加<i class="bi bi-plus-square ms-2 me-1 fs-5"></i>」をタップします。</p>
							  </li>
							  <li>
							    <p class='mb-1 mt-2'>追加をタップ</p>
							    <p class='mb-0'>サイト名とアイコンを確認し、「追加」をタップします。</p>
							  </li>
							  <li>
							    <p class='mb-1 mt-2'>インストール完了</p>
							    <p>ホーム画面にアプリケーションのアイコンが追加されます。</p>
							    <div style='width:80px;'><img src="<?php echo $icon;?>" alt="ホーム画面に追加されたアイコン" style='width:100%;'></div>
							  </li>
							</ol>
						</div>
						<div class='row ps-3 pt-3 tab-pane' style='font-size:12px;' id='android'>
							<ol>
							  <li>
							    <p class='mb-1'>インストールアイコンをタップ</p>
							    <p>アドレスバーに表示されるインストールアイコン<span class="material-symbols-outlined ms-1 me-1 fs-5">browser_updated</span>をタップします。</p>
							  </li>
							  <li>
							    <p class='mb-1 mt-3'>インストールを確認</p>
							    <p>インストール確認のポップアップが表示されるので、「インストール」をタップします。</p>
							  </li>
							  <li>
							    <p class='mb-1 mt-3'>インストール完了</p>
							    <p>ホーム画面にアプリケーションのアイコンが追加されます。</p>
									<div style='width:80px;'><img src="<?php echo $icon;?>" alt="ホーム画面に追加されたアイコン" style='width:100%;'></div>
							  </li>
							</ol>
						</div>
					</div>
				</div>
				<div class='modal-footer'>
					<button type='button' class="btn btn-secondary mbtn" data-bs-dismiss="modal" >閉じる</button>
				</div>
		</div>
	</div>
</div>
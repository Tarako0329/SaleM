self.addEventListener('install', function(e) {
  console.log('[ServiceWorker] Install hoge');
});

self.addEventListener('activate', function(e) {
  console.log('[ServiceWorker] Activate hoge');
});

// サービスワーカー有効化に必須
self.addEventListener('fetch', function(event) {});

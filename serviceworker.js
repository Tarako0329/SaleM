// キャッシュするリソース(css、jsがあれば個別で追加)
var resourcesToCache = [
  '/css'
  ,'/shepherd'
  ,'/img'
  ,'/script'
];
// キャッシュバージョン
var CACHE_VERSION = 'ca-v_20220701';

//キャッシュの保存
self.addEventListener('install', function(e) {
    console.log('[ServiceWorker] Install hoge');

    caches.open(CACHE_VERSION)
      .then(function(cache) {
        console.log('cache.addAll');
        cache.addAll(resourcesToCache);
      });
});

self.addEventListener('activate', function(e) {
  console.log('[ServiceWorker] Activate hoge');
});

// サービスワーカー有効化に必須
self.addEventListener('fetch', function(event) {});

/*
const CACHE_VERSION = 'v2';
const CACHE_NAME = `${registration.scope}!${CACHE_VERSION}`;

// キャッシュするファイルをセットする
const urlsToCache = [
  '.'
  ,'/css'
  ,'/shepherd'
  ,'/img'
  ,'/EVregi.php?evrez'
  ,'/EVregi.php?kobetu'
  ,'/menu.php'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    // キャッシュを開く
    caches.open(CACHE_NAME)
    .then((cache) => {
      // 指定されたファイルをキャッシュに追加する
      console.log('[ServiceWorker] Install hoge');
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener('activate', (event) => {
    console.log('[ServiceWorker] Activate hoge');
    event.waitUntil(
        caches.keys().then((cacheNames) => {
            return cacheNames.filter((cacheName) => {
            // このスコープに所属していて且つCACHE_NAMEではないキャッシュを探す
            return cacheName.startsWith(`${registration.scope}!`) &&
                   cacheName !== CACHE_NAME;
        });
        }).then((cachesToDelete) => {
            return Promise.all(cachesToDelete.map((cacheName) => {
            // いらないキャッシュを削除する
            return caches.delete(cacheName);
            }));
        })
    );
});

self.addEventListener('fetch', (event) => {

    event.respondWith(
        caches.match(event.request)
        .then((response) => {
            // キャッシュ内に該当レスポンスがあれば、それを返す
            if (response) {
                console.log('[ServiceWorker] fetch return cache');
                return response;
            }
            // 重要：リクエストを clone する。リクエストは Stream なので
            // 一度しか処理できない。ここではキャッシュ用、fetch 用と2回
            // 必要なので、リクエストは clone しないといけない
            let fetchRequest = event.request.clone();

            return fetch(fetchRequest)
            .then((response) => {
                if (!response || response.status !== 200 || response.type !== 'basic') {
                    // キャッシュする必要のないタイプのレスポンスならそのまま返す
                    console.log('[ServiceWorker] fetch return http');
                    return response;
                }
                //return response;

            
                // 重要：レスポンスを clone する。レスポンスは Stream で
                // ブラウザ用とキャッシュ用の2回必要。なので clone して
                // 2つの Stream があるようにする
                let responseToCache = response.clone();

                caches.open(CACHE_NAME)
                .then((cache) => {
                    cache.put(event.request, responseToCache);
                });
                console.log('[ServiceWorker] fetch return cache&update');
                return response;
            
            });
        })
    );

});
*/
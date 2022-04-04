<!DOCTYPE html>
<html lang="ja">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>BootStrap Sample</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js" integrity="sha512-QSkVNOCYLtj73J4hbmVoOV6KVZuMluZlioC+trLpewV8qMjsWqlIQvkn1KGX2StWvPMdWGBqim1xlC8krl1EKQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>    
     <script>
window.onload = function() {     
 var ctx = document.getElementById('myChart').getContext('2d');
 var chart = new Chart(ctx, {
     type: 'line',
 
     // データを指定
     data: {
         labels: ['月', '火', '水', '木', '金', '土', '日'],
         datasets: [{
             label: 'dataset example',
             borderColor: 'rgb(75, 192, 192)',
             fill: false,
             data: [10, 2, 5, 4, 6, 7, 11]
         }]
     },
 
     // 設定はoptionsに記述
     options: {
       //タイトル
       title: {
         display: true,
         text: '線グラフの例'
       }
     }
})
};
</script>
</head>
<body>
    <canvas id="myChart"></canvas>
</body>
</html>
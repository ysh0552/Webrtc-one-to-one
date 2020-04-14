<?php include __DIR__ . '/../config.php'?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title>海王星辰远程审方</title>
    <link rel="stylesheet" type="text/css" href="assets/css/video.css"/>
</head>
<body>

<h1 class="title">海王星辰远程审方</h1>
<div class="box-container">
    <ul class="window-list" id="root">
   
    </ul>

</div>

<script src="assets/js/jquery-3.2.1.min.js"></script>

</body>
<script type="text/javascript">


const NotificationInstance = Notification || window.Notification;
if (!!NotificationInstance) {
    const permissionNow = NotificationInstance.permission;
    if (permissionNow === 'granted') {//允许通知
        //CreatNotification();
    } else if (permissionNow === 'denied') {
        console.log('用户拒绝了你!!!');
    } else {
        setPermission();
    }
}

let WSS_ADDRESS = '<?php echo $SIGNALING_ADDRESS; ?>';

//店铺
let sz_arr = ['Z502','Z507','Z512','Z514','Z515','Z516','Z517','Z518','Z519','Z521','Z522','Z523','Z524','Z525','Z526','Z529','Z530','Z532','Z533','Z534','Z537','Z539','Z541','Z544','Z545','Z547','Z553','Z555','Z558','Z559','Z560','Z561','Z562','Z563','Z564','Z565','Z566','Z567','Z569','Z570','Z571','Z572','Z573','Z575','Z576','Z577','Z578','Z579','Z581','Z582','Z583','Z585','Z588','Z589','Z590','Z591','Z592','Z593','Z595','Z596','Z597','Z598','Z599','Z600','Z601','Z602','Z603','Z605','Z606','Z607','Z608','Z609','Z610','Z611','Z612','Z613'];

let wss = new WebSocket(WSS_ADDRESS);
console.log('wss',wss);
wss.onopen = function() {
    for(var i=0;i<sz_arr.length;i++){
        let subject = sz_arr[i];
        console.log('onopen',subject)
        subscribe(subject);
        storeHtml(sz_arr[i]);
    }
};

wss.onmessage = function(event) {
    var package = JSON.parse(event.data);
    var data = package.data;
    switch (package.event){
        case 'client-call':
            CreatNotification(data);
            $('#'+data).show();
            break;
    }
};


function subscribe(subject) {
    wss.send(JSON.stringify({
        cmd: 'subscribe',
        subject: subject,
    }));
}

//创建html
function storeHtml(num){
    let html = '';
    html += '<li class="item active" id="'+num+'" style="display:none"  >'; 
    html += '<div class="text">'+num+'店</div>';
    html += '<button onclick="getUrl(\''+num+'\')" class="btn" id="'+num+'"> <img src="assets/images/video.png" alt="" />  ';
    html += '<div class="txt-btn">立即接听</div> </button>';
    html += '</li>';
    $('#root').append(html);
}

//跳转
function getUrl(num){
    window.open('room.php?cid='+num);
    colseVideo(num);
}

//关闭
function colseVideo(num){
    setTimeout(function(){
        $('#'+num).hide();
    },3000);
}

function setPermission() {
    //请求获取通知权限
    NotificationInstance.requestPermission(function (PERMISSION) {
        if (PERMISSION === 'granted') {
           // CreatNotification();
        } else {
            console.log('用户拒绝了!');
        }
    });
}

//弹窗通知
function CreatNotification(num) {
    const n = new NotificationInstance('药师视频审核', {
        body: '你的有新信息啦！',
        tag: '2ue',
        icon: 'https://timgsa.baidu.com/timg?image&quality=80&size=b9999_10000&sec=1573813231732&di=fbb9c94fc009598d1d13a932a55b8fd4&imgtype=0&src=http%3A%2F%2Fhbimg.b0.upaiyun.com%2Fd2b2790168ca47750e13e9e02f4d2354afc6f4077d6b-mmSedN_fw658',
        data: {
             url: '/room.php?cid='+num
        }
    });
    n.onshow = function () {
       // console.log('通知显示了！');
    }
    n.onclick = function (e) {
        //可以直接通过实例的方式获取data内自定义的数据
        //也可以通过访问回调参数e来获取data的数据
        window.open(n.data.url, '_blank');
        colseVideo(num)
        n.close();
    }

    //关闭按钮调用
    n.onclose = function () {
        console.log('你关闭了');
    }
    n.onerror = function (err) {
        console.log('出错了，小伙子在检查一下吧');
        throw err;
    }
    setTimeout(() => {
        n.close();
    }, 10000);
}

</script>
</html>
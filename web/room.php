<?php include __DIR__ . '/../config.php'?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="description" content="workerman">
    <meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1">
    <meta itemprop="description" content="Video chat using the reference WebRTC application">
    <meta itemprop="name" content="AppRTC">
    <meta name="mobile-web-app-capable" content="yes">
    <meta id="theme-color" name="theme-color" content="#1e1e1e">
    <title>视频</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <style>
        body {
            background-color: #333;
            color: #fff;
            font-family: 'Roboto', 'Open Sans', 'Lucida Grande', sans-serif;
            height: 100%;
            margin: 0;
            padding: 0;
            width: 100%;
        }

        .videos{
            margin:0 auto;
            width: 644px;
        }
       
    </style>
</head>

<body>

<div class="videos">
    <video id="localVideo" autoplay></video>
    <video id="remoteVideo" autoplay class="hidden"></video>
</div>

<script src="assets/js/jquery-3.2.1.min.js"></script>
<script src="assets/js/bootstrap.js"></script>
<script src="assets/js/adapter.js"></script>

<script type="text/javascript">
    var localVideo = document.getElementById('localVideo');
    var remoteVideo = document.getElementById('remoteVideo');

    
    var WS_ADDRESS = '<?php echo $SIGNALING_ADDRESS; ?>';

    // 房间id
    var cid = getUrlParam('cid');
    if (cid == '' || cid == null) {
        cid = Math.random().toString(36).substr(2);
        location.href = '?cid=' + cid;
    }
    var answer = 0;

    // 基于订阅，把房间id作为主题
    var subject = cid;

    // 建立与workerman的连接
    var ws = new WebSocket(WS_ADDRESS);
    
    ws.onopen = function(){
        subscribe(subject);
        navigator.mediaDevices.getUserMedia({
            audio: true,
            video: true
        }).then(function (stream) {
            localVideo.srcObject = stream;
            localStream = stream;
            localVideo.addEventListener('loadedmetadata', function () {
                publish('client-call', subject)
            });
        }).catch(function (e) {
            console.log(e);
        });
    };
    ws.onmessage = function(e){
 
        var package = JSON.parse(e.data);
        var data = package.data;
      
        switch (package.event) {
            case 'client-call':
                icecandidate(localStream);
                pc.createOffer({
                    offerToReceiveAudio: 1,
                    offerToReceiveVideo: 1
                }).then(function (desc) {
                    pc.setLocalDescription(desc).then(
                        function () {
                            publish('client-offer', pc.localDescription);
                        }
                    ).catch(function (e) {
                        console.log(e);
                    });
                }).catch(function (e) {
                    console.log(e);
                });
                break;
            case 'client-answer':
                pc.setRemoteDescription(new RTCSessionDescription(data),function(){}, function(e){
                    console.log(e);
                });
                break;
            case 'client-offer':
                icecandidate(localStream);
                pc.setRemoteDescription(new RTCSessionDescription(data), function(){
                    if (!answer) {
                        pc.createAnswer(function (desc) {
                                pc.setLocalDescription(desc, function () {
                                    publish('client-answer', pc.localDescription);
                                }, function(e){
                                    console.log(e);
                                });
                            }
                        ,function(e){
                            console.log(e);
                        });
                        answer = 1;
                    }
                }, function(e){
                    console.log(e);
                });
                break;
            case 'client-candidate':
                pc.addIceCandidate(new RTCIceCandidate(data), function(){}, function(e){console.log(e);});
                break;
        }
    };
   

    navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia;
    var configuration = {
        iceServers: [{
            urls: 'stun:stun.xten.com'
        }]
    };
    var pc, localStream;

    function icecandidate(localStream) {
        pc = new RTCPeerConnection(configuration);
        pc.onicecandidate = function (event) {
            if (event.candidate) {
                publish('client-candidate', event.candidate);
            }
        };
        try{
            pc.addStream(localStream);
        }catch(e){
            var tracks = localStream.getTracks();
            for(var i=0;i<tracks.length;i++){
                pc.addTrack(tracks[i], localStream);
            }
        }
        pc.onaddstream = function (e) {
            $('#remoteVideo').removeClass('hidden');
            $('#localVideo').remove();
            remoteVideo.srcObject = e.stream;
        };
    }

    function publish(event, data) {
        ws.send(JSON.stringify({
            cmd:'publish',
            subject: subject,
            event:event,
            data:data,
        }));
    }

    function subscribe(subject) {
        ws.send(JSON.stringify({
            cmd:'subscribe',
            subject:subject
        }));
    }

    function getUrlParam(name) {
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)");
        var r = window.location.search.substr(1).match(reg);
        if (r != null) return unescape(r[2]);
        return null;
    }

</script>
</body>
</html>
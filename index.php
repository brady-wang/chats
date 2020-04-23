<?php

$params = $request->get;

if(empty($params['user_name'])){
  echo "<script> location.href='login.html' </script>";
    return '';
}
$user_name = $params['user_name'];
?>
<!doctype html>

<html>

<head>

    <meta charset="utf-8">

    <title>聊天室</title>

    <script src="http://libs.baidu.com/jquery/1.9.1/jquery.min.js"></script>

</head>

<body>
<style>
    .right {
        color: blue;
        display:block;
        width:100%;
        float:right;
    }


    #nav {
        line-height:30px;
        background-color:#ddebf7;
        height:504px;
        width:110px;
        float:left;
        padding:5px;
    }
    #section {
        width:350px;
        float:left;
        padding:10px;
    }


</style>


<div style="width:1000px; margin:0 auto;">

    <div id="nav">

    </div>

    <div id="section">

        <div class="log" id='scrolldIV' style="width:800px;height:500px;border: 2px solid #ccc;line-height: 1.5em;overflow:auto" >

            =======聊天室======

        </div>
        <!--<input type="button" value="连接" onClick="link()">-->
        <input type="hidden" value="" id="fd">
        <!--<input type="button" value="断开" onClick="dis()">-->
        <input type="hidden" name="user_name" id="user_name" value="<?php echo $user_name;?>">
        <div >

            <input type="text" id="text" style="width:344px;height:30px;margin-top:11px;" >

            <input type="button" value="发送" onClick="send()" id="submit" style="margin-top:10px;width:57px;">
        </div>
    </div>






</div>


<script>


    $(document).keydown(function(event){
        if(event.keyCode==13){
            $("#submit").click();
        }
    });


    $(function () {
        $("#text").focus();
        link();
    })

    function link() {
        var user_name = $("#user_name").val();
        var url = 'ws://192.168.33.10:9502?user_name='+user_name;

        socket = new WebSocket(url);

        socket.onopen = function (evt) {
            log1('<div>连接成功</div>')

        }

        socket.onmessage = function (msg) {
            var data = $.parseJSON(msg.data)
            console.log(data);
            html = '';
            for(k in data.all){
                uname = data.all[k]
                html += '<div>'+uname+'('+k+')</div>'
            }
            $("#nav").html(html)
            if(data.msg.length <= 0){
                $("#fd").val(data.no);
            } else {
                log(data);
            }


            var div = document.getElementById('scrolldIV');
            div.scrollTop = div.scrollHeight;

        }

        socket.onclose = function () {
            log1('<div>断开连接</div>')
        }

    }

    function dis() {

        socket.close();

        socket = null;

    }

    function log1(data) {
        $('.log').append(data);
    }
    function log(data) {
        var nowFd = $("#fd").val();
        console.log(data);
        if(data['no'] == nowFd){
            $('.log').append("<div class='right'>我:" +data['msg'] + '</div>');
        } else {
            if(data.user_name == '系统提示'){
                $('.log').append("<div  >" + data.user_name+":"+data['msg'] + '</div>');
            } else {
                $('.log').append("<div  >" + data.user_name+"("+data['no']+") :"+data['msg'] + '</div>');
            }

        }


    }

    function send() {
        var text = $('#text').val();
        if(text.length > 0){
            socket.send(text);
            $("#text").val('');
        }


        $("#text").focus();

    }


</script>

</body>

</html>
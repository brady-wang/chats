<?php
class Chat
{
    const HOST = '0.0.0.0';//ip地址 0.0.0.0代表接受所有ip的访问
    const PART = 9502;//端口号

    private $server = null;//单例存放websocket_server对象

    public static $user_names = [];

    public $redis;

    public function __construct()
    {

        $this->redis  = new MyRedis("192.168.33.10",6379,'123456');
        //实例化swoole_websocket_server并存储在我们Chat类中的属性上，达到单例的设计
        $this->server = new swoole_websocket_server(self::HOST, self::PART);
        //监听连接事件
        $this->server->on('open', [$this, 'onOpen']);
        //监听接收消息事件
        $this->server->on('message', [$this, 'onMessage']);
        //监听关闭事件
        $this->server->on('close', [$this, 'onClose']);


        //开启服务
        $this->server->start();
    }

    /**
     * 连接成功回调函数
     * @param $server
     * @param $request
     */
    public function onOpen($server, $request)
    {
        $params = $request->get;
        $user_name = $params['user_name'];

        $this->redis->set('fd.'.$request->fd,$user_name);
        echo $request->fd .":".$user_name. '连接了' . PHP_EOL;//打印到我们终端

        $all = $this->getAllUser($server);
        $server->push($request->fd,json_encode(['no' => $request->fd,"user_name"=>$user_name, 'msg' =>'','all'=>$all]));

        foreach ($server->connections as $fd) {//遍历TCP连接迭代器，拿到每个在线的客户端id
            //将客户端发来的消息，推送给所有用户，也可以叫广播给所有在线客户端
            if($fd != $request->fd){
                $msg = $user_name."($request->fd) 加入了聊天室";
                $server->push($fd, json_encode(['no' => $request->fd,'user_name'=>"系统提示", 'msg' => $msg,'all'=>$all]));
            }

        }

    }

    public function getAllUser($server)
    {
        foreach ($server->connections as $fd) {//遍历TCP连接迭代器，拿到每个在线的客户端id
            $data[$fd] = $this->redis->get('fd.'.$fd);
        }
        return $data;
    }

    /**
     * 接收到信息的回调函数
     * @param $server
     * @param $frame
     */
    public function onMessage($server, $frame)
    {
        $user_name = $this->redis->get('fd.'.$frame->fd);
        echo $user_name. '来了，说：' . $frame->data . PHP_EOL;//打印到我们终端

        $all = $this->getAllUser($server);
        foreach ($server->connections as $fd) {//遍历TCP连接迭代器，拿到每个在线的客户端id
            //将客户端发来的消息，推送给所有用户，也可以叫广播给所有在线客户端
            $msg = $frame->data;
            $server->push($fd, json_encode(['no' => $frame->fd,'user_name'=>$this->redis->get('fd.'.$frame->fd), 'msg' => $msg,'all'=>$all]));
        }
    }

    /**
     * 断开连接回调函数
     * @param $server
     * @param $fd
     */
    public function onClose($server, $fd)
    {
        echo $fd . '走了' . PHP_EOL;//打印到我们终端

        $all = $this->getAllUser($server);
        foreach ($server->connections as $fdd) {//遍历TCP连接迭代器，拿到每个在线的客户端id
            //将客户端发来的消息，推送给所有用户，也可以叫广播给所有在线客户端
            if($fdd != $fd){
                $msg = $this->redis->get('fd.'.$fd)."($fd) 离开了聊天室";
                $server->push($fdd, json_encode(['no' => $fd,'user_name'=>"系统提示", 'msg' => $msg,'all'=>$all]));
            }
        }

    }
}

class MyRedis
{
    public $redis;
    public function __construct($host='',$port='',$password='')
    {
        $this->redis = new Redis();
        $this->redis->connect($host, $port);//serverip port
        $this->redis->auth($password);//my redis password

    }

    public function set($key,$value)
    {
        $this->redis->set($key,$value);
    }

    public function get($key)
    {
        return $this->redis->get($key);
    }
}



$obj = new Chat();
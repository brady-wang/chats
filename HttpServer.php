<?php


class HttpServer
{
    public $server;
    public $port;
    public $host;

    public function __construct($host = '0.0.0.0', $port = 9501)
    {
        $this->port = $port;
        $this->host = $host;
        $this->server = new Swoole\Http\Server($this->host, $this->port);

        $this->server->set(array(
            'reactor_num' => 2, //reactor thread num
            'worker_num' => 4,    //worker process num
            'backlog' => 128,   //listen backlog
            'max_request' => 50,
            'dispatch_mode' => 1,
            'daemonize'=>0
        ));
        $this->server->on('request', [$this, 'OnRequest']);
    }

    public function onRequest($request, $response)
    {
        if ($request->server['path_info'] == '/favicon.ico' || $request->server['request_uri'] == '/favicon.ico') {
            $response->end();
            return;
        }
        $pathinfo = $request->server['path_info'];
        $file_name = __DIR__ . $pathinfo;
        try {
            $response->header("Content-Type", "text/html; charset=utf-8");

            if (is_file($file_name)) {

                $ext = pathinfo($request->server['path_info'],PATHINFO_EXTENSION);
                if ($ext == 'php') {
                    ob_start();
                    include $file_name;
                    $content = ob_get_contents();
                    ob_end_clean();
                } else {

                    $mime = include('mime.php');
                    $content_type = $mime[$ext];
                    $response->header('Content-Type',$content_type);
                    $content = file_get_contents($file_name);
                }
                $response->end($content);
            } else {

                $response->status(404);
                $response->end("404 页面未找到");
            }

        } catch (\Exception $e) {
            $response->header("Content-Type", "text/html; charset=utf-8");
            $response->end($e->getMessage());
        }


    }

    public function run()
    {
        $this->server->start();
    }
}

(new HttpServer('0.0.0.0', 9501))->run();


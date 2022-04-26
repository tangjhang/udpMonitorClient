# udpMonitorClient
 定时向服务端发监控数据 Regularly send monitoring data to the server

在process文件的配置如下：
```
'udpMonitorClient' => [
        'enable' => true,
        'handler' => \UdpMonitorClient\src\process\UdpMonitorClient::class,
        'constructor' => [
            // 消费者类目录
            'host' => 'udp://127.0.0.1',    //修改为服务端的地址
            'port' => '9090',         //服务端端口
            'site' => 'md_official',  //网站名称，唯一，避免和其他网站冲突
            'time_num' => 55         //定时向服务端发送数据的时间，默认55
        ]
    ],
```

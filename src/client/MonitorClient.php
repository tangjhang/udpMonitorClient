<?php


namespace UdpMonitorClient\src\client;


use Workerman\Connection\AsyncUdpConnection;

class MonitorClient
{
    public static function report(array $config, string $data)
    {
        $host = $config['host'];
        $port = $config['port'];
        $udp_connection = new AsyncUdpConnection($host . ':' . $port);
        $udp_connection->onConnect = function ($udp_connection) use ($data) {
            $udp_connection->send($data);
            $udp_connection->close();
        };
        $udp_connection->connect();
    }
}
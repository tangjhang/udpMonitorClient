<?php


namespace client;


use Carbon\Carbon;
use Workerman\Connection\AsyncUdpConnection;
use Workerman\Timer;

class UdpMonitorClient
{
    /**
     * 网站名称
     */
    private $site;

    /**
     * udp服务端地址
     */
    private $host;

    /**
     * 服务端端口
     */
    private $port;

    /**
     * 定时发送时间
     */
    private $sendTime;


    public function __construct($host, $port, $site, $sendTime = 55)
    {
        $this->host = $host;
        $this->port = $port;
        $this->site = $site;
        $this->sendTime = $sendTime;
    }

    public function onWorkerStart(): void
    {
        Timer::add($this->sendTime, [$this, 'senMessage']);
    }

    public function senMessage(): void
    {
        $udp_connection = new AsyncUdpConnection($this->host . ':' . $this->port);
        $data = json_encode($this->getMemory());
        $udp_connection->onConnect = function ($udp_connection) use ($data) {
            $udp_connection->send($data);
            $udp_connection->close();
        };
        $udp_connection->connect();
    }


    /**
     * 获取进程内存
     * @return array
     */
    public function getMemory(): array
    {
        $ppid = posix_getppid();
        $time = Carbon::now()->toDateTimeString();
        $data = [];
        $childrenFile = "/proc/$ppid/task/$ppid/children";
        if (!is_file($childrenFile) || !($children = file_get_contents($childrenFile))) {
            return [];
        }
        $childrenPIds = explode(' ', $children);
        foreach ($childrenPIds as $pid) {
            $pid = (int)$pid;
            $statusFile = "/proc/$pid/status";
            if (!is_file($statusFile) || !($status = file_get_contents($statusFile))) {
                continue;
            }
            $mem = 0;
            if (preg_match('/VmRSS\s*?:\s*?(\d+?)\s*?kB/', $status, $match)) {
                $mem = $match[1];
            }
            $data[] = [
                'site' => $this->site,
                'pid' => $pid,
                'memory' => $mem,
                'runTime' => $time
            ];
        }
        return $data;
    }
}
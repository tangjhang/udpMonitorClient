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
    private $timeNum;


    public function __construct($host, $port, $site, $timeNum = 55)
    {
        $this->host = $host;
        $this->port = $port;
        $this->site = $site;
        $this->timeNum = $timeNum;
    }

    public function onWorkerStart(): void
    {
        Timer::add($this->timeNum, [$this, 'senMessage']);
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
            return [
                'site' => $this->site,
                'pid' => 0,
                'memory' => '0kB',
                'run_time' => $time
            ];
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
                $mem = $match[1].'kB';
            }
            $data[] = [
                'site' => $this->site,
                'pid' => $pid,
                'memory' => $mem,
                'run_time' => $time,
                'status' => 1
            ];
        }
        return $data;
    }
}
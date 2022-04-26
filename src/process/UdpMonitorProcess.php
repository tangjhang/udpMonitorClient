<?php


namespace UdpMonitorClient\process;


use Carbon\Carbon;
use UdpMonitorClient\client\MonitorClient;
use Workerman\Connection\AsyncUdpConnection;
use Workerman\Timer;

class UdpMonitorProcess
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
        Timer::add($this->timeNum, [$this, 'sendMessage']);
    }

    public function sendMessage(): void
    {
        $data = json_encode(["data" => $this->getMemory(), "mark" => "memory"]);
        $config = [
            'host' => $this->host,
            'port' => $this->port
        ];
        MonitorClient::report($config, $data);
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
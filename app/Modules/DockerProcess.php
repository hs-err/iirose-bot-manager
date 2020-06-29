<?php


namespace App\Modules;

use App\Utils\PacketUtils;
use App\Work;
use Co;
use Co\Http\Server;
use Co\System;
use SplQueue;
use Swoole\Coroutine\Channel;
use Swoole\Coroutine\Http\Client;
use Swoole\Coroutine\Socket;
use Swoole\Process;
use swoole_client;
use swoole_process;
use function foo\func;

class DockerProcess
{
    public $id;
    /**
     * @var Process $pool 
     */
    public $pool;
    public $channel;
    public $con;
    public $initd = false;
    public function __construct($id)
    {
        $this->id = $id;
        $this->init();
        $this->pool = new Process(
            function (Process $childProcess) {
                //$childProcess->exec('/bin/bash',[]);
                while (true) {
                    echo 'b';
                    $childProcess->exec(
                        '/bin/bash', [
                        '-c',
                        'while true;' .
                        'do ' .
                        'docker run -i --name iirose_ubuntu_' . $this->id . ' -m 1G --cpus 1 --pids-limit 16 iirose_ubuntu_docker:latest /bin/bash;' .
                        'docker stop iirose_ubuntu_' . $this->id . ';' .
                        'docker kill iirose_ubuntu_' . $this->id . ';' .
                        'docker rm iirose_ubuntu_' . $this->id . ';' .
                        'done'
                        ]
                    );
                }
            }, true
        );
        $this->pool->start();
        $this->pool->setBlocking(false);
        go(
            function () {
                while (true) {
                    if (!$this->pool) {
                        try {
                            $this->con->close();
                        } catch (\Exception $e) {
                        }
                        return;
                    }
                    $d = @$this->pool->read();
                    if ($d) {
                        $this->push($d);
                        echo $d;
                    }
                    Co::sleep(0.1);
                }
            }
        );
    }
    public function canUse()
    {
        return $this->con == null && $this->initd;
    }
    public function init()
    {
        go(
            function () {
                //重置bot
                echo '重置bot' . $this->id . "\n";
                System::exec('docker stop iirose_ubuntu_' . $this->id . ';docker kill iirose_ubuntu_' . $this->id . ';docker rm iirose_ubuntu_' . $this->id);
                //Co::sleep(10);
                $this->initd = true;
            }
        );
    }
    public function use($ws)
    {
        $this->con = $ws;
    }
    public function close()
    {
        $this->initd = false;
        $this->con = null;
        $this->init();
    }
    public function write($message)
    {
        $this->pool->write($message . "\n");
    }
    public function push($message)
    {
        if ($this->con) {
            $this->con->push($message);
        }
    }
}

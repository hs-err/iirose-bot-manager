<?php


namespace App\Modules;

use App\Utils\PacketUtils;
use App\Work;
use Co;
use SplQueue;
use Swoole\Coroutine\Socket;
use Swoole\Process;
use swoole_client;
use swoole_process;
use function foo\func;

class BotProcess
{
    /**
     * @var Process $pool 
     */
    public $pool;

    public function kill()
    {
        Process::kill($this->pool->pid, 1);
    }

    public function __construct($id)
    {
        $this->pool = new Process(
            function (Process $childProcess) use ($id) {
                $childProcess->exec(env('PHP_PATH', '/usr/bin/php'), [base_path('artisan'),'ob',$id]);
            }, false
        );
        $this->pool->start();
    }

    public function check()
    {
        Process::wait(false);
        if (!Process::kill($this->pool->pid, 0)) {
            return false;
        } else {
            return true;
        }
    }
}

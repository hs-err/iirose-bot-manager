<?php

namespace App\Console\Commands;

spl_autoload_register(
    function ($class_name) {
        @include \Illuminate\Support\Facades\App::basePath() . '/' . str_replace('\\', '/', $class_name) . '.php';
    }
);

use App\Modules\DockerProcess;
use Co\Http\Server;
use Illuminate\Console\Command;

define('DOCKER_COUNT', 5);
class Docker extends Command
{
    protected $signature = 'docker';
    protected $description = 'run bot';
    /**
     * @var DockerProcess[] $pool 
     */
    private $pool=[];
    public function handle()
    {
        for ($i=0; $i<DOCKER_COUNT; $i++) {
            $this->pool[$i]=new DockerProcess($i);
        }
        go(
            function () {
                $server = new Server("127.0.0.1", 10912, false);
                echo 'aaa';
                $server->handle(
                    '/', function ($request, $ws) {
                        echo 'aaa';
                        $pool=null;
                        for ($i=0; $i<DOCKER_COUNT; $i++) {
                            if ($this->pool[$i]->canUse()) {
                                $pool=$this->pool[$i];
                                $pool->use($ws);
                                break;
                            }
                        }
                        if (!$pool) {
                            return;
                        } else {
                            $ws->upgrade();
                        }
                        while (true) {
                            $frame = $ws->recv();
                            if ($frame === false) {
                                echo "error : " . swoole_last_error() . "\n";
                                break;
                            } elseif ($frame == '') {
                                $pool->close();
                                break;
                            } else {
                                $pool->write($frame->data);
                            }
                        }
                    }
                );
                $server->start();
            }
        );
    }
}

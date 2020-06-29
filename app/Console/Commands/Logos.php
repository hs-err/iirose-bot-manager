<?php /**
       * @noinspection DuplicatedCode 
       */

namespace App\Console\Commands;

use Co;
use Co\System;
use Illuminate\Console\Command;
use Swoole\Process;

class Logos extends Command
{
    protected $signature = 'logos';
    protected $description = 'run logos';

    public function handle()
    {
        $pool=new Process(
            function (Process $childProcess) {
                $childProcess->exec('/bin/bash', []);
            }, true, 1, false
        );
        $pool->start();
        $pool->setBlocking(false);
        $pool->setTimeout(10);
        var_dump($pool->pipe);
        go(
            function () use ($pool) {
                while (true) {
                    $d=@$pool->read();
                    if ($d) {
                        echo $d;
                    }
                    Co::sleep(0.1);
                }
            }
        );
        go(
            function () use ($pool) {
                while (true) {
                    $d= System::fread(STDIN);
                    if ($d) {
                        $pool->write($d."\n");
                    }
                    Co::sleep(0.1);
                }
            }
        );
    }
}

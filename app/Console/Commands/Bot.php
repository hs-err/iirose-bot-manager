<?php

namespace App\Console\Commands;

spl_autoload_register(
    function ($class_name) {
        @include \Illuminate\Support\Facades\App::basePath() . '/' . str_replace('\\', '/', $class_name) . '.php';
    }
);

use App\Modules\BotProcess;
use Co;
use Exception;
use Illuminate\Console\Command;
use Swoole\Event;
use Swoole\Process;
use Swoole\Timer;

class Bot extends Command
{
    protected $signature = 'bot';
    protected $description = 'run bot';
    /**
     * @var BotProcess[] $pool 
     */
    private $pool;

    public function handle()
    {
        $this->pool = [];
        while (true) {
            // A.将数据库转换为BOT LIST
            $all_bots = \App\Bot::where('end', '>', date('Y-m-d H:i:s'))->get();
            $bot_list = [];
            foreach ($all_bots as $per_bot) {
                $bot_data = $per_bot;
                $bot_list[md5(serialize($bot_data))] = $bot_data;
            }
            // B.关闭不在BOT LIST上的机器人
            foreach ($this->pool as $uuid => $process) {
                if (!@$bot_list[$uuid]) {
                    $process->kill();
                    sleep(1);
                    if (!$this->pool[$uuid]->check()) {
                        unset($this->pool[$uuid]);
                    } else {
                        $this->warn("BOT卸载失败");
                    }
                }
            }
            // C.检查并加载机器人
            foreach ($bot_list as $uuid => $per_list) {
                if (!@$this->pool[$uuid]) {
                    $this->pool[$uuid] = new BotProcess($per_list->id);
                } else {
                    if (!$this->pool[$uuid]->check()) {
                        unset($this->pool[$uuid]);
                    }
                }
            }
            sleep(2);
        }
    }
}

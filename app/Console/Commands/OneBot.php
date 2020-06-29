<?php


namespace App\Console\Commands;

use App\Modules\RunBot;
use Illuminate\Console\Command;

class OneBot extends Command
{
    protected $signature = 'ob {bot_id}';
    protected $description = 'run bot';
    public function handle()
    {
        /**
 * @var \App\Bot $bot 
*/
        $bot=\App\Bot::find($this->argument('bot_id'));
        new RunBot(
            $bot->username,
            $bot->password,
            $bot->room,
            $bot->plugins,
            $bot->config,
            $bot
        );
    }
}

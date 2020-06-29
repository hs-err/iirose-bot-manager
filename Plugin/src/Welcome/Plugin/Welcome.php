<?php


namespace Plugin;

use App\Event\JoinEvent;
use App\Modules\Plugin;

class Welcome extends Plugin
{
    public function onJoin(JoinEvent $event)
    {
        $message=@$this->config['Welcome.php']['message']?:'[room]欢迎[user]喵~';
        $message=str_replace('[user]', $this->at($event->user_name), $message);
        $message=str_replace('[room]', $this->ro($this->runBot->room), $message);
        $this->sendChat(
            $message,
            @$this->config['Welcome.php']['color']?:$event->color
        );
    }
}

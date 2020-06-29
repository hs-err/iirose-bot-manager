<?php


namespace Plugin;

use App\Event\ChatEvent;
use App\Event\GotoEvent;
use App\Event\LeaveEvent;
use App\Event\PersonChatEvent;
use App\Modules\Plugin;
use Illuminate\Support\Facades\App;

class Assert extends Plugin
{
    public function onChat(ChatEvent $event)
    {
        if ($event->message=='表白'.$this->runBot->username) {
            $this->sendChat($this->at($event->user_name).'谢谢');
        }
        if ($event->message=='我可爱，请给我钱') {
            $this->sendChat($this->at($event->user_name).'~摸摸头~');
            $this->sendPay($event->user_name, 0.001);
        }
    }
}

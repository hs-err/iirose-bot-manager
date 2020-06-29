<?php


namespace Plugin;

use App\Event\ChatEvent;
use App\Event\GotoEvent;
use App\Event\LeaveEvent;
use App\Event\PersonChatEvent;
use App\Event\UserInfoEvent;
use App\Modules\Plugin;
use App\User;
use Illuminate\Support\Facades\App;

class Follow extends Plugin
{
    private $follow;
    public function init()
    {
        if (@$this->config['Follow.php']['follow']) {
            $this->follow=$this->config['Follow.php']['follow'];
        } else {
            /** @var User $user */
            $user=User::find($this->bot->id);
            $this->follow=$user->uid;
            $this->warn('Follow.php 未能被正确配置，目前跟随：'.$user->name);
        }
    }

    public function onGoto(GotoEvent $event)
    {
        if ($event->user_id==$this->follow) {
            $this->changeRoom($event->to);
        }
    }
    public function onUserInfo(UserInfoEvent $event)
    {
        if ($event->user_id==$this->follow) {
            if ($event->room_id != $this->runBot->room) {
                $this->changeRoom($event->room_id);
            }
        }
    }
}

<?php


namespace Plugin;

use App\Event\ChatEvent;
use App\Event\PersonChatEvent;
use App\Modules\Plugin;
use App\Modules\Sender;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Count extends Plugin
{
    public function onChat(ChatEvent $event)
    {
        $key='Count_person_'.$this->runBot->username.'_'.date('Y-m-d').'_'.$event->user_name;
        $room_key='Count_room_'.$this->runBot->username.'_'.date('Y-m-d');
        if (!Cache::get($key, false)) {
            Cache::put($room_key.'_all', Cache::get($room_key.'_all', 0)+1, 24*3600);
        }
        Cache::put($room_key.'_count', Cache::get($room_key.'_count', 0)+1, 24*3600);
        Cache::put($key, Cache::get($key, 0)+1, 24*3600);
    }
    private function count($user_name)
    {
        $key='Count_person_'.$this->runBot->username.'_'.date('Y-m-d').'_'.$user_name;
        return "=========发言统计=========
用户：".$this->at($user_name)."
机器人：".$this->at($this->runBot->username)."
房间：".$this->ro($this->runBot->room)."
时间：".date('Y-m-d')."
发言数：".Cache::get($key, 0);
    }
    private function room()
    {
        $room_key='Count_room_'.$this->runBot->username.'_'.date('Y-m-d');
        return "=========房间统计=========
机器人：".$this->at($this->runBot->username)."
房间：".$this->ro($this->runBot->room)."
时间：".date('Y-m-d')."
发言数：".Cache::get($room_key.'_count', 0)."
发言人数：".Cache::get($room_key.'_all', 0);
    }
    public function onCommand(string $sign, Sender $sender, InputInterface $input, OutputInterface $output)
    {
        if($sign=='room:count'){
            $arg=$input->getArgument('who');
            if($arg){
                $output->write($this->count(substr($arg,2,strlen($arg)-4)));
            }else{
                $output->write($this->count($sender->getUsername()));
            }
        }elseif ($sign=='room:room'){
            $output->write($this->room());
        }
    }
}

<?php

namespace App\Modules;

use App\Event\NoUserEvent;
use App\Event\UserInfoEvent;
use App\Utils\BotUtils;
use App\Event\BoardCastEvent;
use App\Event\ChatEvent;
use App\Event\CloseEvent;
use App\Event\GotoEvent;
use App\Event\InfoEvent;
use App\Event\JoinEvent;
use App\Event\LeaveEvent;
use App\Event\PayEvent;
use App\Event\PersonChatEvent;
use Illuminate\Support\Facades\App;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Plugin
{
    use BotUtils;

    public function __construct($main, $client, $config, $bot)
    {
        $this->runBot=$main;
        $this->client=$client;
        $this->config=$config;
        $this->bot=$bot;
        $this->init();
    }
    protected function init()
    {
    }
    public function onChat(
        ChatEvent $event
    ) {
    }
    public function onCommand(
        string $sign,
        Sender $sender,
        InputInterface $input,
        OutputInterface $output
    ) {
    }
    public function onBoardCast(
        BoardCastEvent $event
    ) {
    }
    public function onJoin(
        JoinEvent $event
    ) {
    }
    public function onLeave(
        LeaveEvent $event
    ) {
    }
    public function onGoto(
        GotoEvent $event
    ) {
    }
    public function onPay(
        PayEvent $event
    ) {
    }
    public function onInfo(
        InfoEvent $event
    ) {
    }
    public function onUserInfo(
        UserInfoEvent $event
    ) {
    }
    public function onNoUser(
        NoUserEvent $event
    ) {
    }
    public function onClose(
        CloseEvent $event
    ) {
    }
    public function onPersonChat(
        PersonChatEvent $event
    ) {
    }

    protected function warn($warn)
    {
        $this->runBot->warn(__CLASS__.'：'.$warn);
    }
    protected function dump($var)
    {
        ob_start();
        var_dump($var);
        $dump=ob_get_flush();
        ob_clean();
        $this->runBot->log('[DUMP]'.__CLASS__.'：'.$dump);
    }
    protected function info($info)
    {
        $this->runBot->info(__CLASS__.'：'.$info);
    }
    protected function log($log)
    {
        $this->runBot->log(__CLASS__.'：'.$log);
    }
    public static function phar($plugin,$file=null){
        $file=substr($plugin, strlen($plugin) - 5) == '.phar'
            ?$file:$file.'.phar';
        return $file
            ?'phar://'.App::basePath().'/Plugin/'.$plugin.'/'.$file
            :'phar://'.App::basePath().'/Plugin/'.$plugin;
    }
}

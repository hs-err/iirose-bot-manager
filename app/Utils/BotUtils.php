<?php


namespace App\Utils;

use App\Bot;
use App\Event\InfoEvent;
use App\Event\UserInfoEvent;
use App\Modules\RunBot;
use Co;
use Swoole\Coroutine\Http\Client;

trait BotUtils
{
    /**
     * @var RunBot $runBot
     */
    public $runBot;
    /* @var Client $client */
    public $client;
    public $config;
    /**
     * @var Bot $bot
     */
    public $bot;

    public function changeRoom(
        $room_id
    ) {
        $this->log('[SEND]ChangeRoom ' . $room_id);
        $this->bot->room=$room_id;
        $this->bot->save();
        $this->runBot->exit();
    }

    public function sendChat(
        $message,
        $color = '6ebadb'
    ) {
        $this->log('[SEND]sendChat ' . $message);
        $this->runBot->push(
            json_encode(
                [
                'm' => $message,
                'mc' => $color,
                'i' => uniqid($message)
                ]
            )
        );
    }

    public function sendBoardCast(
        $message,
        $color = '6ebadb'
    ) {
        $this->log('[SEND]sendBoardCast ' . $message);
        $this->runBot->push(
            '~' . json_encode(
                [
                't' => $message,
                'c' => $color
                ]
            )
        );
    }

    public function sendPay(
        $username,
        $count
    ) {
        $this->log('[SEND]sendPay ' . $username . ' with' . $count);
        $this->runBot->push(
            '+$' . json_encode(
                [
                'g' => $username,
                'c' => $count
                ]
            )
        );
    }

    public function sendPersonChat(
        $user_id,
        $message,
        $color = '6ebadb'
    ) {
        $this->log('[SEND]sendPersonChat ' . $user_id . ' with' . $message);
        $this->runBot->push(
            json_encode(
                [
                'g' => $user_id,
                'm' => $message,
                'mc' => $color,
                'i' => uniqid($message)
                ]
            )
        );
    }

    public function sendPersonChat_username(
        $username,
        $message,
        $color = '6ebadb'
    ) {
        $this->log('[SEND]sendPersonChat_username ' . $username . ' with' . $message);
        $result = $this->getInfo($username);
        $this->runBot->push(
            json_encode(
                [
                'g' => $result->user_id,
                'm' => $message,
                'mc' => $color,
                'i' => uniqid($message)
                ]
            )
        );
    }

    /**
     * @param  $user_name
     * @return null | InfoEvent
     */
    public function getInfo(
        $user_name
    ) {
        while (true) {
            if (!$this->runBot->info_lock) {
                $this->runBot->info_lock = true;
                break;
            } else {
                co::sleep(0.1);
            }
        }
        $this->log('[SEND]getInfo ' . $user_name);
        $this->runBot->info_answer = null;
        $this->runBot->push('++' . $user_name);
        for ($i = 0; $i < 100; $i++) {
            if ($this->runBot->info_answer !== null) {
                $this->runBot->info_lock = false;
                return $this->runBot->info_answer;
            }
            co::sleep(0.1);
        }
        $this->runBot->info_lock = false;
        return null;
    }

    /**
     * @param  $user_name
     * @return null | UserInfoEvent
     */
    public function getUserInfo(
        $user_name
    ) {
        if (@$this->runBot->user_cache[$user_name]) {
            return $this->runBot->user_cache[$user_name];
        } else {
            return null;
        }
    }

    public function getPluginName()
    {
        return __CLASS__;
    }

    public function setClient($client)
    {
        $this->client = $client;
    }

    public function at($username)
    {
        return ' [*' . $username . '*] ';
    }

    public function ro($room_id)
    {
        return ' [_' . $room_id . '_] ';
    }
    public function im($img_url)
    {
        return ' [' . $img_url . '#.png] ';
    }
    public function ur($url)
    {
        return ' [' . $url . '] ';
    }
}

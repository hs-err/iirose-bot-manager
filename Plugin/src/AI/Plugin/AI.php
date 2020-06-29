<?php


namespace Plugin;

use App\Event\ChatEvent;
use App\Modules\Plugin;
use GuzzleHttp\Client;

class AI extends Plugin
{
    public function onChat(ChatEvent $event)
    {
        if (substr_count($event->message, $this->at($this->runBot->username))) {
            try {
                if (substr_count($event->message, $this->at($this->runBot->username))) {
                    $message = $event->message;
                    $message = str_replace($this->at($this->runBot->username), '', $message);
                    $message = str_replace(' ', '', $message);
                    $client = new Client();
                    $response = $client->get('http://api.qingyunke.com/api.php', [
                        'query' => [
                            'key' => 'free',
                            'appid' => '0',
                            'msg' => $message
                        ]
                    ]);
                    $result = json_decode($response->getBody(), true);
                    $return = $result['content'];
                    $return = str_replace('{br}', "\n", $return);
                    $this->sendChat(' [*' . $event->user_name . '*] ' . $return);
                }
            } catch (\Exception $e) {
                $this->sendChat(' [*' . $event->user_name . '*] 喵呜~喵喵cpu坏啦~喂......不要帮我修啦');
                $this->warn($e->getMessage());
            }
        }
    }
}

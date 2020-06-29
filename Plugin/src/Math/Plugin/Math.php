<?php


namespace Plugin;

use App\Event\ChatEvent;
use App\Modules\Plugin;
use App\Modules\Sender;
use GuzzleHttp\Client;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Swoole\Process;
use Co;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Math extends Plugin
{
    public function onCommand(string $sign, Sender $sender, InputInterface $input, OutputInterface $output)
    {
        if($sign=='math:maxima') {
            go(function () use ($input, $sender) {
                try {
                    $message = implode(' ', $input->getArgument('shell'));
                    $ret = Co\System::exec('bash -c "'.addslashes('docker run --rm --name "iirose_maxima_'.addslashes($sender->getUserId()).'" -t -i -m 100M --cpus=0.1 jgoldfar/maxima-docker:debian-latest timeout 5 maxima --batch-string="'.addslashes($message).'"').'" 2>&1');
                    $lines=explode("\n", $ret['output']);
                    $op=false;
                    $return='';
                    foreach ($lines as $line) {
                        if (substr($line, 0, 5)=='(%i1)') {
                            $op=true;
                        }
                        if ($op) {
                            $return.="\n".$line;
                        }
                    }
                    if (!$return) {
                        $sender->sendMessage('喵呜~喵喵算不出来啦~太复杂啦');
                        return;
                    }
                    if (strlen($return)<1024) {
                        $sender->sendMessage('\\\\\\=' . $return);
                        return;
                    }
                    $token=md5($this->runBot->username.microtime().serialize($input));
                    Cache::put('bot_docker_echo_'.$token, $return, 600);
                    $sender->sendMessage("\n喵呜~喵喵搬不动啦，我就丢在这里啦\n".$this->ur(route('cout', ['c'=>$token])));
                } catch (\Exception $e) {
                    $sender->sendMessage('喵呜~喵喵算不出来啦~太复杂啦');
                    $this->warn($e->getMessage());
                }
            });
        }
    }
}

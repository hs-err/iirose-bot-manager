<?php


namespace App\Modules;

use App\Utils\BotUtils;
use Closure;
use Co;
use Exception;
use SplQueue;
use Illuminate\Support\Facades\Cache;
use Swoole\Coroutine\Http\Client;

class DockerClient
{
    public $user_name;
    public $user_id;

    /**
     * @var Client $ws
     */
    public $ws;
    /**
     * @var SplQueue $queue
     */
    public $queue;

    public $message;

    public $last;
    public $commit;

    /**
     * @var Closure $call
     */
    public $call;
    public $call_this;

    public $initd=false;
    public function __construct($user_name, $user_id, Closure $call, $call_this)
    {
        $this->user_name=$user_name;
        $this->queue=new SplQueue();
        $this->call=$call;
        $this->call_this=$call_this;
        $this->commit=9999999999;
    }

    public function init()
    {
        $this->ws = new Client('127.0.0.1', 10912);
        $ret = $this->ws->upgrade('/');
        $this->initd=true;
        $this->last=time();
        var_dump($ret);
        go(
            function () {
                while (true) {
                    if (@$this->ws) {
                        $receive = @$this->ws->recv()->data;
                        if ($receive) {
                            $this->message .= $receive;
                            $this->commit = time();
                        } else {
                            unset($this->ws);
                            break;
                        }
                    } else {
                        unset($this->ws);
                        break;
                    }
                    Co::sleep(0.1);
                }
            }
        );
        go(
            function () {
                while (true) {
                    if (@$this->ws) {
                        if ($this->commit+5<time()) {
                            if (strlen($this->message)<1024) {
                                $this->call->call($this->call_this, $this->message);
                            } else {
                                $token=md5(microtime().serialize($this->message));
                                Cache::put('bot_docker_echo_'.$token, $this->message, 600);
                                $this->call->call($this->call_this, "喵呜~喵喵搬不动啦，我就丢在这里啦\n[".route('cout', ['c'=>$token]).']');
                            }
                            $this->message='';
                            $this->commit=9999999999;
                        }
                    } else {
                        unset($this->ws);
                        break;
                    }
                    Co::sleep(0.1);
                }
            }
        );
        go(
            function () {
                while (true) {
                    if (@$this->ws) {
                        while (!$this->queue->isEmpty()) {
                            $this->ws->push($this->queue->pop());
                        }
                    } else {
                        unset($this->ws);
                        break;
                    }
                    Co::sleep(0.1);
                }
            }
        );
        return (boolean)$ret;
    }
    public function get()
    {
        return true;
    }
    public function push($message)
    {
        $this->last=time();
        $this->queue->push($message);
    }
}

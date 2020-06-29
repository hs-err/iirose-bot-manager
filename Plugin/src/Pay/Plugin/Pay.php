<?php


namespace Plugin;

use App\Bot;
use App\Modules\Plugin;
use App\User;
use App\Event\ChatEvent;
use App\Event\GotoEvent;
use App\Event\PayEvent;
use App\Event\PersonChatEvent;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Swoole\Http\Response;
use Swoole\Http\Request;
use Co;

define('Pay_second', 2000);

class Pay extends Plugin
{

    protected function init()
    {
        $this->info("单线程网站准备中");
        go(function () {
            $server = new Co\Http\Server("127.0.0.1", 9502, false);
            $server->handle('/getInfo', function (Request $request, Response $response) {
                if (@$request->get['username']) {
                    $username = $request->get['username'];
                    $user = $this->getInfo($username);
                    if ($user) {
                        $data = (array)$user;
                        $data['username'] = $username;
                        $response->end(json_encode($data));
                    } else {
                        $response->end(json_encode(null));
                    }
                } else {
                    $response->end(json_encode(null));
                }
            });
            $server->handle('/sendPersonChat', function (Request $request, Response $response) {
                if (@$request->get['user_id'] && @$request->get['message'] && @$request->get['color']) {
                    $this->sendPersonChat($request->get['user_id'], $request->get['message'], $request->get['color']);
                    $response->end(json_encode([
                        'status' => true
                    ]));
                } else {
                    $response->end(json_encode([
                        'status' => false,
                        'message' => '参数都没全？'
                    ]));
                }
            });
            $server->start();
        });
        $this->info("单线程网站服务器就绪");
    }
    public function onPay(PayEvent $event)
    {
        if ($this->runBot->username!='logos') {
            $this->warn('DON\'T USE PAY PLUGIN!');
            return;
        }
        $i_user=$this->getInfo($event->user_name);
        if (!$i_user) {
            $this->sendPay($event->user_name, $event->count);
            return;
        }
        $user=User::where('uid', '=', $i_user->user_id)->first();
        if (!$user) {
            $this->sendPay($event->user_name, $event->count);
            $this->sendPersonChat($i_user->user_id, '抱歉，'.$event->user_name.'在数据库中没有对应用户。');
            return;
        }
        $bot=Bot::find($user->id);
        if (!$bot) {
            $this->sendPay($event->user_name, $event->count);
            $this->sendPersonChat_username($event->user_name, '抱歉，'.$event->user_name.'没有创建bot。');
            return;
        }
        $bot->addTime(Pay_second*$event->count);
        $bot->save();
        $this->sendPersonChat_username($event->user_name, $event->user_name.'购买了'.Pay_second*$event->count.'秒的机器人。有效期至'.date('Y-m-d H:i:s'));
    }
    public function onGoto(GotoEvent $event)
    {
        if (Cache::get('Pay_getRoomId_'.$event->user_id, false)) {
            $this->sendPersonChat($event->user_id, '你需要的房间号为：'.$event->to);
        }
    }
    public function onPersonChat(PersonChatEvent $event)
    {
        if ($event->message=='菜单') {
            $this->sendPersonChat(
                $event->user_id,
                '======机器人销售======
查询房间号
机器人定价
机器人查询
购买机器人
======插件使用======
插件列表'
            );
        } elseif ($event->message=='机器人定价') {
            $this->sendPersonChat(
                $event->user_id,
                '======机器人定价======
目前的动态定价为'.Pay_second
            );
        } elseif ($event->message=='查询房间号') {
            $this->sendPersonChat(
                $event->user_id,
                '======查询房间号======
你所在的房间号是
'.@$this->getUserInfo($event->user_name)->room_id
            );
        } elseif ($event->message=='机器人查询') {
            $user=User::where('name', '=', $event->user_name)->first();
            if (!$user) {
                $this->sendPersonChat($event->user_id, '抱歉，'.$event->user_name.'在数据库中没有对应用户。');
                return;
            }
            $bot=Bot::find($user->id);
            if (!$bot) {
                $this->sendPersonChat($event->user_id, '抱歉，'.$event->user_name.'没有创建bot。');
                return;
            }
            $this->sendPersonChat(
                $event->user_id,
                '======机器人查询======
你的机器人用户：'.$bot->username.'
到期时间：'.$bot->end.'
logos会帮你看好机器人的啦~'
            );
        } elseif ($event->message=='购买机器人') {
            $this->sendPersonChat(
                $event->user_id,
                '======购买机器人======
1.前往 https://panel.imoe.xyz/ 登录你的账号（用户名为机器人管理员用户名
2.配置你的机器人
3.我可爱，请给我钱~
之后logos会帮你充值的啦~'
            );
        } elseif ($event->message=='插件列表') {
            $result="======插件列表======\n";
            $plugins=scandir(App::basePath().'/Plugin');
            foreach ($plugins as $plugin) {
                if ((substr($plugin, strlen($plugin)-5)=='.phar'
                    ||substr($plugin, strlen($plugin)-3)=='.js')
                    && $plugin!='Plugin.php') {
                    $result.=$plugin."\n";
                }
            }
            $result.='如果你需要查看插件的帮助发送插件名给我就行啦~';
            $this->sendPersonChat(
                $event->user_id,
                $result
            );
        } else {
            if (substr($event->message, strlen($event->message)-5)=='.phar'
                ||substr($event->message, strlen($event->message)-3)=='.js') {
                if (!strpos($event->message, '/')
                    && file_exists(Plugin::phar($event->message,'help.txt'))
                    && $event->message!='Plugin.php') {
                    $this->sendPersonChat(
                        $event->user_id,
                        file_get_contents(Plugin::phar($event->message,'help.txt'))
                    );
                } else {
                    $this->sendPersonChat(
                        $event->user_id,
                        '很抱歉，我们没找到你需要的插件的文档'
                    );
                }
            } else {
                $this->sendPersonChat(
                    $event->user_id,
                    '我是人工智能，聊天功能很快就会完成啦~如果你要购买机器人请发送菜单'
                );
            }
        }
    }
}

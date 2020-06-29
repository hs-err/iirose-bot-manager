<?php /**
       * @noinspection DuplicatedCode
       */

namespace App\Modules;

use App\Event\BoardCastEvent;
use App\Event\ChatEvent;
use App\Event\GotoEvent;
use App\Event\InfoEvent;
use App\Event\JoinEvent;
use App\Event\LeaveEvent;
use App\Event\NoUserEvent;
use App\Event\PayEvent;
use App\Event\PersonChatEvent;
use App\Event\UserInfoEvent;
use App\Utils\BotUtils;
use App\Utils\InputUtils;
use App\Utils\OutPutUtils;
use App\Utils\PacketUtils;
use Co;
use Exception;
use Illuminate\Support\Facades\App;
use SplQueue;
use Swoole\Coroutine\http\Client;
use Swoole\ExitException;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\StreamOutput;
use Throwable;

class RunBot extends PacketUtils
{
    use BotUtils;
    /** @var RunBot $instance */
    public static $instance;
    /** @var Application $command */
    public $command;

    public $inited;
    public $exit_now;

    public $timer_u;
    public $room;
    public $username;
    public $password;
    public $plugin_list;
    public $plugins;
    public $start_time;
    public $queue;

    public $cid;

    public $info_lock;
    public $info_answer;

    /**
     * @var UserInfoEvent[] $user_cache
     */
    public $user_cache;

    public function __construct($username, $password, $room, $plugin_list, $config, $bot)
    {
        self::$instance=$this;
        $this->username = $username;
        $this->password = $password;
        $this->room = $room;
        $this->plugins = [];
        $this->plugin_list = json_decode($plugin_list, true);
        $this->config = json_decode($config, true);
        $this->info_lock = false;
        $this->exit_now = false;
        $this->inited = false;
        $this->runBot = $this;
        $this->bot=$bot;
        $this->user_cache=[];
        $this->queue=new SplQueue();
        $this->warn('构造');
        $this->exec();
    }

    private function solve($s)
    {
        $s = @gzdecode(substr($s, 1)) ?: $s;
        $dump = explode('<', $s);
        foreach ($dump as $p) {
            $this->parse($p);
        }
    }

    public function exec()
    {
        $this->command = new Application('IIROSE机器人：'.$this->username,'V1.0.0');

        $this->load();
        go(
            function () {
                $this->info('连接ws服务器');
                $this->connect();
                $this->info('ws服务器准备就绪');

                $this->info('创建时钟');
                $this->start_time = time();
                $this->info('时钟准备就绪');
                $this->info('logos启动完成');
            }
        );
    }

    public function connect()
    {
        try {
            $client = new Client('m.iirose.com', 443, true);
            $ret = $client->upgrade('/');
            if ($ret) {
                $this->client=$client;
                foreach ($this->plugins as $plugin) {
                    /**
 * @var Plugin $plugin
*/
                    $this->log('[EVENT]Client ' . $plugin->getPluginName());
                    $plugin->setClient($this->client);
                }
                $this->info('ws连接完成');
                $handle = '*' . json_encode(
                    [
                        'r' => $this->room,
                        'n' => $this->username,
                        'p' => md5($this->password),
                        'st' => 'n',
                        'mo' => '',
                        'cp' => microtime() . '1090',
                        'mu' => '01',
                        'nt' => '!6',
                        'mb' => '',
                        'fp' => '@' . md5($this->username)
                    ]
                );
                if (!$client->push($handle)) {
                    $this->warn('登陆包发送失败，等待watch dog');
                    return;
                }
                $this->inited = true;
                $client->push('=^v#');
                $client->push(')#');
                $client->push('>#');
                $this->info('登陆包已发送');
                $this->info('接收线程已创建');
                $this->timer_u = swoole_timer_tick(
                    120000, function () {
                        if ($this->client && $this->client->getStatusCode() == 101) {
                            $this->push('');
                            return;
                        }
                        $this->exit();
                    }
                );
                go(
                    function () use ($client) {
                        //ws接收线
                        $cid = Co::getuid();
                        $this->cid = $cid;
                        while (true) {
                            if ($this->exit_now) {
                                return;
                            }
                            if ($client) {
                                $receive = @$client->recv()->data;
                            } else {
                                $this->info('接收线阵亡，等待watch dog');
                                return;
                            }
                            go(
                                function () use ($receive) {
                                    $this->solve($receive);
                                }
                            );
                            co::sleep(0.1);
                        }
                    }
                );
                go(
                    function () use ($client) {
                        //发生线
                        while (true) {
                            try {
                                while (!$this->queue->isEmpty()) {
                                    $client->push($this->queue->pop());
                                }
                                co::sleep(0.1);
                            } catch (Exception $e) {
                                break;
                            }
                        }
                        $this->exit();
                    }
                );
            } else {
                $this->warn('连接失败，等待watch dog');
                $this->exit();
            }
        } catch (Exception $e) {
            $this->warn($e->getMessage());
            $this->exit();
        }
    }

    public function load()
    {
        foreach ($this->plugin_list as $plugin) {
            if (substr($plugin, strlen($plugin) - 4) == '.php') {
                $this->info('正在载入：' . $plugin);
                $plugin_class = 'Plugin\\' . substr($plugin, 0, strlen($plugin) - 4);
                $this->plugins[] = new $plugin_class($this, $this->client, $this->config, $this->bot);
            }
            if (substr($plugin, strlen($plugin) - 5) == '.phar') {
                $this->info('正在载入：' . $plugin);
                spl_autoload_register(function ($class) use($plugin){
                    $file = str_replace('\\', '/', $class).'.php';
                    @include Plugin::phar($plugin,$file);
                });
                $pluginClass = 'Plugin\\' . substr($plugin, 0, strlen($plugin) - 5);
                $pluginModule=new $pluginClass($this, $this->client, $this->config, $this->bot);
                $this->plugins[] = $pluginModule;
                $commands=json_decode(file_get_contents(Plugin::phar($plugin,'commands.json')));
                foreach ($commands as $command){
                    $this->command->add(new Command($pluginModule,$command));
                }
            }
        }
    }

    public function exit()
    {
        die();
    }

    public function __destruct()
    {
        // TODO: Implement __destruct() method.
    }

    private function parse($s)
    {
        $first = substr($s, 0, 1);
        $this->log('[PACKET]' . $s);
        $a = $this->explode($s);
        if ($first == '=' && count($a) == 6) {
            $this->eventBoardCast($s);
            return;
        }
        if (count($a) == 10) {
            $this->eventPersonChat($s);
            return;
        }
        if (count($a) == 11) {
            $this->eventChat($s);
            return;
        }
        if (count($a) == 12) {
            if ($a[3] == '\'1') {
                $this->eventJoin($s);
            } elseif ($a[3] == '\'3') {
                $this->eventLeave($s);
            } elseif (substr($a[3], 0, 1) == '\'') {
                $this->eventGoto($s);
            } elseif ($a[5] == 'n') {
                $this->eventUserInfo($s);
            }
            return;
        }
        if (count($a) == 23) {
            $this->eventInfo($s);
            return;
        }
        if ($s == '+') {
            $this->eventNoUser($s);
            return;
        }
        if (count($a) == 7) {
            if (substr($s, 0, 2) == '@*' && substr($a[3], 0, 2) == '\'$') {
                $this->eventPay($s);
            }
            return;
        }
    }

    private function eventPay($s)
    {
        $a = $this->explode($s);
        $event = new PayEvent(
            $a[6],
            substr($a[0], 2),
            $a[1],
            $a[5],
            substr($a[3], 2)
        );
        $this->onPay(
            $event
        );
    }

    private function onPay(
        PayEvent $event
    ) {
        if ($event->time < $this->start_time) {
            return;
        }
        foreach ($this->plugins as $plugin) {
            /**
 * @var Plugin $plugin
*/
            $plugin->onPay($event);
        }
    }

    private function eventNoUser($s)
    {
        $this->log('[EVENT]For');
        $this->onNoUser(new NoUserEvent());
    }

    private function onNoUser(
        NoUserEvent $event
    ) {
        $this->info_answer = false;
        foreach ($this->plugins as $plugin) {
            /**
 * @var Plugin $plugin
*/
            $plugin->onNoUser($event);
        }
    }

    private function eventUserInfo($s)
    {
        $a = $this->explode($s);
        $event = new UserInfoEvent();
        $event->user_id=$a[8];
        $event->username=$a[2];
        $event->color=$a[3];
        $event->room_id=$a[4];
        $event->online=$a[1];
        $this->onUserInfo(
            $event
        );
    }

    private function onUserInfo(
        UserInfoEvent $event
    ) {
        $this->info_answer = $event;
        $this->user_cache[$event->username]=$event;
        foreach ($this->plugins as $plugin) {
            /**
 * @var Plugin $plugin
*/
            $plugin->onUserInfo($event);
        }
    }

    private function eventInfo($s)
    {
        $a = $this->explode($s);
        $event = new InfoEvent();
        $event->user_id = $a[3];
        $event->first_name = $a[5];
        $event->second_name = $a[6];
        $event->birth_day = $a[7];
        $event->address = $a[8];
        $event->website = $a[9];
        $event->hobby = $a[10];
        $event->friends = $a[11];
        $event->info = $a[12];
        $event->info_background = $a[13];
        $event->last_active = $a[16];
        $event->view = $a[17];
        $event->fans = $a[22];
        $event->likes = $a[21];
        $event->count = $a[21];
        $this->onInfo(
            $event
        );
    }

    private function onInfo(
        InfoEvent $event
    ) {
        $this->info_answer = $event;
        foreach ($this->plugins as $plugin) {
            /**
 * @var Plugin $plugin
*/
            $plugin->onInfo($event);
        }
    }

    private function eventChat($s)
    {
        $a = $this->explode($s);
        $event = new ChatEvent(
            $a[3],
            $a[4],
            $a[10],
            $a[8],
            $a[2],
            $a[9],
            $a[1],
            $a[0]
        );
        $this->onChat(
            $event
        );
    }

    private function onChat(
        ChatEvent $event
    ) {
        if ($event->time < $this->start_time) {
            return;
        }
        if ($event->user_name == $this->username) {
            return;
        }
        if(substr($event->message,0,1)=='/'){
            $output=new BufferedOutput();
            try {
                RunBot::$instance->command->run(new InputUtils(substr($event->message, 1),$event), $output);
            }catch(ExitException $e){

            }catch (Throwable $e) {
                $output->write($e->getMessage(),true);
                $output->write($e->getTraceAsString(),true);
            }
            $d=$output->fetch();
            if(strlen($d)){
                $this->sendChat('\\\\\\='.$d,$event->color?:null);
            }
        }
        foreach ($this->plugins as $plugin) {
            /**
             * @var Plugin $plugin
            */
            $plugin->onChat($event);
        }
    }

    private function eventPersonChat($s)
    {
        $a = $this->explode($s);
        $event = new PersonChatEvent(
            $a[3],
            $a[4],
            $a[9],
            substr($a[0], 1),
            $a[1],
            $a[2]
        );
        $this->onPersonChat(
            $event
        );
    }

    private function onPersonChat(
        PersonChatEvent $event
    ) {
        if(substr($event->message,0,1)=='/'){
            $output=new BufferedOutput();
            try {
                RunBot::$instance->command->run(new InputUtils(substr($event->message, 1),$event), $output);
            }catch(ExitException $e){

            }catch (Throwable $e) {
                $output->write($e->getMessage(),true);
                $output->write($e->getTraceAsString(),true);
            }
            $d=$output->fetch();
            if(strlen($d)) {
                $this->sendPersonChat($event->user_id, '\\\\\\=' . $output->fetch(), $event->color ?: null);
            }
        }
        foreach ($this->plugins as $plugin) {
            /**
             * @var Plugin $plugin
            */
            $plugin->onPersonChat($event);
        }
    }

    private function eventBoardCast($s)
    {
        $a = $this->explode($s);
        $event = new BoardCastEvent(
            $a[1],
            $a[2],
            substr($a[0], 1),
            $a[3],
            $a[5]
        );
        $this->onBoardCast($event);
    }

    private function onBoardCast(
        BoardCastEvent $event
    ) {
        if ($event->user_name == $this->username) {
            return;
        }
        foreach ($this->plugins as $plugin) {
            /**
 * @var Plugin $plugin
*/
            $plugin->onBoardCast($event);
        }
    }

    private function eventJoin($s)
    {
        $a = $this->explode($s);
        $event = new JoinEvent(
            $a[5],
            $a[8],
            $a[2],
            $a[9],
            $a[1],
            $a[0]
        );
        $this->onJoin(
            $event
        );
    }

    private function onJoin(
        JoinEvent $event
    ) {
        if ($event->time < $this->start_time) {
            return;
        }
        if ($event->user_name == $this->username) {
            return;
        }
        foreach ($this->plugins as $plugin) {
            /**
 * @var Plugin $plugin
*/
            $plugin->onJoin($event);
        }
    }

    private function eventLeave($s)
    {
        $a = $this->explode($s);
        $event = new LeaveEvent(
            $a[5],
            $a[8],
            $a[2],
            $a[9],
            $a[1],
            $a[0]
        );
        $this->onLeave(
            $event
        );
    }

    private function onLeave(
        LeaveEvent $event
    ) {
        if ($event->time < $this->start_time) {
            return;
        }
        foreach ($this->plugins as $plugin) {
            /**
 * @var Plugin $plugin
*/
            $plugin->onLeave($event);
        }
    }

    private function eventGoto($s)
    {
        $a = $this->explode($s);
        $event = new GotoEvent(
            $a[5],
            $a[8],
            $a[2],
            $a[9],
            $a[1],
            substr($a[11], 1),
            $a[0]
        );
        $this->onGoto(
            $event
        );
    }

    private function onGoto(
        GotoEvent $event
    ) {
        if ($event->time < $this->start_time) {
            return;
        }
        foreach ($this->plugins as $plugin) {
            /**
 * @var Plugin $plugin
*/
            $this->log('[EVENT]For ' . $plugin->getPluginName());
            $plugin->onGoto($event);
        }
    }


    public function warn($warn)
    {
        $this->log('[WARN]' . $warn);
        echo $this->username . ':' . posix_getpid()."\033[38;5;1m[WARN]\033[0m" . $warn . "\n";
    }

    public function info($info)
    {
        $this->log('[INFO]' . $info);
        echo $this->username . ':' . posix_getpid() ."\033[32;5;1m[INFO]\033[0m" . $info . "\n";
    }

    public function log($log)
    {
        file_put_contents('log.log', '(' . date('Y-m-d H:i:s') . ')' . $log . "\n", FILE_APPEND);
    }

    protected function explode($data)
    {
        $dump = explode('>', $data);
        $result = [];
        foreach ($dump as $p) {
            $result[] = html_entity_decode($p);
        }
        return $result;
    }

    public function push($data)
    {
        $this->queue->push($data);
    }
}

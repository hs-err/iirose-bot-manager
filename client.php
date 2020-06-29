<?php
spl_autoload_register(function ($class_name) {
    @include str_replace('\\','/',$class_name) . '.php';
});
require_once 'vendor/autoload.php';
use Illuminate\Database\Capsule\Manager as DB;
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
$bots=[];
//new bot('hs_err','PoweredBy1090','5e15a14fed59f',['Assert'],[]);
$bot_list=[];
$capsule = new \Illuminate\Database\Capsule\Manager;
$capsule->addConnection([
    'driver' => 'mysql',
    'host' => env('DB_HOST'),
    'database' => env('DB_DATABASE'),
    'username' => env('DB_USERNAME'),
    'password' => env('DB_PASSWORD'),
    'charset' => 'utf8mb4',
    'port' => env('DB_PORT'),
    'collation' => 'utf8mb4_general_ci'
]);
$capsule->setAsGlobal();
$capsule->bootEloquent();
//$bots['logosuuid']=new bot('logos','PoweredBy1090','5e15a14fed59f',['Assert'],[]);
swoole_timer_tick(2000, function (){
    try {
        global $bots, $bot_list;
        $bot_list = [];
        $all_bots = DB::table('bots')->where('end', '>', date('Y-m-d H:i:s'))->get();
        /**$all_bots=[[
         * 'id' => 1,
         * 'end' => '2022-03-10 18:08:39',
         * 'username' => 'logos',
         * 'password' => 'PoweredBy1090',
         * 'room' => '5e15a14fed59f',
         * 'plugins' => serialize([
         * 'Assert',
         * 'Welcome',
         * 'Pay'
         * ]),
         * 'config' =>serialize([
         *
         * ]),
         * ]];**/
        foreach ($all_bots as $per_bot) {
            $bot_data = (array)$per_bot;
            $bot_list[md5(serialize($bot_data))] = (array)$bot_data;
        }
        foreach ($bot_list as $uuid => $per_list) {
            // 检查bot是否加载
            if (!@$bots[$uuid]) {
                $bots[$uuid] = new runBot(
                    $per_list['username'],
                    $per_list['password'],
                    $per_list['room'],
                    json_decode($per_list['plugins'], true),
                    json_decode($per_list['config'], true));
            } else {
                if (!$bots[$uuid]->run()) {
                    $bots[$uuid]->destruct();
                    unset($bots[$uuid]);
                }
            }
        }
        // 检查bot是否在bot_list存在
        foreach ($bots as $uuid => $bot) {
            if (!@$bot_list[$uuid]) {
                $bots[$uuid]->destruct();
                unset($bots[$uuid]);
            }
        }
    }catch (Exception $e){
        var_dump($e->getTrace());
    }
});

<?php


namespace Plugin;

use App\Event\ChatEvent;
use App\Modules\DockerClient;
use App\Modules\Plugin;
use App\Modules\Sender;
use Co;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Docker extends Plugin
{
    /** @var DockerClient[] $user */
    private $user;
    public function init()
    {
        $this->user=[];
        go(function () {
            while (true) {
                foreach ($this->user as $k => $v) {
                    if (!$v->get()) {
                        $this->sendChat($this->at($v->user_name).'到守护进程的连接丢失~感谢你的使用');
                        unset($this->user[$k]);
                        unset($v);
                    }
                }
                Co::sleep(0.1);
            }
        });
    }

    public function onCommand(string $sign, Sender $sender, InputInterface $input, OutputInterface $output)
    {
        if($sign=='docker:attach'){
            go(function ()use($input,$sender){
                $message=implode(' ',$input->getArgument('shell'));
                if (@$this->user[$sender->getUserId()]) {
                    try {
                        $this->user[$sender->getUserId()]->push($message);
                    } catch (Exception $e) {
                        unset($this->user[$sender->getUserId()]);
                        $sender->sendMessage('到守护进程的连接丢失，大概是超时了...可怜的大喵，logos帮你创建一个连接趴');
                        $dc = new DockerClient($sender->getUsername(), $sender->getUserId(), function ($message) use ($sender) {
                            $sender->sendMessage($this->at($sender->getUsername())."\n".$message);
                        }, $this);
                        if ($dc->init()) {
                            $dc->push($message);
                        } else {
                            $sender->sendMessage($this->at($sender->getUsername())."\n".'揉揉~DOCKER被用完了喵~你等一等趴');
                        }
                        $this->user[$sender->getUserId()]=$dc;
                    }
                } else {
                    $sender->sendMessage($this->at($sender->getUsername()).'，logos帮你创建一个新连接趴');
                    $dc = new DockerClient($sender->getUsername(), $sender->getUserId(), function ($message) use ($sender) {
                        $sender->sendMessage($this->at($sender->getUsername()).$message);
                    }, $this);
                    if ($dc->init()) {
                        $dc->push($message);
                    } else {
                        $sender->sendMessage($this->at($sender->getUsername()).'揉揉~DOCKER被用完了喵~你等一等趴');
                    }
                    $this->user[$sender->getUserId()]=$dc;
                }
            });
        }
    }
}

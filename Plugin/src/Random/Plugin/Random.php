<?php


namespace Plugin;

use App\Event\ChatEvent;
use App\Event\PersonChatEvent;
use App\Modules\Plugin;
use App\Modules\Sender;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Random extends Plugin
{
    public function onCommand(string $sign, Sender $sender, InputInterface $input, OutputInterface $output)
    {
        if ($sign == 'math:random') {
            $start=$input->getArgument('start');
            $end=$input->getArgument('end');
            $times=$input->getArgument('times');
            $seed=$input->getOption('seed')?:microtime(true)*10000;
            if ($start >= $end) {
                $output->write('结束 必须大于 起始');
                return;
            }
            if ($times<1) {
                $output->write('次数必须大于1');
                return;
            }
            if ($times>100) {
                $output->write('次数必须小于100');
                return;
            }
            $output->write($this->rand($seed, $start, $end, $times));
        }
    }
    private function rand($seed, $start, $end, $times = 1)
    {
        $return="=========随机数配置=========
随机数种子：$seed
随机数算法：MT19937
随机数起始：$start
随机数结束：$end
随机数数量：$times
=========随机数结果=========\n";
        for ($i=0; $i<$times; $i++) {
            $return.=($i+1).'：'.$this->random($seed, $start, $end, $i)."\n";
        }
        $return.="=========重现指令=========
/random";
        if ($times!=1) {
            $return.=" $start $end $times";
        } elseif ($end!=6) {
            $return.=" $start $end";
        } elseif ($start!=1) {
            $return.=" $start";
        }
        $return.=" -seed $seed";
        return $return;
    }
    private function random($seed, $min, $max, $i)
    {
        $rand=$this->hex2int(md5(md5($seed).'_1090520_'.$min.'_'.$max.'_'.$i));
        return bcadd($min,(int)bcmod($rand,bcsub(bcadd(1,$max),$min)));
    }
    private function hex2int($hex)
    {
        $len = strlen($hex);
        $dec =0;
        for ($i = 1; $i <= $len; $i++) {
            $dec = bcadd($dec, bcmul(strval(hexdec($hex[$i - 1])), bcpow('16', strval($len - $i))));
        }
        return $dec;
    }
}

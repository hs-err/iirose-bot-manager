<?php


namespace Plugin;

use App\Event\ChatEvent;
use App\Modules\Plugin;
use App\Modules\RunBot;
use App\Modules\Sender;
use App\Utils\OutPutUtils;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\OutputInterface;
use Yitznewton\TwentyFortyEight\Game;
use Yitznewton\TwentyFortyEight\Move\Move;
use Yitznewton\TwentyFortyEight\Output\ConsoleOutput;

class Game2048 extends Plugin
{
    /** @var Game[] $user */
    private $user;
    public function init()
    {
        $this->user=[];
    }
    public function onCommand(string $sign, Sender $sender, InputInterface $input, OutputInterface $output)
    {
        if ($sign == 'game:2048') {
            $message = $input->getArgument('where');
            if (!@$this->user[$sender->getUserId()] || @$this->user[$sender->getUserId()]->over) {
                $this->user[$sender->getUserId()] = new Game(4, 2048, new ConsoleOutput());
                $this->user[$sender->getUserId()]->run();
            }
            switch ($message) {
                case 'w':
                    $move = Move::UP;
                    break;
                case 'a':
                    $move = Move::LEFT;
                    break;
                case 's':
                    $move = Move::DOWN;
                    break;
                case 'd':
                    $move = Move::RIGHT;
                    break;
                default:
                    $output->write($this->at($sender->getUsername()) . '喵呜~喵喵不理解,,,用wasd吧？', true);
                    return;
            }
            $output->write($this->at($sender->getUsername()) . "\n" . $this->user[$sender->getUserId()]->go($move), true);
        }
    }
}

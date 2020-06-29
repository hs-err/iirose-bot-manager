<?php


namespace App\Utils;


use App\Event\ChatEvent;
use App\Event\PersonChatEvent;
use App\Modules\Sender;
use Symfony\Component\Console\Input\StringInput;

class InputUtils extends StringInput
{
    /** @var Sender $sender */
    private $sender;
    public function __construct(string $input,$event)
    {
        if($event instanceof ChatEvent){
            $this->sender=new Sender(Sender::ROOM,$event->user_name,$event->user_id,$event->color);
        }elseif ($event instanceof PersonChatEvent){
            $this->sender=new Sender(Sender::PERSON,$event->user_name,$event->user_id,$event->color);
        }
        parent::__construct($input);
    }
    /**
     * @return Sender
     */
    public function getSender(){
        return $this->sender;
    }
}

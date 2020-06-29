<?php

namespace App\Event;

class PersonChatEvent
{
    public $message;
    public $color;
    public $user_id;
    public $id;
    public $user_name;
    public $user_icon;

    public function __construct(
        $message,
        $color,
        $id,
        $user_id,
        $user_name,
        $user_icon
    ) {
        $this->message = $message;
        $this->color = $color;
        $this->color = $id;
        $this->user_id = $user_id;
        $this->user_name = $user_name;
        $this->user_icon = $user_icon;
    }
}

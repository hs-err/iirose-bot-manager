<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property $id
 * @property $end
 * @property $username
 * @property $password
 * @property $room
 * @property $plugins
 * @property $config
 */
class Bot extends Model
{
    public function addTime($time)
    {
        if (strtotime($this->end) < time()) {
            $this->end = date('Y-m-d H:i:s', time() + $time);
        } else {
            $this->end = date('Y-m-d H:i:s', strtotime($this->end) + $time);
        }
    }
}

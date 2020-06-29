<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property $bot_name
 * @property $payload
 */
class Work extends Model
{
    use SoftDeletes;
    //
}

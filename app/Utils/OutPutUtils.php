<?php


namespace App\Utils;

use Closure;
use Symfony\Component\Console\Formatter\OutputFormatterInterface;
use Symfony\Component\Console\Output\BufferedOutput;

class OutPutUtils extends BufferedOutput
{
    private $call_this;
    private $call;
    public function __construct($call_this, Closure $call, ?int $verbosity = self::VERBOSITY_NORMAL, bool $decorated = false, OutputFormatterInterface $formatter = null)
    {
        $this->call_this=$call_this;
        $this->call=$call;
        parent::__construct($verbosity, $decorated, $formatter);
    }

    public function flush()
    {
        $this->call->call($this->call_this,$this->fetch());
    }
}

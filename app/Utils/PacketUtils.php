<?php

namespace App\Utils;

class PacketUtils
{
    private $packet;

    protected function pack($data)
    {
        $this->send('<"' . addslashes(serialize($data)) . '>"');
    }

    protected function unpack($data)
    {
        $this->packet .= $data;
        $start = false;
        //  < " s : 3 : \ " a a a \ " ; > " < " s : 3 : \ " b b b \ " ; > "
        // 0 1 2 3 4 5 6 7 8
        $n = 0;
        while (true) {
            if ($start) {
                $pos = strpos($this->packet, '>"', $n);
                if ($pos !== false) {
                    $this->recv(unserialize(stripslashes(substr($this->packet, $n + 2, $pos - $n - 2))));
                    $n = $pos + 2;
                } else {
                    break;
                }
            } else {
                $pos = strpos($this->packet, '<"', $n);
                if ($pos !== false) {
                    $n = $pos;// Ex:0
                    $start = true;
                } else {
                    break;
                }
            }
        }
        $this->packet = substr($this->packet, $n);
    }

    protected function send($data)
    {
    }

    protected function recv($data)
    {
    }
}

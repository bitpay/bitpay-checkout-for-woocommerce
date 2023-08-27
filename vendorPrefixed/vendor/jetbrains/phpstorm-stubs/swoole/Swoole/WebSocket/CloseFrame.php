<?php

declare (strict_types=1);
namespace BitPayVendor\Swoole\WebSocket;

class CloseFrame extends Frame
{
    public $opcode = 8;
    public $code = 1000;
    public $reason = '';
}

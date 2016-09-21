<?php

namespace ReactCassandra\Async;

class Client extends \ReactCassandra\AbstractClient
{
    public function __construct(\React\EventLoop\LoopInterface $eventLoop, array $options)
    {
        $options['async'] = true;
        parent::__construct($eventLoop, $options);
    }

    public function connect()
    {
        return parent::connect();
    }
}
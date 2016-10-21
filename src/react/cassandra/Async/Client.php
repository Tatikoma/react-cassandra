<?php

namespace React\Cassandra\Async;

class Client extends \React\Cassandra\AbstractClient
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
<?php
namespace ReactCassandra;

class Client extends AbstractClient
{
    public function __construct(\React\EventLoop\LoopInterface $eventLoop, array $options)
    {
        $options['async'] = false;
        parent::__construct($eventLoop, $options);
    }

    public function connect()
    {
        return parent::connect();
    }
}
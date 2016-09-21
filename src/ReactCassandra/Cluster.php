<?php

namespace ReactCassandra;

class Cluster
{
    /**
     * @var array List of Cassandra cluster server IP address to connect
     */
    public $servers = ['127.0.0.1'];
    /**
     * @var \React\EventLoop\LoopInterface
     */
    public $eventLoop = null;
    /**
     * @var array Cassandra PHP client instances
     */
    public $clients = [];

    public function __construct(\React\EventLoop\LoopInterface $eventLoop, $options)
    {
        $this->eventLoop = $eventLoop;
        if (isset($options['servers'])) {
            $this->servers = $options['servers'];
        }

        $this->init();
    }

    public function init()
    {
        foreach ($this->servers as $k => $server) {

        }
    }
}
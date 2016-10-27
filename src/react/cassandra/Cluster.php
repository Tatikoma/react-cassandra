<?php

namespace Tatikoma\React\Cassandra;

class Cluster extends \Tatikoma\React\Cassandra\Async\Cluster
{

    public function __construct($serverOptions)
    {
        $loop = \React\EventLoop\Factory::create();
        parent::__construct($loop, $serverOptions);
    }

    /**
     * @param string $keyspace
     * @return mixed
     */
    public function connect($keyspace = '')
    {
        return \Clue\React\Block\await(parent::connect($keyspace), $this->loop);
    }

    /**
     * @param string $cql
     * @param array $params
     * @param int $consistency
     * @return \Tatikoma\React\Cassandra\Protocol\ResultFrame
     */
    public function query($cql, $params = [], $consistency = \Tatikoma\React\Cassandra\Constants::CONSISTENCY_ONE)
    {
        return \Clue\React\Block\await(parent::query($cql, $params, $consistency), $this->loop);
    }

    public function close()
    {
        foreach ($this->connected as $client) {
            \Clue\React\Block\await($client->close(), $this->loop);
        }
    }
}
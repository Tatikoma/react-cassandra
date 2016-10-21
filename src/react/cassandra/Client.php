<?php
namespace React\Cassandra;

class Client extends AbstractClient
{
    /**
     * @var \React\EventLoop\LoopInterface
     */
    protected $eventLoop;

    public function __construct(\React\EventLoop\LoopInterface $eventLoop, array $options)
    {
        $this->eventLoop = $eventLoop;
        $options['async'] = false;
        parent::__construct($eventLoop, $options);
    }

    public function connect()
    {
        return \Clue\React\Block\await(parent::connect(), $this->eventLoop);
    }

    public function query($cql, $params = [], $consistency = Constants::CONSISTENCY_ONE)
    {
        return \Clue\React\Block\await(parent::query($cql, $params, $consistency), $this->eventLoop);
    }
}
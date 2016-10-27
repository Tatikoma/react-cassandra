<?php
namespace Tatikoma\React\Cassandra;

abstract class AbstractClient extends \Evenement\EventEmitter
{

    /**
     * @var array Список серверов для подключения
     */
    public $options = [
        'host' => 'localhost', // Cassandra hostname or IP-address
        'port' => 9042, // Cassandra CQL binary port
        'async' => false, // Use asynchronous or synchronous connection
        'timeout' => 1.0, // Connection timeout
    ];

    /**
     * @var \React\EventLoop\LoopInterface event loop
     */
    public $eventLoop = null;

    /**
     * @var \React\Stream\Stream Stream connection to CQL binary port
     */
    public $stream = null;

    /**
     * @var int frame sequence number (stream_id)
     */
    public $streamId = 0;

    /**
     * @var int Статус подключения
     */
    public $status = Constants::CLIENT_CLOSED;

    /**
     * @var \React\Promise\Deferred
     */
    public $deferredClose = null;

    /**
     * @var \React\Promise\Deferred
     */
    public $deferredConnect = null;

    /**
     * @var string Incoming data buffer
     */
    public $buffer = '';

    /**
     * @var \React\Promise\Deferred[] key=>value list of deferred packets ([stream_id => Deferred object])
     */
    public $deferredPackets = [];

    /**
     * AbstractClient constructor.
     * @param \React\EventLoop\LoopInterface $eventLoop
     * @param array $options
     */
    public function __construct(\React\EventLoop\LoopInterface $eventLoop, $options = [])
    {
        $this->eventLoop = $eventLoop;
        if (isset($options['host'])) {
            $this->options['host'] = $options['host'];
        }
        if (isset($options['port'])) {
            $this->options['port'] = $options['port'];
        }
        if (isset($options['async'])) {
            $this->options['async'] = $options['async'];
        }
    }

    /**
     * Connects to Cassandra CQL native transport port
     */
    public function connect()
    {
        if ($this->status != Constants::CLIENT_CLOSED) {
            throw new \Tatikoma\React\Cassandra\Exception('Cannot connect client when connection is not closed');
        }

        $this->deferredConnect = new \React\Promise\Deferred();

        $connectionString = "tcp://{$this->options['host']}:{$this->options['port']}";

        $flags = STREAM_CLIENT_CONNECT;
        if ($this->options['async']) {
            $flags |= STREAM_CLIENT_ASYNC_CONNECT;
        }

        $this->status = Constants::CLIENT_CONNECTING;
        $socket = @stream_socket_client($connectionString, $errno, $errstr, (float)$this->options["timeout"], $flags);
        if ($socket === false) {
            throw new Exception(strtr('Cannot create socket connection, code :code error :error', [
                ':code' => $errno,
                ':error' => $errstr
            ]));
        }
        $this->stream = new \React\Stream\Stream($socket, $this->eventLoop);
        $this->stream->on('data', [$this, 'onData']);
        $this->stream->on('error', [$this, 'onError']);
        $this->stream->on('close', [$this, 'onClose']);

        $this->startup();

        return $this->deferredConnect->promise();
    }

    public function startup()
    {
        $frame = new \Tatikoma\React\Cassandra\Protocol\StartupFrame();
        $frame->fromParams();
        $frame->stream_id = $this->getNextStreamId();
        return $this->sendFrame($frame);
    }

    public function getNextStreamId()
    {
        if ($this->streamId > 0x7FFF) {
            $this->streamId = 0;
        }
        if (isset($this->deferredPackets[$this->streamId])) {
            throw new Exception('Cannot get stream id, too many deferred packets');
        }
        return $this->streamId++;
    }

    public function sendFrame(\Tatikoma\React\Cassandra\Protocol\AbstractFrame $frame)
    {
        $this->deferredPackets[$frame->stream_id] = new \React\Promise\Deferred();
        $this->stream->write($frame->toBytes());
        return $this->deferredPackets[$frame->stream_id]->promise();
    }

    public function query($cql, $params = [], $consistency = Constants::CONSISTENCY_ONE)
    {
        $frame = new \Tatikoma\React\Cassandra\Protocol\QueryFrame();
        $frame->fromParams([
            'cql' => $cql,
            'consistency' => $consistency,
            'params' => $params,
        ]);
        $frame->stream_id = $this->getNextStreamId();
        return $this->sendFrame($frame);
    }

    public function onData($readData = null)
    {
        $this->buffer .= $readData;
        do {
            $frame = null;
            $bytesParsed = \Tatikoma\React\Cassandra\Protocol\AbstractFrame::parseBuffer($this->buffer, $frame);
            if (!is_null($frame)) {
                $this->onFrame($frame);
            }
            if ($bytesParsed > 0) {
                $this->buffer = substr($this->buffer, $bytesParsed);
            }
        } while ($bytesParsed > 0);
    }

    public function onFrame(\Tatikoma\React\Cassandra\Protocol\AbstractFrame $frame)
    {
        switch (true) {
            case $frame instanceof \Tatikoma\React\Cassandra\Protocol\ErrorFrame:
                if (isset($this->deferredPackets[$frame->stream_id])) {
                    $this->deferredPackets[$frame->stream_id]->reject(new Exception(strtr('Error code :code, :error', [
                        ':code' => $frame->errorCode,
                        ':error' => $frame->errorString,
                    ])));
                    unset($this->deferredPackets[$frame->stream_id]);
                }
                switch ($this->status) {
                    case Constants::CLIENT_CONNECTING:
                        $this->status = Constants::CLIENT_CONNECTED;
                        $this->close();
                        break;
                    case Constants::CLIENT_CONNECTED:
                        $this->close();
                        break;
                    case Constants::CLIENT_CLOSING:
                        $this->status = Constants::CLIENT_CLOSED;
                        $this->stream = null;
                        break;
                }
                break;
            case $frame instanceof \Tatikoma\React\Cassandra\Protocol\ReadyFrame:
                $this->status = Constants::CLIENT_CONNECTED;
                if ($this->deferredConnect instanceof \React\Promise\Deferred) {
                    $this->deferredConnect->resolve($this);
                }
                break;
        }
        if (isset($this->deferredPackets[$frame->stream_id])) {
            $this->deferredPackets[$frame->stream_id]->resolve($frame);
            unset($this->deferredPackets[$frame->stream_id]);
        }
    }

    /**
     * Closes connection to Cassandra server
     * @return \React\Promise\PromiseInterface
     */
    public function close()
    {
        if ($this->status != Constants::CLIENT_CONNECTED) {
            return \React\Promise\reject(new Exception('Cannot close connection while client is not connected'));
        }

        $this->deferredClose = new \React\Promise\Deferred();

        $this->status = Constants::CLIENT_CLOSING;
        $this->stream->close();
        $this->stream = null;

        if ($this->status == Constants::CLIENT_CLOSED) {
            return \React\Promise\resolve($this);
        } else {
            return \React\Promise\reject(new Exception('Cannot close connection'));
        }
    }

    public function onError($e = null)
    {
        if ($e instanceof \Exception) {
            $message = $e->getMessage();
        } else {
            $message = var_export($e, 1);
        }
        $this->emit('error', [$this, 'Cassandra socket connection error ' . $message]);
    }

    public function onClose()
    {
        if ($this->status != Constants::CLIENT_CLOSING) {
            throw new \Tatikoma\React\Cassandra\Exception('Cassandra socket connection unexpectedly closed');
        }
        $this->stream = null;
        $this->status = Constants::CLIENT_CLOSED;
        if ($this->deferredClose instanceof \React\Promise\Deferred) {
            $this->deferredClose->resolve($this);
            $this->deferredClose = null;
        }
        if ($this->deferredConnect instanceof \React\Promise\Deferred) {
            $this->deferredConnect->reject(new Exception('Connection closed'));
            $this->deferredConnect = null;
        }
        foreach ($this->deferredPackets as $deferred) {
            $deferred->reject(new Exception('Connection closed'));
        }
        $this->deferredPackets = [];
        $this->emit('close', [$this]);
    }
}
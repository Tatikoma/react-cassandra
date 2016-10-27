<?php

namespace Tatikoma\React\Cassandra\Protocol;

abstract class AbstractFrame
{
    /**
     * @var int Frame direction (0 = client to server, 0x80 = server to client)
     */
    public $frame_direction = 0x00;

    /**
     * @var int Protocol version in use
     */
    public $protocol_version = 0x04;
    /**
     * @var int Flags applying to this frame.
     */
    public $flags;
    /**
     * @var int A frame has a stream id.
     * If a client sends a request message with the stream id X, it is guaranteed that the stream id of the response to that message will be X.
     */
    public $stream_id;
    /**
     * @var int An integer byte that distinguishes the actual message:
     */
    public $opcode;
    /**
     * @var int A 4 byte integer representing the length of the body of the frame
     */
    public $length;

    public static function parseBuffer($buffer, &$frame)
    {
        if (strlen($buffer) < \Tatikoma\React\Cassandra\Constants::FRAME_SIZE_MIN) {
            return 0;
        }
        $header = unpack('Cversion/Cflags/nstream/Copcode/Nlength', substr($buffer, 0, \Tatikoma\React\Cassandra\Constants::FRAME_SIZE_MIN));
        if (strlen($buffer) < $header['length'] + \Tatikoma\React\Cassandra\Constants::FRAME_SIZE_MIN) {
            return 0;
        }

        if ($header['flags'] & \Tatikoma\React\Cassandra\Constants::FRAME_FLAG_COMPRESSION) {
            throw new \Tatikoma\React\Cassandra\Exception('Compression flag not implemented yet');
        }
        if ($header['flags'] & \Tatikoma\React\Cassandra\Constants::FRAME_FLAG_TRACING) {
            throw new \Tatikoma\React\Cassandra\Exception('Tracing flag not implemented yet');
        }
        if ($header['flags'] & \Tatikoma\React\Cassandra\Constants::FRAME_FLAG_PAYLOAD) {
            throw new \Tatikoma\React\Cassandra\Exception('Payload flag not implemented yet');
        }
        if ($header['flags'] & \Tatikoma\React\Cassandra\Constants::FRAME_FLAG_WARNING) {
            throw new \Tatikoma\React\Cassandra\Exception('Warning flag not implemented yet');
        }
        if ($header['length'] > \Tatikoma\React\Cassandra\Constants::FRAME_SIZE_LIMIT) {
            throw new \Tatikoma\React\Cassandra\Exception(strtr('Got too large frame. Frame size :actual, maximum size is :expected', [
                ':actual' => $header['length'],
                ':expected' => \Tatikoma\React\Cassandra\Constants::FRAME_SIZE_LIMIT,
            ]));
        }


        $payload = substr($buffer, \Tatikoma\React\Cassandra\Constants::FRAME_SIZE_MIN, $header['length']);
        switch ($header['opcode']) {
            case \Tatikoma\React\Cassandra\Constants::OPCODE_ERROR:
                $frame = new ErrorFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_STARTUP:
                $frame = new StartupFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_READY:
                $frame = new ReadyFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_AUTHENTICATE:
                $frame = new AuthenticateFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_OPTIONS:
                $frame = new OptionsFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_SUPPORTED:
                $frame = new SupportedFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_QUERY:
                $frame = new QueryFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_RESULT:
                $frame = new ResultFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_PREPARE:
                $frame = new PrepareFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_EXECUTE:
                $frame = new ExecuteFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_REGISTER:
                $frame = new RegisterFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_EVENT:
                $frame = new EventFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_BATCH:
                $frame = new BatchFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_AUTH_CHALLENGE:
                $frame = new AuthChallengeFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_AUTH_RESPONSE:
                $frame = new AuthResponseFrame;
                break;
            case \Tatikoma\React\Cassandra\Constants::OPCODE_AUTH_SUCCESS:
                $frame = new AuthSuccessFrame;
                break;

            default:
                throw new \Tatikoma\React\Cassandra\Exception(strtr('Got unknown frame opcode: :opcode', [
                    ':opcode' => $header['opcode'],
                ]));
                break;
        }

        $frame->frame_direction = $header['version'] & 0x80;
        $frame->protocol_version = $header['version'] & 0x7F;
        $frame->flags = $header['flags'];
        $frame->stream_id = $header['stream'];
        $frame->opcode = $header['opcode'];
        $frame->length = $header['length'];

        $frame->fromBytes($payload);

        return \Tatikoma\React\Cassandra\Constants::FRAME_SIZE_MIN + $header['length'];
    }

    public function fromParams($params)
    {
        $allowedParams = ['frame_direction', 'protocol_version', 'flags', 'stream_id', 'opcode', 'length'];
        foreach ($allowedParams as $param) {
            if (isset($params[$param])) {
                $this->$param = $params[$param];
            }
        }
    }

    /**
     * @param string $frame Binary frame without frame header
     * @return string Binary frame with frame header
     * @throws \Tatikoma\React\Cassandra\Exception
     */
    public function writeHeader($frame = '')
    {
        if (is_null($this->stream_id)) {
            throw new \Tatikoma\React\Cassandra\Exception('Cannot write frame header for frame without stream id');
        }
        $frame = pack('CCnCN',
                $this->frame_direction | $this->protocol_version,
                $this->flags,
                $this->stream_id,
                $this->opcode,
                strlen($frame)
            ) . $frame;
        return $frame;
    }

    public function readString($buffer, &$position)
    {
        $stringLength = $this->readShort($buffer, $position);
        $string = substr($buffer, $position, $stringLength);
        $position += $stringLength;
        return $string;
    }

    public function readShort($buffer, &$position)
    {
        $read = unpack('nshort', substr($buffer, $position, 2));
        $position += 2;
        return $read['short'];
    }

    public function readBytes($buffer, &$position)
    {
        $bytesLength = $this->readInt($buffer, $position);
        if ($bytesLength & 0x80000000) {
            // if negative then null
            // readInt reads unsigned value, so checking sign bit
            return null;
        }
        $bytes = substr($buffer, $position, $bytesLength);
        $position += $bytesLength;
        return $bytes;
    }

    public function readInt($buffer, &$position)
    {
        $read = unpack('Nint', substr($buffer, $position, 4));
        $position += 4;
        return $read['int'];
    }

    /**
     * @return string
     * @throws \Tatikoma\React\Cassandra\Exception
     */
    public function toBytes()
    {
        throw new \Tatikoma\React\Cassandra\Exception('Cannot call toBytes method of Abstract Frame');
    }
}
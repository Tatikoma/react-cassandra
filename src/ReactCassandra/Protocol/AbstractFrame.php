<?php

namespace ReactCassandra\Protocol;

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
        if (strlen($buffer) < \ReactCassandra\Constants::FRAME_SIZE_MIN) {
            return 0;
        }
        $header = unpack('Cversion/Cflags/nstream/Copcode/Nlength', substr($buffer, 0, \ReactCassandra\Constants::FRAME_SIZE_MIN));
        if (strlen($buffer) < $header['length'] + \ReactCassandra\Constants::FRAME_SIZE_MIN) {
            return 0;
        }

        if ($header['flags'] & \ReactCassandra\Constants::FRAME_FLAG_COMPRESSION) {
            throw new \ReactCassandra\Exception('Compression flag not implemented yet');
        }
        if ($header['flags'] & \ReactCassandra\Constants::FRAME_FLAG_TRACING) {
            throw new \ReactCassandra\Exception('Tracing flag not implemented yet');
        }
        if ($header['flags'] & \ReactCassandra\Constants::FRAME_FLAG_PAYLOAD) {
            throw new \ReactCassandra\Exception('Payload flag not implemented yet');
        }
        if ($header['flags'] & \ReactCassandra\Constants::FRAME_FLAG_WARNING) {
            throw new \ReactCassandra\Exception('Warning flag not implemented yet');
        }
        if ($header['length'] > \ReactCassandra\Constants::FRAME_SIZE_LIMIT) {
            throw new \ReactCassandra\Exception(strtr('Got too large frame. Frame size :actual, maximum size is :expected', [
                ':actual' => $header['length'],
                ':expected' => \ReactCassandra\Constants::FRAME_SIZE_LIMIT,
            ]));
        }


        $payload = substr($buffer, \ReactCassandra\Constants::FRAME_SIZE_MIN, $header['length']);
        switch ($header['opcode']) {
            case \ReactCassandra\Constants::OPCODE_ERROR:
                $frame = new ErrorFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_STARTUP:
                $frame = new StartupFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_READY:
                $frame = new ReadyFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_AUTHENTICATE:
                $frame = new AuthenticateFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_OPTIONS:
                $frame = new OptionsFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_SUPPORTED:
                $frame = new SupportedFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_QUERY:
                $frame = new QueryFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_RESULT:
                $frame = new ResultFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_PREPARE:
                $frame = new PrepareFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_EXECUTE:
                $frame = new ExecuteFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_REGISTER:
                $frame = new RegisterFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_EVENT:
                $frame = new EventFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_BATCH:
                $frame = new BatchFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_AUTH_CHALLENGE:
                $frame = new AuthChallengeFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_AUTH_RESPONSE:
                $frame = new AuthResponseFrame;
                break;
            case \ReactCassandra\Constants::OPCODE_AUTH_SUCCESS:
                $frame = new AuthSuccessFrame;
                break;

            default:
                throw new \ReactCassandra\Exception(strtr('Got unknown frame opcode: :opcode', [
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

        return \ReactCassandra\Constants::FRAME_SIZE_MIN + $header['length'];
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
     * @throws \ReactCassandra\Exception
     */
    public function writeHeader($frame = '')
    {
        if (is_null($this->stream_id)) {
            throw new \ReactCassandra\Exception('Cannot write frame header for frame without stream id');
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
     * @throws \ReactCassandra\Exception
     */
    public function toBytes()
    {
        throw new \ReactCassandra\Exception('Cannot call toBytes method of Abstract Frame');
    }
}
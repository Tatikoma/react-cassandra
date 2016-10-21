<?php
namespace React\Cassandra\Protocol;

class QueryFrame extends AbstractFrame
{

    public $cql;
    public $consistency;
    public $params = [];

    public function fromBytes($bytes = "")
    {
        throw new \React\Cassandra\Exception('Not implemented yet');
    }

    public function fromParams($params = [])
    {
        $this->opcode = \React\Cassandra\Constants::OPCODE_QUERY;

        if (isset($params['cql'])) {
            $this->cql = $params['cql'];
        }
        if (isset($params['consistency'])) {
            $this->consistency = $params['consistency'];
        }
        if (isset($params['params'])) {
            $this->params = $params['params'];
        }
    }

    public function toBytes()
    {
        $flags = \React\Cassandra\Constants::QUERY_FLAG_VALUES | \React\Cassandra\Constants::QUERY_FLAG_WITH_NAMES_FOR_VALUES;

        $packet = FrameHelper::writeLongString($this->cql);
        $packet .= FrameHelper::writeShort($this->consistency);
        $packet .= FrameHelper::writeByte($flags);
        $packet .= FrameHelper::writeShort(count($this->params));
        foreach ($this->params as $k => $v) {
            if (is_object($v)) {
                $v = (string)$v;
            }
            $packet .= FrameHelper::writeString($k);
            switch (true) {
                case is_null($v):
                    $packet .= FrameHelper::writeInt(-1);
                    break;
                case !isset($v):
                    // this 'll never happens, but we have some code for this
                    $packet .= FrameHelper::writeInt(-2);
                    break;
                case is_int($v):
                    $v = FrameHelper::writeInt($v);
                    $packet .= FrameHelper::writeInt(strlen($v)) . $v;
                    break;
                default:
                    $packet .= FrameHelper::writeInt(strlen($v)) . $v;
                    break;
            }
        }
        $packet = parent::writeHeader($packet);

        return $packet;
    }
}
<?php
namespace ReactCassandra\Protocol;

class QueryFrame extends AbstractFrame
{

    public $cql;
    public $consistency;
    public $params = [];

    public function fromBytes($bytes = "")
    {
        throw new \ReactCassandra\CassandraException('Not implemented yet');
    }

    public function fromParams($params = [])
    {
        $this->opcode = \ReactCassandra\Constants::OPCODE_QUERY;

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
        $flags = \ReactCassandra\Constants::QUERY_FLAG_VALUES | \ReactCassandra\Constants::QUERY_FLAG_WITH_NAMES_FOR_VALUES;

        $packet = parent::writeLongString($this->cql);
        $packet .= parent::writeShort($this->consistency);
        $packet .= parent::writeByte($flags);
        $packet .= parent::writeShort(count($this->params));
        foreach ($this->params as $k => $v) {
            if (is_object($v)) {
                $v = (string)$v;
            }
            $packet .= parent::writeString($k);
            switch (true) {
                case is_null($v):
                    $packet .= parent::writeInt(-1);
                    break;
                case !isset($v):
                    // this 'll never happens, but we have some code for this
                    $packet .= parent::writeInt(-2);
                    break;
                case is_int($v):
                    $v = parent::writeInt($v);
                    $packet .= parent::writeInt(strlen($v)) . $v;
                    break;
                default:
                    $packet .= parent::writeInt(strlen($v)) . $v;
                    break;
            }
        }
        $packet = parent::writeHeader($packet);

        return $packet;
    }
}
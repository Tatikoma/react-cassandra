<?php
namespace ReactCassandra\Protocol;

class ResultFrame extends AbstractFrame implements \Iterator
{

    /**
     * @var int an [int] representing the `kind` of result. The rest of the body depends on the kind.
     */
    public $kind;

    /**
     * @var int The bits of <flags> provides information on the
     * formatting of the remaining information. A flag is set if the bit
     * corresponding to its `mask` is set.
     */
    public $flags;

    /**
     * @var string indicating the name of the keyspace that has been set.
     */
    public $keyspace;

    /**
     * @var array Resulting array
     */
    public $results = [];

    /**
     * @var int Iterator position
     */
    public $position = 0;

    public function fromBytes($bytes = "")
    {
        $position = 0;
        $this->kind = $this->readInt($bytes, $position);
        switch ($this->kind) {
            case \ReactCassandra\Constants::RESULT_VOID:
                break;
            case \ReactCassandra\Constants::RESULT_ROWS:
                $this->flags = $this->readInt($bytes, $position);
                if (!$this->flags & \ReactCassandra\Constants::RESULT_FLAG_GLOBAL_TABLE_SPEC) {
                    // Flag is NOT set
                    throw new \ReactCassandra\CassandraException('Not implemented yet');
                }
                if ($this->flags & \ReactCassandra\Constants::RESULT_FLAG_HAS_MORE_PAGES) {
                    throw new \ReactCassandra\CassandraException('Not implemented yet');
                }
                if ($this->flags & \ReactCassandra\Constants::RESULT_FLAG_NO_METADATA) {
                    throw new \ReactCassandra\CassandraException('Not implemented yet');
                }
                $columnsCount = $this->readInt($bytes, $position);
                $keyspace = $this->readString($bytes, $position);;
                $tableName = $this->readString($bytes, $position);
                $schema = [];
                for ($i = 0; $i < $columnsCount; $i++) {
                    $fieldName = $this->readString($bytes, $position);
                    $fieldType = $this->readShort($bytes, $position);
                    $schema[$i] = [
                        'name' => $fieldName,
                        'type' => $fieldType,
                    ];
                }
                $rowsCount = $this->readInt($bytes, $position);
                $this->results = [];
                for ($i = 0; $i < $rowsCount; $i++) {
                    $this->results[$i] = [];
                    for ($j = 0; $j < $columnsCount; $j++) {
                        $value = $this->readBytes($bytes, $position);
                        switch ($schema[$j]['type']) {
                            case \ReactCassandra\Constants::FIELD_TYPE_CUSTOM:
                            case \ReactCassandra\Constants::FIELD_TYPE_UUID:
                                $value = \ReactCassandra\Type\UUID::parse($value);
                                break;
                            case \ReactCassandra\Constants::FIELD_TYPE_TIMESTAMP:
                                // bigint
                                $value = \ReactCassandra\Type\BigInt::parse($value);
                                break;
                            case \ReactCassandra\Constants::FIELD_TYPE_VARCHAR:
                                // got empty string, so i cannot test it
                                break;
                            case \ReactCassandra\Constants::FIELD_TYPE_INT:
                                $value = unpack('N', $value)[1];
                                break;
                            case \ReactCassandra\Constants::FIELD_TYPE_BLOB:
                                // bigint again, wtf?
                                $value = \ReactCassandra\Type\BigInt::parse($value);
                                break;
                            case \ReactCassandra\Constants::FIELD_TYPE_INET:
                                if (strlen($value) != 4) {
                                    throw new \ReactCassandra\CassandraException('Only inet v4 implemented yet');
                                }
                                $value = \ReactCassandra\Type\Inet::parse($value);
                                break;
                            case \ReactCassandra\Constants::FIELD_TYPE_ASCII:
                            case \ReactCassandra\Constants::FIELD_TYPE_BOOLEAN:
                            case \ReactCassandra\Constants::FIELD_TYPE_COUNTER:
                            case \ReactCassandra\Constants::FIELD_TYPE_DECIMAL:
                            case \ReactCassandra\Constants::FIELD_TYPE_DOUBLE:
                            case \ReactCassandra\Constants::FIELD_TYPE_FLOAT:
                            case \ReactCassandra\Constants::FIELD_TYPE_VARINT:
                            case \ReactCassandra\Constants::FIELD_TYPE_TIMEUUID:
                            case \ReactCassandra\Constants::FIELD_TYPE_DATE:
                            case \ReactCassandra\Constants::FIELD_TYPE_TIME:
                            case \ReactCassandra\Constants::FIELD_TYPE_SMALLINT:
                            case \ReactCassandra\Constants::FIELD_TYPE_TINYINT:
                            case \ReactCassandra\Constants::FIELD_TYPE_LIST:
                            case \ReactCassandra\Constants::FIELD_TYPE_MAP:
                            case \ReactCassandra\Constants::FIELD_TYPE_SET:
                            case \ReactCassandra\Constants::FIELD_TYPE_UDT:
                            case \ReactCassandra\Constants::FIELD_TYPE_TUPLE:
                                throw new \ReactCassandra\CassandraException(strtr('Field type :type is not implemented yet', [
                                    ':type' => $schema[$j]['type'],
                                ]));
                                break;
                            default:
                                throw new \ReactCassandra\CassandraException(strtr('Got unknown field type :type', [
                                    ':type' => $schema[$j]['type'],
                                ]));
                                break;
                        }
                        $this->results[$i][$schema[$j]['name']] = $value;
                    }
                }

                break;
            case \ReactCassandra\Constants::RESULT_SET_KEYSPACE:
                $this->keyspace = $this->readString($bytes, $position);
                break;
            case \ReactCassandra\Constants::RESULT_PREPARED:
                throw new \ReactCassandra\CassandraException('Not implemented yet');
                break;
            case \ReactCassandra\Constants::RESULT_SCHEMA_CHANGE:
                throw new \ReactCassandra\CassandraException('Not implemented yet');
                break;
            default:
                throw new \ReactCassandra\CassandraException(strtr('Got unknown result kind :kind', [
                    ':kind' => $this->kind,
                ]));
                break;
        }
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function current()
    {
        return $this->results[$this->position];
    }

    public function key()
    {
        return $this->position;
    }

    public function next()
    {
        ++$this->position;
    }

    public function valid()
    {
        return isset($this->results[$this->position]);
    }
}
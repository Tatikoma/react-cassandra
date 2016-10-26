<?php
namespace React\Cassandra\Protocol;

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

    public $change_type;
    public $change_target;
    public $change_options;

    /**
     * @var int Iterator position
     */
    public $position = 0;

    public function fromBytes($bytes = "")
    {
        $position = 0;
        $this->kind = $this->readInt($bytes, $position);
        switch ($this->kind) {
            case \React\Cassandra\Constants::RESULT_VOID:
                break;
            case \React\Cassandra\Constants::RESULT_ROWS:
                $this->flags = $this->readInt($bytes, $position);
                if (!$this->flags & \React\Cassandra\Constants::RESULT_FLAG_GLOBAL_TABLE_SPEC) {
                    // Flag is NOT set
                    throw new \React\Cassandra\Exception('Not implemented yet');
                }
                if ($this->flags & \React\Cassandra\Constants::RESULT_FLAG_HAS_MORE_PAGES) {
                    throw new \React\Cassandra\Exception('Not implemented yet');
                }
                if ($this->flags & \React\Cassandra\Constants::RESULT_FLAG_NO_METADATA) {
                    throw new \React\Cassandra\Exception('Not implemented yet');
                }
                $columnsCount = $this->readInt($bytes, $position);
                $keyspace = $this->readString($bytes, $position);;
                $tableName = $this->readString($bytes, $position);
                $schema = [];
                for ($i = 0; $i < $columnsCount; $i++) {
                    $fieldName = $this->readString($bytes, $position);
                    $fieldType = $this->readShort($bytes, $position);
                    $subType = null;
                    switch ($fieldType) {
                        case \React\Cassandra\Constants::FIELD_TYPE_LIST:
                            $subType = $this->readShort($bytes, $position);
                            break;
                    }
                    $schema[$i] = [
                        'name' => $fieldName,
                        'type' => $fieldType,
                        'subtype' => $subType,
                    ];
                }
                $rowsCount = $this->readInt($bytes, $position);
                $this->results = [];
                for ($i = 0; $i < $rowsCount; $i++) {
                    $this->results[$i] = [];
                    for ($j = 0; $j < $columnsCount; $j++) {
                        $value = $this->readBytes($bytes, $position);
                        switch ($schema[$j]['type']) {
                            case \React\Cassandra\Constants::FIELD_TYPE_CUSTOM:
                            case \React\Cassandra\Constants::FIELD_TYPE_UUID:
                                $value = \React\Cassandra\Type\UUID::parse($value);
                                break;
                            case \React\Cassandra\Constants::FIELD_TYPE_TIMESTAMP:
                                // bigint
                                $value = \React\Cassandra\Type\BigInt::parse($value);
                                break;
                            case \React\Cassandra\Constants::FIELD_TYPE_VARCHAR:
                                // got empty string, so i cannot test it
                                break;
                            case \React\Cassandra\Constants::FIELD_TYPE_INT:
                                $value = unpack('N', $value)[1];
                                break;
                            case \React\Cassandra\Constants::FIELD_TYPE_BLOB:
                                // bigint again, wtf?
                                $value = \React\Cassandra\Type\BigInt::parse($value);
                                break;
                            case \React\Cassandra\Constants::FIELD_TYPE_INET:
                                if (strlen($value) != 4) {
                                    throw new \React\Cassandra\Exception('Only inet v4 implemented yet');
                                }
                                $value = \React\Cassandra\Type\Inet::parse($value);
                                break;
                            case \React\Cassandra\Constants::FIELD_TYPE_LIST:
                                if ($value != "") {
                                    switch ($schema[$j]['subtype']) {
                                        case \React\Cassandra\Constants::FIELD_TYPE_INT:
                                            // @todo create & use \Type\Integer for parsing
                                            $result = [];
                                            $fieldPosition = 0;
                                            $numberOfElements = $this->readInt($value, $fieldPosition);
                                            for ($i = 0; $i < $numberOfElements; $i++) {
                                                $item = unpack('Nint', $this->readBytes($value, $fieldPosition));
                                                $result[] = $item['int'];
                                            }
                                            $value = $result;
                                            break;
                                        default:
                                            throw new \React\Cassandra\Exception('Only integer field list implemented yet');
                                            break;
                                    }
                                } else {
                                    $value = [];
                                }
                                break;
                            case \React\Cassandra\Constants::FIELD_TYPE_ASCII:
                            case \React\Cassandra\Constants::FIELD_TYPE_BOOLEAN:
                            case \React\Cassandra\Constants::FIELD_TYPE_COUNTER:
                            case \React\Cassandra\Constants::FIELD_TYPE_DECIMAL:
                            case \React\Cassandra\Constants::FIELD_TYPE_DOUBLE:
                            case \React\Cassandra\Constants::FIELD_TYPE_FLOAT:
                            case \React\Cassandra\Constants::FIELD_TYPE_VARINT:
                            case \React\Cassandra\Constants::FIELD_TYPE_TIMEUUID:
                            case \React\Cassandra\Constants::FIELD_TYPE_DATE:
                            case \React\Cassandra\Constants::FIELD_TYPE_TIME:
                            case \React\Cassandra\Constants::FIELD_TYPE_SMALLINT:
                            case \React\Cassandra\Constants::FIELD_TYPE_TINYINT:
                            case \React\Cassandra\Constants::FIELD_TYPE_MAP:
                            case \React\Cassandra\Constants::FIELD_TYPE_SET:
                            case \React\Cassandra\Constants::FIELD_TYPE_UDT:
                            case \React\Cassandra\Constants::FIELD_TYPE_TUPLE:
                                throw new \React\Cassandra\Exception(strtr('Field type :type is not implemented yet', [
                                    ':type' => $schema[$j]['type'],
                                ]));
                                break;
                            default:
                                throw new \React\Cassandra\Exception(strtr('Got unknown field type :type', [
                                    ':type' => $schema[$j]['type'],
                                ]));
                                break;
                        }
                        $this->results[$i][$schema[$j]['name']] = $value;
                    }
                }

                break;
            case \React\Cassandra\Constants::RESULT_SET_KEYSPACE:
                $this->keyspace = $this->readString($bytes, $position);
                break;
            case \React\Cassandra\Constants::RESULT_PREPARED:
                throw new \React\Cassandra\Exception('Not implemented yet');
                break;
            case \React\Cassandra\Constants::RESULT_SCHEMA_CHANGE:
                $this->change_type = $this->readString($bytes, $position);
                $this->change_target = $this->readString($bytes, $position);
                switch ($this->change_target) {
                    case 'KEYSPACE':
                        $this->change_options = [
                            'keyspace' => $this->readString($bytes, $position),
                        ];
                        break;
                    case 'TABLE':
                        $this->change_options = [
                            'keyspace' => $this->readString($bytes, $position),
                            'table' => $this->readString($bytes, $position),
                        ];
                        break;
                    case 'TYPE':
                        $this->change_options = [
                            'keyspace' => $this->readString($bytes, $position),
                            'type' => $this->readString($bytes, $position),
                        ];
                        break;
                    case 'FUNCTION':
                    case 'AGGREGATE':
                        $this->change_options = [
                            'keyspace' => $this->readString($bytes, $position),
                            'function' => $this->readString($bytes, $position),
                            'arguments' => [],
                        ];
                        $numberOfArguments = $this->readShort($bytes, $position);
                        for ($i = 0; $i < $numberOfArguments; $i++) {
                            $this->change_options['arguments'][] = $this->readString($bytes, $position);
                        }
                        break;
                    default:
                        throw new \React\Cassandra\Exception('Not implemented yet');
                        break;
                }
                break;
            default:
                throw new \React\Cassandra\Exception(strtr('Got unknown result kind :kind', [
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
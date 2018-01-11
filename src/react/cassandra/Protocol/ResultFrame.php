<?php
namespace Tatikoma\React\Cassandra\Protocol;

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
            case \Tatikoma\React\Cassandra\Constants::RESULT_VOID:
                break;
            case \Tatikoma\React\Cassandra\Constants::RESULT_ROWS:
                $this->flags = $this->readInt($bytes, $position);
                if (!$this->flags & \Tatikoma\React\Cassandra\Constants::RESULT_FLAG_GLOBAL_TABLE_SPEC) {
                    // Flag is NOT set
                    throw new \Tatikoma\React\Cassandra\Exception('Not implemented yet');
                }
                if ($this->flags & \Tatikoma\React\Cassandra\Constants::RESULT_FLAG_HAS_MORE_PAGES) {
                    throw new \Tatikoma\React\Cassandra\Exception('Not implemented yet');
                }
                if ($this->flags & \Tatikoma\React\Cassandra\Constants::RESULT_FLAG_NO_METADATA) {
                    throw new \Tatikoma\React\Cassandra\Exception('Not implemented yet');
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
                        case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_LIST:
                        case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_SET:
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
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_CUSTOM:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_UUID:
                                $value = \Tatikoma\React\Cassandra\Type\UUID::parse($value);
                                break;
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_TIMESTAMP:
                                // bigint
                                $value = \Tatikoma\React\Cassandra\Type\BigInt::parse($value);
                                break;
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_VARCHAR:
                                // got empty string, so i cannot test it
                                break;
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_INT:
                                if ($value !== null) {
                                    $value = unpack('N', $value)[1];
                                }
                                break;
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_BLOB:
                                // bigint again, wtf?
                                $value = \Tatikoma\React\Cassandra\Type\BigInt::parse($value);
                                break;
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_INET:
                                if ($value !== null) {
                                    if (strlen($value) != 4) {
                                        throw new \Tatikoma\React\Cassandra\Exception('Only inet v4 implemented yet');
                                    }
                                    $value = \Tatikoma\React\Cassandra\Type\Inet::parse($value);
                                }
                                break;
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_LIST:
                                if ($value != "") {
                                    switch ($schema[$j]['subtype']) {
                                        case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_INT:
                                            // @todo create & use \Type\Integer for parsing
                                            $result = [];
                                            $fieldPosition = 0;
                                            $numberOfElements = $this->readInt($value, $fieldPosition);
                                            for ($elementNum = 0; $elementNum < $numberOfElements; $elementNum++) {
                                                $item = unpack('Nint', $this->readBytes($value, $fieldPosition));
                                                $result[] = $item['int'];
                                            }
                                            $value = $result;
                                            break;
                                        default:
                                            throw new \Tatikoma\React\Cassandra\Exception('Only integer field list implemented yet');
                                            break;
                                    }
                                } else {
                                    $value = [];
                                }
                                break;
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_SET:
                                if ($value != "") {
                                    switch ($schema[$j]['subtype']) {
                                        case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_INT:
                                            // @todo create & use \Type\Integer for parsing
                                            $result = [];
                                            $fieldPosition = 0;
                                            $numberOfElements = $this->readInt($value, $fieldPosition);
                                            for ($elementNum = 0; $elementNum < $numberOfElements; $elementNum++) {
                                                $item = unpack('Nint', $this->readBytes($value, $fieldPosition));
                                                $result[] = $item['int'];
                                            }
                                            $value = $result;
                                            break;
                                        case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_UUID:
                                            $result = [];
                                            $fieldPosition = 0;
                                            $numberOfElements = $this->readInt($value, $fieldPosition);
                                            for ($elementNum = 0; $elementNum < $numberOfElements; $elementNum++) {
                                                $item = $this->readBytes($value, $fieldPosition);
                                                $item = \Tatikoma\React\Cassandra\Type\UUID::parse($item);
                                                $result[] = $item;
                                            }
                                            $value = $result;
                                            break;
                                        case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_VARCHAR:
                                            $result = [];
                                            $fieldPosition = 0;
                                            $numberOfElements = $this->readInt($value, $fieldPosition);
                                            for ($elementNum = 0; $elementNum < $numberOfElements; $elementNum++) {
                                                $result[] = $this->readBytes($value, $fieldPosition);
                                            }
                                            $value = $result;
                                            break;
                                        default:
                                            throw new \Tatikoma\React\Cassandra\Exception('Only integer field list implemented yet');
                                            break;
                                    }
                                } else {
                                    $value = [];
                                }
                                break;
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_ASCII:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_BOOLEAN:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_COUNTER:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_DECIMAL:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_DOUBLE:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_FLOAT:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_VARINT:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_TIMEUUID:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_DATE:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_TIME:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_SMALLINT:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_TINYINT:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_MAP:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_UDT:
                            case \Tatikoma\React\Cassandra\Constants::FIELD_TYPE_TUPLE:
                                throw new \Tatikoma\React\Cassandra\Exception(strtr('Field type :type is not implemented yet', [
                                    ':type' => $schema[$j]['type'],
                                ]));
                                break;
                            default:
                                throw new \Tatikoma\React\Cassandra\Exception(strtr('Got unknown field type :type', [
                                    ':type' => $schema[$j]['type'],
                                ]));
                                break;
                        }
                        $this->results[$i][$schema[$j]['name']] = $value;
                    }
                }

                break;
            case \Tatikoma\React\Cassandra\Constants::RESULT_SET_KEYSPACE:
                $this->keyspace = $this->readString($bytes, $position);
                break;
            case \Tatikoma\React\Cassandra\Constants::RESULT_PREPARED:
                throw new \Tatikoma\React\Cassandra\Exception('Not implemented yet');
                break;
            case \Tatikoma\React\Cassandra\Constants::RESULT_SCHEMA_CHANGE:
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
                        throw new \Tatikoma\React\Cassandra\Exception('Not implemented yet');
                        break;
                }
                break;
            default:
                throw new \Tatikoma\React\Cassandra\Exception(strtr('Got unknown result kind :kind', [
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
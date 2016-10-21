<?php
namespace React\Cassandra\Type;

class Collection
{

    public $value = [];

    public function __construct($value = [])
    {
        if (!is_array($this->value)) {
            throw new \React\Cassandra\Exception('Passed argument is not an array to Cassandra Collection');
        }
        $this->value = $value;
    }

    public static function parse($binary)
    {
        throw new \React\Cassandra\Exception('Not implemented yet');
    }

    public function __toString()
    {
        return self::binary($this->value);
    }

    public static function binary($value = [])
    {
        $packet = \React\Cassandra\Protocol\FrameHelper::writeInt(count($value));
        foreach ($value as $k => $v) {
            if (is_object($v)) {
                $v = (string)$v;
            }
            switch (true) {
                case is_null($v):
                    $packet .= \React\Cassandra\Protocol\FrameHelper::writeInt(-1);
                    break;
                case !isset($v):
                    // this 'll never happens, but we have some code for this
                    $packet .= \React\Cassandra\Protocol\FrameHelper::writeInt(-2);
                    break;
                case is_int($v):
                    $v = \React\Cassandra\Protocol\FrameHelper::writeInt($v);
                    $packet .= \React\Cassandra\Protocol\FrameHelper::writeInt(strlen($v)) . $v;
                    break;
                default:
                    $packet .= \React\Cassandra\Protocol\FrameHelper::writeInt(strlen($v)) . $v;
                    break;
            }
        }
        return $packet;
    }
}
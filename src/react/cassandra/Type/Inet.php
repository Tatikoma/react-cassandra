<?php
namespace Tatikoma\React\Cassandra\Type;

class Inet
{
    public $value;

    public function __construct($value = null)
    {
        if (!is_null($value)) {
            $this->value = $value;
        }
    }

    public static function parse($binary)
    {
        $unpacked = unpack('C4', $binary);
        return implode('.', $unpacked);
    }

    public function __toString()
    {
        return self::binary($this->value);
    }

    public static function binary($value)
    {
        $value = explode('.', $value);
        return pack('CCCC', $value[0], $value[1], $value[2], $value[3]);
    }
}
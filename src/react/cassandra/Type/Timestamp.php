<?php
namespace Tatikoma\React\Cassandra\Type;

class Timestamp
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
        $higher = unpack("N", substr($binary, 0, 4));
        $lower = unpack("N", substr($binary, 4, 4));

        return (($higher[1] << 32) + $lower[1]) / 1000;
    }

    public function __toString()
    {
        return self::binary($this->value);
    }

    public static function binary($value)
    {
        $value = (int)floor($value * 1000);
        return pack('NN', $value >> 32, $value & 0xFFFFFFFF);
    }
}
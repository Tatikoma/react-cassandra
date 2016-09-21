<?php
namespace ReactCassandra\Type;

class BigInt
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

        return ($higher[1] << 32) + $lower[1];
    }

    public function __toString()
    {
        return self::binary($this->value);
    }

    public static function binary($value)
    {
        return pack('NN', $value >> 32, $value & 0xFFFFFFFF);
    }
}
<?php
namespace Tatikoma\React\Cassandra\Type;

class UUID
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
        $unpacked = unpack('n8', $binary);
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x', $unpacked[1], $unpacked[2], $unpacked[3], $unpacked[4], $unpacked[5], $unpacked[6], $unpacked[7], $unpacked[8]);
    }

    public function __toString()
    {
        return self::binary($this->value);
    }

    public static function binary($value)
    {
        return pack('H*', str_replace('-', '', $value));
    }
}
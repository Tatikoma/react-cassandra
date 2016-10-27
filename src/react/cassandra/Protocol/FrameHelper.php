<?php
namespace Tatikoma\React\Cassandra\Protocol;

class FrameHelper
{
    /**
     * @param array $stringList Associative list of strings
     * @return string
     */
    static public function writeStringMap($stringList = [])
    {
        $pdu = pack('n', count($stringList));
        foreach ($stringList as $k => $v) {
            $pdu .= self::writeString($k) . self::writeString($v);
        }
        return $pdu;
    }

    /**
     * @param string $string String
     * @return string
     */
    static public function writeString($string = '')
    {
        return self::writeShort(strlen($string)) . $string;
    }

    static public function writeShort($int = 0)
    {
        return pack('n', $int);
    }

    static public function writeLongString($string = '')
    {
        return self::writeInt(strlen($string)) . $string;
    }

    static public function writeInt($int = 0)
    {
        return pack('N', $int);
    }

    static public function writeByte($int = 0)
    {
        return pack('C', $int);
    }
}
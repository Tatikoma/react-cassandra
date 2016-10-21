<?php
namespace React\Cassandra\Type\Collection;

class Integer extends \React\Cassandra\Type\Collection
{
    public $value = [];

    public function __construct($value = [])
    {
        parent::__construct($value);
        foreach ($this->value as $k => $v) {
            $this->value[$k] = intval($v);
        }
    }
}
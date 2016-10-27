<?php
namespace Tatikoma\React\Cassandra\Type\Collection;

class Integer extends \Tatikoma\React\Cassandra\Type\Collection
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
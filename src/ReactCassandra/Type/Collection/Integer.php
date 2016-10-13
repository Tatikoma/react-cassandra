<?php
namespace ReactCassandra\Type\Collection;

class Integer extends \ReactCassandra\Type\Collection
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
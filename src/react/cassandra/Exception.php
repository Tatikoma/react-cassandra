<?php

namespace Tatikoma\React\Cassandra;

class Exception extends \Exception
{
    /**
     * @var \Tatikoma\React\Cassandra\Protocol\AbstractFrame
     */
    public $frame;
}
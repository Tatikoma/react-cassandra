<?php
namespace React\Cassandra\Protocol;

class ReadyFrame extends AbstractFrame
{

    public function fromBytes($bytes = "")
    {
        // body is empty
    }
}
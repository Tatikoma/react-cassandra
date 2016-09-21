<?php
namespace ReactCassandra\Protocol;

class ReadyFrame extends AbstractFrame
{

    public function fromBytes($bytes = "")
    {
        // body is empty
    }
}
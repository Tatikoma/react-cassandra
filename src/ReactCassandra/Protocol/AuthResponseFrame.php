<?php
namespace ReactCassandra\Protocol;

class AuthResponseFrame extends AbstractFrame
{

    public function fromBytes($bytes = "")
    {
        throw new \ReactCassandra\CassandraException('Not implemented yet');
    }
}
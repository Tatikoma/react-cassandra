<?php
namespace ReactCassandra\Protocol;

class AuthSuccessFrame extends AbstractFrame
{

    public function fromBytes($bytes = "")
    {
        throw new \ReactCassandra\CassandraException('Not implemented yet');
    }
}
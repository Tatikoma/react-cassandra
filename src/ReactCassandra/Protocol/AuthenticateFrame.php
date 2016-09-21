<?php
namespace ReactCassandra\Protocol;

class AuthenticateFrame extends AbstractFrame
{

    public function fromBytes($bytes = "")
    {
        throw new \ReactCassandra\CassandraException('Not implemented yet');
    }
}
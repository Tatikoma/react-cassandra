<?php
namespace ReactCassandra\Protocol;

class PrepareFrame extends AbstractFrame
{

    public function fromBytes($bytes = "")
    {
        throw new \ReactCassandra\CassandraException('Not implemented yet');
    }
}
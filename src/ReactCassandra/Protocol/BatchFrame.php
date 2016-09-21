<?php
namespace ReactCassandra\Protocol;

class BatchFrame extends AbstractFrame
{

    public function fromBytes($bytes = "")
    {
        throw new \ReactCassandra\CassandraException('Not implemented yet');
    }
}
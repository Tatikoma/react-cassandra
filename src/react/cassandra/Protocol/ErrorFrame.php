<?php
namespace React\Cassandra\Protocol;

class ErrorFrame extends AbstractFrame
{
    /**
     * @var int Error code
     */
    public $errorCode;
    /**
     * @var string Error message
     */
    public $errorString;

    public function fromBytes($bytes = "")
    {
        $position = 0;
        $this->errorCode = $this->readInt($bytes, $position);
        $this->errorString = $this->readString($bytes, $position);
    }
}
<?php

namespace APPNAME\Networking;

/**
 * Class Connection
 */
class Connection
{
    /**
     * @var \React\Stream\DuplexStreamInterface
     */
    private $stream;

    /**
     * @var int
     */
    private $lastKeepAlive;

    /**
     * Connection constructor.
     * @param \React\Stream\DuplexStreamInterface $stream
     * @param int $lastKeepAlive
     */
    public function __construct(\React\Stream\DuplexStreamInterface $stream)
    {
        $this->stream = $stream;
    }

    /**
     * @return \React\Stream\DuplexStreamInterface
     */
    public function getStream()
    {
        return $this->stream;
    }

    /**
     * @return int
     */
    public function getLastKeepAlive()
    {
        return $this->lastKeepAlive;
    }

    /**
     * @param int $lastKeepAlive
     */
    public function setLastKeepAlive($lastKeepAlive)
    {
        $this->lastKeepAlive = $lastKeepAlive;
    }
}
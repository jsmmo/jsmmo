<?php
namespace APPNAME\Helper;


/**
 * Class SSEConnectionHelper
 */
class SSEConnectionHelper {

    public function isSSEConnectionRequest($request)
    {
        if (in_array('text/event-stream', $request->getHeader('Accept'))) {
            return true;
        }
        return false;
    }
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public function handleIncommingConnection($request, $loop, $broadcastStream) {
        if ($this->isSSEConnectionRequest($request)) {
            echo "incomming sse: ".$request->getHeaderLine('Last-Event-ID').PHP_EOL;
            return $this->getStreamingResponse($loop, $broadcastStream);
        }
    }

    /**
     * @return \React\Http\Response
     */
    public function getStreamingResponse($loop, $broadcastStream) {
        // create a stream and format it as sse data
        $privateStream = new \React\Stream\ThroughStream(function ($data) {
            if (is_string($data)) {
                return 'data: ' . $data . "\n\n";
            } else if (is_array($data)) {
                $str = '';
                foreach($data as $key => $value) {
                    $str .= "$key: $value\n";
                }
                return $str. "\n\n";
            }
        });

        // connect broadcast to private stream and merge data
        $broadcastStream->on('data', function($data) use ($loop, $privateStream){
            $privateStream->write($data);
        });

        // send connection data to browser
        return new \React\Http\Response(
            200,
            array(
                'Content-Type' => 'text/event-stream'
            ),
            $privateStream
        );
    }

}
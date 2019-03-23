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
    public function handleIncommingConnection($request, $broadcastStream) {
        if ($this->isSSEConnectionRequest($request)) {
            echo "incomming sse: ".$request->getHeaderLine('Last-Event-ID').PHP_EOL;
            return $this->getStreamingResponse($broadcastStream);
        }
    }


    public function isSSEDataRequest($request)
    {
        if (strpos($request->getUri()->getPath(),'/data') === 0) {
            return true;
        }
        return false;
    }
    /**
     * @param \Psr\Http\Message\ServerRequestInterface $request
     */
    public function handleIncommingData($request)
    {
        $event = str_replace('/data/','',$request->getUri()->getPath());
        $data = $request->getBody()->getContents();

        echo "incomming data: $event > $data " . PHP_EOL;

        return $this->returnDataReadResponse();
    }

    public function generateSSEFormatedStream() {
        return new \React\Stream\ThroughStream(function ($data) {
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
    }

    /**
     * @return \React\Http\Response
     */
    public function getStreamingResponse($broadcastStream) {
        // create a stream and format it as sse data
        $privateStream = $this->generateSseFormatedStream();

        $broadcastStream->pipe($privateStream);

        // say hello
        $broadcastStream->write(array(
            'event' => 'new_player',
            'data' => ','
        ));

        // send connection data to browser
        return new \React\Http\Response(
            200,
            array(
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache'
            ),
            $privateStream
        );
    }

    public function returnDataReadResponse() {
        // send OK to browser
        return new \React\Http\Response(
            200,
            array(
                'Content-Type' => 'text/plain'
            ),
            ''
        );
    }

}
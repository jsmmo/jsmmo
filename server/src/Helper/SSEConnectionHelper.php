<?php

namespace APPNAME\Helper;

/**
 * Class SSEConnectionHelper
 */
class SSEConnectionHelper
{

    /**
     * @var array[]
     */
    protected $connections = [];

    /**
     * @param \React\HttpClient\Request $request
     *
     * @return bool
     */
    public function isSSEConnectionRequest($request)
    {
        if (in_array('text/event-stream', $request->getHeader('Accept'))) {
            return true;
        }

        return false;
    }

    /**
     * @param \React\HttpClient\Request $request
     * @param \React\Stream\ThroughStream $broadcastStream
     *
     * @return \React\Http\Response
     */
    public function handleIncomingConnection($request, $broadcastStream)
    {
        if ($this->isSSEConnectionRequest($request)) {
            echo "incomming sse: " . $request->getHeaderLine('Last-Event-ID') . PHP_EOL;

            return $this->getStreamingResponse($broadcastStream,$request);
        }


        return new \React\Http\Response(
            500,
            array(
                'Content-Type' => 'text/html'
            ),
            '<h1>500</h1><p>Internal Server Error</p>'
        );
    }

    /**
     * @param \React\HttpClient\Request $request
     *
     * @return bool
     */
    public function isSSEDataRequest($request)
    {
        if (strpos($request->getUri()->getPath(),'/data') === 0) {
            return true;
        }

        return false;
    }

    /**
     * @param \React\HttpClient\Request $request
     *
     * @return \React\Http\Response
     */
    public function handleIncommingData($request)
    {
        $event = str_replace('/data/','',$request->getUri()->getPath());
        $tokens = explode('/',$event);
        if (count($tokens) == 2) {
            $event = $tokens[1];
            $targetId = $tokens[0];
        }

        $data = $request->getBody()->getContents();

        echo "incomming data: $event > $data " . PHP_EOL;

        // todo implement some event listener here...
        if ($event == 'keep-alive') {
            $data = \json_decode($data,true);
            $connection = $this->getConnection($data['uniqid']);
            if ($connection) {
                $connection->setLastKeepAlive(time());
            }
        }

        if (strlen($targetId)) {
            $connection = $this->getConnection($targetId);
            $connection->getStream()->write(array(
                'event' => $event,
                'data' => $data
            ));
        }

        return $this->returnDataReadResponse();
    }

    /**
     * @return \React\Stream\ThroughStream
     */
    public function generateSSEFormatedStream()
    {
        return new \React\Stream\ThroughStream(function ($data) {
            if (is_string($data)) {
                return 'data: ' . $data . "\n\n";
            } else if (is_array($data)) {
                $str = '';

                foreach($data as $key => $value) {
                    $str .= "$key: $value\n";
                }

                return $str . "\n\n";
            }

            return null;
        });
    }

    /**
     * @param \React\Stream\ThroughStream $broadcastStream
     * @param \RingCentral\Psr7\Request $request
     * @return \React\Http\Response
     */
    public function getStreamingResponse($broadcastStream,$request)
    {
        // create a stream and format it as sse data
        $privateStream = $this->generateSseFormatedStream();

        $cookies = [];
        $cookieHeaders = $request->getHeader('Cookie');
        foreach ($cookieHeaders as $cookieHeader) {
            $cookies = array_merge($cookies,$this->parseCookies($cookieHeader));
        }

        $this->connections[$cookies['uniqid']] = new \APPNAME\Connection($privateStream);

        $broadcastStream->pipe($privateStream);

        // say hello
        $broadcastStream->write(array(
            'event' => 'new_connecetion',
            'data' => json_encode(array(
                'id' => $cookies['uniqid'],
            ))
        ));

        // send connection data to browser
        return new \React\Http\Response(
            200,
            array(
                'Content-Type' => 'text/event-stream',
                'Cache-Control' => 'no-cache',
            ),
            $privateStream
        );
    }

    /**
     * @return \React\Http\Response
     */
    public function returnDataReadResponse()
    {
        // send OK to browser
        return new \React\Http\Response(
            200,
            array(
                'Content-Type' => 'text/plain'
            ),
            ''
        );
    }


    private function parseCookies($cookieString) {
        parse_str(strtr($cookieString, array('&' => '%26', '+' => '%2B', ';' => '&')), $cookies);

        return $cookies;
    }

    /**
     * @return array[]
     */
    public function getConnections()
    {
        return $this->connections;
    }

    public function getConnection($id) {
        if (isset($this->connections[$id])) {
            return $this->connections[$id];
        }
        return false;
    }
    public function removeConnection($id) {
        unset($this->connections[$id]);
    }

}
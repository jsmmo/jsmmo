<?php

namespace APPNAME\Helper;

/**
 * Class ErrorPageHelper
 */
class ErrorPageHelper
{
    /**
     * @return \React\Http\Response
     */
    public function return404Page()
    {
        return new \React\Http\Response(
            404,
            array(
                'Content-Type' => 'text/html'
            ),
            '<h1>404</h1><p>Resource not found</p>'
        );
    }
}
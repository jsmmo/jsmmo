<?php
namespace APPNAME\Helper;


/**
 * Class ErrorPageHelper
 */
class ErrorPageHelper {

    public function return404Page($request) {
        return new \React\Http\Response(
            404,
            array(
                'Content-Type' => 'text/html'
            ),
            '<h1>404</h1><p>Resource not found</p>'
        );
    }

}
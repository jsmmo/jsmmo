<?php
namespace APPNAME\Helper;

/**
 * Class StaticFileDeliveryHelper
 */
class StaticFileDeliveryHelper
{
    /**
     * Static file mappings
     * @var array
     */
    protected static $staticMapping = array(
        '/' => 'index.html'
    );

    /**
     * @param $request
     *
     * @return bool
     */
    public function isStaticFile($request)
    {
        $file = $this->getCleanPath(__DIR__ . '/../../../client/' . $this->mapPath($request->getUri()->getPath()));

        echo $file;

        if (file_exists($file))
        {
            return true;
        }

        return false;
    }

    /**
     * @param $request
     *
     * @return \React\Http\Response
     */
    public function deliverStaticFile($request)
    {
        $file = $this->getCleanPath(__DIR__ . '/../../../client/' . $this->mapPath($request->getUri()->getPath()));

        return new \React\Http\Response(
            200,
            array(
                'Content-Type' => 'text/html'
            ),
            file_get_contents($file)
        );
    }

    /**
     * cleans up the path
     *
     * @param $path
     * @return bool|string
     */
    private function getCleanPath($path)
    {
        return realpath($path);
    }

    /**
     * maps the file to a static file path
     *
     * @param $file
     * @return mixed
     */
    private function mapPath($file)
    {
        if (isset(static::$staticMapping[$file])) {
            return static::$staticMapping[$file];
        }

        return $file;
    }
}
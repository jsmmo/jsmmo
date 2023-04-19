<?php
/**
 * Created by PhpStorm.
 * Author: Philip Maaß
 * Date: 19.04.23
 * Time: 15:57
 * License
 */

namespace APPNAME\Service;

class RandomService
{
    /**
     * Fetches a random number for using as a seed from an external source.
     *
     * @return int
     */
    public static function getSeed() : int
    {
        $url = "https://rnd.is/number?min=1000&max=1000000000";
        $json = file_get_contents($url);
        $data = json_decode($json, true);

        return $data['data']['value'];
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: LoiLT2
 * Date: 8/26/2019
 * Time: 2:00 PM
 */

namespace trongloikt192\Utils;

class HttpUtil
{
    /**
     * @param $method
     * @param $url
     * @param $parameters
     * @return string
     */
    public static function request($method, $url, $parameters=[])
    {
        $httpHeader = ['X-Api-Key' => XApiAuth::make()];
        $crawler = GetLinkFunction::g_goutteRequest($method, $url, $parameters, null, ['headers' => $httpHeader]);
        return $crawler->getResponse()->getContent();
    }
}

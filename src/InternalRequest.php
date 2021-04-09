<?php
/**
 * Created by PhpStorm.
 * User: LoiLT2
 * Date: 8/26/2019
 * Time: 2:00 PM
 */

namespace trongloikt192\Utils;

class InternalRequest
{
    /**
     * @param $method
     * @param $path
     * @param array $parameter
     * @return string
     */
    public static function docsHTML($method, $path, $parameter = []): string
    {
        $url = env('DOCS_API_ADDRESS') . '/' . $path;
        if (!empty($parameter)) {
            $url .= '?' . http_build_query($parameter);
        }
        $option  = ['isRequestJson' => false,];

        $client = HttpUtil::g_goutteRequest($method, $url, $parameter, null, $option);
        return $client->getResponse()->getContent();
    }

    /**
     * Request to mailbox service for send email
     *
     * @param $code
     * @param $sendTo
     * @param $data
     * @return array
     */
    public static function mailbox($code, $sendTo, $data)
    {
        $requestBody = compact('code', 'sendTo', 'data');
        return self::request('POST', env('MAILBOX_API_ADDRESS'), $requestBody);
    }

    /**
     * @param $method
     * @param $url
     * @param $parameters
     * @return array [content, status code, headers]
     */
    public static function request($method, $url, $parameters=[])
    {
        $httpHeader = ['X-Api-Key' => XApiAuth::make()];
        $crawler    = HttpUtil::g_goutteRequest($method, $url, $parameters, null, ['headers' => $httpHeader]);
        $response   = $crawler->getResponse();

        return [
            $response->getContent(),
            $response->getStatusCode(),
            $response->getHeaders()
        ];
    }
}

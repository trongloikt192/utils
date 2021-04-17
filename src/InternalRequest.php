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
     * @return array
     */
    public static function docsHTML($method, $path, $parameter = []): array
    {
        $url = env('X_API_DOCS_URL') . '/' . ltrim($path, '/');
        return self::request($method, $url, $parameter);
    }

    /**
     * Request to master
     *
     * @param string $method
     * @param string $path
     * @param array $parameter
     * @return array
     */
    public static function master($method, $path, $parameter = [])
    {
        $url = env('X_API_MASTER_URL') . '/' . ltrim($path, '/');
        return self::request($method, $url, $parameter);
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
        return self::request('POST', env('X_API_MAILBOX_URL'), $requestBody);
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

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
    const PREFIX_INTERNAL_API_PATH = '/api/internal/';

    /**
     * @param string $method
     * @param string $path
     * @param array $parameter
     * @return array
     */
    public static function docs($method, $path, $parameter = []): array
    {
        $url = self::formatURL(env('X_API_DOCS_URL'), $path);
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
        $url = self::formatURL(env('X_API_MASTER_URL'), $path);
        return self::request($method, $url, $parameter);
    }

    /**
     * Request to backup servers
     *
     * @param string $method
     * @param string $serverAddress
     * @param string $path
     * @param array $parameter
     * @return array
     */
    public static function backup($method, $serverAddress, $path, $parameter = [])
    {
        $url = self::formatURL($serverAddress, $path);
        return self::request($method, $url, $parameter);
    }

    /**
     * Request to cache servers
     *
     * @param string $method
     * @param string $serverAddress
     * @param string $path
     * @param array $parameter
     * @return array
     */
    public static function cache($method, $serverAddress, $path, $parameter = [])
    {
        return self::backup($method, $serverAddress, $path, $parameter);
    }

    /**
     * Request to mailbox service for send email
     *
     * @param string $code
     * @param array $sendTo
     * @param array $data
     * @return array
     */
    public static function mailbox($code, $sendTo, $data)
    {
        $url = self::formatURL(env('X_API_MAILBOX_URL'), '/send');
        $requestBody = compact('code', 'sendTo', 'data');
        return self::request('POST', $url, $requestBody);
    }

    /**
     * Request to mailbox service for send email
     *
     * @param string $serverAddress
     * @param string $path
     * @param array $parameter
     * @return array
     */
    public static function getlink($serverAddress, $path, $parameter)
    {
        $serverAddress = GetLinkFunction::removeSchemeURL($serverAddress);
        $url = 'http://'.$serverAddress.':81'.self::PREFIX_INTERNAL_API_PATH.trim($path, '/');
        return self::request('POST', $url, $parameter);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $parameters
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

    /**
     * Remove unnecessary slash
     *
     * @param string $domain
     * @param string $path
     * @return string
     */
    private static function formatURL($domain, $path)
    {
        $domain = rtrim($domain, '/');
        $domain .= self::PREFIX_INTERNAL_API_PATH;
        $domain .= ltrim($path, '/');
        return $domain;
    }
}

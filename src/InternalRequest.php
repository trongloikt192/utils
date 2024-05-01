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
        $url = self::getDomainBackupFromServerName($serverAddress) . self::PREFIX_INTERNAL_API_PATH . trim($path, '/');
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
        $url = GetLinkFunction::getDomainGetLinkFromServerName($serverAddress) . self::PREFIX_INTERNAL_API_PATH . trim($path, '/');
        return self::request('POST', $url, $parameter);
    }

    /**
     * Request to crawler service
     *
     * @param $method
     * @param string $path
     * @param array $parameter
     * @return array
     */
    public static function crawler($method, $path, $parameter)
    {
        $url = self::formatURL(env('X_API_CRAWLER_URL'), $path);
        return self::request($method, $url, $parameter);
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

        if ($response->getStatusCode() != 200) {
            logger()->error(
                'InternalRequest Error' . PHP_EOL .
                'URL       : ' . $url . PHP_EOL .
                'Method    : ' . $method . PHP_EOL .
                'X-Api-Key : ' . $httpHeader['X-Api-Key'] . PHP_EOL .
                'Parameters: ' . json_encode($parameters) . PHP_EOL .
                'Response  : ' . $response->getContent() . PHP_EOL
            );
        }

        return [
            $response->getContent(),
            $response->getStatusCode(),
            $response->getHeaders()
        ];
    }

    /**
     * @param $path
     * @param $filePath
     * @param array $parameters
     * @return array
     * @throws Exceptions\UtilException
     */
    public static function uploadFileToDocs($path, $filePath, $parameters = []): array
    {
        $url = self::formatURL(env('X_API_DOCS_URL'), $path);
        return self::uploadFile($url, $filePath, $parameters);
    }

    /**
     * @param $path
     * @param $filePath
     * @param array $parameters
     * @return array
     * @throws Exceptions\UtilException
     */
    public static function uploadFileToMaster($path, $filePath, $parameters = []): array
    {
        $url = self::formatURL(env('X_API_MASTER_URL'), $path);
        return self::uploadFile($url, $filePath, $parameters);
    }

    /**
     * @param $serverAddress
     * @param $path
     * @param $filePath
     * @param array $parameters
     * @return array
     * @throws Exceptions\UtilException
     */
    public static function uploadFileToBackup($serverAddress, $path, $filePath, $parameters = []): array
    {
        $url = self::formatURL($serverAddress, $path);
        return self::uploadFile($url, $filePath, $parameters);
    }

    /**
     * @param $serverAddress
     * @param $path
     * @param $filePath
     * @param array $parameters
     * @return array
     * @throws Exceptions\UtilException
     */
    public static function uploadFileToGetlink($serverAddress, $path, $filePath, $parameters = [])
    {
        $url = GetLinkFunction::getDomainGetLinkFromServerName($serverAddress) . self::PREFIX_INTERNAL_API_PATH . trim($path, '/');
        return self::uploadFile($url, $filePath, $parameters);
    }

    /**
     * @param $url
     * @param $filePath
     * @param array $body
     * @return array [content, status code]
     * @throws Exceptions\UtilException
     */
    public static function uploadFile($url, $filePath, $body = [])
    {
        $options = [
            'header' => ['X-Api-Key' => XApiAuth::make()],
            'body'   => $body
        ];
        [$content, $statusCode] = HttpUtil::uploadFile($url, $filePath, $options);

        return [
            $content,
            $statusCode
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

    /**
     * Return backup address
     * http://backup01.vnlinks.net/<path>
     * @param $server
     * @param null $path request-getlink, request-getlink-directly
     * @return string
     */
    private static function getDomainBackupFromServerName($server, $path=null)
    {
        $result = 'http://' . GetLinkFunction::removeSchemeURL($server);
        if (strlen($path) > 0) {
            $result .= '/' . ltrim($path, '/');
        }
        return $result;
    }
}

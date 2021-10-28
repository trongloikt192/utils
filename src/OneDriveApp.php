<?php

namespace trongloikt192\Utils;

use Microsoft\Graph\Graph;
use Microsoft\Graph\Model\UploadSession;
use trongloikt192\Utils\Exceptions\UtilException;

class OneDriveApp
{
    private $_Graph;

    /**
     * OneDriveApp constructor.
     * @param array $config
     * @throws UtilException
     */
    public function __construct(array $config)
    {
        $guzzle       = new \GuzzleHttp\Client();
        $tenantId     = $config['tenantId'];
        $clientId     = $config['clientId'];
        $clientSecret = $config['clientSecret'];
        $username     = $config['username'];
        $password     = $config['password'];

        $url  = 'https://login.microsoftonline.com/' . $tenantId . '/oauth2/token';
        $resp = $guzzle->post($url, [
            'form_params' => [
                'client_id'     => $clientId,
                'client_secret' => $clientSecret,
                'resource'      => 'https://graph.microsoft.com/',
                'grant_type'    => 'password',
                'username'      => $username,
                'password'      => $password
            ],
        ]);

        if ($resp->getStatusCode() != 200) {
            throw new UtilException($resp->getBody()->getContents());
        }

        logger()->error('info', $config);
        logger()->error($resp->getBody()->getContents());

        $user_token = json_decode($resp->getBody()->getContents());

        $this->_Graph = new Graph();
        $this->_Graph->setAccessToken($user_token->access_token);
    }

    /**
     * @param $sourcePath
     * @param $destFolder
     * @param null $filename
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Microsoft\Graph\Exception\GraphException
     */
    public function uploadFile($sourcePath, $destFolder, $filename = null)
    {
        $filename = $filename ?? basename($sourcePath);

        $maxUploadSize = 1024 * 1024 * 4;
        if (filesize($sourcePath) < $maxUploadSize) {
            $this->_Graph->createRequest('PUT', '/me/drive/root:/' . trim($destFolder, '/') . '/' . $filename . ':/content')->upload($sourcePath);
        } else {
            $uploadSession = $this->_Graph->createRequest('POST', '/me/drive/root:/' . trim($destFolder, '/') . '/' . $filename . ':/createUploadSession')
                ->addHeaders(['Content-Type' => 'application/json'])
                ->attachBody([
                    'item' => [
                        '@microsoft.graph.conflictBehavior' => 'replace'
                    ]
                ])
                ->setReturnType(UploadSession::class)
                ->execute();

            logger()->error('upload url ' . $uploadSession->getUploadUrl());

            $handle        = fopen($sourcePath, 'rb');
            $fileSize      = fileSize($sourcePath);
            $chunkSize     = 1024 * 1024 * 64;
            $prevBytesRead = 0;
            while (!feof($handle)) {
                $bytes     = fread($handle, $chunkSize);
                $bytesRead = ftell($handle);

                $this->_Graph->createRequest('PUT', $uploadSession->getUploadUrl())
                    ->addHeaders([
                        'Connection'     => 'keep-alive',
                        'Content-Length' => ($bytesRead - $prevBytesRead),
                        'Content-Range'  => 'bytes ' . $prevBytesRead . '-' . ($bytesRead - 1) . '/' . $fileSize,
                    ])
                    ->setReturnType(UploadSession::class)
                    ->attachBody($bytes)
                    ->execute();

                $prevBytesRead = $bytesRead;
            }
        }
    }
}
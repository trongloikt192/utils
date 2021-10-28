<?php

namespace trongloikt192\Utils;


use trongloikt192\Utils\Entities\RcloneEntity;
use trongloikt192\Utils\Exceptions\UtilException;

class RcloneUtil
{
    /**
     * @var RcloneEntity
     */
    public $entity;

    /**
     * RcloneUtil constructor.
     * @param $st_storage
     */
    public function __construct($st_storage)
    {
        $this->entity = new RcloneEntity($st_storage);
        $this->syncConfigFile();
    }

    /**
     * @return array
     */
    public function about()
    {
        $rcloneName = $this->entity->rclone_name;
        $rcloneType = $this->entity->rclone_type;

        $cmd = '';
        switch ($rcloneType) {
            case RCLONE_TYPE_1FICHIER:
                // No support
                break;

            case RCLONE_TYPE_GDRIVE:
            case RCLONE_TYPE_ONEDRIVE:
                $cmd = sprintf('rclone about %s: --json', $rcloneName);
                break;
        }

        if (strlen($cmd) > 0) {
            $out  = shell_exec($cmd);
            $json = json_decode($out, true);
        }

        return [
            'storage_total' => $json['total'] ?? null,
            'storage_usage' => $json['used'] ?? null
        ];
    }

    /**
     * @return void
     */
    public function syncConfigFile()
    {
        $arr        = [];
        $rcloneName = $this->entity->rclone_name;
        $rcloneType = $this->entity->rclone_type;

        switch ($rcloneType) {
            case RCLONE_TYPE_1FICHIER:
                $arr[] = 'type = ' . RCLONE_TYPE_1FICHIER;
                $arr[] = 'api_key = ' . $this->entity->token;
                break;

            case RCLONE_TYPE_GDRIVE:
                $arr[] = 'type =' . RCLONE_TYPE_GDRIVE;
                $arr[] = 'token = ' . $this->entity->token;
                $arr[] = 'client_id = ' . $this->entity->client_id;
                $arr[] = 'client_secret = ' . $this->entity->client_secret;

                $arr[] = 'scope = ' . $this->entity->gdrive_scope;
                $arr[] = 'team_drive = ' . $this->entity->gdrive_team;
                $arr[] = 'root_folder_id = ';
                break;

            case RCLONE_TYPE_ONEDRIVE:
                $arr[] = 'type = ' . RCLONE_TYPE_ONEDRIVE;
                $arr[] = 'token = ' . $this->entity->token;
                $arr[] = 'client_id = ' . $this->entity->client_id;
                $arr[] = 'client_secret = ' . $this->entity->client_secret;

                $arr[] = 'drive_id = ' . $this->entity->onedrive_id;
                $arr[] = 'drive_type = ' . $this->entity->onedrive_type;
                break;
        }

        // Get config path
        $rcloneFile = exec('rclone config file');
        // Delete
        exec(sprintf('rclone config delete %s', $rcloneName));
        // Create
        $h = fopen($rcloneFile, 'a');
        fwrite($h, "[{$rcloneName}]\n");
        foreach ($arr as $item) {
            fwrite($h, $item . "\n");
        }
        fclose($h);
    }

    /**
     * @return false|string
     */
    public function delete()
    {
        $cmd = sprintf('rclone config delete %s', $this->entity->rclone_name);
        return exec($cmd);
    }

    /**
     * @param $path
     * @return false|string
     */
    public function mkdir($path)
    {
        $cmd = sprintf('rclone mkdir %s:%s', $this->entity->rclone_name, $path);
        return exec($cmd);
    }

    /**
     * @param $path
     * @return false|string
     * @throws UtilException
     */
    public function rmdir($path)
    {
        $cmd = sprintf('rclone purge %s:%s', $this->entity->rclone_name, $path);
        $out = shell_exec($cmd);
        if (strlen(trim($out)) > 0) {
            throw new UtilException($out);
        }
        return $out;
    }

    /**
     * @param $sourcePath
     * @param null $toFolderPath
     * @return false|string
     * @throws UtilException
     */
    public function uploadFile($sourcePath, $toFolderPath=null)
    {
        $cmd = sprintf('rclone copy %s %s:%s --ignore-existing --ignore-checksum --transfers 4 --checkers 8 --onedrive-chunk-size 160M --drive-chunk-size 128M --log-level INFO --log-file /var/tmp/rclone.log 2>&1', $sourcePath, $this->entity->rclone_name, $toFolderPath);
        $out = shell_exec($cmd);
        if (strlen(trim($out)) > 0) {
            throw new UtilException($out);
        }
        return $out;
    }

    /**
     * @param $path
     * @return false|string
     * @throws UtilException
     */
    public function deleteFile($path)
    {
        $cmd = sprintf('rclone deletefile %s:%s', $this->entity->rclone_name, $path);
        $out = shell_exec($cmd);
        if (strlen(trim($out)) > 0) {
            throw new UtilException($out);
        }
        return $out;
    }

    /**
     * @param $storagePath
     * @return false|string
     */
    public function getFileId($storagePath)
    {
        $cmd  = sprintf('rclone lsjson %s:%s', $this->entity->rclone_name, $storagePath);
        $out  = shell_exec($cmd);
        $json = json_decode($out, true);
        return $json[0]['ID'];
    }

    /**
     * @param $storagePath
     * @return false|string
     */
    public function getDownloadLink($storagePath)
    {
        $cmd  = sprintf('rclone link %s:%s', $this->entity->rclone_name, $storagePath);
        return exec($cmd);
    }

    /**
     * get token gdrive only
     *
     * @return array
     */
    public function getTokenGdrive($refreshBefore = false)
    {
        // refresh new token
        if ($refreshBefore == true) {
            $this->about();
        }

        // get token from rclone file
        $cmd   = 'rclone config dump';
        $out   = shell_exec($cmd);
        $json  = json_decode($out, true);
        $token = $json[$this->entity->rclone_name]['token'];

        // set token to entity
        $this->entity->token = $token;

        return json_decode($token, true);
    }

    /**
     * @return array
     */
    public function exportEntityArray()
    {
        return (array)$this->entity;
    }

    /**
     * @param $rcloneType
     * @return string
     */
    public static function convertTypeToHostname($rcloneType)
    {
        switch ($rcloneType) {
            case RCLONE_TYPE_1FICHIER:
                return HOST_1FICHIER_COM;
            case RCLONE_TYPE_ONEDRIVE:
                return HOST_ONEDRIVE_COM;
            case RCLONE_TYPE_GDRIVE:
                return HOST_GOOGLE_DRIVE_COM;
            case RCLONE_TYPE_NITROFLARE:
                return HOST_NITROFLARE_COM;
            case RCLONE_TYPE_RAPIDGATOR:
                return HOST_RAPIDGATOR_NET;
        }

        return null;
    }

    /**
     * @param $hostname
     * @return string
     */
    public static function convertHostnameToType($hostname)
    {
        switch ($hostname) {
            case HOST_1FICHIER_COM:
                return RCLONE_TYPE_1FICHIER;
            case HOST_ONEDRIVE_COM:
                return RCLONE_TYPE_ONEDRIVE;
            case HOST_GOOGLE_DRIVE_COM:
                return RCLONE_TYPE_GDRIVE;
            case HOST_NITROFLARE_COM:
                return RCLONE_TYPE_NITROFLARE;
            case HOST_RAPIDGATOR_NET:
                return RCLONE_TYPE_RAPIDGATOR;
        }

        return null;
    }
}

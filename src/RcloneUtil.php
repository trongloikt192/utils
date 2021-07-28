<?php

namespace trongloikt192\Utils;


use trongloikt192\Utils\Entities\RcloneEntity;

class RcloneUtil
{
    /**
     * @var RcloneEntity
     */
    protected $entity;

    public function __construct(RcloneEntity $entity)
    {
        $this->entity = $entity;
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
    public function updateOrCreate()
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
     * @return void
     */
    public function delete()
    {
        $cmd = sprintf('rclone config delete %s', $this->entity->rclone_name);
        exec($cmd);
    }

    /**
     * @param $path
     */
    public function mkdir($path)
    {
        $cmd = sprintf('rclone mkdir %s:%s', $this->entity->rclone_name, $path);
        exec($cmd);
    }

    /**
     * @param $path
     */
    public function rmdir($path)
    {
        $cmd = sprintf('rclone purge %s:%s', $this->entity->rclone_name, $path);
        exec($cmd);
    }

    /**
     * @param $sourcePath
     * @param null $toFolderPath
     */
    public function uploadFile($sourcePath, $toFolderPath=null)
    {
        $cmd = sprintf('rclone copy %s %s:%s', $sourcePath, $this->entity->rclone_name, $toFolderPath);
        exec($cmd);
    }

    /**
     * @param $path
     */
    public function deleteFile($path)
    {
        $cmd = sprintf('rclone deletefile %s:%s', $this->entity->rclone_name, $path);
        exec($cmd);
    }

    /**
     * @param $rcloneType
     * @return string
     */
    public static function detectHostnameByType($rcloneType)
    {
        switch ($rcloneType) {
            case RCLONE_TYPE_1FICHIER:
                return HOST_1FICHIER_COM;
            case RCLONE_TYPE_ONEDRIVE:
                return HOST_ONEDRIVE_COM;
            case RCLONE_TYPE_GDRIVE:
                return HOST_GOOGLE_DRIVE_COM;
        }

        return null;
    }
}

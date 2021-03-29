<?php


namespace trongloikt192\Utils;


use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use trongloikt192\Utils\Exceptions\UtilException;

class S3Util
{
    /**
     * @param $_FileInputPath
     * @param $_DestinationFolder
     * @return array
     * @throws UtilException
     */
    public static function upload($_FileInputPath, $_DestinationFolder)
    {
        $S3Path = null;

        try {
            // Substring "D:/www/xamp/htdocs/vnlinks.net/uploads/post/1" => "/uploads/post/1"
            $posStartCut = strpos($_DestinationFolder, 'uploads/');
            $storeFolder = substr($_DestinationFolder, $posStartCut);
            $storeFolder = rtrim($storeFolder, '/');

            $file       = new \Illuminate\Http\File($_FileInputPath);
            $S3Path     = Storage::cloud()->putFile($storeFolder, $file);
            $S3FileName = basename($S3Path);
            $S3FileURL  = Storage::cloud()->url($S3Path);

            // Use s3 file name to sync with local
            rename($_FileInputPath, $_DestinationFolder . $S3FileName);

        } catch (\Exception $exception) {
            Log::error($exception);
            throw new UtilException($exception->getMessage());
        }

        return [$S3FileURL, $S3Path, $S3FileName];
    }
}
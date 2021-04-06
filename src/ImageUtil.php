<?php


namespace trongloikt192\Utils;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use trongloikt192\Utils\Exceptions\UtilException;

class ImageUtil
{
    /**
     * Create thumbnail image
     *
     * @param $originPath
     * @param $destinationPath
     * @param $resizeWidth
     * @return string
     */
    public static function makeThumb($originPath, $destinationPath, $resizeWidth)
    {
        $img = Image::make($originPath);
        if ($img->getWidth() > $resizeWidth) {
            $img->resize($resizeWidth, null, static function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath, 100);
        } else {
            File::copy($originPath, $destinationPath);
        }

        return $destinationPath;
    }

    /**
     * Kéo hình ảnh trên mạng ($imgURLList) về $storagePath (cloud)
     * ex:
     * $imgURLList = ['http://img_1', 'http://img_2',]
     * $storagePath = 'post/102'
     * => return [ ['origin' => 'http://img_1', 'newImgPath' => 'post/102/s8dj', 'newImgURL' => 'http://xxx/post/102/s8dj'], ]
     *
     *
     * @param array $imgURLList
     * @param string $storagePath
     * @param bool $clearBeforeUp : Xoá tất cả trước khi upload image
     * @return array|void
     * @throws UtilException
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public static function grabImagesToFolder($imgURLList, $storagePath, $clearBeforeUp=false)
    {
        $replaceList   = [];
        $keepFileList  = [];

        $tempFolder    = Helper::getUploadsPath('tmp');
        $imgFolder     = Helper::getUploadsPath($storagePath);
        $imgFolderSort = "uploads/{$storagePath}";
        Log::debug('imgURLList', $imgURLList);
        foreach ($imgURLList as $imgURL) {
            // 1. GET FILE FROM URL
            // get file name + extension name
            $parseURL      = parse_url($imgURL);
            $basename      = basename($parseURL['path']);
            $fileExtension = pathinfo($basename, PATHINFO_EXTENSION);
            $tmpFilename   = md5($basename . date('YmdHis')) . '.' . $fileExtension;

            // HÌNH ĐÃ NẰM ĐÚNG THƯ MỤC CỦA BÀI POST THÌ KO XỬ LÝ NỮA
            if (strpos($imgURL, $imgFolderSort) !== false) {
                $keepFileList[] = $imgFolderSort .'/'. $basename;
                continue;
            }

            // BỎ QUA HÌNH KHÔNG TỒN TẠI
            if (empty($imgURL) || !Helper::urlExists($imgURL)) {
                continue;
            }

            // File path
            $tempPath  = $tempFolder . $tmpFilename;
            $imagePath = $imgFolder . $tmpFilename;

            // 2. RESIZE + WATERMARK
            Helper::grabImage($imgURL, $tempPath);
            self::resizeAndWatermark($tempPath, $imagePath, POST_COVER_MAX_WIDTH);

            // SYNC TO S3
            list($s3FileURL, $s3FilePath, $s3Filename) = S3Util::upload($imagePath, $imgFolder);

            $replaceList[]  = ['origin' => $imgURL, 'newPath' => $s3FilePath, 'newURL' => $s3FileURL];
            $keepFileList[] = $s3FilePath;

            // clean temp image
            File::delete($tempPath);
        }

        Log::debug('keepFileList', $keepFileList);

        // XÓA NHỮNG IMAGES KO SỬ DỤNG
        File::cleanDirectory($imgFolder);
        /*$scanFiles = scandir($imgFolder);
        foreach ($scanFiles as $filename) {
            $filePath = $imgFolder . '/' . $filename;
            if (!in_array($filename, $keepFileList, true)
                && File::exists($filePath)
                && File::isFile($filePath)) {
                File::delete($filePath);
                $deleteFileList[] = $imgFolderSort . '/' . $filename;
            }
        }*/

        // XÓA NHỮNG IMAGES TRONG FOLDER NHƯNG KO SỬ DỤNG TRÊN CLOUD
        $cloudFiles = Storage::cloud()->files($imgFolderSort);
        $deleteFileList = [];
        foreach ($cloudFiles as $filePath) {
            if (!in_array($filePath, $keepFileList, true)) {
                $deleteFileList[] = $filePath;
            }
        }

        Log::debug('deleteFileList', $deleteFileList);

        // remove on S3
        if (!empty($deleteFileList)) {
            Storage::cloud()->delete($deleteFileList);
        }

        $newImgURLList = Storage::cloud()->files($imgFolderSort);

        Log::debug('newImgURLList', $newImgURLList);

        return [$replaceList, $newImgURLList];
    }

    /**
     * Resize image => apply water mark => compressing by Tinify
     *
     * @param $originPath
     * @param $destinationPath
     * @param $resizeWidth
     * @return mixed
     * @throws \Exception
     */
    public static function resizeAndWatermark($originPath, $destinationPath, $resizeWidth)
    {
        $minWidth = 200;

        $img      = Image::make($originPath);
        $imgWidth = $img->getWidth();

        // Resize
        if ($imgWidth > $resizeWidth) {
            $img->resize($resizeWidth, null, static function ($constraint) {
                $constraint->aspectRatio();
            })->save($destinationPath, 100);
        } else {
            File::copy($originPath, $destinationPath);
        }

        // Watermark & Compressing images
        if ($imgWidth > $minWidth) {
            $watermark = public_path('img/watermark.png');
            if (!file_exists($watermark)) {
                throw new UtilException('watermark.png not exist');
            }
            $img = Image::make($destinationPath);
            $img->insert($watermark, 'bottom-right', 0, 0);
            $img->save($destinationPath, 100);

            /* TODO: Tinify
            try {
                \Tinify\setKey(env('TINIFY_API_KEY'));
                \Tinify\validate();
                $source = \Tinify\fromFile($destinationPath);
                $source->toFile($destinationPath);
            } catch (\Tinify\Exception $e) {
                throw new \Exception('Tinify error');
            }*/
        }

        return $destinationPath;
    }
}

<?php


namespace trongloikt192\Utils;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;
use trongloikt192\Utils\Exceptions\UtilException;

class ImageUtil
{
    /**
     * @param $url
     * @param $saveTo
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public static function grabImage($url, $saveTo)
    {
        $resource = fopen($saveTo, 'wb');
        $client   = new Client();
        $client->request('GET', $url, ['sink' => $resource]);
    }

    /**
     * Resize image => apply water mark => compressing by Tinify
     *
     * @param $originPath
     * @param $destPath
     * @param $resizeWidth
     * @return mixed
     * @throws \Exception
     */
    public static function resize($originPath, $destPath, $resizeWidth)
    {
        $img      = Image::make($originPath);
        $imgWidth = $img->getWidth();

        // Resize
        if ($imgWidth > $resizeWidth) {
            $img->resize($resizeWidth, null, static function ($constraint) {
                $constraint->aspectRatio();
            })->save($destPath, 100);
        } else {
            File::copy($originPath, $destPath);
        }

        return $destPath;
    }

    /**
     * Resize and set watermark
     *
     * @throws UtilException
     * @throws \Exception
     */
    public static function resizeAndWatermark($originPath, $destPath, $resizeWidth)
    {
        self::resize($originPath, $destPath, $resizeWidth);
        self::setWatermark($destPath, $destPath);
        return $destPath;
    }

    /**
     * Set watermark to image
     *
     * @param $sourcePath
     * @param $destPath
     * @return mixed
     * @throws UtilException
     */
    public static function setWatermark($sourcePath, $destPath)
    {
        $img   = Image::make($sourcePath);
        $width = $img->getWidth();

        if ($width <= WATERMARK_SM_WIDTH) {
            $watermark = 'img/watermark_sm.png';
        } elseif ($width <= WATERMARK_MD_WIDTH) {
            $watermark = 'img/watermark_md.png';
        } else {
            throw new UtilException('No watermark for this size (' . $width . 'px)');
        }

        // Set watermark
        $wf = public_path($watermark);
        if (!file_exists($wf)) {
            throw new UtilException($watermark . ' not exist');
        }

        $img = Image::make($destPath);
        $img->insert($wf, 'bottom-right', 0, 0);
        $img->save($destPath, 100);

        return $destPath;
    }

    /**
     * Compressing image
     *
     * @param $imgPath
     * @param $destPath
     * @throws \Exception
     */
    public static function compressing($imgPath, $destPath)
    {
        try {
            \Tinify\setKey(env('TINIFY_API_KEY'));
            \Tinify\validate();
            $source = \Tinify\fromFile($imgPath);
            $source->toFile($destPath);
        } catch (\Tinify\Exception $e) {
            throw new \Exception('Tinify error');
        }
    }

    /**
     * @param string $url
     * @param string $saveTo
     * @param int $retry
     * @return bool
     */
    public static function grabImageWithRetry($url, $saveTo, $retry = 0)
    {
        if ($retry > 5) {
            return false;
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla 5.0');
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_REFERER, 'https://www.google.com/');
        curl_setopt($ch, CURLOPT_COOKIEFILE, './cookie.txt');
        curl_setopt($ch, CURLOPT_COOKIEJAR, './cookie.txt');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        // handling the follow redirect
        if (preg_match('|Location: (https?://\S+)|', $result, $m)) {
            return self::grabImageWithRetry($m[1], $saveTo, $retry + 1);
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw = curl_exec($ch);
        curl_close($ch);
        if (file_exists($saveTo)) {
            unlink($saveTo);
        }
        $fp = fopen($saveTo, 'xb');
        fwrite($fp, $raw);
        fclose($fp);
        return true;
    }
}

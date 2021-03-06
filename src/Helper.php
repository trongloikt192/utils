<?php

namespace trongloikt192\Utils;

use RuntimeException;
use ZipArchive;

class Helper
{
    /**
     * @param string $folder
     * @param boolean $relativePath
     * @return string
     */
    public static function getUploadsPath($folder = null, $relativePath = false)
    {
        $publicPath = public_path();
        $uploadDir  = $publicPath .'/uploads';
        if (strlen($folder) > 0) {
            $uploadDir .= '/'.trim($folder, '/');
        }
        // Create folder if not exists
        self::createDir($uploadDir);

        return $relativePath == true
            ? str_replace($publicPath.'/', null, $uploadDir)
            : $uploadDir;
    }

    /**
     * @param string $folder
     * @param boolean $relativePath
     * @return string
     */
    public static function getDownloadsPath($folder = null, $relativePath = false)
    {
        $publicPath  = public_path();
        $downloadDir = $publicPath .'/downloads';
        if (strlen($folder) > 0) {
            $downloadDir .= '/'.trim($folder, '/');
        }
        // Create folder if not exists
        self::createDir($downloadDir);

        return $relativePath == true
            ? str_replace($publicPath.'/', null, $downloadDir)
            : $downloadDir;
    }

    /**
     * @param $folder
     * @return mixed
     */
    public static function createDir($folder)
    {
        if (file_exists($folder)) {
            return $folder;
        }

        if (!mkdir($folder, 0775, true) && !is_dir($folder)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $folder));
        }
        chmod($folder, 0775);
        return $folder;
    }

    /**
     * @param string $folder
     * @param string $fileName
     * @return string
     */
    public static function getUploadURL($folder = '', $fileName = '')
    {
        $fileName = rawurlencode($fileName);
        return url("uploads/{$folder}/{$fileName}");
    }

    /**
     * @param string $folder
     * @param string $fileName
     * @return string
     */
    public static function getDownloadURL($folder = '', $fileName = '')
    {
        $fileName = rawurlencode($fileName);
        return url("downloads/{$folder}/{$fileName}");
    }


    /* GENERATE SLUG ===============================================
     * Date: 2015/11/19
     * By: Le Trong Loi
     */
    public static function genarate_slug($str)
    {
        $slug = self::replace_TiengViet($str);
        $slug = self::convert_utf8($slug, MB_CASE_LOWER);
//	    $slug = preg_replace( '([^a-zA-Z0-9_.-])', '-', $slug );
        $slug = preg_replace('~[^\pL\d]+~u', '-', $slug);
        // trim
        $slug = trim($slug, '-');
        // Remove duplicate "-"
        $slug = preg_replace('~-+~', '-', $slug);
        $slug = strtolower($slug);
        return $slug;
    }

    /**
     * @param $str
     * @return mixed
     */
    private static function replace_TiengViet($str)
    {
        $coDau = array('??', '??', '???', '???', '??', '??', '???', '???', '???', '???', '???', '??', '???', '???', '???', '???', '???', '??', '??', '???', '???', '???', '??', '???', '???', '???', '???', '???', '??', '??', '???', '???', '??', '??', '??', '???', '???', '??', '??', '???', '???', '???', '???', '???', '??', '???', '???', '???', '???', '???', '??', '??', '???', '???', '??', '??', '???', '???', '???', '???', '???', '???', '??', '???', '???', '???', '??', '??', '??', '???', '???', '??', '??', '???', '???', '???', '???', '???', '??', '???', '???', '???', '???', '???', '??', '??', '???', '???', '???', '??', '???', '???', '???', '???', '???', '??', '??', '???', '???', '??', '??', '??', '???', '???', '??', '??', '???', '???', '???', '???', '???', '??', '???', '???', '???', '???', '???', '??', '??', '???', '???', '??', '??', '???', '???', '???', '???', '???', '???', '??', '???', '???', '???', '??', '??', '??', '??');

        $khongDau = array('a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y', 'd', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'Y', 'Y', 'Y', 'Y', 'Y', 'D', 'e', 'u', 'a');

        return str_replace($coDau, $khongDau, $str);
    }


    /**
     * @param $str
     * @param int $option
     * @return bool|false|mixed|string|string[]|null
     */
    private static function convert_utf8($str, $option = MB_CASE_TITLE)
    {
        switch ($option) {
            case 'upper':
                $option = MB_CASE_UPPER;
                break;
            case 'lower':
                $option = MB_CASE_LOWER;
                break;
            case 'title':
                $option = MB_CASE_TITLE;
                break;
        }
        return mb_convert_case($str, $option, 'UTF-8');

    }
    // --END GENERATE SLUG


    /* creates a compressed zip file */
    /*
        $files_to_zip = array(
        'preload-images/1.jpg',
        'preload-images/2.jpg',
        'preload-images/5.jpg',
        'kwicks/ringo.gif',
        'rod.jpg',
        'reddit.gif'
    );
    //if true, good; if false, zip creation failed
    $result = create_zip($files_to_zip, '/var/tmp/my-archive.zip', 'toilatoi');
    */
    /**
     * @param array $files
     * @param string $destination
     * @param string $inFolder
     * @param bool $overwrite
     * @return bool
     */
    public static function createZip($files = array(), $destination = '', $inFolder = '', $overwrite = false)
    {
        //if the zip file already exists and overwrite is false, return false
        if (file_exists($destination) && !$overwrite) {
            return false;
        }
        //vars
        $valid_files = array();
        //if files were passed in...
        if (is_array($files)) {
            //cycle through each file
            foreach ($files as $file) {
                //make sure the file exists
                if (file_exists($file)) {
                    $valid_files[] = $file;
                }
            }
        }
        //if we have good files...
        if (count($valid_files)) {
            //create the archive
            $zip  = new ZipArchive();
            $mode = ZIPARCHIVE::CREATE;
            if (file_exists($destination) && $overwrite) {
                $mode = ZIPARCHIVE::OVERWRITE;
            }
            if ($zip->open($destination, $mode) !== true) {
                return false;
            }

            //add the files
            foreach ($valid_files as $file) {
                $exFile   = explode('/', $file);
                $fileName = !empty($inFolder) ? ($inFolder . '/' . $exFile[count($exFile) - 1]) : $exFile[count($exFile) - 1];
                $zip->addFile($file, $fileName);
            }
            //debug
            //echo 'The zip archive contains ',$zip->numFiles,' files with a status of ',$zip->status;

            //close the zip -- done!
            $zip->close();

            //check to make sure the file exists
            return file_exists($destination);
        }

        return false;
    }

    /**
     * @param $bytes
     * @param int $precision
     * @return string
     */
    public static function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $bytes = max($bytes, 0);
        $pow   = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow   = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Tr??? v??? size MB
     *
     * @param string $fileSizeStr
     * @return float|int
     */
    public static function getSizeMB($fileSizeStr)
    {
        // Qui t???t c??? out->fileSize v??? d???ng s??? (????n v??? MB)
        $sizeType = substr($fileSizeStr, -2);
        $size     = trim(str_replace($sizeType, '', $fileSizeStr));
        $size     = str_replace(' ', '', $size);
        $size     = (float)$size;
        switch (strtoupper($sizeType)) {
            case 'GB':
                $fileSizeMB = $size * 1024;
                break;

            case 'MB':
                $fileSizeMB = $size;
                break;

            case 'KB':

                $fileSizeMB = 1;
                break;

            default:
                $fileSizeMB = 0;
                break;
        }

        return $fileSizeMB;
    }

    /**
     * @param $url
     * @return bool
     */
    public static function urlExists($url)
    {
        $file_headers = @get_headers($url);
        return !(!$file_headers || strpos($file_headers[0], '404 Not Found'));
    }

    /**
     * Nh???ng $url trong content c?? d???ng /images/picture_01.jpg s??? d???a v??o $webpage ????? th??m shceme v?? host v??o ph??a tr?????c
     * => full url
     * @param $url
     * @param $webpage
     * @return string|null
     */
    public static function safeUrl($url, $webpage)
    {
        if (empty($url)) {
            return null;
        }

        // Gh??p th??m $webpage v??o nh???ng image l???y t??? $webpage kh??c, ch??? c?? relative path, ko c?? full url
        if (strlen($webpage) > 0 && strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
            $url   = ltrim($url, '/');
            $parse = parse_url($webpage);
            if (!isset($parse['scheme']) || !isset($parse['host'])) {
                return $url;
            }

            $url = $parse['scheme'] . '://' . $parse['host'] . '/' . $url;
        }

        return $url;
    }

    /**
     * @return bool
     */
    public static function isChrome()
    {
        $agent = $_SERVER['HTTP_USER_AGENT'];
        if (preg_match("/like\sGecko\)\sChrome\//", $agent)) {    // if user agent is google chrome
            if (!strstr($agent, 'Iron')) // but not Iron
                return true;
        }
        return false; // if isn't chrome return false
    }

    /**
     * encode URLs
     * input:   http://www.example.com/Data/image/office-d??n-s??-??.jpg
     * returns: http://www.example.com/Data/image/office-d%25C3%25B4n-s%25C3%25AC-%25C3%25A0.jpg
     * @param string $url
     * @return string
     */
    public static function urlEncodeBasename($url)
    {
        /*
        // encode URLs according to RFC 3986.
        $entities     = array('%21', '%2A', '%27', '%28', '%29', '%3B', '%3A', '%40', '%26', '%3D', '%2B', '%24', '%2C', '%2F', '%3F', '%25', '%23', '%5B', '%5D');
        $replacements = array('!', '*', "'", '(', ')', ';', ':', '@', '&', '=', '+', '$', ',', '/', '?', '%', '#', '[', ']');
        return str_replace($entities, $replacements, urlencode($string));
        */

        $url  = explode('/', $url);
        $base = array_pop($url);
        return implode('/', $url) . '/' . urlencode($base);
    }

    /**
     * Generate hex color from a string any
     * @param $str
     * @return false|string
     */
    public static function stringToColorCode($str) {
        $code = dechex(crc32($str));
        $code = substr($code, 0, 6);
        return $code ? '#'.$code : $code;
    }
}

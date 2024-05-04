<?php

namespace trongloikt192\Utils;

use Illuminate\Support\Facades\File;
use RuntimeException;
use ZipArchive;

class Helper
{
    const VIDEO_EXTENSIONS         = ['mp4', 'flv', 'webm', 'mkv', 'avi', '3gp', 'wav', 'ts', 'wmv', 'mov'];
    const VIDEO_BROWSER_EXTENSIONS = ['mp4', 'flv', 'webm'];

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
        $coDau = array('à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ', 'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ', 'ì', 'í', 'ị', 'ỉ', 'ĩ', 'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ', 'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ', 'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ', 'À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ', 'È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ', 'Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ', 'Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ', 'Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ', 'Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ', 'Đ', 'ê', 'ù', 'à');

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
     * Trả về size MB
     *
     * @param string $fileSizeStr
     * @return float|int
     */
    public static function getSizeMB($fileSizeStr)
    {
        // Qui tất cả out->fileSize về dạng số (đơn vị MB)
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
     * Những $url trong content có dạng /images/picture_01.jpg sẽ dựa vào $webpage để thêm shceme và host vào phía trước
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

        // Ghép thêm $webpage vào những image lấy từ $webpage khác, chỉ có relative path, ko có full url
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
     * input:   http://www.example.com/Data/image/office-dôn-sì-à.jpg
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

    /**
     * Check file's extension is video
     * @param $filename
     * @return bool
     */
    public static function fileIsVideo($filename)
    {
        $extension = strtolower(File::extension($filename));

        if (in_array($extension, self::VIDEO_EXTENSIONS, true)) {
            return true;
        }

        return false;
    }

    /**
     * Check file's extension is video and browser support for play
     * @param $filename
     * @return bool
     */
    public static function fileIsVideoBrowser($filename)
    {
        $extension = strtolower(File::extension($filename));

        if (in_array($extension, self::VIDEO_BROWSER_EXTENSIONS, true)) {
            return true;
        }

        return false;
    }

    /**
     * Get post Id from post slug
     * @param $slug
     * @return mixed|null
     */
    public static function getPostIdFromSlug($slug)
    {
        if (preg_match('/\-(\d+)\.download/', $slug, $matches) !== false) {
            return $matches[1] ?? null;
        }
        return null;
    }

    /**
     * @param $filename
     * @return string
     */
    public static function getMimeType($filename) {
        $idx = explode( '.', $filename);
        $count_explode = count($idx);
        $idx = strtolower($idx[$count_explode-1]);

        $mimet = array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv',

            // images
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml',

            // archives
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed',

            // audio/video
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',

            // adobe
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript',

            // ms office
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint',
            'docx' => 'application/msword',
            'xlsx' => 'application/vnd.ms-excel',
            'pptx' => 'application/vnd.ms-powerpoint',


            // open office
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        );

        return $mimet[$idx] ?? 'application/octet-stream';
    }
}

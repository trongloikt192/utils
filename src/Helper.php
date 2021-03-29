<?php

namespace trongloikt192\Utils;

use finfo;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\File;
use ZipArchive;

class Helper {

	// Set Active state for current Navigation links
	// Used in views>layouts>navigation.blade.php
	public static function active($path, $active = 'active')
	{
	    return Request::is($path) ? $active : null;
	}

	// Generate back button
	// Used in post.edit, post.create, deleted_users, settings_edit, profile_edit, feedback
	public static function cancel_button($text = 'Cancel')
	{
	    return "<a href=' " . URL::previous() . " ' class='btn btn-default pull-right'>$text</a>";
	}

	// Generate the Gravatar url for given email
	// Used in posts>show.blade.php and user>profile_public.blade.php
	public static function gravatar_url($email, $size = '150')
	{
	    if($size == null) {
	        return 'http://www.gravatar.com/avatar/'.md5($email) ;
	    }
	    return 'http://www.gravatar.com/avatar/'. md5($email) .'?s='. $size ;
	}

	// Generate the url for given Image
	// Used in user>profile.blade.php
	public static function image_url($type, $id = '', $filename = '')
	{
	    $url = '';

		switch ($type) {
			case 'post':
				if (File::isFile(self::getUploadsPath('pictures', "posts/{$id}/{$filename}"))) {
					$url = asset("/uploads/pictures/posts/{$id}/{$filename}");
				} else {
					$url = 'http://placehold.it/300x450/000/fff';
				}
				break;

			case 'setting':
				$url = asset('/uploads/setting/'. $filename);
				if (File::isFile(self::getUploadsPath('setting', $filename))) {
					$url = asset("/uploads/setting/{$filename}");
				} else {
					$url = 'http://placehold.it/300x450/000/fff';
				}
				break;

			default:
				# code...
				break;
		}

	    return $url;
	}

    /**
     * @param string $folder
     * @param string $fileName
     * @return string
     */
    public static function getUploadsPath($folder='', $fileName='')
	{
		$folder = public_path("uploads/{$folder}/");
		if (!file_exists($folder)) {
            if (!mkdir($folder, 0775, true) && !is_dir($folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
		    chmod($folder, 0775);
		}
        return $folder . $fileName;
	}

    /**
     * @param string $folder
     * @param string $fileName
     * @return string
     */
    public static function getDownloadsPath($folder='', $fileName='')
	{
		$folder = public_path("downloads/{$folder}/");
		if (!file_exists($folder)) {
            if (!mkdir($folder, 0775, true) && !is_dir($folder)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $folder));
            }
		    chmod($folder, 0775);
		}
        return $folder . $fileName;
	}

    /**
     * @param string $folder
     * @param string $fileName
     * @return string
     */
    public static function getUploadURL($folder='', $fileName='')
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

	public static function replace_TiengViet($str)
	{
		$coDau=array('à', 'á', 'ạ', 'ả', 'ã', 'â', 'ầ', 'ấ', 'ậ', 'ẩ', 'ẫ', 'ă', 'ằ', 'ắ', 'ặ', 'ẳ', 'ẵ', 'è', 'é', 'ẹ', 'ẻ', 'ẽ', 'ê', 'ề', 'ế', 'ệ', 'ể', 'ễ', 'ì', 'í', 'ị', 'ỉ', 'ĩ', 'ò', 'ó', 'ọ', 'ỏ', 'õ', 'ô', 'ồ', 'ố', 'ộ', 'ổ', 'ỗ', 'ơ', 'ờ', 'ớ', 'ợ', 'ở', 'ỡ', 'ù', 'ú', 'ụ', 'ủ', 'ũ', 'ư', 'ừ', 'ứ', 'ự', 'ử', 'ữ', 'ỳ', 'ý', 'ỵ', 'ỷ', 'ỹ', 'đ', 'À', 'Á', 'Ạ', 'Ả', 'Ã', 'Â', 'Ầ', 'Ấ', 'Ậ', 'Ẩ', 'Ẫ', 'Ă', 'Ằ', 'Ắ', 'Ặ', 'Ẳ', 'Ẵ', 'È', 'É', 'Ẹ', 'Ẻ', 'Ẽ', 'Ê', 'Ề', 'Ế', 'Ệ', 'Ể', 'Ễ', 'Ì', 'Í', 'Ị', 'Ỉ', 'Ĩ', 'Ò', 'Ó', 'Ọ', 'Ỏ', 'Õ', 'Ô', 'Ồ', 'Ố', 'Ộ', 'Ổ', 'Ỗ', 'Ơ', 'Ờ', 'Ớ', 'Ợ', 'Ở', 'Ỡ', 'Ù', 'Ú', 'Ụ', 'Ủ', 'Ũ', 'Ư', 'Ừ', 'Ứ', 'Ự', 'Ử', 'Ữ', 'Ỳ', 'Ý', 'Ỵ', 'Ỷ', 'Ỹ', 'Đ', 'ê', 'ù', 'à');

		$khongDau=array('a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'a', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'e', 'i', 'i', 'i', 'i', 'i', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'o', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'u', 'y', 'y', 'y', 'y', 'y', 'd', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'A', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'E', 'I', 'I', 'I', 'I', 'I', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'O', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'U', 'Y', 'Y', 'Y', 'Y', 'Y', 'D', 'e', 'u', 'a');

		return str_replace($coDau,$khongDau,$str);
	}


	public static function convert_utf8($str,$option=MB_CASE_TITLE)
	{
		switch($option)
		{
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


	/* GENERATE THUMBNAIL FROM IMAGE
	 * Date: 2015/11/25
	 * By: Le Trong Loi
	 * Note: *png 2880 x 1800 in true color will need about 20 Megabyte.
	 *			Check your php.ini for memory_limit.
	 */
	public static function createImageThumb( $pathToImages, $pathToThumbs, $thumbWidth = 265, $thumbHeight = 'auto' )
	{
		$mimeType = null;
		if(is_file($pathToImages)) {
			$result = new finfo();
		    if (is_resource($result) === true) {
		        $mimeType = $result->file($pathToImages, FILEINFO_MIME_TYPE);
		    }
		} else {
			return 'error';
		}

		$fname = pathinfo( $pathToImages, PATHINFO_BASENAME );
		// $extension = pathinfo($pathToImages, PATHINFO_EXTENSION);
		// $extension = strtolower($extension);

  		// load image and get image size
		if ($mimeType == 'image/jpeg') {
        	$source_image = @imagecreatefromjpeg( "{$pathToImages}" );
	    } else if ($mimeType == 'image/png') {
	        $source_image = @imagecreatefrompng( "{$pathToImages}" ); // I think the crash occurs here.
	    } else {
	        return 'error';
	    }

		$width = imagesx( $source_image );
		$height = imagesy( $source_image );

  		// calculate thumbnail size
		$new_width = $thumbWidth;
		$new_height = $thumbHeight;
		if($thumbHeight == 'auto') {
			$new_height = floor( $height * ( $thumbWidth / $width ) );
		}


  		// create a new temporary image
		$tmp_img = imagecreatetruecolor( $new_width, $new_height );

  		// copy and resize old image into new image
		imagecopyresized( $tmp_img, $source_image, 0, 0, 0, 0, $new_width, $new_height, $width, $height );

  		// save thumbnail into a file
		// imagejpeg( $tmp_img, "{$pathToThumbs}{$fname}" );
		// Create the physical thumbnail image to its destination
	    if ($mimeType == 'image/jpeg') {
	        @imagejpeg($tmp_img, "{$pathToThumbs}{$fname}");
	    } else if ($mimeType == 'image/png') {
	        @imagepng($tmp_img, "{$pathToThumbs}{$fname}", 1);
	    } else {
	        return 'another error';
	    }

		return $fname;
	}
	// --END GENERATE THUMBNAIL FROM IMAGE




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
	public static function createZip($files = array(), $destination = '', $inFolder = '', $overwrite = false) {
		//if the zip file already exists and overwrite is false, return false
		if(file_exists($destination) && !$overwrite) { return false; }
		//vars
		$valid_files = array();
		//if files were passed in...
		if(is_array($files)) {
			//cycle through each file
			foreach($files as $file) {
				//make sure the file exists
				if(file_exists($file)) {
					$valid_files[] = $file;
				}
			}
		}
		//if we have good files...
		if(count($valid_files)) {
			//create the archive
			$zip = new ZipArchive();
			$mode = ZIPARCHIVE::CREATE;
			if (file_exists($destination) && $overwrite) {
				$mode = ZIPARCHIVE::OVERWRITE;
			}
			if($zip->open($destination, $mode) !== true) {
				return false;
			}

			//add the files
			foreach($valid_files as $file) {
				$exFile = explode('/', $file);
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

    public static function str_limit_p_link($link)
    {
        if (empty($link)) {
            return null;
        }
        $out       = trim($link, '/');
        $parse     = parse_url($out);
        $path      = $parse['path'];
        $ex_path   = explode('/', $path);
        $last_path = last($ex_path);
        $out       = $parse['scheme'] . '://' . $parse['host'] . '/.../' . $last_path;
        return $out;
    }

    public static function grab_image($url, $saveto, $retry=0)
    {
        if($retry > 5) {
            return false;
        }

        $ch = curl_init();
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_USERAGENT, 'Mozilla 5.0');
        curl_setopt ($ch, CURLOPT_HEADER, TRUE);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt ($ch, CURLOPT_REFERER, 'http://www.google.com/');
        curl_setopt ($ch, CURLOPT_COOKIEFILE, './cookie.txt');
        curl_setopt ($ch, CURLOPT_COOKIEJAR, './cookie.txt');
        curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);

        // handling the follow redirect
        if(preg_match("|Location: (https?://\S+)|", $result, $m)){
            return self::grab_image($m[1], $saveto, $retry + 1);
        }

        $ch = curl_init ($url);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
        $raw = curl_exec($ch);
        curl_close ($ch);
        if(file_exists($saveto)){
            unlink($saveto);
        }
        $fp = fopen($saveto, 'xb');
        fwrite($fp, $raw);
        fclose($fp);
        return true;
    }

    /**
     * @param $url
     * @param $saveTo
     */
    public static function grabImage($url, $saveTo)
    {
        $resource = fopen($saveTo, 'wb');
        $client = new Client();
        $client->request('GET', $url, ['sink' => $resource]);
        return;
    }

	public static function formatBytes($bytes, $precision = 2) {
	    $units = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
	    $bytes = max($bytes, 0);
	    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
	    $pow = min($pow, count($units) - 1);
	    $bytes /= pow(1024, $pow);
	    return round($bytes, $precision) . ' ' . $units[$pow];
	}

	function is_chrome(){
		$agent=$_SERVER['HTTP_USER_AGENT'];
		if( preg_match("/like\sGecko\)\sChrome\//", $agent) ){	// if user agent is google chrome
			if(!strstr($agent, 'Iron')) // but not Iron
				return true;
		}
		return false;	// if isn't chrome return false
	}

	function _mime_content_type($filename) {
	    $result = new finfo();

	    if (is_resource($result) === true) {
	        return $result->file($filename, FILEINFO_MIME_TYPE);
	    }

	    return false;
	}

    public static function urlExists($url) {
        $file_headers = @get_headers($url);
        if(!$file_headers || strpos($file_headers[0], '404 Not Found')) {
            return false;
        }
        return true;
    }

    /**
     * Những $url trong content có dạng /images/picture_01.jpg sẽ dựa vào $webpage để thêm shceme và host vào phía trước
     * => full url
     * @param $url
     * @param $webpage
     * @return string|null
     */
    public static function safeUrl($url, $webpage) {
        if (empty($url)) {
            return null;
        }

        if (strpos($url, 'http://') === false && strpos($url, 'https://') === false) {
            $url = ltrim($url, '/');
            $parse = parse_url($webpage);
            $url = $parse['scheme'] .'://'. $parse['host'] .'/'. $url;
        }
        return $url;
    }

    /**
     * Chuyển đổi tag list dạng array thành string để lưu vào databaseß
     *
     * @param array $tagList
     * @return string
     */
    public static function convertTagListToString(array $tagList)
    {
        return implode(';', $tagList);
    }
}

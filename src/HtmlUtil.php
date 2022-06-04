<?php

namespace trongloikt192\Utils;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\URL;

class HtmlUtil
{
    /**
     * Set Active state for current Navigation links
     * Used in views>layouts>navigation.blade.php
     * @param $path
     * @param string $active
     * @return string|null
     */
    public static function active($path, $active = 'active')
    {
        return Request::is($path) ? $active : null;
    }

    /**
     * Generate back button
     * Used in post.edit, post.create, deleted_users, settings_edit, profile_edit, feedback
     * @param string $text
     * @return string
     */
    public static function cancel_button($text = 'Cancel')
    {
        return "<a href=' " . URL::previous() . " ' class='btn btn-default pull-right'>$text</a>";
    }

    /**
     * Generate the Gravatar url for given email
     * Used in posts>show.blade.php and user>profile_public.blade.php
     * @param $email
     * @param string $size
     * @return string
     */
    public static function gravatar_url($email, $size = '150')
    {
        if ($size == null) {
            return 'http://www.gravatar.com/avatar/' . md5($email);
        }
        return 'http://www.gravatar.com/avatar/' . md5($email) . '?s=' . $size;
    }

    /**
     * Generate the url for given Image
     * Used in user>profile.blade.php
     * @param $type
     * @param string $id
     * @param string $filename
     * @return string
     */
    public static function image_url($type, $id = '', $filename = '')
    {
        $url = '';

        switch ($type) {
            case 'post':
                $url = 'http://placehold.it/300x450/000/fff';
                if (File::isFile(Helper::getUploadsPath('pictures', "posts/{$id}/{$filename}"))) {
                    $url = asset("/uploads/pictures/posts/{$id}/{$filename}");
                }
                break;

            case 'setting':
                $url = 'http://placehold.it/300x450/000/fff';
                if (File::isFile(Helper::getUploadsPath('setting', $filename))) {
                    $url = asset("/uploads/setting/{$filename}");
                }
                break;

            default:
                # code...
                break;
        }

        return $url;
    }

    /**
     * Hiển thị rút gọn url
     *
     * Ex: https://nitro.download/view/7584C349B50DCC5/the.resident.s04e13.1080p.web.hevc.x265.rmteam.mkv
     * => https://nitro.download/.../the.resident.s04e13.1080p.web.hevc.x265.rmteam.mkv
     * @param $link
     * @return string|null
     */
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

    /**
     * Chuyển giá trị field tags từ chuỗi định dạnh json -> chuỗi thường
     * Ex: ["Juvenile", "Nonfiction"]
     * => 'Juvenile', 'Nonfiction'
     *
     * @param $jsonStr
     * @return string
     */
    public static function formatTags($jsonStr)
    {
        $arr = json_decode($jsonStr, true);
        return is_array($arr)
            ? implode(', ', $arr)
            : null;
    }

    /**
     * Chuyển số bytes thành định dạng lớn nhất
     * Ex:
     * 999999 -> 7.99 Mb
     * 999999999 -> 7.99 Gb
     *
     * @param $bytes
     * @return string
     */
    public static function formatSize($bytes)
    {
        return Helper::formatBytes($bytes);
    }

    /**
     * Format minute to hour
     * Ex:
     * 90 => 1h 30m
     * 120 => 2 hours
     *
     * @param $minutes
     * @return string|null
     */
    public static function formatDuration($minutes)
    {
        if (strlen($minutes) <= 0) {
            return null;
        }

        if ($minutes < 60) {
            return $minutes . ' minutes';
        }
        $hours = floor( $minutes / 60);
        $minutes = $minutes % 60;

        if ($minutes == 0) {
            return $hours . ' hours';
        }

        return $hours . 'h ' . $minutes . 'm';
    }

    /**
     * Get full đường dẫn từ static-service
     * Ex: path: uploads/post/1151/AVFfc0AqtoanbL8gxtFSyndHhL32xMNgLTtdDEou.jpg
     * => https://static.roleplayvn.com/file-storage/uploads/post/1151/AVFfc0AqtoanbL8gxtFSyndHhL32xMNgLTtdDEou.jpg
     *
     * @param $path
     * @return string
     */
    public static function dispStaticImg($path)
    {
        // Nếu chuỗi path không bắt đầu bằng "http" thì có thể là đường dẫn full (asset từ hệ thống khác)
        if (strpos($path, 'http') !== false) {
            return $path;
        }

        return env('MINIO_ENDPOINT') .'/'. env('MINIO_BUCKET') .'/'. sprintf($path, DISP_SM);
    }

    /**
     * Ráp full địa chỉ url cho những image trong post content
     *
     * @param $content
     * @return array|string|string[]|null
     */
    public static function replaceImgInContent($content)
    {
        $minio   = env('MINIO_ENDPOINT') . '/' . env('MINIO_BUCKET');
        $search  = '/(uploads\/post\/\d+\/img\/disp\/content\/\w+)%s(\.\w+)/';
        $replace = $minio . '/$1' . DISP_SM . '$2';

        return preg_replace($search, $replace, $content);
    }

    /**
     * Check file's extension is video
     *
     * @param $filename
     * @return bool
     */
    public static function fileIsVideo($filename)
    {
        return Helper::fileIsVideo($filename);
    }

    /**
     * Check file's extension is video and browser support for play
     *
     * @param $filename
     * @return bool
     */
    public static function fileIsVideoBrowser($filename)
    {
        return Helper::fileIsVideoBrowser($filename);
    }
}

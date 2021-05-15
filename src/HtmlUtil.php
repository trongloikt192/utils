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
        return implode(', ', $arr);
    }

}

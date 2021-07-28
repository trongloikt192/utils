<?php


namespace trongloikt192\Utils;


class PathUtil
{
    /**
     *
     * @param $postId
     * @return string
     */
    public static function postStorageDir($postId): string
    {
        return "post/{$postId}";
    }

    /**
     *
     * @param $postId
     * @return string
     */
    public static function postImgPath($postId): string
    {
        return "uploads/post/{$postId}/img";
    }

}

<?php


namespace trongloikt192\Utils;


class PathUtil
{
    /**
     *
     * @param $postId
     * @return string
     */
    public static function postFilePath($postId): string
    {
        return "post/{$postId}/files";
    }

    /**
     *
     * @param $postId
     * @return string
     */
    public static function postImgPath($postId): string
    {
        return "uploads/post/{$postId}/images";
    }

}

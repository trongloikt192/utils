<?php
/**
 * Created by PhpStorm.
 * User: LoiLT2
 * Date: 8/26/2019
 * Time: 2:00 PM
 */

namespace trongloikt192\Utils;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JwtAuth
{
    protected static $sub;
    protected static $user;
    protected static $userId;
    protected static $userRole;
    protected static $userEmail;

    /**
     * @param $accessKey
     */
    public static function parse($accessKey)
    {
        $credentials = JWT::decode($accessKey, new Key(config('jwt.secret'), config('jwt.algo')));
        self::$userId = $credentials->id;
        self::$userRole = $credentials->role_id;
        self::$userEmail = $credentials->email;
    }

    public static function getUser()
    {
        return self::$user;
    }

    public static function getUserId()
    {
        return self::$userId;
    }

    public static function getUserRole()
    {
        return self::$userRole;
    }

    public static function getUserEmail()
    {
        return self::$userEmail;
    }

    public static function setUserId($userId)
    {
        self::$userId = $userId;
    }
}

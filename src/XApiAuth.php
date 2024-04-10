<?php
/**
 * Created by PhpStorm.
 * User: LoiLT2
 * Date: 8/26/2019
 * Time: 2:00 PM
 */

namespace trongloikt192\Utils;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class XApiAuth
{
    /**
     * @param $XApiKey
     * @return bool
     */
    public static function parse($XApiKey)
    {
        // IF X-API-KEY IS USED => RETURN FALSE
        $XApiKeyCache = Cache::get(X_API_KEY_PREFIX . $XApiKey, null);
        if (isset($XApiKeyCache)) {
            return false;
        }

        // Decrypt XApiKey by HASH_ACCESS_KEY and check HASH_SECRET_KEY is valid
        $accessKey = config('xapi.access_key');
        $secretKey = config('xapi.secret_key');
        $encoded   = base64_decode($XApiKey);
        $decoded   = '';
        for ($i = 0, $iMax = strlen($encoded); $i < $iMax; $i++) {
            $b       = ord($encoded[$i]);
            $a       = $b ^ $accessKey;
            $decoded .= chr($a);
        }
        $msg = base64_decode($decoded); // secret:timestamp
        $arr = explode(':', $msg);
        if (isset($arr[0]) && $arr[0] == $secretKey) {
            Cache::put(X_API_KEY_PREFIX . $XApiKey, true, Carbon::tomorrow());
            return true;
        }

        return false;
    }

    /**
     * create a unique x-api-key
     *
     * @return string
     */
    public static function make()
    {
        $accessKey = config('xapi.access_key');
        $secretKey = config('xapi.secret_key');
        $msg       = $secretKey .':'. round(microtime(true) * 1000);
        $msgBase64 = base64_encode($msg);
        $encoded = '';
        for ($i = 0, $iMax = strlen($msgBase64); $i < $iMax; $i++) {
            $a       = ord($msgBase64[$i]);
            $b       = $a ^ $accessKey;
            $encoded .= chr($b);
        }

        return base64_encode($encoded);
    }
}

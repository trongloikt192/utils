<?php
/**
 * Created by PhpStorm.
 * User: thang
 * Date: 6/25/2018
 * Time: 10:27 PM
 */

namespace trongloikt192\Utils;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class GetLinkFunction
{
    // ================================================================================
    // ================================== GLOBAL FUNCTION =============================
    // ================================================================================

    /**
     * Get short link Ouo
     *
     * @param string $link
     * @param string $ip
     * @return string
     */
    public static function g_getShortUrl($link, $ip)
    {
        $longURL      = urlencode($link);
        $providerList = [
            // "http://ouo.io/api/UqgBB0RM?s={$link}",
            // "http://shink.in/stxt/0/id/132106/auth_token/uqBeFx?s={$link}",
            // "http://shink.in/stxt/0/id/140178/auth_token/4ETc03?s={$link}",
            // "http://short.am/s/17768?s={$link}",
            // "https://licklink.net/full/?api=0d2bedbae872e4f3db38bba8917a81828ae4132b&url={$linkEncode}&type=2",
            // "https://123link.pw/full/?api=fd886e7c09b3d9dc09aafbfa7cda474465b5030c&url={$linkEncode}&type=2",
            "https://link4m.co/api-shorten/v2?api=63773e752fc559675a34d1c5&url={$longURL}"
        ];

        // CHỌN RA QUẢNG CÁO KẾ TIẾP
        $cacheKey    = NEXT_SHORTURL_NUMBER_PREFIX . $ip;
        $stackNumber = Cache::get($cacheKey, 0);
        if ($stackNumber > (count($providerList) - 1)) {
            $stackNumber = 0;
        }
        $nextStackNumber = $stackNumber + 1;
        if ($nextStackNumber > (count($providerList) - 1)) {
            $nextStackNumber = 0;
        }
        Cache::put($cacheKey, $nextStackNumber, Carbon::tomorrow());
        return $providerList[$stackNumber];

        /* UPDATE 2019-02-09 : Trả trực tiếp link, bỏ qua bước call api, để tăng tốc độ
         *
        // Get cURL resource
        $curl = curl_init();
        // Set some options - we are passing in a useragent too here
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER  => 1,
            CURLOPT_URL             => $url,
            CURLOPT_USERAGENT       => 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:46.0) Gecko/20100101 Firefox/46.0'
        ));
        // Send the request & save response to $resp
        $res = curl_exec($curl);
        // Close request to clear up some resources
        curl_close($curl);

        $shortLink = json_decode($res, 1);
        if (isset($shortLink['shortenedUrl']) && !empty($shortLink['shortenedUrl'])) {
            return $shortLink['shortenedUrl'];
        }

        return $res;*/
    }

    /**
     * @param $link
     * @return mixed|null
     */
    public static function detectProviderFromLink($link)
    {
        $rs = self::detectProviderAndCodeFromLink($link);
        if ($rs['status'] === TRUE) {
            return $rs['provider'];
        }

        return null;
    }

    /**
     * Get provider và code từ link đầu vào
     *
     * @param string $link
     * @return array
     */
    public static function detectProviderAndCodeFromLink($link)
    {
        $result = array('provider' => null, 'code' => null, 'status' => FALSE);

        $regex = [
            HOST_FSHARE_VN          => ['/fshare.vn\/file\/(\w{1,})/']
            , HOST_4SHARE_VN        => ['/4share.vn\/f\/(\w{1,})/']
            , HOST_ZIPPYSHARE_COM   => ['/zippyshare.com\/v\/([0-9a-zA-Z]+)\/file.html/']
            , HOST_ALFAFILE_NET     => ['/alfafile.net\/file\/([0-9a-zA-Z]+)/']
            , HOST_EXTMATRIX_COM    => ['/extmatrix.com\/files\/([0-9a-zA-Z]+)/']
            //, HOST_DATAFILE_COM     => ["/datafile.com\/d\/([0-9a-zA-Z]+)/"]
            //, HOST_DEPFILE_COM      => ["/depfile.com\/([0-9a-zA-Z]+)/"]
            //, HOST_DEPOSITFILES_COM => ["/depositfiles.com\/files\/([0-9a-zA-Z]+)/"]
            //, HOST_FILEFACTORY_COM  => ["/filefactory.com\/file\/([0-9a-zA-Z]+)/"]
            , HOST_FILENEXT_COM     => ['/filenext.com\/([0-9a-zA-Z]+)/']
            , HOST_HITFILE_NET      => ['/hitfile.net\/([0-9a-zA-Z]+)/', '/hil.to\/([0-9a-zA-Z]+)/']
            , HOST_KATFILE_COM      => ['/katfile.com\/([0-9a-zA-Z]+)/']
            , HOST_KEEP2SHARE_CC    => ['/keep2share.cc\/file\/([0-9a-zA-Z]+)/', '/k2s.cc\/file\/([0-9a-zA-Z]+)/', '/keep2s.cc\/file\/([0-9a-zA-Z]+)/', '/k2share.cc\/file\/([0-9a-zA-Z]+)/']
            , HOST_NITROFLARE_COM   => ['/nitroflare.com\/view\/([0-9a-zA-Z]+)/', '/nitro.download\/view\/([0-9a-zA-Z]+)/']
            , HOST_UPLOADED_NET     => ['/uploaded.net\/file\/([0-9a-zA-Z]+)/', '/uploaded.to\/file\/([0-9a-zA-Z]+)/', '/ul.to\/([0-9a-zA-Z]+)/']
            , HOST_UPLOADGIG_COM    => ['/uploadgig.com\/file\/download\/([0-9a-zA-Z]+)/']
            , HOST_UPTOBOX_COM      => ['/uptobox.com\/([0-9a-zA-Z]+)/']
            , HOST_PREFILES_COM     => ['/prefiles.com\/([0-9a-zA-Z]+)/']
            , HOST_RAPIDGATOR_NET   => ['/rapidgator.net\/file\/([0-9a-zA-Z]+)/', '/rg.to\/file\/([0-9a-zA-Z]+)/']
            , HOST_TURBOBIT_NET     => ['/turbobit.net\/([0-9a-zA-Z]+\.html)/', '/turboget.net\/([0-9a-zA-Z]+\.html)/', '/turb.cc\/([0-9a-zA-Z]+\.html)/']
            , HOST_1FICHIER_COM     => ['/1fichier.com\/\?([0-9a-zA-Z]+)/']
            , HOST_GOOGLE_DRIVE_COM => ['/drive.google.com\/file\/d\/([0-9a-zA-Z-_=.]+)/', '/drive.google.com\/open\?id=([0-9a-zA-Z-_=.]+)/']
            , HOST_ONEDRIVE_COM     => ['/sharepoint.com\/.*\/download.aspx\?share=([\w\_\-\+]+)/']
            , HOST_VNLINKS_NET      => ['/vnlinks.net\/file\/(\w{1,})/']
        ];

        foreach ($regex as $provider => $arrRegx) {
            foreach ($arrRegx as $regx) {
                if (preg_match($regx, $link, $match) == 1) {
                    $result['provider'] = $provider;
                    $result['code']     = $match[1];
                    $result['link']     = self::reformatLink($result['provider'], $result['code']) ?? $link;
                    $result['status']   = TRUE;
                    return $result;
                }
            }
        }
        return $result;
    }

    /**
     * Chỉnh lại link cho chuẩn
     *
     * @param $provider
     * @param $code
     * @return string
     */
    public static function reformatLink($provider, $code)
    {
        switch ($provider) {
            case HOST_1FICHIER_COM:
                return "https://1fichier.com/?{$code}";
            case HOST_4SHARE_VN:
                return "https://4share.vn/f/{$code}";
            case HOST_FSHARE_VN:
                return "https://fshare.vn/file/{$code}";
            case HOST_GOOGLE_DRIVE_COM:
                return "https://drive.google.com/file/d/{$code}";
        }
        return null;
    }

    /**
     * @param $link
     * @return mixed|null
     */
    public static function detectCodeFromLink($link)
    {
        $rs = self::detectProviderAndCodeFromLink($link);
        if ($rs['status'] === TRUE) {
            return $rs['code'];
        }

        return null;
    }

    /**
     * Get số lượng connection hiện tại của server
     *
     * @param $address
     * @return mixed
     */
    public static function getCurrentConnectionNumOfServer($address)
    {
        $url_check = self::urlGetNumConnOfServer($address);
        $ch        = curl_init($url_check);
        curl_setopt($ch, CURLOPT_HEADER, false);    // we want headers
        curl_setopt($ch, CURLOPT_NOBODY, false);    // we don't need body
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $numConnection = curl_exec($ch);
        curl_close($ch);

        return $numConnection;
    }

    /**
     * @param $server
     * @return string
     */
    public static function urlGetNumConnOfServer($server)
    {
        return 'https://' . $server . '/connections';
    }

    /**
     * Return getlink address
     * http://download01.vnlinks.net:81/<path>
     * @param $server
     * @param null $path request-getlink, request-getlink-directly
     * @return string
     */
    public static function getDomainGetLinkFromServerName($server, $path = null)
    {
        $result = 'http://' . self::removeSchemeURL($server) . ':81';
        if (strlen($path) > 0) {
            $result .= '/' . ltrim($path, '/');
        }
        return $result;
    }

    /**
     * @param $server
     * @return string
     */
    public static function urlGetBandwidthUsageOfServer($server)
    {
        return 'https://' . $server . '/bandwidth';
    }

    /**
     * @param $server
     * @return string
     */
    public static function urlSaveDownloadInfoOfServer($server)
    {
        return 'https://' . $server . '/generate';
    }

    /**
     * Download link (SSL)
     * @param $server
     * @param $file_name
     * @param $download_key
     * @return string
     */
    public static function urlDownloadOfServer($server, $file_name, $download_key)
    {
        $file_name_encode = urlencode($file_name);
        return 'https://' . self::removeSchemeURL($server) . '/download/' . $download_key . '/' . $file_name_encode;
    }

    /**
     * Remove scheme from URL
     * Ex: https://192.168.0.1:8080 => 192.168.0.1:8080
     *
     * @param $url
     * @return mixed
     */
    public static function removeSchemeURL($url)
    {
        $parse  = parse_url($url);
        $scheme = $parse['scheme'] ?? null;

        // Skip if not exist scheme
        if (!isset($scheme)) {
            return $url;
        }

        // Remove scheme
        return ltrim($url, $scheme . '://');
    }
}

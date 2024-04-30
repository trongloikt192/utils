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
use trongloikt192\Utils\Exceptions\UtilException;
use trongloikt192\Utils\Host\RapidgatorApi;

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
             "http://ouo.io/api/UqgBB0RM?s={$link}",
            // "http://shink.in/stxt/0/id/132106/auth_token/uqBeFx?s={$link}",
            // "http://shink.in/stxt/0/id/140178/auth_token/4ETc03?s={$link}",
            // "http://short.am/s/17768?s={$link}",
            // "https://licklink.net/full/?api=0d2bedbae872e4f3db38bba8917a81828ae4132b&url={$linkEncode}&type=2",
            // "https://link4m.co/api-shorten/v2?api=63773e752fc559675a34d1c5&url={$longURL}",
            // "https://link1s.com/api?api=d81b6b59b1b20102eb7a37ee79241f146a80e129&url={$longURL}",
            // "https://megaurl.io/api?api=5b71f70c40494e1a3e884e3606b018eef2d09ac1&url={$longURL}"
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

        // Get ads link
        $requestURL = $providerList[$stackNumber];
        if ($stackNumber == 0) {
            return file_get_contents($requestURL);
        }

        $result = @json_decode(file_get_contents($requestURL),TRUE);
        if($result["status"] !== 'success') {
            $adsLink = $link;
        } else {
            $adsLink = $result["shortenedUrl"];
        }

        return $adsLink;
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
     * Get provider và code (và filename nếu có) từ link đầu vào
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
            , HOST_NITROFLARE_COM   => ['/nitroflare.com\/view\/([0-9a-zA-Z]+)\/?(.+)?/', '/nitro.download\/view\/([0-9a-zA-Z]+)\/?(.+)?/']
            , HOST_UPLOADED_NET     => ['/uploaded.net\/file\/([0-9a-zA-Z]+)/', '/uploaded.to\/file\/([0-9a-zA-Z]+)/', '/ul.to\/([0-9a-zA-Z]+)/']
            , HOST_UPLOADGIG_COM    => ['/uploadgig.com\/file\/download\/([0-9a-zA-Z]+)/']
            , HOST_UPTOBOX_COM      => ['/uptobox.com\/([0-9a-zA-Z]+)/']
            , HOST_PREFILES_COM     => ['/prefiles.com\/([0-9a-zA-Z]+)/']
            , HOST_RAPIDGATOR_NET   => ['/rapidgator.net\/file\/([0-9a-zA-Z]+)\/?(.+)?/', '/rg.to\/file\/([0-9a-zA-Z]+)\/?(.+)?/']
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
                    $result['filename'] = isset($match[2]) ? trim(utf8_decode(urldecode($match[2]))) : null;
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
            case HOST_NITROFLARE_COM:
                return "https://nitroflare.com/view/{$code}";
            case HOST_RAPIDGATOR_NET:
                return "https://rapidgator.net/file/{$code}";
            case HOST_ALFAFILE_NET:
                return "https://alfafile.net/file/{$code}";
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
     * Get filename from link and remove domain in filename
     *
     * @param $link
     * @return mixed|null
     */
    public static function detectFilenameFromLink($link)
    {
        $rs = self::detectProviderAndCodeFromLink($link);
        if ($rs['status'] !== TRUE) {
            return null;
        }

        $provider = $rs['provider'];
        $filename = null;
        switch ($provider) {
            case HOST_NITROFLARE_COM:
                $filename = $rs['filename'];
                break;

            case HOST_RAPIDGATOR_NET:
                $filename = rtrim($rs['filename'], '.html');
                break;

            default:
                break;
        }

        // Remove sanet.st in filename by regex with case insensitive
        $filename = preg_replace('/sanet\.st/i', '', $filename);

        // Other case

        return $filename;
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
     * //http://download01.vnlinks.net:81/<path> <-- old
     * https://download01.vnlinks.net/<path> <-- new
     * @param $server
     * @param null $path request-getlink, request-getlink-directly
     * @return string
     */
    public static function getDomainGetLinkFromServerName($server, $path = null)
    {
        $result = 'https://' . self::removeSchemeURL(trim($server, '/'));
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

    /**
     * Get file info from link
     *
     * @param $linkList
     * @return null
     * Ex:
     * [
     *   'nitroflare.com' => [
     *     'code' => [
     *      'url' => 'abc',
     *      'filename' => 'abc',
     *      'filesize' => 123,
     *     ]
     *     ...
     *   ],
     *   'rapidgator.net' => [
     *     'code' => [
     *      'url' => 'abc',
     *      'filename' => 'abc',
     *      'filesize' => 123,
     *     ]
     *     ...
     *   ]
     *   ...
     * ]
     */
    public static function getFileInfoFromLinks($linkList)
    {
        $result = [];
        $groupLinks = [];

        // Group list link by provider
        foreach ($linkList as $link) {
            $provider = self::detectProviderFromLink($link);
            if (isset($groupLinks[$provider])) {
                $groupLinks[$provider][] = $link;
            } else {
                $groupLinks[$provider] = [$link];
            }
        }

        // Get file info from each provider
        foreach ($groupLinks as $provider => $links) {
            switch ($provider) {
                case HOST_NITROFLARE_COM:
                    // separate links to 25 links per request
                    $links = array_chunk($links, 25);
                    foreach ($links as $link) {
                        $result[$provider] = array_merge($result[$provider] ?? [], self::getNitroflareFileInfo($link));
                    }
                    break;

                case HOST_RAPIDGATOR_NET:
                    // separate links to 25 links per request
                    $links = array_chunk($links, 25);
                    foreach ($links as $link) {
                        $result[$provider] = array_merge($result[$provider] ?? [], self::getRapidgatorFileInfo($link));
                    }
                    break;

                default:
                    break;
            }
        }

        return $result;
    }

    /**
     * Get nitroflare file info
     *
     * @param $linkList
     * @return array
     * Ex: [
     *    'code' => [
     *       'url'      => 'abc',
     *       'filename' => 'abc',
     *       'filesize' => 123,
     *    ]
     * ]
     */
    public static function getNitroflareFileInfo($linkList) {
        $result = [];

        $codeList = [];
        foreach ($linkList as $link) {
            $codeList[] = self::detectCodeFromLink($link);
        }

        $data = HttpUtil::g_curlGet('https://nitroflare.com/api/v2/getFileInfo?files=' . implode(',', $codeList));
        $info = json_decode($data, true );
        if ($info['type'] == 'success') {
            $files = $info['result']['files'];
            foreach ($files as $cd => $file) {
                $result[$cd] = [
                    'url'      => $file['url'],
                    'filename' => $file['name'],
                    'filesize' => $file['size'],
                ];
            }
        }

        return $result;
    }

    /**
     * Get rapidgator file info
     *
     * @param $linkList
     * @return array
     * Ex: [
     *    'code' => [
     *       'url'      => 'abc',
     *       'filename' => 'abc',
     *       'filesize' => 123,
     *    ]
     * ]
     */
    public static function getRapidgatorFileInfo($linkList)
    {
        $result = [];

        try {
            // CONNECT API
            $client = new RapidgatorApi(env('RAPIDGATOR_EMAIL'), env('RAPIDGATOR_PASSWORD'), storage_path('rapidgator.token'));

            // GET FILE SIZE & NAME
            $response = $client->checkLink(implode(',', $linkList));
            foreach ($response as $file) {
                if ($file->status != 'ACCESS') {
                    continue;
                }

                $code = self::detectCodeFromLink($file->url);
                $result[$code] = [
                    'url'      => $file->url,
                    'filename' => $file->filename,
                    'filesize' => $file->size,
                ];
            }
        } catch (\Exception $e) {
            //
        }

        return $result;
    }
}

<?php
/**
 * Created by PhpStorm.
 * User: thang
 * Date: 6/25/2018
 * Time: 10:27 PM
 */

namespace trongloikt192\Utils;

use Carbon\Carbon;
use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use stdClass;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use trongloikt192\Utils\Exceptions\UtilException;

class GetLinkFunction
{
    // ================================================================================
    // ================================== GLOBAL FUNCTION =============================
    // ================================================================================

    /**
     * Get short link Ouo
     *
     * @param $link
     * @param string $ip
     * @return string
     */
    public static function g_getShortUrl($link, $ip)
    {
        $linkEncode   = base64_encode($link);
        $providerList = [
            // "http://ouo.io/api/UqgBB0RM?s={$link}",
            // "http://shink.in/stxt/0/id/132106/auth_token/uqBeFx?s={$link}",
            // "http://shink.in/stxt/0/id/140178/auth_token/4ETc03?s={$link}",
            // "http://short.am/s/17768?s={$link}",
            "https://licklink.net/full/?api=0d2bedbae872e4f3db38bba8917a81828ae4132b&url={$linkEncode}&type=2",
            "https://123link.pw/full/?api=fd886e7c09b3d9dc09aafbfa7cda474465b5030c&url={$linkEncode}&type=2",
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
        $url = $providerList[$stackNumber];

        return $url;

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
     * Declare response format
     *
     * @param null $provider
     * @return stdClass
     */
    public static function g_declareOut($provider = null)
    {
        $out          = new StdClass();
        $out->status  = false;
        $out->message = null;

        $out->inputErrors = [];

        // Thông tin file
        $out->id           = null;
        $out->provider     = null;
        $out->link         = null;
        $out->fileName     = null;
        $out->fileSize     = null;
        $out->downloadLink = null;
        $out->fdFlag       = false; // fshare direct link free tốc độ thấp
        $out->fdLink       = null; // fshare direct link free tốc độ thấp

        // Tiện ích
        $out->favorite  = false;
        $out->shareLink = null;

        // Captcha
        $out->requestCaptcha = false;
        $out->captchaSrc     = null;

        // file video
        $out->isVideo        = false;
        $out->videoFormat    = null;
        $out->isVideoBrowser = false;

        //
        $out->callback   = null;
        $out->requestAdv = false;

        return $out;
    }

    /**
     * @param $url
     * @param null $cookie_jar
     * @param null $proxy
     * @param bool $redirect
     * @return mixed
     */
    public static function g_curlGet($url, $cookie_jar = null, $proxy = null, $redirect = false)
    {
        $ch = curl_init();
        if (isset($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2');
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        if (isset($cookie_jar)) {
            curl_setopt($ch, CURLOPT_COOKIE, 1);
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);  // <-- add this line
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: en']);
        if ($redirect == true) {
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);
        }
        $response = curl_exec($ch);
        if ($redirect == true) {
            $response = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        }
        curl_close($ch);

        return $response;
    }

    /**
     * @param $url
     * @param $data
     * @param null $cookie_jar
     * @param null $proxy
     * @param bool $redirect
     * @return mixed
     */
    public static function g_curlPost($url, $data, $cookie_jar = null, $proxy = null, $redirect = false)
    {
        if (is_array($data)) {
            $postdata = http_build_query($data);
        } else {
            $postdata = $data;
        }

        $ch = curl_init();
        if (isset($proxy)) {
            curl_setopt($ch, CURLOPT_PROXY, $proxy);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (isset($cookie_jar)) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);  // <-- add this line
        }
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept-Language: en']);
        if ($redirect == true) {
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            // curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Connection: Close']);
        }
        $response = curl_exec($ch);
        if ($redirect == true) {
            // $loop = 5;
            // $newurl = curl_getinfo ($ch, CURLINFO_EFFECTIVE_URL);
            // curl_setopt($ch, CURLOPT_HEADER, true);
            // curl_setopt($ch, CURLOPT_FORBID_REUSE, false);
            // do {
            //     curl_setopt($ch, CURLOPT_URL, $newurl);
            //     $header = curl_exec($ch);
            //     print_r($newurl); echo "\n";
            //     if (curl_errno($ch)) {
            //         $code = 0;
            //     } else {
            //         $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            //         echo $code; echo "\n";
            //         if ($code == 301 || $code == 302) {
            //             preg_match('/Location:(.*?)\n/', $header, $matches);
            //             $newurl = trim(array_pop($matches));
            //         } else {
            //             $code = 0;
            //         }
            //     }
            // } while($code && --$loop);
            // print_r($newurl); echo "\n";
            $response = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
        }
        curl_close($ch);
        return $response;
    }

    /**
     * @param $url
     * @return string
     */
    public static function g_getDirectLink($url)
    {
        $urlInfo = parse_url($url);
        $out     = "GET  {$url} HTTP/1.1\r\n";
        $out     .= "Host: {$urlInfo['host']}\r\n";
        $out     .= "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6\r\n";
        $out     .= "Connection: Close\r\n\r\n";
        $con     = @fsockopen('ssl://' . $urlInfo['host'], 443, $errno, $errstr, 10);
        if (!$con) {
            return $errstr . ' ' . $errno;
        }
        fwrite($con, $out);
        $data = '';
        while (!feof($con)) {
            $data .= fgets($con, 512);
        }
        fclose($con);
        preg_match("!\r\n(?:Location|URI): *(.*?) *\r\n!", $data, $matches);
        $url = $matches[1];
        return trim($url);
    }

    /**
     * abstract the request content-type behind one single method,
     * since Goutte does not natively support this
     * * Lưu ý: chỉ đọc Cookie, dùng g_goutteRequestLogin, g_goutteSubmitForm để lưu cookie
     *
     * @param string $method HTTP method (GET/POST/PUT..)
     * @param string $url URL to load
     * @param mixed $parameters HTTP parameters (POST, etc) to be sent URLencoded
     *                           or in JSON format.
     * @param null $cookie_jar
     * @param array $options
     * @return Client
     */
    public static function g_goutteRequest($method, $url, $parameters, $cookie_jar = null, $options = array('redirect' => false, 'isRequestJson' => false, 'headers' => array(), 'proxy' => null, 'useIpv6' => false))
    {
        $redirect_url = '';
        $config       = array('debug' => false, 'timeout' => 10, 'http_errors' => false, 'verify' => false, 'cookies' => true);

        if (isset($options['proxy']) && !empty($options['proxy'])) {
            $config['proxy'] = ['http' => $options['proxy'], 'https' => $options['proxy']];
        }

        if (isset($options['redirect']) && $options['redirect'] == true) {
            $config['allow_redirects'] = false;
            $config['on_headers']      = function (ResponseInterface $response) use (&$redirect_url) {
                $statusCode = $response->getStatusCode();
                if ($statusCode == 301 || $statusCode == 302) {
                    $redirect_url = $response->getHeaderLine('Location');
                    throw new UtilException('Redirect url!');
                }

                if (!empty($redirect_url)) {
                    throw new UtilException('Error Processing Request');
                }

                if ($response->getHeaderLine('Content-Length') > 1024) {
                    throw new UtilException('The file is too big!');
                }
            };
        }

        if (isset($options['useIpv6']) && $options['useIpv6'] == true) {
            $config['force_ip_resolve'] = 'v6';
        }

        $cookies = null;
        if (isset($cookie_jar)) {
            $cookies = self::goutteSetCookieJar($cookie_jar);
        }

        $guzzleClient = new GuzzleClient($config);
        $client       = new Client([], null, $cookies);
        $client->setClient($guzzleClient);
        $client->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:46.0) Gecko/20100101 Firefox/46.0');
        $client->setHeader('Accept-Language', 'en');

        if (isset($options['headers']) && !empty($options['headers'])) {
            foreach ($options['headers'] as $key => $value) {
                $client->setHeader($key, $value);
            }
        }

        if (isset($options['isRequestJson']) && $options['isRequestJson'] == true) {
            $client->request($method, $url, array(), array(), array('HTTP_CONTENT_TYPE' => 'application/json'), json_encode($parameters));
        } elseif (isset($options['headers']['Content-Type']) && $options['headers']['Content-Type'] === 'text/plain') {
            $client->request($method, $url, array(), array(), array('HTTP_CONTENT_TYPE' => 'text/plain'), $parameters);
        } else {
            $client->request($method, $url, $parameters);
        }

//        if (isset($cookie_jar)) {
//            self::goutteSaveCookieJar($client, $cookie_jar);
//        }

        return $client;
    }

    /**
     * Hàm đọc cookie từ File
     *
     * @param $cookie_jar
     * @return CookieJar
     */
    private static function goutteSetCookieJar($cookie_jar)
    {
        $cookieJar = new CookieJar();

        if (is_file($cookie_jar)) {
            // Load cookies and populate browserkit's cookie jar
            $cookie_array = json_decode(file_get_contents($cookie_jar), 1);

            foreach ($cookie_array as $ck) {
                $name     = $ck['name'];
                $value    = $ck['value'];
                $expires  = isset($ck['expirationDate']) ? round($ck['expirationDate']) : null;
                $path     = $ck['path'] ?? '/';
                $domain   = $ck['domain'] ?? '';
                $secure   = $ck['secure'] ?? false;
                $httpOnly = $ck['httpOnly'] ?? true;

                $cookie = new Cookie($name
                    , $value
                    , $expires
                    , $path
                    , $domain
                    , $secure
                    , $httpOnly
                );
                $cookieJar->set($cookie);
            }

        }

        return $cookieJar;
    }

    /**
     * Dùng để login và ghi Cookie
     *
     * abstract the request content-type behind one single method,
     * since Goutte does not natively support this
     * @param string $method HTTP method (GET/POST/PUT..)
     * @param string $url URL to load
     * @param array $parameters HTTP parameters (POST, etc) to be sent URLencoded
     *                           or in JSON format.
     * @param null $cookie_jar
     * @param null $proxy
     * @param array $options
     * @return Client
     */
    public static function g_goutteRequestLogin($method, $url, $parameters, $cookie_jar = null, $proxy = null, $options = array('redirect' => false, 'isRequestJson' => false))
    {
        $redirect_url = '';
        $config       = array('debug' => false, 'timeout' => 10, 'http_errors' => false, 'verify' => false, 'cookies' => true);

        if (isset($proxy)) {
            $config['proxy'] = ['http' => $proxy, 'https' => $proxy];
        }

        if (isset($options['redirect']) && $options['redirect'] == true) {
            $config['allow_redirects'] = false;
            $config['on_headers']      = static function (ResponseInterface $response) use (&$redirect_url) {
                $statusCode = $response->getStatusCode();
                if ($statusCode == 301 || $statusCode == 302) {
                    $redirect_url = $response->getHeaderLine('Location');
                    throw new UtilException('Redirect url!');
                }

                if (!empty($redirect_url)) {
                    throw new UtilException('Error Processing Request');
                }
            };
        }

        if (isset($options['useIpv6']) && $options['useIpv6'] == true) {
            $config['force_ip_resolve'] = 'v6';
        }

        if (!is_array($parameters)) {
            $parameters = array();
        }

        $cookies = null;
        if (isset($cookie_jar)) {
            $cookies = self::goutteSetCookieJar($cookie_jar);
        }

        $guzzleClient = new GuzzleClient($config);
        $client       = new Client([], null, $cookies);
        $client->setClient($guzzleClient);
        $client->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:46.0) Gecko/20100101 Firefox/46.0');
        $client->setHeader('Accept-Language', 'en');

        if (isset($options['isRequestJson']) && $options['isRequestJson'] == true) {
            $client->request($method, $url, array(), array(), array('HTTP_CONTENT_TYPE' => 'application/json'), json_encode($parameters));
        } else {
            $client->request($method, $url, $parameters);
        }

        if (isset($cookie_jar)) {
            self::goutteSaveCookieJar($client, $cookie_jar);
        }

        return $client;
    }

    /**
     * Hàm lưu cookie vào File
     * EditThisCookie Chrome Extension
     *
     * @param Client $client
     * @param $cookie_jar
     */
    private static function goutteSaveCookieJar($client, $cookie_jar)
    {
        $cookie_array = array();
        $cookieJar    = $client->getCookieJar();
        $cookies      = $cookieJar->all();

        if ($cookies) {
            $id = 1;
            foreach ($cookies as $cookie) {
                $cookie_array[] = [
                    'domain'         => $cookie->getDomain(),
                    'expirationDate' => (int)$cookie->getExpiresTime(),
                    /*"hostOnly"          => $cookie->getDomain(),*/
                    'httpOnly'       => $cookie->isHttpOnly(),
                    'name'           => $cookie->getName(),
                    'path'           => $cookie->getPath(),
                    'sameSite'       => 'no_restriction',
                    'secure'         => (bool)$cookie->isSecure(),
                    'session'        => (bool)false,
                    'storeId'        => '0',
                    'value'          => $cookie->getValue(),
                    'id'             => $id
                ];

                $id++;
            }

            $cookie_json = json_encode($cookie_array);

            file_put_contents($cookie_jar, $cookie_json);
        }
    }

    /**
     * Submit Form - chủ yếu dùng cho login
     * @param string $url URL to load
     * @param array $params HTTP parameters (POST, etc) to be sent URLencoded
     *                           or in JSON format.
     * @param $cookie_jar
     * @param null $proxy
     * @param string $button_name : button submit form. Ex: <button>button_name</button>
     * @param string $form_filter : id, class hoặc attribute của form. Ex: form.login-form hoặc #login-form
     * @return Client
     */
    public static function g_goutteSubmitForm($url, $params, $cookie_jar, $proxy = null, $button_name = null, $form_filter = null)
    {
        $config = array('debug' => false, 'verify' => false);
        if (isset($proxy)) {
            $config['proxy'] = ['http' => $proxy, 'https' => $proxy];
        }
        $guzzleClient = new GuzzleClient($config);
        $client       = new Client();
        $client->setClient($guzzleClient);
        $client->setHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; WOW64; rv:46.0) Gecko/20100101 Firefox/46.0');
        $client->setHeader('Accept-Language', 'en');
        $crawler = $client->request('GET', $url);
        if (isset($form_filter)) {
            $form = $crawler->filter($form_filter)->form();
        } else if (isset($button_name)) {
            $form = $crawler->selectButton($button_name)->form();
        } else {
            $form = $crawler->filter('form')->form();
        }
        $client->submit($form, $params);
        self::goutteSaveCookieJar($client, $cookie_jar);

        return $client;
    }

    /**
     * Chỉnh lại link cho chuẩn
     *
     * @param $link
     * @return string
     */
    public static function reformatLink($link)
    {
        $provider = self::detectProviderFromLink($link);
        $code     = self::detectCodeFromLink($link);

        switch ($provider) {
            case HOST_4SHARE_VN:
                return "https://4share.vn/f/{$code}";
            case HOST_FSHARE_VN:
                return "https://fshare.vn/file/{$code}";
            case HOST_GOOGLE_DRIVE_COM:
                return "https://drive.google.com/file/d/{$code}";
        }
        return $link;
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
            HOST_FSHARE_VN          => ["/fshare.vn\/file\/(\w{1,})/"]
            , HOST_4SHARE_VN        => ["/4share.vn\/f\/(\w{1,})/"]
            , HOST_TENLUA_VN        => ["/tenlua.vn\/[0-9a-zA-Z]+\/(\w{1,})/"]
            , HOST_YOUTUBE_COM      => ["/youtube.com\/watch\?v=([0-9a-zA-Z-]+)/", "/youtu.be\/([0-1a-zA-Z-]+)/"]
            , HOST_TAILIEU_VN       => ["/tailieu.vn\/([doc|bst]{3}\/[a-zA-Z0-9-=.]+\.html)/"]
            , HOST_ZIPPYSHARE_COM   => ["/zippyshare.com\/v\/([0-9a-zA-Z]+)\/file.html/"]
            , HOST_ALFAFILE_NET     => ["/alfafile.net\/file\/([0-9a-zA-Z]+)/"]
            , HOST_BIGFILE_TO       => ["/bigfile.to\/file\/([0-9a-zA-Z]+)/", "/bigfile.to\/video\/([0-9a-zA-Z]+)/"]
            , HOST_DATAFILE_COM     => ["/datafile.com\/d\/([0-9a-zA-Z]+)/"]
            , HOST_DEPFILE_COM      => ["/depfile.com\/([0-9a-zA-Z]+)/"]
            , HOST_DEPOSITFILES_COM => ["/depositfiles.com\/files\/([0-9a-zA-Z]+)/"]
            , HOST_FILEFACTORY_COM  => ["/filefactory.com\/file\/([0-9a-zA-Z]+)/"]
            , HOST_FILENEXT_COM     => ["/filenext.com\/([0-9a-zA-Z]+)/"]
            , HOST_KEEP2SHARE_CC    => ["/keep2share.cc\/file\/([0-9a-zA-Z]+)/", "/k2s.cc\/file\/([0-9a-zA-Z]+)/", "/keep2s.cc\/file\/([0-9a-zA-Z]+)/", "/k2share.cc\/file\/([0-9a-zA-Z]+)/"]
            , HOST_NITROFLARE_COM   => ["/nitroflare.com\/view\/([0-9a-zA-Z]+)/"]
            , HOST_UPLOADED_NET     => ["/uploaded.net\/file\/([0-9a-zA-Z]+)/", "/uploaded.to\/file\/([0-9a-zA-Z]+)/", "/ul.to\/([0-9a-zA-Z]+)/"]
            , HOST_UPTOBOX_COM      => ["/uptobox.com\/([0-9a-zA-Z]+)/"]
            , HOST_RAPIDGATOR_NET   => ["/rapidgator.net\/file\/([0-9a-zA-Z]+)/", "/rg.to\/file\/([0-9a-zA-Z]+)/"]
            , HOST_TURBOBIT_NET     => ["/turbobit.net\/([^\/]*)/"]
            , HOST_NHACCUATUI_COM   => ["/nhaccuatui\.com\/bai\-hat\/.*\.([0-9a-zA-Z]+)\.html/", "/nhaccuatui\.com\/video\/.*\.([0-9a-zA-Z]+)\.html/", "/nhaccuatui\.com\/playlist\/.*\.([0-9a-zA-Z]+)\.html/"]
            , HOST_1FICHIER_COM     => ["/1fichier.com\/\?([0-9a-zA-Z]+)/"]
            , HOST_GOOGLE_DRIVE_COM => ["/drive.google.com\/file\/d\/([0-9a-zA-Z-_=.]+)/", "/drive.google.com\/open\?id=([0-9a-zA-Z-_=.]+)/"]
        ];

        foreach ($regex as $site => $arr) {
            foreach ($arr as $rex) {
                $match = null;
                if (preg_match($rex, $link, $match) == 1) {
                    $result['provider'] = $site;
                    $result['code']     = $match[1];
                    break;
                }
            }

            if ($result['provider'] !== null) {
                $result['status'] = TRUE;
                break;
            }
        }

        return $result;
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
     * Check Valid Url
     *
     * @param $provider
     * @param $url
     * @param array $header
     * @param bool $is_redirect
     * @return bool
     */
    public static function checkUrlValid($provider, &$url, &$header = array(), &$is_redirect = false)
    {
        $code = null;

        switch ($provider) {
            case HOST_FSHARE_VN:
                $file_headers = self::g_getHeader($url, 2);
                break;

            default:
                $file_headers = @get_headers($url);
                break;
        }

        // when server not found
        if ($file_headers === false) {
            return false;
        }

        // parse all headers:
        foreach ($file_headers as $item) {
            // corrects $url when 301/302 redirect(s) lead(s) to 200:
            if (preg_match('/^Location: (http.+)$/', $item, $m)) {
                $url         = $m[1];
                $is_redirect = true;
                // @TODO: Nếu redirect => đệ qui
            }
            // grabs the last $header $code, in case of redirect(s):
            if (preg_match("/^HTTP.+\s(\d\d\d)\s/", $item, $m)) {
                $code = $m[1];
            }
            if (preg_match('/Content-Disposition: (.*)/', $item, $m)) {
                $header['content-disposition'] = trim($m[1]);
            }
            if (preg_match('/Content-Type: (.*)/', $item, $m)) {
                $header['content-type'] = trim($m[1]);
            }
            if (preg_match('/Accept-Ranges: bytes/', $item)) {
                $header['accept-ranges'] = 'bytes';
            }
            if ($code == 200
                && empty($header['file_size_bytes'])
                && preg_match("/Content-Length: (\d+)/", $item, $m)) {
                $header['content-length'] = $m[1];
            }
        }

        // $code 200 == all OK
        return $code == 200;
    }

    /**
     * @param $url
     * @param $timeout
     * @return array|bool|string
     */
    public static function g_getHeader($url, $timeout = 60)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        // Tell cURL that it should only spend 10 seconds
        // trying to connect to the URL in question.
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $response = curl_exec($ch);

        // Return headers seperatly from the Response Body
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers     = substr($response, 0, $header_size);
        // $body = substr($response, $header_size);
        curl_close($ch);

        $headers = explode("\r\n", $headers); // The seperator used in the Response Header is CRLF (Aka. \r\n)
        $headers = array_filter($headers);

        return $headers;
    }

    /**
     * @param $server
     * @return string
     */
    public static function getDomainGetLinkFromServerName($server)
    {
        $domain     = explode('.', $server);
        $downloadxx = $domain[0];
        $vnlinks    = $domain[1];
        $net        = $domain[2];
        return $downloadxx . '-getlink.' . $vnlinks . '.' . $net . ':81';
    }

    /**
     * @param $server
     * @return string
     */
    public static function urlGetNumConnOfServer($server)
    {
        return 'http://' . $server . '/connections';
    }

    /**
     * @param $server
     * @return string
     */
    public static function urlGetBandwidthUsageOfServer($server)
    {
        return 'http://' . $server . '/bandwidth';
    }

    /**
     * @param $server
     * @return string
     */
    public static function urlSaveDownloadInfoOfServer($server)
    {
        return 'http://' . $server . '/generate';
    }

    /**
     * @param $server
     * @param $file_name
     * @param $download_key
     * @return string
     */
    public static function urlDownloadOfServer($server, $file_name, $download_key)
    {
        $file_name_encode = urlencode($file_name);
        return 'http://' . $server . '/download/' . $download_key . '/' . $file_name_encode;
    }
}

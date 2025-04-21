<?php
/**
 * Created by PhpStorm.
 * User: LoiLT2
 * Date: 8/26/2019
 * Time: 2:00 PM
 */

namespace trongloikt192\Utils;

use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\HttpClient;
use trongloikt192\Utils\Exceptions\UtilException;

class HttpUtil
{
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
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.2) Gecko/20070219 Firefox/2.0.0.2');
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        if (isset($cookie_jar)) {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_jar);
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
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
            curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie_jar);
        }
        curl_setopt($ch, CURLOPT_REFERER, $url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postdata);
        curl_setopt($ch, CURLOPT_POST, 1);
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
     * @return string
     */
    public static function g_getDirectLink($url)
    {
        $urlInfo = parse_url($url);
        $out     = "GET  {$url} HTTP/1.1\r\n";
        $out     .= "Host: {$urlInfo['host']}\r\n";
        $out     .= "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36 Edg/87.0.664.75\r\n";
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
     * since HttpBrowser does not natively support this
     * * Lưu ý: chỉ đọc Cookie, dùng g_goutteRequestLogin, g_goutteSubmitForm để lưu cookie
     *
     * @param string $method HTTP method (GET/POST/PUT..)
     * @param string $url URL to load
     * @param mixed $parameters HTTP parameters (POST, etc) to be sent URLencoded
     *                           or in JSON format.
     * @param null $cookie_jar
     * @param array $options
     * @return HttpBrowser
     */
    public static function g_goutteRequest($method, $url, $parameters=[], $cookie_jar=null, $options=['redirect' => false, 'isRequestJson' => false, 'headers' => [], 'proxy' => null, 'useIpv6' => false])
    {
        $redirect_url = '';
        $config       = array(
            'timeout'     => 60,
            'verify_peer' => false,
            'verify_host' => false,
            'headers'     => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36 Edg/87.0.664.75',
                'Accept-Language' => 'en'
            ]
        );

        if (isset($options['proxy']) && !empty($options['proxy'])) {
            $config['proxy'] = ['http' => $options['proxy'], 'https' => $options['proxy']];
        }

        // TODO: Not working on v10x
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

        // TODO: Not working on v10x
        if (isset($options['useIpv6']) && $options['useIpv6'] == true) {
            $config['force_ip_resolve'] = 'v6';
        }

        $cookies = null;
        if (isset($cookie_jar)) {
            $cookies = self::goutteSetCookieJar($cookie_jar);
        }

        // Fix Parameters are being ignored when sending a GET request
        if (strtoupper($method) == 'GET' && !empty($parameters)) {
            $url .= '?'.http_build_query($parameters);
        }

        $browser = new HttpBrowser(HttpClient::createForBaseUri($url, $config), null, $cookies);

        // Set custom headers if provided
        if (isset($options['headers']) && !empty($options['headers'])) {
            foreach ($options['headers'] as $key => $value) {
                $browser->setServerParameter('HTTP_' . strtoupper(str_replace('-', '_', $key)), $value);
            }
        }

        // Handle request with different content types
        if (isset($options['isRequestJson']) && $options['isRequestJson'] == true) {
            $browser->setServerParameter('HTTP_CONTENT_TYPE', 'application/json');
            $browser->request($method, $url, [], [], ['HTTP_CONTENT_TYPE' => 'application/json'], json_encode($parameters));
        } elseif (isset($options['headers']['Content-Type']) && $options['headers']['Content-Type'] === 'text/plain') {
            $browser->setServerParameter('HTTP_CONTENT_TYPE', 'text/plain');
            $browser->request($method, $url, [], [], ['HTTP_CONTENT_TYPE' => 'text/plain'], $parameters);
        } else {
            $browser->request($method, $url, $parameters);
        }

        return $browser;
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
            $cookie_array = json_decode(file_get_contents($cookie_jar), true);

            foreach ($cookie_array as $ck) {
                $name     = $ck['name'];
                $value    = $ck['value'];
                $expires  = isset($ck['expirationDate']) ? round($ck['expirationDate']) : null;
                $path     = $ck['path'] ?? '/';
                $domain   = $ck['domain'] ?? '';
                $secure   = $ck['secure'] ?? false;
                $httpOnly = $ck['httpOnly'] ?? true;
                $sameSite = $ck['sameSite'];

                $cookie = new Cookie($name
                    , $value
                    , $expires
                    , $path
                    , $domain
                    , $secure
                    , $httpOnly
                    , false
                    , $sameSite
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
     * since HttpBrowser does not natively support this
     * @param string $method HTTP method (GET/POST/PUT..)
     * @param string $url URL to load
     * @param array $parameters HTTP parameters (POST, etc) to be sent URLencoded
     *                           or in JSON format.
     * @param null $cookie_jar
     * @param array $options
     * @return HttpBrowser
     */
    public static function g_goutteRequestLogin($method, $url, $parameters, $cookie_jar, $options=['redirect' => false, 'isRequestJson' => false, 'headers' => [], 'proxy' => null, 'useIpv6' => false])
    {
        $browser = self::g_goutteRequest($method, $url, $parameters, $cookie_jar, $options);

        // Save cookies to file
        if (isset($cookie_jar)) {
            self::goutteSaveCookieJar($browser, $cookie_jar);
        }

        return $browser;
    }

    /**
     * Hàm lưu cookie vào File
     * EditThisCookie Chrome Extension
     *
     * @param HttpBrowser $browser
     * @param $cookie_jar
     */
    public static function goutteSaveCookieJar($browser, $cookie_jar)
    {
        $cookie_array = array();
        $cookieJar    = $browser->getCookieJar();
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
                    'sameSite'       => $cookie->getSameSite(),
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
     * @return HttpBrowser
     */
    public static function g_goutteSubmitForm($url, $params, $cookie_jar, $proxy = null, $button_name = null, $form_filter = null)
    {
        $httpClientOptions = [
            'verify_peer' => false,
            'verify_host' => false,
            'headers' => [
                'User-Agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36 Edg/87.0.664.75',
                'Accept-Language' => 'en'
            ]
        ];
        if (isset($proxy)) {
            $httpClientOptions['proxy'] = $proxy;
        }

        $browser = new HttpBrowser(HttpClient::create($httpClientOptions));

        $crawler = $browser->request('GET', $url);

        if (isset($form_filter)) {
            $form = $crawler->filter($form_filter)->form();
        } else if (isset($button_name)) {
            $form = $crawler->selectButton($button_name)->form();
        } else {
            $form = $crawler->filter('form')->form();
        }

        $browser->submit($form, $params);

        self::goutteSaveCookieJar($browser, $cookie_jar);

        return $browser;
    }

    /**
     * Check Valid Url
     *
     * @param $url
     * @return array
     */
    public static function getHeader(&$url)
    {
        $code = null;
        $header = [];

        $context = stream_context_create([
            'ssl' => [
                'verify_peer'      => false,
                'verify_peer_name' => false,
            ],
            'http' => [
                'method'  => 'HEAD',
                'timeout' => 60,
                'header'  => "User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36 Edg/87.0.664.75'\r\n"
            ]
        ]);
        $file_headers = get_headers($url, true, $context);

        // when server not found
        if (empty($file_headers)) {
            return null;
        }

        // grabs the last $header $code, in case of redirect(s):
        $maxRedirect = 10;
        for ($index = 0; $index < $maxRedirect; $index++) {
            if (isset($file_headers[$index])
                && preg_match("/^HTTP.+\s(\d\d\d)\s/", $file_headers[$index], $m)) {
                $code = $m[1];
                if ($code == 200) {
                    break;
                }
            }
        }

        if ($code != 200) {
            return null;
        }

        if (isset($file_headers['Location'])) {
            $url = is_array($file_headers['Location'])
                ? $file_headers['Location'][$index - 1]
                : $file_headers['Location'];
        }

        if (isset($file_headers['Content-Disposition'])) {
            $header['content-disposition'] = is_array($file_headers['Content-Disposition'])
                ? trim($file_headers['Content-Disposition'][$index])
                : trim($file_headers['Content-Disposition']);
        }
        if (isset($file_headers['Content-Type'])) {
            $header['content-type'] = is_array($file_headers['Content-Type'])
                ? trim($file_headers['Content-Type'][$index])
                : trim($file_headers['Content-Type']);
        }
        if (isset($file_headers['Accept-Ranges'])) {
            $header['accept-ranges'] = is_array($file_headers['Accept-Ranges'])
                ? trim($file_headers['Accept-Ranges'][$index])
                : trim($file_headers['Accept-Ranges']);
        }
        if (isset($file_headers['Content-Length']) && empty($header['file_size_bytes'])) {
            $header['content-length'] = is_array($file_headers['Content-Length'])
                ? trim($file_headers['Content-Length'][$index])
                : trim($file_headers['Content-Length']);
        }

        return $header;
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
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);

        $response = curl_exec($ch);

        // Return headers seperatly from the Response Body
        $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $headers     = substr($response, 0, $header_size);
        curl_close($ch);

        $headers = explode("\r\n", $headers);
        $headers = array_filter($headers);

        return $headers;
    }

    /**
     * Upload single file
     *
     * @throws UtilException
     * @return array
     */
    public static function uploadFile($url, $sourcePath, $options=['headers' => [], 'body' => []])
    {
        // Vì chúng ta đang gửi file nên header của nó
        // phải ở dạng Content-Type:multipart/form-data
        $headers = ['Content-Type: multipart/form-data'];

        // Đối với file cần new CURLFile
        $file = new \CURLFile($sourcePath);
        $postFields = ['file' => $file];

        // Custom headers
        if (!empty($options['headers'])) {
            foreach ($options['headers'] as $key => $val) {
                $headers[] = "{$key}: {$val}";
            }
        }

        // Custom post data
        if (!empty($options['body'])) {
            foreach ($options['body'] as $key => $val) {
                $postFields[$key] = $val;
            }
        }

        // Khởi tạo CURL
        $ch = curl_init($url);

        // Cấu hình có sử dụng header
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Cấu hình sử dụng method POST
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);

        // Thiết lập có gửi file và thông tin file
        curl_setopt($ch, CURLOPT_INFILESIZE, filesize($sourcePath));

        // Cấu hình return
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        // Thực thi
        $respContent = curl_exec($ch);

        // Nếu không tồn tại lỗi nào trong CURL
        if (curl_errno($ch)) {
            throw new UtilException(curl_error($ch));
        }

        $info = curl_getinfo($ch);
        $respCode = $info['http_code'];

        curl_close($ch);

        return [$respContent, $respCode];
    }
}

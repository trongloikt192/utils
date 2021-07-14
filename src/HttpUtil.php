<?php
/**
 * Created by PhpStorm.
 * User: LoiLT2
 * Date: 8/26/2019
 * Time: 2:00 PM
 */

namespace trongloikt192\Utils;

use Goutte\Client;
use GuzzleHttp\Client as GuzzleClient;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\BrowserKit\CookieJar;
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
    public static function g_goutteRequest($method, $url, $parameters=[], $cookie_jar=null, $options=['redirect' => false, 'isRequestJson' => false, 'headers' => [], 'proxy' => null, 'useIpv6' => false])
    {
        $redirect_url = '';
        $config       = array(
            'debug'       => false,
            'timeout'     => 60,
            'http_errors' => false,
            'verify'      => false,
            'cookies'     => true
        );

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

        // Fix Parameters are being ignored when sending a GET request
        if (strtoupper($method) == 'GET' && !empty($parameters)) {
            $url .= '?'.http_build_query($parameters);
        }

        $guzzleClient = new GuzzleClient($config);
        $client       = new Client([], null, $cookies);
        $client->setClient($guzzleClient);
        $client->setHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36 Edg/87.0.664.75');
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
            $cookie_array = json_decode(file_get_contents($cookie_jar), true);

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
     * @param array $options
     * @return Client
     */
    public static function g_goutteRequestLogin($method, $url, $parameters, $cookie_jar, $options=['redirect' => false, 'isRequestJson' => false, 'headers' => [], 'proxy' => null, 'useIpv6' => false])
    {
        $client = self::g_goutteRequest($method, $url, $parameters, $cookie_jar, $options);

        // Save cookies to file
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
        $client->setHeader('User-Agent', 'Mozilla/5.0 (Macintosh; Intel Mac OS X 11_2_3) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/87.0.4280.141 Safari/537.36 Edg/87.0.664.75');
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
        //$file_headers = self::g_getHeader($url, 2);

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
}

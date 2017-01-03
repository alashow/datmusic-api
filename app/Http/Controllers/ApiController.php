<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Controllers;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use PHPHtmlParser\Dom;
use Psr\Http\Message\ResponseInterface;
use Utils;

class ApiController extends Controller
{
    /**
     * @var string selected account phone number
     */
    protected $authPhone;
    /**
     * @var string selected account password
     */
    protected $authPassword;
    /**
     * @var bool is $cookieFiles exists
     */
    protected $authenticated = false;
    /**
     * @var int authentication retries
     */
    protected $authRetries = 0;
    /**
     * @var string full path to cookie file
     */
    protected $cookieFile;
    /**
     * @var FileCookieJar cookie object
     */
    protected $jar;
    /**
     * @var Client Guzzle client
     */
    protected $client;
    /**
     * @var Request
     */
    protected $request;

    /**
     * ApiController constructor.
     * @param $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request;

        $account = config('app.accounts')[array_rand(config('app.accounts'))];

        $this->authPhone = $account[0];
        $this->authPassword = $account[1];

        $this->cookieFile = sprintf(config('app.cookiePath'), md5($this->authPhone));
        $this->authenticated = file_exists($this->cookieFile);
        $this->jar = new FileCookieJar($this->cookieFile);

        // setup default client
        $this->client = new Client([
            'base_uri' => 'https://m.vk.com',
            'cookies' => true,
            'defaults' => [
                'headers' => [
                    'User-agent' => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.95 Safari/537.36'
                ]
            ]
        ]);
    }

    // Data & parse related functions

    /**
     * Searches audios from request query, with caching
     * @return array
     */
    public function search()
    {
        $cacheKey = $this->getCacheKeyForRequest();

        if ($this->hasRequestInCache()) {
            return $this->ok(
                $this->transformSearchResponse(
                    Cache::get($cacheKey)
                )
            );
        }

        // if cookie file doesn't exist, we need to authenticate first
        if (!$this->authenticated) {
            $this->auth();
            $this->authenticated = true;
        }

        // get inputs
        $query = trim($this->request->get('q'));
        $offset = intval($this->request->get('page')) * 50; // calculate offset from page index

        // send request
        $response = $this->getSearchResults($query, $offset);

        // check for security checks
        $this->authSecurityCheck($response);

        // if not authenticated, authenticate then retry the search
        if (!$this->checkIsAuthenticated($response)) {
            // we need to get out of the loop. maybe something is wrong authentication.
            if ($this->authRetries >= 3) {
                abort(403);
            }
            $this->auth();
            return $this->search();
        }

        $result = array($this->parseAudioItems($response));

        // get more pages if needed
        for ($i = 1; $i < config('app.search.pageMultiplier'); $i++) {
            // increment offset
            $offset += 50;
            // get result and parse it
            $resultData = $this->parseAudioItems($this->getSearchResults($query, $offset));

            //  we can't request more pages if result is empty, break the loop
            if (empty($resultData)) {
                break;
            }

            array_push($result, $resultData);
        }

        // store in cache
        Cache::put($cacheKey, $result, config('app.cache.duration'));

        // parse data, save in cache, and response
        return $this->ok($this->transformSearchResponse(
            $result
        ));
    }

    /**
     * Request search page
     * @param $query
     * @param $offset
     * @return ResponseInterface
     */
    private function getSearchResults($query, $offset)
    {
        return $this->client->get(
            "audio?act=search&q=$query&offset=$offset",
            ['cookies' => $this->jar]
        );
    }

    /**
     * Parses response html for audio items, saves it in cache and returns parsed array
     * @param ResponseInterface $response
     * @return array
     */
    private function parseAudioItems($response)
    {
        $dom = new Dom;
        $dom->load((string)$response->getBody());

        $items = $dom->find('.audio_item');
        $data = array();

        foreach ($items as $item) {
            $audio = new Dom();
            $audio->load($item->innerHtml);

            $artist = $audio->find('.ai_artist')->text(true);
            $title = $audio->find('.ai_title')->text(true);
            $duration = $audio->find('.ai_dur')->getAttribute('data-dur');
            $mp3 = $audio->find('input[type=hidden]')->value;
            $id = hash(config('app.hash.id'), $mp3);

            array_push($data, [
                'id' => $id,
                'artist' => trim(htmlspecialchars_decode($artist, ENT_QUOTES)),
                'title' => trim(htmlspecialchars_decode($title, ENT_QUOTES)),
                'duration' => (int)$duration,
                'mp3' => $mp3
            ]);
        }

        return $data;
    }

    /**
     * Serves given audio item or aborts with 404 if not found
     * @param string $key
     * @param string $id
     * @param bool $stream
     * @param int $bitrate
     * @return mixed
     */
    public function download($key, $id, $stream = false, $bitrate = -1)
    {
        if (!in_array($bitrate, config('app.allowed_bitrates'))) {
            $bitrate = -1;
        }

        $item = $this->getAudio($key, $id);

        $name = sprintf('%s - %s', $item['artist'], $item['title']); // format
        $name = Utils::sanitize($name, false, false); // remove bad characters
        $name = sprintf('%s.mp3', $name); // append extension

        $filePath = sprintf('%s.mp3', hash(config('app.hash.mp3'), $item['id']));

        $path = sprintf('%s/%s', config('app.mp3Path'), $filePath);

        if (file_exists($path) || $this->downloadFile($item['mp3'], $path)) {
            if ($bitrate > 0) {
                $pathConverted = str_replace('.mp3', "_$bitrate.mp3", $path);
                $filePathConverted = str_replace('.mp3', "_$bitrate.mp3", $filePath);

                // change path only if already converted or conversion function returns true
                if (file_exists($filePathConverted) || $this->convertMp3Bitrate($bitrate, $path,
                        $pathConverted)
                ) {
                    //change file path to converted one.
                    $path = $pathConverted;
                    $filePath = $filePathConverted;
                }
            }

            if ($stream) {
                return redirect("mp3/$filePath");
            } else {
                return $this->downloadResponse($path, $name);
            }
        } else {
            abort(404);
        }
    }

    /**
     * Just like download but with stream enabled
     * @param $key
     * @param $id
     * @return mixed
     */
    public function stream($key, $id)
    {
        return $this->download($key, $id, true);
    }

    /**
     * Just like download but with bitrate converting enabled
     * @param $key
     * @param $id
     * @return mixed
     */
    public function bitrateDownload($key, $id, $bitrate)
    {
        return $this->download($key, $id, false, $bitrate);
    }

    /**
     * Get size of audio file in bytes
     * @param $key
     * @param $id
     * @return mixed
     */
    public function bytes($key, $id)
    {
        // get from cache or store to cache and return value
        return Cache::remember($id . $key, config('app.cache.duration'),
            function () use ($key, $id) {
                $item = $this->getAudio($key, $id);

                $response = $this->client->head($item['mp3']);
                return $response->getHeader('Content-Length')[0];
            });
    }

    /**
     * Get audio item from cache or abort with 404 if not found
     * @param string $key
     * @param string $id
     * @return mixed
     */
    public function getAudio($key, $id)
    {
        // get search cache instance
        $data = Cache::get($key);

        if (is_null($data)) {
            abort(404);
        }

        // search audio by audio id/hash
        $key = array_search($id, array_column($data, 'id'));

        if ($key === false) {
            abort(404);
        }

        return $data[$key];
    }

    // Auth related functions

    /**
     * Checks whether response page has authenticated user data
     * @param ResponseInterface $response
     * @return boolean
     */
    private function checkIsAuthenticated($response)
    {
        $body = (string)$response->getBody();

        return str_contains($body, 'https://login.vk.com/?act=logout');
    }

    /**
     * Checks whether response page has security check form
     * @param ResponseInterface $response
     * @return array
     */
    private function checkIsSecurityCheck($response)
    {
        $body = (string)$response->getBody();

        return str_contains($body, 'login.php?act=security_check');
    }

    /**
     * Login to the site
     */
    private function auth()
    {
        $this->authRetries++;
        $loginResponse = $this->client->get('login', ['cookies' => $this->jar]);

        $authUrl = $this->getFormUrl($loginResponse);

        $this->client->post($authUrl, [
            'cookies' => $this->jar,
            'form_params' => [
                'email' => $this->authPhone,
                'pass' => $this->authPassword
            ]
        ]);
    }

    /**
     * Completes VK security check with current credentials if response has security check form
     * Has side effects
     * @param ResponseInterface $response
     */
    private function authSecurityCheck($response)
    {
        if (!$this->checkIsSecurityCheck($response)) {
            return;
        }
        $body = $response->getBody();

        // for now we can handle only phone number security checks
        if (str_contains($body, "все недостающие цифры номера")) {
            $dom = new Dom;
            $dom->load($body);
            $prefixes = $dom->find('.field_prefix');

            $leftPrefixCount = strlen($prefixes[0]->text) - 1; // length country code without plus: +7(1), +33(2), +993(3).
            $rightPrefixCount = strlen(filter_var($prefixes[1]->text,
                FILTER_SANITIZE_NUMBER_INT)); // just filter out numbers and count

            // code is 'middle' of the phone number
            $securityCode = substr($this->authPhone, $leftPrefixCount, -$rightPrefixCount);
        }

        if (isset($securityCode)) {
            $formUrl = $this->getFormUrl($response);

            $this->client->post($formUrl, [
                'cookies' => $this->jar,
                'form_params' => [
                    'code' => $securityCode,
                ]
            ]);
        } else {
            abort(403);
        }
    }

    /**
     * Get form action url from response
     * @param ResponseInterface $response
     * @return string
     */
    private function getFormUrl($response)
    {
        if (preg_match('/<form method="post" action="([^"]+)"/Us', $response->getBody(),
            $match)) {
            return $match[1];
        } else {
            return null;
        }
    }

    // Cache & hash related functions

    /**
     * Get current request cache key
     * @return string
     */
    private function getCacheKeyForRequest()
    {
        $q = $this->request->get('q');
        $page = $this->request->get('page');

        return hash(config('app.hash.cache'), ($q . $page));
    }

    /**
     * Whether current request is already cached
     * @return mixed
     */
    private function hasRequestInCache()
    {
        return Cache::has($this->getCacheKeyForRequest());
    }

    // Response related functions

    /**
     * Cleanup data for response
     *
     * @param $data
     * @return array
     */
    private function transformSearchResponse($data)
    {
        $cacheKey = $this->getCacheKeyForRequest();
        return array_map(function ($item) use ($cacheKey) {

            $downloadUrl = Utils::url(sprintf('%s/%s', $cacheKey, $item['id']));
            $streamUrl = Utils::url(sprintf('stream/%s/%s', $cacheKey, $item['id']));

            // remove mp3 link and id from array.
            unset($item['mp3']);
            unset($item['id']);

            return array_merge($item, [
                'download' => $downloadUrl,
                'stream' => $streamUrl
            ]);
        }, $data);
    }

    /**
     * @param $data
     * @param string $arrayName
     * @return array
     */
    private function ok($data, $arrayName = 'data', $headers = [])
    {
        return response()->json([
            'status' => 'ok',
            $arrayName => $data
        ], 200, ['Access-Control-Allow-Origin' => '*']);
    }

    // File manipulation related functions

    /**
     * Download given file url to given path
     * @param string $url
     * @param string $path
     * @return bool true if succeeds
     */
    function downloadFile($url, $path)
    {
        $handle = fopen($path, 'x');
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FILE, $handle);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_exec($curl);

        // if curl had errors
        if (curl_errno($curl) > 0) {
            return false;
        }

        //close files
        curl_close($curl);
        fclose($handle);

        return true;
    }

    /**
     * VK sometimes redirects to error page, but curl downloads redirect page, which is html.
     * check if downloaded file is html and throw 404
     * if mp3 size smaller than 170 bytes (default/expected size is 158) check is html and exit with 404
     * @param string $path full path of mp3
     */
    function checkIsBadMp3($path)
    {
        $size = filesize($path);

        if ($size < 170) {
            $content = file_get_contents($path);

            if (str_contains($content, "<html>")) {
                unlink($path);
                abort(404);
            }
        }
    }

    /**
     * Force download given file with given name
     * @param $path string path of the file
     * @param $name string name of the downloading file
     * @return ResponseFactory
     */
    function downloadResponse($path, $name)
    {
        if (!file_exists($path)) {
            abort(404);
        }

        $this->checkIsBadMp3($path);

        $headers = [
            'Cache-Control' => 'private',
            'Cache-Description' => 'File Transfer',
            'Content-Type' => 'audio/mpeg',
            'Content-Length' => filesize($path),
        ];

        return response()->download(
            $path,
            $name,
            $headers
        );
    }

    /**
     * Executes ffmpeg command synchronously for converting given file to given bitrate
     * @param $bitrate integer, one of $config["allowed_bitrates"]
     * @param $input string input mp3 file full path
     * @param $output string output mp3 file full path
     * @return bool is success
     */
    function convertMp3Bitrate($bitrate, $input, $output)
    {
        $bitrateString = config('app.allowed_bitrates_ffmpeg')[array_search($bitrate,
            config('app.allowed_bitrates'))];
        $ffmpegPath = config('app.ffmpeg_path');

        exec("$ffmpegPath -i $input -codec:a libmp3lame $bitrateString $output", $exOutput,
            $result);

        return $result == 0;
    }
}
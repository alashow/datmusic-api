<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Controllers;

use Aws\S3\S3Client;
use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Illuminate\Contracts\Routing\ResponseFactory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
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
     * @var S3Client Amazon S3 client
     */
    protected $s3Client;
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

        $this->cookieFile = sprintf(config('app.paths.cookie'), md5($this->authPhone));
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

        if (config('app.aws.enabled')) {
            $this->s3Client = new S3Client(config('app.aws.config'));
            $this->s3Client->registerStreamWrapper();
        }
    }

    // Route & parse related functions

    /**
     * Just response status
     * @return array
     */
    public function index()
    {
        return $this->ok();
    }

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
        $offset = abs(intval($this->request->get('page'))) * 50; // calculate offset from page index

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

        $result = $this->parseAudioItems($response);

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

            $result = array_merge($result, $resultData);
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

            $id = explode('_search-', $item->getAttribute('data-id'))[0];
            $artist = $audio->find('.ai_artist')->text(true);
            $title = $audio->find('.ai_title')->text(true);
            $duration = $audio->find('.ai_dur')->getAttribute('data-dur');
            $mp3 = $audio->find('input[type=hidden]')->value;

            $hash = hash(config('app.hash.id'), $id);

            array_push($data, [
                'id' => $hash,
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
        // is using s3 as storage
        $isS3 = !is_null($this->s3Client);

        if (!in_array($bitrate, config('app.conversion.allowed'))) {
            $bitrate = -1;
        }

        // filename including extension
        $filePath = sprintf('%s.mp3', hash(config('app.hash.mp3'), $id));
        // used for downloading from s3 when bitrate converting
        $localPath = sprintf('%s/%s', config('app.paths.mp3'), $filePath);

        // build full path from file path
        if ($isS3) {
            $s3PathWithFolder = sprintf(config('app.aws.paths.mp3'), $filePath);
            $path = sprintf('s3://%s/%s', config('app.aws.bucket'), $s3PathWithFolder);
        } else {
            $path = $localPath;
        }

        // cache check only for s3.
        // check bucket for file and redirect if exists
        if ($isS3 && @file_exists(Utils::formatPathWithBitrate($path, $bitrate))) {
            return redirect(Utils::buildS3Url(Utils::formatPathWithBitrate($filePath, $bitrate)));
        }

        $item = $this->getAudio($key, $id);

        $name = sprintf('%s - %s', $item['artist'], $item['title']); // format
        // ascii-fy and remove bad characters
        $name = Str::ascii($name);
        $name = Utils::sanitize($name, false, false);
        $name = sprintf('%s.mp3', $name); // append extension

        if ($isS3) {
            $streamContext = Utils::buildS3StreamContextOptions($name);
        } else {
            $streamContext = null;
        }

        if (@file_exists($path) || $this->downloadFile($item['mp3'], $path, $streamContext)) {

            //TODO: make bitrate conversion function separate
            if ($bitrate > 0) {
                // Download to local if s3 mode and upload converted one to s3
                // Change path only if already converted or conversion function returns true

                $pathConverted = Utils::formatPathWithBitrate($localPath, $bitrate);
                $filePathConverted = Utils::formatPathWithBitrate($filePath, $bitrate);

                // s3 mode
                if ($isS3) {
                    // download file from s3 to local
                    // continue only if download succeeds
                    $convertable = $this->downloadFile(Utils::buildS3Url($filePath), $localPath);
                } else {
                    $convertable = true;
                }

                if ($convertable) {
                    if (file_exists($pathConverted)
                        || $this->convertMp3Bitrate($bitrate, $localPath, $pathConverted)
                    ) {
                        // upload converted file
                        if ($isS3) {
                            $converted = fopen($pathConverted, 'r');
                            $s3ConvertedPath = Utils::formatPathWithBitrate($path, $bitrate);
                            $s3Stream = fopen($s3ConvertedPath, 'w', false, $streamContext);

                            // if upload succeeds
                            if (stream_copy_to_stream($converted, $s3Stream) != false) {
                                $filePath = $filePathConverted;
                            }
                        } else {
                            //change file paths to converted ones
                            $path = $pathConverted;
                            $filePath = $filePathConverted;
                        }
                    }
                }
            }

            if ($isS3) {
                return redirect(Utils::buildS3Url($filePath));
            } else {
                if ($stream) {
                    return redirect("mp3/$filePath");
                } else {
                    return $this->downloadResponse($path, $name);
                }
            }
        } else {
            abort(404);
        }
    }

    /**
     * Just like download but with stream enabled
     * @param string $key
     * @param string $id
     * @return mixed
     */
    public function stream($key, $id)
    {
        return $this->download($key, $id, true);
    }

    /**
     * Just like download but with bitrate converting enabled
     * @param string $key
     * @param string $id
     * @param int $bitrate
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
        $cacheKey = "bytes_$id";

        // get from cache or store in cache and return value
        return Cache::rememberForever($cacheKey, function () use ($key, $id) {
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
        $q = strtolower($this->request->get('q'));
        $page = abs(intval($this->request->get('page')));

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
     * @param array $strings items need to be tested
     * @return bool true if any of inputs is bad match
     */
    private function isBadMatch(array $strings)
    {
        foreach ($strings as $string) {
            if (preg_match_all(config('app.search.sortRegex'), $string) == 1) {
                return true;
            }
        }
        return false;
    }

    /**
     * Cleanup data for response
     *
     * @param $data
     * @return array
     */
    private function transformSearchResponse($data)
    {
        // if query matches sort regex, we shouldn't sort
        $query = $this->request->get('q');
        $sortable = $this->isBadMatch([$query]) == false;

        // items that needs to sorted to the end of response list if matches the regex
        $badMatches = array();

        $cacheKey = $this->getCacheKeyForRequest();
        $mapped = array_map(function ($item) use (&$cacheKey, &$badMatches, &$sortable) {
            $downloadUrl = Utils::url(sprintf('%s/%s', $cacheKey, $item['id']));
            $streamUrl = Utils::url(sprintf('stream/%s/%s', $cacheKey, $item['id']));

            // remove mp3 link and id from array
            unset($item['mp3']);
            unset($item['id']);

            $result = array_merge($item, [
                'download' => $downloadUrl,
                'stream' => $streamUrl
            ]);

            // is audio name bad match
            $badMatch = $sortable && $this->isBadMatch([$item['artist'], $item['title']]);

            // add to bad matches
            if ($badMatch) {
                array_push($badMatches, $result);
            }

            // remove from main array if bad match
            return $badMatch ? null : $result;
        }, $data);

        // remove null items from mapped (nulls are added to badMatches, emptied in mapping above)
        $mapped = array_filter($mapped);

        // if there was any bad matches, merge with base list or just return
        return empty($badMatches) ? $mapped : array_merge($mapped, $badMatches);
    }

    /**
     * @param $data
     * @param string $arrayName
     * @param int $status
     * @param $headers
     * @return array
     */
    private function ok($data = null, $arrayName = 'data', $status = 200, $headers = [])
    {
        $result = ['status' => 'ok'];
        if (!is_null($data)) {
            $result = array_merge($result, [$arrayName => $data]);
        }

        return response()->json($result, $status, $headers);
    }

    // File manipulation related functions

    /**
     * Download given file url to given path
     * @param string $url
     * @param string $path
     * @param resource $context stream context options when opening $path
     * @return bool true if succeeds
     */
    function downloadFile($url, $path, $context = null)
    {
        if ($context == null) {
            $handle = fopen($path, 'w');
        } else {
            $handle = fopen($path, 'w', false, $context);
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FILE, $handle);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, config('app.downloading.timeout.connection'));
        curl_setopt($curl, CURLOPT_TIMEOUT, config('app.downloading.timeout.execution'));
        curl_exec($curl);

        // if curl had errors
        if (curl_errno($curl) > 0) {
            // remove the file just in case
            @unlink($path);
            return false;
        }

        //close files
        curl_close($curl);
        fclose($handle);

        return true;
    }

    /**
     * Checks given files mime type and abort with 404 if file is not an mp3 file
     * @param string $path full path of mp3
     */
    function checkIsBadMp3($path)
    {
        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);

        // if the file is corrupted (mime is wrong), delete it from cache and return 404
        if ($mime !== 'audio/mpeg') {
            @unlink($path);
            abort(404);
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
        $bitrateString = config('app.conversion.allowed_ffmpeg')[array_search($bitrate,
            config('app.conversion.allowed'))];
        $ffmpegPath = config('app.conversion.ffmpeg_path');

        exec("$ffmpegPath -i $input -codec:a libmp3lame $bitrateString $output", $exOutput,
            $result);

        return $result == 0;
    }
}
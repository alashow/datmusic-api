<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use stdClass;

trait SearchesTrait
{
    use CachesTrait, ParserTrait, AlbumArtistSearchesTrait;

    protected $audioKeyId = 'audio';
    private $count = 200;
    private $accessTokenIndex = 0;

    /**
     * SearchesTrait constructor.
     */
    public function bootSearches()
    {
        $this->accessTokenIndex = array_rand(config('app.auth.tokens'));
    }

    /**
     * Searches audios from request query, with caching.
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function search(Request $request)
    {
        // get inputs
        $query = getQuery($request);
        $offset = getPage($request) * $this->count; // calculate offset from page index

        if (Str::startsWith($query, $this->artistsSearchPrefix)) {
            $response = $this->audiosByArtistName($request, $query);
        }
        if (Str::startsWith($query, $this->albumSearchPrefix)) {
            $response = $this->audiosByAlbumName($request, $query);
        }
        if (Str::startsWith($query, $this->albumsSearchPrefix)) {
            $response = $this->audiosByAlbumNameMultiple($request, $query);
        }

        if (isset($response) && $response) {
            return $response;
        }

        $cacheKey = $this->getCacheKey($request);

        // return immediately if has in cache
        $cachedResult = $this->getCache($cacheKey);
        if (! is_null($cachedResult)) {
            logger()->searchCache($query, $offset);

            return $this->ok($this->transformAudioResponse($request, $cacheKey, $cachedResult));
        }

        $response = $this->getSearchResults($request, $query, $offset);
        $error = $this->checkForErrors($request, $response);
        if ($error) {
            return $error;
        }

        // parse then store in cache
        $result = $this->parseAudioItems($response);
        $this->cacheResult($cacheKey, $result);
        logger()->search($query, $offset);

        // parse data, save in cache, and response
        return $this->ok($this->transformAudioResponse($request, $cacheKey, $result));
    }

    /**
     * Request search page.
     *
     * @param Request $request
     * @param string  $query
     * @param int     $offset
     *
     * @return stdClass
     */
    private function getSearchResults(Request $request, string $query, int $offset)
    {
        if (empty($query)) {
            $query = randomArtist();
        }

        $captchaParams = $this->getCaptchaParams($request);
        $params = [
            'access_token' => config('app.auth.tokens')[$this->accessTokenIndex],
            'q'            => $query,
            'offset'       => $offset,
            'count'        => $this->count,
        ];

        return as_json(httpClient()->get('method/audio.search', [
            'query' => $params + $captchaParams,
        ]
        ));
    }

    /**
     * Get captcha inputs from given request.
     *
     * @param Request $request
     *
     * @return array extra params array to send to solve captcha if there's a key in request or empty array
     */
    protected function getCaptchaParams(Request $request)
    {
        if ($request->has('captcha_key')) {
            $captchaParams = [
                'captcha_sid' => $request->get('captcha_id'),
                'captcha_key' => $request->get('captcha_key'),
            ];
            $this->accessTokenIndex = min(intval($request->get('captcha_index', 0)), $this->accessTokenIndex);

            return $captchaParams;
        } else {
            return [];
        }
    }

    /**
     * @param Request $request
     * @param stdClass $response
     *
     * @return bool|JsonResponse
     */
    protected function checkForErrors(Request $request, stdClass $response)
    {
        if ($request->has('captcha_key')) {
            reportCaptchaSolved($request);
        }

        if (property_exists($response, 'error')) {
            $error = $response->error;
            $errorData = [
                'message' => $error->error_msg,
                'code'    => $error->error_code,
            ];
            if ($error->error_code == 14) {
                $captcha = [
                    'captcha_index' => $this->accessTokenIndex,
                    'captcha_id'    => intval($error->captcha_sid),
                    'captcha_img'   => $error->captcha_img,
                ];
                reportCaptchaError($request, $captcha, $error);
                return $this->error($errorData + $captcha);
            } else {
                return $this->error($errorData);
            }
        } else {
            return false;
        }
    }

    /**
     * Cleanup data for response.
     *
     * @param Request $request
     * @param string  $cacheKey
     * @param array   $data
     * @param bool    $sort
     *
     * @return array
     */
    private function transformAudioResponse(Request $request, string $cacheKey, array $data, bool $sort = true)
    {
        // if query matches sort regex, we shouldn't sort
        $query = $request->get('q');
        $sortable = $sort && $this->isBadMatch([$query]) == false;

        // items that needs to sorted to the end of response list if matches the regex
        $badMatches = [];

        $mapped = array_map(function ($item) use (&$cacheKey, &$badMatches, &$sortable) {
            $downloadUrl = fullUrl(sprintf('dl/%s/%s', $cacheKey, $item['id']));
            $streamUrl = fullUrl(sprintf('stream/%s/%s', $cacheKey, $item['id']));
            $coverUrl = fullUrl(sprintf('cover/%s/%s', $cacheKey, $item['id']));

            // we don't wanna share original mp3 urls
            unset($item['mp3']);

            $result = array_merge($item, [
                'key'      => $cacheKey,
                'artist'   => $this->cleanBadWords($item['artist']),
                'title'    => $this->cleanBadWords($item['title']),
                'download' => $downloadUrl,
                'stream'   => $streamUrl,
                'cover'    => $coverUrl,
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
     * @param array $strings items need to be tested
     *
     * @return bool true if any of inputs is bad match
     */
    private function isBadMatch(array $strings)
    {
        // if audio name is too long, consider it bad match.
        if (strlen(implode($strings)) > 100) {
            return true;
        }

        foreach ($strings as $string) {
            if (preg_match_all(config('app.search.sortRegex'), $string) == 1) {
                return true;
            }
        }

        return false;
    }

    /**
     * Replace bad words with empty string.
     *
     * @param string $text
     *
     * @return string sanitized string
     */
    private function cleanBadWords(string $text)
    {
        return preg_replace(config('app.search.badWordsRegex'), '', $text);
    }

    /**
     * Standard audio response with optional caching for each audio item.
     *
     * @param Request $request
     * @param array   $data
     * @param bool    $cache
     *
     * @return JsonResponse
     */
    protected function audiosResponse(Request $request, array $data, bool $cache = true)
    {
        if ($cache) {
            foreach ($data as $audio) {
                $this->cacheAudioItem($audio['id'], $audio, true);
            }
        }

        return $this->ok($this->transformAudioResponse($request, $this->audioKeyId, $data, false));
    }
}

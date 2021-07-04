<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use stdClass;

trait SearchesTrait
{
    use CachesTrait, ParserTrait, AlbumArtistSearchesTrait, MultisearchTrait;

    public static $SEARCH_BACKEND_AUDIOS = 'audios';
    public static $SEARCH_BACKEND_ALBUMS = 'albums';
    public static $SEARCH_BACKEND_ARTISTS = 'artists';
    public static $SEARCH_BACKEND_TYPES = ['audios', 'albums', 'artists'];

    private $count = 200;
    private $accessTokenIndex = 0;

    /**
     * SearchesTrait constructor.
     */
    public function bootSearches()
    {
        $tokens = config('app.auth.tokens');

        if (config('app.captcha_lock.weighted_tokens_enabled')) {
            $tokenWeights = array_map(function ($index, $token) {
                return [$index => $this->isCaptchaLocked($index) ? config('app.captcha_lock.locked_token_weight') : config('app.captcha_lock.unlocked_token_weight')];
            }, range(0, count($tokens) - 1), $tokens);
            $this->accessTokenIndex = getRandomWeightedElement(collect($tokenWeights)->flatten()->toArray());
        } else {
            $this->accessTokenIndex = array_rand(config('app.auth.tokens'));
        }
    }

    /**
     * Searches audios from request query, with caching.
     *
     * @param Request $request
     *
     * @return JsonResponse|array
     */
    public function search(Request $request)
    {
        // get inputs
        $query = getQuery($request);
        $offset = getPage($request) * $this->count; // calculate offset from page index

        $cacheKey = $this->getCacheKey($request);
        $cachedResult = $this->getCache($cacheKey);
        $isCachedQuery = ! is_null($cachedResult);

        if (! $isCachedQuery && ! $request->has('captcha_key') && $this->isCaptchaLocked($this->accessTokenIndex)) {
            $captchaError = $this->getCaptchaLockError($this->accessTokenIndex);
            logger()->captchaLockedQuery($this->accessTokenIndex, $query, $captchaError['captcha_id']);

            return errorResponse($captchaError);
        }

        // return immediately if has in cache
        if ($isCachedQuery) {
            logger()->searchCache($query, $offset, 'count='.count($cachedResult));

            return $this->cleanAudioList($request, $cacheKey, $cachedResult);
        }

        $response = $this->getSearchResults($request, $query, $offset);
        $error = $this->checkSearchResponseError($request, $response);
        if ($error) {
            return $error;
        }

        // parse then store in cache
        $data = $this->parseAudioItems($response);
        $this->cacheResult($cacheKey, $data);
        logger()->search($query, $offset, 'Account#'.$this->accessTokenIndex, 'count='.count($data));

        return $this->cleanAudioList($request, $cacheKey, $data);
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

        return as_json(vkClient()->get('method/audio.search', [
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
     * @param Request  $request
     * @param stdClass $response
     *
     * @return bool|JsonResponse
     */
    protected function checkSearchResponseError(Request $request, stdClass $response)
    {
        $hasCaptchaKey = $request->has('captcha_key');
        if (property_exists($response, 'error')) {
            $error = $response->error;
            $errorData = [
                'message' => $error->error_msg,
                'code'    => $error->error_code,
            ];
            if ($error->error_code == 14) {
                $captcha = [
                    'id' => 'captcha',
                    'captcha_index' => $this->accessTokenIndex,
                    'captcha_id'    => intval($error->captcha_sid),
                    'captcha_img'   => $error->captcha_img,
                ];
                $errorData = $errorData + $captcha;
                $this->captchaLock($this->accessTokenIndex, $errorData);
                reportCaptchaLock($request, $captcha, $error);

                if ($hasCaptchaKey) {
                    $this->captchaFailedAttempt($request);
                }
            }

            return errorResponse($errorData);
        } else {
            if ($hasCaptchaKey && $this->isCaptchaLocked($this->accessTokenIndex)) {
                $this->releaseCaptchaLock($this->accessTokenIndex);
                reportCaptchaLockRelease($request);
            }

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
    private function cleanAudioList(Request $request, string $cacheKey, array $data, bool $sort = true)
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

        return okResponse($this->cleanAudioList($request, self::$audioKeyId, $data, false), self::$SEARCH_BACKEND_AUDIOS);
    }
}

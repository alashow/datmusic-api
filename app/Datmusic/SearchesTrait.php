<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

trait SearchesTrait
{
    use CachesTrait, ParserTrait;

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
        $query = trim($request->get('q'));
        $offset = abs(intval($request->get('page'))) * $this->count; // calculate offset from page index

        $cacheKey = $this->getCacheKey($request);

        // return immediately if has in cache
        $cachedResult = $this->getSearchResult($request);
        if (! is_null($cachedResult)) {
            logger()->searchCache($query, $offset);

            return $this->ok($this->transformSearchResponse($request, $cachedResult));
        }

        // send request
        $response = $this->getSearchResults($request, $query, $offset);

        $error = $this->checkForErrors($response);
        if ($error) {
            return $error;
        }

        $result = $this->getAudioItems($response);

        // store in cache
        $this->cacheSearchResult($cacheKey, $result);

        logger()->search($query, $offset);

        // parse data, save in cache, and response
        return $this->ok($this->transformSearchResponse(
            $request,
            $result
        ));
    }

    /**
     * Request search page.
     *
     * @param Request $request
     * @param string  $query
     * @param int     $offset
     *
     * @return \stdClass
     */
    private function getSearchResults($request, $query, $offset)
    {
        if (empty($query)) {
            $query = randomArtist();
        }

        $captchaParams = [];
        if ($request->has('captcha_key')) {
            $captchaParams = [
                'captcha_sid' => $request->get('captcha_id'),
                'captcha_key' => $request->get('captcha_key'),
            ];
            $this->accessTokenIndex = min(intval($request->get('captcha_index', 0)), $this->accessTokenIndex);
        }

        $params = [
            'access_token' => config('app.auth.tokens')[$this->accessTokenIndex],
            'q'            => $query,
            'offset'       => $offset,
            'sort'         => 2,
            'count'        => $this->count,
        ];

        return as_json(httpClient()->get('method/audio.search', [
                'query' => $params + $captchaParams,
            ]
        ));
    }

    /**
     * @param \stdClass $response
     *
     * @return bool|JsonResponse
     */
    private function checkForErrors($response)
    {
        if (property_exists($response, 'error')) {
            $error = $response->error;
            $errorData = [
                'message' => $error->error_msg,
                'code'    => $error->error_code,
            ];
            if ($error->error_code == 14) {
                return $this->error($errorData + [
                        'captcha_index' => $this->accessTokenIndex,
                        'captcha_id'    => intval($error->captcha_sid),
                        'captcha_img'   => $error->captcha_img,
                    ]);
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
     * @param array   $data
     *
     * @return array
     */
    private function transformSearchResponse($request, $data)
    {
        // if query matches sort regex, we shouldn't sort
        $query = $request->get('q');
        $sortable = $this->isBadMatch([$query]) == false;

        // items that needs to sorted to the end of response list if matches the regex
        $badMatches = [];

        $cacheKey = $this->getCacheKey($request);
        $mapped = array_map(function ($item) use (&$cacheKey, &$badMatches, &$sortable) {
            $downloadUrl = fullUrl(sprintf('dl/%s/%s', $cacheKey, $item['id']));
            $streamUrl = fullUrl(sprintf('stream/%s/%s', $cacheKey, $item['id']));

            $item['artist'] = $this->cleanBadWords($item['artist']);
            $item['title'] = $this->cleanBadWords($item['title']);

            // remove mp3 link and id from array
            unset($item['mp3']);
            unset($item['id']);

            $result = array_merge($item, [
                'download' => $downloadUrl,
                'stream'   => $streamUrl,
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
     * @param $string
     *
     * @return string clean string
     */
    private function cleanBadWords($string)
    {
        return preg_replace(config('app.search.badWordsRegex'), '', $string);
    }
}

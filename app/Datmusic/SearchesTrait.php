<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use GuzzleHttp\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Psr\Http\Message\ResponseInterface;
use Utils;

trait SearchesTrait
{
    use CachesTrait, AuthenticatorTrait, ParserTrait;

    /**
     * @var Client Guzzle client
     */
    protected $httpClient;

    /**
     * SearchesTrait constructor.
     */
    public function bootSearches()
    {
        $this->bootAuthenticator();
    }

    /**
     * Searches audios from request query, with caching
     * @param Request $request
     * @return array
     */
    public function search(Request $request)
    {
        $cacheKey = $this->getCacheKey($request);

        // return immediately if has in cache
        if ($this->hasRequestInCache($request)) {
            return $this->ok(
                $this->transformSearchResponse(
                    $request,
                    Cache::get($cacheKey)
                )
            );
        }

        // if the cookie file doesn't exist, we need to authenticate first
        if (!$this->authenticated) {
            $this->auth();
            $this->authenticated = true;
        }

        // get inputs
        $query = trim($request->get('q'));
        $offset = abs(intval($request->get('page'))) * 50; // calculate offset from page index

        // send request
        $response = $this->getSearchResults($query, $offset);

        // check for security checks
        $this->authSecurityCheck($response);

        // if not authenticated, authenticate then retry the search
        if (!$this->checkIsAuthenticated($response)) {
            // we need to get out of the loop. maybe something is wrong with authentication.
            if ($this->authRetries >= 3) {
                abort(403);
            }
            $this->auth();
            return $this->search($request);
        }

        $result = $this->getAudioItems($response);

        // get more pages if needed
        for ($i = 1; $i < config('app.search.pageMultiplier'); $i++) {
            // increment offset
            $offset += 50;
            // get result and parse it
            $resultData = $this->getAudioItems($this->getSearchResults($query, $offset));

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
            $request,
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
        $query = urlencode($query);
        return $this->httpClient->get(
            "audio?act=search&q=$query&offset=$offset",
            ['cookies' => $this->jar]
        );
    }

    /**
     * Cleanup data for response
     *
     * @param Request $request
     * @param array $data
     * @return array
     */
    private function transformSearchResponse($request, $data)
    {
        // if query matches sort regex, we shouldn't sort
        $query = $request->get('q');
        $sortable = $this->isBadMatch([$query]) == false;

        // items that needs to sorted to the end of response list if matches the regex
        $badMatches = array();

        $cacheKey = $this->getCacheKey($request);
        $mapped = array_map(function ($item) use (&$cacheKey, &$badMatches, &$sortable) {
            $downloadUrl = Utils::url(sprintf('%s/%s', $cacheKey, $item['id']));
            $streamUrl = Utils::url(sprintf('stream/%s/%s', $cacheKey, $item['id']));

            $item['artist'] = $this->cleanBadWords($item['artist']);
            $item['title'] = $this->cleanBadWords($item['title']);

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
     * Replace bad words with empty string
     * @param $string
     * @return mixed
     */
    private function cleanBadWords($string)
    {
        return preg_replace(config('app.search.badWordsRegex'), '', $string);
    }
}
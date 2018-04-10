<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Psr\Http\Message\ResponseInterface;

trait SearchesTrait
{
    use CachesTrait, ParserTrait;

    /**
     * SearchesTrait constructor.
     */
    public function bootSearches()
    {
        //no-op
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
        $offset = abs(intval($request->get('page'))) * 50; // calculate offset from page index

        $cacheKey = $this->getCacheKey($request);

        // return immediately if has in cache
        $cachedResult = $this->getSearchResult($request);
        if (! is_null($cachedResult)) {
            logger()->searchCache($query, $offset);

            return $this->ok($this->transformSearchResponse($request, $cachedResult));
        }

        // send request
        $response = $this->getSearchResults($query, $offset);

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
     * @param $query
     * @param $offset
     *
     * @return ResponseInterface
     */
    private function getSearchResults($query, $offset)
    {
        if (empty($query)) {
            $query = randomArtist();
        }

        $query = urlencode($query);

        return httpClient()->get('api.php', [
                'query' => [
                    'key'    => config('app.auth.ya_key'),
                    'method' => 'search',
                    'q'      => $query,
                    'offset' => $offset,
                ],
            ]
        );
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

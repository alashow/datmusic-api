<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

trait ParserTrait
{
    /**
     * Maps response to audio items.
     *
     * @param \stdClass $response
     *
     * @return array
     */
    public function getAudioItems($response)
    {
        $audios = $response->response->items;

        $data = [];
        foreach ($audios as $item) {
            if (isset($item->content_restricted) && empty($item->url)) {
                \Log::notice('Audio item restricted, skipping it', [$item]);
                continue;
            }

            $id = $item->id;
            $userId = $item->owner_id;
            $sourceId = sprintf('%s_%s', $userId, $id);
            $artist = $item->artist;
            $title = $item->title;
            $duration = $item->duration;
            $date = $item->date;
            $mp3 = $item->url;

            $hlsReg = '/(\/[a-zA-Z0-9]{1,30})(\/audios)?\/([a-zA-Z0-9]{1,30})(\/index\.m3u8)/';
            preg_match($hlsReg, $mp3, $matches);
            if (array_key_exists(4, $matches)) {
                $mp3 = str_replace($matches[1], '', $mp3);
                $mp3 = str_replace($matches[4], '.mp3', $mp3);
            }

            $hash = hash(config('app.hash.id'), $sourceId);

            $itemData = [
                'id'        => $hash,
                'source_id' => $sourceId,
                'artist'    => trim(html_entity_decode($artist, ENT_QUOTES)),
                'title'     => trim(html_entity_decode($title, ENT_QUOTES)),
                'duration'  => (int) $duration,
                'date'      => $date,
                'mp3'       => $mp3,
            ];

            if (isset($item->album)) {
                $itemData = array_merge($itemData, [
                    'album' => $item->album->title,
                    'cover_url' => $item->album->thumb->photo_600,
                ]);
            }

            array_push($data, $itemData);
        }

        return $data;
    }

    /**
     * Tries to get mp3 url bounded to this machines IP when proxy enabled, to avoid downloading mp3 via proxy.
     * Uses VK's bug/hack which leaks new mp3 url for requester's IP.
     *
     * @param array $item audio item
     *
     * @return bool has been optimized or not
     */
    public function optimizeMp3Url(&$item)
    {
        if (! env('PROXY_ENABLE', false)) {
            return false;
        }

        try {
            $locations = get_headers($item['mp3'], 1)['Location'];
            $url = array_last($locations);
            if (starts_with($url, 'https://vk.com/err404.php')) {
                return false;
            } else {
                $item['mp3'] = $url;

                return true;
            }
        } catch (\Exception $e) {
            \Log::error('Exception while trying to optimize url', [$item, $e]);

            return false;
        }
    }
}

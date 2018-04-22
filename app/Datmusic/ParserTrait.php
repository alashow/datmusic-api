<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Psr\Http\Message\ResponseInterface;

trait ParserTrait
{
    private $AUDIO_ITEM_INDEX_ID = 0;
    private $AUDIO_ITEM_INDEX_OWNER_ID = 1;
    private $AUDIO_ITEM_INDEX_URL = 2;
    private $AUDIO_ITEM_INDEX_EXTRAS = 12;
    private $AUDIO_ITEM_INDEX_TITLE = 3;
    private $AUDIO_ITEM_INDEX_PERFORMER = 4;
    private $AUDIO_ITEM_INDEX_DURATION = 5;

    private $getAudiosLimit = 10;

    /**
     * Maps response to audio items.
     *
     * @param ResponseInterface $response
     *
     * @return array
     */
    public function getAudioItems($response)
    {
        $audios = json_decode((string) $response->getBody())->list;

        $data = [];
        foreach ($audios as $item) {
            $id = $item[$this->AUDIO_ITEM_INDEX_ID];
            $userId = $item[$this->AUDIO_ITEM_INDEX_OWNER_ID];
            $artist = $item[$this->AUDIO_ITEM_INDEX_PERFORMER];
            $title = $item[$this->AUDIO_ITEM_INDEX_TITLE];
            $duration = $item[$this->AUDIO_ITEM_INDEX_DURATION];
            $sourceId = sprintf('%s_%s', $userId, $id);

            $hash = hash(config('app.hash.id'), $sourceId);

            array_push($data, [
                'id'        => $hash,
                'source_id' => $sourceId,
                'artist'    => trim(html_entity_decode($artist, ENT_QUOTES)),
                'title'     => trim(html_entity_decode($title, ENT_QUOTES)),
                'duration'  => (int) $duration,
            ]);
        }

        // prefetch mp3 urls of first 2 batches of audios
        $limit = $this->getAudiosLimit;
        for ($i = 0; $i < min(count($data), $limit * 2); $i += $limit) {
            $urls = $this->getUrlsForAudios(array_slice($data, $i, $limit));
            for ($j = 0; $j < count($urls); $j++) {
                $data[$j + $i]['mp3'] = $urls[$j];
            }
        }

        return $data;
    }

    /**
     * @param array $audios , max {@link #$getAudiosLimit}
     *
     * @return array with mp3 urls
     */
    public function getUrlsForAudios($audios)
    {
        if (count($audios) > $this->getAudiosLimit) {
            throw new \RuntimeException("Audios count must not be more than {$this->getAudiosLimit}");
        }

        $ids = array_map(function ($item) use ($audios) {
            return $item['source_id'];
        }, $audios);
        $ids = implode(',', $ids);
        $response = httpClient()->get('api.php', [
                'query' => [
                    'key'    => config('app.auth.ya_key'),
                    'method' => 'get.audio',
                    'ids'    => $ids,
                ],
            ]
        );

        $audios = json_decode((string) $response->getBody());

        return array_map(function ($item) {
            return [$item[$this->AUDIO_ITEM_INDEX_URL], $item[$this->AUDIO_ITEM_INDEX_EXTRAS]];
        }, $audios);
    }

    /**
     * @param $audio
     *
     * @return string mp3 url
     */
    public function getUrlForAudio($audio)
    {
        return $this->getUrlsForAudios([$audio])[0];
    }

    /**
     * Verifies audio item contains url, fetches it if it doesn't.
     * Also tries to get cdn-ed url.
     *
     * @param $item
     */
    public function verifyMp3Url(&$item)
    {
        // fetch url if not pre-fetched
        if (! isset($item['mp3'])) {
            $item['mp3'] = $this->getUrlForAudio($item);
        }
        if (is_array($item['mp3'])) {
            try {
                $item['mp3'] = get_headers($item['mp3'][1], 1)['Location'][2];
            } catch (\Exception $e) {
                $item['mp3'] = $item['mp3'][0];
            }
        }
    }
}

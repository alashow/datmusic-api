<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use PHPHtmlParser\Dom;
use Psr\Http\Message\ResponseInterface;

trait ParserTrait
{
    /**
     * Parses response html for audio items, saves it in cache and returns parsed array.
     *
     * @param ResponseInterface $response
     *
     * @return array
     */
    public static function getAudioItems($response)
    {
        $dom = new Dom();
        $dom->load((string) $response->getBody());

        $items = $dom->find('.audio_item');
        $data = [];

        foreach ($items as $item) {
            $audio = new Dom();
            $audio->load($item->innerHtml);

            $id = explode('_search', $item->getAttribute('data-id'))[0];
            $artist = $audio->find('.ai_artist')->text(true);
            $title = $audio->find('.ai_title')->text(true);
            $duration = $audio->find('.ai_dur')->getAttribute('data-dur');

            if (env('DATMUSIC_MP3_URL_DECODER', false)) {
                $decoder = new VkMp3Decoder($audio->find('input[type=hidden]')->value);
                $mp3 = $decoder->decodeMp3Url();
            } else {
                $mp3 = $audio->find('input[type=hidden]')->value;
            }

            $hash = hash(config('app.hash.id'), $id);

            array_push($data, [
                'id'       => $hash,
                'artist'   => trim(html_entity_decode($artist, ENT_QUOTES)),
                'title'    => trim(html_entity_decode($title, ENT_QUOTES)),
                'duration' => (int) $duration,
                'mp3'      => $mp3,
            ]);
        }

        return $data;
    }
}

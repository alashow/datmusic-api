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
            $id = $item->id;
            $userId = $item->owner_id;
            $sourceId = sprintf('%s_%s', $userId, $id);
            $genreId = $item->track_genre_id;
            $artist = $item->artist;
            $title = $item->title;
            $duration = $item->duration;
            $date = $item->date;
            $mp3 = $item->url;

            $hash = hash(config('app.hash.id'), $sourceId);

            array_push($data, [
                'id'         => $hash,
                'source_id'  => $sourceId,
                'artist'     => trim(html_entity_decode($artist, ENT_QUOTES)),
                'title'      => trim(html_entity_decode($title, ENT_QUOTES)),
                'duration'   => (int) $duration,
                'date'       => $date,
                'genre_id'   => $genreId,
                'mp3'        => $mp3,
            ]);
        }

        return $data;
    }
}

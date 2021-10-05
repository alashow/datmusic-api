<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Controllers;

use App\Util\Scanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Lumen\Http\Redirector;

class CoverController extends ApiController
{
    /**
     * Returns cover image url or 404 if fails.
     *
     * @param  string  $key
     * @param  string  $id
     * @param  string|null  $size
     * @return Redirector|JsonResponse
     */
    public function cover(string $key, string $id, string $size = null)
    {
        $size = Scanner::validateSize($size);

        $audio = $this->getAudio($key, $id);
        if ($audio != null) {
            $imageUrl = covers()->getCover($audio, $size);
            if ($imageUrl) {
                return redirect($imageUrl);
            }
        }

        return notFoundResponse();
    }

    /**
     * Returns image of the artist or 404 if fails to find it.
     *
     * @param  string  $artist
     * @param  string|null  $size
     * @return JsonResponse|RedirectResponse
     */
    public function artistImage(string $artist, string $size = null)
    {
        $size = Scanner::validateSize($size);
        $imageUrl = covers()->getArtistImage(urldecode($artist), $size);

        if ($imageUrl) {
            return redirect($imageUrl);
        } else {
            return notFoundResponse();
        }
    }
}

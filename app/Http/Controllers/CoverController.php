<?php
/**
 * Copyright (c) 2018  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Laravel\Lumen\Http\Redirector;

class CoverController extends ApiController
{
    /**
     * Returns cover image url or 404 if fails.
     *
     * @param $key
     * @param $id
     *
     * @return Redirector|JsonResponse
     */
    public function cover($key, $id)
    {
        $audio = $this->getAudio($key, $id);
        if ($audio != null) {
            $imageUrl = covers()->getCover($audio);
            if ($imageUrl) {
                return redirect($imageUrl);
            }
        }

        return notFoundResponse();
    }
}

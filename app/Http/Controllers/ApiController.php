<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Controllers;

use App\Datmusic\DeemixTrait;
use App\Datmusic\DownloaderTrait;
use App\Datmusic\SearchesTrait;
use Illuminate\Http\JsonResponse;

class ApiController extends Controller
{
    use DownloaderTrait, SearchesTrait, DeemixTrait;

    /**
     * ApiController constructor.
     */
    public function __construct()
    {
        $this->bootSearches();
        $this->bootDownloader();
    }

    /**
     * Just response status.
     *
     * @return JsonResponse
     */
    public function index()
    {
        return okResponse();
    }
}

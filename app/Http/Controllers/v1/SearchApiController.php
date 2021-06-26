<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Controllers\v1;

use App\Datmusic\SearchesTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchApiController extends Controller
{
    use SearchesTrait {
        search as public searchTrait;
    }

    /**
     * ApiController constructor.
     */
    public function __construct()
    {
        $this->bootSearches();
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

    public function search(Request $request)
    {
        return okResponse($this->searchTrait($request));
    }
}

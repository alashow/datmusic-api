<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Controllers;

use App\Datmusic\SearchesTrait;
use App\Datmusic\DownloaderTrait;

class ApiController extends Controller
{
    use SearchesTrait, DownloaderTrait;

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
     * @return array
     */
    public function index()
    {
        return $this->ok();
    }

    /**
     * @param array $data
     * @param int   $status
     * @param array $headers
     *
     * @return array
     */
    private function ok($data = null, $status = 200, $headers = [])
    {
        return $this->response('ok', $data, null, $status, $headers);
    }

    /**
     * @param array $error
     * @param int   $status
     * @param array $headers
     *
     * @return array
     */
    private function error($error = null, $status = 200, $headers = [])
    {
        return $this->response('error', null, $error, $status, $headers);
    }

    private function response($status = 'ok', $data = null, $error = null, $httpStatus, $headers = [])
    {
        $result = ['status' => $status];
        if (! is_null($data)) {
            $result = array_merge($result, ['data' => $data]);
        }
        if (! is_null($error)) {
            $result = array_merge($result, ['error' => $error]);
        }

        return response()->json($result, $httpStatus, $headers);
    }
}

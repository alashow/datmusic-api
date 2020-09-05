<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Http\Controllers;

use App\Datmusic\DownloaderTrait;
use App\Datmusic\SearchesTrait;
use Illuminate\Http\JsonResponse;

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
     * @return JsonResponse
     */
    protected function ok($data = null, $status = 200, $headers = [])
    {
        return $this->response('ok', $data, null, $status, $headers);
    }

    /**
     * @param string $message
     *
     * @return JsonResponse
     */
    protected function notFound($message = 'Not found')
    {
        return $this->error(['message' => $message], 404);
    }

    /**
     * @param array $error
     * @param int   $status
     * @param array $headers
     *
     * @return JsonResponse
     */
    protected function error($error = null, $status = 200, $headers = [])
    {
        return $this->response('error', null, $error, $status, $headers);
    }

    /**
     * @param string $status
     * @param array   $data
     * @param array   $error
     * @param int    $httpStatus
     * @param array  $headers
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function response($status = 'ok', $data = null, $error = null, $httpStatus = 200, $headers = [])
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

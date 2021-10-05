<?php

use App\Services\CoverArtClient;
use App\Services\SpotifyClient;
use App\Util\Logger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Psr\Http\Message\ResponseInterface;

function buildHttpProxyString()
{
    $proxy = sprintf('%s://', env('PROXY_METHOD'));
    if (! empty(env('PROXY_USERNAME')) && ! empty(env('PROXY_PASSWORD'))) {
        $proxy .= sprintf('%s:%s@', env('PROXY_USERNAME'), env('PROXY_PASSWORD'));
    }
    $proxy .= sprintf('%s:%s', env('PROXY_IP'), env('PROXY_PORT'));

    return $proxy;
}

/**
 * @return GuzzleHttp\Client Guzzle http client
 */
function httpClient()
{
    return app('httpClient')->getClient();
}

/**
 * @return GuzzleHttp\Client Guzzle vk http client
 */
function vkClient()
{
    return app('vkClient')->getClient();
}

/**
 * @return GuzzleHttp\Client Guzzle deemix http client
 */
function deemixClient()
{
    return app('deemixClient')->getClient();
}

/**
 * @return SpotifyClient
 */
function scanners()
{
    return app('scanners');
}

/**
 * @return CoverArtClient
 */
function covers()
{
    return app('coverArtClient');
}

/**
 * @return Logger logger instance
 */
function logger()
{
    return app('logger');
}

/**
 * Function: sanitize (from Laravel)
 * Returns a sanitized string, typically for URLs.
 * Parameters:.
 *
 * @param $string          - The string to sanitize.
 * @param $force_lowercase - Force the string to lowercase?
 * @param $anal            - If set to *true*, will remove all non-alphanumeric characters.
 * @param $trunc           - Number of characters to truncate to (default 100, 0 to disable).
 * @return string sanitized string
 */
function sanitize($string, $force_lowercase = true, $anal = false, $trunc = 100)
{
    $strip = [
        '~', '`', '!', '@', '#', '$', '%', '^', '&', '*', '(', ')', '_', '=', '+', '[', '{', ']',
        '}', '\\', '|', ';', ':', '"', "'", '&#8216;', '&#8217;', '&#8220;', '&#8221;', '&#8211;',
        '&#8212;', '—', '–', ',', '<', '>', '/', '?',
    ];
    $clean = trim(str_replace($strip, '', strip_tags($string)));
    // $clean = preg_replace('/\s+/', "-", $clean);
    $clean = ($anal ? preg_replace('/[^a-zA-Z0-9]/', '', $clean) : $clean);
    $clean = ($trunc ? substr($clean, 0, $trunc) : $clean);

    return ($force_lowercase) ? (function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean) : $clean;
}

/**
 * Build full url. Prepends APP_URL to given string.
 *
 * @param $path
 * @return string
 */
function fullUrl($path)
{
    return sprintf('%s/%s', env('APP_URL'), $path);
}

/**
 * @return string random query
 */
function randomQuery()
{
    $randomArray = config('app.random_queries');
    $randomIndex = array_rand($randomArray);

    return $randomArray[$randomIndex];
}

/**
 * @param  ResponseInterface  $response
 * @return stdClass
 */
function as_json($response)
{
    return json_decode((string) $response->getBody());
}

/**
 * @param  mixed|null  $data
 * @param  string|null  $dataFieldName
 * @param  int  $status
 * @param  array  $headers
 * @return JsonResponse
 */
function okResponse($data = null, string $dataFieldName = null, int $status = 200, array $headers = [])
{
    return json_response('ok', $data, null, $status, $headers, $dataFieldName);
}

/**
 * @param  string  $message
 * @return JsonResponse
 */
function notFoundResponse(string $message = 'Not found')
{
    return errorResponse(['message' => $message], 404);
}

/**
 * @param  array|null  $error
 * @param  int  $status
 * @param  array  $headers
 * @return JsonResponse
 */
function errorResponse(array $error = null, int $status = 200, array $headers = [])
{
    return json_response('error', null, $error, $status, $headers);
}

/**
 * @param  string  $status
 * @param  array|mixed|null  $data
 * @param  array|null  $error
 * @param  int  $httpStatus
 * @param  array  $headers
 * @param  string|null  $dataFieldName
 * @return JsonResponse
 *
 * @throws Exception if $data is not array and dataFieldName is null
 */
function json_response(string $status = 'ok', $data = null, array $error = null, int $httpStatus = 200, array $headers = [], string $dataFieldName = null)
{
    $result = ['status' => $status];
    if (! is_null($data)) {
        if ($dataFieldName != null) {
            $result = array_merge($result, ['data' => [$dataFieldName => $data]]);
        } else {
            if (is_array($data)) {
                $result = array_merge($result, ['data' => $data]);
            } else {
                throw new Exception();
            }
        }
    }
    if (! is_null($error)) {
        $result = array_merge($result, ['error' => $error]);
    }

    return response()->json($result, $httpStatus, $headers);
}

/**
 * Get param from the given request for given possible keys.
 *
 * @param  Request  $request
 * @param  mixed  ...$keys
 * @return mixed|null
 */
function getPossibleKeys(Request $request, ...$keys)
{
    foreach ($keys as $key) {
        if ($request->has($key)) {
            return $request->get($key);
        }
    }

    return null;
}

function getRandomWeightedElement(array $weightedValues)
{
    $rand = mt_rand(1, (int) array_sum(array_values($weightedValues)));

    foreach ($weightedValues as $key => $value) {
        $rand -= $value;
        if ($rand <= 0) {
            return $key;
        }
    }
}

/**
 * Get query param from given request.
 *
 * @param  Request  $request
 * @return string
 */
function getQuery(Request $request)
{
    return trim(getPossibleKeys($request, 'q', 'query'));
}

/**
 * Get page param from given request.
 *
 * @param  Request  $request
 * @return int
 */
function getPage(Request $request)
{
    return abs(intval($request->get('page')));
}

function subPathForHash($hash)
{
    return sprintf('%s/%s', substr($hash, 0, 2), substr($hash, 2, 2));
}

/**
 * Logs captcha errors for later analysis.
 *
 * @param  Request  $request
 * @param  array  $captcha  captcha info
 * @param  stdClass  $error
 */
function reportCaptchaLock(Request $request, array $captcha, stdClass $error)
{
    $firstAttempt = $request->has('captcha_key') ? 'false' : 'true';
    logger()->captchaLock($captcha['captcha_index'], $firstAttempt, getQuery($request), $captcha['captcha_id']);
}

/**
 * Logs captcha responses for later analysis.
 *
 * @param  Request  $request
 */
function reportCaptchaLockRelease(Request $request)
{
    $captchaIndex = $request->get('captcha_index');
    $captchaKey = $request->get('captcha_key');
    $captchaId = $request->get('captcha_id');
    logger()->captchaSolved($captchaIndex, $captchaKey, $captchaId);
}

if (! function_exists('get_file_type')) {
    function get_mime_type($filePath)
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $fileType = finfo_file($finfo, $filePath);
        finfo_close($finfo);

        return $fileType;
    }
}

function isMimeTypeAudio($mimeType)
{
    return in_array($mimeType, ['audio/mpeg', 'application/octet-stream']);
}

function stdToArray($std)
{
    return json_decode(json_encode($std), true);
}

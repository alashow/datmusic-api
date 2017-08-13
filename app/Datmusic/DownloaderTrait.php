<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use Aws\S3\S3Client;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

trait DownloaderTrait
{
    /**
     * @var S3Client
     */
    protected $s3Client;
    /**
     * @var resource S3 atream context resource
     */
    protected $s3StreamContext;
    /**
     * @var bool is using s3 as storage
     */
    protected $isS3 = false;

    /**
     * DownloaderTrait constructor.
     */
    public function bootDownloader()
    {
        if (config('app.aws.enabled')) {
            $this->s3Client = new S3Client(config('app.aws.config'));
            $this->s3Client->registerStreamWrapper();
            $this->isS3 = true;
        }
    }

    /**
     * Get size of audio file in bytes.
     *
     * @param $key
     * @param $id
     *
     * @return mixed
     */
    public function bytes($key, $id)
    {
        logger()->log('Bytes', $key, $id);

        $cacheKey = "bytes_$id";

        // get from cache or store in cache and return value
        return Cache::rememberForever($cacheKey, function () use ($key, $id) {
            $item = $this->getAudio($key, $id);

            $response = httpClient()->head($item['mp3']);

            return $response->getHeader('Content-Length')[0];
        });
    }

    /**
     * Just like download but with stream enabled.
     *
     * @param string $key
     * @param string $id
     *
     * @return mixed
     */
    public function stream($key, $id)
    {
        return $this->download($key, $id, true);
    }

    /**
     * Just like download but with bitrate converting enabled.
     *
     * @param string $key
     * @param string $id
     * @param int    $bitrate
     *
     * @return mixed
     */
    public function bitrateDownload($key, $id, $bitrate)
    {
        return $this->download($key, $id, false, $bitrate);
    }

    /**
     * Serves given audio item or aborts with 404 if not found.
     *
     * @param string $key
     * @param string $id
     * @param bool   $stream
     * @param int    $bitrate
     *
     * @return mixed
     */
    public function download($key, $id, $stream = false, $bitrate = -1)
    {
        if (! in_array($bitrate, config('app.conversion.allowed'))) {
            $bitrate = -1;
        }

        // filename including extension and local file path
        list($fileName, $localPath) = $this->buildFilePathsForId($id);

        // build full path from file path
        if ($this->isS3) {
            $s3PathWithFolder = sprintf(config('app.aws.paths.mp3'), $fileName);
            $path = sprintf('s3://%s/%s', config('app.aws.bucket'), $s3PathWithFolder);
        } else {
            $path = $localPath;
        }

        // check bucket for file and redirect if exists
        if ($this->isS3 && @file_exists($this->formatPathWithBitrate($path, $bitrate))) {
            logger()->log('S3.Cache', $path, $bitrate);

            return redirect($this->buildS3Url($this->formatPathWithBitrate($fileName, $bitrate)));
        } else {
            if (@file_exists($path)) {
                $item = $this->getAudioCache($id);
                // try looking in search cache if not found
                if (is_null($item)) {
                    $item = $this->getAudio($key, $id, false);
                }
                $name = ! is_null($item) ? $this->getFormattedName($item) : "$id.mp3";

                $this->tryToConvert($bitrate, $path, $localPath, $fileName, $name);

                return $this->downloadLocal($path, $fileName, $key, $id, $name, $stream, true);
            }
        }

        $item = $this->getAudio($key, $id);
        $name = $this->getFormattedName($item);

        if ($this->isS3) {
            $this->s3StreamContext = $this->buildS3StreamContextOptions($name);
        }

        if ($this->downloadFile($item['mp3'], $path)) {
            $this->tryToConvert($bitrate, $path, $localPath, $fileName, $name);

            if ($this->isS3) {
                return redirect($this->buildS3Url($fileName));
            } else {
                return $this->downloadLocal($path, $fileName, $key, $id, $name, $stream, false);
            }
        } else {
            abort(404);
        }

        return 0;
    }

    /**
     * Download/Stream local file.
     *
     * @param $path     string full path
     * @param $fileName string file name
     * @param $key      string search key
     * @param $id       string audio id
     * @param $name     string download response name
     * @param $stream   boolean  is stream
     * @param $cache    boolean is cache
     *
     * @return \Illuminate\Http\RedirectResponse|\Laravel\Lumen\Http\Redirector|BinaryFileResponse
     */
    private function downloadLocal($path, $fileName, $key, $id, $name, $stream, $cache)
    {
        if ($stream) {
            $this->checkIsBadMp3($path);
            logger()->stream($cache, $key, $id);

            return redirect("mp3/$fileName");
        } else {
            logger()->download($cache, $key, $id);

            return $this->downloadResponse($path, $name);
        }
    }

    /**
     * Try to convert mp3 if possible, alters given path and file path if succeeds.
     *
     * @param $bitrate   int bitrate
     * @param $path      string path
     * @param $localPath string local file path
     * @param $fileName  string file name
     * @param $name      string file name (logging)
     */
    private function tryToConvert($bitrate, &$path, $localPath, &$fileName, &$name)
    {
        $convertResult = $this->bitrateConvert($bitrate, $path, $localPath, $fileName);

        if ($convertResult != false) {
            list($fileName, $path) = $convertResult;
            logger()->convert($name, $bitrate);
            $name = str_replace('.mp3', " ($bitrate).mp3", $name);
        }
    }

    /**
     * @param $bitrate
     * @param $path
     * @param $localPath
     * @param $fileName
     *
     * @return array|bool
     */
    private function bitrateConvert($bitrate, $path, $localPath, $fileName)
    {
        if ($bitrate > 0) {
            // Download to local if s3 mode and upload converted one to s3
            // Change path only if already converted or conversion function returns true

            $pathConverted = $this->formatPathWithBitrate($localPath, $bitrate);
            $fileNameConverted = $this->formatPathWithBitrate($fileName, $bitrate);

            // s3 mode
            if ($this->isS3) {
                // download file from s3 to local
                // continue only if download succeeds
                $convertable = $this->downloadFile($this->buildS3Url($fileName), $localPath);
            } else {
                $convertable = true;
            }

            if ($convertable) {
                if (file_exists($pathConverted)
                    || $this->convertMp3Bitrate($bitrate, $localPath, $pathConverted)
                ) {
                    // upload converted file
                    if ($this->isS3) {
                        $converted = fopen($pathConverted, 'r');
                        $s3ConvertedPath = $this->formatPathWithBitrate($path, $bitrate);
                        $s3Stream = fopen($s3ConvertedPath, 'w', false, $this->s3StreamContext);

                        // if upload succeeds
                        if (stream_copy_to_stream($converted, $s3Stream) != false) {
                            $convertedPaths = [$fileNameConverted, $path];
                        }
                    } else {
                        $convertedPaths = [$fileNameConverted, $pathConverted];
                    }
                }
            }
        }

        return isset($convertedPaths) ? $convertedPaths : false;
    }

    /**
     * Formats name, appends mp3, ascii-fy and remove bad characters.
     *
     * @param array $item
     *
     * @return string formatted name
     */
    public function getFormattedName($item)
    {
        $name = sprintf('%s - %s', $item['artist'], $item['title']);
        $name = Str::ascii($name);
        $name = sanitize($name, false, false);
        $name = sprintf('%s.mp3', $name);

        return $name;
    }

    /**
     * Build file name and full path for given audio id.
     *
     * @param string $id audio id
     *
     * @return array 0 - file name, 1 - full path
     */
    public function buildFilePathsForId($id)
    {
        $name = sprintf('%s.mp3', hash(config('app.hash.mp3'), $id));
        $path = sprintf('%s/%s', config('app.paths.mp3'), $name);
        return [$name, $path];
    }

    /**
     * @param string $path    path to mp3
     * @param int    $bitrate bitrate
     *
     * @return string path_bitrate.mp3 formatted path
     */
    private function formatPathWithBitrate($path, $bitrate)
    {
        if ($bitrate > 0) {
            return str_replace('.mp3', "_$bitrate.mp3", $path);
        } else {
            return $path;
        }
    }

    /**
     * Download given file url to given path.
     *
     * @param string   $url
     * @param string   $path
     * @param resource $context stream context options when opening $path
     *
     * @return bool true if succeeds
     */
    public function downloadFile($url, $path)
    {
        if ($this->s3StreamContext == null) {
            $handle = fopen($path, 'w');
        } else {
            $handle = fopen($path, 'w', false, $this->s3StreamContext);
        }

        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_FILE, $handle);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_FAILONERROR, 1);
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, config('app.downloading.timeout.connection'));
        curl_setopt($curl, CURLOPT_TIMEOUT, config('app.downloading.timeout.execution'));

        if (env('PROXY_ENABLE', false)) {
            curl_setopt($curl, CURLOPT_PROXY, env('PROXY_IP'));
            curl_setopt($curl, CURLOPT_PROXYPORT, env('PROXY_PORT'));
            curl_setopt($curl, CURLOPT_PROXYTYPE, env('PROXY_METHOD'));

            if (! empty(env('PROXY_USERNAME')) && ! empty(env('PROXY_PASSWORD'))) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, sprintf('%s:%s', env('PROXY_USERNAME'), env('PROXY_PASSWORD')));
            }
        }

        curl_exec($curl);

        // if curl had errors
        if (curl_errno($curl) > 0) {
            logger()->log('Download.Fail', curl_errno($curl));

            // remove the file just in case
            @unlink($path);

            return false;
        }

        //close files
        curl_close($curl);
        fclose($handle);

        return true;
    }

    /**
     * Checks given files mime type and abort with 404 if file is not an mp3 file.
     *
     * @param string $path full path of mp3
     */
    public function checkIsBadMp3($path)
    {
        if (! file_exists($path)) {
            logger()->log('Download.Bad.NotFound');

            abort(404);
        }

        // valid mimes
        $validMimes = ['audio/mpeg', 'audio/mp3', 'application/octet-stream'];

        $mime = finfo_file(finfo_open(FILEINFO_MIME_TYPE), $path);
        // checks mime-type with unix file command
        $nativeCheck = function () use ($path) {
            return exec("file -b --mime-type $path");
        };

        $checks = [$mime, $nativeCheck()];

        // md5 hash blacklist of bad mp3's
        $badMp3Hashes = [
            '9d6ddee7a36a6b1b638c2ca1e26ad46e',
            '8efd23e1cf7989a537a8bf0fb3ed7f62',
            '21a9fef2f321de657d7b54985be55888',
        ];
        $badMp3 = in_array(md5_file($path), $badMp3Hashes);

        // if the file is corrupted (mime is wrong) or md5 file is one of the bad mp3s,
        // delete it from storage and return 404
        if ($badMp3 || ! count(array_intersect($checks, $validMimes))) { // if arrays don't have any common values, mp3 is broken.
            logger()->log('Download.Bad.'.($badMp3 ? 'Mp3' : 'Mime'), $path, implode(' ', $checks));

            @unlink($path);
            abort(404);
        }
    }

    /**
     * Force download given file with given name.
     *
     * @param $path string path of the file
     * @param $name string name of the downloading file
     *
     * @return BinaryFileResponse
     */
    public function downloadResponse($path, $name)
    {
        $this->checkIsBadMp3($path);

        $headers = [
            'Cache-Control'     => 'private',
            'Cache-Description' => 'File Transfer',
            'Content-Type'      => 'audio/mpeg',
            'Content-Length'    => filesize($path),
        ];

        return response()->download(
            $path,
            $name,
            $headers
        );
    }

    /**
     * Executes ffmpeg command synchronously for converting given file to given bitrate.
     *
     * @param $bitrate integer, one of $config["allowed_bitrates"]
     * @param $input   string input mp3 file full path
     * @param $output  string output mp3 file full path
     *
     * @return bool is success
     */
    public function convertMp3Bitrate($bitrate, $input, $output)
    {
        $bitrateString = config('app.conversion.allowed_ffmpeg')[array_search($bitrate,
            config('app.conversion.allowed'))];
        $ffmpegPath = config('app.conversion.ffmpeg_path');

        exec("$ffmpegPath -i $input -codec:a libmp3lame $bitrateString $output", $exOutput,
            $result);

        return $result == 0;
    }

    // s3 utils

    /**
     * Builds url with region and bucket name from config.
     *
     * @param string $fileName path to file
     *
     * @return string full url
     */
    private function buildS3Url($fileName)
    {
        if (env('CDN_ROOT_URL', null) !== null) {
            return sprintf('%s%s', env('CDN_ROOT_URL'), $fileName);
        }

        $region = config('app.aws.config.region');
        $bucket = config('app.aws.bucket');
        $path = sprintf(config('app.aws.paths.mp3'), $fileName);

        return "https://s3-$region.amazonaws.com/$bucket/$path";
    }

    /**
     * Builds S3 schema stream context options
     * All options available at http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject.
     *
     * @param string $name Force download file name
     *
     * @return resource
     */
    private function buildS3StreamContextOptions($name)
    {
        return stream_context_create([
            's3' => [
                'ACL'                => 'public-read',
                'ContentType'        => 'audio/mpeg',
                'ContentDisposition' => "attachment; filename=\"$name\"",
                'StorageClass'       => 'STANDARD_IA',
            ],
        ]);
    }
}

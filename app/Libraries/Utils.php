<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

class Utils
{

    /**
     * Function: sanitize (from Laravel)
     * Returns a sanitized string, typically for URLs.
     *
     * Parameters:
     * @param $string - The string to sanitize.
     * @param $force_lowercase - Force the string to lowercase?
     * @param $anal - If set to *true*, will remove all non-alphanumeric characters.
     * @param $trunc - Number of characters to truncate to (default 100, 0 to disable).
     * @return string sanitized string
     */
    public static function sanitize($string, $force_lowercase = true, $anal = false, $trunc = 100)
    {
        $strip = array("~", "`", "!", "@", "#", "$", "%", "^", "&", "*", "(", ")", "_", "=", "+", "[", "{", "]", "}", "\\", "|", ";", ":", "\"", "'", "&#8216;", "&#8217;", "&#8220;", "&#8221;", "&#8211;", "&#8212;", "—", "–", ",", "<", ">", "/", "?");
        $clean = trim(str_replace($strip, "", strip_tags($string)));
        // $clean = preg_replace('/\s+/', "-", $clean);
        $clean = ($anal ? preg_replace("/[^a-zA-Z0-9]/", "", $clean) : $clean);
        $clean = ($trunc ? substr($clean, 0, $trunc) : $clean);
        return ($force_lowercase) ? (function_exists('mb_strtolower')) ? mb_strtolower($clean, 'UTF-8') : strtolower($clean) : $clean;
    }

    public static function url($path)
    {
        return sprintf('%s/%s', env('APP_URL'), $path);
    }

    /**
     * @param string $path path to mp3
     * @param int $bitrate bitrate
     * @return string path_bitrate.mp3 formatted path
     */
    public static function formatPathWithBitrate($path, $bitrate)
    {
        if ($bitrate > 0) {
            return str_replace('.mp3', "_$bitrate.mp3", $path);
        } else {
            return $path;
        }
    }

    /**
     * Builds url with region and bucket name from config
     * @param string $fileName path to file
     * @return string full url
     */
    public static function buildS3Url($fileName)
    {
        $region = config('app.aws.config.region');
        $bucket = config('app.aws.bucket');
        $path = sprintf(config('app.aws.paths.mp3'), $fileName);

        return "https://s3-$region.amazonaws.com/$bucket/$path";
    }

    /**
     * Builds S3 schema stream context options
     * All options available at http://docs.aws.amazon.com/aws-sdk-php/v3/api/api-s3-2006-03-01.html#putobject
     * @param string $name Force download file name
     * @return resource
     */
    public static function buildS3StreamContextOptions($name)
    {
        return stream_context_create([
            's3' => [
                'ACL' => 'public-read',
                'ContentType' => 'audio/mpeg',
                'ContentDisposition' => "attachment; filename=\"$name\""
            ]
        ]);
    }
}